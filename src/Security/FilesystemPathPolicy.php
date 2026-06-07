<?php

declare(strict_types=1);

namespace SymPress\Assets\Security;

use SymPress\Assets\Util\FilesystemPath;

final readonly class FilesystemPathPolicy
{
    /**
     * @var list<string>
     */
    private array $allowedBaseDirectories;

    /**
     * @param list<string> $allowedBaseDirectories
     */
    public function __construct(array $allowedBaseDirectories)
    {
        $this->allowedBaseDirectories = self::normalizeDirectories($allowedBaseDirectories);
    }

    /**
     * @param list<string> $additionalDirectories
     */
    public static function fromWordPressAssetDirectories(array $additionalDirectories = []): self
    {
        $directories = $additionalDirectories;

        foreach (['WP_PLUGIN_DIR', 'WPMU_PLUGIN_DIR'] as $constant) {
            if (defined($constant)) {
                $directories[] = (string) constant($constant);
            }
        }

        foreach (['get_template_directory', 'get_stylesheet_directory'] as $function) {
            if (!function_exists($function)) {
                continue;
            }

            try {
                $directory = $function();
            } catch (\Throwable) {
                continue;
            }

            if ('' !== $directory) {
                $directories[] = $directory;
            }
        }

        return new self($directories);
    }

    public function allowsPath(string $path): bool
    {
        $canonicalPath = self::canonicalPath($path);
        if (null === $canonicalPath) {
            return false;
        }

        return $this->contains($canonicalPath);
    }

    public function allowsDirectory(string $path): bool
    {
        return is_dir($path) && $this->allowsPath($path);
    }

    /**
     * @return list<string>
     */
    public function allowedBaseDirectories(): array
    {
        return $this->allowedBaseDirectories;
    }

    private function contains(string $canonicalPath): bool
    {
        foreach ($this->allowedBaseDirectories as $allowedBaseDirectory) {
            if (FilesystemPath::isBasePath($allowedBaseDirectory, $canonicalPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    private static function normalizeDirectories(array $directories): array
    {
        $normalized = [];

        foreach ($directories as $directory) {
            $canonical = self::canonicalPath($directory);
            if (null === $canonical || !is_dir($canonical)) {
                continue;
            }

            $normalized[$canonical] = $canonical;
        }

        return array_values($normalized);
    }

    private static function canonicalPath(string $path): ?string
    {
        return FilesystemPath::canonical($path);
    }
}
