<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

final readonly class CacheOptimizationExclusion
{
    public function __construct(
        private bool $minify = true,
        private bool $combine = true,
        private bool $defer = true,
        private bool $delay = true,
        private bool $async = true,
    ) {
    }

    public static function all(): self
    {
        return new self();
    }

    public static function minifyAndCombine(): self
    {
        return new self(defer: false, delay: false, async: false);
    }

    public function minify(): bool
    {
        return $this->minify;
    }

    public function combine(): bool
    {
        return $this->combine;
    }

    public function defer(): bool
    {
        return $this->defer;
    }

    public function delay(): bool
    {
        return $this->delay;
    }

    public function async(): bool
    {
        return $this->async;
    }

    public function excludesFileOptimization(): bool
    {
        return $this->minify || $this->combine;
    }
}
