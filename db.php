<?php
require_once __DIR__ . '/config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        init_db($pdo);
    }
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id      INTEGER PRIMARY KEY AUTOINCREMENT,
            name    TEXT    NOT NULL,
            email   TEXT    NOT NULL UNIQUE,
            balance REAL    NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS transactions (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id        INTEGER NOT NULL,
            tap_charge_id  TEXT,
            amount         REAL    NOT NULL,
            status         TEXT    NOT NULL DEFAULT 'pending',
            created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ");

    // Demo user — replace with real auth in production
    $pdo->exec("
        INSERT OR IGNORE INTO users (name, email, balance)
        VALUES ('أحمد العلي', 'ahmed@example.com', 0)
    ");
}

function get_user(int $id): array|false {
    return get_db()->prepare('SELECT * FROM users WHERE id = ?')
        ->execute([$id]) ? get_db()->query("SELECT * FROM users WHERE id = $id")->fetch() : false;
}

function get_user_simple(): array {
    $stmt = get_db()->query('SELECT * FROM users LIMIT 1');
    return $stmt->fetch();
}

function create_pending_transaction(int $user_id, float $amount): int {
    $db = get_db();
    $stmt = $db->prepare('INSERT INTO transactions (user_id, amount, status) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $amount, 'pending']);
    return (int) $db->lastInsertId();
}

function update_transaction(int $txn_id, string $tap_charge_id, string $status): void {
    get_db()->prepare('UPDATE transactions SET tap_charge_id = ?, status = ? WHERE id = ?')
        ->execute([$tap_charge_id, $status, $txn_id]);
}

function credit_wallet(int $user_id, float $amount): void {
    get_db()->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')
        ->execute([$amount, $user_id]);
}

function get_transactions(int $user_id): array {
    $stmt = get_db()->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function transaction_already_captured(string $tap_charge_id): bool {
    $stmt = get_db()->prepare("SELECT id FROM transactions WHERE tap_charge_id = ? AND status = 'captured'");
    $stmt->execute([$tap_charge_id]);
    return (bool) $stmt->fetch();
}
