<?php

declare(strict_types=1);

namespace SymPress\Assets\Performance;

interface ResourceHintAwareAsset
{
    /**
     * @return list<ResourceHint>
     */
    public function resourceHints(): array;

    public function withResourceHint(ResourceHint $hint): static;
}
