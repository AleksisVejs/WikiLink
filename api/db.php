<?php
define('DB_PATH', __DIR__ . '/wikilink.db');

function getDb() {
    static $db = null;
    if ($db) return $db;

    $isNew = !file_exists(DB_PATH);
    $db = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    if ($isNew) initSchema($db);
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
    ];

    foreach ($statements as $sql) {
        $db->exec($sql);
    }
}
