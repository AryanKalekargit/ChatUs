<?php
// config/session.php
// Database-backed session handler for Vercel serverless compatibility.
// PHP's default file-based sessions don't persist across serverless invocations.

require_once __DIR__ . '/database.php';

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->db->exec("CREATE TABLE IF NOT EXISTS db_sessions (
            id VARCHAR(255) PRIMARY KEY,
            data TEXT NOT NULL,
            last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string|false {
        $stmt = $this->db->prepare("SELECT data FROM db_sessions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO db_sessions (id, data, last_accessed) VALUES (?, ?, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_accessed = CURRENT_TIMESTAMP"
        );
        return $stmt->execute([$id, $data]);
    }

    public function destroy($id): bool {
        $stmt = $this->db->prepare("DELETE FROM db_sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc($max_lifetime): int|false {
        $stmt = $this->db->prepare(
            "DELETE FROM db_sessions WHERE last_accessed < (CURRENT_TIMESTAMP - (? || ' seconds')::interval)"
        );
        $stmt->execute([$max_lifetime]);
        return $stmt->rowCount();
    }
}

session_set_save_handler(new DatabaseSessionHandler(), true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
