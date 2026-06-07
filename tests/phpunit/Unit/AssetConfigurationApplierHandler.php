<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit;

use SymPress\Assets\Asset;
use SymPress\Assets\Handler\AssetHandler;

final class AssetConfigurationApplierHandler implements AssetHandler
{
    public function register(Asset $asset): bool
    {
        return true;
    }

    public function enqueue(Asset $asset): bool
    {
        return true;
    }
}
