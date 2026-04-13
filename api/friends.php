<?php
require_once __DIR__ . '/db.php';

function sendFriendRequest($userId, $friendUsername) {
    $friendUsername = trim($friendUsername);
    if (empty($friendUsername)) {
        return ['error' => 'Username is required.'];
    }

    $db = getDb();

    $stmt = $db->prepare('SELECT id, username FROM users WHERE username = ? COLLATE NOCASE');
    $stmt->execute([$friendUsername]);
    $friend = $stmt->fetch();

    if (!$friend) return ['error' => 'User not found.'];
    if ((int)$friend['id'] === $userId) return ['error' => 'You cannot add yourself.'];

    $friendId = (int)$friend['id'];

    // Check if any friendship already exists in either direction
    $stmt = $db->prepare('SELECT id, status, user_id FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)');
    $stmt->execute([$userId, $friendId, $friendId, $userId]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'accepted') return ['error' => 'Already friends.'];
        if ((int)$existing['user_id'] === $userId) return ['error' => 'Friend request already sent.'];
        // They sent us a request - accept it
        $db->prepare('UPDATE friendships SET status = \'accepted\' WHERE id = ?')->execute([$existing['id']]);
        return ['ok' => true, 'status' => 'accepted', 'message' => 'Friend request accepted!'];
    }

    $db->prepare('INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, \'pending\')')->execute([$userId, $friendId]);
    return ['ok' => true, 'status' => 'pending', 'message' => 'Friend request sent!'];
}

function acceptFriendRequest($userId, $requestId) {
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM friendships WHERE id = ? AND friend_id = ? AND status = \'pending\'');
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    if (!$request) return ['error' => 'Friend request not found.'];

    $db->prepare('UPDATE friendships SET status = \'accepted\' WHERE id = ?')->execute([$requestId]);
    return ['ok' => true];
}

function declineFriendRequest($userId, $requestId) {
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM friendships WHERE id = ? AND friend_id = ? AND status = \'pending\'');
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    if (!$request) return ['error' => 'Friend request not found.'];

    $db->prepare('DELETE FROM friendships WHERE id = ?')->execute([$requestId]);
    return ['ok' => true];
}

function removeFriend($userId, $friendshipId) {
    $db = getDb();
    $stmt = $db->prepare('DELETE FROM friendships WHERE id = ? AND (user_id = ? OR friend_id = ?)');
    $stmt->execute([$friendshipId, $userId, $userId]);
    if ($stmt->rowCount() === 0) return ['error' => 'Friendship not found.'];
    return ['ok' => true];
}

function getFriendsList($userId) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT f.id as friendship_id, f.status, f.created_at,
               CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END as friend_user_id,
               u.username, u.created_at as member_since
        FROM friendships f
        JOIN users u ON u.id = CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END
        WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
        ORDER BY u.username COLLATE NOCASE
    ");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $friends = $stmt->fetchAll();

    foreach ($friends as &$f) {
        $sStmt = $db->prepare('SELECT stats_json FROM user_stats WHERE user_id = ?');
        $sStmt->execute([$f['friend_user_id']]);
        $sRow = $sStmt->fetch();
        $stats = $sRow ? (json_decode($sRow['stats_json'], true) ?: []) : [];

        $totalGames = 0;
        $totalWins = 0;
        foreach (($stats['modes'] ?? []) as $m) {
            $totalGames += ($m['gamesPlayed'] ?? 0);
            $totalWins += ($m['gamesWon'] ?? 0);
        }
        $f['total_games'] = $totalGames;
        $f['total_wins'] = $totalWins;
        $f['friend_user_id'] = (int)$f['friend_user_id'];
    }

    return $friends;
}

function getPendingRequests($userId) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT f.id as request_id, f.created_at, u.id as from_user_id, u.username
        FROM friendships f
        JOIN users u ON u.id = f.user_id
        WHERE f.friend_id = ? AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getSentRequests($userId) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT f.id as request_id, f.created_at, u.id as to_user_id, u.username
        FROM friendships f
        JOIN users u ON u.id = f.friend_id
        WHERE f.user_id = ? AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getPublicProfile($username) {
    $db = getDb();
    $stmt = $db->prepare('SELECT id, username, created_at FROM users WHERE username = ? COLLATE NOCASE');
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();

    if (!$user) return null;

    $userId = (int)$user['id'];

    $statsRow = $db->prepare('SELECT stats_json FROM user_stats WHERE user_id = ?');
    $statsRow->execute([$userId]);
    $row = $statsRow->fetch();
    $stats = $row ? (json_decode($row['stats_json'], true) ?: ['modes' => [], 'genres' => []]) : ['modes' => [], 'genres' => []];

    $totalGames = 0;
    $totalWins = 0;
    foreach (($stats['modes'] ?? []) as $m) {
        $totalGames += ($m['gamesPlayed'] ?? 0);
        $totalWins += ($m['gamesWon'] ?? 0);
    }

    $streak = getUserDailyStreak($userId);

    return [
        'id' => $userId,
        'username' => $user['username'],
        'created_at' => $user['created_at'],
        'total_games' => $totalGames,
        'total_wins' => $totalWins,
        'streak' => $streak,
        'stats' => $stats,
    ];
}

function getFriendshipStatus($userId, $targetUserId) {
    $db = getDb();
    $stmt = $db->prepare('SELECT id, status, user_id, friend_id FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)');
    $stmt->execute([$userId, $targetUserId, $targetUserId, $userId]);
    $f = $stmt->fetch();
    if (!$f) return ['status' => 'none'];
    return [
        'friendship_id' => (int)$f['id'],
        'status' => $f['status'],
        'direction' => (int)$f['user_id'] === $userId ? 'sent' : 'received',
    ];
}

function searchUsers($query, $currentUserId, $limit = 10) {
    $query = trim($query);
    if (strlen($query) < 2) return [];

    $db = getDb();
    $stmt = $db->prepare('SELECT id, username, created_at FROM users WHERE username LIKE ? AND id != ? LIMIT ?');
    $stmt->execute(['%' . $query . '%', $currentUserId, $limit]);
    return $stmt->fetchAll();
}
