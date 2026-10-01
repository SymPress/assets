<?php

declare(strict_types=1);

namespace SymPress\Assets\Security;

use SymPress\Assets\Exception\InvalidResourceException;
use SymPress\Assets\FilterAwareAsset;

final class IntegrityMetadata
{
    public static function apply(FilterAwareAsset $asset, mixed $integrity, mixed $crossorigin = null): void
    {
        if ($integrity === null) {
            return;
        }
        if (!is_string($integrity) || $integrity === '') {
            throw new InvalidResourceException('Integrity metadata must contain build-produced SHA hashes.');
        }
        foreach (explode(' ', $integrity) as $hash) {
            if (preg_match('/^sha(256|384|512)-([A-Za-z0-9+\/]+={0,2})$/D', $hash, $match) !== 1) {
                throw new InvalidResourceException('Invalid integrity hash metadata.');
            }
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Validate build-produced SRI digest bytes; never execute decoded content.
            $bytes = base64_decode($match[2], true);
            if ($bytes === false || strlen($bytes) !== ((int) $match[1]) / 8) {
                throw new InvalidResourceException('Integrity hash has an invalid digest length.');
            }
        }
        if ($crossorigin !== null && !in_array($crossorigin, ['anonymous', 'use-credentials'], true)) {
            throw new InvalidResourceException('Invalid integrity crossorigin metadata.');
        }
        $asset->withAttributes(['integrity' => $integrity, 'crossorigin' => $crossorigin ?? 'anonymous']);
    }
}
