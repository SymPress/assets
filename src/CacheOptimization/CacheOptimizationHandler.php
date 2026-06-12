<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

use SymPress\Assets\AssetManager;

final class CacheOptimizationHandler implements CacheOptimizationContextProvider
{
    /** @var list<CacheOptimizerAdapter> */
    private array $adapters;

    private CacheOptimizationContext $context;

    private bool $registered = false;

    private CacheOptimizationContextFactory $contextFactory;

    /** @param CacheOptimizerAdapter|iterable<CacheOptimizerAdapter>|null $adapters */
    public function __construct(
        ?CacheOptimizationContextFactory $contextFactory = null,
        CacheOptimizerAdapter|iterable|null $adapters = null,
        CacheOptimizerAdapter ...$additionalAdapters,
    ) {

        $this->contextFactory = $contextFactory ?? new CacheOptimizationContextFactory();
        $this->adapters = self::normalizeAdapters($adapters, $additionalAdapters);
        $this->context = CacheOptimizationContext::empty();
    }

    public function run(AssetManager $assetManager): bool
    {
        $this->context = $this->contextFactory->create($assetManager->assets());

        if (!$this->context->hasAssets()) {
            return false;
        }

        if ($this->registered) {
            return true;
        }

        $this->registered = true;

        foreach ($this->adapters as $adapter) {
            $adapter->register($this);
        }

        return true;
    }

    public function context(): CacheOptimizationContext
    {
        return $this->context;
    }

    /**
     * @param CacheOptimizerAdapter|iterable<CacheOptimizerAdapter>|null $adapters
     * @param list<CacheOptimizerAdapter>                                $additionalAdapters
     * @return list<CacheOptimizerAdapter>
     */
    private static function normalizeAdapters(
        CacheOptimizerAdapter|iterable|null $adapters,
        array $additionalAdapters,
    ): array {

        if ($adapters === null && $additionalAdapters === []) {
            return self::defaultAdapters();
        }

        $normalized = [];

        if ($adapters instanceof CacheOptimizerAdapter) {
            $normalized[] = $adapters;
        } elseif ($adapters !== null) {
            foreach ($adapters as $adapter) {
                if (!$adapter instanceof CacheOptimizerAdapter) {
                    throw new \InvalidArgumentException(sprintf(
                        'Cache optimizer adapters must implement %s.',
                        CacheOptimizerAdapter::class,
                    ));
                }

                $normalized[] = $adapter;
            }
        }

        foreach ($additionalAdapters as $adapter) {
            $normalized[] = $adapter;
        }

        return array_values($normalized);
    }

    /** @return list<CacheOptimizerAdapter> */
    private static function defaultAdapters(): array
    {
        return [
            new Adapter\WpRocketAdapter(),
            new Adapter\SiteGroundOptimizerAdapter(),
            new Adapter\W3TotalCacheAdapter(),
            new Adapter\AutoptimizeAdapter(),
            new Adapter\LiteSpeedCacheAdapter(),
        ];
    }
}
