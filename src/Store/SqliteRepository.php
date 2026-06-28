<?php

declare(strict_types=1);

namespace App\Store;

use App\Domain\Instance;
use App\Domain\Layout;
use App\Domain\WidgetConfig;

final class SqliteRepository implements InstanceRepository
{
    private \PDO $db;

    public function __construct(string $file)
    {
        $this->db = new \PDO('sqlite:' . $file);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->db->exec('PRAGMA journal_mode = WAL');
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS instances (
                id      INTEGER PRIMARY KEY AUTOINCREMENT,
                type    TEXT    NOT NULL,
                title   TEXT    NOT NULL DEFAULT "",
                enabled INTEGER NOT NULL DEFAULT 1,
                x INTEGER NOT NULL DEFAULT 1,
                y INTEGER NOT NULL DEFAULT 1,
                w INTEGER NOT NULL DEFAULT 3,
                h INTEGER NOT NULL DEFAULT 2,
                config  TEXT    NOT NULL DEFAULT "{}"
            )'
        );
    }

    /** @return Instance[] */
    public function all(): array
    {
        $rows = $this->db->query('SELECT * FROM instances ORDER BY id')->fetchAll();
        return array_map([self::class, 'toInstance'], $rows);
    }

    public function find(string $id): ?Instance
    {
        $stmt = $this->db->prepare('SELECT * FROM instances WHERE id = ?');
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        return $row ? self::toInstance($row) : null;
    }

    public function add(Instance $instance): Instance
    {
        $this->db->beginTransaction();
        // Place the new widget in the first free grid row below everything else.
        $bottom = (int)$this->db->query('SELECT COALESCE(MAX(y + h), 0) FROM instances')->fetchColumn();
        $y = $bottom > 0 ? $bottom : 1;

        $stmt = $this->db->prepare(
            'INSERT INTO instances (type, title, enabled, x, y, w, h, config)
             VALUES (:type, :title, :enabled, :x, :y, :w, :h, :config)'
        );
        $stmt->execute([
            ':type' => $instance->type,
            ':title' => $instance->title,
            ':enabled' => $instance->enabled ? 1 : 0,
            ':x' => 1,
            ':y' => $y,
            ':w' => $instance->layout->w,
            ':h' => $instance->layout->h,
            ':config' => self::encodeConfig($instance->config),
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->db->commit();

        return $this->find((string)$id);
    }

    public function update(Instance $instance): Instance
    {
        $stmt = $this->db->prepare('UPDATE instances SET title = ?, enabled = ?, config = ? WHERE id = ?');
        $stmt->execute([
            $instance->title,
            $instance->enabled ? 1 : 0,
            self::encodeConfig($instance->config),
            (int)$instance->id,
        ]);
        return $this->find((string)$instance->id);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM instances WHERE id = ?');
        $stmt->execute([(int)$id]);
        return $stmt->rowCount() > 0;
    }

    /** @param  array<string,Layout>  $byId */
    public function saveLayout(array $byId): void
    {
        $stmt = $this->db->prepare('UPDATE instances SET x = ?, y = ?, w = ?, h = ? WHERE id = ?');
        $this->db->beginTransaction();
        foreach ($byId as $id => $layout) {
            $stmt->execute([$layout->x, $layout->y, $layout->w, $layout->h, (int)$id]);
        }
        $this->db->commit();
    }

    /** @param  array<string,mixed>  $r */
    private static function toInstance(array $r): Instance
    {
        $config = json_decode((string)($r['config'] ?? '{}'), true) ?: [];

        return new Instance(
            id: (string)$r['id'],
            type: (string)$r['type'],
            title: (string)$r['title'],
            enabled: (bool)$r['enabled'],
            layout: new Layout((int)$r['x'], (int)$r['y'], (int)$r['w'], (int)$r['h']),
            config: WidgetConfig::fromArray($config),
        );
    }

    private static function encodeConfig(WidgetConfig $config): string
    {
        $values = $config->toArray();
        return json_encode(
            $values === [] ? new \stdClass() : $values,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
