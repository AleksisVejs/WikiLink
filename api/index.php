<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Top-level error handler so exceptions return JSON instead of a blank 500
try {

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/daily.php';
require_once __DIR__ . '/challenge.php';
require_once __DIR__ . '/stats.php';

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

// --- Routing ---

// POST /register
if ($method === 'POST' && $uri === '/register') {
    $body = jsonInput();
    $result = registerUser(
        isset($body['username']) ? $body['username'] : '',
        isset($body['password']) ? $body['password'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /login
if ($method === 'POST' && $uri === '/login') {
    $body = jsonInput();
    $result = loginUser(
        isset($body['username']) ? $body['username'] : '',
        isset($body['password']) ? $body['password'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 401 : 200);
}

// GET /me
if ($method === 'GET' && $uri === '/me') {
    $user = requireAuth();
    $streak = getUserDailyStreak($user['id']);
    jsonResponse(['user' => ['id' => $user['id'], 'username' => $user['username']], 'streak' => $streak]);
}

// POST /logout
if ($method === 'POST' && $uri === '/logout') {
    $user = requireAuth();
    logoutUser($user['session_id']);
    jsonResponse(['ok' => true]);
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
    $body = jsonInput();
    $date = gmdate('Y-m-d');
    $existing = getDailyPair($date);
    if ($existing) {
        jsonResponse($existing);
    }
    if (empty($body['start']) || empty($body['end'])) {
        jsonResponse(['error' => 'start and end are required'], 400);
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
    jsonResponse(['date' => $date, 'scores' => getDailyLeaderboard($date)]);
}

// POST /challenge
if ($method === 'POST' && $uri === '/challenge') {
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

// POST /stats/increment
if ($method === 'POST' && $uri === '/stats/increment') {
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
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
