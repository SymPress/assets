<?php

declare(strict_types=1);

namespace SymPress\Assets\Loader;

use SymPress\Assets\Asset;
use SymPress\Assets\AssetFactory;
use SymPress\Assets\BaseAsset;
use SymPress\Assets\Exception;
use Symfony\Component\Filesystem\Path;

/**
 * Implementation of Webpack manifest.json parsing into Assets.
 *
 * @see https://www.npmjs.com/package/webpack-manifest-plugin
 * @phpstan-import-type AssetConfig from AssetFactory
 * @phpstan-import-type AssetExtensionConfig from AssetFactory
 * @phpstan-type Configuration = AssetConfig&AssetExtensionConfig
 */
class WebpackManifestLoader extends AbstractWebpackLoader
{
    protected function parseData(array $data, string $resource): array
    {
        $directory = Path::getDirectory($resource);
        $assets = [];
        foreach ($data as $handle => $fileOrArray) {
            if ($handle === '') {
                continue;
            }

            $asset = null;

            if (is_array($fileOrArray)) {
                $asset = $this->handleAsArray($handle, $this->normalizeConfiguration($fileOrArray), $directory);
            }
            if (is_string($fileOrArray)) {
                $asset = $this->handleUsingFileName($handle, $fileOrArray, $directory);
            }

            if (!$asset) {
                continue;
            }

            $assets[] = $asset;
        }

        return $assets;
    }

    /**
     * @param array<mixed> $configuration
     * @return array<string, mixed>
     */
    private function normalizeConfiguration(array $configuration): array
    {
        $normalized = [];

        foreach ($configuration as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $configuration
     * @throws Exception\InvalidArgumentException
     * @throws Exception\MissingArgumentException
     */
    protected function handleAsArray(string $handle, array $configuration, string $directory): ?Asset
    {
        $file = $this->extractFilePath($configuration);

        if (!$file) {
            return null;
        }

        $sanitizedFile = $this->sanitizeFileName($file);
        $class = $this->resolveClassByExtension($sanitizedFile);

        if (!$class) {
            return null;
        }

        $location = $this->buildLocations($configuration);
        $version = $this->extractVersion($configuration);
        $handle = $this->normalizeHandle($handle);

        $configuration['handle'] = $handle;
        $configuration['url'] = $this->fileUrl($sanitizedFile);
        $configuration['filePath'] = $this->filePath($sanitizedFile, $directory);
        $configuration['type'] = $class;
        $configuration['location'] = $location;
        if ($version !== null) {
            $configuration['version'] = $version;
        } else {
            unset($configuration['version']);
        }

        /** @var Configuration $configuration */
        $asset = AssetFactory::create($configuration);
        if ($version === null && $asset instanceof BaseAsset) {
            $this->autodiscoverVersion
                ? $asset->enableAutodiscoverVersion()
                : $asset->disableAutodiscoverVersion();
        }

        return $asset;
    }

    /** @param array<string, mixed> $configuration */
    protected function extractFilePath(array $configuration): ?string
    {
        $filePath = $configuration['filePath'] ?? null;

        return is_string($filePath) ? $filePath : null;
    }

    /** @param array<string, mixed> $configuration */
    protected function extractVersion(array $configuration): ?string
    {
        $version = $configuration['version'] ?? null;

        if (!is_scalar($version) && !$version instanceof \Stringable) {
            return null;
        }

        return (string) $version;
    }

    /** @param array<string, mixed> $configuration */
    protected function buildLocations(array $configuration): int
    {
        $locations = $configuration['location'] ?? null;
        $locations = is_array($locations) ? $locations : [];

        if (count($locations) === 0) {
            return Asset::FRONTEND;
        }

        $locations = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (mixed $location): ?string => is_scalar($location) || $location instanceof \Stringable
                            ? (string) $location
                            : null,
                        $locations,
                    ),
                ),
            ),
        );

        if (count($locations) === 0) {
            return Asset::FRONTEND;
        }

        $collector = array_shift($locations);
        $collector = static::resolveLocation("-{$collector}");
        foreach ($locations as $location) {
            $collector |= static::resolveLocation("-{$location}");
        }

        return $collector;
    }

    protected function handleUsingFileName(string $handle, string $file, string $directory): ?Asset
    {
        $handle = $this->normalizeHandle($handle);
        $sanitizedFile = $this->sanitizeFileName($file);
        $fileUrl = $this->fileUrl($sanitizedFile);
        $filePath = $this->filePath($sanitizedFile, $directory);

        return $this->buildAsset($handle, $fileUrl, $filePath);
    }

    protected function fileUrl(string $file): string
    {
        $sanitizedFile = $this->sanitizeFileName($file);

        return !$this->directoryUrl ? $file : $this->directoryUrl . $sanitizedFile;
    }

    protected function filePath(string $file, string $directory): string
    {
        return Path::join($directory, $this->sanitizeFileName($file));
    }
}
