<?php
require_once __DIR__ . '/db.php';

function ensureLobbyTables() {
    $db = getDb();
    $db->exec("CREATE TABLE IF NOT EXISTS lobbies (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        code         TEXT    NOT NULL UNIQUE,
        start_title  TEXT    NOT NULL,
        end_title    TEXT    NOT NULL,
        host_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        max_players  INTEGER NOT NULL DEFAULT 8,
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
        joined_at     TEXT    NOT NULL DEFAULT (datetime('now')),
        UNIQUE(lobby_id, user_id)
    )");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_lobby_players_lobby ON lobby_players(lobby_id)');
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

    // Remove old completed lobbies.
    $db->exec("DELETE FROM lobbies
        WHERE status = 'finished'
          AND created_at < datetime('now', '-14 days')");
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
        SELECT lp.user_id, lp.clicks, lp.time_seconds, lp.path, u.username
        FROM lobby_players lp
        JOIN users u ON u.id = lp.user_id
        WHERE lp.lobby_id = ?
        ORDER BY lp.joined_at ASC
    ');
    $stmt->execute([$lobbyId]);
    return $stmt->fetchAll();
}

function buildLobbyLeaderboard($players) {
    $rows = [];
    foreach ($players as $p) {
        if ($p['clicks'] === null) continue;
        $rows[] = $p;
    }
    usort($rows, function ($a, $b) {
        $ca = (int)$a['clicks'];
        $cb = (int)$b['clicks'];
        if ($ca !== $cb) return $ca - $cb;
        $ta = (int)$a['time_seconds'];
        $tb = (int)$b['time_seconds'];
        return $ta - $tb;
    });
    $board = [];
    $rank = 1;
    foreach ($rows as $p) {
        $board[] = [
            'rank' => $rank++,
            'user_id' => (int)$p['user_id'],
            'username' => $p['username'],
            'clicks' => (int)$p['clicks'],
            'time' => (int)$p['time_seconds'],
        ];
    }
    return $board;
}

function formatLobbyResponse($lobby, $userId) {
    $db = getDb();
    $playersRaw = lobbyPlayersWithNames($db, $lobby['id']);
    $players = [];
    foreach ($playersRaw as $p) {
        $players[] = [
            'user_id' => (int)$p['user_id'],
            'username' => $p['username'],
            'clicks' => $p['clicks'] !== null ? (int)$p['clicks'] : null,
            'time' => $p['time_seconds'] !== null ? (int)$p['time_seconds'] : null,
            'submitted' => $p['clicks'] !== null,
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
        'players' => $players,
    ];
    if ($lobby['status'] === 'finished') {
        $out['leaderboard'] = buildLobbyLeaderboard($playersRaw);
    }
    return $out;
}

function createLobby($startTitle, $endTitle, $hostId, $maxPlayers = 8) {
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

    ensureLobbyTables();
    $db = getDb();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = generateUniqueCode();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare('INSERT INTO lobbies (code, start_title, end_title, host_id, max_players) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$code, $startTitle, $endTitle, $hostId, $maxPlayers]);
            $lobbyId = (int)$db->lastInsertId();
            $db->prepare('INSERT INTO lobby_players (lobby_id, user_id) VALUES (?, ?)')->execute([$lobbyId, $hostId]);
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
        return formatLobbyResponse($lobby, $userId);
    }

    $count = lobbyPlayerCount($db, $lobbyId);
    if ($count >= (int)$lobby['max_players']) {
        return ['error' => 'Lobby is full.'];
    }

    try {
        $db->prepare('INSERT INTO lobby_players (lobby_id, user_id) VALUES (?, ?)')->execute([$lobbyId, $userId]);
    } catch (PDOException $e) {
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

    $count = lobbyPlayerCount($db, (int)$lobby['id']);
    if ($count < 2) {
        return ['error' => 'Need at least two players to start.'];
    }

    $stmt = $db->prepare("UPDATE lobbies SET status = 'active' WHERE code = ? AND status = 'waiting'");
    $stmt->execute([$code]);
    if ($stmt->rowCount() === 0) {
        return ['error' => 'Could not start lobby.'];
    }

    $lobby = lobbyFetchByCode($db, $code);
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

    $stmt = $db->prepare('UPDATE lobby_players SET clicks = ?, time_seconds = ?, path = ? WHERE lobby_id = ? AND user_id = ?');
    $stmt->execute([(int)$clicks, (int)$time, $pathJson, $lobbyId, $userId]);

    $stmt = $db->prepare('
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN clicks IS NOT NULL THEN 1 ELSE 0 END) AS submitted
        FROM lobby_players WHERE lobby_id = ?
    ');
    $stmt->execute([$lobbyId]);
    $row = $stmt->fetch();
    if ((int)$row['total'] > 0 && (int)$row['submitted'] === (int)$row['total']) {
        $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
        $lobby = lobbyFetchByCode($db, $code);
    } else {
        $lobby = lobbyFetchByCode($db, $code);
    }

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

    return formatLobbyResponse($lobby, $userId);
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

        if ((int)$row['status'] === 'active' && $remaining < 2) {
            $db->prepare("UPDATE lobbies SET status = 'finished' WHERE id = ?")->execute([$lobbyId]);
        }
        $left++;
    }

    return $left;
}
