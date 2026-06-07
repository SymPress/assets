<?php

declare(strict_types=1);

namespace SymPress\Assets;

final readonly class StyleLoadingMode
{
    public const string BLOCKING = 'blocking';
    public const string PRELOAD = 'preload';

    /**
     * @return self::BLOCKING|self::PRELOAD
     */
    public static function normalize(string $mode): string
    {
        if (self::BLOCKING === $mode || self::PRELOAD === $mode) {
            return $mode;
        }

        throw new Exception\InvalidArgumentException(sprintf(
            'Style loading mode must be one of "%s" or "%s".',
            self::BLOCKING,
            self::PRELOAD,
        ));
    }
}
