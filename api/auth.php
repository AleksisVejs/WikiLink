<?php
require_once __DIR__ . '/db.php';

const MIN_PASSWORD_LENGTH = 6;

function generateToken() {
    return bin2hex(random_bytes(32));
}

function validatePassword($password) {
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        return 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    }
    return null;
}

function registerUser($username, $password) {
    $username = trim($username);
    if (strlen($username) < 2 || strlen($username) > 24) {
        return ['error' => 'Username must be 2-24 characters.'];
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return ['error' => 'Username may only contain letters, numbers, _ and -.'];
    }
    $pwErr = validatePassword($password);
    if ($pwErr) return ['error' => $pwErr];

    $db = getDb();
    $existing = $db->prepare('SELECT id FROM users WHERE username = ?');
    $existing->execute([$username]);
    if ($existing->fetch()) {
        return ['error' => 'Username already taken.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, profile_icon, profile_accent, profile_title, profile_banner, profile_nameplate_border, profile_pinned_badge) VALUES (?, ?, 'rookie', 'rank', 'newcomer', 'default', 'default', '')");
    $stmt->execute([$username, $hash]);
    $userId = (int)$db->lastInsertId();

    $token = createSession($userId);
    return ['user' => [
        'id' => $userId,
        'username' => $username,
        'profile_icon' => 'rookie',
        'profile_accent' => 'rank',
        'profile_title' => 'newcomer',
        'profile_banner' => 'default',
        'profile_nameplate_border' => 'default',
        'profile_pinned_badge' => '',
    ], 'token' => $token];
}

function loginUser($username, $password) {
    $db = getDb();
    $stmt = $db->prepare('SELECT id, username, password_hash, profile_icon, profile_accent, profile_title, profile_banner, profile_nameplate_border, profile_pinned_badge, created_at FROM users WHERE username = ?');
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['error' => 'Invalid username or password.'];
    }

    $token = createSession((int)$user['id']);
    return [
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'profile_icon' => $user['profile_icon'] ?: 'rookie',
            'profile_accent' => $user['profile_accent'] ?: 'rank',
            'profile_title' => $user['profile_title'] ?: 'newcomer',
            'profile_banner' => $user['profile_banner'] ?: 'default',
            'profile_nameplate_border' => $user['profile_nameplate_border'] ?: 'default',
            'profile_pinned_badge' => isset($user['profile_pinned_badge']) ? $user['profile_pinned_badge'] : '',
            'created_at' => $user['created_at'],
        ],
        'token' => $token,
    ];
}

