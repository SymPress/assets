<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\CacheOptimization;

use Brain\Monkey\Functions;
use SymPress\Assets\CacheOptimization\Adapter\AutoptimizeAdapter;
use SymPress\Assets\CacheOptimization\Adapter\LiteSpeedCacheAdapter;
use SymPress\Assets\CacheOptimization\Adapter\SiteGroundOptimizerAdapter;
use SymPress\Assets\CacheOptimization\Adapter\W3TotalCacheAdapter;
use SymPress\Assets\CacheOptimization\Adapter\WpRocketAdapter;
use SymPress\Assets\CacheOptimization\CacheOptimizationAsset;
use SymPress\Assets\CacheOptimization\CacheOptimizationContext;
use SymPress\Assets\CacheOptimization\CacheOptimizationContextProvider;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class CacheOptimizerAdaptersTest extends AbstractTestCase
{
    /** @var array<string, callable> */
    private array $callbacks = [];

    public function testWpRocketAdapterRegistersFileDeferAndDelayExclusions(): void
    {
        $this->expectAddFilter('rocket_exclude_js');
        $this->expectAddFilter('rocket_exclude_css');
        $this->expectAddFilter('rocket_exclude_defer_js');
        $this->expectAddFilter('rocket_delay_js_exclusions');

        (new WpRocketAdapter())->register($this->provider());

        static::assertNotContains('critical-script', ($this->callbacks['rocket_exclude_js'])(['existing.js']));
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['rocket_exclude_js'])(['existing.js']),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-style.css',
            ($this->callbacks['rocket_exclude_css'])([]),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['rocket_exclude_defer_js'])([]),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['rocket_delay_js_exclusions'])([]),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['rocket_exclude_js'])(false),
        );
    }

    public function testSiteGroundAdapterRegistersHandleExclusions(): void
    {
        foreach (
            [
                'sgo_js_minify_exclude',
                'sgo_javascript_combine_exclude',
                'sgo_js_async_exclude',
                'sgo_css_minify_exclude',
                'sgo_css_combine_exclude',
            ] as $hook
        ) {
            $this->expectAddFilter($hook);
        }

        (new SiteGroundOptimizerAdapter())->register($this->provider());

        static::assertSame(['existing', 'critical-script'], ($this->callbacks['sgo_js_minify_exclude'])(['existing']));
        static::assertSame(['existing', 'critical-script'], ($this->callbacks['sgo_js_minify_exclude'])('existing'));
        static::assertContains('critical-style', ($this->callbacks['sgo_css_combine_exclude'])([]));
    }

    public function testW3TotalCacheAdapterDisablesTagMinificationForMatchingAssets(): void
    {
        $this->expectAddFilter('w3tc_minify_js_do_tag_minification', 4);
        $this->expectAddFilter('w3tc_minify_css_do_tag_minification', 4);

        (new W3TotalCacheAdapter())->register($this->provider());

        static::assertFalse(
            ($this->callbacks['w3tc_minify_js_do_tag_minification'])(
                true,
                '<script src="/wp-content/plugins/app/critical-script.js"></script>',
                '/var/www/app/critical-script.js',
            ),
        );
        static::assertTrue(
            ($this->callbacks['w3tc_minify_js_do_tag_minification'])(
                true,
                '<script src="/wp-content/plugins/app/ordinary-critical-script-helper.js"></script>',
                '/var/www/app/ordinary-critical-script-helper.js',
            ),
        );
        static::assertFalse(
            ($this->callbacks['w3tc_minify_css_do_tag_minification'])(
                true,
                '<link rel="stylesheet" href="/wp-content/plugins/app/critical-style.css" />',
                null,
            ),
        );
        static::assertFalse(
            ($this->callbacks['w3tc_minify_js_do_tag_minification'])(
                '0',
                null,
                [],
            ),
        );
        static::assertFalse(
            ($this->callbacks['w3tc_minify_js_do_tag_minification'])(
                1,
                '<script src="/wp-content/plugins/app/critical-script.js"></script>',
                null,
            ),
        );
    }

    public function testAutoptimizeAdapterAppendsCommaSeparatedExclusions(): void
    {
        $this->expectAddFilter('autoptimize_filter_js_exclude');
        $this->expectAddFilter('autoptimize_filter_css_exclude');
        $this->expectAddFilter('autoptimize_filter_js_consider_minified');
        $this->expectAddFilter('autoptimize_filter_css_consider_minified');

        (new AutoptimizeAdapter())->register($this->provider());

        static::assertStringContainsString(
            'critical-script.js',
            ($this->callbacks['autoptimize_filter_js_exclude'])('jquery.js'),
        );
        static::assertStringContainsString(
            'critical-style.css',
            ($this->callbacks['autoptimize_filter_css_exclude'])('admin-bar.css'),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['autoptimize_filter_js_consider_minified'])(false),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-style.css',
            ($this->callbacks['autoptimize_filter_css_consider_minified'])(['existing.css']),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['autoptimize_filter_js_exclude'])(['jquery.js']),
        );
    }

    public function testLiteSpeedAdapterRegistersFileAndDeferExclusions(): void
    {
        foreach (
            [
                'litespeed_optimize_js_excludes',
                'litespeed_optimize_css_excludes',
                'litespeed_optm_js_defer_exc',
                'litespeed_optm_gm_js_exc',
            ] as $hook
        ) {
            $this->expectAddFilter($hook);
        }

        (new LiteSpeedCacheAdapter())->register($this->provider());

        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['litespeed_optimize_js_excludes'])([]),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['litespeed_optimize_js_excludes'])(null),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-style.css',
            ($this->callbacks['litespeed_optimize_css_excludes'])([]),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['litespeed_optm_js_defer_exc'])([]),
        );
        static::assertContains(
            '/wp-content/plugins/app/critical-script.js',
            ($this->callbacks['litespeed_optm_gm_js_exc'])([]),
        );
    }

    private function expectAddFilter(string $hook, int $argumentCount = 2): void
    {
        $arguments = [
            $hook,
            \Mockery::on(function (mixed $candidate) use ($hook): bool {
                if (!is_callable($candidate)) {
                    return false;
                }

                $this->callbacks[$hook] = $candidate;

                return true;
            }),
        ];

        if ($argumentCount === 4) {
            $arguments[] = 10;
            $arguments[] = 3;
        }

        Functions\expect('add_filter')->once()->with(...$arguments)->andReturn(true);
    }

    private function provider(): CacheOptimizationContextProvider
    {
        return new class ($this->context()) implements CacheOptimizationContextProvider {
            public function __construct(
                private readonly CacheOptimizationContext $context,
            ) {
            }

            public function context(): CacheOptimizationContext
            {
                return $this->context;
            }
        };
    }

    private function context(): CacheOptimizationContext
    {
        return new CacheOptimizationContext(
            [
                new CacheOptimizationAsset(
                    'critical-script',
                    'https://example.com/wp-content/plugins/app/critical-script.js?ver=1',
                    '/var/www/app/critical-script.js',
                    CacheOptimizationExclusion::all(),
                    [
                        'https://example.com/wp-content/plugins/app/critical-script.js',
                        '/wp-content/plugins/app/critical-script.js',
                        '/var/www/app/critical-script.js',
                    ],
                ),
            ],
            [
                new CacheOptimizationAsset(
                    'critical-style',
                    'https://example.com/wp-content/plugins/app/critical-style.css',
                    '/var/www/app/critical-style.css',
                    CacheOptimizationExclusion::all(),
                    [
                        'https://example.com/wp-content/plugins/app/critical-style.css',
                        '/wp-content/plugins/app/critical-style.css',
                        '/var/www/app/critical-style.css',
                    ],
                ),
            ],
        );
    }
}
