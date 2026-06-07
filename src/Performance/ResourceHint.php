<?php

declare(strict_types=1);

namespace SymPress\Assets\Performance;

use SymPress\Assets\Exception\InvalidArgumentException;

final readonly class ResourceHint
{
    public const string PRELOAD = 'preload';
    public const string PRECONNECT = 'preconnect';
    public const string DNS_PREFETCH = 'dns-prefetch';

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public function __construct(
        private string $relation,
        private string $href,
        private array $attributes = [],
    ) {
        if (!in_array($relation, [self::PRELOAD, self::PRECONNECT, self::DNS_PREFETCH], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported resource hint relation "%s".', $relation));
        }

        if ('' === $href) {
            throw new InvalidArgumentException('Resource hint href must not be empty.');
        }
    }

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public static function preload(string $href, string $as, array $attributes = []): self
    {
        return new self(self::PRELOAD, $href, ['as' => $as, ...$attributes]);
    }

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public static function preconnect(string $href, array $attributes = []): self
    {
        return new self(self::PRECONNECT, $href, $attributes);
    }

    public static function dnsPrefetch(string $href): self
    {
        return new self(self::DNS_PREFETCH, $href);
    }

    public function relation(): string
    {
        return $this->relation;
    }

    public function href(): string
    {
        return $this->href;
    }

    /**
     * @return array<string, string|bool|int|float|null>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