function updateProfileCustomization($userId, $iconId = null, $accentId = null, $titleId = null, $bannerId = null, $nameplateBorderId = null, $pinnedBadgeId = null) {
    $allowedIcons = ['rookie', 'compass', 'spark', 'crown', 'star'];
    $allowedAccents = ['rank', 'neon', 'cyan', 'amber', 'purple'];
    $allowedTitles = ['newcomer', 'pathfinder', 'specialist', 'veteran', 'expert', 'master', 'grandmaster', 'legend'];
    $allowedBanners = ['default', 'matrix', 'sunset', 'royal'];
    $allowedNameplateBorders = ['default', 'dashed', 'double', 'glow'];
    $iconMinLevel = ['rookie' => 1, 'compass' => 5, 'spark' => 10, 'crown' => 15, 'star' => 20];
    $accentMinLevel = ['rank' => 1, 'neon' => 1, 'cyan' => 5, 'amber' => 10, 'purple' => 15];
    $titleMinLevel = ['newcomer' => 1, 'pathfinder' => 5, 'specialist' => 10, 'veteran' => 15, 'expert' => 20, 'master' => 30, 'grandmaster' => 40, 'legend' => 50];
    $bannerMinLevel = ['default' => 1, 'matrix' => 8, 'sunset' => 12, 'royal' => 18];
    $nameplateMinLevel = ['default' => 1, 'dashed' => 10, 'double' => 18, 'glow' => 25];

    $db = getDb();
    $cosmeticLevel = getUserCosmeticLevel($db, (int)$userId);

    $sets = [];
    $values = [];

    if ($iconId !== null) {
        $iconId = strtolower(trim((string)$iconId));
        if (!in_array($iconId, $allowedIcons, true)) {
            return ['error' => 'Invalid profile icon.'];
        }
        if ($cosmeticLevel < ($iconMinLevel[$iconId] ?? 1)) {
            return ['error' => 'This profile icon is not unlocked yet.'];
        }
        $sets[] = 'profile_icon = ?';
        $values[] = $iconId;
    }

    if ($accentId !== null) {
        $accentId = strtolower(trim((string)$accentId));
        if (!in_array($accentId, $allowedAccents, true)) {
            return ['error' => 'Invalid profile accent.'];
        }
        if ($cosmeticLevel < ($accentMinLevel[$accentId] ?? 1)) {
            return ['error' => 'This profile accent is not unlocked yet.'];
        }
        $sets[] = 'profile_accent = ?';
        $values[] = $accentId;
    }
    if ($titleId !== null) {
        $titleId = strtolower(trim((string)$titleId));
        if (!in_array($titleId, $allowedTitles, true)) {
            return ['error' => 'Invalid profile title.'];
        }
        if ($cosmeticLevel < ($titleMinLevel[$titleId] ?? 1)) {
            return ['error' => 'This profile title is not unlocked yet.'];
        }
        $sets[] = 'profile_title = ?';
        $values[] = $titleId;
    }
    if ($bannerId !== null) {
        $bannerId = strtolower(trim((string)$bannerId));
        if (!in_array($bannerId, $allowedBanners, true)) {
            return ['error' => 'Invalid profile banner.'];
        }
        if ($cosmeticLevel < ($bannerMinLevel[$bannerId] ?? 1)) {
            return ['error' => 'This profile banner is not unlocked yet.'];
        }
        $sets[] = 'profile_banner = ?';
        $values[] = $bannerId;
    }
    if ($nameplateBorderId !== null) {
        $nameplateBorderId = strtolower(trim((string)$nameplateBorderId));
        if (!in_array($nameplateBorderId, $allowedNameplateBorders, true)) {
            return ['error' => 'Invalid profile nameplate border.'];
        }
        if ($cosmeticLevel < ($nameplateMinLevel[$nameplateBorderId] ?? 1)) {
            return ['error' => 'This profile nameplate border is not unlocked yet.'];
        }
        $sets[] = 'profile_nameplate_border = ?';
        $values[] = $nameplateBorderId;
    }
    if ($pinnedBadgeId !== null) {
        $pinnedBadgeId = trim((string)$pinnedBadgeId);
        if (strlen($pinnedBadgeId) > 64) {
            return ['error' => 'Pinned badge id is too long.'];
        }
        if ($pinnedBadgeId !== '') {
            $statsStmt = $db->prepare('SELECT stats_json FROM user_stats WHERE user_id = ?');
            $statsStmt->execute([(int)$userId]);
            $statsRow = $statsStmt->fetch();
            $stats = $statsRow ? (json_decode($statsRow['stats_json'], true) ?: ['modes' => [], 'genres' => []]) : ['modes' => [], 'genres' => []];
            $dailyStmt = $db->prepare('SELECT COUNT(*) as total FROM daily_scores WHERE user_id = ?');
            $dailyStmt->execute([(int)$userId]);
            $dailyCompletions = (int)($dailyStmt->fetch()['total'] ?? 0);
            $streak = function_exists('getUserDailyStreak') ? (int)getUserDailyStreak((int)$userId) : 0;
            $unlockedIds = function_exists('getPublicAchievementIds')
                ? getPublicAchievementIds($stats, $streak, $dailyCompletions)
                : [];
            if (!in_array($pinnedBadgeId, $unlockedIds, true)) {
                return ['error' => 'Pinned badge is not unlocked.'];
            }
        }
        $sets[] = 'profile_pinned_badge = ?';
        $values[] = $pinnedBadgeId;
    }

    if (empty($sets)) {
        return ['error' => 'No customization changes provided.'];
    }

    $values[] = (int)$userId;
    $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $db->prepare($sql)->execute($values);

    $read = $db->prepare('SELECT profile_icon, profile_accent, profile_title, profile_banner, profile_nameplate_border, profile_pinned_badge FROM users WHERE id = ?');
    $read->execute([(int)$userId]);
    $row = $read->fetch();
    return [
        'ok' => true,
        'profile_icon' => ($row['profile_icon'] ?? 'rookie') ?: 'rookie',
        'profile_accent' => ($row['profile_accent'] ?? 'rank') ?: 'rank',
        'profile_title' => ($row['profile_title'] ?? 'newcomer') ?: 'newcomer',
        'profile_banner' => ($row['profile_banner'] ?? 'default') ?: 'default',
        'profile_nameplate_border' => ($row['profile_nameplate_border'] ?? 'default') ?: 'default',
        'profile_pinned_badge' => isset($row['profile_pinned_badge']) ? $row['profile_pinned_badge'] : '',
    ];
}

