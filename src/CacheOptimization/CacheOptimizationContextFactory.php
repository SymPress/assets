<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

use SymPress\Assets\Asset;
use SymPress\Assets\Script;
use SymPress\Assets\ScriptModule;
use SymPress\Assets\Style;
use SymPress\Assets\Util\FilesystemPath;

final readonly class CacheOptimizationContextFactory
{
    /**
     * @param array<class-string, array<string, Asset>> $assets
     */
    public function create(array $assets): CacheOptimizationContext
    {
        $scripts = [];
        $styles = [];

        foreach ($assets as $typedAssets) {
            foreach ($typedAssets as $asset) {
                if (!$asset instanceof CacheOptimizationAwareAsset) {
                    continue;
                }

                $exclusion = $asset->cacheOptimizationExclusion();
                if (null === $exclusion) {
                    continue;
                }

                $optimizationAsset = $this->createAsset($asset, $exclusion);

                if ($asset instanceof Script || $asset instanceof ScriptModule) {
                    $scripts[] = $optimizationAsset;
                    continue;
                }

                if ($asset instanceof Style) {
                    $styles[] = $optimizationAsset;
                }
            }
        }

        return new CacheOptimizationContext($scripts, $styles);
    }

    private function createAsset(Asset $asset, CacheOptimizationExclusion $exclusion): CacheOptimizationAsset
    {
        $url = $asset->url();
        $filePath = $asset->filePath();

        return new CacheOptimizationAsset(
            $asset->handle(),
            $url,
            $filePath,
            $exclusion,
            $this->fileIdentifiers($url, $filePath),
        );
    }

    /**
     * @return list<string>
     */
    private function fileIdentifiers(string $url, string $filePath): array
    {
        $identifiers = [$url, $this->withoutQuery($url), $this->urlPath($url)];

        $decodedUrl = rawurldecode($url);
        if ($decodedUrl !== $url) {
            $identifiers[] = $decodedUrl;
            $identifiers[] = $this->withoutQuery($decodedUrl);
            $identifiers[] = $this->urlPath($decodedUrl);
        }

        if ('' !== $filePath) {
            $identifiers[] = $filePath;
            $identifiers[] = FilesystemPath::normalize($filePath);
        }

        return array_values(array_unique(array_filter($identifiers, static fn (string $identifier): bool => '' !== $identifier)));
    }

    private function withoutQuery(string $reference): string
    {
        return strtok($reference, '?#') ?: $reference;
    }

    private function urlPath(string $reference): string
    {
        $path = parse_url($reference, PHP_URL_PATH);

        return is_string($path) ? rawurldecode($path) : '';
    }
}
