<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\CacheOptimization\CacheOptimizationAwareAsset;
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;
use SymPress\Assets\CacheOptimization\CacheOptimizationHandler;
use SymPress\Assets\Handler\AssetHandler;
use SymPress\Assets\Handler\OutputFilterAwareAssetHandler;
use SymPress\Assets\Handler\ScriptHandler;
use SymPress\Assets\Handler\ScriptModuleHandler;
use SymPress\Assets\Handler\StyleHandler;
use SymPress\Assets\Performance\ResourceHintHandler;
use SymPress\Assets\Util\AssetHookResolver;

/**
 * @phpstan-import-type AssetExtensionConfig from AssetFactory
 */
final class AssetManager
{
    public const string ACTION_SETUP = 'sympress.assets.setup';

    /**
     * Contains the state of the AssetManager, where keys are hook names that are already added
     * to avoid add them more than once.
     *
     * @var array<string, bool>
     */
    private array $hooksAdded = [];

    /**
     * @var array<
     *      Style::class|Script::class|ScriptModule::class,
     *      array<string, array<string, mixed>>
     * >
     */
    private array $extensions = [];

    /**
     * @var array<string, bool>
     */
    private array $processedAssets = [];

    private AssetCollection $assets;

    /**
     * @var array<AssetHandler>
     */
    private array $handlers = [];

    private AssetHookResolver $hookResolver;

    private bool $setupDone = false;

    private AssetExtensionMerger $extensionMerger;

    private CacheOptimizationHandler $cacheOptimizationHandler;

    private ResourceHintHandler $resourceHintHandler;

    /**
     * @var array<string, list<Asset>>
     */
    private array $assetsByHook = [];

    private bool $assetIndexDirty = true;

    public function __construct(
        ?AssetHookResolver $hookResolver = null,
        ?AssetExtensionMerger $extensionMerger = null,
        ?CacheOptimizationHandler $cacheOptimizationHandler = null,
        ?ResourceHintHandler $resourceHintHandler = null,
    ) {
        $this->hookResolver = $hookResolver ?? new AssetHookResolver();
        $this->extensionMerger = $extensionMerger ?? new AssetExtensionMerger();
        $this->cacheOptimizationHandler = $cacheOptimizationHandler ?? new CacheOptimizationHandler();
        $this->resourceHintHandler = $resourceHintHandler ?? new ResourceHintHandler();
        $this->assets = new AssetCollection();
    }

    public function useDefaultHandlers(): static
    {
        $this->handlers[StyleHandler::class] ??= new StyleHandler(wp_styles());
        $this->handlers[ScriptHandler::class] ??= new ScriptHandler(wp_scripts());
        $this->handlers[ScriptModuleHandler::class] ??= new ScriptModuleHandler();

        return $this;
    }

    public function withHandler(string $name, AssetHandler $handler): static
    {
        $this->handlers[$name] = $handler;

        return $this;
    }

    /**
     * @return array<AssetHandler>
     */
    public function handlers(): array
    {
        return $this->handlers;
    }

    public function register(Asset $asset, Asset ...$assets): static
    {
        array_unshift($assets, $asset);

        foreach ($assets as $asset) {
            $this->extendAndRegisterAsset($asset);
        }

        return $this;
    }

    /**
     * @return array<class-string, array<string, Asset>>
     */
    public function assets(): array
    {
        $this->ensureSetup();

        return $this->assets->all();
    }

    /**
     * Retrieve an `Asset` instance by a given asset handle and type (class).
     *
     * @param class-string|null $type deprecated, will be in future not nullable
     */
    public function asset(string $handle, ?string $type = null): ?Asset
    {
        $this->ensureSetup();

        if (null === $type) {
            return $this->assets->getFirst($handle);
        }

        return $this->assets->get($handle, $type);
    }

    /**
     * @param class-string         $type
     * @param array<string, mixed> $extensions
     *
     * @return $this
     */
    public function extendAsset(string $handle, string $type, array $extensions): static
    {
        $this->extensions[$type][$handle] = $this->extensionMerger->merge(
            $this->extensions[$type][$handle] ?? [],
            $extensions,
        );

        // In case, the asset is already registered,
        // but not yet processed, extend it.
        $asset = $this->assets->get($handle, $type);
        if (null !== $asset && !$this->isAssetProcessed($asset)) {
            $this->extendAndRegisterAsset($asset);
        }

        return $this;
    }

    /**
     * @param class-string $type
     *
     * @return array<string, mixed>
     */
    public function assetExtensions(string $handle, string $type): array
    {
        return $this->extensions[$type][$handle] ?? [];
    }

    /**
     * Registers optimizer-plugin exclusions for assets that explicitly opted in via
     * Asset::excludeFromCacheOptimization().
     */
    public function registerCacheOptimizationExclusions(): bool
    {
        return $this->cacheOptimizationHandler->run($this);
    }

    public function registerResourceHints(): bool
    {
        return $this->resourceHintHandler->run($this->assets->all());
    }