function getUserCosmeticLevel($db, $userId) {
    $statsStmt = $db->prepare('SELECT stats_json FROM user_stats WHERE user_id = ?');
    $statsStmt->execute([(int)$userId]);
    $statsRow = $statsStmt->fetch();
    $stats = $statsRow ? (json_decode($statsRow['stats_json'], true) ?: ['modes' => []]) : ['modes' => []];
    $totalWins = 0;
    foreach (($stats['modes'] ?? []) as $m) {
        $totalWins += (int)($m['gamesWon'] ?? 0);
    }
    $dailyStmt = $db->prepare('SELECT COUNT(*) as total FROM daily_scores WHERE user_id = ?');
    $dailyStmt->execute([(int)$userId]);
    $dailyCompletions = (int)($dailyStmt->fetch()['total'] ?? 0);
    $estimatedLevel = 1 + (int)floor($totalWins / 2) + (int)floor($dailyCompletions / 5);
    return max(1, min(50, $estimatedLevel));
}

function changePassword($userId, $sessionId, $currentPassword, $newPassword) {
    $db = getDb();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        return ['error' => 'Current password is incorrect.'];
    }
    $pwErr = validatePassword($newPassword);
    if ($pwErr) return ['error' => $pwErr];

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);

    $db->prepare('DELETE FROM sessions WHERE user_id = ? AND id != ?')
       ->execute([$userId, $sessionId]);

    return ['ok' => true];
}

function deleteAccount($userId, $password) {
    $db = getDb();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['error' => 'Password is incorrect.'];
    }

    $db->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$userId]);
    $db->prepare('DELETE FROM daily_scores WHERE user_id = ?')->execute([$userId]);
    $db->prepare('DELETE FROM user_stats WHERE user_id = ?')->execute([$userId]);
    $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);

    return ['ok' => true];
}

function createSession($userId) {
    $db = getDb();
    $token = generateToken();
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    $stmt = $db->prepare('INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $token, $expires]);

    $db->exec("DELETE FROM sessions WHERE expires_at < datetime('now')");
    return $token;
}

function authenticateRequest() {
    $header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return null;

    $db = getDb();
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.profile_icon, u.profile_accent, u.profile_title, u.profile_banner, u.profile_nameplate_border, u.profile_pinned_badge, u.created_at, s.id as session_id
        FROM sessions s JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > datetime('now')
    ");
    $stmt->execute([$m[1]]);
    $row = $stmt->fetch();
    return $row ? [
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'profile_icon' => $row['profile_icon'] ?: 'rookie',
        'profile_accent' => $row['profile_accent'] ?: 'rank',
        'profile_title' => $row['profile_title'] ?: 'newcomer',
        'profile_banner' => $row['profile_banner'] ?: 'default',
        'profile_nameplate_border' => $row['profile_nameplate_border'] ?: 'default',
        'profile_pinned_badge' => isset($row['profile_pinned_badge']) ? $row['profile_pinned_badge'] : '',
        'created_at' => $row['created_at'],
        'session_id' => (int)$row['session_id'],
    ] : null;
}

function logoutUser($sessionId) {
    $db = getDb();
    $db->prepare('DELETE FROM sessions WHERE id = ?')->execute([$sessionId]);
}
