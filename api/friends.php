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
               u.username, u.profile_icon, u.profile_accent, u.profile_title, u.profile_banner, u.profile_nameplate_border, u.profile_pinned_badge, u.created_at as member_since
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
        $f['profile_icon'] = $f['profile_icon'] ?: 'rookie';
        $f['profile_accent'] = $f['profile_accent'] ?: 'rank';
        $f['profile_title'] = $f['profile_title'] ?: 'newcomer';
        $f['profile_banner'] = $f['profile_banner'] ?: 'default';
        $f['profile_nameplate_border'] = $f['profile_nameplate_border'] ?: 'default';
        $f['profile_pinned_badge'] = isset($f['profile_pinned_badge']) ? $f['profile_pinned_badge'] : '';
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
        INSERT INTO game_invites (sender_user_id, receiver_user_id, match_code, room_code, invite_type, status)
        VALUES (?, ?, ?, ?, 'match', 'pending')
    ")->execute([(int)$senderUserId, $receiverId, $result['code'], $result['code']]);
    $inviteId = (int)$db->lastInsertId();

    return [
        'ok' => true,
        'invite' => [
            'id' => $inviteId,
            'status' => 'pending',
            'target_username' => $receiver['username'],
            'invite_type' => 'match',
            'room_code' => $result['code'],
            'match_code' => $result['code'],
            'start_title' => $result['start_title'],
            'end_title' => $result['end_title'],
        ],
    ];
}

