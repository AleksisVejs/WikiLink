<?php
require_once __DIR__ . '/db.php';

function getGlobalStats() {
    $db = getDb();
    $row = $db->query('SELECT total_games, total_wins, total_clicks FROM global_stats WHERE id = 1')->fetch();
    $players = $db->query('SELECT COUNT(*) as cnt FROM users')->fetch();
    return [
        'totalGames'   => (int)($row['total_games']),
        'totalWins'    => (int)($row['total_wins']),
        'totalClicks'  => (int)($row['total_clicks']),
        'totalPlayers' => (int)($players['cnt']),
    ];
}

function incrementGlobalGame($clicks, $won) {
    $clicks = max(0, min((int)$clicks, 999));
    $wonInc = $won ? 1 : 0;

    $db = getDb();
    $stmt = $db->prepare('UPDATE global_stats SET total_games = total_games + 1, total_wins = total_wins + ?, total_clicks = total_clicks + ? WHERE id = 1');
    $stmt->execute([$wonInc, $clicks]);
}
