<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Loader;

use PHPUnit\Framework\Attributes\Test;
use SymPress\Assets\Exception\FileNotFoundException;
use SymPress\Assets\Exception\InvalidResourceException;
use SymPress\Assets\Loader\JsonFileReader;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use org\bovigo\vfs\vfsStream;

final class JsonFileReaderTest extends AbstractTestCase
{
    #[Test]
    public function itReadsJsonObjectsAsArrays(): void
    {
        $root = vfsStream::setup('tmp');
        $file = vfsStream::newFile('manifest.json')
            ->withContent('{"app.js": "/assets/app.js"}')
            ->at($root)
            ->url();

        self::assertSame(
            ['app.js' => '/assets/app.js'],
            (new JsonFileReader())->read($file),
        );
    }

    #[Test]
    public function itRejectsMissingFiles(): void
    {
        $this->expectException(FileNotFoundException::class);

        (void) (new JsonFileReader())->read('missing.json');
    }

    #[Test]
    public function itRejectsMalformedJson(): void
    {
        $root = vfsStream::setup('tmp');
        $file = vfsStream::newFile('manifest.json')
            ->withContent('{"app.js" "/assets/app.js"}')
            ->at($root)
            ->url();

        $this->expectException(InvalidResourceException::class);

        (void) (new JsonFileReader())->read($file);
    }

    #[Test]
    public function itRejectsScalarJson(): void
    {
        $root = vfsStream::setup('tmp');
        $file = vfsStream::newFile('manifest.json')
            ->withContent('"not-a-manifest"')
            ->at($root)
            ->url();

        $this->expectException(InvalidResourceException::class);

        (void) (new JsonFileReader())->read($file);
    }
}
