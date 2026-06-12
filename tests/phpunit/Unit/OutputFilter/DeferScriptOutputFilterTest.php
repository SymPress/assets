<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\OutputFilter;

use SymPress\Assets\OutputFilter\AssetOutputFilter;
use SymPress\Assets\OutputFilter\DeferScriptOutputFilter;
use SymPress\Assets\Script;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class DeferScriptOutputFilterTest extends AbstractTestCase
{
    /** @test */
    public function testBasic(): void
    {
        static::assertInstanceOf(AssetOutputFilter::class, new DeferScriptOutputFilter());
    }

    /** @test */
    public function testRender(): void
    {
        $testee = new DeferScriptOutputFilter();

        $asset = new Script('foo', 'foo.js');

        $input = '<script src="foo.js"></script>';
        $result = $testee($input, $asset);

        static::assertStringContainsString('defer="defer"', $result);
        static::assertSame(1, substr_count($result, 'defer='));
    }
}
