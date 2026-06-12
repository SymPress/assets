<?php

declare(strict_types=1);

namespace SymPress\Assets\OutputFilter;

use SymPress\Assets\FilterAwareAsset;

class AttributesOutputFilter implements AssetOutputFilter
{
    public function __invoke(string $html, FilterAwareAsset $asset): string
    {
        $attributes = $asset->attributes();

        if (!class_exists(\WP_HTML_Tag_Processor::class) || count($attributes) === 0) {
            return $html;
        }

        $tags = $this->externalAssetTag($html);

        if (!$tags instanceof \WP_HTML_Tag_Processor) {
            return $html;
        }

        HtmlAttributes::applyToTag($tags, $attributes);

        return $tags->get_updated_html();
    }

    private function externalAssetTag(string $html): ?\WP_HTML_Tag_Processor
    {
        foreach (
            [
                ['tag_name' => 'script', 'source_attribute' => 'src'],
                ['tag_name' => 'link', 'source_attribute' => 'href'],
            ] as $query
        ) {
            $tags = new \WP_HTML_Tag_Processor($html);

            if (
                $tags->next_tag(['tag_name' => $query['tag_name']])
                && (string) $tags->get_attribute($query['source_attribute'])
            ) {
                return $tags;
            }
        }

        return null;
    }
}
