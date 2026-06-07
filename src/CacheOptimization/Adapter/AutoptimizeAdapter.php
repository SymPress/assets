<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization\Adapter;

use SymPress\Assets\CacheOptimization\CacheOptimizationContextProvider;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\CacheOptimization\CacheOptimizerAdapter;

final class AutoptimizeAdapter implements CacheOptimizerAdapter
{
    public function register(CacheOptimizationContextProvider $contextProvider): void
    {
        add_filter(
            'autoptimize_filter_js_exclude',
            static fn (mixed $excluded): mixed => self::append(
                $excluded,
                $contextProvider->context()->scriptIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
                ),
            ),
        );

        add_filter(
            'autoptimize_filter_css_exclude',
            static fn (mixed $excluded): mixed => self::append(
                $excluded,
                $contextProvider->context()->styleIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->excludesFileOptimization(),
                ),
            ),
        );

        add_filter(
            'autoptimize_filter_js_consider_minified',
            static fn (mixed $excluded): array => self::appendToArray(
                $excluded,
                $contextProvider->context()->scriptIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->minify(),
                ),
            ),
        );

        add_filter(
            'autoptimize_filter_css_consider_minified',
            static fn (mixed $excluded): array => self::appendToArray(
                $excluded,
                $contextProvider->context()->styleIdentifiers(
                    static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->minify(),
                ),
            ),
        );
    }

    /**
     * @param list<string> $identifiers
     */
    private static function append(mixed $excluded, array $identifiers): mixed
    {
        if (is_array($excluded)) {
            return self::appendToArray($excluded, $identifiers);
        }

        $values = array_filter(
            array_map('trim', explode(',', is_scalar($excluded) ? (string) $excluded : '')),
            static fn (string $value): bool => '' !== $value,
        );

        return implode(', ', array_values(array_unique([...$values, ...$identifiers])));
    }

    /**
     * @param list<string> $identifiers
     *
     * @return list<string>
     */
    private static function appendToArray(mixed $excluded, array $identifiers): array
    {
        $values = [];

        if (is_array($excluded)) {
            foreach ($excluded as $value) {
                if (is_scalar($value) || $value instanceof \Stringable) {
                    $values[] = (string) $value;
                }
            }
        }

        return array_values(array_unique([...$values, ...$identifiers]));
    }
}
