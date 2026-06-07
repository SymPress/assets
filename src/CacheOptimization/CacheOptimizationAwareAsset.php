<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

interface CacheOptimizationAwareAsset
{
    public function cacheOptimizationExclusion(): ?CacheOptimizationExclusion;

    public function excludeFromCacheOptimization(?CacheOptimizationExclusion $exclusion = null): static;

    public function allowCacheOptimization(): static;
}
