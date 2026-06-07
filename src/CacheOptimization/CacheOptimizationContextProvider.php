<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

interface CacheOptimizationContextProvider
{
    public function context(): CacheOptimizationContext;
}
