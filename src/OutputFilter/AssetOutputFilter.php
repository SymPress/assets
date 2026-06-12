<?php

declare(strict_types=1);

namespace SymPress\Assets\OutputFilter;

use SymPress\Assets\FilterAwareAsset;

interface AssetOutputFilter
{
    /** @return string $html */
    public function __invoke(string $html, FilterAwareAsset $asset): string;
}
