<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Exception\InvalidArgumentException;
use SymPress\Assets\Loader\ArrayLoader;
use SymPress\Assets\Loader\PhpFileLoader;

/**
 * Class AssetFactory.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 *
 * @phpstan-type AssetLocation Asset::FRONTEND|Asset::BACKEND|Asset::CUSTOMIZER|Asset::LOGIN|Asset::BLOCK_EDITOR_ASSETS|Asset::BLOCK_ASSETS|Asset::CUSTOMIZER_PREVIEW|Asset::ACTIVATE
 * @phpstan-type AssetConfig array{
 *      type: class-string<Style>|class-string<Script>|class-string<ScriptModule>,
 *      handle: string,
 *      url: string,
 *  }
 * @phpstan-type AssetExtensionConfig array{
 *     filePath?: string,
 *     version?: string,
 *     enqueue?: bool,
 *     handler?: class-string<Handler\AssetHandler>,
 *     location?: AssetLocation,
 *     condition?: string,
     *     attributes?: array<string, string|bool>,
     *     cacheOptimization?: bool|\SymPress\Assets\CacheOptimization\CacheOptimizationExclusion|null,
     *     resourceHints?: array<int, \SymPress\Assets\Performance\ResourceHint|array<string, mixed>>,
     *     dependencyExtractionEnabled?: bool,
     *     phpDependencyFiles?: bool,
     *     dependencyFileSizeLimit?: positive-int,
     *     translation?: array<string, mixed>,
     *     localize?: array<string, mixed>|callable(): array<string, mixed>,
     *     inFooter?: bool,
     *     strategy?: string,
     *     loadingStrategy?: string,
     *     inline?: array<string, mixed>,
     *     dependencies?: string|array<int, string>,
     *     media?: string,
     *     loading?: string,
     *     loadingMode?: string,
     *     inlineStyles?: string,
     * }
 *
 * phpcs:enable Generic.Files.LineLength.TooLong
 */
final class AssetFactory
{
    private const array REQUIRED_CONFIG_FIELDS = [
        'type',
        'url',
        'handle',
    ];

    /**
     * @param array<string, mixed> $config
     *
     * @throws Exception\MissingArgumentException
     * @throws InvalidArgumentException
     */
    #[\NoDiscard]
    public static function create(array $config): Asset
    {
        $config = self::validateConfig($config);

        $class = (string) $config['type'];
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('The given class "%s" does not exist.', esc_html($class)));
        }

        try {
            $asset = new $class($config['handle'], $config['url'], $config['location'] ?? Asset::FRONTEND);
        } catch (\TypeError $exception) {
            throw new InvalidArgumentException(
                sprintf('The given asset class "%s" has an incompatible constructor.', esc_html($class)),
                previous: $exception,
            );
        }
        if (!$asset instanceof Asset) {
            throw new InvalidArgumentException(sprintf('The given class "%s" is not implementing %s', esc_html($class), Asset::class));
        }

        return self::configureAsset($asset, $config);
    }

    /**
     * @param array<string, mixed> $config
     */
    #[\NoDiscard]
    public static function configureAsset(Asset $asset, array $config): Asset
    {
        return (new AssetConfigurationApplier())->apply($asset, $config);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return AssetConfig&AssetExtensionConfig
     *
     * @throws Exception\MissingArgumentException
     */
    private static function validateConfig(array $config): array
    {
        self::ensureRequiredConfigFields($config);
        $config = self::normalizeVersionConfig($config);
        $config = self::normalizeTranslationConfig($config);
        $config = self::normalizeLocalizeConfig($config);

        if (!self::isValidatedConfig($config)) {
            throw new InvalidArgumentException('Config keys <code>type</code>, <code>handle</code> and <code>url</code> must be valid.');
        }

        return $config;
    }

    /**
     * @phpstan-assert-if-true AssetConfig&AssetExtensionConfig $config
     *
     * @param array<string, mixed> $config
     */
    private static function isValidatedConfig(array $config): bool
    {
        $type = $config['type'] ?? null;

        return is_string($config['handle'] ?? null)
            && is_string($config['url'] ?? null)
            && is_string($type)
            && class_exists($type)
            && is_a($type, Asset::class, true);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function ensureRequiredConfigFields(array $config): void
    {
        foreach (self::REQUIRED_CONFIG_FIELDS as $key) {
            if (!isset($config[$key])) {
                throw new Exception\MissingArgumentException(sprintf('The given config <code>%s</code> is missing.', esc_html($key)));
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private static function normalizeVersionConfig(array $config): array
    {
        // some existing configurations uses time() as version parameter which leads to
        // fatal errors since 2.5
        if (isset($config['version']) && (is_scalar($config['version']) || $config['version'] instanceof \Stringable)) {
            $config['version'] = (string) $config['version'];
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private static function normalizeTranslationConfig(array $config): array
    {
        if (!isset($config['translation'])) {
            return $config;
        }

        if (is_string($config['translation'])) {
            // backward compatibility
            $config['translation'] = [
                'domain' => $config['translation'],
                'path' => null,
            ];

            return $config;
        }

        if (!is_array($config['translation'])) {
            throw new InvalidArgumentException('Config key <code>translation</code> must be of type string or array');
        }

        if (!isset($config['translation']['domain'])) {
            throw new Exception\MissingArgumentException('Config key <code>translation[domain]</code> is missing.');
        }

        if (!isset($config['translation']['path'])) {
            $config['translation']['path'] = null;
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private static function normalizeLocalizeConfig(array $config): array
    {
        if (!isset($config['localize'])) {
            $config['localize'] = [];

            return $config;
        }
        if (is_callable($config['localize'])) {
            $config['localize'] = $config['localize']();
        }
        if (!is_array($config['localize'])) {
            throw new InvalidArgumentException('Config key <code>localize</code> must evaluate as an array');
        }

        return $config;
    }

    /**
     * @return Asset[]
     *
     * @throws Exception\FileNotFoundException
     *
     * @deprecated PhpArrayFileLoader::load(string $filePath)
     */
    #[\NoDiscard]
    public static function createFromFile(string $file): array
    {
        return (new PhpFileLoader())->load($file);
    }

    /**
     * @param array<mixed> $data
     *
     * @return Asset[]
     *
     * @throws Exception\FileNotFoundException
     *
     * @deprecated ArrayLoader::load(array $data)
     */
    #[\NoDiscard]
    public static function createFromArray(array $data): array
    {
        return (new ArrayLoader())->load($data);
    }
}
