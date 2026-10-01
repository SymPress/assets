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

    public function __construct(?InlineAssetPolicy $policy = null)
    {
        $this->policy = $policy ?? InlineAssetPolicy::fromWordPressEnvironment();
    }

    /** @psalm-suppress PossiblyNullArgument */
    public function __invoke(string $html, FilterAwareAsset $asset): string
    {
        $filePath = $asset->filePath();

        if ($filePath === '') {
            return $html;
        }

        if (!$this->policy->allows($asset, $filePath)) {
            return $html;
        }

        $content = $this->fileContent($filePath);
        if ($content === null || !$this->policy->allowsContent($content)) {
            return $html;
        }

        if ($asset instanceof Script) {
            // Raw programs cannot be rewritten safely in every string, regexp or tagged-template parser state.
            if (preg_match('~</script|<!--|<script|[\x{2028}\x{2029}]~iu', $content) !== 0) {
                return $html;
            }
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
        return \SymPress\Assets\IO\RequestFiles::shared()->contents($filePath);
    }

    /** @param list<string> $excludedAttributes */
    private function attributes(FilterAwareAsset $asset, array $excludedAttributes): string
    {
        return HtmlAttributes::render(
            [
                ...$asset->attributes(),
                'data-version' => (string) $asset->version(),
                'data-id'      => $asset->handle(),
            ],
            $excludedAttributes,
        );
    }

    private function safeRawText(string $content, string $tagName): string
    {
        return preg_replace_callback(
            sprintf('/<\\/%s/i', preg_quote($tagName, '/')),
            static fn (array $match): string => '<\\/' . substr($match[0], 2),
            $content,
        ) ?? $content;
    }
}
