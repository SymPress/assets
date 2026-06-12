<?php

declare(strict_types=1);

namespace SymPress\Assets\Loader;

use SymPress\Assets\Asset;
use SymPress\Assets\AssetFactory;
use SymPress\Assets\BaseAsset;
use SymPress\Assets\ConfigureAutodiscoverVersionTrait;

/** @phpstan-import-type AssetConfig from AssetFactory */
class ArrayLoader implements LoaderInterface
{
    use ConfigureAutodiscoverVersionTrait;

    /**
     * @return array<Asset>
     * @psalm-suppress MixedArgument
     */
    #[\NoDiscard]
    #[\Override]
    public function load(mixed $resource): array
    {
        $assets = [];
        foreach ((array) $resource as $config) {
            if (!$this->isAssetConfig($config)) {
                continue;
            }

            $assets[] = AssetFactory::create($config);
        }

        return array_map(
            function (Asset $asset): Asset {
                if ($asset instanceof BaseAsset) {
                    $this->autodiscoverVersion
                        ? $asset->enableAutodiscoverVersion()
                        : $asset->disableAutodiscoverVersion();
                }

                return $asset;
            },
            $assets,
        );
    }

    /** @phpstan-assert-if-true AssetConfig $config */
    private function isAssetConfig(mixed $config): bool
    {
        if (!is_array($config)) {
            return false;
        }

        $type = $config['type'] ?? null;

        return is_string($config['handle'] ?? null)
            && is_string($config['url'] ?? null)
            && is_string($type)
            && class_exists($type)
            && is_a($type, Asset::class, true);
    }
}
