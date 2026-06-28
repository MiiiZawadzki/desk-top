<?php
declare(strict_types=1);

namespace App\Domain;

final readonly class Instance implements \JsonSerializable
{
    public function __construct(
        public ?string      $id,
        public string       $type,
        public string       $title,
        public bool         $enabled,
        public Layout       $layout,
        public WidgetConfig $config,
    ) {}

    public function withTitle(string $title): self
    {
        return new self($this->id, $this->type, $title, $this->enabled, $this->layout, $this->config);
    }

    public function withEnabled(bool $enabled): self
    {
        return new self($this->id, $this->type, $this->title, $enabled, $this->layout, $this->config);
    }

    public function withConfig(WidgetConfig $config): self
    {
        return new self($this->id, $this->type, $this->title, $this->enabled, $this->layout, $config);
    }

    public function withLayout(Layout $layout): self
    {
        return new self($this->id, $this->type, $this->title, $this->enabled, $layout, $this->config);
    }

    public function jsonSerialize(): array
    {
        return [
            'id'      => $this->id,
            'type'    => $this->type,
            'title'   => $this->title,
            'enabled' => $this->enabled,
            'layout'  => $this->layout,
            'config'  => $this->config,
        ];
    }
}
