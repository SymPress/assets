<?php

declare(strict_types=1);

namespace SymPress\Assets;

interface AssetProviderInterface
{
    /** @return iterable<Asset> */
    public function assets(): iterable;
}
