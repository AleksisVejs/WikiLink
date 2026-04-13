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
        status      TEXT    NOT NULL DEFAULT 'waiting',
        created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
    )");
}

function createMatch($startTitle, $endTitle, $userId) {
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
        $stmt = $db->prepare('UPDATE matches SET player2_id = ?, status = \'active\' WHERE code = ? AND player2_id IS NULL');
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
        'player' => 2,
    ];
}

function submitMatchResult($code, $userId, $clicks, $time, $path) {
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
    ];

    $result['opponent'] = [
        'username' => $oppId ? (isset($names[$oppId]) ? $names[$oppId] : null) : null,
        'clicks' => $isP1 ? $match['p2_clicks'] : $match['p1_clicks'],
        'time' => $isP1 ? $match['p2_time'] : $match['p1_time'],
        'submitted' => ($isP1 ? $match['p2_clicks'] : $match['p1_clicks']) !== null,
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
