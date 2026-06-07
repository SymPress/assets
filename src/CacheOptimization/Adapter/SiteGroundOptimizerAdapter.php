<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization\Adapter;

use SymPress\Assets\CacheOptimization\CacheOptimizationContextProvider;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\CacheOptimization\CacheOptimizerAdapter;

final class SiteGroundOptimizerAdapter implements CacheOptimizerAdapter
{
    public function register(CacheOptimizationContextProvider $contextProvider): void
    {
        $scriptHandles = static fn (callable $selector): array => $contextProvider->context()->scriptHandles($selector);
        $styleHandles = static fn (callable $selector): array => $contextProvider->context()->styleHandles($selector);

        add_filter(
            'sgo_js_minify_exclude',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $scriptHandles(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->minify()),
            ),
        );
        add_filter(
            'sgo_javascript_combine_exclude',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $scriptHandles(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->combine()),
            ),
        );
        add_filter(
            'sgo_js_async_exclude',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $scriptHandles(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->async()),
            ),
        );
        add_filter(
            'sgo_css_minify_exclude',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $styleHandles(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->minify()),
            ),
        );
        add_filter(
            'sgo_css_combine_exclude',
            static fn (mixed $excluded): array => self::append(
                $excluded,
                $styleHandles(static fn (CacheOptimizationExclusion $exclusion): bool => $exclusion->combine()),
            ),
        );
    }

    /**
     * @param list<string> $handles
     *
     * @return array<mixed>
     */
    private static function append(mixed $excluded, array $handles): array
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

        return array_values(array_unique([...$normalized, ...$handles]));
    }
}
