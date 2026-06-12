<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\OutputFilter\AssetOutputFilter;

interface FilterAwareAsset extends Asset
{
    /**
     * A list of assigned output filters to change the rendered tag.
     *
     * @return array<callable>|array<AssetOutputFilter>|array<class-string<AssetOutputFilter>>
     */
    public function filters(): array;

    /** @param callable|class-string<AssetOutputFilter> ...$filters */
    public function withFilters(...$filters): static;

    /** @return array<string, mixed> */
    public function attributes(): array;

    /** @param array<string, mixed> $attributes */
    public function withAttributes(array $attributes): static;

    public function withoutAttributes(string ...$attributes): static;
}
