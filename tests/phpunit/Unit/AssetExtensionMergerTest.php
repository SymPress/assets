<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SymPress\Assets\Asset;
use SymPress\Assets\AssetExtensionMerger;

final class AssetExtensionMergerTest extends AbstractTestCase
{
    #[Test]
    public function itReplacesScalarValuesInsteadOfRecursivelyMergingThem(): void
    {
        $merged = (new AssetExtensionMerger())->merge(
            [
                'enqueue'  => true,
                'location' => Asset::FRONTEND,
                'version'  => '1',
            ],
            [
                'enqueue'  => false,
                'location' => Asset::BACKEND,
                'version'  => '2',
            ],
        );

        self::assertSame(
            [
                'enqueue'  => false,
                'location' => Asset::BACKEND,
                'version'  => '2',
            ],
            $merged,
        );
    }

    #[Test]
    public function itAppendsDependenciesAndInlineScripts(): void
    {
        $merged = (new AssetExtensionMerger())->merge(
            [
                'dependencies' => ['core'],
                'inline'       => [
                    'before' => ['window.beforeCore = true;'],
                    'after'  => ['window.afterCore = true;'],
                ],
            ],
            [
                'dependencies' => 'feature',
                'inline'       => [
                    'before' => ['window.beforeFeature = true;'],
                    'after'  => ['window.afterFeature = true;'],
                ],
            ],
        );

        self::assertSame(['core', 'feature'], $merged['dependencies']);
        self::assertSame(
            ['window.beforeCore = true;', 'window.beforeFeature = true;'],
            $merged['inline']['before'],
        );
        self::assertSame(
            ['window.afterCore = true;', 'window.afterFeature = true;'],
            $merged['inline']['after'],
        );
    }

    #[Test]
    public function itReplacesMapKeysWithIncomingValues(): void
    {
        $merged = (new AssetExtensionMerger())->merge(
            [
                'attributes' => ['defer' => true, 'type' => 'text/javascript'],
                'localize'   => ['config' => ['debug' => false]],
            ],
            [
                'attributes' => ['type' => 'module'],
                'localize'   => ['config' => ['debug' => true]],
            ],
        );

        self::assertSame(['defer' => true, 'type' => 'module'], $merged['attributes']);
        self::assertSame(['config' => ['debug' => true]], $merged['localize']);
    }
}
