<?php

declare(strict_types=1);

namespace App\Widgets\TodoList;

use App\Exception\NotFoundException;

final class TodoStore
{
    private \PDO $db;

    public function __construct(string $file)
    {
        $this->db = new \PDO('sqlite:' . $file);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->db->exec('PRAGMA journal_mode = WAL');

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS todo_items (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                instance_id INTEGER NOT NULL,
                text        TEXT    NOT NULL,
                done        INTEGER NOT NULL DEFAULT 0,
                position    INTEGER NOT NULL DEFAULT 0,
                created_at  TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_todo_items_instance ON todo_items(instance_id)');
    }

    public function snapshot(int $instanceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, text, done, created_at AS createdAt, updated_at AS updatedAt
             FROM todo_items WHERE instance_id = ?
             ORDER BY done ASC, CASE WHEN done = 1 THEN updated_at END DESC, position ASC, id ASC'
        );
        $stmt->execute([$instanceId]);

        return ['items' => array_map(self::castItem(...), $stmt->fetchAll())];
    }

    public function add(int $instanceId, string $text): array
    {
        $pos = (int)$this->one(
            'SELECT COALESCE(MAX(position), 0) + 1 FROM todo_items WHERE instance_id = ?',
            [$instanceId]
        );

        $stmt = $this->db->prepare('INSERT INTO todo_items (instance_id, text, position) VALUES (?, ?, ?)');
        $stmt->execute([$instanceId, $text, $pos]);

        return $this->snapshot($instanceId);
    }

    public function toggle(int $instanceId, int $id): array
    {
        $item = $this->requireItem($instanceId, $id);
        $done = $item['done'] ? 0 : 1;

        $stmt = $this->db->prepare(
            'UPDATE todo_items SET done = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ? AND instance_id = ?'
        );
        $stmt->execute([$done, $id, $instanceId]);

        return $this->snapshot($instanceId);
    }

    public function edit(int $instanceId, int $id, string $text): array
    {
        $this->requireItem($instanceId, $id);

        $stmt = $this->db->prepare(
            'UPDATE todo_items SET text = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ? AND instance_id = ?'
        );
        $stmt->execute([$text, $id, $instanceId]);

        return $this->snapshot($instanceId);
    }

    public function remove(int $instanceId, int $id): array
    {
        $this->requireItem($instanceId, $id);

        $stmt = $this->db->prepare('DELETE FROM todo_items WHERE id = ? AND instance_id = ?');
        $stmt->execute([$id, $instanceId]);

        return $this->snapshot($instanceId);
    }

    public function clearCompleted(int $instanceId): array
    {
        $stmt = $this->db->prepare('DELETE FROM todo_items WHERE instance_id = ? AND done = 1');
        $stmt->execute([$instanceId]);

        return $this->snapshot($instanceId);
    }

    private function requireItem(int $instanceId, int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM todo_items WHERE id = ? AND instance_id = ?');
        $stmt->execute([$id, $instanceId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new NotFoundException('task not found');
        }
        return $row;
    }

    private function one(string $sql, array $params): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private static function castItem(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'text' => (string)$row['text'],
            'done' => (bool)$row['done'],
            'createdAt' => $row['createdAt'],
            'updatedAt' => $row['updatedAt'],
        ];
    }
}
