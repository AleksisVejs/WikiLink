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

function areUsersFriends($userId, $otherUserId) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT 1
        FROM friendships
        WHERE status = 'accepted'
          AND ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
        LIMIT 1
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    return (bool)$stmt->fetchColumn();
}

function createGameInvite($senderUserId, $receiverUsername, $startTitle, $endTitle) {
    $receiverUsername = trim((string)$receiverUsername);
    if ($receiverUsername === '') return ['error' => 'Friend username is required.'];
    if (trim((string)$startTitle) === '' || trim((string)$endTitle) === '') {
        return ['error' => 'A valid start and end article are required.'];
    }

    $db = getDb();
    $stmt = $db->prepare('SELECT id, username FROM users WHERE username = ? COLLATE NOCASE');
    $stmt->execute([$receiverUsername]);
    $receiver = $stmt->fetch();
    if (!$receiver) return ['error' => 'User not found.'];

    $receiverId = (int)$receiver['id'];
    if ($receiverId === (int)$senderUserId) return ['error' => 'You cannot invite yourself.'];
    if (!areUsersFriends((int)$senderUserId, $receiverId)) return ['error' => 'You can only invite friends.'];

    if (getOpenMatchForUser((int)$senderUserId) || getOpenLobbyForUser((int)$senderUserId)) {
        return ['error' => 'You are already in another room.'];
    }
    if (getOpenMatchForUser($receiverId) || getOpenLobbyForUser($receiverId)) {
        return ['error' => $receiver['username'] . ' is already in another room.'];
    }

    $result = createMatch($startTitle, $endTitle, (int)$senderUserId);
    if (isset($result['error'])) return $result;

    $db->prepare("
        INSERT INTO game_invites (sender_user_id, receiver_user_id, match_code, status)
        VALUES (?, ?, ?, 'pending')
    ")->execute([(int)$senderUserId, $receiverId, $result['code']]);
    $inviteId = (int)$db->lastInsertId();

    return [
        'ok' => true,
        'invite' => [
            'id' => $inviteId,
            'status' => 'pending',
            'target_username' => $receiver['username'],
            'match_code' => $result['code'],
            'start_title' => $result['start_title'],
            'end_title' => $result['end_title'],
        ],
    ];
}

function getIncomingGameInvites($userId) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT gi.id, gi.status, gi.created_at, gi.match_code,
               u.id AS sender_user_id, u.username AS sender_username
        FROM game_invites gi
        JOIN users u ON u.id = gi.sender_user_id
        JOIN matches m ON m.code = gi.match_code
        WHERE gi.receiver_user_id = ?
          AND gi.status = 'pending'
          AND m.status = 'waiting'
        ORDER BY gi.created_at DESC
    ");
    $stmt->execute([(int)$userId]);
    return $stmt->fetchAll();
}

function getGameInviteByIdForUser($inviteId, $userId) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT gi.id, gi.sender_user_id, gi.receiver_user_id, gi.match_code, gi.status, gi.created_at, gi.responded_at,
               sender.username AS sender_username, receiver.username AS receiver_username,
               m.start_title, m.end_title, m.status AS match_status
        FROM game_invites gi
        JOIN users sender ON sender.id = gi.sender_user_id
        JOIN users receiver ON receiver.id = gi.receiver_user_id
        JOIN matches m ON m.code = gi.match_code
        WHERE gi.id = ?
          AND (gi.sender_user_id = ? OR gi.receiver_user_id = ?)
        LIMIT 1
    ");
    $stmt->execute([(int)$inviteId, (int)$userId, (int)$userId]);
    $invite = $stmt->fetch();
    if (!$invite) return ['error' => 'Invite not found.'];

    return [
        'id' => (int)$invite['id'],
        'sender_user_id' => (int)$invite['sender_user_id'],
        'receiver_user_id' => (int)$invite['receiver_user_id'],
        'sender_username' => $invite['sender_username'],
        'receiver_username' => $invite['receiver_username'],
        'match_code' => $invite['match_code'],
        'match_status' => $invite['match_status'],
        'start_title' => $invite['start_title'],
        'end_title' => $invite['end_title'],
        'status' => $invite['status'],
        'created_at' => $invite['created_at'],
        'responded_at' => $invite['responded_at'],
    ];
}

function respondToGameInvite($inviteId, $receiverUserId, $action) {
    $action = strtolower(trim((string)$action));
    if ($action !== 'accept' && $action !== 'deny') {
        return ['error' => 'Invalid invite action.'];
    }

    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM game_invites WHERE id = ? AND receiver_user_id = ? LIMIT 1");
    $stmt->execute([(int)$inviteId, (int)$receiverUserId]);
    $invite = $stmt->fetch();
    if (!$invite) return ['error' => 'Invite not found.'];
    if ($invite['status'] !== 'pending') return ['error' => 'Invite already handled.'];

    ensureMatchTable();
    $matchLookup = $db->prepare("SELECT status, player2_id FROM matches WHERE code = ? LIMIT 1");
    $matchLookup->execute([$invite['match_code']]);
    $match = $matchLookup->fetch();
    if (!$match || $match['status'] !== 'waiting') {
        return ['error' => 'This invite is no longer available.'];
    }
    if (!empty($match['player2_id']) && (int)$match['player2_id'] !== (int)$receiverUserId) {
        return ['error' => 'This invite is no longer available.'];
    }

    if ($action === 'deny') {
        $db->prepare("UPDATE game_invites SET status = 'declined', responded_at = datetime('now') WHERE id = ?")
            ->execute([(int)$inviteId]);
        $db->prepare("UPDATE matches SET status = 'finished' WHERE code = ?")
            ->execute([$invite['match_code']]);
        return ['ok' => true, 'status' => 'declined'];
    }

    $join = joinMatch($invite['match_code'], (int)$receiverUserId);
    if (isset($join['error'])) return $join;

    $db->prepare("UPDATE game_invites SET status = 'accepted', responded_at = datetime('now') WHERE id = ?")
        ->execute([(int)$inviteId]);

    return [
        'ok' => true,
        'status' => 'accepted',
        'match' => [
            'code' => $join['code'],
            'start_title' => $join['start_title'],
            'end_title' => $join['end_title'],
            'status' => $join['status'],
        ],
    ];
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
