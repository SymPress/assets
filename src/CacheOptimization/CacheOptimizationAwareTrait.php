<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

use SymPress\Assets\FilterAwareAsset;
use SymPress\Assets\Script;
use SymPress\Assets\ScriptModule;
use SymPress\Assets\Style;

trait CacheOptimizationAwareTrait
{
    protected ?CacheOptimizationExclusion $cacheOptimizationExclusion = null;

    public function cacheOptimizationExclusion(): ?CacheOptimizationExclusion
    {
        return $this->cacheOptimizationExclusion;
    }

    public function excludeFromCacheOptimization(?CacheOptimizationExclusion $exclusion = null): static
    {
        $this->cacheOptimizationExclusion = $exclusion ?? CacheOptimizationExclusion::all();
        $this->applyCacheOptimizationAttributes($this->cacheOptimizationExclusion);

        return $this;
    }

    public function allowCacheOptimization(): static
    {
        $this->cacheOptimizationExclusion = null;

        if ($this instanceof FilterAwareAsset) {
            $this->withoutAttributes(
                'data-no-minify',
                'data-no-optimize',
                'data-noptimize',
                'data-no-defer',
                'data-no-async',
                'data-cfasync',
                'data-wpfc-render',
            );
        }

        return $this;
    }

    private function applyCacheOptimizationAttributes(CacheOptimizationExclusion $exclusion): void
    {
        if (!$this instanceof FilterAwareAsset) {
            return;
        }

        $attributes = [];

        if ($exclusion->excludesFileOptimization()) {
            $attributes['data-no-minify'] = true;
            $attributes['data-no-optimize'] = true;
            $attributes['data-noptimize'] = true;
        }

        if ($this instanceof Script || $this instanceof ScriptModule) {
            if ($exclusion->defer() || $exclusion->delay()) {
                $attributes['data-no-defer'] = true;
                $attributes['data-cfasync'] = 'false';
                $attributes['data-wpfc-render'] = 'false';
            }

            if ($exclusion->async()) {
                $attributes['data-cfasync'] ??= 'false';
            }
        }

        if ($this instanceof Style && $exclusion->async()) {
            $attributes['data-no-async'] = true;
        }

        if ([] === $attributes) {
            return;
        }

        $this->withAttributes($attributes);
    }
}
