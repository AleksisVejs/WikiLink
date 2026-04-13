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
    $stmt = $db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
    $stmt->execute([$username, $hash]);
    $userId = (int)$db->lastInsertId();

    $token = createSession($userId);
    return ['user' => ['id' => $userId, 'username' => $username], 'token' => $token];
}

function loginUser($username, $password) {
    $db = getDb();
    $stmt = $db->prepare('SELECT id, username, password_hash, created_at FROM users WHERE username = ?');
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['error' => 'Invalid username or password.'];
    }

    $token = createSession((int)$user['id']);
    return [
        'user' => ['id' => (int)$user['id'], 'username' => $user['username'], 'created_at' => $user['created_at']],
        'token' => $token,
    ];
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
        SELECT u.id, u.username, u.created_at, s.id as session_id
        FROM sessions s JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > datetime('now')
    ");
    $stmt->execute([$m[1]]);
    $row = $stmt->fetch();
    return $row ? [
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'created_at' => $row['created_at'],
        'session_id' => (int)$row['session_id'],
    ] : null;
}

function logoutUser($sessionId) {
    $db = getDb();
    $db->prepare('DELETE FROM sessions WHERE id = ?')->execute([$sessionId]);
}
