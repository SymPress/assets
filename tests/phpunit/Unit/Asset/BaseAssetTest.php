<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Asset;

use Brain\Monkey\Functions;
use SymPress\Assets\Asset;
use SymPress\Assets\BaseAsset;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use org\bovigo\vfs\vfsStream;

class BaseAssetTest extends AbstractTestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    public function setUp(): void
    {
        $this->root = vfsStream::setup('tmp');
        parent::setUp();
    }

    /**
     * @test
     */
    public function testBasic(): void
    {
        $expectedHandle = bin2hex(random_bytes(4));
        $expectedUrl = "{$expectedHandle}.js";

        $asset = new class($expectedHandle, $expectedUrl) extends BaseAsset {
            protected function defaultHandler(): string
            {
                return '';
            }
        };

        static::assertSame($expectedUrl, $asset->url());
        static::assertSame($expectedHandle, $asset->handle());
        static::assertTrue($asset->enqueue());
        static::assertSame(Asset::FRONTEND | Asset::ACTIVATE, $asset->location());
    }

    /**
     * @test
     */
    public function testVersion(): void
    {
        $asset = new class('', '') extends BaseAsset {
            protected function defaultHandler(): string
            {
                return '';
            }
        };

        $fileStub = vfsStream::newFile('style.css')
            ->withContent('body { background: white; }')
            ->at($this->root);

        $asset->withFilePath($fileStub->url());

        // If automatic discovering of version is disabled and no version is set --> ''
        $asset->disableAutodiscoverVersion();
        static::assertSame(null, $asset->version());

        $asset->enableAutodiscoverVersion();
        $version = $asset->version();

        static::assertTrue($version && is_numeric($version));

        // if we set a version, the version should be returned.
        $asset->withVersion('foo');
        static::assertEquals('foo', $asset->version());
    }

    /**
     * @test
     */
    public function testNoVersion(): void
    {
        $asset = new class('', '') extends BaseAsset {
            protected function defaultHandler(): string
            {
                return '';
            }
        };

        $asset->withVersion('');

        static::assertSame('', $asset->version());
    }

    public function testVersionReturnsNullWhenFilePathCannotBeResolved(): void
    {
        $asset = new class('asset', 'https://example.com/missing.css') extends BaseAsset {
            protected function defaultHandler(): string
            {
                return '';
            }
        };

        Functions\expect('set_url_scheme')->once()->andReturnFirstArg();

        static::assertNull($asset->version());
    }

    /**
     * @test
     */
    public function testFilePath(): void
    {
        $root = sys_get_temp_dir() . '/sympress-base-asset-' . bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        file_put_contents($root . '/style.css', 'body { background: white; }');

        Functions\expect('set_url_scheme')->once()->andReturnFirstArg();
        Functions\expect('get_stylesheet_directory_uri')->once()->andReturn('https://example.com');
        Functions\expect('get_template_directory_uri')->once()->andReturn('https://example.com');
        Functions\expect('get_stylesheet_directory')->once()->andReturn($root);
        Functions\when('get_template_directory')->justReturn($root);

        $asset = $this->createBaseAsset('foo', 'https://example.com/style.css');

        $expectedFilePath = $root . '/style.css';

        try {
            static::assertSame($expectedFilePath, $asset->filePath());
            static::assertSame($expectedFilePath, $asset->filePath());
        } finally {
            unlink($root . '/style.css');
            rmdir($root);
        }
    }

    /**
     * @test
     */
    public function testFilePathFails(): void
    {
        $asset = $this->createBaseAsset();

        Functions\expect('set_url_scheme')->once()->andThrow(new \Exception());

        static::assertSame('', $asset->filePath());
        static::assertSame('', $asset->filePath());
    }

    /**
     * @test
     */
    public function testDependencies(): void
    {
        $asset = $this->createBaseAsset();

        static::assertEmpty($asset->dependencies());

        $asset->withDependencies('foo');
        static::assertEquals(['foo'], $asset->dependencies());

        $asset->withDependencies('bar', 'baz');
        static::assertEquals(['foo', 'bar', 'baz'], $asset->dependencies());

        // Adding "foo" again shouldn't lead to duplicated dependencies.
        $asset->withDependencies('foo');
        static::assertEquals(['foo', 'bar', 'baz'], $asset->dependencies());
    }

    /**
     * @test
     */
    public function testLocation(): void
    {
        $asset = $this->createBaseAsset();

        static::assertSame(Asset::FRONTEND | Asset::ACTIVATE, $asset->location());

        $asset->forLocation(Asset::BACKEND);
        static::assertSame(Asset::BACKEND, $asset->location());
    }

    /**
     * @test
     */
    public function testEnqueue()
    {
        $asset = $this->createBaseAsset();

        static::assertTrue($asset->enqueue());

        $asset->canEnqueue(false);
        static::assertFalse($asset->enqueue());

        $asset->canEnqueue('__return_true');
        static::assertTrue($asset->enqueue());
    }

    /**
     * @test
     */
    public function testHandler()
    {
        $expectedHandler = 'myHandler';
        $asset = new class($expectedHandler) extends BaseAsset {
            protected $expectedHandler;

            public function __construct(string $expectedHandler)
            {
                $this->expectedHandler = $expectedHandler;
                parent::__construct('', '');
            }

            protected function defaultHandler(): string
            {
                return $this->expectedHandler;
            }
        };

        static::assertSame($expectedHandler, $asset->handler());

        $expected = bin2hex(random_bytes(4));
        $asset->useHandler($expected);
        static::assertSame($expected, $asset->handler());
    }

    private function createBaseAsset(string $handle = '', string $src = ''): BaseAsset
    {
        return new class($handle, $src) extends BaseAsset {
            protected function defaultHandler(): string
            {
                return __CLASS__;
            }
        };
    }
}
