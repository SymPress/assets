<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\OutputFilter\AssetOutputFilter;
use SymPress\Assets\OutputFilter\AttributesOutputFilter;
use SymPress\Assets\OutputFilter\InlineAssetOutputFilter;

trait FilterAwareTrait
{
    /** @var array<callable>|array<AssetOutputFilter>|array<class-string<AssetOutputFilter>> */
    protected array $filters = [];

    /**
     * Additional attributes to "link"- or "script"-tag.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /** @return array<callable>|array<AssetOutputFilter>|array<class-string<AssetOutputFilter>> */
    public function filters(): array
    {
        return $this->filters;
    }

    /** @param callable|class-string<AssetOutputFilter> ...$filters */
    public function withFilters(...$filters): static
    {
        foreach ($filters as $filter) {
            if (is_string($filter) && in_array($filter, $this->filters, true)) {
                continue;
            }

            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * Shortcut to use the InlineFilter.
     */
    public function useInlineFilter(): static
    {
        $this->withFilters(InlineAssetOutputFilter::class);

        return $this;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Allows you to set additional attributes to your "link"- or "script"-tag.
     * Existing attributes like "src" or "id" will not be overwrite.
     *
     * @param array<string, mixed> $attributes
     */
    public function withAttributes(array $attributes): static
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        $this->withFilters(AttributesOutputFilter::class);

        return $this;
    }

    public function withoutAttributes(string ...$attributes): static
    {
        foreach ($attributes as $attribute) {
            unset($this->attributes[$attribute]);
        }

        return $this;
    }
}
