<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Fixtures;

use SymPress\Assets\Asset;
use SymPress\Assets\AssetConfiguratorInterface;

final class ConfiguratorFixture implements AssetConfiguratorInterface
{
    public function supports(Asset $asset): bool
    {
        return false;
    }

    public function configure(Asset $asset): Asset
    {
        return $asset;
    }
}
