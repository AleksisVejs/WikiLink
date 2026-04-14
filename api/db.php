<?php
define('DB_PATH', __DIR__ . '/wikilink.db');

function getDb() {
    static $db = null;
    if ($db) return $db;

    $db = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    initSchema($db);
    return $db;
}

function initSchema($db) {
    $statements = [
        "CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            password_hash TEXT    NOT NULL,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        )",

        "CREATE TABLE IF NOT EXISTS sessions (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            token      TEXT    NOT NULL UNIQUE,
            expires_at TEXT    NOT NULL
        )",
        "CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token)",

        "CREATE TABLE IF NOT EXISTS daily_pairs (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            date        TEXT    NOT NULL UNIQUE,
            start_title TEXT    NOT NULL,
            start_data  TEXT    NOT NULL,
            end_title   TEXT    NOT NULL,
            end_data    TEXT    NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS daily_scores (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            date         TEXT    NOT NULL,
            clicks       INTEGER NOT NULL,
            time_seconds INTEGER NOT NULL,
            path         TEXT    NOT NULL DEFAULT '[]',
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(user_id, date)
        )",
        "CREATE INDEX IF NOT EXISTS idx_daily_scores_date ON daily_scores(date)",

        "CREATE TABLE IF NOT EXISTS shared_challenges (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            code        TEXT    NOT NULL UNIQUE,
            start_title TEXT    NOT NULL,
            end_title   TEXT    NOT NULL,
            created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        )",

        "CREATE TABLE IF NOT EXISTS global_stats (
            id           INTEGER PRIMARY KEY CHECK (id = 1),
            total_games  INTEGER NOT NULL DEFAULT 0,
            total_wins   INTEGER NOT NULL DEFAULT 0,
            total_clicks INTEGER NOT NULL DEFAULT 0
        )",
        "INSERT OR IGNORE INTO global_stats (id) VALUES (1)",

        "CREATE TABLE IF NOT EXISTS user_stats (
            user_id    INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
            stats_json TEXT NOT NULL DEFAULT '{\"modes\":{},\"genres\":{}}'
        )",

        "CREATE TABLE IF NOT EXISTS rate_limits (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            ip         TEXT    NOT NULL,
            action     TEXT    NOT NULL,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        )",
        "CREATE INDEX IF NOT EXISTS idx_rate_limits_lookup ON rate_limits(ip, action, created_at)",

        "CREATE TABLE IF NOT EXISTS friendships (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            friend_id  INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            status     TEXT    NOT NULL DEFAULT 'pending',
            created_at TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(user_id, friend_id)
        )",
        "CREATE INDEX IF NOT EXISTS idx_friendships_user ON friendships(user_id, status)",
        "CREATE INDEX IF NOT EXISTS idx_friendships_friend ON friendships(friend_id, status)",

        "CREATE TABLE IF NOT EXISTS game_invites (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_user_id   INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            receiver_user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            match_code       TEXT    NOT NULL,
            status           TEXT    NOT NULL DEFAULT 'pending',
            created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
            responded_at     TEXT
        )",
        "CREATE INDEX IF NOT EXISTS idx_game_invites_receiver ON game_invites(receiver_user_id, status, created_at)",
        "CREATE INDEX IF NOT EXISTS idx_game_invites_sender ON game_invites(sender_user_id, created_at)",
    ];

    foreach ($statements as $sql) {
        $db->exec($sql);
    }
}

function generateUniqueCode($length = 6) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function checkRateLimit($action, $maxAttempts = 10, $windowSeconds = 300) {
    $db = getDb();
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

    $db->prepare("DELETE FROM rate_limits WHERE created_at < datetime('now', ? || ' seconds')")
       ->execute(['-' . $windowSeconds]);

    $stmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip = ? AND action = ? AND created_at > datetime('now', ? || ' seconds')");
    $stmt->execute([$ip, $action, '-' . $windowSeconds]);
    $count = (int)$stmt->fetchColumn();

    if ($count >= $maxAttempts) return false;

    $db->prepare("INSERT INTO rate_limits (ip, action) VALUES (?, ?)")
       ->execute([$ip, $action]);

    return true;
}
