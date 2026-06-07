<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Security;

use SymPress\Assets\Security\AssetSymlinkPublisher;
use SymPress\Assets\Security\FilesystemPathPolicy;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class AssetSymlinkPublisherTest extends AbstractTestCase
{
    private string $root = '';

    private string $allowedRoot = '';

    private string $publishRoot = '';

    public function setUp(): void
    {
        parent::setUp();

        if (!function_exists('symlink')) {
            static::markTestSkipped('The symlink function is not available.');
        }

        $this->root = sys_get_temp_dir() . '/sympress-symlink-' . bin2hex(random_bytes(6));
        $this->allowedRoot = $this->root . '/allowed';
        $this->publishRoot = $this->root . '/public';

        mkdir($this->allowedRoot . '/assets', 0777, true);
        mkdir($this->publishRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
        parent::tearDown();
    }

    public function testPublishesAllowedDirectoryAsSymlink(): void
    {
        $publisher = $this->publisher();

        $url = $publisher->publish($this->allowedRoot . '/assets', 'package-assets');

        static::assertSame('https://example.test/assets/package-assets/', $url);
        static::assertTrue(is_link($this->publishRoot . '/package-assets'));
        $target = readlink($this->publishRoot . '/package-assets');
        static::assertIsString($target);
        static::assertSame(
            realpath($this->allowedRoot . '/assets'),
            realpath($target),
        );
    }

    public function testRejectsOriginOutsideAllowedDirectory(): void
    {
        $privateRoot = $this->root . '/private';
        mkdir($privateRoot, 0777, true);

        static::assertNull($this->publisher()->publish($privateRoot, 'private-assets'));
    }

    public function testRejectsUnsafePublishNames(): void
    {
        static::assertNull($this->publisher()->publish($this->allowedRoot . '/assets', '../private'));
        static::assertNull($this->publisher()->publish($this->allowedRoot . '/assets', 'nested/path'));
    }

    public function testDoesNotOverwriteExistingDirectories(): void
    {
        mkdir($this->publishRoot . '/package-assets');

        static::assertNull($this->publisher()->publish($this->allowedRoot . '/assets', 'package-assets'));
    }

    public function testRejectsDirectoriesWithNonPublicFiles(): void
    {
        file_put_contents($this->allowedRoot . '/assets/bootstrap.php', '<?php echo "private";');

        static::assertNull($this->publisher()->publish($this->allowedRoot . '/assets', 'package-assets'));
        static::assertFalse(file_exists($this->publishRoot . '/package-assets'));
    }

    private function publisher(): AssetSymlinkPublisher
    {
        return new AssetSymlinkPublisher(
            new FilesystemPathPolicy([$this->allowedRoot]),
            $this->publishRoot,
            'https://example.test/assets/',
        );
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
            if ($file->isLink()) {
                unlink($file->getPathname());
                continue;
            }

            $file->isDir()
                ? rmdir($file->getPathname())
                : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
