<?php
require_once __DIR__ . '/db.php';

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
        status      TEXT    NOT NULL DEFAULT 'waiting',
        created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
    )");
    ensureMatchColumn($db, 'p1_quit', "ALTER TABLE matches ADD COLUMN p1_quit INTEGER NOT NULL DEFAULT 0");
    ensureMatchColumn($db, 'p2_quit', "ALTER TABLE matches ADD COLUMN p2_quit INTEGER NOT NULL DEFAULT 0");
}

function ensureMatchColumn($db, $columnName, $alterSql) {
    $stmt = $db->query("PRAGMA table_info(matches)");
    $columns = $stmt ? $stmt->fetchAll() : [];
    foreach ($columns as $col) {
        if (isset($col['name']) && $col['name'] === $columnName) return;
    }
    $db->exec($alterSql);
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

    // Safety net: very old active matches should not block new rooms forever.
    $db->exec("UPDATE matches
        SET status = 'finished'
        WHERE status = 'active'
          AND created_at < datetime('now', '-12 hours')");

    // Keep DB lean: remove old finished rows.
    $db->exec("DELETE FROM matches
        WHERE status = 'finished'
          AND created_at < datetime('now', '-14 days')");
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
            $stmt = $db->prepare('INSERT INTO matches (code, start_title, end_title, player1_id) VALUES (?, ?, ?, ?)');
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

    if ($match['player1_id'] == $userId) {
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
        $stmt = $db->prepare('UPDATE matches SET player2_id = ? WHERE code = ? AND player2_id IS NULL');
        $stmt->execute([$userId, $code]);
        if ($stmt->rowCount() === 0) {
            return ['error' => 'Match is full.'];
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
        $stmt = $db->prepare("UPDATE matches SET status = 'active' WHERE code = ? AND status = 'waiting'");
        $stmt->execute([$code]);
    }

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $updated = $stmt->fetch();

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

    if ($match['player1_id'] == $userId) {
        $stmt = $db->prepare('UPDATE matches SET p1_clicks = ?, p1_time = ?, p1_path = ? WHERE code = ?');
        $stmt->execute([$clicks, $time, $pathJson, $code]);
    } elseif ($match['player2_id'] == $userId) {
        $stmt = $db->prepare('UPDATE matches SET p2_clicks = ?, p2_time = ?, p2_path = ? WHERE code = ?');
        $stmt->execute([$clicks, $time, $pathJson, $code]);
    } else {
        return ['error' => 'You are not in this match.'];
    }

    $stmt = $db->prepare('SELECT * FROM matches WHERE code = ?');
    $stmt->execute([$code]);
    $updated = $stmt->fetch();

    if ($updated['p1_clicks'] !== null && $updated['p2_clicks'] !== null) {
        $db->prepare('UPDATE matches SET status = \'finished\' WHERE code = ?')->execute([$code]);
        $updated['status'] = 'finished';
    }

    return formatMatchResult($updated, $userId);
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
        $db->prepare('UPDATE matches SET p2_quit = 1 WHERE code = ?')->execute([$code]);
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
    $result['you'] = [
        'username' => isset($names[$uid]) ? $names[$uid] : null,
        'clicks' => $isP1 ? $match['p1_clicks'] : $match['p2_clicks'],
        'time' => $isP1 ? $match['p1_time'] : $match['p2_time'],
        'path' => json_decode($isP1 ? ($match['p1_path'] ?: '[]') : ($match['p2_path'] ?: '[]'), true),
        'submitted' => ($isP1 ? $match['p1_clicks'] : $match['p2_clicks']) !== null,
        'quit' => (bool)($isP1 ? $match['p1_quit'] : $match['p2_quit']),
    ];

    $result['opponent'] = [
        'username' => $oppId ? (isset($names[$oppId]) ? $names[$oppId] : null) : null,
        'clicks' => $isP1 ? $match['p2_clicks'] : $match['p1_clicks'],
        'time' => $isP1 ? $match['p2_time'] : $match['p1_time'],
        'submitted' => ($isP1 ? $match['p2_clicks'] : $match['p1_clicks']) !== null,
        'quit' => (bool)($isP1 ? $match['p2_quit'] : $match['p1_quit']),
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
