<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\CacheOptimization;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use SymPress\Assets\AssetManager;
use SymPress\Assets\CacheOptimization\CacheOptimizationContextProvider;
use SymPress\Assets\CacheOptimization\CacheOptimizationHandler;
use SymPress\Assets\CacheOptimization\CacheOptimizerAdapter;
use SymPress\Assets\Script;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use SymPress\Assets\Util\AssetHookResolver;
use SymPress\Kernel\WpContext;

class CacheOptimizationHandlerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('wp_scripts')->justReturn(\Mockery::mock('WP_Scripts'));
        Functions\when('wp_styles')->justReturn(\Mockery::mock('WP_Styles'));
    }

    public function testDoesNothingWhenNoAssetOptedIn(): void
    {
        $adapter = $this->adapter();
        $manager = $this->assetManager($adapter);

        Actions\expectDone(AssetManager::ACTION_SETUP)->once()->with($manager);
        $manager->register(new Script('ordinary', 'https://example.com/ordinary.js'));

        static::assertFalse($manager->registerCacheOptimizationExclusions());
        static::assertFalse($adapter->registered);
    }

    public function testRegistersAdaptersForOptedInAssetsWithoutDependingOnPluginLoadOrder(): void
    {
        $first = $this->adapter();
        $second = $this->adapter();
        $manager = $this->assetManager($first, $second);

        Actions\expectDone(AssetManager::ACTION_SETUP)->once()->with($manager);
        $manager->register(
            (new Script('critical', 'https://example.com/critical.js'))->excludeFromCacheOptimization(),
        );

        static::assertTrue($manager->registerCacheOptimizationExclusions());
        static::assertTrue($first->registered);
        static::assertTrue($second->registered);
        static::assertTrue($first->provider?->context()->hasAssets());
    }

    public function testSetupAutomaticallyRegistersCacheOptimizationExclusions(): void
    {
        $adapter = $this->adapter();
        $manager = $this->assetManager($adapter);

        Actions\expectDone(AssetManager::ACTION_SETUP)
            ->once()
            ->with($manager)
            ->whenHappen(static function (AssetManager $assetManager): void {
                $assetManager->register(
                    (new Script('critical', 'https://example.com/critical.js'))->excludeFromCacheOptimization(),
                );
            });

        $manager->assets();

        static::assertTrue($adapter->registered);
    }

    public function testIgnoreCacheMarksRegisteredAssetsAndSupportsFiltering(): void
    {
        $adapter = $this->adapter();
        $manager = $this->assetManager($adapter);
        $critical = new Script('critical', 'https://example.com/critical.js');
        $ordinary = new Script('ordinary', 'https://example.com/ordinary.js');

        Actions\expectDone(AssetManager::ACTION_SETUP)->once()->with($manager);
        $manager->register($critical, $ordinary);

        $manager->ignoreCache(static fn (Script $asset): bool => $asset->handle() === 'critical');

        static::assertNotNull($critical->cacheOptimizationExclusion());
        static::assertTrue($critical->cacheOptimizationExclusion()?->minify());
        static::assertTrue($critical->cacheOptimizationExclusion()?->combine());
        static::assertFalse($critical->cacheOptimizationExclusion()?->defer());
        static::assertFalse($critical->cacheOptimizationExclusion()?->delay());
        static::assertNull($ordinary->cacheOptimizationExclusion());
    }

    private function assetManager(CacheOptimizerAdapter ...$adapters): AssetManager
    {
        $wpContext = WpContext::new()->force(WpContext::FRONTOFFICE);
        $resolver = new AssetHookResolver($wpContext);
        $handler = new CacheOptimizationHandler(null, ...$adapters);

        return new AssetManager($resolver, null, $handler);
    }

    private function adapter(): CacheOptimizerAdapter
    {
        return new class implements CacheOptimizerAdapter {
            public bool $registered = false;

            public ?CacheOptimizationContextProvider $provider = null;

            public function register(CacheOptimizationContextProvider $contextProvider): void
            {
                $this->registered = true;
                $this->provider = $contextProvider;
            }
        };
    }
}
