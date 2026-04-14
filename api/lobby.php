<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/multiplayer_settings.php';

const LOBBY_HEARTBEAT_INTERVAL_SECONDS = 15;
const LOBBY_MISSED_HEARTBEAT_LIMIT = 3;
const LOBBY_RECONNECT_GRACE_SECONDS = 60;

function ensureLobbyTables() {
    $db = getDb();
    $db->exec("CREATE TABLE IF NOT EXISTS lobbies (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        code         TEXT    NOT NULL UNIQUE,
        start_title  TEXT    NOT NULL,
        end_title    TEXT    NOT NULL,
        host_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        max_players  INTEGER NOT NULL DEFAULT 8,
        settings_json TEXT,
        status       TEXT    NOT NULL DEFAULT 'waiting',
        created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS lobby_players (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        lobby_id      INTEGER NOT NULL REFERENCES lobbies(id) ON DELETE CASCADE,
        user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        clicks        INTEGER,
        time_seconds  INTEGER,
        path          TEXT,
        last_seen_at  TEXT,
        missed_heartbeats INTEGER NOT NULL DEFAULT 0,
        disconnected_at TEXT,
        joined_at     TEXT    NOT NULL DEFAULT (datetime('now')),
        UNIQUE(lobby_id, user_id)
    )");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_lobby_players_lobby ON lobby_players(lobby_id)');
    ensureLobbyColumn($db, 'settings_json', "ALTER TABLE lobbies ADD COLUMN settings_json TEXT");
    ensureLobbyPlayersColumn($db, 'last_seen_at', "ALTER TABLE lobby_players ADD COLUMN last_seen_at TEXT");
    ensureLobbyPlayersColumn($db, 'missed_heartbeats', "ALTER TABLE lobby_players ADD COLUMN missed_heartbeats INTEGER NOT NULL DEFAULT 0");
    ensureLobbyPlayersColumn($db, 'disconnected_at', "ALTER TABLE lobby_players ADD COLUMN disconnected_at TEXT");
    ensureLobbyPlayersColumn($db, 'replay_ready', "ALTER TABLE lobby_players ADD COLUMN replay_ready INTEGER NOT NULL DEFAULT 0");
}

function ensureLobbyColumn($db, $columnName, $alterSql) {
    $stmt = $db->query("PRAGMA table_info(lobbies)");
    $columns = $stmt ? $stmt->fetchAll() : [];
    foreach ($columns as $col) {
        if (isset($col['name']) && $col['name'] === $columnName) return;
    }
    $db->exec($alterSql);
}

function ensureLobbyPlayersColumn($db, $columnName, $alterSql) {
    $stmt = $db->query("PRAGMA table_info(lobby_players)");
    $columns = $stmt ? $stmt->fetchAll() : [];
    foreach ($columns as $col) {
        if (isset($col['name']) && $col['name'] === $columnName) return;
    }
    $db->exec($alterSql);
}

function lobbySecondsSinceTimestamp($timestamp) {
    if (!$timestamp) return null;
    $ts = strtotime($timestamp . ' UTC');
    if ($ts === false) return null;
    return max(0, time() - $ts);
}

function cleanupLobbyPresenceState($db) {
    $stats = ['disconnect_marked' => 0, 'reconnected' => 0, 'timed_out' => 0];
    $rowsStmt = $db->query("SELECT id, lobby_id, last_seen_at, missed_heartbeats, disconnected_at FROM lobby_players");
    $rows = $rowsStmt ? $rowsStmt->fetchAll() : [];
    foreach ($rows as $row) {
        $missed = 0;
        $inactiveFor = lobbySecondsSinceTimestamp($row['last_seen_at']);
        if ($inactiveFor === null) {
            $missed = LOBBY_MISSED_HEARTBEAT_LIMIT;
        } else {
            $missed = (int)floor($inactiveFor / LOBBY_HEARTBEAT_INTERVAL_SECONDS);
        }
        if ($missed < 0) $missed = 0;

        if ($missed >= LOBBY_MISSED_HEARTBEAT_LIMIT) {
            if (empty($row['disconnected_at'])) {
                $db->prepare("UPDATE lobby_players SET missed_heartbeats = ?, disconnected_at = datetime('now') WHERE id = ?")
                    ->execute([$missed, (int)$row['id']]);
                $stats['disconnect_marked']++;
            } else {
                $discFor = lobbySecondsSinceTimestamp($row['disconnected_at']);
                if ($discFor !== null && $discFor >= LOBBY_RECONNECT_GRACE_SECONDS) {
                    $db->prepare('DELETE FROM lobby_players WHERE id = ?')->execute([(int)$row['id']]);
                    $stats['timed_out']++;
                } else {
                    $db->prepare('UPDATE lobby_players SET missed_heartbeats = ? WHERE id = ?')->execute([$missed, (int)$row['id']]);
                }
            }
        } else {
            if (!empty($row['disconnected_at'])) $stats['reconnected']++;
            $db->prepare('UPDATE lobby_players SET missed_heartbeats = ?, disconnected_at = NULL WHERE id = ?')
                ->execute([$missed, (int)$row['id']]);
        }
    }
    return $stats;
}

function cleanupStaleLobbies() {
    ensureLobbyTables();
    $db = getDb();

    // Waiting lobbies that never started.
    $db->exec("DELETE FROM lobbies
        WHERE status = 'waiting'
          AND created_at < datetime('now', '-2 hours')");

    // Active lobbies with no recent completion should not block forever.
    $db->exec("UPDATE lobbies
        SET status = 'finished'
        WHERE status = 'active'
          AND created_at < datetime('now', '-12 hours')");

    $stats = cleanupLobbyPresenceState($db);

    // If host disappeared, transfer host to oldest present player.
    $hostlessStmt = $db->query("
        SELECT l.id
        FROM lobbies l
        LEFT JOIN lobby_players lp ON lp.lobby_id = l.id AND lp.user_id = l.host_id
        WHERE l.status != 'finished' AND lp.user_id IS NULL
    ");
    $hostless = $hostlessStmt ? $hostlessStmt->fetchAll() : [];
    foreach ($hostless as $row) {
        $lobbyId = (int)$row['id'];
        $nextHostStmt = $db->prepare('SELECT user_id FROM lobby_players WHERE lobby_id = ? ORDER BY joined_at ASC LIMIT 1');
        $nextHostStmt->execute([$lobbyId]);
        $nextHost = (int)$nextHostStmt->fetchColumn();
        if ($nextHost > 0) {
            $db->prepare('UPDATE lobbies SET host_id = ? WHERE id = ?')->execute([$nextHost, $lobbyId]);
        } else {
            $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
        }
    }

    // Active lobbies need at least two present players.
    $underfilledStmt = $db->query("
        SELECT l.id, COUNT(lp.id) AS player_count
        FROM lobbies l
        LEFT JOIN lobby_players lp ON lp.lobby_id = l.id
        WHERE l.status = 'active'
        GROUP BY l.id
        HAVING player_count < 2
    ");
    $underfilled = $underfilledStmt ? $underfilledStmt->fetchAll() : [];
    foreach ($underfilled as $row) {
        $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([(int)$row['id']]);
    }

    // Remove old completed lobbies.
    $db->exec("DELETE FROM lobbies
        WHERE status = 'finished'
          AND created_at < datetime('now', '-14 days')");
    return $stats;
}

function lobbyFetchByCode($db, $code) {
    $stmt = $db->prepare('SELECT * FROM lobbies WHERE code = ?');
    $stmt->execute([$code]);
    return $stmt->fetch();
}

function lobbyPlayerCount($db, $lobbyId) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM lobby_players WHERE lobby_id = ?');
    $stmt->execute([$lobbyId]);
    return (int)$stmt->fetchColumn();
}

function lobbyIsMember($db, $lobbyId, $userId) {
    $stmt = $db->prepare('SELECT 1 FROM lobby_players WHERE lobby_id = ? AND user_id = ?');
    $stmt->execute([$lobbyId, $userId]);
    return (bool)$stmt->fetchColumn();
}

function lobbyPlayersWithNames($db, $lobbyId) {
    $stmt = $db->prepare('
        SELECT lp.user_id, lp.clicks, lp.time_seconds, lp.path, lp.missed_heartbeats, lp.disconnected_at, lp.replay_ready, u.username
        FROM lobby_players lp
        JOIN users u ON u.id = lp.user_id
        WHERE lp.lobby_id = ?
        ORDER BY lp.joined_at ASC
    ');
    $stmt->execute([$lobbyId]);
    return $stmt->fetchAll();
}

function buildLobbyLeaderboard($players, $targetTitle) {
    $rows = [];
    $targetNorm = strtolower(str_replace(' ', '_', (string)$targetTitle));
    foreach ($players as $p) {
        if ($p['clicks'] === null) continue;
        $pathArr = json_decode($p['path'] ?: '[]', true);
        if (!is_array($pathArr)) $pathArr = [];
        $lastTitle = null;
        if (!empty($pathArr)) $lastTitle = strtolower(str_replace(' ', '_', (string)$pathArr[count($pathArr) - 1]));
        $p['reached_target'] = ($lastTitle !== null && $lastTitle === $targetNorm);
        $rows[] = $p;
    }

    $finishers = [];
    $nonFinishers = [];
    foreach ($rows as $row) {
        if (!empty($row['reached_target'])) $finishers[] = $row;
        else $nonFinishers[] = $row;
    }

    $rankedSort = function ($a, $b) {
        $ca = (int)$a['clicks'];
        $cb = (int)$b['clicks'];
        if ($ca !== $cb) return $ca - $cb;
        $ta = (int)$a['time_seconds'];
        $tb = (int)$b['time_seconds'];
        return $ta - $tb;
    };

    usort($finishers, $rankedSort);
    usort($nonFinishers, $rankedSort);

    $board = [];
    if (count($finishers) === 0) {
        // Nobody reached the target: treat as a tie, do not reward "fastest failure".
        foreach ($nonFinishers as $p) {
            $board[] = [
                'rank' => 1,
                'user_id' => (int)$p['user_id'],
                'username' => $p['username'],
                'clicks' => (int)$p['clicks'],
                'time' => (int)$p['time_seconds'],
                'reached_target' => false,
            ];
        }
        return $board;
    }

    $rank = 1;
    foreach ($finishers as $p) {
        $board[] = [
            'rank' => $rank++,
            'user_id' => (int)$p['user_id'],
            'username' => $p['username'],
            'clicks' => (int)$p['clicks'],
            'time' => (int)$p['time_seconds'],
            'reached_target' => true,
        ];
    }
    $dnfRank = $rank;
    foreach ($nonFinishers as $p) {
        $board[] = [
            'rank' => $dnfRank,
            'user_id' => (int)$p['user_id'],
            'username' => $p['username'],
            'clicks' => (int)$p['clicks'],
            'time' => (int)$p['time_seconds'],
            'reached_target' => false,
        ];
    }
    return $board;
}

function formatLobbyResponse($lobby, $userId) {
    $db = getDb();
    $playersRaw = lobbyPlayersWithNames($db, $lobby['id']);
    $players = [];
    foreach ($playersRaw as $p) {
        $graceLeft = null;
        if (!empty($p['disconnected_at'])) {
            $graceLeft = max(0, LOBBY_RECONNECT_GRACE_SECONDS - (lobbySecondsSinceTimestamp($p['disconnected_at']) ?? LOBBY_RECONNECT_GRACE_SECONDS));
        }
        $players[] = [
            'user_id' => (int)$p['user_id'],
            'username' => $p['username'],
            'clicks' => $p['clicks'] !== null ? (int)$p['clicks'] : null,
            'time' => $p['time_seconds'] !== null ? (int)$p['time_seconds'] : null,
            'submitted' => $p['clicks'] !== null,
            'replay_ready' => !empty($p['replay_ready']),
            'presence' => [
                'missed_heartbeats' => (int)$p['missed_heartbeats'],
                'is_in_reconnect_grace' => !empty($p['disconnected_at']),
                'grace_seconds_left' => $graceLeft,
            ],
        ];
    }
    $out = [
        'code' => $lobby['code'],
        'status' => $lobby['status'],
        'start_title' => $lobby['start_title'],
        'end_title' => $lobby['end_title'],
        'host_id' => (int)$lobby['host_id'],
        'max_players' => (int)$lobby['max_players'],
        'is_host' => ((int)$lobby['host_id'] === (int)$userId),
        'settings' => decodeMultiplayerSettings(isset($lobby['settings_json']) ? $lobby['settings_json'] : null),
        'can_edit_settings' => ($lobby['status'] === 'waiting' && (int)$lobby['host_id'] === (int)$userId),
        'players' => $players,
        'presence_config' => [
            'heartbeat_interval_seconds' => LOBBY_HEARTBEAT_INTERVAL_SECONDS,
            'missed_heartbeat_limit' => LOBBY_MISSED_HEARTBEAT_LIMIT,
            'reconnect_grace_seconds' => LOBBY_RECONNECT_GRACE_SECONDS,
        ],
    ];
    if ($lobby['status'] === 'finished') {
        $out['leaderboard'] = buildLobbyLeaderboard($playersRaw, $lobby['end_title']);
    }
    return $out;
}

function createLobby($startTitle, $endTitle, $hostId, $maxPlayers = 8, $settings = null) {
    cleanupStaleLobbies();
    $startTitle = trim($startTitle);
    $endTitle = trim($endTitle);
    if (empty($startTitle) || empty($endTitle)) {
        return ['error' => 'Both start and end titles are required.'];
    }
    if (strtolower($startTitle) === strtolower($endTitle)) {
        return ['error' => 'Start and end must be different.'];
    }
    $maxPlayers = max(2, min(8, (int)$maxPlayers));

    $normalizedSettings = normalizeMultiplayerSettings($settings);
    ensureLobbyTables();
    $db = getDb();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = generateUniqueCode();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare('INSERT INTO lobbies (code, start_title, end_title, host_id, max_players, settings_json) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$code, $startTitle, $endTitle, $hostId, $maxPlayers, encodeMultiplayerSettings($normalizedSettings)]);
            $lobbyId = (int)$db->lastInsertId();
            $db->prepare("INSERT INTO lobby_players (lobby_id, user_id, last_seen_at) VALUES (?, ?, datetime('now'))")->execute([$lobbyId, $hostId]);
            $db->commit();

            $lobby = lobbyFetchByCode($db, $code);
            return formatLobbyResponse($lobby, $hostId);
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            if (strpos($e->getMessage(), 'UNIQUE') === false) throw $e;
        }
    }
    return ['error' => 'Could not generate a unique lobby code. Try again.'];
}

function updateLobbySettings($code, $userId, $settings) {
    cleanupStaleLobbies();
    $code = strtoupper(trim($code));
    ensureLobbyTables();
    $db = getDb();

    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return ['error' => 'Lobby not found.'];
    if ((int)$lobby['host_id'] !== (int)$userId) {
        return ['error' => 'Only the host can update settings.'];
    }
    if ($lobby['status'] !== 'waiting') {
        return ['error' => 'Settings are locked after the lobby starts.'];
    }

    $normalizedSettings = normalizeMultiplayerSettings($settings);
    $nextStart = trim((string)$lobby['start_title']);
    $nextEnd = trim((string)$lobby['end_title']);
    if ($normalizedSettings['mode'] === 'custom') {
        $candidateStart = isset($normalizedSettings['customStartTitle']) ? trim((string)$normalizedSettings['customStartTitle']) : '';
        $candidateEnd = isset($normalizedSettings['customEndTitle']) ? trim((string)$normalizedSettings['customEndTitle']) : '';
        if ($candidateStart !== '') $nextStart = $candidateStart;
        if ($candidateEnd !== '') $nextEnd = $candidateEnd;
        if ($nextStart === '' || $nextEnd === '') {
            return ['error' => 'Custom mode requires both start and target titles.'];
        }
        if (strcasecmp($nextStart, $nextEnd) === 0) {
            return ['error' => 'Custom start and target must be different.'];
        }
        $normalizedSettings['customStartTitle'] = $nextStart;
        $normalizedSettings['customEndTitle'] = $nextEnd;
    } else {
        $normalizedSettings['customStartTitle'] = null;
        $normalizedSettings['customEndTitle'] = null;
    }

    $db->prepare('UPDATE lobbies SET settings_json = ?, start_title = ?, end_title = ? WHERE code = ?')
        ->execute([encodeMultiplayerSettings($normalizedSettings), $nextStart, $nextEnd, $code]);

    $lobby = lobbyFetchByCode($db, $code);
    return formatLobbyResponse($lobby, $userId);
}

function joinLobby($code, $userId) {
    cleanupStaleLobbies();
    $code = strtoupper(trim($code));
    ensureLobbyTables();
    $db = getDb();

    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return ['error' => 'Lobby not found.'];

    if ($lobby['status'] !== 'waiting') {
        return ['error' => 'This lobby is no longer accepting players.'];
    }

    $lobbyId = (int)$lobby['id'];
    if (lobbyIsMember($db, $lobbyId, $userId)) {
        touchLobbyPresence($code, $userId);
        return formatLobbyResponse($lobby, $userId);
    }

    try {
        $db->beginTransaction();

        $fresh = lobbyFetchByCode($db, $code);
        if (!$fresh || $fresh['status'] !== 'waiting') {
            if ($db->inTransaction()) $db->rollBack();
            return ['error' => 'This lobby is no longer accepting players.'];
        }

        $count = lobbyPlayerCount($db, $lobbyId);
        if ($count >= (int)$fresh['max_players']) {
            if ($db->inTransaction()) $db->rollBack();
            return ['error' => 'Lobby is full.'];
        }

        $db->prepare("INSERT INTO lobby_players (lobby_id, user_id, last_seen_at) VALUES (?, ?, datetime('now'))")->execute([$lobbyId, $userId]);
        $db->commit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        if (strpos($e->getMessage(), 'UNIQUE') !== false) {
            return formatLobbyResponse($lobby, $userId);
        }
        throw $e;
    }

    $lobby = lobbyFetchByCode($db, $code);
    return formatLobbyResponse($lobby, $userId);
}

function startLobby($code, $userId) {
    cleanupStaleLobbies();
    $code = strtoupper(trim($code));
    ensureLobbyTables();
    $db = getDb();

    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return ['error' => 'Lobby not found.'];
    if ((int)$lobby['host_id'] !== (int)$userId) {
        return ['error' => 'Only the host can start the lobby.'];
    }
    if ($lobby['status'] !== 'waiting') {
        return ['error' => 'Lobby has already started.'];
    }

    try {
        $db->beginTransaction();
        $fresh = lobbyFetchByCode($db, $code);
        if (!$fresh || $fresh['status'] !== 'waiting') {
            if ($db->inTransaction()) $db->rollBack();
            return ['error' => 'Lobby has already started.'];
        }

        $count = lobbyPlayerCount($db, (int)$fresh['id']);
        if ($count < 2) {
            if ($db->inTransaction()) $db->rollBack();
            return ['error' => 'Need at least two players to start.'];
        }

        $stmt = $db->prepare("UPDATE lobbies SET status = 'active' WHERE code = ? AND status = 'waiting'");
        $stmt->execute([$code]);
        if ($stmt->rowCount() === 0) {
            if ($db->inTransaction()) $db->rollBack();
            return ['error' => 'Could not start lobby.'];
        }
        $db->prepare('UPDATE lobby_players SET replay_ready = 0 WHERE lobby_id = ?')->execute([(int)$fresh['id']]);
        $db->commit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    $lobby = lobbyFetchByCode($db, $code);
    touchLobbyPresence($code, $userId);
    return formatLobbyResponse($lobby, $userId);
}

function submitLobbyResult($code, $userId, $clicks, $time, $path) {
    cleanupStaleLobbies();
    $code = strtoupper(trim($code));
    ensureLobbyTables();
    $db = getDb();

    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return ['error' => 'Lobby not found.'];
    if ($lobby['status'] === 'finished') {
        return ['error' => 'Lobby is already finished.'];
    }
    if ($lobby['status'] !== 'active') {
        return ['error' => 'Game has not started yet.'];
    }

    $lobbyId = (int)$lobby['id'];
    if (!lobbyIsMember($db, $lobbyId, $userId)) {
        return ['error' => 'You are not in this lobby.'];
    }

    $pathJson = json_encode(is_array($path) ? $path : []);
    $safeClicks = max(0, (int)$clicks);
    $safeTime = max(0, (int)$time);

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE lobby_players SET clicks = ?, time_seconds = ?, path = ?, last_seen_at = datetime('now') WHERE lobby_id = ? AND user_id = ?");
        $stmt->execute([$safeClicks, $safeTime, $pathJson, $lobbyId, $userId]);

        $stmt = $db->prepare('
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN clicks IS NOT NULL THEN 1 ELSE 0 END) AS submitted
            FROM lobby_players WHERE lobby_id = ?
        ');
        $stmt->execute([$lobbyId]);
        $row = $stmt->fetch();
        if ((int)$row['total'] > 0 && (int)$row['submitted'] === (int)$row['total']) {
            $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
        }
        $db->commit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    $lobby = lobbyFetchByCode($db, $code);
    return formatLobbyResponse($lobby, $userId);
}

function getLobbyStatus($code, $userId) {
    cleanupStaleLobbies();
    $code = strtoupper(trim($code));
    ensureLobbyTables();
    $db = getDb();

    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return ['error' => 'Lobby not found.'];

    if (!lobbyIsMember($db, (int)$lobby['id'], $userId)) {
        return ['error' => 'You are not in this lobby.'];
    }

    touchLobbyPresence($code, $userId);
    return formatLobbyResponse($lobby, $userId);
}

function requestLobbyReplay($code, $userId) {
    cleanupStaleLobbies();
    $code = strtoupper(trim($code));
    ensureLobbyTables();
    $db = getDb();

    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return ['error' => 'Lobby not found.'];
    $lobbyId = (int)$lobby['id'];

    if (!lobbyIsMember($db, $lobbyId, $userId)) {
        return ['error' => 'You are not in this lobby.'];
    }
    if ($lobby['status'] !== 'finished' && $lobby['status'] !== 'waiting') {
        return ['error' => 'Replay is only available after a finished lobby.'];
    }

    if ($lobby['status'] === 'finished') {
        $db->prepare("UPDATE lobbies SET status = 'waiting' WHERE id = ?")->execute([$lobbyId]);
        $db->prepare('UPDATE lobby_players SET clicks = NULL, time_seconds = NULL, path = NULL, replay_ready = 0 WHERE lobby_id = ?')->execute([$lobbyId]);
    }

    $db->prepare("UPDATE lobby_players SET replay_ready = 1, last_seen_at = datetime('now') WHERE lobby_id = ? AND user_id = ?")
        ->execute([$lobbyId, $userId]);

    $lobby = lobbyFetchByCode($db, $code);
    return formatLobbyResponse($lobby, $userId);
}

function reseedLobbyPair($code, $userId, $startTitle, $endTitle) {
    cleanupStaleLobbies();
    $code = strtoupper(trim($code));
    $startTitle = trim((string)$startTitle);
    $endTitle = trim((string)$endTitle);
    if ($startTitle === '' || $endTitle === '') {
        return ['error' => 'Both start and end titles are required.'];
    }
    if (strcasecmp($startTitle, $endTitle) === 0) {
        return ['error' => 'Start and end must be different.'];
    }

    ensureLobbyTables();
    $db = getDb();
    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return ['error' => 'Lobby not found.'];
    if ((int)$lobby['host_id'] !== (int)$userId) {
        return ['error' => 'Only the host can reseed this lobby.'];
    }
    if ($lobby['status'] !== 'waiting') {
        return ['error' => 'Cannot reseed after lobby start.'];
    }

    $db->prepare("UPDATE lobbies SET start_title = ?, end_title = ? WHERE code = ?")
        ->execute([$startTitle, $endTitle, $code]);
    $db->prepare("UPDATE lobby_players SET clicks = NULL, time_seconds = NULL, path = NULL WHERE lobby_id = ?")
        ->execute([(int)$lobby['id']]);

    $lobby = lobbyFetchByCode($db, $code);
    return formatLobbyResponse($lobby, $userId);
}

function quitLobby($code, $userId) {
    cleanupStaleLobbies();
    $code = strtoupper(trim($code));
    ensureLobbyTables();
    $db = getDb();

    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return ['error' => 'Lobby not found.'];
    $lobbyId = (int)$lobby['id'];
    if (!lobbyIsMember($db, $lobbyId, $userId)) {
        return ['error' => 'You are not in this lobby.'];
    }

    $isHost = ((int)$lobby['host_id'] === (int)$userId);
    $db->prepare('DELETE FROM lobby_players WHERE lobby_id = ? AND user_id = ?')->execute([$lobbyId, $userId]);

    $countStmt = $db->prepare('SELECT COUNT(*) FROM lobby_players WHERE lobby_id = ?');
    $countStmt->execute([$lobbyId]);
    $remaining = (int)$countStmt->fetchColumn();

    if ($isHost || $remaining <= 0) {
        $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
    } elseif ($lobby['status'] === 'active' && $remaining < 2) {
        $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
    }

    return ['ok' => true];
}

function touchLobbyPresence($code, $userId) {
    ensureLobbyTables();
    $db = getDb();
    $code = strtoupper(trim($code));
    if (!$code) return;

    $lobby = lobbyFetchByCode($db, $code);
    if (!$lobby) return;
    $db->prepare("UPDATE lobby_players SET last_seen_at = datetime('now'), missed_heartbeats = 0, disconnected_at = NULL WHERE lobby_id = ? AND user_id = ?")
        ->execute([(int)$lobby['id'], (int)$userId]);
}

function getOpenLobbyForUser($userId) {
    cleanupStaleLobbies();
    ensureLobbyTables();
    $db = getDb();
    $stmt = $db->prepare("
        SELECT l.*
        FROM lobbies l
        JOIN lobby_players lp ON lp.lobby_id = l.id
        WHERE lp.user_id = ?
          AND l.status != 'finished'
        ORDER BY l.id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function leaveOpenLobbiesForUser($userId) {
    ensureLobbyTables();
    $db = getDb();
    $stmt = $db->prepare("
        SELECT l.id, l.host_id, l.status
        FROM lobbies l
        JOIN lobby_players lp ON lp.lobby_id = l.id
        WHERE lp.user_id = ?
          AND l.status != 'finished'
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $left = 0;
    foreach ($rows as $row) {
        $lobbyId = (int)$row['id'];
        $isHost = ((int)$row['host_id'] === (int)$userId);

        $db->prepare('DELETE FROM lobby_players WHERE lobby_id = ? AND user_id = ?')->execute([$lobbyId, $userId]);

        $countStmt = $db->prepare('SELECT COUNT(*) FROM lobby_players WHERE lobby_id = ?');
        $countStmt->execute([$lobbyId]);
        $remaining = (int)$countStmt->fetchColumn();

        if ($remaining <= 0) {
            $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
            $left++;
            continue;
        }

        if ($isHost) {
            $nextHostStmt = $db->prepare('SELECT user_id FROM lobby_players WHERE lobby_id = ? ORDER BY joined_at ASC LIMIT 1');
            $nextHostStmt->execute([$lobbyId]);
            $nextHostId = (int)$nextHostStmt->fetchColumn();
            if ($nextHostId > 0) {
                $db->prepare('UPDATE lobbies SET host_id = ? WHERE id = ?')->execute([$nextHostId, $lobbyId]);
            } else {
                $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
                $left++;
                continue;
            }
        }

        if ($row['status'] === 'active' && $remaining < 2) {
            $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
        }
        $left++;
    }

    return $left;
}
