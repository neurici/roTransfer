<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    foreach ([STORAGE_PATH, TRANSFERS_PATH, TMP_PATH, LOG_PATH] as $d) {
        if (!is_dir($d)) @mkdir($d, 0775, true);
    }

    $needInit = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    if ($needInit) init_db($pdo);
    migrate_db($pdo);
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transfers (
            id TEXT PRIMARY KEY,
            token TEXT UNIQUE NOT NULL,
            title TEXT,
            message TEXT,
            sender_email TEXT,
            recipient_email TEXT,
            recipient_email2 TEXT,
            recipient_email3 TEXT,
            password_hash TEXT,
            expires_at INTEGER NOT NULL,
            max_downloads INTEGER,
            downloads_count INTEGER NOT NULL DEFAULT 0,
            total_bytes INTEGER NOT NULL DEFAULT 0,
            created_at INTEGER NOT NULL
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS files (
            id TEXT PRIMARY KEY,
            transfer_id TEXT NOT NULL,
            original_name TEXT NOT NULL,
            stored_name TEXT NOT NULL,
            size INTEGER NOT NULL,
            mime TEXT,
            sha256 TEXT,
            created_at INTEGER NOT NULL,
            FOREIGN KEY(transfer_id) REFERENCES transfers(id)
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS download_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transfer_id TEXT NOT NULL,
            file_id TEXT,
            kind TEXT NOT NULL,
            ip TEXT,
            user_agent TEXT,
            created_at INTEGER NOT NULL
        );
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_files_transfer ON files(transfer_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_logs_transfer ON download_logs(transfer_id);");
}


function migrate_db(PDO $pdo): void {
    // Add missing columns for older installs
    $cols = [];
    $q = $pdo->query("PRAGMA table_info(transfers)");
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cols[$row['name']] = true;
    }
    if (empty($cols['recipient_email2'])) {
        $pdo->exec("ALTER TABLE transfers ADD COLUMN recipient_email2 TEXT");
    }
    if (empty($cols['recipient_email3'])) {
        $pdo->exec("ALTER TABLE transfers ADD COLUMN recipient_email3 TEXT");
    }

    // ===== Users (conturi) =====
    // users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            password_plain TEXT NOT NULL,
            created_at INTEGER NOT NULL,
            last_login_at INTEGER
        );
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);");

    // transfers.user_id column (optional ownership)
    if (empty($cols['user_id'])) {
        $pdo->exec("ALTER TABLE transfers ADD COLUMN user_id TEXT");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transfers_user ON transfers(user_id);");
    }
}
