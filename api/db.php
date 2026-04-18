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
            profile_icon  TEXT    NOT NULL DEFAULT 'rookie',
            profile_accent TEXT   NOT NULL DEFAULT 'rank',
            profile_title TEXT    NOT NULL DEFAULT 'newcomer',
            profile_banner TEXT   NOT NULL DEFAULT 'default',
            profile_nameplate_border TEXT NOT NULL DEFAULT 'default',
            profile_pinned_badge TEXT NOT NULL DEFAULT '',
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

        "CREATE TABLE IF NOT EXISTS daily_sessions (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            date         TEXT    NOT NULL,
            snapshot_json TEXT   NOT NULL,
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(user_id, date)
        )",
        "CREATE INDEX IF NOT EXISTS idx_daily_sessions_date ON daily_sessions(date)",

        "CREATE TABLE IF NOT EXISTS shared_challenges (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            code        TEXT    NOT NULL UNIQUE,
            start_title TEXT    NOT NULL,
            end_title   TEXT    NOT NULL,
            created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        )",
        "CREATE INDEX IF NOT EXISTS idx_shared_challenges_created_by ON shared_challenges(created_by, created_at)",
        "CREATE TABLE IF NOT EXISTS community_pair_votes (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            pair_id      INTEGER NOT NULL REFERENCES shared_challenges(id) ON DELETE CASCADE,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            vote         INTEGER NOT NULL CHECK (vote IN (-1, 1)),
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(pair_id, user_id)
        )",
        "CREATE INDEX IF NOT EXISTS idx_community_pair_votes_pair ON community_pair_votes(pair_id)",

        "CREATE TABLE IF NOT EXISTS community_pair_groups (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            name       TEXT    NOT NULL,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        )",
        "CREATE INDEX IF NOT EXISTS idx_community_pair_groups_user ON community_pair_groups(user_id, created_at)",
        "CREATE TABLE IF NOT EXISTS community_group_votes (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id     INTEGER NOT NULL REFERENCES community_pair_groups(id) ON DELETE CASCADE,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            vote         INTEGER NOT NULL CHECK (vote IN (-1, 1)),
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(group_id, user_id)
        )",
        "CREATE INDEX IF NOT EXISTS idx_community_group_votes_group ON community_group_votes(group_id)",

        "CREATE TABLE IF NOT EXISTS community_pair_group_items (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id     INTEGER NOT NULL REFERENCES community_pair_groups(id) ON DELETE CASCADE,
            position_idx INTEGER NOT NULL,
            challenge_code TEXT,
            start_title TEXT    NOT NULL,
            end_title   TEXT    NOT NULL
        )",
        "CREATE INDEX IF NOT EXISTS idx_community_pair_group_items_group ON community_pair_group_items(group_id, position_idx)",

        "CREATE TABLE IF NOT EXISTS community_paths (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            start_title  TEXT    NOT NULL,
            end_title    TEXT    NOT NULL,
            path_json    TEXT    NOT NULL,
            path_hash    TEXT    NOT NULL,
            path_length  INTEGER NOT NULL,
            likes_count  INTEGER NOT NULL DEFAULT 0,
            dislikes_count INTEGER NOT NULL DEFAULT 0,
            score        INTEGER NOT NULL DEFAULT 0,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(user_id, start_title, end_title, path_hash)
        )",
        "CREATE INDEX IF NOT EXISTS idx_community_paths_pair ON community_paths(start_title, end_title, path_length, score)",

        "CREATE TABLE IF NOT EXISTS community_path_votes (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            community_path_id INTEGER NOT NULL REFERENCES community_paths(id) ON DELETE CASCADE,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            vote         INTEGER NOT NULL CHECK (vote IN (-1, 1)),
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(community_path_id, user_id)
        )",
        "CREATE INDEX IF NOT EXISTS idx_community_votes_path ON community_path_votes(community_path_id)",

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

        "CREATE TABLE IF NOT EXISTS head_to_head_stats (
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            opponent_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            wins INTEGER NOT NULL DEFAULT 0,
            losses INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            PRIMARY KEY (user_id, opponent_id)
        )",
        "CREATE INDEX IF NOT EXISTS idx_h2h_user ON head_to_head_stats(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_h2h_opponent ON head_to_head_stats(opponent_id)",

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

    // Lightweight migration for room-based invites.
    $inviteColumns = [];
    foreach ($db->query("PRAGMA table_info(game_invites)") as $col) {
        $inviteColumns[$col['name']] = true;
    }
    if (!isset($inviteColumns['invite_type'])) {
        $db->exec("ALTER TABLE game_invites ADD COLUMN invite_type TEXT NOT NULL DEFAULT 'match'");
    }
    if (!isset($inviteColumns['room_code'])) {
        $db->exec("ALTER TABLE game_invites ADD COLUMN room_code TEXT");
    }

    // User cosmetics migration.
    $userColumns = [];
    foreach ($db->query("PRAGMA table_info(users)") as $col) {
        $userColumns[$col['name']] = true;
    }
    if (!isset($userColumns['profile_icon'])) {
        $db->exec("ALTER TABLE users ADD COLUMN profile_icon TEXT NOT NULL DEFAULT 'rookie'");
    }
    if (!isset($userColumns['profile_accent'])) {
        $db->exec("ALTER TABLE users ADD COLUMN profile_accent TEXT NOT NULL DEFAULT 'rank'");
    }
    if (!isset($userColumns['profile_title'])) {
        $db->exec("ALTER TABLE users ADD COLUMN profile_title TEXT NOT NULL DEFAULT 'newcomer'");
    }
    if (!isset($userColumns['profile_banner'])) {
        $db->exec("ALTER TABLE users ADD COLUMN profile_banner TEXT NOT NULL DEFAULT 'default'");
    }
    if (!isset($userColumns['profile_nameplate_border'])) {
        $db->exec("ALTER TABLE users ADD COLUMN profile_nameplate_border TEXT NOT NULL DEFAULT 'default'");
    }
    if (!isset($userColumns['profile_pinned_badge'])) {
        $db->exec("ALTER TABLE users ADD COLUMN profile_pinned_badge TEXT NOT NULL DEFAULT ''");
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
