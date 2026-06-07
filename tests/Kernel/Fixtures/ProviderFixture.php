<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Fixtures;

use SymPress\Assets\AssetProviderInterface;

final class ProviderFixture implements AssetProviderInterface
{
    public function assets(): iterable
    {
        return [];
    }
}
