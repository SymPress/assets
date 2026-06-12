<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

final readonly class CacheOptimizationContext
{
    /**
     * @param list<CacheOptimizationAsset> $scripts
     * @param list<CacheOptimizationAsset> $styles
     */
    public function __construct(
        private array $scripts,
        private array $styles,
    ) {
    }

    public static function empty(): self
    {
        return new self([], []);
    }

    public function hasAssets(): bool
    {
        return $this->scripts !== [] || $this->styles !== [];
    }

    /** @return list<CacheOptimizationAsset> */
    public function scriptAssets(callable $selector): array
    {
        return $this->assets($this->scripts, $selector);
    }

    /** @return list<CacheOptimizationAsset> */
    public function styleAssets(callable $selector): array
    {
        return $this->assets($this->styles, $selector);
    }

    /** @return list<string> */
    public function scriptHandles(callable $selector): array
    {
        return $this->handles($this->scripts, $selector);
    }

    /** @return list<string> */
    public function styleHandles(callable $selector): array
    {
        return $this->handles($this->styles, $selector);
    }

    /** @return list<string> */
    public function scriptIdentifiers(callable $selector): array
    {
        return $this->identifiers($this->scripts, $selector);
    }

    /** @return list<string> */
    public function styleIdentifiers(callable $selector): array
    {
        return $this->identifiers($this->styles, $selector);
    }

    /**
     * @param list<CacheOptimizationAsset> $assets
     * @return list<string>
     */
    private function handles(array $assets, callable $selector): array
    {
        $handles = [];

        foreach ($assets as $asset) {
            if (!$selector($asset->exclusion) || $asset->handle === '') {
                continue;
            }

            $handles[] = $asset->handle;
        }

        return array_values(array_unique($handles));
    }

    /**
     * @param list<CacheOptimizationAsset> $assets
     * @return list<CacheOptimizationAsset>
     */
    private function assets(array $assets, callable $selector): array
    {
        return array_values(array_filter(
            $assets,
            static fn (CacheOptimizationAsset $asset): bool => (bool) $selector($asset->exclusion),
        ));
    }

    /**
     * @param list<CacheOptimizationAsset> $assets
     * @return list<string>
     */
    private function identifiers(array $assets, callable $selector): array
    {
        $identifiers = [];

        foreach ($assets as $asset) {
            if (!$selector($asset->exclusion)) {
                continue;
            }

            array_push($identifiers, ...$asset->fileIdentifiers());
        }

        return array_values(array_unique(array_filter($identifiers, static fn (string $identifier): bool => $identifier !== '')));
    }
}
