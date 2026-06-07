<?php

declare(strict_types=1);

namespace SymPress\Assets\OutputFilter;

use SymPress\Assets\FilterAwareAsset;

class AsyncStyleOutputFilter implements AssetOutputFilter
{
    private const string LOAD_STYLESHEET_ON_LOAD = "this.onload=null;this.rel='stylesheet'";

    public function __invoke(string $html, FilterAwareAsset $asset): string
    {
        $attributes = [
            ...$this->linkAttributes($html, $asset),
            ...$asset->attributes(),
            'rel' => 'preload',
            'as' => 'style',
            'onload' => self::LOAD_STYLESHEET_ON_LOAD,
        ];

        unset($attributes['id']);

        return sprintf(
            '<link%1$s><noscript>%2$s</noscript>',
            HtmlAttributes::render($attributes),
            $html,
        );
    }

    /**
     * @return array<string, string|true>
     */
    private function linkAttributes(string $html, FilterAwareAsset $asset): array
    {
        $attributes = $this->parseAttributes($html);
        $href = $attributes['href'] ?? $this->assetUrl($asset);

        return [
            ...$attributes,
            'href' => esc_url((string) $href),
        ];
    }

    private function assetUrl(FilterAwareAsset $asset): string
    {
        $url = $asset->url();
        $version = $asset->version();

        return $version
            ? add_query_arg('ver', $version, $url)
            : $url;
    }

    /**
     * @return array<string, string|true>
     */
    private function parseAttributes(string $html): array
    {
        if (1 !== preg_match('/<link\b(?<attributes>[^>]*)>/i', $html, $tag)) {
            return [];
        }

        $attributes = [];

        if (preg_match_all(
            '/\s(?<name>[A-Za-z_:][A-Za-z0-9:_.-]*)\s*=\s*(["\'])(?<value>.*?)\2/s',
            (string) $tag['attributes'],
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $attributes[(string) $match['name']] = html_entity_decode(
                    (string) $match['value'],
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8',
                );
            }
        }

        if (preg_match_all(
            '/\s(?<name>[A-Za-z_:][A-Za-z0-9:_.-]*)(?=\s|$)/',
            preg_replace('/\s[A-Za-z_:][A-Za-z0-9:_.-]*\s*=\s*(["\']).*?\1/s', '', (string) $tag['attributes']) ?? '',
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $attributes[(string) $match['name']] ??= true;
            }
        }

        return $attributes;
    }
}
