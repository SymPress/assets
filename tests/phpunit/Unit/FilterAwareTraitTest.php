<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit;

use SymPress\Assets\BaseAsset;
use SymPress\Assets\FilterAwareAsset;
use SymPress\Assets\FilterAwareTrait;
use SymPress\Assets\OutputFilter\AttributesOutputFilter;
use SymPress\Assets\OutputFilter\InlineAssetOutputFilter;

class FilterAwareTraitTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function testFilters(): void
    {
        $asset = $this->createFilterAwareAsset();

        static::assertEmpty($asset->filters());

        $expectedFilter1 = static function (): string {
            return 'foo';
        };

        $expectedFilter2 = static function (): string {
            return 'bar';
        };

        $asset->withFilters($expectedFilter1, $expectedFilter2);

        static::assertEquals([$expectedFilter1, $expectedFilter2], $asset->filters());
    }

    /**
     * @test
     */
    public function testUseInlineFilter(): void
    {
        $asset = $this->createFilterAwareAsset();
        $asset->useInlineFilter();

        $filters = $asset->filters();

        static::assertSame(InlineAssetOutputFilter::class, $filters[0]);
    }

    /**
     * @test
     */
    public function testAttributes(): void
    {
        $expectedAttributes = ['foo' => 'bar'];

        $asset = $this->createFilterAwareAsset();
        $asset->withAttributes($expectedAttributes);

        static::assertSame($expectedAttributes, $asset->attributes());

        $filters = $asset->filters();
        static::assertSame(AttributesOutputFilter::class, $filters[0]);
    }

    /**
     * @test
     */
    public function testAttributesAddedMultipleTimes(): void
    {
        $expectedValue = 'baz';
        $expectedAttributes1 = [
            'foo' => 'foo',
        ];
        $expectedAttributes2 = [
            'bar' => 'bar',
            // overwrite "foo"
            'foo' => $expectedValue,
        ];

        $asset = $this->createFilterAwareAsset();
        $asset->withAttributes($expectedAttributes1);
        $asset->withAttributes($expectedAttributes2);

        $attributes = $asset->attributes();
        $filters = $asset->filters();

        static::assertArrayHasKey('foo', $attributes);
        static::assertArrayHasKey('bar', $attributes);
        static::assertSame($expectedValue, $attributes['foo']);
        static::assertSame([AttributesOutputFilter::class], $filters);
    }

    public function testWithoutAttributes(): void
    {
        $asset = $this->createFilterAwareAsset();
        $asset->withAttributes([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $asset->withoutAttributes('foo');

        static::assertSame(['baz' => 'qux'], $asset->attributes());
    }

    private function createFilterAwareAsset(string $handle = '', string $src = ''): FilterAwareAsset
    {
        return new class($handle, $src) extends BaseAsset implements FilterAwareAsset {
            use FilterAwareTrait;

            protected function defaultHandler(): string
            {
                return __CLASS__;
            }
        };
    }
}
