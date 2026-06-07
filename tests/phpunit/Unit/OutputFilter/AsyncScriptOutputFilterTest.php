<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\OutputFilter;

use SymPress\Assets\OutputFilter\AssetOutputFilter;
use SymPress\Assets\OutputFilter\AsyncScriptOutputFilter;
use SymPress\Assets\Script;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class AsyncScriptOutputFilterTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function testBasic()
    {
        static::assertInstanceOf(AssetOutputFilter::class, new AsyncScriptOutputFilter());
    }

    /**
     * @test
     */
    public function testRender()
    {
        $testee = new AsyncScriptOutputFilter();

        $asset = new Script('foo', 'foo.js');

        $input = '<script src="foo.js"></script>';
        $result = $testee($input, $asset);

        static::assertStringContainsString('async="async"', $result);
        static::assertSame(1, substr_count($result, 'async='));
    }
}
