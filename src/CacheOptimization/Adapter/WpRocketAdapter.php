<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization\Adapter;

use SymPress\Assets\CacheOptimization\CacheOptimizationContextProvider;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\CacheOptimization\CacheOptimizerAdapter;

final class WpRocketAdapter implements CacheOptimizerAdapter
{
    public function register(CacheOptimizationContextProvider $contextProvider): void
    {
        add_filter(
            'rocket_exclude_js',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $contextProvider->context()->scriptIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
                ),
            ),
        );

        add_filter(
            'rocket_exclude_css',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $contextProvider->context()->styleIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
                ),
            ),
        );

        add_filter(
            'rocket_exclude_defer_js',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $contextProvider->context()->scriptIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->defer(),
                ),
            ),
        );

        add_filter(
            'rocket_delay_js_exclusions',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $contextProvider->context()->scriptIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->delay(),
                ),
            ),
        );
    }

    /**
     * @param list<string> $identifiers
     * @return array<mixed>
     */
    private static function append(mixed $excluded, array $identifiers): array
    {
        $normalized = [];

        if (is_array($excluded)) {
            foreach ($excluded as $value) {
                if (!is_scalar($value) && !($value instanceof \Stringable)) {
                    continue;
                }

                $normalized[] = (string) $value;
            }
        } elseif (is_scalar($excluded) || $excluded instanceof \Stringable) {
            $normalized[] = (string) $excluded;
        }

        return array_values(array_unique([...$normalized, ...$identifiers]));
    }
}
