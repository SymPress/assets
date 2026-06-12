<?php

declare(strict_types=1);

namespace SymPress\Assets\Handler;

use SymPress\Assets\Asset;
use SymPress\Assets\FilterAwareAsset;
use SymPress\Assets\OutputFilter\AssetOutputFilter;

trait OutputFilterAwareAssetHandlerTrait
{
    /** @var array<string, callable|class-string<AssetOutputFilter>> */
    protected array $outputFilters = [];

    /** @var array<string, bool> */
    private array $registeredOutputFilters = [];

    /**
     * @var array<string, array{
     *      key: string,
     *      asset: FilterAwareAsset,
     *      filters: list<class-string<AssetOutputFilter>|callable>
     * }>
     */
    private array $outputFilterAssets = [];

    private bool $outputFilterDispatcherRegistered = false;

    public function withOutputFilter(string $name, callable $filter): OutputFilterAwareAssetHandler
    {
        $this->outputFilters[$name] = $filter;

        return $this;
    }

    /** @return array<string, callable|class-string<AssetOutputFilter>> */
    public function outputFilters(): array
    {
        return $this->outputFilters;
    }

    public function filter(Asset $asset): bool
    {
        if (!$asset instanceof FilterAwareAsset) {
            return false;
        }

        $filters = $this->currentOutputFilters($asset);
        if (count($filters) === 0) {
            return false;
        }

        $filterKey = $this->filterKey($asset);
        $alreadyRegistered = !empty($this->registeredOutputFilters[$filterKey]);
        $registeredAsset = $this->outputFilterAssets[$asset->handle()] ?? null;
        if ($registeredAsset !== null && $registeredAsset['key'] !== $filterKey) {
            throw new \LogicException(sprintf(
                'Output filters cannot be registered for duplicate asset handle "%s" on hook "%s".',
                $asset->handle(),
                $this->filterHook(),
            ));
        }

        $this->registeredOutputFilters[$filterKey] = true;
        $this->outputFilterAssets[$asset->handle()] = [
            'key'     => $filterKey,
            'asset'   => $asset,
            'filters' => array_values($filters),
        ];

        if (!$this->outputFilterDispatcherRegistered) {
            $this->outputFilterDispatcherRegistered = true;

            add_filter(
                $this->filterHook(),
                fn (string $html, string $handle): string => $this->applyOutputFilters($html, $handle),
                10,
                2,
            );
        }

        return !$alreadyRegistered;
    }

    /** @return array<class-string<AssetOutputFilter>|callable> */
    protected function currentOutputFilters(Asset $asset): array
    {
        $filters = [];
        $registeredFilters = $this->outputFilters();

        if (!$asset instanceof FilterAwareAsset) {
            return $filters;
        }

        foreach ($asset->filters() as $filter) {
            if (is_callable($filter)) {
                $filters[] = $filter;
                continue;
            }
            if (!isset($registeredFilters[$filter])) {
                continue;
            }

            $filters[] = $registeredFilters[$filter];
        }

        return $filters;
    }

    /**
     * Defines the name of hook to filter the specific asset.
     */
    abstract public function filterHook(): string;

    private function filterKey(Asset $asset): string
    {
        return $this->filterHook() . '|' . $asset::class . '|' . $asset->handle();
    }

    private function applyOutputFilters(string $html, string $handle): string
    {
        $registration = $this->outputFilterAssets[$handle] ?? null;
        if ($registration === null) {
            return $html;
        }

        foreach ($registration['filters'] as $filter) {
            if (!is_callable($filter)) {
                continue;
            }

            $result = $filter($html, $registration['asset']);
            if (!is_scalar($result) && !($result instanceof \Stringable)) {
                continue;
            }

            $html = (string) $result;
        }

        return $html;
    }
}
