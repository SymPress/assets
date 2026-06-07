<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

final class FunctionsTest extends AbstractTestCase
{
    #[Test]
    public function itAddsAssetSuffixBeforeTheFinalExtension(): void
    {
        static::assertSame('app.bundle.min.js', \SymPress\Assets\withAssetSuffix('app.bundle.js'));
    }

    #[Test]
    public function itKeepsFilesWithoutExtensionUnchanged(): void
    {
        static::assertSame('README', \SymPress\Assets\withAssetSuffix('README'));
    }
}
