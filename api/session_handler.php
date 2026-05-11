<?php
/**
 * Database-backed session handler.
 * Needed on Railway where the filesystem is ephemeral between deploys/restarts.
 * Must be registered before session_start().
 */
class DbSessionHandler implements SessionHandlerInterface {
    private PDO $db;

    public function __construct(PDO $db) { $this->db = $db; }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false {
        $stmt = $this->db->prepare('SELECT data FROM sessions WHERE id = :id AND expires_at > :now');
        $stmt->execute([':id' => $id, ':now' => time()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool {
        $lifetime   = (int)ini_get('session.gc_maxlifetime') ?: 7200;
        $expiresAt  = time() + $lifetime;
        $stmt = $this->db->prepare(
            'INSERT INTO sessions (id, data, expires_at) VALUES (:id, :data, :exp)
             ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)'
        );
        return $stmt->execute([':id' => $id, ':data' => $data, ':exp' => $expiresAt]);
    }

    public function destroy(string $id): bool {
        $this->db->prepare('DELETE FROM sessions WHERE id = :id')->execute([':id' => $id]);
        return true;
    }

    public function gc(int $maxLifetime): int|false {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE expires_at <= :now');
        $stmt->execute([':now' => time()]);
        return $stmt->rowCount();
    }
}
