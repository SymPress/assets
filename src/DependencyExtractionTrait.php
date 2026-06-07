<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Exception\InvalidResourceException;
use SymPress\Assets\Loader\JsonFileReader;
use SymPress\Assets\Security\DependencyFilePolicy;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

trait DependencyExtractionTrait
{
    private const string DEPENDENCY_FILE_MARKER = 'asset';

    protected bool $resolvedDependencyExtractionPlugin = false;

    protected bool $dependencyExtractionEnabled = false;

    protected bool $phpDependencyFilesAllowed = false;

    protected int $dependencyFileMaxBytes = DependencyFilePolicy::DEFAULT_MAX_BYTES;

    public function withDependencyExtraction(bool $enabled = true): static
    {
        $this->dependencyExtractionEnabled = $enabled;
        $this->resolvedDependencyExtractionPlugin = false;

        return $this;
    }

    public function withPhpDependencyFiles(bool $allowed = true): static
    {
        $this->phpDependencyFilesAllowed = $allowed;
        $this->resolvedDependencyExtractionPlugin = false;

        return $this;
    }

    public function withDependencyFileSizeLimit(int $maxBytes): static
    {
        if ($maxBytes < 1) {
            throw new \InvalidArgumentException('The dependency file size limit must be greater than zero.');
        }

        $this->dependencyFileMaxBytes = $maxBytes;
        $this->resolvedDependencyExtractionPlugin = false;

        return $this;
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    protected function resolveDependencyExtractionPlugin(): bool
    {
        if ($this->resolvedDependencyExtractionPlugin) {
            return false;
        }
        $depsFile = $this->findDependencyFile(
            new DependencyFilePolicy($this->phpDependencyFilesAllowed, $this->dependencyFileMaxBytes),
        );
        $this->resolvedDependencyExtractionPlugin = true;

        if (!$depsFile) {
            return false;
        }

        $depsFilePath = $depsFile->getPathname();
        $data = 'json' === $depsFile->getExtension()
            ? (new JsonFileReader())->read($depsFilePath)
            : $this->loadPhpDependencyFile($depsFilePath);

        $dependencies = $this->normalizeDependencyHandles($data['dependencies'] ?? []);
        $version = $this->normalizeDependencyVersion($data['version'] ?? null);

        $this->withDependencies(...$dependencies);
        if (!$this->version && $version) {
            $this->withVersion($version);
        }

        return true;
    }

    /**
     * Searching for in directory of the asset:
     *
     *      - {fileName}.asset.json
     *      - {fileName}.{hash}.asset.json
     *      - {fileName}.asset.php
     *      - {fileName}.{hash}.asset.php
     *
     * @deprecated use findDependencyFile()
     */
    protected function findDepdendencyFile(): ?\SplFileInfo
    {
        return $this->findDependencyFile();
    }

    protected function findDependencyFile(?DependencyFilePolicy $policy = null): ?\SplFileInfo
    {
        $policy ??= new DependencyFilePolicy($this->phpDependencyFilesAllowed, $this->dependencyFileMaxBytes);

        $filePath = $this->filePath();
        if ('' === $filePath) {
            return null;
        }

        $path = Path::getDirectory($filePath);

        if (!is_dir($path) || !is_readable($path)) {
            return null;
        }

        $fileNames = $this->dependencyFileBaseNames($filePath);

        foreach ($fileNames as $fileName) {
            $exactJsonFile = $this->dependencyFileFromPath(
                Path::join($path, "{$fileName}." . self::DEPENDENCY_FILE_MARKER . '.json'),
                $policy,
            );
            if (null !== $exactJsonFile) {
                return $exactJsonFile;
            }
        }

        foreach ($fileNames as $fileName) {
            $hashedJsonFile = $this->findHashedDependencyFile($path, $fileName, 'json', $policy);
            if (null !== $hashedJsonFile) {
                return $hashedJsonFile;
            }
        }

        foreach ($fileNames as $fileName) {
            $exactPhpFile = $this->dependencyFileFromPath(
                Path::join($path, "{$fileName}." . self::DEPENDENCY_FILE_MARKER . '.php'),
                $policy,
            );
            if (null !== $exactPhpFile) {
                return $exactPhpFile;
            }
        }

        foreach ($fileNames as $fileName) {
            $hashedPhpFile = $this->findHashedDependencyFile($path, $fileName, 'php', $policy);
            if (null !== $hashedPhpFile) {
                return $hashedPhpFile;
            }
        }

        return null;
    }

    /**
     * @return non-empty-list<string>
     */
    private function dependencyFileBaseNames(string $filePath): array
    {
        $fileName = Path::getFilenameWithoutExtension($filePath);
        $baseNames = [$fileName];
        $hashlessFileName = preg_replace('/\.[^.]+$/', '', $fileName) ?: $fileName;

        if ($hashlessFileName !== $fileName) {
            $baseNames[] = $hashlessFileName;
        }

        return array_values(array_unique($baseNames));
    }

    private function dependencyFileFromPath(
        string $filePath,
        ?DependencyFilePolicy $policy = null,
    ): ?\SplFileInfo {
        if (!is_file($filePath)) {
            return null;
        }

        $file = new \SplFileInfo($filePath);

        return null === $policy || $policy->allows($file)
            ? $file
            : null;
    }

    private function findHashedDependencyFile(
        string $path,
        string $fileName,
        string $extension,
        ?DependencyFilePolicy $policy = null,
    ): ?\SplFileInfo {
        $regex = sprintf(
            '/^%s\.[a-zA-Z0-9]+\.%s\.%s$/',
            preg_quote($fileName, '/'),
            preg_quote(self::DEPENDENCY_FILE_MARKER, '/'),
            preg_quote($extension, '/'),
        );

        foreach (Finder::create()->files()->depth('== 0')->in($path)->name($regex)->sortByName() as $fileInfo) {
            $file = $this->dependencyFileFromPath($fileInfo->getPathname(), $policy);
            if (null !== $file) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress UnresolvableInclude
     */
    private function loadPhpDependencyFile(string $filePath): array
    {
        $data = require $filePath;

        if (!is_array($data)) {
            throw new InvalidResourceException(sprintf('Dependency extraction file "%s" must return an array.', esc_html($filePath)));
        }

        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private function normalizeDependencyHandles(mixed $dependencies): array
    {
        if (!is_array($dependencies)) {
            throw new InvalidResourceException('Dependency extraction key "dependencies" must be an array.');
        }

        $normalized = [];

        foreach ($dependencies as $dependency) {
            if (!is_scalar($dependency) && !$dependency instanceof \Stringable) {
                throw new InvalidResourceException('Dependency handles must be scalar or stringable values.');
            }

            $normalized[] = (string) $dependency;
        }

        return $normalized;
    }

    private function normalizeDependencyVersion(mixed $version): ?string
    {
        if (null === $version) {
            return null;
        }

        if (!is_scalar($version) && !$version instanceof \Stringable) {
            throw new InvalidResourceException('Dependency extraction key "version" must be scalar or stringable.');
        }

        return (string) $version;
    }
}
