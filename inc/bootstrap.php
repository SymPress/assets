<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Bootstrap\AssetBootstrapper;
use SymPress\Assets\Bootstrap\WordPressHookRegistrar;

// Exit early in case multiple Composer autoloaders try to include this file.
if (defined(__NAMESPACE__ . '\BOOTSTRAPPED')) {
    return;
}

const BOOTSTRAPPED = true;

function bootstrap(?AssetManager $assetManager = null): bool
{
    /** @var AssetBootstrapper|null $assetBootstrapper */
    static $assetBootstrapper = null;

    $assetBootstrapper ??= new AssetBootstrapper();

    return $assetBootstrapper->bootstrap($assetManager);
}

function bootstrapWordPress(): bool
{
    return bootstrap();
}

(new WordPressHookRegistrar())->register(__NAMESPACE__ . '\\bootstrapWordPress');
