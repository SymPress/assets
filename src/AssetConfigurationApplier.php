<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\CacheOptimization\CacheOptimizationAwareAsset;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\Performance\ResourceHint;
use SymPress\Assets\Performance\ResourceHintAwareAsset;

final readonly class AssetConfigurationApplier
{
    private const array COMMON_PROPERTIES_TO_METHOD = [
        'filePath'   => 'withFilePath',
        'version'    => 'withVersion',
        'location'   => 'forLocation',
        'enqueue'    => 'canEnqueue',
        'handler'    => 'useHandler',
        'condition'  => 'withCondition',
        'attributes' => 'withAttributes',
    ];

    private const array STYLE_PROPERTIES_TO_METHOD = [
        'media'        => 'forMedia',
        'inlineStyles' => 'withInlineStyles',
        'loading'      => 'withLoadingMode',
        'loadingMode'  => 'withLoadingMode',
    ];

    /** @param array<string, mixed> $config */
    #[\NoDiscard]
    public function apply(Asset $asset, array $config): Asset
    {
        if ($asset instanceof Script) {
            $this->applyScriptConfig($asset, $config);
        }

        if ($asset instanceof DependencyExtractionAwareAsset) {
            $this->applyDependencyExtractionConfig($asset, $config);
        }

        $propertyMap = self::COMMON_PROPERTIES_TO_METHOD;

        if ($asset instanceof Style) {
            $propertyMap = [...$propertyMap, ...self::STYLE_PROPERTIES_TO_METHOD];
        }

        $this->applyMappedConfig($asset, $config, $propertyMap);
        $this->applyDependencies($asset, $config['dependencies'] ?? null);
        $this->applyCacheOptimizationConfig($asset, $config['cacheOptimization'] ?? null);
        $this->applyResourceHintsConfig($asset, $config['resourceHints'] ?? null);

        return $asset;
    }

    private function applyCacheOptimizationConfig(Asset $asset, mixed $config): void
    {
        if (!$asset instanceof CacheOptimizationAwareAsset) {
            return;
        }

        if ($config === null) {
            return;
        }

        if ($config instanceof CacheOptimizationExclusion) {
            $asset->excludeFromCacheOptimization($config);

            return;
        }

        if ($config === true) {
            $asset->excludeFromCacheOptimization();

            return;
        }

        if ($config === false) {
            $asset->allowCacheOptimization();

            return;
        }

        throw new Exception\InvalidArgumentException(
            'Config key <code>cacheOptimization</code> must be boolean or a CacheOptimizationExclusion.',
        );
    }

    /** @param array<string, mixed> $config */
    private function applyDependencyExtractionConfig(DependencyExtractionAwareAsset $asset, array $config): void
    {
        if (array_key_exists('dependencyExtractionEnabled', $config) && !is_bool($config['dependencyExtractionEnabled'])) {
            throw new Exception\InvalidArgumentException('Config key <code>dependencyExtractionEnabled</code> must be boolean.');
        }

        if (array_key_exists('dependencyExtractionEnabled', $config)) {
            $asset->withDependencyExtraction($config['dependencyExtractionEnabled']);
        }

        if (array_key_exists('phpDependencyFiles', $config) && !is_bool($config['phpDependencyFiles'])) {
            throw new Exception\InvalidArgumentException('Config key <code>phpDependencyFiles</code> must be boolean.');
        }

        if (array_key_exists('phpDependencyFiles', $config)) {
            $asset->withPhpDependencyFiles($config['phpDependencyFiles']);
        }

        $sizeLimit = $config['dependencyFileSizeLimit'] ?? null;
        if ($sizeLimit === null) {
            return;
        }

        if (!is_int($sizeLimit)) {
            throw new Exception\InvalidArgumentException('Config key <code>dependencyFileSizeLimit</code> must be an integer.');
        }

        $asset->withDependencyFileSizeLimit($sizeLimit);
    }

    /** @param array<string, mixed> $config */
    private function applyScriptConfig(Script $asset, array $config): void
    {
        $localize = $config['localize'] ?? [];
        if (is_callable($localize)) {
            $localize = $localize();
        }

        $this->applyLocalizeConfig($asset, is_array($localize) ? $localize : []);
        $this->applyTranslationConfig($asset, $config['translation'] ?? null);

        $inFooter = $config['inFooter'] ?? true;
        if (!is_bool($inFooter)) {
            throw new Exception\InvalidArgumentException('Config key <code>inFooter</code> must be boolean.');
        }

        $inFooter
            ? $asset->isInFooter()
            : $asset->isInHeader();

        $this->applyScriptLoadingStrategy($asset, $config);

        $inline = $config['inline'] ?? [];
        $inline = is_array($inline) ? $inline : [];

        $this->applyInlineScripts($asset, $inline['before'] ?? [], 'prependInlineScript');
        $this->applyInlineScripts($asset, $inline['after'] ?? [], 'appendInlineScript');
    }

    /** @param array<string, mixed> $config */
    private function applyScriptLoadingStrategy(Script $asset, array $config): void
    {
        $strategy = $config['loadingStrategy'] ?? $config['strategy'] ?? null;
        if ($strategy === null) {
            return;
        }

        if (!is_scalar($strategy) && !$strategy instanceof \Stringable) {
            throw new Exception\InvalidArgumentException('Config key <code>strategy</code> must be a string.');
        }

        $asset->withLoadingStrategy((string) $strategy);
    }

    /** @param array<mixed> $localize */
    private function applyLocalizeConfig(Script $asset, array $localize): void
    {
        foreach ($localize as $objectName => $data) {
            if (!is_array($data) && !is_scalar($data) && !is_callable($data)) {
                continue;
            }

            if (is_bool($data) || is_float($data)) {
                $data = (string) $data;
            }

            $asset->withLocalize((string) $objectName, $data);
        }
    }

    private function applyTranslationConfig(Script $asset, mixed $translation): void
    {
        if (!is_array($translation) || !isset($translation['domain'])) {
            return;
        }

        $path = $translation['path'] ?? null;

        $domain = $translation['domain'];
        if (!is_scalar($domain) && !$domain instanceof \Stringable) {
            return;
        }

        $asset->withTranslation((string) $domain, is_string($path) ? $path : null);
    }

    /** @param 'appendInlineScript'|'prependInlineScript' $method */
    private function applyInlineScripts(Script $asset, mixed $scripts, string $method): void
    {
        if (!is_array($scripts)) {
            $scripts = is_scalar($scripts) || $scripts instanceof \Stringable
                ? [$scripts]
                : [];
        }

        foreach ($scripts as $script) {
            if (!is_scalar($script) && !$script instanceof \Stringable) {
                continue;
            }

            $asset->{$method}((string) $script);
        }
    }

    /**
     * @param array<string, mixed>  $config
     * @param array<string, string> $propertyMap
     */
    private function applyMappedConfig(Asset $asset, array $config, array $propertyMap): void
    {
        foreach ($propertyMap as $key => $methodName) {
            if (!array_key_exists($key, $config)) {
                continue;
            }

            $callback = [$asset, $methodName];

            if (!is_callable($callback)) {
                continue;
            }

            $callback($config[$key]);
        }
    }

    private function applyDependencies(Asset $asset, mixed $dependencies): void
    {
        if (is_array($dependencies)) {
            $asset->withDependencies(...$this->normalizeDependencies($dependencies));

            return;
        }

        if (!is_scalar($dependencies)) {
            return;
        }

        $asset->withDependencies((string) $dependencies);
    }

    /**
     * @param array<mixed> $dependencies
     * @return list<string>
     */
    private function normalizeDependencies(array $dependencies): array
    {
        $normalized = [];

        foreach ($dependencies as $dependency) {
            if (!is_scalar($dependency) && !$dependency instanceof \Stringable) {
                continue;
            }

            $normalized[] = (string) $dependency;
        }

        return $normalized;
    }

    private function applyResourceHintsConfig(Asset $asset, mixed $config): void
    {
        if (!$asset instanceof ResourceHintAwareAsset || $config === null) {
            return;
        }

        if (!is_array($config)) {
            throw new Exception\InvalidArgumentException('Config key <code>resourceHints</code> must be an array.');
        }

        foreach ($config as $hint) {
            if ($hint instanceof ResourceHint) {
                $asset->withResourceHint($hint);

                continue;
            }

            if (!is_array($hint)) {
                continue;
            }

            $this->applyResourceHintConfig($asset, $hint);
        }
    }

    /** @param array<mixed> $config */
    private function applyResourceHintConfig(ResourceHintAwareAsset&Asset $asset, array $config): void
    {
        $relation = $config['relation'] ?? $config['rel'] ?? null;
        if (!is_scalar($relation) && !$relation instanceof \Stringable) {
            throw new Exception\InvalidArgumentException('Resource hint relation must be a string.');
        }

        $href = $config['href'] ?? $asset->url();
        if (!is_scalar($href) && !$href instanceof \Stringable) {
            throw new Exception\InvalidArgumentException('Resource hint href must be a string.');
        }

        $attributes = $this->resourceHintAttributes($config);
        if ((string) $relation === ResourceHint::PRELOAD && !isset($attributes['as'])) {
            throw new Exception\InvalidArgumentException('Preload resource hints require an <code>as</code> attribute.');
        }

        $asset->withResourceHint(new ResourceHint((string) $relation, (string) $href, $attributes));
    }

    /**
     * @param array<mixed> $config
     * @return array<string, string|bool|int|float|null>
     */
    private function resourceHintAttributes(array $config): array
    {
        $attributes = [];
        if (is_array($config['attributes'] ?? null)) {
            foreach ($config['attributes'] as $key => $value) {
                $attributes[(string) $key] = $value;
            }
        }

        foreach ($config as $key => $value) {
            if (in_array($key, ['relation', 'rel', 'href', 'attributes'], true)) {
                continue;
            }

            $attributes[(string) $key] = $value;
        }

        return array_filter(
            $attributes,
            static fn (mixed $value): bool => $value === null || is_scalar($value),
        );
    }
}
