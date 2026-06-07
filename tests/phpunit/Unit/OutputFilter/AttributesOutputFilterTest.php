<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\OutputFilter;

use SymPress\Assets\OutputFilter\AttributesOutputFilter;
use SymPress\Assets\Script;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

final class AttributesOutputFilterTest extends AbstractTestCase
{
    public function testAddsAttributesToExternalScriptTag(): void
    {
        $asset = (new Script('asset', 'https://example.test/app.js'))
            ->withAttributes([
                'defer' => true,
                'data-module' => 'booking',
                'src' => 'ignored',
                'bad attr' => 'ignored',
            ]);

        $html = '<script id="asset-js" src="https://example.test/app.js"></script>';
        $result = (new AttributesOutputFilter())($html, $asset);

        self::assertStringContainsString('defer="defer"', $result);
        self::assertStringContainsString('data-module="booking"', $result);
        self::assertSame(1, substr_count($result, 'src='));
        self::assertStringNotContainsString('bad attr', $result);
    }

    public function testAddsAttributesToExternalStyleTag(): void
    {
        $asset = (new Style('asset', 'https://example.test/app.css'))
            ->withAttributes([
                'media' => 'print',
                'data-critical' => true,
            ]);

        $html = '<link rel="stylesheet" href="https://example.test/app.css">';
        $result = (new AttributesOutputFilter())($html, $asset);

        self::assertStringContainsString('media="print"', $result);
        self::assertStringContainsString('data-critical="data-critical"', $result);
    }

    public function testKeepsInlineScriptTagsUntouched(): void
    {
        $asset = (new Script('asset', 'https://example.test/app.js'))
            ->withAttributes(['defer' => true]);

        $html = '<script>console.log("inline")</script>';

        self::assertSame($html, (new AttributesOutputFilter())($html, $asset));
    }
}
