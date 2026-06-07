<?php

declare(strict_types=1);

namespace SymPress\Assets\Handler;

use SymPress\Assets\Asset;
use SymPress\Assets\OutputFilter\AsyncScriptOutputFilter;
use SymPress\Assets\OutputFilter\AttributesOutputFilter;
use SymPress\Assets\OutputFilter\DeferScriptOutputFilter;
use SymPress\Assets\OutputFilter\InlineAssetOutputFilter;
use SymPress\Assets\Script;

class ScriptHandler implements AssetHandler, OutputFilterAwareAssetHandler
{
    use OutputFilterAwareAssetHandlerTrait;

    protected \WP_Scripts $wpScripts;

    /**
     * ScriptHandler constructor.
     *
     * @param array<string, callable> $outputFilters
     */
    public function __construct(\WP_Scripts $wpScripts, array $outputFilters = [])
    {
        /** @phpstan-ignore-next-line classConstant.deprecatedClass, new.deprecatedClass */
        $this->withOutputFilter(AsyncScriptOutputFilter::class, new AsyncScriptOutputFilter());
        /** @phpstan-ignore-next-line classConstant.deprecatedClass, new.deprecatedClass */
        $this->withOutputFilter(DeferScriptOutputFilter::class, new DeferScriptOutputFilter());
        $this->withOutputFilter(InlineAssetOutputFilter::class, new InlineAssetOutputFilter());
        $this->withOutputFilter(AttributesOutputFilter::class, new AttributesOutputFilter());

        $this->wpScripts = $wpScripts;
        foreach ($outputFilters as $name => $callable) {
            $this->withOutputFilter($name, $callable);
        }
    }

    public function enqueue(Asset $asset): bool
    {
        if (!$asset instanceof Script) {
            return false;
        }

        $handle = $asset->handle();
        if ('' === $handle) {
            return false;
        }
        /* @var non-empty-string $handle */

        if (!$this->register($asset)) {
            return false;
        }

        if ($asset->enqueue()) {
            wp_enqueue_script($handle);

            return true;
        }

        return false;
    }

    public function register(Asset $asset): bool
    {
        if (!$asset instanceof Script) {
            return false;
        }

        $handle = $asset->handle();
        if ('' === $handle) {
            return false;
        }
        /* @var non-empty-string $handle */

        $strategy = $asset->loadingStrategy();
        $args = ['in_footer' => $asset->inFooter()];
        if (null !== $strategy) {
            $args['strategy'] = $strategy;
        }

        wp_register_script(
            $handle,
            '' !== $asset->url() ? $asset->url() : false,
            array_values(array_filter($asset->dependencies(), static fn (string $dependency): bool => '' !== $dependency)),
            $asset->version(),
            $args,
        );

        $localizations = $asset->localize();
        if (count($localizations) > 0) {
            foreach ($localizations as $name => $args) {
                if (!is_array($args)) {
                    continue;
                }

                $localizeData = [];
                foreach ($args as $key => $value) {
                    if (is_string($key)) {
                        $localizeData[$key] = $value;
                    }
                }

                /*
                 * Actually it is possible to use $args as scalar value for
                 * \WP_Scripts::localize() - but it will produce a _doing_it_wrong().
                 *
                 * @psalm-suppress MixedArgument
                 */
                wp_localize_script($handle, $name, $localizeData);
            }
        }

        foreach ($asset->inlineScripts() as $location => $data) {
            if (count($data) > 0) {
                wp_add_inline_script($handle, implode("\n", $data), $location);
            }
        }

        $translation = $asset->translation();
        if ('' !== $translation['domain']) {
            /*
             * The $path is allowed to be "null"- or a "string"-value.
             * @psalm-suppress PossiblyNullArgument
             */
            wp_set_script_translations($handle, $translation['domain'], $translation['path'] ?? '');
        }

        $data = $asset->data();
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->wpScripts->add_data($handle, $key, $value);
            }
        }

        return true;
    }

    public function filterHook(): string
    {
        return 'script_loader_tag';
    }
}
