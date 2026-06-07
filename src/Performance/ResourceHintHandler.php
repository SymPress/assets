<?php

declare(strict_types=1);

namespace SymPress\Assets\Performance;

use SymPress\Assets\Asset;

final class ResourceHintHandler
{
    /**
     * @var list<ResourceHint>
     */
    private array $hints = [];

    private bool $registered = false;

    /**
     * @param array<class-string, array<string, Asset>> $assets
     */
    public function run(array $assets): bool
    {
        $this->hints = $this->collectHints($assets);
        if ([] === $this->hints) {
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

    /**
     * @param mixed $urls
     *
     * @return mixed
     */
    public function resourceHints(mixed $urls, string $relationType): mixed
    {
        if (!is_array($urls)) {
            return $urls;
        }

        $knownUrls = [];
        foreach ($urls as $url) {
            if (is_string($url)) {
                $knownUrls[$url] = true;
            }
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

    /**
     * @param mixed $resources
     *
     * @return mixed
     */
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

    /**
     * @param array<class-string, array<string, Asset>> $assets
     *
     * @return list<ResourceHint>
     */
    private function collectHints(array $assets): array
    {
        $hints = [];

        foreach ($assets as $typedAssets) {
            foreach ($typedAssets as $asset) {
                if (!$asset instanceof ResourceHintAwareAsset) {
                    continue;
                }

                foreach ($asset->resourceHints() as $hint) {
                    $hints[$hint->relation() . "\0" . $hint->href() . "\0" . serialize($hint->attributes())] = $hint;
                }
            }
        }

        return array_values($hints);
    }

    /**
     * @return list<ResourceHint>
     */
    private function hintsForRelation(string $relation): array
    {
        return array_values(array_filter(
            $this->hints,
            static fn (ResourceHint $hint): bool => $relation === $hint->relation(),
        ));
    }
}
