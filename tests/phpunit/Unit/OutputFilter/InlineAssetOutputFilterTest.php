<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\OutputFilter;

use SymPress\Assets\Asset;
use SymPress\Assets\OutputFilter\AssetOutputFilter;
use SymPress\Assets\OutputFilter\InlineAssetOutputFilter;
use SymPress\Assets\Script;
use SymPress\Assets\Security\FilesystemPathPolicy;
use SymPress\Assets\Security\InlineAssetPolicy;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class InlineAssetOutputFilterTest extends AbstractTestCase
{
    private string $root;

    public function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/sympress-inline-' . bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function testBasic()
    {
        static::assertInstanceOf(AssetOutputFilter::class, new InlineAssetOutputFilter());
    }

    /**
     * @test
     */
    public function testRenderStyle()
    {
        $expectedVersion = 'foo';
        $expectedHandle = 'bar';

        $filePath = $this->writeFile('style.css', 'body { background: white; }');

        $stub = \Mockery::mock(Asset::class . ',' . Style::class);
        $stub->expects('filePath')->andReturn($filePath);
        $stub->expects('version')->andReturn($expectedVersion);
        $stub->expects('handle')->andReturn($expectedHandle);
        $stub->expects('attributes')->andReturn([]);

        $input = '<link rel="stylesheet" href="https://localhost.com/style.css" />';

        $testee = $this->filterForRoot();
        $result = $testee($input, $stub);

        static::assertNotSame($input, $result);

        static::assertStringContainsString('<style', $result);
        static::assertStringContainsString('data-id="' . $expectedHandle . '"', $result);
        static::assertStringContainsString('data-version="' . $expectedVersion . '"', $result);
        static::assertStringContainsString('</style>', $result);
    }

    /**
     * @test
     */
    public function testRenderScript()
    {
        $expectedVersion = 'foo';
        $expectedHandle = 'bar';

        $filePath = $this->writeFile('script.js', 'console.log("foo");');

        $stub = \Mockery::mock(Asset::class . ',' . Script::class);
        $stub->expects('filePath')->andReturn($filePath);
        $stub->expects('version')->andReturn($expectedVersion);
        $stub->expects('handle')->andReturn($expectedHandle);
        $stub->expects('attributes')->andReturn([]);

        $input = '<script src="https://localhost.com/script.js"></script>';

        $testee = $this->filterForRoot();
        $result = $testee($input, $stub);

        static::assertNotSame($input, $result);

        static::assertStringContainsString('<script', $result);
        static::assertStringContainsString('data-id="' . $expectedHandle . '"', $result);
        static::assertStringContainsString('data-version="' . $expectedVersion . '"', $result);
        static::assertStringContainsString('</script>', $result);
    }

    public function testEscapesGeneratedAttributes(): void
    {
        $filePath = $this->writeFile('script.js', 'console.log("foo");');

        $stub = \Mockery::mock(Asset::class . ',' . Script::class);
        $stub->expects('filePath')->andReturn($filePath);
        $stub->expects('version')->andReturn('1" onclick="alert(1)');
        $stub->expects('handle')->andReturn('asset" data-bad="1');
        $stub->expects('attributes')->andReturn([]);

        $result = $this->filterForRoot()(
            '<script src="https://localhost.com/script.js"></script>',
            $stub,
        );

        static::assertStringContainsString('data-version="1&quot; onclick=&quot;alert(1)"', $result);
        static::assertStringContainsString('data-id="asset&quot; data-bad=&quot;1"', $result);
        static::assertStringNotContainsString('onclick="alert(1)"', $result);
    }

    public function testInlineScriptPreservesSafeAttributesAndDropsExternalOnlyAttributes(): void
    {
        $filePath = $this->writeFile('script.js', 'console.log("foo");');

        $asset = (new Script('secure-script', 'https://localhost.com/script.js'))
            ->withFilePath($filePath)
            ->withVersion('1')
            ->withAttributes([
                'nonce' => 'nonce-value',
                'data-no-optimize' => true,
                'data-cfasync' => 'false',
                'src' => 'https://attacker.test/override.js',
                'integrity' => 'ignored',
                'defer' => true,
                'bad attr' => 'ignored',
            ]);

        $result = $this->filterForRoot()(
            '<script src="https://localhost.com/script.js"></script>',
            $asset,
        );

        static::assertStringContainsString('nonce="nonce-value"', $result);
        static::assertStringContainsString('data-no-optimize="data-no-optimize"', $result);
        static::assertStringContainsString('data-cfasync="false"', $result);
        static::assertStringNotContainsString('src=', $result);
        static::assertStringNotContainsString('integrity=', $result);
        static::assertStringNotContainsString('defer=', $result);
        static::assertStringNotContainsString('bad attr=', $result);
    }

    public function testInlineScriptEscapesClosingScriptSequence(): void
    {
        $filePath = $this->writeFile('script.js', 'window.x = "</script><script>alert(1)</script>";');

        $asset = (new Script('safe-script', 'https://localhost.com/script.js'))
            ->withFilePath($filePath);

        $result = $this->filterForRoot()(
            '<script src="https://localhost.com/script.js"></script>',
            $asset,
        );

        static::assertStringNotContainsString('</script><script>alert(1)', $result);
        static::assertStringContainsString('<\/script><script>alert(1)<\/script>', $result);
    }

    /**
     * @test
     */
    public function testRenderNonExistingFile()
    {
        $stub = \Mockery::mock(Asset::class . ',' . Script::class);
        $stub->expects('filePath')->andReturn('non-existing.file');

        $expected = '<link rel="stylesheet" href="https://localhost.com/style.css" />';

        $testee = new InlineAssetOutputFilter();
        $result = $testee($expected, $stub);

        static::assertSame($expected, $result);
    }

    public function testDoesNotInlineFilesOutsideAllowedRoots(): void
    {
        $outsideRoot = sys_get_temp_dir() . '/sympress-inline-outside-' . bin2hex(random_bytes(6));
        mkdir($outsideRoot, 0777, true);
        $filePath = $outsideRoot . '/script.js';
        file_put_contents($filePath, 'alert("outside");');

        $stub = \Mockery::mock(Asset::class . ',' . Script::class);
        $stub->expects('filePath')->andReturn($filePath);

        $input = '<script src="https://localhost.com/script.js"></script>';

        try {
            static::assertSame($input, $this->filterForRoot()($input, $stub));
        } finally {
            $this->removeDirectory($outsideRoot);
        }
    }

    public function testDoesNotInlineUnexpectedFileTypes(): void
    {
        $filePath = $this->writeFile('script.php', '<?php echo "secret";');

        $stub = \Mockery::mock(Asset::class . ',' . Script::class);
        $stub->expects('filePath')->andReturn($filePath);

        $input = '<script src="https://localhost.com/script.php"></script>';

        static::assertSame($input, $this->filterForRoot()($input, $stub));
    }

    public function testDoesNotInlineFilesAboveSizeLimit(): void
    {
        $filePath = $this->writeFile('script.js', 'console.log("too-large");');

        $stub = \Mockery::mock(Asset::class . ',' . Script::class);
        $stub->expects('filePath')->andReturn($filePath);

        $input = '<script src="https://localhost.com/script.js"></script>';

        $filter = $this->filterForRoot(maxBytes: 4);

        static::assertSame($input, $filter($input, $stub));
    }

    private function filterForRoot(?int $maxBytes = null): InlineAssetOutputFilter
    {
        return new InlineAssetOutputFilter(
            new InlineAssetPolicy(
                new FilesystemPathPolicy([$this->root]),
                $maxBytes ?? InlineAssetPolicy::DEFAULT_MAX_BYTES,
            ),
        );
    }

    private function writeFile(string $name, string $content): string
    {
        $filePath = $this->root . '/' . $name;
        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir()
                ? rmdir($file->getPathname())
                : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
