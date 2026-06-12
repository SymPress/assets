<?php

declare(strict_types=1);

namespace SymPress\Assets\Loader;

use SymPress\Assets\Asset;
use Symfony\Component\Filesystem\Path;

/**
 * Implementation of Symfony's Encore implementation of entrypoints.json which
 * supports splitEntryChunks and hashing.
 */
class EncoreEntrypointsLoader extends AbstractWebpackLoader implements LoaderInterface
{
    /**
     * {@inheritDoc}
     */
    protected function parseData(array $data, string $resource): array
    {
        $directory = Path::getDirectory($resource);
        /** @var array{entrypoints:array{css?:array<string>, js?:array<string>}} $data */
        $data = $data['entrypoints'] ?? [];

        $assets = [];
        foreach ($data as $handle => $filesByExtension) {
            $files = $filesByExtension['css'] ?? [];
            $assets = array_merge($assets, $this->extractAssets($handle, $files, $directory));

            $files = $filesByExtension['js'] ?? [];
            $assets = array_merge($assets, $this->extractAssets($handle, $files, $directory));
        }

        return $assets;
    }

    /**
     * @param array<string> $files
     * @return array<Asset>
     */
    protected function extractAssets(string $handle, array $files, string $directory): array
    {
        $assets = [];

        foreach ($files as $i => $file) {
            $assetHandle = $i > 0
                ? "{$handle}-{$i}"
                : $handle;

            $sanitizedFile = $this->sanitizeFileName($file);

            $fileUrl = !$this->directoryUrl
                ? $file
                : $this->directoryUrl . $sanitizedFile;

            $filePath = Path::join($directory, $sanitizedFile);

            $asset = $this->buildAsset($assetHandle, $fileUrl, $filePath);

            if ($asset === null) {
                continue;
            }

            $assets[] = $asset;
        }

        foreach ($assets as $i => $asset) {
            $dependencies = array_map(
                static function (Asset $asset): string {
                    return $asset->handle();
                },
                array_slice($assets, 0, $i),
            );
            $asset->withDependencies(...$dependencies);
        }

        return $assets;
    }
}
