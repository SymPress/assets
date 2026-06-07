<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Loader;

use SymPress\Assets\Loader\PhpFileLoader;
use SymPress\Assets\Script;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class PhpFileLoaderTest extends AbstractTestCase
{
    /**
     * @var vfsStreamDirectory
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
    public function testLoadFileNotFound()
    {
        \Brain\Monkey\Functions\expect('esc_html')->andReturnFirstArg();
        static::expectException(\SymPress\Assets\Exception\FileNotFoundException::class);
        (void) (new PhpFileLoader())->load('foo');
    }

    /**
     * @test
     */
    public function testLoad()
    {
        $content = <<<FILE
<?php 
use SymPress\Assets\Asset;
use SymPress\Assets\Script;
use SymPress\Assets\Style;

return [
    [
        'handle' => 'foo',
        'url' => 'foo.css',
        'location' => Asset::FRONTEND,
        'type' => Style::class,
    ],
    [
        'handle' => 'bar',
        'url' => 'bar.js',
        'location' => Asset::FRONTEND,
        'type' => Script::class,
    ],
];
FILE;

        $filePath = vfsStream::newFile('config.php')
            ->withContent($content)
            ->at($this->root)
            ->url();

        $testee = new PhpFileLoader();
        $assets = $testee->load($filePath);
        static::assertCount(2, $assets);
        static::assertInstanceOf(Style::class, $assets[0]);
        static::assertInstanceOf(Script::class, $assets[1]);
    }
}
