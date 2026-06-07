<?php

declare(strict_types=1);

namespace SymPress\Assets\OutputFilter;

use SymPress\Assets\FilterAwareAsset;

/**
 * @deprecated use \SymPress\Assets\Script::defer().
 */
class DeferScriptOutputFilter implements AssetOutputFilter
{
    public function __invoke(string $html, FilterAwareAsset $asset): string
    {
        $asset = clone $asset;

        return (new AttributesOutputFilter())(
            $html,
            $asset->withAttributes(['defer' => true]),
        );
    }
}
