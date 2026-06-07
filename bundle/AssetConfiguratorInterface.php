<?php

declare(strict_types=1);

namespace SymPress\Assets;

interface AssetConfiguratorInterface
{
    public function supports(Asset $asset): bool;

    public function configure(Asset $asset): Asset;
}
