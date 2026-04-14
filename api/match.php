<?php
require_once __DIR__ . '/db.php';

const MATCH_HEARTBEAT_INTERVAL_SECONDS = 15;
const MATCH_MISSED_HEARTBEAT_LIMIT = 3;
const MATCH_RECONNECT_GRACE_SECONDS = 45;

function ensureMatchTable() {
    $db = getDb();
    $db->exec("CREATE TABLE IF NOT EXISTS matches (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        code        TEXT    NOT NULL UNIQUE,
        start_title TEXT    NOT NULL,
        end_title   TEXT    NOT NULL,
        player1_id  INTEGER REFERENCES users(id) ON DELETE SET NULL,
        player2_id  INTEGER REFERENCES users(id) ON DELETE SET NULL,
        p1_clicks   INTEGER,
        p1_time     INTEGER,
        p1_path     TEXT,
        p2_clicks   INTEGER,
        p2_time     INTEGER,
        p2_path     TEXT,
        p1_quit     INTEGER NOT NULL DEFAULT 0,
        p2_quit     INTEGER NOT NULL DEFAULT 0,
        p1_last_seen_at TEXT,
        p2_last_seen_at TEXT,
        p1_missed_heartbeats INTEGER NOT NULL DEFAULT 0,
        p2_missed_heartbeats INTEGER NOT NULL DEFAULT 0,
        p1_disconnected_at TEXT,
        p2_disconnected_at TEXT,
        status      TEXT    NOT NULL DEFAULT 'waiting',
        created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
    )");
    ensureMatchColumn($db, 'p1_quit', "ALTER TABLE matches ADD COLUMN p1_quit INTEGER NOT NULL DEFAULT 0");
    ensureMatchColumn($db, 'p2_quit', "ALTER TABLE matches ADD COLUMN p2_quit INTEGER NOT NULL DEFAULT 0");
    ensureMatchColumn($db, 'p1_last_seen_at', "ALTER TABLE matches ADD COLUMN p1_last_seen_at TEXT");
    ensureMatchColumn($db, 'p2_last_seen_at', "ALTER TABLE matches ADD COLUMN p2_last_seen_at TEXT");
    ensureMatchColumn($db, 'p1_missed_heartbeats', "ALTER TABLE matches ADD COLUMN p1_missed_heartbeats INTEGER NOT NULL DEFAULT 0");
    ensureMatchColumn($db, 'p2_missed_heartbeats', "ALTER TABLE matches ADD COLUMN p2_missed_heartbeats INTEGER NOT NULL DEFAULT 0");
    ensureMatchColumn($db, 'p1_disconnected_at', "ALTER TABLE matches ADD COLUMN p1_disconnected_at TEXT");
    ensureMatchColumn($db, 'p2_disconnected_at', "ALTER TABLE matches ADD COLUMN p2_disconnected_at TEXT");
}

function ensureMatchColumn($db, $columnName, $alterSql) {
    $stmt = $db->query("PRAGMA table_info(matches)");
    $columns = $stmt ? $stmt->fetchAll() : [];
    foreach ($columns as $col) {
        if (isset($col['name']) && $col['name'] === $columnName) return;
    }
    $db->exec($alterSql);
}

function secondsSinceTimestamp($timestamp) {
    if (!$timestamp) return null;
    $ts = strtotime($timestamp . ' UTC');
    if ($ts === false) return null;
    return max(0, time() - $ts);
}

