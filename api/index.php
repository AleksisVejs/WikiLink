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
require_once __DIR__ . '/trending.php';
require_once __DIR__ . '/multiplayer_settings.php';
require_once __DIR__ . '/match.php';
require_once __DIR__ . '/lobby.php';
require_once __DIR__ . '/friends.php';

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

function getCleanupKey() {
    $key = getenv('WIKILINK_CLEANUP_KEY');
    if ($key && trim($key) !== '') return trim($key);
    if (!empty($_SERVER['WIKILINK_CLEANUP_KEY'])) return trim($_SERVER['WIKILINK_CLEANUP_KEY']);
    return null;
}

function requireCleanupAuth() {
    $configured = getCleanupKey();
    if (!$configured) {
        return true;
    }
    $provided = isset($_GET['key']) ? (string)$_GET['key'] : '';
    if ($provided === $configured) return true;
    jsonResponse(['error' => 'Forbidden.'], 403);
}

function ensureNotBusyInAnotherRoom($userId, $targetType, $targetCode = null) {
    $openMatch = getOpenMatchForUser($userId);
    if ($openMatch) {
        $same = ($targetType === 'match' && $targetCode && strtoupper($openMatch['code']) === strtoupper($targetCode));
        if (!$same) {
            jsonResponse(['error' => 'You are already in another 1v1 match. Leave it before creating or joining a new room.'], 400);
        }
    }

    $openLobby = getOpenLobbyForUser($userId);
    if ($openLobby) {
        $same = ($targetType === 'lobby' && $targetCode && strtoupper($openLobby['code']) === strtoupper($targetCode));
        if (!$same) {
            jsonResponse(['error' => 'You are already in another group lobby. Leave it before creating or joining a new room.'], 400);
        }
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
            'profile_icon' => $user['profile_icon'] ?: 'rookie',
            'profile_accent' => $user['profile_accent'] ?: 'rank',
            'profile_title' => $user['profile_title'] ?: 'newcomer',
            'profile_banner' => $user['profile_banner'] ?: 'default',
            'profile_nameplate_border' => $user['profile_nameplate_border'] ?: 'default',
            'profile_pinned_badge' => isset($user['profile_pinned_badge']) ? $user['profile_pinned_badge'] : '',
            'created_at' => $user['created_at'],
        ],
        'streak' => $streak,
        'stats' => getUserStats($user['id']),
    ]);
}

