<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

interface CacheOptimizerAdapter
{
    public function register(CacheOptimizationContextProvider $contextProvider): void;
}
