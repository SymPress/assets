<?php

declare(strict_types=1);

namespace SymPress\Assets\Loader;

use SymPress\Assets\Asset;

interface LoaderInterface
{
    /** @return array<Asset> */
    #[\NoDiscard]
    public function load(mixed $resource): array;
}