function createRoomInvite($senderUserId, $receiverUsername, $roomType, $roomCode) {
    $receiverUsername = trim((string)$receiverUsername);
    $roomType = strtolower(trim((string)$roomType));
    $roomCode = strtoupper(trim((string)$roomCode));
    if ($receiverUsername === '') return ['error' => 'Friend username is required.'];
    if ($roomType !== 'match' && $roomType !== 'lobby') return ['error' => 'Invalid room type.'];
    if ($roomCode === '') return ['error' => 'Room code is required.'];

    $db = getDb();
    $stmt = $db->prepare('SELECT id, username FROM users WHERE username = ? COLLATE NOCASE');
    $stmt->execute([$receiverUsername]);
    $receiver = $stmt->fetch();
    if (!$receiver) return ['error' => 'User not found.'];

    $receiverId = (int)$receiver['id'];
    if ($receiverId === (int)$senderUserId) return ['error' => 'You cannot invite yourself.'];
    if (!areUsersFriends((int)$senderUserId, $receiverId)) return ['error' => 'You can only invite friends.'];
    if (getOpenMatchForUser($receiverId) || getOpenLobbyForUser($receiverId)) {
        return ['error' => $receiver['username'] . ' is already in another room.'];
    }

    if ($roomType === 'match') {
        $room = getMatchStatus($roomCode, (int)$senderUserId);
        if (isset($room['error'])) return ['error' => 'Match not found.'];
        if (($room['status'] ?? '') !== 'waiting') return ['error' => 'Match is no longer joinable.'];
        if (!empty($room['opponent']['username'])) return ['error' => 'Match already has an opponent.'];

        $pendingInviteStmt = $db->prepare("
            SELECT id
            FROM game_invites
            WHERE sender_user_id = ?
              AND COALESCE(invite_type, 'match') = 'match'
              AND COALESCE(room_code, match_code) = ?
              AND status = 'pending'
            LIMIT 1
        ");
        $pendingInviteStmt->execute([(int)$senderUserId, $roomCode]);
        if ($pendingInviteStmt->fetch()) {
            return ['error' => 'You already sent a pending invite for this room.'];
        }
    } else {
        $room = getLobbyStatus($roomCode, (int)$senderUserId);
        if (isset($room['error'])) return ['error' => 'Lobby not found.'];
        if (($room['status'] ?? '') !== 'waiting') return ['error' => 'Lobby is no longer joinable.'];
        $isMember = false;
        foreach (($room['players'] ?? []) as $p) {
            if ((int)($p['user_id'] ?? 0) === (int)$senderUserId) {
                $isMember = true;
                break;
            }
        }
        if (!$isMember) return ['error' => 'Only lobby members can invite friends.'];
    }

    $db->prepare("
        INSERT INTO game_invites (sender_user_id, receiver_user_id, match_code, room_code, invite_type, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ")->execute([(int)$senderUserId, $receiverId, $roomCode, $roomCode, $roomType]);
    $inviteId = (int)$db->lastInsertId();

    return [
        'ok' => true,
        'invite' => [
            'id' => $inviteId,
            'status' => 'pending',
            'target_username' => $receiver['username'],
            'invite_type' => $roomType,
            'room_code' => $roomCode,
        ],
    ];
}

function getIncomingGameInvites($userId) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT gi.id, gi.status, gi.created_at, gi.match_code, gi.room_code, gi.invite_type,
               u.id AS sender_user_id, u.username AS sender_username,
               m.status AS match_status, l.status AS lobby_status
        FROM game_invites gi
        JOIN users u ON u.id = gi.sender_user_id
        LEFT JOIN matches m ON m.code = gi.match_code
        LEFT JOIN lobbies l ON l.code = COALESCE(gi.room_code, gi.match_code)
        WHERE gi.receiver_user_id = ?
          AND gi.status = 'pending'
          AND (
            (COALESCE(gi.invite_type, 'match') = 'match' AND m.status = 'waiting')
            OR
            (COALESCE(gi.invite_type, 'match') = 'lobby' AND l.status = 'waiting')
          )
        ORDER BY gi.created_at DESC
    ");
    $stmt->execute([(int)$userId]);
    return $stmt->fetchAll();
}

function getGameInviteByIdForUser($inviteId, $userId) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT gi.id, gi.sender_user_id, gi.receiver_user_id, gi.match_code, gi.room_code, gi.invite_type, gi.status, gi.created_at, gi.responded_at,
               sender.username AS sender_username, receiver.username AS receiver_username,
               m.start_title, m.end_title, m.status AS match_status,
               l.start_title AS lobby_start_title, l.end_title AS lobby_end_title, l.status AS lobby_status
        FROM game_invites gi
        JOIN users sender ON sender.id = gi.sender_user_id
        JOIN users receiver ON receiver.id = gi.receiver_user_id
        LEFT JOIN matches m ON m.code = gi.match_code
        LEFT JOIN lobbies l ON l.code = COALESCE(gi.room_code, gi.match_code)
        WHERE gi.id = ?
          AND (gi.sender_user_id = ? OR gi.receiver_user_id = ?)
        LIMIT 1
    ");
    $stmt->execute([(int)$inviteId, (int)$userId, (int)$userId]);
    $invite = $stmt->fetch();
    if (!$invite) return ['error' => 'Invite not found.'];

    $inviteType = $invite['invite_type'] ?: 'match';
    $roomCode = $invite['room_code'] ?: $invite['match_code'];
    return [
        'id' => (int)$invite['id'],
        'sender_user_id' => (int)$invite['sender_user_id'],
        'receiver_user_id' => (int)$invite['receiver_user_id'],
        'sender_username' => $invite['sender_username'],
        'receiver_username' => $invite['receiver_username'],
        'invite_type' => $inviteType,
        'room_code' => $roomCode,
        'match_code' => $invite['match_code'],
        'match_status' => $invite['match_status'],
        'start_title' => $inviteType === 'lobby' ? $invite['lobby_start_title'] : $invite['start_title'],
        'end_title' => $inviteType === 'lobby' ? $invite['lobby_end_title'] : $invite['end_title'],
        'room_status' => $inviteType === 'lobby' ? $invite['lobby_status'] : $invite['match_status'],
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

    $inviteType = isset($invite['invite_type']) ? strtolower(trim((string)$invite['invite_type'])) : '';
    $roomCode = trim((string)($invite['room_code'] ?: $invite['match_code']));
    if ($inviteType !== 'match' && $inviteType !== 'lobby') {
        // Backward-safe fallback: infer from existing room data instead of defaulting to match.
        $lobbyLookup = $db->prepare("SELECT status FROM lobbies WHERE code = ? LIMIT 1");
        $lobbyLookup->execute([$roomCode]);
        $lobby = $lobbyLookup->fetch();
        if ($lobby) {
            $inviteType = 'lobby';
        } else {
            $inviteType = 'match';
        }
    }

    if ($action === 'deny') {
        $db->prepare("UPDATE game_invites SET status = 'declined', responded_at = datetime('now') WHERE id = ?")
            ->execute([(int)$inviteId]);

        // If a direct 1v1 invite is denied, close that waiting room so the host can invite someone else.
        if ($inviteType === 'match' && $roomCode !== '') {
            ensureMatchTable();
            $closeStmt = $db->prepare("
                UPDATE matches
                SET status = 'finished',
                    p1_quit = 1
                WHERE code = ?
                  AND player1_id = ?
                  AND status = 'waiting'
            ");
            $closeStmt->execute([$roomCode, (int)$invite['sender_user_id']]);
        }

        return ['ok' => true, 'status' => 'declined'];
    }

    if ($inviteType === 'lobby') {
        $lobby = getLobbyStatus($roomCode, (int)$invite['sender_user_id']);
        if (isset($lobby['error']) || ($lobby['status'] ?? '') !== 'waiting') {
            return ['error' => 'This invite is no longer available.'];
        }
        $join = joinLobby($roomCode, (int)$receiverUserId);
        if (isset($join['error'])) return $join;

        $db->prepare("UPDATE game_invites SET status = 'accepted', responded_at = datetime('now') WHERE id = ?")
            ->execute([(int)$inviteId]);
        return [
            'ok' => true,
            'status' => 'accepted',
            'roomType' => 'lobby',
            'lobby' => [
                'code' => $join['code'],
                'start_title' => $join['start_title'],
                'end_title' => $join['end_title'],
                'status' => $join['status'],
            ],
        ];
    }

    ensureMatchTable();
    $matchLookup = $db->prepare("SELECT status, player2_id FROM matches WHERE code = ? LIMIT 1");
    $matchLookup->execute([$roomCode]);
    $match = $matchLookup->fetch();
    if (!$match || $match['status'] !== 'waiting') {
        return ['error' => 'This invite is no longer available.'];
    }
    if (!empty($match['player2_id']) && (int)$match['player2_id'] !== (int)$receiverUserId) {
        return ['error' => 'This invite is no longer available.'];
    }

    $join = joinMatch($roomCode, (int)$receiverUserId);
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
    $stmt = $db->prepare('SELECT id, username, profile_icon, profile_accent, profile_title, profile_banner, profile_nameplate_border, profile_pinned_badge, created_at FROM users WHERE username = ? COLLATE NOCASE');
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
    $dailyStmt = $db->prepare('SELECT COUNT(*) as total FROM daily_scores WHERE user_id = ?');
    $dailyStmt->execute([$userId]);
    $dailyCompletions = (int)($dailyStmt->fetch()['total'] ?? 0);
    $achievementIds = getPublicAchievementIds($stats, $streak, $dailyCompletions);

    return [
        'id' => $userId,
        'username' => $user['username'],
        'profile_icon' => $user['profile_icon'] ?: 'rookie',
        'profile_accent' => $user['profile_accent'] ?: 'rank',
        'profile_title' => $user['profile_title'] ?: 'newcomer',
        'profile_banner' => $user['profile_banner'] ?: 'default',
        'profile_nameplate_border' => $user['profile_nameplate_border'] ?: 'default',
        'profile_pinned_badge' => isset($user['profile_pinned_badge']) ? $user['profile_pinned_badge'] : '',
        'created_at' => $user['created_at'],
        'total_games' => $totalGames,
        'total_wins' => $totalWins,
        'streak' => $streak,
        'stats' => $stats,
        'friends' => getFriendsList($userId),
        'achievements' => [
            'unlocked_ids' => $achievementIds,
            'unlocked_count' => count($achievementIds),
        ],
    ];
}

function getPublicAchievementIds($stats, $streak, $dailyCompletions = 0) {
    $modeStats = is_array($stats['modes'] ?? null) ? $stats['modes'] : [];
    $genreStats = is_array($stats['genres'] ?? null) ? $stats['genres'] : [];
    $unlocked = [];

    $totalWins = 0;
    $totalPlayed = 0;
    $bestAnyTime = null;
    $bestAnyClicks = null;

    foreach ($modeStats as $m) {
        $wins = (int)($m['gamesWon'] ?? 0);
        $played = (int)($m['gamesPlayed'] ?? 0);
        $bestTime = $m['bestTime'] ?? null;
        $bestClicks = $m['bestClicks'] ?? null;
        $currentStreak = (int)($m['currentStreak'] ?? 0);

        $totalWins += $wins;
        $totalPlayed += $played;

        if ($bestTime !== null) {
            $bestTimeInt = (int)$bestTime;
            if ($bestAnyTime === null || $bestTimeInt < $bestAnyTime) $bestAnyTime = $bestTimeInt;
        }
        if ($bestClicks !== null) {
            $bestClicksInt = (int)$bestClicks;
            if ($bestAnyClicks === null || $bestClicksInt < $bestAnyClicks) $bestAnyClicks = $bestClicksInt;
        }

        if ($currentStreak >= 3) $unlocked['streak_3'] = true;
        if ($currentStreak >= 10) $unlocked['streak_10'] = true;
    }

    $genresWon = 0;
    foreach ($genreStats as $g) {
        if ((int)($g['gamesWon'] ?? 0) > 0) $genresWon++;
    }

    if ($totalWins >= 1) $unlocked['first_blood'] = true;
    if ($totalWins >= 10) $unlocked['ten_wins'] = true;
    if ($totalWins >= 50) $unlocked['fifty_wins'] = true;
    if ($totalWins >= 100) $unlocked['century'] = true;
    if ($totalPlayed >= 50) $unlocked['wiki_wanderer'] = true;

    if ($bestAnyTime !== null && $bestAnyTime <= 15) $unlocked['lightning'] = true;
    else if ($bestAnyTime !== null && $bestAnyTime <= 30) $unlocked['speed_demon'] = true;

    if ($bestAnyClicks !== null && $bestAnyClicks <= 2) $unlocked['minimalist'] = true;
    else if ($bestAnyClicks !== null && $bestAnyClicks <= 3) $unlocked['straight_line'] = true;
    else if ($bestAnyClicks !== null && $bestAnyClicks <= 5) $unlocked['pathfinder'] = true;

    if ($genresWon >= 5) $unlocked['globe_trotter'] = true;
    if ((int)($modeStats['sprint']['gamesWon'] ?? 0) >= 10) $unlocked['sprinter'] = true;
    if ((int)($modeStats['challenge']['gamesWon'] ?? 0) >= 10) $unlocked['sharpshooter'] = true;
    if ((int)($modeStats['trending']['gamesWon'] ?? 0) >= 5) $unlocked['trendsetter'] = true;
    if ($streak >= 3) $unlocked['streak_3'] = true;
    if ($streak >= 10) $unlocked['streak_10'] = true;
    if ($dailyCompletions >= 7) $unlocked['daily_devotee'] = true;
    if ($dailyCompletions >= 30) $unlocked['ironman'] = true;

    // Requires per-round modifier telemetry and cannot be inferred from aggregates.
    // $unlocked['modifier_master']
    // $unlocked['no_hints']
    // $unlocked['close_call']

    return array_values(array_keys($unlocked));
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
