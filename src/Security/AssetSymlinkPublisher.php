<?php

declare(strict_types=1);

namespace SymPress\Assets\Security;

use SymPress\Assets\Util\FilesystemPath;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final readonly class AssetSymlinkPublisher
{
    private const string FOLDER_NAME = '/~sympress-assets/';

    private const array ALLOWED_PUBLIC_EXTENSIONS = [
        'avif',
        'css',
        'eot',
        'gif',
        'ico',
        'jpeg',
        'jpg',
        'js',
        'json',
        'map',
        'mjs',
        'otf',
        'png',
        'svg',
        'ttf',
        'webp',
        'woff',
        'woff2',
    ];

    public function __construct(
        private FilesystemPathPolicy $originPolicy,
        private ?string $rootPath = null,
        private ?string $rootUrl = null,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @param list<string> $additionalAllowedOriginDirectories
     */
    public static function forWordPress(array $additionalAllowedOriginDirectories = []): self
    {
        return new self(
            FilesystemPathPolicy::fromWordPressAssetDirectories($additionalAllowedOriginDirectories),
        );
    }

    public function publish(string $originDir, string $name): ?string
    {
        if (!function_exists('symlink')) {
            return null;
        }

        $originDir = FilesystemPath::canonical($originDir);
        if (null === $originDir || !is_dir($originDir)) {
            return null;
        }

        if (!$this->originPolicy->allowsDirectory($originDir)) {
            return null;
        }

        if (!$this->containsOnlyPublishableFiles($originDir)) {
            return null;
        }

        $name = $this->normalizeName($name);
        if (null === $name) {
            return null;
        }

        $rootPath = $this->rootPath();
        $rootUrl = $this->rootUrl();
        if (null === $rootPath || null === $rootUrl) {
            return null;
        }

        if (!$this->ensureDirectory($rootPath)) {
            return null;
        }

        $rootPath = FilesystemPath::canonical($rootPath);
        if (null === $rootPath) {
            return null;
        }

        $targetDir = $this->trailingslashit($rootPath) . $name;
        $targetUrl = $this->trailingslashit($this->trailingslashit($rootUrl) . $name);

        if (is_link($targetDir)) {
            if ($this->resolveSymlinkTarget($targetDir) === $originDir) {
                return $targetUrl;
            }

            try {
                $this->filesystem->remove($targetDir);
            } catch (IOExceptionInterface) {
                return null;
            }
        }

        if ($this->filesystem->exists($targetDir)) {
            return null;
        }

        try {
            $this->filesystem->symlink($originDir, $targetDir);
        } catch (IOExceptionInterface) {
            return null;
        }

        return $this->resolveSymlinkTarget($targetDir) === $originDir
            ? $targetUrl
            : null;
    }

    private function rootPath(): ?string
    {
        if (null !== $this->rootPath) {
            return $this->rootPath;
        }

        return defined('WP_CONTENT_DIR')
            ? rtrim((string) WP_CONTENT_DIR, '/\\') . self::FOLDER_NAME
            : null;
    }

    private function rootUrl(): ?string
    {
        if (null !== $this->rootUrl) {
            return $this->rootUrl;
        }

        if (function_exists('content_url')) {
            return content_url(self::FOLDER_NAME);
        }

        return null;
    }

    private function normalizeName(string $name): ?string
    {
        $name = trim($name, "/\\ \t\n\r\0\x0B");
        if ('' === $name || !preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
            return null;
        }

        return $name;
    }

    private function ensureDirectory(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        try {
            $this->filesystem->mkdir($path);
        } catch (IOExceptionInterface) {
            return false;
        }

        return is_dir($path);
    }

    private function containsOnlyPublishableFiles(string $originDir): bool
    {
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($originDir, \FilesystemIterator::SKIP_DOTS),
            );
        } catch (\UnexpectedValueException) {
            return false;
        }

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (!in_array($extension, self::ALLOWED_PUBLIC_EXTENSIONS, true)) {
                return false;
            }
        }

        return true;
    }

    private function resolveSymlinkTarget(string $link): ?string
    {
        $target = $this->filesystem->readlink($link);
        if (null === $target || '' === $target) {
            return null;
        }

        if (!Path::isAbsolute($target)) {
            $target = Path::join(Path::getDirectory($link), $target);
        }

        return FilesystemPath::canonical($target);
    }

    private function trailingslashit(string $value): string
    {
        if (function_exists('trailingslashit')) {
            return trailingslashit($value);
        }

        return rtrim($value, '/\\') . '/';
    }
}
