<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Loader;

use SymPress\Assets\Asset;
use SymPress\Assets\Loader\EncoreEntrypointsLoader;
use SymPress\Assets\Script;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class EncoreEntrypointsLoaderTest extends AbstractTestCase
{
    private vfsStreamDirectory $root;

    public function setUp(): void
    {
        $this->root = vfsStream::setup('tmp');
        parent::setUp();
    }

    /** @test */
    public function testLoad(): void
    {
        $testee = new EncoreEntrypointsLoader();

        $file = $this->mockEntrypointsFile(
            [
                'entrypoints' => [
                    'theme' => [
                        'css' => [
                            './theme.css',
                        ],
                        'js'  => [
                            './theme.js',
                        ],
                    ],
                ],
            ],
        );

        $assets = $testee->load($file);

        static::assertCount(2, $assets);
        static::assertInstanceOf(Style::class, $assets[0]);
        static::assertInstanceOf(Script::class, $assets[1]);
    }

    /** @test */
    public function testLoadWithDependencies(): void
    {
        $testee = new EncoreEntrypointsLoader();

        $file = $this->mockEntrypointsFile(
            [
                'entrypoints' => [
                    'theme' => [
                        'css' => [
                            './theme.css',
                            './theme1.css',
                            './theme2.css',
                        ],
                    ],
                ],
            ],
        );

        $assets = $testee->load($file);
        static::assertCount(3, $assets);

        /** @var Asset $asset */
        $asset = $assets[1];
        static::assertSame(['theme'], $asset->dependencies());

        $asset = $assets[2];
        static::assertSame('theme-2', $asset->handle());
        static::assertSame(['theme', 'theme-1'], $asset->dependencies());
    }

    public function testLoadFromArrayPreservesPathsAndChunkDependenciesWithoutReadingManifest(): void
    {
        $loader = (new EncoreEntrypointsLoader())->withDirectoryUrl('https://example.com/build/');
        $data = [
            'entrypoints' => [
                'theme' => [
                    'css' => ['css/theme.css'],
                    'js' => ['js/runtime.js', 'js/theme.js'],
                ],
            ],
        ];
        $assets = $loader->loadFromArray($data, '/theme/build/missing-entrypoints.json');

        static::assertCount(3, $assets);
        static::assertInstanceOf(Style::class, $assets[0]);
        static::assertInstanceOf(Script::class, $assets[1]);
        static::assertSame('https://example.com/build/css/theme.css', $assets[0]->url());
        static::assertSame('/theme/build/css/theme.css', $assets[0]->filePath());
        static::assertSame('/theme/build/js/theme.js', $assets[2]->filePath());
        static::assertSame('https://example.com/build/js/theme.js', $assets[2]->url());
        static::assertSame('theme-1', $assets[2]->handle());
        static::assertSame(['theme'], $assets[2]->dependencies());
        static::assertSame([], $loader->loadFromArray([], '/theme/build/entrypoints.json'));
    }

    public function testLoadFromArrayMatchesFileLoader(): void
    {
        $data = ['entrypoints' => ['theme' => ['js' => ['./theme.js']]]];
        $file = $this->mockEntrypointsFile($data);
        $loader = new EncoreEntrypointsLoader();

        static::assertEquals($loader->load($file), $loader->loadFromArray($data, $file));
    }

    private function mockEntrypointsFile(array $json): string
    {
        return vfsStream::newFile('entrypoints.json')
            ->withContent(json_encode($json))
            ->at($this->root)
            ->url();
    }
}
