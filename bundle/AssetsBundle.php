<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\CacheOptimization\CacheOptimizerAdapter;
use SymPress\Kernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssetsBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(AssetConfiguratorInterface::class)
            ->addTag('assets.configurator');

        $container->registerForAutoconfiguration(AssetProviderInterface::class)
            ->addTag('assets.provider');

        $container->registerForAutoconfiguration(CacheOptimizerAdapter::class)
            ->addTag('assets.cache_optimizer_adapter');
    }
}
