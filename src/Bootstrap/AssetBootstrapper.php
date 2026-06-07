<?php

declare(strict_types=1);

namespace SymPress\Assets\Bootstrap;

use SymPress\Assets\AssetManager;

final class AssetBootstrapper
{
    private bool $booted = false;

    #[\NoDiscard]
    public function bootstrap(?AssetManager $assetManager = null): bool
    {
        if ($this->booted) {
            return false;
        }

        $bootstrapped = ($assetManager ?? new AssetManager())->setup();
        if ($bootstrapped) {
            $this->booted = true;
        }

        return $bootstrapped;
    }

    public function reset(): void
    {
        $this->booted = false;
    }
}
