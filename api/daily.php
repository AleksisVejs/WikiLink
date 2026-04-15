<?php
require_once __DIR__ . '/db.php';

function getDailyPair($date) {
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM daily_pairs WHERE date = ?');
    $stmt->execute([$date]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return [
        'date'  => $row['date'],
        'start' => json_decode($row['start_data'], true),
        'end'   => json_decode($row['end_data'], true),
    ];
}

function saveDailyPair($date, $start, $end) {
    $db = getDb();
    $stmt = $db->prepare('INSERT OR IGNORE INTO daily_pairs (date, start_title, start_data, end_title, end_data) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$date, $start['title'], json_encode($start), $end['title'], json_encode($end)]);
}

function submitDailyScore($userId, $date, $clicks, $time, $path) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return ['error' => 'Invalid date format.'];
    }
    $today = gmdate('Y-m-d');
    if ($date !== $today) {
        return ['error' => 'Can only submit scores for today.'];
    }

    $db = getDb();

    $pair = getDailyPair($date);
    if (!$pair) return ['error' => 'No daily challenge found for this date.'];

    $clicks = max(0, min((int)$clicks, 999));
    $time = max(0, min((int)$time, 86400));

    $existing = $db->prepare('SELECT clicks, time_seconds FROM daily_scores WHERE user_id = ? AND date = ?');
    $existing->execute([$userId, $date]);
    $prev = $existing->fetch();

    if ($prev) {
        $isBetter = ($clicks < (int)$prev['clicks'])
                 || ($clicks === (int)$prev['clicks'] && $time < (int)$prev['time_seconds']);
        if ($isBetter) {
            $stmt = $db->prepare('UPDATE daily_scores SET clicks = ?, time_seconds = ?, path = ? WHERE user_id = ? AND date = ?');
            $stmt->execute([$clicks, $time, json_encode($path), $userId, $date]);
        }
    } else {
        $stmt = $db->prepare('INSERT INTO daily_scores (user_id, date, clicks, time_seconds, path) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $date, $clicks, $time, json_encode($path)]);
    }

    return ['ok' => true];
}

function getDailyLeaderboard($date, $limit = 50) {
    $db = getDb();
    $stmt = $db->prepare('
        SELECT u.username, u.profile_title, u.profile_nameplate_border, u.profile_accent, u.profile_pinned_badge, ds.clicks, ds.time_seconds, ds.path
        FROM daily_scores ds
        JOIN users u ON u.id = ds.user_id
        WHERE ds.date = ?
        ORDER BY ds.clicks ASC, ds.time_seconds ASC
        LIMIT ?
    ');
    $stmt->execute([$date, $limit]);
    $rows = $stmt->fetchAll();

    return array_map(function ($row, $idx) {
        return [
            'rank'     => $idx + 1,
            'username' => $row['username'],
            'profile_title' => !empty($row['profile_title']) ? $row['profile_title'] : 'newcomer',
            'profile_nameplate_border' => !empty($row['profile_nameplate_border']) ? $row['profile_nameplate_border'] : 'default',
            'profile_accent' => !empty($row['profile_accent']) ? $row['profile_accent'] : 'rank',
            'profile_pinned_badge' => isset($row['profile_pinned_badge']) ? (string)$row['profile_pinned_badge'] : '',
            'clicks'   => (int)$row['clicks'],
            'time'     => (int)$row['time_seconds'],
            'path'     => json_decode($row['path'], true),
        ];
    }, $rows, array_keys($rows));
}

function getUserDailyStreak($userId) {
    $db = getDb();
    $stmt = $db->prepare('SELECT DISTINCT date FROM daily_scores WHERE user_id = ? ORDER BY date DESC');
    $stmt->execute([$userId]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($dates)) return 0;

    $streak = 0;
    $expected = new DateTime('now', new DateTimeZone('UTC'));
    $expected->setTime(0, 0, 0);

    $todayStr = $expected->format('Y-m-d');
    $yesterdayStr = (clone $expected)->modify('-1 day')->format('Y-m-d');

    if ($dates[0] === $todayStr) {
        $expected = new DateTime($todayStr, new DateTimeZone('UTC'));
    } elseif ($dates[0] === $yesterdayStr) {
        $expected = new DateTime($yesterdayStr, new DateTimeZone('UTC'));
    } else {
        return 0;
    }

    foreach ($dates as $d) {
        if ($d === $expected->format('Y-m-d')) {
            $streak++;
            $expected->modify('-1 day');
        } else {
            break;
        }
    }
    return $streak;
}

function getUserDailyStatus($userId, $date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = gmdate('Y-m-d');
    }

    $db = getDb();
    $todayStmt = $db->prepare('SELECT clicks, time_seconds FROM daily_scores WHERE user_id = ? AND date = ? LIMIT 1');
    $todayStmt->execute([(int)$userId, $date]);
    $today = $todayStmt->fetch();

    $countStmt = $db->prepare('SELECT COUNT(*) as total FROM daily_scores WHERE user_id = ?');
    $countStmt->execute([(int)$userId]);
    $totalCompletions = (int)($countStmt->fetch()['total'] ?? 0);

    return [
        'date' => $date,
        'completed' => !!$today,
        'clicks' => $today ? (int)$today['clicks'] : null,
        'time' => $today ? (int)$today['time_seconds'] : null,
        'totalCompletions' => $totalCompletions,
    ];
}
