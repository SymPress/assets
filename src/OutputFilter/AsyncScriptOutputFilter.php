<?php

declare(strict_types=1);

namespace SymPress\Assets\OutputFilter;

use SymPress\Assets\FilterAwareAsset;

/**
 * @deprecated use \SymPress\Assets\Script::async().
 */
class AsyncScriptOutputFilter implements AssetOutputFilter
{
    public function __invoke(string $html, FilterAwareAsset $asset): string
    {
        $asset = clone $asset;

        return (new AttributesOutputFilter())(
            $html,
            $asset->withAttributes(['async' => true]),
        );
    }
}
