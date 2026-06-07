<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\OutputFilter;

use Brain\Monkey;
use SymPress\Assets\FilterAwareAsset;
use SymPress\Assets\OutputFilter\AssetOutputFilter;
use SymPress\Assets\OutputFilter\AsyncStyleOutputFilter;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class AsyncStyleOutputFilterTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function testBasic()
    {
        static::assertInstanceOf(AssetOutputFilter::class, new AsyncStyleOutputFilter());
    }

    /**
     * @test
     */
    public function testRender()
    {
        $testee = new AsyncStyleOutputFilter();

        $expectedUrl = 'https://cdn.example.test/app.css';
        $input = '<link rel="stylesheet" id="app-css" href="' . $expectedUrl
            . '" media="screen" integrity="sha384-test" crossorigin="anonymous" fetchpriority="high">';

        Monkey\Functions\when('esc_url')->returnArg();
        Monkey\Functions\when('esc_attr')->returnArg();

        $stub = \Mockery::mock(FilterAwareAsset::class);
        $stub->expects('attributes')->once()->andReturn(['data-owner' => 'theme']);

        $output = $testee($input, $stub);
        $preloadTag = strstr($output, '<noscript>', true);

        static::assertStringContainsString('rel="preload"', $output);
        static::assertStringContainsString('href="' . $expectedUrl . '"', $output);
        static::assertStringContainsString('as="style"', $output);
        static::assertStringContainsString('media="screen"', $output);
        static::assertStringContainsString('integrity="sha384-test"', $output);
        static::assertStringContainsString('crossorigin="anonymous"', $output);
        static::assertStringContainsString('fetchpriority="high"', $output);
        static::assertStringContainsString('data-owner="theme"', $output);
        static::assertIsString($preloadTag);
        static::assertStringNotContainsString('id="app-css"', $preloadTag);
        static::assertStringContainsString("<noscript>{$input}</noscript>", $output);
        static::assertStringNotContainsString('<script>', $output);
    }
}
