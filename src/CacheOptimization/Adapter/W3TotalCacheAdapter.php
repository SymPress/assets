<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization\Adapter;

use SymPress\Assets\CacheOptimization\CacheOptimizationAsset;
use SymPress\Assets\CacheOptimization\CacheOptimizationContextProvider;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\CacheOptimization\CacheOptimizerAdapter;

final class W3TotalCacheAdapter implements CacheOptimizerAdapter
{
    public function register(CacheOptimizationContextProvider $contextProvider): void
    {
        add_filter(
            'w3tc_minify_js_do_tag_minification',
            static function (mixed $doMinification, mixed $tag, mixed $file = null) use ($contextProvider): bool {
                if (!self::shouldMinify($doMinification)) {
                    return false;
                }

                return !self::matchesAnyAsset(
                    [self::nullableString($file), ...self::tagReferences(self::stringValue($tag), 'src')],
                    $contextProvider->context()->scriptAssets(
                        static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
                    ),
                );
            },
            10,
            3,
        );

        add_filter(
            'w3tc_minify_css_do_tag_minification',
            static function (mixed $doMinification, mixed $tag, mixed $file = null) use ($contextProvider): bool {
                if (!self::shouldMinify($doMinification)) {
                    return false;
                }

                return !self::matchesAnyAsset(
                    [self::nullableString($file), ...self::tagReferences(self::stringValue($tag), 'href')],
                    $contextProvider->context()->styleAssets(
                        static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
                    ),
                );
            },
            10,
            3,
        );
    }

    private static function shouldMinify(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return false;
        }

        return filter_var((string) $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_scalar($value) || $value instanceof \Stringable
            ? (string) $value
            : null;
    }

    private static function stringValue(mixed $value): string
    {
        return self::nullableString($value) ?? '';
    }

    /**
     * @param array<int, string|null>      $references
     * @param list<CacheOptimizationAsset> $assets
     */
    private static function matchesAnyAsset(array $references, array $assets): bool
    {
        foreach ($references as $reference) {
            if ($reference === null || $reference === '') {
                continue;
            }

            foreach ($assets as $asset) {
                if ($asset->matchesFileReference($reference)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> */
    private static function tagReferences(string $tag, string $attribute): array
    {
        if (preg_match('/\s' . preg_quote($attribute, '/') . '\s*=\s*(["\'])(.*?)\1/i', $tag, $matches) !== 1) {
            return [];
        }

        return [$matches[2]];
    }
}
