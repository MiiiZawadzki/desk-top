<?php

declare(strict_types=1);

namespace App\Store;

use App\Domain\User;

final class SqliteUserRepository implements UserRepository
{
    private \PDO $db;

    public function __construct(string $file)
    {
        $this->db = new \PDO('sqlite:' . $file);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->db->exec('PRAGMA journal_mode = WAL');
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                email         TEXT    NOT NULL UNIQUE,
                password_hash TEXT    NOT NULL,
                created_at    TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row === false ? null : self::toUser($row);
    }

    public function add(User $user): User
    {
        $stmt = $this->db->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$user->email, $user->passwordHash]);

        return new User((string)$this->db->lastInsertId(), $user->email, $user->passwordHash);
    }

    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    private static function toUser(array $r): User
    {
        return new User((string)$r['id'], (string)$r['email'], (string)$r['password_hash']);
    }
}
