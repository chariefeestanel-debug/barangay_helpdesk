<?php
// ============================================================
// BarangayHelpDesk - Database Connection (PDO Singleton)
// ============================================================

class Database {
    private static $instance = null;

    public static function connect() {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
            }
        }
        return self::$instance;
    }

    // Prevent cloning / unserialization
    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton."); }
}

function db() {
    return Database::connect();
}
