<?php

declare(strict_types=1);

namespace SymPress\Assets\Handler;

use SymPress\Assets\Asset;
use SymPress\Assets\OutputFilter\AsyncStyleOutputFilter;
use SymPress\Assets\OutputFilter\AttributesOutputFilter;
use SymPress\Assets\OutputFilter\InlineAssetOutputFilter;
use SymPress\Assets\Style;

class StyleHandler implements AssetHandler, OutputFilterAwareAssetHandler
{
    use OutputFilterAwareAssetHandlerTrait;

    protected \WP_Styles $wpStyles;

    /**
     * StyleHandler constructor.
     *
     * @param array<string, callable> $outputFilters
     */
    public function __construct(\WP_Styles $wpStyles, array $outputFilters = [])
    {
        $this->withOutputFilter(AsyncStyleOutputFilter::class, new AsyncStyleOutputFilter());
        $this->withOutputFilter(InlineAssetOutputFilter::class, new InlineAssetOutputFilter());
        $this->withOutputFilter(AttributesOutputFilter::class, new AttributesOutputFilter());

        $this->wpStyles = $wpStyles;
        foreach ($outputFilters as $name => $callable) {
            $this->withOutputFilter($name, $callable);
        }
    }

    public function enqueue(Asset $asset): bool
    {
        if (!$asset instanceof Style) {
            return false;
        }

        $handle = $asset->handle();
        if ('' === $handle) {
            return false;
        }

        if (!$this->register($asset)) {
            return false;
        }

        if ($asset->enqueue()) {
            wp_enqueue_style($handle);

            return true;
        }

        return false;
    }

    public function register(Asset $asset): bool
    {
        if (!$asset instanceof Style) {
            return false;
        }

        $handle = $asset->handle();
        if ('' === $handle) {
            return false;
        }
        /* @var non-empty-string $handle */

        wp_register_style(
            $handle,
            $asset->url(),
            $asset->dependencies(),
            $asset->version(),
            $asset->media(),
        );

        $inlineStyles = $asset->inlineStyles();
        if (null !== $inlineStyles) {
            wp_add_inline_style($handle, implode("\n", $inlineStyles));
        }

        $cssVars = $asset->cssVars();
        if (count($cssVars) > 0) {
            wp_add_inline_style($handle, $asset->cssVarsAsString());
        }

        $data = $asset->data();
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->wpStyles->add_data($handle, $key, $value);
            }
        }

        return true;
    }

    public function filterHook(): string
    {
        return 'style_loader_tag';
    }
}
