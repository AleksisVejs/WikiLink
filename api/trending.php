<?php
require_once __DIR__ . '/db.php';

function ensureTrendingTable() {
    $db = getDb();
    $db->exec("CREATE TABLE IF NOT EXISTS trending_cache (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        articles_json TEXT NOT NULL,
        fetched_at TEXT NOT NULL
    )");
}

function getCachedTrending() {
    ensureTrendingTable();
    $db = getDb();
    $stmt = $db->query('SELECT articles_json, fetched_at FROM trending_cache WHERE id = 1');
    $row = $stmt->fetch();
    if (!$row) return null;

    $age = time() - strtotime($row['fetched_at']);
    if ($age > 3600) return null;

    return json_decode($row['articles_json'], true);
}

function saveTrendingCache($articles) {
    ensureTrendingTable();
    $db = getDb();
    $json = json_encode($articles);
    $now = gmdate('Y-m-d H:i:s');
    $stmt = $db->prepare('INSERT INTO trending_cache (id, articles_json, fetched_at) VALUES (1, ?, ?)
               ON CONFLICT(id) DO UPDATE SET articles_json = ?, fetched_at = ?');
    $stmt->execute([$json, $now, $json, $now]);
}

function fetchTrendingFromWikipedia() {
    $ctx = stream_context_create([
        'http' => [
            'header' => "User-Agent: WikiLink/2.0 (https://wikilink.fraksis.com)\r\n",
            'timeout' => 10,
        ]
    ]);

    $data = null;
    // Wikimedia pageview dumps can lag; system clocks can also drift.
    // Probe up to the past week and use the first day that returns valid data.
    for ($daysBack = 1; $daysBack <= 7; $daysBack++) {
        $date = gmdate('Y/m/d', strtotime("-{$daysBack} day"));
        $url = "https://wikimedia.org/api/rest_v1/metrics/pageviews/top/en.wikipedia/all-access/{$date}";
        $response = @file_get_contents($url, false, $ctx);
        if (!$response) continue;

        $decoded = json_decode($response, true);
        if (!$decoded || empty($decoded['items'][0]['articles'])) continue;
        $data = $decoded;
        break;
    }

    if (!$data || empty($data['items'][0]['articles'])) return null;

    $excluded = [
        'Main_Page', 'Special:', 'Wikipedia:', 'Portal:', 'Help:',
        'File:', 'Template:', 'Category:', 'Talk:', 'User:',
        'MediaWiki:', 'Draft:', 'Module:',
    ];

    $articles = [];
    foreach ($data['items'][0]['articles'] as $entry) {
        $title = $entry['article'] ?? '';
        if (!$title) continue;

        $skip = false;
        foreach ($excluded as $prefix) {
            if (strpos($title, $prefix) === 0 || $title === $prefix) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;
        if (strpos($title, ':') !== false) continue;

        $articles[] = [
            'title' => str_replace('_', ' ', $title),
            'views' => (int)($entry['views'] ?? 0),
            'rank' => (int)($entry['rank'] ?? 0),
        ];

        if (count($articles) >= 50) break;
    }

    return $articles;
}

function getTrendingArticles() {
    $cached = getCachedTrending();
    if ($cached) return $cached;

    $articles = fetchTrendingFromWikipedia();
    if ($articles && count($articles) > 0) {
        saveTrendingCache($articles);
        return $articles;
    }

    return null;
}
