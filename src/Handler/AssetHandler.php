<?php

declare(strict_types=1);

namespace SymPress\Assets\Handler;

use SymPress\Assets\Asset;

interface AssetHandler
{
    public function register(Asset $asset): bool;

    public function enqueue(Asset $asset): bool;
}