// POST /profile/icon
if ($method === 'POST' && $uri === '/profile/icon') {
    $user = requireAuth();
    $body = jsonInput();
    $result = updateProfileCustomization($user['id'], isset($body['icon']) ? $body['icon'] : null, null);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /profile/customization
if ($method === 'POST' && $uri === '/profile/customization') {
    $user = requireAuth();
    $body = jsonInput();
    $icon = array_key_exists('icon', $body) ? $body['icon'] : null;
    $accent = array_key_exists('accent', $body) ? $body['accent'] : null;
    $title = array_key_exists('title', $body) ? $body['title'] : null;
    $banner = array_key_exists('banner', $body) ? $body['banner'] : null;
    $nameplateBorder = array_key_exists('nameplateBorder', $body) ? $body['nameplateBorder'] : null;
    $pinnedBadge = array_key_exists('pinnedBadge', $body) ? $body['pinnedBadge'] : null;
    $result = updateProfileCustomization($user['id'], $icon, $accent, $title, $banner, $nameplateBorder, $pinnedBadge);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
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
    requireAuth();
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

// GET /daily/status (logged-in: current daily completion + total completions)
if ($method === 'GET' && $uri === '/daily/status') {
    $user = requireAuth();
    $date = isset($_GET['date']) ? $_GET['date'] : gmdate('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonResponse(['error' => 'Invalid date format.'], 400);
    }
    jsonResponse(getUserDailyStatus($user['id'], $date));
}

// GET /daily/session (logged-in: returns in-progress snapshot for today)
if ($method === 'GET' && $uri === '/daily/session') {
    $user = requireAuth();
    $date = isset($_GET['date']) ? $_GET['date'] : gmdate('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonResponse(['error' => 'Invalid date format.'], 400);
    }
    jsonResponse(getUserDailySession($user['id'], $date));
}

// POST /daily/session (logged-in: upsert in-progress snapshot)
if ($method === 'POST' && $uri === '/daily/session') {
    $user = requireAuth();
    requireRateLimit('daily_session_save', 6, 60);

    $body = jsonInput();
    $date = isset($body['date']) ? $body['date'] : gmdate('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonResponse(['error' => 'Invalid date format.'], 400);
    }

    $snapshot = isset($body['snapshot']) ? $body['snapshot'] : null;
    if (!is_array($snapshot) && !is_object($snapshot)) {
        jsonResponse(['error' => 'Snapshot payload is required.'], 400);
    }

    $result = upsertDailySession($user['id'], $date, $snapshot);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /daily/session/clear (logged-in: clears in-progress snapshot)
if ($method === 'POST' && $uri === '/daily/session/clear') {
    $user = requireAuth();
    requireRateLimit('daily_session_clear', 6, 60);

    $body = jsonInput();
    $date = isset($body['date']) ? $body['date'] : gmdate('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonResponse(['error' => 'Invalid date format.'], 400);
    }

    $result = clearUserDailySession($user['id'], $date);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
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

// GET /community-paths?start=...&end=...
if ($method === 'GET' && $uri === '/community-paths') {
    $start = isset($_GET['start']) ? $_GET['start'] : '';
    $end = isset($_GET['end']) ? $_GET['end'] : '';
    $viewer = authenticateRequest();
    $result = getCommunityPaths($start, $end, $viewer ? $viewer['id'] : null, 20);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// GET /community/pairs
if ($method === 'GET' && $uri === '/community/pairs') {
    $pairLimit = isset($_GET['pairLimit']) ? (int)$_GET['pairLimit'] : 24;
    $collectionLimit = isset($_GET['collectionLimit']) ? (int)$_GET['collectionLimit'] : 8;
    $collectionPairsLimit = isset($_GET['collectionPairsLimit']) ? (int)$_GET['collectionPairsLimit'] : 5;
    jsonResponse(getCommunityPairCatalog($pairLimit, $collectionLimit, $collectionPairsLimit));
}

// GET /community/hub
if ($method === 'GET' && $uri === '/community/hub') {
    $pairLimit = isset($_GET['pairLimit']) ? (int)$_GET['pairLimit'] : 30;
    $groupLimit = isset($_GET['groupLimit']) ? (int)$_GET['groupLimit'] : 12;
    $groupItemsLimit = isset($_GET['groupItemsLimit']) ? (int)$_GET['groupItemsLimit'] : 8;
    $viewer = authenticateRequest();
    jsonResponse(getCommunityHubData($pairLimit, $groupLimit, $groupItemsLimit, $viewer ? $viewer['id'] : null));
}

// GET /community/group/:id
if ($method === 'GET' && preg_match('#^/community/group/(\d+)$#', $uri, $m)) {
    $result = getCommunityGroupById((int)$m[1]);
    if (!$result) jsonResponse(['error' => 'Group not found.'], 404);
    jsonResponse($result);
}

// POST /community/pair
if ($method === 'POST' && $uri === '/community/pair') {
    $user = requireAuth();
    $body = jsonInput();
    $result = createCommunityPair(
        $user['id'],
        isset($body['startTitle']) ? $body['startTitle'] : '',
        isset($body['endTitle']) ? $body['endTitle'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /community/group
if ($method === 'POST' && $uri === '/community/group') {
    $user = requireAuth();
    $body = jsonInput();
    $result = createCommunityPairGroup(
        $user['id'],
        isset($body['name']) ? $body['name'] : '',
        isset($body['pairs']) ? $body['pairs'] : []
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /community/pair/delete
if ($method === 'POST' && $uri === '/community/pair/delete') {
    $user = requireAuth();
    $body = jsonInput();
    $result = deleteCommunityPair($user['id'], (int)(isset($body['pairId']) ? $body['pairId'] : 0));
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /community/group/delete
if ($method === 'POST' && $uri === '/community/group/delete') {
    $user = requireAuth();
    $body = jsonInput();
    $result = deleteCommunityGroup($user['id'], (int)(isset($body['groupId']) ? $body['groupId'] : 0));
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /community/pair/vote
if ($method === 'POST' && $uri === '/community/pair/vote') {
    $user = requireAuth();
    $body = jsonInput();
    $result = voteCommunityPair(
        $user['id'],
        (int)(isset($body['pairId']) ? $body['pairId'] : 0),
        (int)(isset($body['vote']) ? $body['vote'] : 0)
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /community/group/vote
if ($method === 'POST' && $uri === '/community/group/vote') {
    $user = requireAuth();
    $body = jsonInput();
    $result = voteCommunityGroup(
        $user['id'],
        (int)(isset($body['groupId']) ? $body['groupId'] : 0),
        (int)(isset($body['vote']) ? $body['vote'] : 0)
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /community-paths
if ($method === 'POST' && $uri === '/community-paths') {
    $user = requireAuth();
    $body = jsonInput();
    $result = submitCommunityPath(
        $user['id'],
        isset($body['startTitle']) ? $body['startTitle'] : '',
        isset($body['endTitle']) ? $body['endTitle'] : '',
        isset($body['path']) ? $body['path'] : []
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /community-paths/vote
if ($method === 'POST' && $uri === '/community-paths/vote') {
    $user = requireAuth();
    $body = jsonInput();
    $result = voteCommunityPath(
        $user['id'],
        (int)(isset($body['pathId']) ? $body['pathId'] : 0),
        (int)(isset($body['vote']) ? $body['vote'] : 0)
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
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

// POST /progression/sync (logged-in: sync total XP across devices)
if ($method === 'POST' && $uri === '/progression/sync') {
    $user = requireAuth();
    requireRateLimit('progression_sync', 30, 60);
    $body = jsonInput();
    $totalXp = (int)(isset($body['totalXp']) ? $body['totalXp'] : 0);
    $stats = setUserProgressionXp($user['id'], $totalXp);
    jsonResponse([
        'ok' => true,
        'totalXp' => (int)($stats['progression']['totalXp'] ?? 0),
        'stats' => $stats,
    ]);
}

// POST /achievements/sync (logged-in: sync achievement state across devices)
if ($method === 'POST' && $uri === '/achievements/sync') {
    $user = requireAuth();
    requireRateLimit('achievements_sync', 30, 60);
    $body = jsonInput();
    $unlocked = (isset($body['unlocked']) && is_array($body['unlocked'])) ? $body['unlocked'] : [];
    $noHintWins = (int)(isset($body['noHintWins']) ? $body['noHintWins'] : 0);
    $stats = setUserAchievementsState($user['id'], $unlocked, $noHintWins);
    jsonResponse([
        'ok' => true,
        'achievements' => $stats['achievements'] ?? ['unlocked' => [], 'noHintWins' => 0],
        'stats' => $stats,
    ]);
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

// GET /trending
if ($method === 'GET' && $uri === '/trending') {
    $articles = getTrendingArticles();
    if ($articles) {
        jsonResponse(['articles' => $articles]);
    }
    jsonResponse(['articles' => [], 'error' => 'Could not fetch trending articles.']);
}

// POST /match/create
if ($method === 'POST' && $uri === '/match/create') {
    $user = requireAuth();
    ensureNotBusyInAnotherRoom($user['id'], 'match');
    requireRateLimit('match_create', 10, 60);
    $body = jsonInput();
    $result = createMatch(
        isset($body['startTitle']) ? $body['startTitle'] : '',
        isset($body['endTitle']) ? $body['endTitle'] : '',
        $user['id'],
        isset($body['settings']) ? $body['settings'] : null
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /match/settings/:code
if ($method === 'POST' && preg_match('#^/match/settings/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $body = jsonInput();
    $result = updateMatchSettings($m[1], $user['id'], isset($body['settings']) ? $body['settings'] : null);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /match/join/:code
if ($method === 'POST' && preg_match('#^/match/join/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    ensureNotBusyInAnotherRoom($user['id'], 'match', $m[1]);
    jsonResponse(joinMatch($m[1], $user['id']));
}

// POST /match/submit/:code
if ($method === 'POST' && preg_match('#^/match/submit/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $body = jsonInput();
    $result = submitMatchResult(
        $m[1],
        $user['id'],
        (int)(isset($body['clicks']) ? $body['clicks'] : 0),
        (int)(isset($body['time']) ? $body['time'] : 0),
        isset($body['path']) ? $body['path'] : []
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /match/start/:code
if ($method === 'POST' && preg_match('#^/match/start/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = startMatch($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /match/quit/:code
if ($method === 'POST' && preg_match('#^/match/quit/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = quitMatch($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /match/replay/:code
if ($method === 'POST' && preg_match('#^/match/replay/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = requestMatchReplay($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /match/reseed/:code
if ($method === 'POST' && preg_match('#^/match/reseed/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $body = jsonInput();
    $result = reseedMatchPair(
        $m[1],
        $user['id'],
        isset($body['startTitle']) ? $body['startTitle'] : '',
        isset($body['endTitle']) ? $body['endTitle'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// GET /match/:code
if ($method === 'GET' && preg_match('#^/match/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = getMatchStatus($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 404 : 200);
}

// POST /match/ping/:code
if ($method === 'POST' && preg_match('#^/match/ping/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    touchMatchPresence($m[1], $user['id']);
    jsonResponse(['ok' => true]);
}

// POST /multiplayer/leave-current
if ($method === 'POST' && $uri === '/multiplayer/leave-current') {
    $user = requireAuth();
    $matchesLeft = leaveOpenMatchesForUser($user['id']);
    $lobbiesLeft = leaveOpenLobbiesForUser($user['id']);
    jsonResponse([
        'ok' => true,
        'matches_left' => $matchesLeft,
        'lobbies_left' => $lobbiesLeft,
    ]);
}

// GET|POST /multiplayer/cleanup (for cron jobs)
if (($method === 'GET' || $method === 'POST') && $uri === '/multiplayer/cleanup') {
    requireCleanupAuth();
    $matchStats = cleanupStaleMatches();
    $lobbyStats = cleanupStaleLobbies();
    jsonResponse([
        'ok' => true,
        'matches' => $matchStats,
        'lobbies' => $lobbyStats,
        'ran_at' => gmdate('c'),
    ]);
}

// POST /lobby/create
if ($method === 'POST' && $uri === '/lobby/create') {
    $user = requireAuth();
    ensureNotBusyInAnotherRoom($user['id'], 'lobby');
    requireRateLimit('lobby_create', 10, 60);
    $body = jsonInput();
    $max = isset($body['maxPlayers']) ? $body['maxPlayers'] : 8;
    $result = createLobby(
        isset($body['startTitle']) ? $body['startTitle'] : '',
        isset($body['endTitle']) ? $body['endTitle'] : '',
        $user['id'],
        $max,
        isset($body['settings']) ? $body['settings'] : null
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /lobby/settings/:code
if ($method === 'POST' && preg_match('#^/lobby/settings/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $body = jsonInput();
    $result = updateLobbySettings($m[1], $user['id'], isset($body['settings']) ? $body['settings'] : null);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /lobby/join/:code
if ($method === 'POST' && preg_match('#^/lobby/join/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    ensureNotBusyInAnotherRoom($user['id'], 'lobby', $m[1]);
    $result = joinLobby($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /lobby/start/:code
if ($method === 'POST' && preg_match('#^/lobby/start/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = startLobby($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /lobby/submit/:code
if ($method === 'POST' && preg_match('#^/lobby/submit/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $body = jsonInput();
    $result = submitLobbyResult(
        $m[1],
        $user['id'],
        (int)(isset($body['clicks']) ? $body['clicks'] : 0),
        (int)(isset($body['time']) ? $body['time'] : 0),
        isset($body['path']) ? $body['path'] : []
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// GET /lobby/:code
if ($method === 'GET' && preg_match('#^/lobby/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = getLobbyStatus($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 404 : 200);
}

// POST /lobby/ping/:code
if ($method === 'POST' && preg_match('#^/lobby/ping/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    touchLobbyPresence($m[1], $user['id']);
    jsonResponse(['ok' => true]);
}

// POST /lobby/replay/:code
if ($method === 'POST' && preg_match('#^/lobby/replay/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = requestLobbyReplay($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /lobby/reseed/:code
if ($method === 'POST' && preg_match('#^/lobby/reseed/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $body = jsonInput();
    $result = reseedLobbyPair(
        $m[1],
        $user['id'],
        isset($body['startTitle']) ? $body['startTitle'] : '',
        isset($body['endTitle']) ? $body['endTitle'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /lobby/quit/:code
if ($method === 'POST' && preg_match('#^/lobby/quit/([A-Za-z0-9]+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = quitLobby($m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// GET /user/search?q=...
if ($method === 'GET' && $uri === '/user/search') {
    $user = requireAuth();
    $q = isset($_GET['q']) ? $_GET['q'] : '';
    jsonResponse(['users' => searchUsers($q, $user['id'])]);
}

// GET /user/:username
if ($method === 'GET' && preg_match('#^/user/([^/]+)$#', $uri, $m)) {
    $profile = getPublicProfile(urldecode($m[1]));
    if (!$profile) jsonResponse(['error' => 'User not found.'], 404);
    $authedUser = authenticateRequest();
    if ($authedUser) {
        $profile['friendship'] = getFriendshipStatus($authedUser['id'], $profile['id']);
    }
    jsonResponse($profile);
}

// GET /friends
if ($method === 'GET' && $uri === '/friends') {
    $user = requireAuth();
    jsonResponse(['friends' => getFriendsList($user['id'])]);
}

// GET /friends/requests
if ($method === 'GET' && $uri === '/friends/requests') {
    $user = requireAuth();
    jsonResponse([
        'incoming' => getPendingRequests($user['id']),
        'sent' => getSentRequests($user['id']),
    ]);
}

// POST /friends/request
if ($method === 'POST' && $uri === '/friends/request') {
    $user = requireAuth();
    requireRateLimit('friend_request', 20, 300);
    $body = jsonInput();
    $result = sendFriendRequest($user['id'], isset($body['username']) ? $body['username'] : '');
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /friends/accept
if ($method === 'POST' && $uri === '/friends/accept') {
    $user = requireAuth();
    $body = jsonInput();
    $result = acceptFriendRequest($user['id'], (int)(isset($body['requestId']) ? $body['requestId'] : 0));
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /friends/decline
if ($method === 'POST' && $uri === '/friends/decline') {
    $user = requireAuth();
    $body = jsonInput();
    $result = declineFriendRequest($user['id'], (int)(isset($body['requestId']) ? $body['requestId'] : 0));
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /friends/remove
if ($method === 'POST' && $uri === '/friends/remove') {
    $user = requireAuth();
    $body = jsonInput();
    $result = removeFriend($user['id'], (int)(isset($body['friendshipId']) ? $body['friendshipId'] : 0));
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /friends/game-invite
if ($method === 'POST' && $uri === '/friends/game-invite') {
    $user = requireAuth();
    requireRateLimit('friend_game_invite', 20, 300);
    $body = jsonInput();
    $result = createGameInvite(
        $user['id'],
        isset($body['username']) ? $body['username'] : '',
        isset($body['startTitle']) ? $body['startTitle'] : '',
        isset($body['endTitle']) ? $body['endTitle'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// POST /friends/room-invite
if ($method === 'POST' && $uri === '/friends/room-invite') {
    $user = requireAuth();
    requireRateLimit('friend_game_invite', 20, 300);
    $body = jsonInput();
    $result = createRoomInvite(
        $user['id'],
        isset($body['username']) ? $body['username'] : '',
        isset($body['roomType']) ? $body['roomType'] : 'match',
        isset($body['roomCode']) ? $body['roomCode'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// GET /friends/game-invites
if ($method === 'GET' && $uri === '/friends/game-invites') {
    $user = requireAuth();
    jsonResponse(['incoming' => getIncomingGameInvites($user['id'])]);
}

// GET /friends/game-invite/:id
if ($method === 'GET' && preg_match('#^/friends/game-invite/(\d+)$#', $uri, $m)) {
    $user = requireAuth();
    $result = getGameInviteByIdForUser((int)$m[1], $user['id']);
    jsonResponse($result, isset($result['error']) ? 404 : 200);
}

// POST /friends/game-invite/respond
if ($method === 'POST' && $uri === '/friends/game-invite/respond') {
    $user = requireAuth();
    $body = jsonInput();
    $result = respondToGameInvite(
        (int)(isset($body['inviteId']) ? $body['inviteId'] : 0),
        $user['id'],
        isset($body['action']) ? $body['action'] : ''
    );
    jsonResponse($result, isset($result['error']) ? 400 : 200);
}

// 404 catch-all
jsonResponse(['error' => 'Not found.'], 404);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal error occurred.']);
    error_log('WikiLink API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    exit;
}
