<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\CacheOptimization;

use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\OutputFilter\AttributesOutputFilter;
use SymPress\Assets\Script;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class CacheOptimizationAwareTraitTest extends AbstractTestCase
{
    public function testScriptCanBeExcludedFromCacheOptimization(): void
    {
        $script = new Script('critical-sdk', 'https://example.com/sdk.js');

        $script->excludeFromCacheOptimization();

        static::assertInstanceOf(CacheOptimizationExclusion::class, $script->cacheOptimizationExclusion());
        static::assertSame(
            [
                'data-no-minify' => true,
                'data-no-optimize' => true,
                'data-noptimize' => true,
                'data-no-defer' => true,
                'data-cfasync' => 'false',
                'data-wpfc-render' => 'false',
            ],
            $script->attributes(),
        );
        static::assertSame([AttributesOutputFilter::class], $script->filters());
    }

    public function testStyleCanBeExcludedFromFileOptimizationOnly(): void
    {
        $style = new Style('critical-css', 'https://example.com/critical.css');

        $style->excludeFromCacheOptimization(CacheOptimizationExclusion::minifyAndCombine());

        static::assertSame(
            [
                'data-no-minify' => true,
                'data-no-optimize' => true,
                'data-noptimize' => true,
            ],
            $style->attributes(),
        );
    }

    public function testAllowCacheOptimizationRemovesPolicyAndAttributes(): void
    {
        $script = new Script('critical-sdk', 'https://example.com/sdk.js');

        $script->excludeFromCacheOptimization();
        $script->allowCacheOptimization();

        static::assertNull($script->cacheOptimizationExclusion());
        static::assertSame([], $script->attributes());
    }
}
