<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Asset;

use SymPress\Assets\Asset;
use SymPress\Assets\Handler\StyleHandler;
use SymPress\Assets\OutputFilter\AsyncStyleOutputFilter;
use SymPress\Assets\Style;
use SymPress\Assets\StyleLoadingMode;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class StyleTest extends AbstractTestCase
{
    /** @test */
    public function testBasic(): void
    {
        $testee = new Style('foo', 'foo.css');

        static::assertInstanceOf(Asset::class, $testee);
        static::assertSame('all', $testee->media());
        static::assertSame(StyleLoadingMode::BLOCKING, $testee->loadingMode());
        static::assertSame(Asset::FRONTEND | Asset::ACTIVATE, $testee->location());
        static::assertSame(StyleHandler::class, $testee->handler());
    }

    /** @test */
    public function testMedia(): void
    {
        $expected = 'bar';

        $testee = new Style('foo', 'foo.css');

        static::assertSame('all', $testee->media());

        $testee->forMedia($expected);
        static::assertSame($expected, $testee->media());
    }

    /** @test */
    public function testInlineStyles(): void
    {
        $expected = 'bar';

        $testee = new Style('foo', 'foo.css');

        static::assertNull($testee->inlineStyles());

        $testee->withInlineStyles($expected);
        static::assertSame([$expected], $testee->inlineStyles());
    }

    public function testInlineStylesEscapeClosingStyleTags(): void
    {
        $testee = new Style('foo', 'foo.css');

        $testee->withInlineStyles('body::after{content:"</style><script>alert(1)</script>";}');

        static::assertSame(
            ['body::after{content:"<\/style><script>alert(1)</script>";}'],
            $testee->inlineStyles(),
        );
    }

    /** @test */
    public function testUseAsyncFilter(): void
    {
        $testee = new Style('handle', 'foo.css');
        static::assertEmpty($testee->filters());

        $testee->useAsyncFilter();
        static::assertSame([AsyncStyleOutputFilter::class], $testee->filters());
        static::assertSame(StyleLoadingMode::PRELOAD, $testee->loadingMode());
    }

    public function testStyleLoadingModeCanReturnToBlocking(): void
    {
        $testee = new Style('handle', 'foo.css');

        $testee->preload();
        static::assertSame([AsyncStyleOutputFilter::class], $testee->filters());

        $testee->blocking();
        static::assertSame(StyleLoadingMode::BLOCKING, $testee->loadingMode());
        static::assertSame([], $testee->filters());
    }

    /** @dataProvider provideCssVars */
    public function testWithCssVars(string $element, array $cssVars, array $expected): void
    {
        $testee = new Style('handle', 'foo.css');
        $testee->withCssVars($element, $cssVars);

        static::assertSame($expected, $testee->cssVars());
    }

    public static function provideCssVars(): \Generator
    {
        yield 'non-prefixed vars' => [
            '.some-element',
            ['white' => '#fff', 'black' => '#000'],
            ['.some-element' => ['--white' => '#fff', '--black' => '#000']],
        ];

        yield 'prefixed vars' => [
            ':root',
            ['--white' => '#fff', '--black' => '#000'],
            [':root' => ['--white' => '#fff', '--black' => '#000']],
        ];

        yield 'prefixed and non-prefixed vars' => [
            'div',
            ['white' => '#fff', '--black' => '#000'],
            ['div' => ['--white' => '#fff', '--black' => '#000']],
        ];
    }

    /** @test */
    public function testCssVarsAsString(): void
    {
        $element = ':root';
        $vars = ['white' => '#fff', 'black' => '#000'];

        $expected = ':root{--white:#fff;--black:#000;}';

        $testee = new Style('handle', 'foo.css');
        $testee->withCssVars($element, $vars);

        static::assertSame($expected, $testee->cssVarsAsString());
    }

    public function testCssVarsRejectInvalidCustomPropertyNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Style('handle', 'foo.css'))->withCssVars(':root', ['bad;name' => '#fff']);
    }

    public function testCssVarsRejectUnsafeSelectors(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Style('handle', 'foo.css'))->withCssVars('</style><script>', ['white' => '#fff']);
    }

    public function testCssVarsEscapeClosingStyleTags(): void
    {
        $testee = new Style('handle', 'foo.css');

        $testee->withCssVars(':root', ['text' => '</style><script>alert(1)</script>']);

        static::assertSame(
            ':root{--text:<\/style><script>alert(1)</script>;}',
            $testee->cssVarsAsString(),
        );
    }

    /** @test */
    public function testMultipleCssVarsAsString(): void
    {
        $element1 = ':root';
        $vars1 = ['white' => '#fff', 'black' => '#000'];

        $element2 = 'div';
        $vars2 = ['--grey' => '#ddd'];

        $expected = ':root{--white:#fff;--black:#000;}div{--grey:#ddd;}';

        $testee = new Style('handle', 'foo.css');
        $testee->withCssVars($element1, $vars1);
        $testee->withCssVars($element2, $vars2);

        static::assertSame($expected, $testee->cssVarsAsString());
    }
}
