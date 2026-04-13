<?php
require_once __DIR__ . '/db.php';

function generateToken() {
    return bin2hex(random_bytes(32));
}

function registerUser($username, $password) {
    $username = trim($username);
    if (strlen($username) < 2 || strlen($username) > 24) {
        return ['error' => 'Username must be 2-24 characters.'];
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return ['error' => 'Username may only contain letters, numbers, _ and -.'];
    }
    if (strlen($password) < 4) {
        return ['error' => 'Password must be at least 4 characters.'];
    }

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
    $stmt = $db->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['error' => 'Invalid username or password.'];
    }

    $token = createSession((int)$user['id']);
    return ['user' => ['id' => (int)$user['id'], 'username' => $user['username']], 'token' => $token];
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
        SELECT u.id, u.username, s.id as session_id
        FROM sessions s JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > datetime('now')
    ");
    $stmt->execute([$m[1]]);
    $row = $stmt->fetch();
    return $row ? ['id' => (int)$row['id'], 'username' => $row['username'], 'session_id' => (int)$row['session_id']] : null;
}

function logoutUser($sessionId) {
    $db = getDb();
    $db->prepare('DELETE FROM sessions WHERE id = ?')->execute([$sessionId]);
}
