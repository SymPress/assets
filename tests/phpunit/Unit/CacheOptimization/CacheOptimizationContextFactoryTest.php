<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\CacheOptimization;

use SymPress\Assets\CacheOptimization\CacheOptimizationContextFactory;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\Script;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class CacheOptimizationContextFactoryTest extends AbstractTestCase
{
    public function testCreatesContextForOptedInAssetsOnly(): void
    {
        $script = (new Script('critical-sdk', 'https://example.com/wp-content/plugins/app/sdk.js?ver=1'))
            ->excludeFromCacheOptimization(CacheOptimizationExclusion::minifyAndCombine());
        $script->withFilePath('/var/www/html/wp-content/plugins/app/sdk.js');

        $style = (new Style('critical-css', 'https://example.com/wp-content/plugins/app/critical.css'))
            ->excludeFromCacheOptimization();
        $ignored = new Script('ordinary', 'https://example.com/ordinary.js');

        $context = (new CacheOptimizationContextFactory())->create([
            Script::class => [
                $script->handle()  => $script,
                $ignored->handle() => $ignored,
            ],
            Style::class  => [
                $style->handle() => $style,
            ],
        ]);

        static::assertSame(
            ['critical-sdk'],
            $context->scriptHandles(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->minify()),
        );
        static::assertSame(
            [],
            $context->scriptHandles(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->defer()),
        );
        static::assertContains(
            '/wp-content/plugins/app/sdk.js',
            $context->scriptIdentifiers(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->combine()),
        );
        static::assertContains(
            'critical-css',
            $context->styleHandles(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->async()),
        );
        static::assertNotContains(
            'ordinary',
            $context->scriptIdentifiers(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->minify()),
        );
    }

    public function testCreatesDecodedFileIdentifiers(): void
    {
        $script = (new Script('critical-sdk', 'https://example.com/wp-content/plugins/app/critical%20script.js?ver=1'))
            ->excludeFromCacheOptimization();
        $script->withFilePath('/var/www/html/wp-content/plugins/app/critical script.js');

        $context = (new CacheOptimizationContextFactory())->create([
            Script::class => [
                $script->handle() => $script,
            ],
        ]);

        $identifiers = $context->scriptIdentifiers(
            static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
        );

        static::assertContains('/wp-content/plugins/app/critical script.js', $identifiers);
        static::assertContains('https://example.com/wp-content/plugins/app/critical script.js', $identifiers);
    }
}
