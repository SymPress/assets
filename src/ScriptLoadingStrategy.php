<?php

declare(strict_types=1);

namespace SymPress\Assets;

final readonly class ScriptLoadingStrategy
{
    public const string BLOCKING = 'blocking';
    public const string DEFER = 'defer';
    public const string ASYNC = 'async';

    /**
     * @return self::ASYNC|self::DEFER|null
     */
    public static function normalize(?string $strategy): ?string
    {
        if (null === $strategy || '' === $strategy || self::BLOCKING === $strategy) {
            return null;
        }

        if (self::DEFER === $strategy || self::ASYNC === $strategy) {
            return $strategy;
        }

        throw new Exception\InvalidArgumentException(sprintf(
            'Script loading strategy must be one of "%s", "%s" or "%s".',
            self::BLOCKING,
            self::DEFER,
            self::ASYNC,
        ));
    }
}
