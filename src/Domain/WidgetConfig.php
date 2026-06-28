<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class WidgetConfig implements \JsonSerializable
{
    /** @param  array<string,mixed>  $values */
    public function __construct(private array $values)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function merge(array $patch): self
    {
        return new self(array_merge($this->values, $patch));
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
