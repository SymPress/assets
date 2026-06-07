<?php

declare(strict_types=1);

namespace SymPress\Assets\Hook;

use SymPress\Assets\AssetManager;
use SymPress\Assets\Bootstrap\AssetBootstrapper;

final class AssetBootstrap
{
    public function __construct(
        private readonly AssetManager $assetManager,
        private readonly AssetBootstrapper $assetBootstrapper,
    ) {
    }

    public function setup(): bool
    {
        return $this->assetBootstrapper->bootstrap($this->assetManager);
    }
}
