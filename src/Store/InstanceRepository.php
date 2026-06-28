<?php

declare(strict_types=1);

namespace App\Store;

use App\Domain\Instance;
use App\Domain\Layout;

interface InstanceRepository
{
    /** @return Instance[] */
    public function all(): array;

    public function find(string $id): ?Instance;

    /** Persist a new instance (assigns an id and a first-free-row placement). */
    public function add(Instance $instance): Instance;

    /** Persist title/enabled/config of an existing instance; returns the saved row. */
    public function update(Instance $instance): Instance;

    public function delete(string $id): bool;

    /**
     * Bulk-persist grid placement after a drag/resize session.
     * @param  array<string,Layout>  $byId  instance id => new Layout
     */
    public function saveLayout(array $byId): void;
}
