<?php

declare(strict_types=1);

namespace SymPress\Assets\OutputFilter;

use SymPress\Assets\FilterAwareAsset;

class AsyncStyleOutputFilter implements AssetOutputFilter
{
    public const string NONCE_FILTER = 'sympress_assets_csp_nonce';

    public function __invoke(string $html, FilterAwareAsset $asset): string
    {
        $nonce = function_exists('apply_filters') ? apply_filters(self::NONCE_FILTER, null, $asset) : null;
        if (!is_string($nonce) || $nonce === '' || !class_exists(\WP_HTML_Tag_Processor::class)) {
            return $html;
        }
        $tags = new \WP_HTML_Tag_Processor($html);
        if (!$tags->next_tag(['tag_name' => 'LINK'])) {
            return $html;
        }
        $id = 'sympress-async-' . bin2hex(random_bytes(8));
        HtmlAttributes::applyToTag($tags, $asset->attributes(), ['id', 'rel', 'as', 'onload']);
        $tags->set_attribute('id', $id);
        $tags->set_attribute('rel', 'preload');
        $tags->set_attribute('as', 'style');
        $tags->remove_attribute('onload');
        $identifier = json_encode($id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
        $script = '(function(){const link=document.getElementById(' . $identifier . ');'
            . 'if(!link)return;const activate=()=>{link.rel="stylesheet";};'
            . 'link.addEventListener("load",activate,{once:true});'
            . 'if(link.sheet||(window.performance&&performance.getEntriesByName(link.href).some(e=>e.responseEnd>0)))activate();})();';
        // A completed Resource Timing entry handles a preload event that fired before this script.
        return $tags->get_updated_html() . '<script' . HtmlAttributes::render(['nonce' => $nonce]) . '>' . $script . '</script><noscript>' . $html . '</noscript>';
    }
}