function applyMatchPresenceRules($db, $match) {
    $id = (int)$match['id'];
    $updates = [];
    $stats = ['disconnect_marked' => 0, 'reconnected' => 0, 'timed_out' => 0, 'finished' => 0];

    $players = [
        [
            'id_col' => 'player1_id',
            'quit_col' => 'p1_quit',
            'seen_col' => 'p1_last_seen_at',
            'missed_col' => 'p1_missed_heartbeats',
            'disc_col' => 'p1_disconnected_at',
        ],
        [
            'id_col' => 'player2_id',
            'quit_col' => 'p2_quit',
            'seen_col' => 'p2_last_seen_at',
            'missed_col' => 'p2_missed_heartbeats',
            'disc_col' => 'p2_disconnected_at',
        ],
    ];

    foreach ($players as $p) {
        if (empty($match[$p['id_col']]) || (int)$match[$p['quit_col']] === 1) continue;

        $inactiveFor = secondsSinceTimestamp($match[$p['seen_col']]);
        $missed = $inactiveFor === null
            ? MATCH_MISSED_HEARTBEAT_LIMIT
            : (int)floor($inactiveFor / MATCH_HEARTBEAT_INTERVAL_SECONDS);
        if ($missed < 0) $missed = 0;

        $updates[$p['missed_col']] = $missed;

        if ($missed >= MATCH_MISSED_HEARTBEAT_LIMIT) {
            if (empty($match[$p['disc_col']])) {
                $updates[$p['disc_col']] = gmdate('Y-m-d H:i:s');
                $stats['disconnect_marked']++;
            } else {
                $discFor = secondsSinceTimestamp($match[$p['disc_col']]);
                if ($discFor !== null && $discFor >= MATCH_RECONNECT_GRACE_SECONDS) {
                    $updates[$p['quit_col']] = 1;
                    $stats['timed_out']++;
                }
            }
        } else {
            if (!empty($match[$p['disc_col']])) $stats['reconnected']++;
            $updates[$p['disc_col']] = null;
        }
    }

    if (!empty($updates)) {
        $setSql = [];
        $params = [];
        foreach ($updates as $col => $value) {
            $setSql[] = "$col = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $stmt = $db->prepare('UPDATE matches SET ' . implode(', ', $setSql) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    $shouldFinish = (isset($updates['p1_quit']) && (int)$updates['p1_quit'] === 1)
        || (isset($updates['p2_quit']) && (int)$updates['p2_quit'] === 1)
        || (int)$match['p1_quit'] === 1
        || (int)$match['p2_quit'] === 1;
    if ($shouldFinish && $match['status'] !== 'finished') {
        $db->prepare("UPDATE matches SET status = 'finished' WHERE id = ?")->execute([$id]);
        $stats['finished']++;
    }

    return $stats;
}

function cleanupStaleMatches() {
    ensureMatchTable();
    $db = getDb();

    // Waiting room with no opponent: host abandoned create flow.
    $db->exec("DELETE FROM matches
        WHERE status = 'waiting'
          AND player2_id IS NULL
          AND created_at < datetime('now', '-30 minutes')");

    // Waiting room with two players but never started.
    $db->exec("DELETE FROM matches
        WHERE status = 'waiting'
          AND player2_id IS NOT NULL
          AND created_at < datetime('now', '-2 hours')");

    // If someone quit and match remained open, close it after grace period.
    $db->exec("UPDATE matches
        SET status = 'finished'
        WHERE status != 'finished'
          AND (COALESCE(p1_quit, 0) = 1 OR COALESCE(p2_quit, 0) = 1)
          AND created_at < datetime('now', '-15 minutes')");

    $stats = ['disconnect_marked' => 0, 'reconnected' => 0, 'timed_out' => 0, 'finished' => 0];
    $rowsStmt = $db->query("SELECT * FROM matches WHERE status != 'finished'");
    $rows = $rowsStmt ? $rowsStmt->fetchAll() : [];
    foreach ($rows as $row) {
        $s = applyMatchPresenceRules($db, $row);
        $stats['disconnect_marked'] += (int)$s['disconnect_marked'];
        $stats['reconnected'] += (int)$s['reconnected'];
        $stats['timed_out'] += (int)$s['timed_out'];
        $stats['finished'] += (int)$s['finished'];
    }

    // Safety net: very old active matches should not block new rooms forever.
    $db->exec("UPDATE matches
        SET status = 'finished'
        WHERE status = 'active'
          AND created_at < datetime('now', '-12 hours')");

    // Keep DB lean: remove old finished rows.
    $db->exec("DELETE FROM matches
        WHERE status = 'finished'
          AND created_at < datetime('now', '-14 days')");
    return $stats;
}

function createMatch($startTitle, $endTitle, $userId) {
    cleanupStaleMatches();
    $startTitle = trim($startTitle);
    $endTitle = trim($endTitle);
    if (empty($startTitle) || empty($endTitle)) {
        return ['error' => 'Both start and end titles are required.'];
    }
    if (strtolower($startTitle) === strtolower($endTitle)) {
        return ['error' => 'Start and end must be different.'];
    }

    ensureMatchTable();
    $db = getDb();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = generateUniqueCode();
        try {
            $stmt = $db->prepare('INSERT INTO matches (code, start_title, end_title, player1_id, p1_last_seen_at) VALUES (?, ?, ?, ?, datetime(\'now\'))');
            $stmt->execute([$code, $startTitle, $endTitle, $userId]);
            return ['code' => $code, 'start_title' => $startTitle, 'end_title' => $endTitle];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') === false) throw $e;
        }
    }

    return ['error' => 'Could not generate a unique match code. Try again.'];
}

function joinMatch($code, $userId) {
    cleanupStaleMatches();
    $code = strtoupper(trim($code));
    ensureMatchTable();
    $db = getDb();

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $match = $stmt->fetch();

    if (!$match) return ['error' => 'Match not found.'];
    if ($match['status'] === 'finished') return ['error' => 'Match is closed.'];

    if ($match['player1_id'] == $userId) {
        touchMatchPresence($code, $userId);
        return [
            'code' => $match['code'],
            'start_title' => $match['start_title'],
            'end_title' => $match['end_title'],
            'status' => $match['status'],
            'player' => 1,
        ];
    }

    if ($match['player2_id'] && $match['player2_id'] != $userId) {
        return ['error' => 'Match is full.'];
    }

    if ($match['status'] === 'waiting' && !$match['player2_id']) {
        try {
            $db->beginTransaction();
            $freshStmt = $db->prepare('SELECT status, player2_id FROM matches WHERE code = ?');
            $freshStmt->execute([$code]);
            $fresh = $freshStmt->fetch();
            if (!$fresh || $fresh['status'] !== 'waiting' || $fresh['player2_id']) {
                if ($db->inTransaction()) $db->rollBack();
                return ['error' => 'Match is full.'];
            }

            $stmt = $db->prepare('UPDATE matches SET player2_id = ?, p2_last_seen_at = datetime(\'now\') WHERE code = ? AND player2_id IS NULL');
            $stmt->execute([$userId, $code]);
            if ($stmt->rowCount() === 0) {
                if ($db->inTransaction()) $db->rollBack();
                return ['error' => 'Match is full.'];
            }
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $updated = $stmt->fetch();

    return [
        'code' => $updated['code'],
        'start_title' => $updated['start_title'],
        'end_title' => $updated['end_title'],
        'status' => $updated['status'],
        'player' => ($updated['player1_id'] == $userId ? 1 : 2),
    ];
}

function startMatch($code, $userId) {
    cleanupStaleMatches();
    $code = strtoupper(trim($code));
    ensureMatchTable();
    $db = getDb();

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $match = $stmt->fetch();
    if (!$match) return ['error' => 'Match not found.'];
    if ((int)$match['player1_id'] !== (int)$userId) {
        return ['error' => 'Only the host can start this match.'];
    }
    if (!$match['player2_id']) {
        return ['error' => 'Cannot start until opponent joins.'];
    }
    if ($match['status'] === 'finished') {
        return ['error' => 'Match is already finished.'];
    }

    if ($match['status'] !== 'active') {
        try {
            $db->beginTransaction();
            $freshStmt = $db->prepare('SELECT status, player2_id FROM matches WHERE code = ?');
            $freshStmt->execute([$code]);
            $fresh = $freshStmt->fetch();
            if (!$fresh) {
                if ($db->inTransaction()) $db->rollBack();
                return ['error' => 'Match not found.'];
            }
            if ($fresh['status'] === 'finished') {
                if ($db->inTransaction()) $db->rollBack();
                return ['error' => 'Match is already finished.'];
            }
            if (!$fresh['player2_id']) {
                if ($db->inTransaction()) $db->rollBack();
                return ['error' => 'Cannot start until opponent joins.'];
            }

            $stmt = $db->prepare("UPDATE matches SET status = 'active' WHERE code = ? AND status = 'waiting'");
            $stmt->execute([$code]);
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $updated = $stmt->fetch();

    touchMatchPresence($code, $userId);
    return formatMatchResult($updated, $userId);
}

function submitMatchResult($code, $userId, $clicks, $time, $path) {
    cleanupStaleMatches();
    $code = strtoupper(trim($code));
    ensureMatchTable();
    $db = getDb();

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $match = $stmt->fetch();

    if (!$match) return ['error' => 'Match not found.'];
    if ($match['status'] === 'finished') return ['error' => 'Match is already finished.'];
    if ($match['status'] === 'waiting') return ['error' => 'Match has not started yet.'];

    $pathJson = json_encode(is_array($path) ? $path : []);
    $safeClicks = max(0, (int)$clicks);
    $safeTime = max(0, (int)$time);

    try {
        $db->beginTransaction();
        if ($match['player1_id'] == $userId) {
            $stmt = $db->prepare("UPDATE matches SET p1_clicks = ?, p1_time = ?, p1_path = ?, p1_last_seen_at = datetime('now') WHERE code = ?");
            $stmt->execute([$safeClicks, $safeTime, $pathJson, $code]);
        } elseif ($match['player2_id'] == $userId) {
            $stmt = $db->prepare("UPDATE matches SET p2_clicks = ?, p2_time = ?, p2_path = ?, p2_last_seen_at = datetime('now') WHERE code = ?");
            $stmt->execute([$safeClicks, $safeTime, $pathJson, $code]);
        } else {
            if ($db->inTransaction()) $db->rollBack();
            return ['error' => 'You are not in this match.'];
        }

        $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
        $stmt->execute([$code]);
        $updated = $stmt->fetch();

        if ($updated['p1_clicks'] !== null && $updated['p2_clicks'] !== null) {
            $db->prepare('UPDATE matches SET status = \'finished\' WHERE code = ?')->execute([$code]);
            $updated['status'] = 'finished';
        }
        $db->commit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    return formatMatchResult($updated, $userId);
}

function touchMatchPresence($code, $userId) {
    ensureMatchTable();
    $db = getDb();
    $code = strtoupper(trim($code));
    if (!$code) return;

    $stmt = $db->prepare('SELECT player1_id, player2_id FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $match = $stmt->fetch();
    if (!$match) return;

    if ((int)$match['player1_id'] === (int)$userId) {
        $db->prepare("UPDATE matches SET p1_last_seen_at = datetime('now'), p1_missed_heartbeats = 0, p1_disconnected_at = NULL WHERE code = ?")->execute([$code]);
    } elseif ((int)$match['player2_id'] === (int)$userId) {
        $db->prepare("UPDATE matches SET p2_last_seen_at = datetime('now'), p2_missed_heartbeats = 0, p2_disconnected_at = NULL WHERE code = ?")->execute([$code]);
    }
}

function quitMatch($code, $userId) {
    cleanupStaleMatches();
    $code = strtoupper(trim($code));
    ensureMatchTable();
    $db = getDb();

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $match = $stmt->fetch();
    if (!$match) return ['error' => 'Match not found.'];
    if ($match['status'] === 'finished') return formatMatchResult($match, $userId);

    if ($match['player1_id'] == $userId) {
        $db->prepare('UPDATE matches SET p1_quit = 1 WHERE code = ?')->execute([$code]);
    } elseif ($match['player2_id'] == $userId) {
        // In waiting rooms, guest leaving should not destroy host's room.
        if ($match['status'] === 'waiting') {
            $db->prepare("
                UPDATE matches
                SET player2_id = NULL,
                    p2_quit = 0,
                    p2_clicks = NULL,
                    p2_time = NULL,
                    p2_path = NULL,
                    p2_last_seen_at = NULL,
                    p2_missed_heartbeats = 0,
                    p2_disconnected_at = NULL
                WHERE code = ?
            ")->execute([$code]);
        } else {
            $db->prepare('UPDATE matches SET p2_quit = 1 WHERE code = ?')->execute([$code]);
        }
    } else {
        return ['error' => 'You are not in this match.'];
    }

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $updated = $stmt->fetch();
    return formatMatchResult($updated, $userId);
}

function getUsernamesByIds($db, $ids) {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT id, username FROM users WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $map = [];
    while ($row = $stmt->fetch()) {
        $map[(int)$row['id']] = $row['username'];
    }
    return $map;
}

function getMatchStatus($code, $userId) {
    cleanupStaleMatches();
    $code = strtoupper(trim($code));
    ensureMatchTable();
    $db = getDb();

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $match = $stmt->fetch();

    if (!$match) return ['error' => 'Match not found.'];
    if ($match['player1_id'] != $userId && $match['player2_id'] != $userId) {
        return ['error' => 'You are not in this match.'];
    }

    touchMatchPresence($code, $userId);
    return formatMatchResult($match, $userId);
}

function getOpenMatchForUser($userId) {
    cleanupStaleMatches();
    ensureMatchTable();
    $db = getDb();
    $stmt = $db->prepare("
        SELECT *
        FROM matches
        WHERE
          (
            player1_id = ?
            AND COALESCE(p1_quit, 0) = 0
            AND (
              (
                status = 'active'
                AND player2_id IS NOT NULL
                AND COALESCE(p2_quit, 0) = 0
              )
              OR (
                status = 'waiting'
                AND player2_id IS NOT NULL
                AND COALESCE(p2_quit, 0) = 0
              )
            )
          )
          OR
          (
            player2_id = ?
            AND COALESCE(p2_quit, 0) = 0
            AND (
              (
                status = 'active'
                AND player1_id IS NOT NULL
                AND COALESCE(p1_quit, 0) = 0
              )
              OR (
                status = 'waiting'
                AND COALESCE(p1_quit, 0) = 0
              )
            )
          )
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function leaveOpenMatchesForUser($userId) {
    ensureMatchTable();
    $db = getDb();
    $stmt = $db->prepare("
        SELECT id, code, player1_id, player2_id, status
        FROM matches
        WHERE status != 'finished'
          AND (
            (player1_id = ? AND COALESCE(p1_quit, 0) = 0)
            OR
            (player2_id = ? AND COALESCE(p2_quit, 0) = 0)
          )
    ");
    $stmt->execute([$userId, $userId]);
    $rows = $stmt->fetchAll();

    $closed = 0;
    foreach ($rows as $r) {
        $isHost = ((int)$r['player1_id'] === (int)$userId);
        $noOpponent = empty($r['player2_id']);
        $isGuest = ((int)$r['player2_id'] === (int)$userId);

        // If host cancels an unjoined waiting room, hard-delete so code becomes invalid immediately.
        if ($isHost && $noOpponent && $r['status'] === 'waiting') {
            $db->prepare('DELETE FROM matches WHERE id = ?')->execute([(int)$r['id']]);
            $closed++;
            continue;
        }

        // If a guest leaves a waiting room, keep host room alive and free the slot.
        if ($isGuest && $r['status'] === 'waiting') {
            $db->prepare("
                UPDATE matches
                SET player2_id = NULL,
                    p2_quit = 0,
                    p2_clicks = NULL,
                    p2_time = NULL,
                    p2_path = NULL,
                    p2_last_seen_at = NULL,
                    p2_missed_heartbeats = 0,
                    p2_disconnected_at = NULL
                WHERE id = ?
            ")->execute([(int)$r['id']]);
            $closed++;
            continue;
        }

        if ((int)$r['player1_id'] === (int)$userId) {
            $db->prepare('UPDATE matches SET p1_quit = 1 WHERE id = ?')->execute([(int)$r['id']]);
        } elseif ((int)$r['player2_id'] === (int)$userId) {
            $db->prepare('UPDATE matches SET p2_quit = 1 WHERE id = ?')->execute([(int)$r['id']]);
        }
        $db->prepare("UPDATE matches SET status = 'finished' WHERE id = ?")->execute([(int)$r['id']]);
        $closed++;
    }
    return $closed;
}

function formatMatchResult($match, $userId) {
    $isP1 = ($match['player1_id'] == $userId);
    $db = getDb();
    $names = getUsernamesByIds($db, [$match['player1_id'], $match['player2_id']]);
    $oppId = $isP1 ? (int)$match['player2_id'] : (int)$match['player1_id'];

    $result = [
        'code' => $match['code'],
        'status' => $match['status'],
        'start_title' => $match['start_title'],
        'end_title' => $match['end_title'],
    ];

    $uid = (int)$userId;
    $youMissed = $isP1 ? (int)$match['p1_missed_heartbeats'] : (int)$match['p2_missed_heartbeats'];
    $oppMissed = $isP1 ? (int)$match['p2_missed_heartbeats'] : (int)$match['p1_missed_heartbeats'];
    $youDisc = $isP1 ? $match['p1_disconnected_at'] : $match['p2_disconnected_at'];
    $oppDisc = $isP1 ? $match['p2_disconnected_at'] : $match['p1_disconnected_at'];
    $youGraceLeft = null;
    $oppGraceLeft = null;
    if ($youDisc) $youGraceLeft = max(0, MATCH_RECONNECT_GRACE_SECONDS - (secondsSinceTimestamp($youDisc) ?? MATCH_RECONNECT_GRACE_SECONDS));
    if ($oppDisc) $oppGraceLeft = max(0, MATCH_RECONNECT_GRACE_SECONDS - (secondsSinceTimestamp($oppDisc) ?? MATCH_RECONNECT_GRACE_SECONDS));

    $result['you'] = [
        'username' => isset($names[$uid]) ? $names[$uid] : null,
        'clicks' => $isP1 ? $match['p1_clicks'] : $match['p2_clicks'],
        'time' => $isP1 ? $match['p1_time'] : $match['p2_time'],
        'path' => json_decode($isP1 ? ($match['p1_path'] ?: '[]') : ($match['p2_path'] ?: '[]'), true),
        'submitted' => ($isP1 ? $match['p1_clicks'] : $match['p2_clicks']) !== null,
        'quit' => (bool)($isP1 ? $match['p1_quit'] : $match['p2_quit']),
        'presence' => [
            'missed_heartbeats' => $youMissed,
            'is_in_reconnect_grace' => $youDisc !== null,
            'grace_seconds_left' => $youGraceLeft,
        ],
    ];

    $result['opponent'] = [
        'username' => $oppId ? (isset($names[$oppId]) ? $names[$oppId] : null) : null,
        'clicks' => $isP1 ? $match['p2_clicks'] : $match['p1_clicks'],
        'time' => $isP1 ? $match['p2_time'] : $match['p1_time'],
        'submitted' => ($isP1 ? $match['p2_clicks'] : $match['p1_clicks']) !== null,
        'quit' => (bool)($isP1 ? $match['p2_quit'] : $match['p1_quit']),
        'presence' => [
            'missed_heartbeats' => $oppMissed,
            'is_in_reconnect_grace' => $oppDisc !== null,
            'grace_seconds_left' => $oppGraceLeft,
        ],
    ];

    $result['presence_config'] = [
        'heartbeat_interval_seconds' => MATCH_HEARTBEAT_INTERVAL_SECONDS,
        'missed_heartbeat_limit' => MATCH_MISSED_HEARTBEAT_LIMIT,
        'reconnect_grace_seconds' => MATCH_RECONNECT_GRACE_SECONDS,
    ];

    if ($match['status'] === 'finished') {
        $yourClicks = $result['you']['clicks'];
        $oppClicks = $result['opponent']['clicks'];
        if ($yourClicks < $oppClicks) $result['winner'] = 'you';
        elseif ($oppClicks < $yourClicks) $result['winner'] = 'opponent';
        else {
            $yourTime = $result['you']['time'];
            $oppTime = $result['opponent']['time'];
            if ($yourTime < $oppTime) $result['winner'] = 'you';
            elseif ($oppTime < $yourTime) $result['winner'] = 'opponent';
            else $result['winner'] = 'tie';
        }
    }

    return $result;
}
