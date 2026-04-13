<?php
require_once __DIR__ . '/db.php';

function getDefaultStats() {
    return ['modes' => [], 'genres' => []];
}

function getDefaultModeStats() {
    return [
        'gamesPlayed' => 0, 'gamesWon' => 0, 'gamesLost' => 0,
        'bestClicks' => null, 'bestTime' => null,
        'totalClicks' => 0, 'currentStreak' => 0, 'bestStreak' => 0,
    ];
}

function getDefaultGenreStats() {
    return ['gamesPlayed' => 0, 'gamesWon' => 0, 'gamesLost' => 0];
}

function getUserStats($userId) {
    $db = getDb();
    $stmt = $db->prepare('SELECT stats_json FROM user_stats WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) return getDefaultStats();

    $stats = json_decode($row['stats_json'], true);
    if (!is_array($stats)) return getDefaultStats();
    if (!isset($stats['modes']) || !is_array($stats['modes'])) $stats['modes'] = [];
    if (!isset($stats['genres']) || !is_array($stats['genres'])) $stats['genres'] = [];
    return $stats;
}

function sanitizeStats($stats) {
    if (!is_array($stats)) return getDefaultStats();
    $clean = ['modes' => [], 'genres' => []];

    $modeFields = ['gamesPlayed','gamesWon','gamesLost','totalClicks','currentStreak','bestStreak'];
    $nullableFields = ['bestClicks','bestTime'];
    $genreFields = ['gamesPlayed','gamesWon','gamesLost'];

    if (isset($stats['modes']) && is_array($stats['modes'])) {
        $i = 0;
        foreach ($stats['modes'] as $key => $m) {
            if ($i++ >= 20 || !is_string($key) || !is_array($m)) continue;
            $cm = getDefaultModeStats();
            foreach ($modeFields as $f) {
                if (isset($m[$f])) $cm[$f] = max(0, min((int)$m[$f], 999999));
            }
            foreach ($nullableFields as $f) {
                $cm[$f] = (isset($m[$f]) && $m[$f] !== null) ? max(0, min((int)$m[$f], 999999)) : null;
            }
            $clean['modes'][$key] = $cm;
        }
    }

    if (isset($stats['genres']) && is_array($stats['genres'])) {
        $i = 0;
        foreach ($stats['genres'] as $key => $g) {
            if ($i++ >= 50 || !is_string($key) || !is_array($g)) continue;
            $cg = getDefaultGenreStats();
            foreach ($genreFields as $f) {
                if (isset($g[$f])) $cg[$f] = max(0, min((int)$g[$f], 999999));
            }
            $clean['genres'][$key] = $cg;
        }
    }

    return $clean;
}

function saveUserStats($userId, $stats) {
    $stats = sanitizeStats($stats);

    $db = getDb();
    $json = json_encode($stats);
    $stmt = $db->prepare('INSERT INTO user_stats (user_id, stats_json) VALUES (?, ?) ON CONFLICT(user_id) DO UPDATE SET stats_json = ?');
    $stmt->execute([$userId, $json, $json]);
}

function recordGameResult($userId, $mode, $genre, $clicks, $time, $won) {
    $mode = is_string($mode) ? $mode : 'classic';
    $genre = is_string($genre) ? $genre : 'random';
    $clicks = max(0, min((int)$clicks, 999));
    $time = max(0, min((int)$time, 86400));
    $won = (bool)$won;

    $stats = getUserStats($userId);

    if (!isset($stats['modes'][$mode])) {
        $stats['modes'][$mode] = getDefaultModeStats();
    }
    $ms = &$stats['modes'][$mode];
    $ms['gamesPlayed']++;
    $ms['totalClicks'] += $clicks;

    if ($won) {
        $ms['gamesWon']++;
        $ms['currentStreak']++;
        if ($ms['currentStreak'] > $ms['bestStreak']) $ms['bestStreak'] = $ms['currentStreak'];
        if ($ms['bestClicks'] === null || $clicks < $ms['bestClicks']) $ms['bestClicks'] = $clicks;
        if ($ms['bestTime'] === null || $time < $ms['bestTime']) $ms['bestTime'] = $time;
    } else {
        $ms['gamesLost']++;
        $ms['currentStreak'] = 0;
    }

    if (!isset($stats['genres'][$genre])) {
        $stats['genres'][$genre] = getDefaultGenreStats();
    }
    $gs = &$stats['genres'][$genre];
    $gs['gamesPlayed']++;
    if ($won) $gs['gamesWon']++;
    else $gs['gamesLost']++;

    saveUserStats($userId, $stats);
    return $stats;
}
