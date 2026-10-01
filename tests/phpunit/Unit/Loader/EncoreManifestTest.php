<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Loader;

use SymPress\Assets\Loader\EncoreEntrypointsLoader;
use SymPress\Assets\Loader\EncoreManifest;
use SymPress\Assets\Security\FilesystemPathPolicy;
use SymPress\Assets\SmallStyleConfigurator;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

final class EncoreManifestTest extends AbstractTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/sympress-manifest-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
        mkdir($this->directory . '/build');
        file_put_contents($this->directory . '/build/app.12345678.css', 'body{color:red}');
        file_put_contents($this->directory . '/outside.css', 'body{color:blue}');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/build/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory . '/build');
        unlink($this->directory . '/outside.css');
        rmdir($this->directory);
        parent::tearDown();
    }

    public function testUnsafeOptionalEntriesDoNotDisableValidFrontendAssets(): void
    {
        symlink($this->directory . '/outside.css', $this->directory . '/build/link.css');
        $file = $this->directory . '/build/entrypoints.json';
        file_put_contents($file, json_encode([
        'entrypoints' => [
            'app' => ['css' => ['app.12345678.css']],
            'traversal' => ['css' => ['../outside.css']],
            'symlink' => ['css' => ['link.css']],
            'wrong-type' => ['js' => ['app.12345678.css']],
            'missing-editor' => ['css' => ['missing.css']],
            'remote' => ['css' => ['https://example.test/outside.css']],
        ],
        ], JSON_THROW_ON_ERROR));
        self::assertSame(['app'], array_keys(EncoreManifest::read($file) ?? []));
        $loader = (new EncoreEntrypointsLoader())->withDirectoryUrl('https://example.test/build/');
        $assets = $loader->fromFile($file);
        self::assertCount(1, $assets);
        self::assertSame('https://example.test/build/app.12345678.css', $assets[0]->url());
        self::assertSame(['build/app.12345678.css'], $loader->editorStyles($file, 'app'));
        self::assertSame([], $loader->editorStyles($file, 'missing-editor'));
    }

    public function testBrokenManifestReturnsNoAssets(): void
    {
        $file = $this->directory . '/build/entrypoints.json';
        $loader = new EncoreEntrypointsLoader();
        self::assertSame([], $loader->fromFile($file));
        foreach (['null', '{}', '{broken', '{"entrypoints":false}'] as $json) {
            file_put_contents($file, $json);
            self::assertSame([], $loader->fromFile($file));
        }
    }

    public function testInliningRequiresHashSmallFileAndSafeDirectory(): void
    {
        $file = $this->directory . '/build/app.12345678.css';
        $configurator = new SmallStyleConfigurator(new FilesystemPathPolicy([$this->directory . '/build']));
        $style = (new Style('app', '/app.css'))->withFilePath($file);
        $configurator->configure($style);
        self::assertNotEmpty($style->filters());
        file_put_contents($file, 'body{background:url(image.png)}');
        $external = (new Style('external', '/app.css'))->withFilePath($file);
        $configurator->configure($external);
        self::assertSame([], $external->filters());
        $outside = (new Style('outside', '/outside.css'))->withFilePath($this->directory . '/outside.css');
        $configurator->configure($outside);
        self::assertSame([], $outside->filters());
    }
}
