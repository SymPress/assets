<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests;

use PHPUnit\Framework\TestCase;
use SymPress\Assets\AssetManager;
use SymPress\Assets\AssetsBundle;
use SymPress\Assets\Tests\Fixtures\CacheOptimizerAdapterFixture;
use SymPress\Assets\Tests\Fixtures\ConfiguratorFixture;
use SymPress\Assets\Tests\Fixtures\ProviderFixture;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class AssetsBundleTest extends TestCase
{
    public function testServicesExposeAssetManagerAndAutoconfigureProviderPatterns(): void
    {
        $container = new ContainerBuilder();

        (new AssetsBundle())->build($container);
        (new YamlFileLoader(
            $container,
            new FileLocator(dirname(__DIR__, 2) . '/config'),
            'test',
        ))->load('services.yaml');

        $container->register(ProviderFixture::class, ProviderFixture::class)
            ->setAutoconfigured(true)
            ->setPublic(true);
        $container->register(ConfiguratorFixture::class, ConfiguratorFixture::class)
            ->setAutoconfigured(true)
            ->setPublic(true);
        $container->register(CacheOptimizerAdapterFixture::class, CacheOptimizerAdapterFixture::class)
            ->setAutoconfigured(true)
            ->setPublic(true);

        $container->compile();

        self::assertTrue($container->has(AssetManager::class));
        self::assertTrue($container->has('assets.manager'));
        self::assertTrue($container->getDefinition(ProviderFixture::class)->hasTag('assets.provider'));
        self::assertTrue($container->getDefinition(ConfiguratorFixture::class)->hasTag('assets.configurator'));
        self::assertTrue($container->getDefinition(CacheOptimizerAdapterFixture::class)->hasTag('assets.cache_optimizer_adapter'));
    }
}