    /**
     * Convenience method matching the common cache-ignore use case from asset-library integrations.
     *
     * @param callable(Asset): bool|null $assetFilter
     */
    public function ignoreCache(?callable $assetFilter = null): bool
    {
        foreach ($this->assets() as $typedAssets) {
            foreach ($typedAssets as $asset) {
                if (!$asset instanceof CacheOptimizationAwareAsset) {
                    continue;
                }

                if (null !== $assetFilter && !$assetFilter($asset)) {
                    continue;
                }

                $asset->excludeFromCacheOptimization(CacheOptimizationExclusion::minifyAndCombine());
            }
        }

        return $this->registerCacheOptimizationExclusions();
    }

    /**
     * @return $this
     */
    private function extendAndRegisterAsset(Asset $asset): static
    {
        $handle = $asset->handle();
        $type = get_class($asset);
        $extensions = $this->assetExtensions($handle, $type);
        if (count($extensions) > 0 && !$this->isAssetProcessed($asset)) {
            $asset = AssetFactory::configureAsset($asset, $extensions);
        }

        $this->assets->add($asset);
        $this->assetIndexDirty = true;

        return $this;
    }

    public function setup(): bool
    {
        $hooksAdded = 0;

        /**
         * It is possible to execute AssetManager::setup() at a specific hook to only process assets
         * specific of that hook.
         *
         * E.g. `add_action('enqueue_block_editor_assets', [new AssetManager, 'setup']);`
         *
         * `$this->hookResolver->resolve()` will return current hook if it is one of the assets
         * enqueuing hook.
         */
        foreach ($this->hookResolver->resolve() as $hook) {
            // If the hook was already added, or it is in the past, don't bother adding.
            if (!empty($this->hooksAdded[$hook]) || (did_action($hook) && !doing_action($hook))) {
                continue;
            }

            ++$hooksAdded;
            $this->hooksAdded[$hook] = true;

            add_action(
                $hook,
                function () use ($hook) {
                    $this->processAssets($hook);
                },
            );
        }

        return $hooksAdded > 0;
    }

    /**
     * Returning all matching assets to given hook.
     *
     * @return array<Asset>
     */
    public function currentAssets(string $currentHook): array
    {
        return $this->loopCurrentHookAssets($currentHook, false);
    }

    private function processAssets(string $currentHook): void
    {
        $this->loopCurrentHookAssets($currentHook, true);
    }

    /**
     * @return array<Asset>
     */
    private function loopCurrentHookAssets(string $currentHook, bool $process): array
    {
        $this->ensureSetup();
        if (count($this->assets->all()) < 1) {
            return [];
        }

        /** @var int|null $locationId */
        $locationId = Asset::HOOK_TO_LOCATION[$currentHook] ?? null;
        if (!$locationId) {
            return [];
        }

        $found = [];

        foreach ($this->assetsForHook($currentHook) as $asset) {
            $handlerName = $asset->handler();
            $handler = $this->handlers[$handlerName] ?? null;
            if (!$handler) {
                continue;
            }

            $found[] = $asset;
            if (!$process || $this->isAssetProcessed($asset)) {
                continue;
            }

            $done = $asset->enqueue()
                ? $handler->enqueue($asset)
                : $handler->register($asset);
            if ($done && ($handler instanceof OutputFilterAwareAssetHandler)) {
                $handler->filter($asset);
            }

            $this->processedAssets[$this->assetKey($asset)] = $done;
        }

        return $found;
    }

    private function isAssetProcessed(Asset $asset): bool
    {
        return (bool) ($this->processedAssets[$this->assetKey($asset)] ?? false);
    }

    private function ensureSetup(): void
    {
        if ($this->setupDone) {
            return;
        }

        $lastHook = $this->hookResolver->lastHook();

        /*
         * We should not setup if there's no asset hook or the last hook already fired.
         */
        if (null === $lastHook || (did_action($lastHook) && !doing_action($lastHook))) {
            return;
        }

        $this->setupDone = true;

        $this->useDefaultHandlers();
        do_action(self::ACTION_SETUP, $this);
        $this->registerResourceHints();
        $this->registerCacheOptimizationExclusions();
    }

    private function assetKey(Asset $asset): string
    {
        return get_class($asset) . '_' . $asset->handle();
    }

    /**
     * @return list<Asset>
     */
    private function assetsForHook(string $hook): array
    {
        if ($this->assetIndexDirty) {
            $this->rebuildAssetIndex();
        }

        return $this->assetsByHook[$hook] ?? [];
    }

    private function rebuildAssetIndex(): void
    {
        $this->assetsByHook = [];

        foreach (Asset::HOOK_TO_LOCATION as $hook => $locationId) {
            foreach ($this->assets->all() as $assets) {
                foreach ($assets as $asset) {
                    $location = $asset->location();
                    if (($location & $locationId) === $locationId) {
                        $this->assetsByHook[$hook][] = $asset;
                    }
                }
            }
        }

        $this->assetIndexDirty = false;
    }
}
