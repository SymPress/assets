<?php

declare(strict_types=1);

namespace SymPress\Assets;

/** @phpstan-type Assets array<class-string<Asset>, array<string, Asset>> */
class AssetCollection
{
    /** @var Assets */
    protected array $assets = [];

    public function add(Asset $asset): void
    {
        $type = $asset::class;
        $handle = $asset->handle();
        $this->assets[$type][$handle] = $asset;
    }

    /** @param class-string $type */
    public function get(string $handle, string $type): ?Asset
    {
        $asset = $this->assets[$type][$handle] ?? null;
        if ($asset !== null && is_a($asset, $type)) {
            return $asset;
        }

        foreach ($this->assets as $assets) {
            foreach ($assets as $asset) {
                if ($asset->handle() !== $handle) {
                    continue;
                }
                if (is_a($asset, $type)) {
                    return $asset;
                }
            }
        }

        return null;
    }

    public function getFirst(string $handle): ?Asset
    {
        $found = null;
        foreach ($this->assets as $assets) {
            foreach ($assets as $asset) {
                if ($asset->handle() === $handle) {
                    $found = $asset;
                    break 2;
                }
            }
        }

        return $found;
    }

    /** @param class-string $type */
    public function has(string $handle, string $type): bool
    {
        return $this->get($handle, $type) !== null;
    }

    /** @return Assets */
    public function all(): array
    {
        return $this->assets;
    }
}
