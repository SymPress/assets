<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Handler\StyleHandler;
use SymPress\Assets\OutputFilter\AsyncStyleOutputFilter;
use SymPress\Assets\Security\InlineStyleContent;

class Style extends BaseAsset implements Asset, DataAwareAsset, FilterAwareAsset
{
    use DataAwareTrait;
    use FilterAwareTrait;

    /**
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/link#attr-media
     */
    protected string $media = 'all';

    /**
     * @var StyleLoadingMode::BLOCKING|StyleLoadingMode::PRELOAD
     */
    protected string $loadingMode = StyleLoadingMode::BLOCKING;

    /**
     * @var string[]|null
     */
    protected ?array $inlineStyles = null;

    /**
     * @var array<string, array<string, string>>
     */
    protected array $cssVars = [];

    public function media(): string
    {
        return $this->media;
    }

    public function forMedia(string $media): static
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return StyleLoadingMode::BLOCKING|StyleLoadingMode::PRELOAD
     */
    public function loadingMode(): string
    {
        return $this->loadingMode;
    }

    public function withLoadingMode(string $loadingMode): static
    {
        $this->loadingMode = StyleLoadingMode::normalize($loadingMode);

        if (StyleLoadingMode::PRELOAD === $this->loadingMode) {
            $this->withFilters(AsyncStyleOutputFilter::class);

            return $this;
        }

        $this->filters = array_values(array_filter(
            $this->filters,
            static fn (mixed $filter): bool => AsyncStyleOutputFilter::class !== $filter,
        ));

        return $this;
    }

    public function blocking(): static
    {
        return $this->withLoadingMode(StyleLoadingMode::BLOCKING);
    }

    public function preload(): static
    {
        return $this->withLoadingMode(StyleLoadingMode::PRELOAD);
    }

    /**
     * @return string[]|null
     */
    public function inlineStyles(): ?array
    {
        return $this->inlineStyles;
    }

    /**
     * @see https://codex.wordpress.org/Function_Reference/wp_add_inline_style
     */
    public function withInlineStyles(string $inline): static
    {
        if (!$this->inlineStyles) {
            $this->inlineStyles = [];
        }

        $this->inlineStyles[] = InlineStyleContent::safeRawText($inline);

        return $this;
    }

    /**
     * Add custom CSS properties (CSS vars) to an element.
     * Those custom CSS vars will be enqueued with inline style
     * to your handle. Variables will be automatically prefixed
     * with '--'.
     *
     * @param array<string, string> $vars
     *
     * @return $this
     *
     * @example Style::withCssVars('.some-element', ['--white' => '#fff']);
     * @example Style::withCssVars('.some-element', ['white' => '#fff']);
     */
    public function withCssVars(string $element, array $vars): static
    {
        $element = InlineStyleContent::safeSelector($element);

        if (!isset($this->cssVars[$element])) {
            $this->cssVars[$element] = [];
        }

        foreach ($vars as $key => $value) {
            $this->cssVars[$element][InlineStyleContent::customPropertyName((string) $key)] =
                InlineStyleContent::customPropertyValue($value);
        }

        return $this;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function cssVars(): array
    {
        return $this->cssVars;
    }

    public function cssVarsAsString(): string
    {
        $return = '';
        foreach ($this->cssVars() as $element => $vars) {
            $values = '';
            foreach ($vars as $key => $value) {
                $values .= sprintf('%1$s:%2$s;', $key, $value);
            }
            $return .= sprintf('%1$s{%2$s}', $element, $values);
        }

        return InlineStyleContent::safeRawText($return);
    }

    /**
     * Wrapper function to set AsyncStyleOutputFilter as filter.
     */
    public function useAsyncFilter(): static
    {
        return $this->preload();
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultHandler(): string
    {
        return StyleHandler::class;
    }
}
