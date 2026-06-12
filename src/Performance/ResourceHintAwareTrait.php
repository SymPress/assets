<?php

declare(strict_types=1);

namespace SymPress\Assets\Performance;

use SymPress\Assets\Asset;

/** @phpstan-require-implements Asset */
trait ResourceHintAwareTrait
{
    /** @var list<ResourceHint> */
    protected array $resourceHints = [];

    /** @return list<ResourceHint> */
    public function resourceHints(): array
    {
        return $this->resourceHints;
    }

    public function withResourceHint(ResourceHint $hint): static
    {
        $this->resourceHints[] = $hint;

        return $this;
    }

    /** @param array<string, string|bool|int|float|null> $attributes */
    public function withPreloadResource(string $as, array $attributes = []): static
    {
        return $this->withResourceHint(ResourceHint::preload($this->url(), $as, $attributes));
    }

    /** @param array<string, string|bool|int|float|null> $attributes */
    public function withPreconnect(?string $href = null, array $attributes = []): static
    {
        return $this->withResourceHint(ResourceHint::preconnect($href ?? $this->url(), $attributes));
    }

    public function withDnsPrefetch(?string $href = null): static
    {
        return $this->withResourceHint(ResourceHint::dnsPrefetch($href ?? $this->url()));
    }
}
