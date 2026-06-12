<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Loader;

use SymPress\Assets\Asset;
use SymPress\Assets\Loader\ArrayLoader;
use SymPress\Assets\Script;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class ArrayLoaderTest extends AbstractTestCase
{
    /** @test */
    public function testLoad(): void
    {
        $input = [
            [
                'handle'   => 'foo',
                'url'      => 'foo.css',
                'location' => Asset::FRONTEND,
                'type'     => Style::class,
            ],
            [
                'handle'   => 'bar',
                'url'      => 'bar.js',
                'location' => Asset::FRONTEND,
                'type'     => Script::class,
            ],
        ];

        $assets = (new ArrayLoader())->load($input);
        static::assertCount(2, $assets);
        static::assertInstanceOf(Style::class, $assets[0]);
        static::assertInstanceOf(Script::class, $assets[1]);
    }

    /** @test */
    public function testLoadDisabledAutodiscoverVersion(): void
    {
        $input = [
            [
                'handle'   => 'foo',
                'url'      => 'foo.css',
                'location' => Asset::FRONTEND,
                'type'     => Style::class,
            ],
        ];

        $assets = (new ArrayLoader())
            ->disableAutodiscoverVersion()
            ->load($input);

        static::assertCount(1, $assets);

        /** @var Asset $asset */
        $asset = $assets[0];
        static::assertNull($asset->version());
    }

    /** @test */
    public function testLoadWithAttributes(): void
    {
        $expectedAttributes = [
            'data-id' => 'foo',
        ];

        $input = [
            [
                'handle'     => 'foo',
                'url'        => 'foo.css',
                'location'   => Asset::FRONTEND,
                'type'       => Style::class,
                'attributes' => $expectedAttributes,
            ],
        ];

        $assets = (new ArrayLoader())
            ->load($input);

        $style = $assets[0];
        static::assertSame($expectedAttributes, $style->attributes());
    }
}
