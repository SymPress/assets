<?php

declare(strict_types=1);

namespace SymPress\Assets\Util;

use Symfony\Component\Filesystem\Path;

final readonly class FilesystemPath
{
    public static function canonical(string $path): ?string
    {
        $realPath = realpath($path);

        return is_string($realPath)
            ? self::normalize($realPath)
            : null;
    }

    public static function normalize(string $path): string
    {
        return rtrim(Path::canonicalize(str_replace('\\', '/', $path)), '/');
    }

    public static function isBasePath(string $basePath, string $path): bool
    {
        return '' !== $basePath
            && '' !== $path
            && Path::isBasePath($basePath, $path);
    }

    public static function join(string ...$paths): string
    {
        return self::normalize(Path::join(...$paths));
    }
}
