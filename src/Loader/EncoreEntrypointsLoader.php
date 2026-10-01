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
     * Load local files only, confined to the manifest directory.
     *
     * @return array<Asset>
     */
    public function fromFile(string $file): array
    {
        $entries = EncoreManifest::read($file);
        return $entries === null ? [] : $this->loadFromArray(['entrypoints' => $entries], $file);
    }

    /**
     * @return list<string>
     */
    public function editorStyles(string $file, string $entry, string $prefix = 'build/'): array
    {
        $entries = EncoreManifest::read($file);
        return array_map(static fn (string $css): string => $prefix . preg_replace('~^\\./~', '', $css), $entries[$entry]['css'] ?? []);
    }

    /**
     * Load an already decoded and validated Encore manifest without reading it again.
     * The resource path anchors relative asset paths; the manifest need not exist.
     * File access and inline-content policies still apply when assets are consumed.
     *
     * @param array{entrypoints?: array<string, array{css?: list<string>, js?: list<string>}>} $data
     * @return array<Asset>
     */
    #[\NoDiscard]
    public function loadFromArray(array $data, string $resource): array
    {
        return $this->parseData($data, $resource);
    }

    /**
     * {@inheritDoc}
     */
    protected function parseData(array $data, string $resource): array
    {
        $directory = Path::getDirectory($resource);
        /**
     * @var array{entrypoints:array{css?:array<string>, js?:array<string>}} $data
     */
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
