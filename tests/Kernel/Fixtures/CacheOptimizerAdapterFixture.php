<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Fixtures;

use SymPress\Assets\CacheOptimization\CacheOptimizationContextProvider;
use SymPress\Assets\CacheOptimization\CacheOptimizerAdapter;

final class CacheOptimizerAdapterFixture implements CacheOptimizerAdapter
{
    public function register(CacheOptimizationContextProvider $contextProvider): void
    {
    }
}
