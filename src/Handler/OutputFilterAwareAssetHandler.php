<?php

declare(strict_types=1);

namespace SymPress\Assets\Handler;

use SymPress\Assets\Asset;
use SymPress\Assets\OutputFilter\AssetOutputFilter;

interface OutputFilterAwareAssetHandler
{
    /**
     * @return bool true when at least 1 filter is applied, otherwise false
     */
    public function filter(Asset $asset): bool;

    /**
     * Register new outputFilters to the Handler.
     */
    public function withOutputFilter(string $name, callable $filter): OutputFilterAwareAssetHandler;

    /**
     * Returns all registered outputFilters.
     *
     * @return array<string, callable|class-string<AssetOutputFilter>>
     */
    public function outputFilters(): array;
}
