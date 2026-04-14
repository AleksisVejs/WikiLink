<?php
require_once __DIR__ . '/match.php';
require_once __DIR__ . '/lobby.php';

$matchStats = cleanupStaleMatches();
$lobbyStats = cleanupStaleLobbies();

echo json_encode([
    'ok' => true,
    'matches' => $matchStats,
    'lobbies' => $lobbyStats,
    'ran_at' => gmdate('c'),
], JSON_UNESCAPED_SLASHES) . PHP_EOL;
