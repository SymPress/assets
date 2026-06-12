<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Security\AssetSymlinkPublisher;
use Symfony\Component\Filesystem\Path;

// Exit early in case multiple Composer autoloaders try to include this file.
if (function_exists(__NAMESPACE__ . '\\assetSuffix')) {
    return;
}

/**
 * Returns ".min" if SCRIPT_DEBUG is false.
 */
function assetSuffix(): string
{
    return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG
        ? ''
        : '.min';
}

/**
 * Adding the assetSuffix() before file extension to the given file.
 *
 * @example before: my-script.js | after: my-script.min.js
 */
function withAssetSuffix(string $file): string
{
    $suffix = assetSuffix();
    $extension = Path::getExtension($file);
    if ($extension === '') {
        return $file;
    }

    return Path::changeExtension($file, "{$suffix}.{$extension}");
}

/**
 * Symlinks a folder inside the web-root for Assets, which are outside of the web-root
 * and returns a link to that folder.
 *
 * @param list<string> $allowedBaseDirs additional origin directories allowed for publishing
 */
function symlinkedAssetFolder(string $originDir, string $name, array $allowedBaseDirs = []): ?string
{
    return AssetSymlinkPublisher::forWordPress($allowedBaseDirs)->publish($originDir, $name);
}
