<?php

declare(strict_types=1);

namespace SymPress\Assets\Performance;

use SymPress\Assets\Asset;

final class ResourceHintHandler
{
    /** @var list<Asset&ResourceHintAwareAsset> */
    private array $assets = [];

    /** @var list<ResourceHint>|null */
    private ?array $hints = null;

    private bool $registered = false;

    /** @param array<class-string, array<string, Asset>> $assets */
    public function run(array $assets): bool
    {
        $this->assets = [];
        $this->hints = null;
        foreach ($assets as $typedAssets) {
            foreach ($typedAssets as $asset) {
                if (!$asset instanceof ResourceHintAwareAsset || $asset->resourceHints() === []) {
                    continue;
                }

                $this->assets[] = $asset;
            }
        }

        if ($this->assets === []) {
            return false;
        }

        if ($this->registered) {
            return true;
        }

        add_filter('wp_resource_hints', $this->resourceHints(...), 10, 2);
        add_filter('wp_preload_resources', $this->preloadResources(...));
        $this->registered = true;

        return true;
    }

    public function resourceHints(mixed $urls, string $relationType): mixed
    {
        if (!is_array($urls)) {
            return $urls;
        }

        $knownUrls = [];
        foreach ($urls as $url) {
            if (!is_string($url)) {
                continue;
            }

            $knownUrls[$url] = true;
        }

        foreach ($this->hintsForRelation($relationType) as $hint) {
            if (isset($knownUrls[$hint->href()])) {
                continue;
            }

            $urls[] = $hint->href();
            $knownUrls[$hint->href()] = true;
        }

        return array_values($urls);
    }

    public function preloadResources(mixed $resources): mixed
    {
        if (!is_array($resources)) {
            return $resources;
        }

        foreach ($this->hintsForRelation(ResourceHint::PRELOAD) as $hint) {
            $resources[] = [
                'href' => $hint->href(),
                ...$hint->attributes(),
            ];
        }

        return $resources;
    }

    /** @return list<ResourceHint> */
    private function collectHints(): array
    {
        $hints = [];

        foreach ($this->assets as $asset) {
            if (!$asset->enqueue()) {
                continue;
            }

            foreach ($asset->resourceHints() as $hint) {
                $hints[$hint->relation() . "\0" . $hint->href() . "\0" . serialize($hint->attributes())] = $hint;
            }
        }

        return array_values($hints);
    }

    /** @return list<ResourceHint> */
    private function hintsForRelation(string $relation): array
    {
        // Collected when WordPress prints the hints: enqueue() conditions may rely on query tags.
        $this->hints ??= $this->collectHints();

        return array_values(array_filter(
            $this->hints,
            static fn (ResourceHint $hint): bool => $relation === $hint->relation(),
        ));
    }
}
