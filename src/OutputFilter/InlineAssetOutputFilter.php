<?php

declare(strict_types=1);

namespace SymPress\Assets\OutputFilter;

use SymPress\Assets\FilterAwareAsset;
use SymPress\Assets\Script;
use SymPress\Assets\Security\InlineAssetPolicy;
use SymPress\Assets\Style;

final class InlineAssetOutputFilter implements AssetOutputFilter
{
    private readonly InlineAssetPolicy $policy;

    /**
     * @var array<string, string|null>
     */
    private array $contentsByFile = [];

    public function __construct(?InlineAssetPolicy $policy = null)
    {
        $this->policy = $policy ?? InlineAssetPolicy::fromWordPressEnvironment();
    }

    /**
     * @psalm-suppress PossiblyNullArgument
     */
    public function __invoke(string $html, FilterAwareAsset $asset): string
    {
        $filePath = $asset->filePath();

        if ('' === $filePath) {
            return $html;
        }

        if (!$this->policy->allows($asset, $filePath)) {
            return $html;
        }

        $content = $this->fileContent($filePath);
        if (null === $content) {
            return $html;
        }

        if ($asset instanceof Script) {
            return sprintf(
                '<script%1$s>%2$s</script>',
                $this->attributes($asset, ['src', 'href', 'rel', 'integrity', 'defer', 'async', 'as']),
                $this->safeRawText($content, 'script'),
            );
        }

        if ($asset instanceof Style) {
            return sprintf(
                '<style%1$s>%2$s</style>',
                $this->attributes($asset, ['src', 'href', 'rel', 'integrity', 'defer', 'async', 'as']),
                $this->safeRawText($content, 'style'),
            );
        }

        return $html;
    }

    private function fileContent(string $filePath): ?string
    {
        $modifiedAt = @filemtime($filePath);
        $cacheKey = sprintf('%s:%s', $filePath, false === $modifiedAt ? 'unknown' : (string) $modifiedAt);
        if (array_key_exists($cacheKey, $this->contentsByFile)) {
            return $this->contentsByFile[$cacheKey];
        }

        $content = file_get_contents($filePath);
        $this->contentsByFile[$cacheKey] = false === $content ? null : $content;

        return $this->contentsByFile[$cacheKey];
    }

    /**
     * @param list<string> $excludedAttributes
     */
    private function attributes(FilterAwareAsset $asset, array $excludedAttributes): string
    {
        return HtmlAttributes::render(
            [
                ...$asset->attributes(),
                'data-version' => (string) $asset->version(),
                'data-id' => $asset->handle(),
            ],
            $excludedAttributes,
        );
    }

    private function safeRawText(string $content, string $tagName): string
    {
        return preg_replace(
            sprintf('/<\\/%s/i', preg_quote($tagName, '/')),
            '<\/' . $tagName,
            $content,
        ) ?? $content;
    }
}
