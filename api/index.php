<?php
header('Content-Type: application/json');

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowedOrigins = ['http://localhost:5173', 'https://wikilink.fraksis.com'];
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} elseif (!$origin) {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/daily.php';
require_once __DIR__ . '/challenge.php';
require_once __DIR__ . '/stats.php';
require_once __DIR__ . '/user_stats.php';

$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$uri = parse_url($uri, PHP_URL_PATH);
$uri = preg_replace('#^/api#', '', $uri);
$uri = rtrim($uri, '/');
if ($uri === '') $uri = '/';
$method = $_SERVER['REQUEST_METHOD'];

function jsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function requireAuth() {
    $user = authenticateRequest();
    if (!$user) jsonResponse(['error' => 'Authentication required.'], 401);
    return $user;
}

function requireRateLimit($action, $max = 10, $window = 300) {
    if (!checkRateLimit($action, $max, $window)) {
        jsonResponse(['error' => 'Too many requests. Please wait a few minutes.'], 429);
    }
}

// --- Routing ---

// POST /register
if ($method === 'POST' && $uri === '/register') {
    requireRateLimit('register', 5, 300);
    $body = jsonInput();
    $result = registerUser(
        isset($body['username']) ? $body['username'] : '',
        isset($body['password']) ? $body['password'] : ''
    );
    if (!isset($result['error'])) {
        $userId = $result['user']['id'];
        $initialStats = (!empty($body['stats']) && is_array($body['stats'])) ? $body['stats'] : getDefaultStats();
        saveUserStats($userId, $initialStats);
        $result['stats'] = getUserStats($userId);
    }
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /login
if ($method === 'POST' && $uri === '/login') {
    requireRateLimit('login', 10, 300);
    $body = jsonInput();
    $result = loginUser(
        isset($body['username']) ? $body['username'] : '',
        isset($body['password']) ? $body['password'] : ''
    );
    if (!isset($result['error'])) {
        $userId = $result['user']['id'];
        $result['streak'] = getUserDailyStreak($userId);
        $result['stats'] = getUserStats($userId);
    }
    jsonResponse($result, isset($result['error']) ? 401 : 200);
}

// GET /me
if ($method === 'GET' && $uri === '/me') {
    $user = requireAuth();
    $streak = getUserDailyStreak($user['id']);
    jsonResponse([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'created_at' => $user['created_at'],
        ],
        'streak' => $streak,
        'stats' => getUserStats($user['id']),
    ]);
}

// POST /logout
if ($method === 'POST' && $uri === '/logout') {
    $user = requireAuth();
    logoutUser($user['session_id']);
    jsonResponse(['ok' => true]);
}

// POST /change-password
if ($method === 'POST' && $uri === '/change-password') {
    requireRateLimit('change_password', 5, 300);
    $user = requireAuth();
    $body = jsonInput();
    $result = changePassword(
        $user['id'],
        $user['session_id'],
        isset($body['currentPassword']) ? $body['currentPassword'] : '',
        isset($body['newPassword']) ? $body['newPassword'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /delete-account
if ($method === 'POST' && $uri === '/delete-account') {
    requireRateLimit('delete_account', 3, 300);
    $user = requireAuth();
    $body = jsonInput();
    $result = deleteAccount(
        $user['id'],
        isset($body['password']) ? $body['password'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// GET /daily
if ($method === 'GET' && $uri === '/daily') {
    $date = gmdate('Y-m-d');
    $pair = getDailyPair($date);
    if ($pair) {
        jsonResponse($pair);
    }
    jsonResponse(['date' => $date, 'start' => null, 'end' => null]);
}

// POST /daily
if ($method === 'POST' && $uri === '/daily') {
    requireRateLimit('daily_set', 3, 60);
    $body = jsonInput();
    $date = gmdate('Y-m-d');
    $existing = getDailyPair($date);
    if ($existing) {
        jsonResponse($existing);
    }
    if (empty($body['start']) || empty($body['end'])
        || !is_array($body['start']) || !is_array($body['end'])
        || empty($body['start']['title']) || empty($body['end']['title'])) {
        jsonResponse(['error' => 'Valid start and end article data are required.'], 400);
    }
    saveDailyPair($date, $body['start'], $body['end']);
    jsonResponse(getDailyPair($date));
}

// POST /daily/score
if ($method === 'POST' && $uri === '/daily/score') {
    $user = requireAuth();
    $body = jsonInput();
    $date = isset($body['date']) ? $body['date'] : gmdate('Y-m-d');
    $result = submitDailyScore(
        $user['id'],
        $date,
        (int)(isset($body['clicks']) ? $body['clicks'] : 0),
        (int)(isset($body['time']) ? $body['time'] : 0),
        isset($body['path']) ? $body['path'] : []
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// GET /daily/leaderboard
if ($method === 'GET' && $uri === '/daily/leaderboard') {
    $date = isset($_GET['date']) ? $_GET['date'] : gmdate('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonResponse(['error' => 'Invalid date format.'], 400);
    }
    jsonResponse(['date' => $date, 'scores' => getDailyLeaderboard($date)]);
}

// POST /challenge
if ($method === 'POST' && $uri === '/challenge') {
    requireRateLimit('challenge', 10, 60);
    $user = authenticateRequest();
    $body = jsonInput();
    $result = createChallenge(
        isset($body['startTitle']) ? $body['startTitle'] : '',
        isset($body['endTitle']) ? $body['endTitle'] : '',
        $user ? $user['id'] : null
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// GET /challenge/:code
if ($method === 'GET' && preg_match('#^/challenge/([A-Za-z0-9]+)$#', $uri, $m)) {
    $challenge = getChallenge($m[1]);
    if (!$challenge) jsonResponse(['error' => 'Challenge not found.'], 404);
    jsonResponse($challenge);
}

// GET /stats
if ($method === 'GET' && $uri === '/stats') {
    jsonResponse(getGlobalStats());
}

// POST /stats/game (logged-in: update user stats + global stats)
if ($method === 'POST' && $uri === '/stats/game') {
    $user = requireAuth();
    requireRateLimit('stats_game', 20, 60);
    $body = jsonInput();
    $mode = isset($body['mode']) ? $body['mode'] : 'classic';
    $genre = isset($body['genre']) ? $body['genre'] : 'random';
    $clicks = (int)(isset($body['clicks']) ? $body['clicks'] : 0);
    $time = (int)(isset($body['time']) ? $body['time'] : 0);
    $won = !empty($body['won']);
    $stats = recordGameResult($user['id'], $mode, $genre, $clicks, $time, $won);
    incrementGlobalGame($clicks, $won);
    jsonResponse(['ok' => true, 'stats' => $stats]);
}

// POST /stats/increment (anonymous: global stats only)
if ($method === 'POST' && $uri === '/stats/increment') {
    requireRateLimit('stats_inc', 20, 60);
    $body = jsonInput();
    incrementGlobalGame(
        (int)(isset($body['clicks']) ? $body['clicks'] : 0),
        !empty($body['won'])
    );
    jsonResponse(['ok' => true]);
}

// 404 catch-all
jsonResponse(['error' => 'Not found.'], 404);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal error occurred.']);
    error_log('WikiLink API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    exit;
}
