<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization\Adapter;

use SymPress\Assets\CacheOptimization\CacheOptimizationContextProvider;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\CacheOptimization\CacheOptimizerAdapter;

final class LiteSpeedCacheAdapter implements CacheOptimizerAdapter
{
    public function register(CacheOptimizationContextProvider $contextProvider): void
    {
        add_filter(
            'litespeed_optimize_js_excludes',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $contextProvider->context()->scriptIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
                ),
            ),
        );

        add_filter(
            'litespeed_optimize_css_excludes',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $contextProvider->context()->styleIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
                ),
            ),
        );

        add_filter(
            'litespeed_optm_js_defer_exc',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $contextProvider->context()->scriptIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->defer() || $exclusion->delay(),
                ),
            ),
        );

        add_filter(
            'litespeed_optm_gm_js_exc',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $contextProvider->context()->scriptIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->defer() || $exclusion->delay(),
                ),
            ),
        );
    }

    /**
     * @param list<string> $identifiers
     *
     * @return array<mixed>
     */
    private static function append(mixed $excluded, array $identifiers): array
    {
        $normalized = [];

        if (is_array($excluded)) {
            foreach ($excluded as $value) {
                if (is_scalar($value) || $value instanceof \Stringable) {
                    $normalized[] = (string) $value;
                }
            }
        } elseif (is_scalar($excluded) || $excluded instanceof \Stringable) {
            $normalized[] = (string) $excluded;
        }

        return array_values(array_unique([...$normalized, ...$identifiers]));
    }
}
