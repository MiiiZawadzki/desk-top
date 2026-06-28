<?php
declare(strict_types=1);

namespace App\Domain;

final class Layout implements \JsonSerializable
{
    public const int COLS = 12;

    public function __construct(
        public readonly int $x,
        public readonly int $y,
        public readonly int $w,
        public readonly int $h,
    ) {}

    public static function clamped(int $x, int $y, int $w, int $h): self
    {
        $w = self::bound($w, 1, self::COLS);
        $x = self::bound($x, 1, self::COLS - $w + 1);
        return new self($x, max(1, $y), $w, max(1, $h));
    }

    /** @param array<string,mixed> $a */
    public static function fromArray(array $a): self
    {
        return self::clamped(
            (int) ($a['x'] ?? 1),
            (int) ($a['y'] ?? 1),
            (int) ($a['w'] ?? 3),
            (int) ($a['h'] ?? 2),
        );
    }

    /** @return array{x:int,y:int,w:int,h:int} */
    public function jsonSerialize(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'w' => $this->w, 'h' => $this->h];
    }

    private static function bound(int $v, int $min, int $max): int
    {
        return max($min, min($max, $v));
    }
}
