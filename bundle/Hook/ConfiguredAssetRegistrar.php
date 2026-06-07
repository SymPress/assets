<?php

declare(strict_types=1);

namespace SymPress\Assets\Hook;

use SymPress\Assets\Asset;
use SymPress\Assets\AssetConfiguratorInterface;
use SymPress\Assets\AssetManager;
use SymPress\Assets\AssetProviderInterface;

final class ConfiguredAssetRegistrar
{
    /**
     * @param iterable<AssetProviderInterface>     $providers
     * @param iterable<AssetConfiguratorInterface> $configurators
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly iterable $configurators,
    ) {
    }

    public function register(AssetManager $assetManager): void
    {
        foreach ($this->providers as $provider) {
            try {
                foreach ($provider->assets() as $asset) {
                    $assetManager->register($this->configure($asset));
                }
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    sprintf('Asset provider "%s" failed while registering assets.', get_debug_type($provider)),
                    previous: $exception,
                );
            }
        }
    }

    private function configure(Asset $asset): Asset
    {
        foreach ($this->configurators as $configurator) {
            if (!$configurator->supports($asset)) {
                continue;
            }

            try {
                $asset = $configurator->configure($asset);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    sprintf(
                        'Asset configurator "%s" failed while configuring asset "%s".',
                        get_debug_type($configurator),
                        $asset->handle(),
                    ),
                    previous: $exception,
                );
            }
        }

        return $asset;
    }
}
