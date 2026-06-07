<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\CacheOptimization\CacheOptimizationAwareAsset;
use SymPress\Assets\CacheOptimization\CacheOptimizationAwareTrait;
use SymPress\Assets\Handler\AssetHandler;
use SymPress\Assets\Performance\ResourceHintAwareAsset;
use SymPress\Assets\Performance\ResourceHintAwareTrait;
use SymPress\Assets\Util\AssetPathResolver;

abstract class BaseAsset implements Asset, CacheOptimizationAwareAsset, ResourceHintAwareAsset
{
    use CacheOptimizationAwareTrait;
    use ConfigureAutodiscoverVersionTrait;
    use ResourceHintAwareTrait;

    protected string $url = '';

    private bool $filePathResolved = false;

    /**
     * Full filePath to an Asset which can
     * be used to auto-discover version or
     * load Asset content inline.
     */
    protected string $filePath = '';

    protected string $handle = '';

    /**
     * Dependencies to other Asset handles.
     *
     * @var string[]
     */
    protected array $dependencies = [];

    /**
     * Location where the Asset will be enqueued.
     */
    protected int $location = self::FRONTEND;

    /**
     * Version can be auto-discovered if null.
     *
     * @see BaseAsset::enableAutodiscoverVersion().
     */
    protected ?string $version = null;

    /**
     * @var bool|callable(): bool
     */
    protected $enqueue = true;

    /**
     * @var class-string<AssetHandler>|null
     */
    protected $handler;

    public function __construct(
        string $handle,
        string $url,
        int $location = Asset::FRONTEND | Asset::ACTIVATE,
    ) {
        $this->handle = $handle;
        $this->url = $url;
        $this->location = $location;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function handle(): string
    {
        return $this->handle;
    }

    public function filePath(): string
    {
        $filePath = $this->filePath;

        if ('' !== $filePath) {
            return $filePath;
        }

        if ($this->filePathResolved) {
            return '';
        }

        $this->filePathResolved = true;

        try {
            $filePath = AssetPathResolver::resolve($this->url());
        } catch (\Throwable) {
            $filePath = null;
        }

        // if replacement fails, don't set the url as path.
        if (null === $filePath || !file_exists($filePath)) {
            return '';
        }

        $this->withFilePath($filePath);

        return $filePath;
    }

    public function withFilePath(string $filePath): static
    {
        $this->filePath = $filePath;
        $this->filePathResolved = true;

        return $this;
    }

    /**
     * Returns a version which will be automatically generated based on file time by default.
     */
    public function version(): ?string
    {
        $version = $this->version;

        if (null === $version && $this->autodiscoverVersion) {
            $filePath = $this->filePath();

            if ('' === $filePath || !is_file($filePath)) {
                return null;
            }

            $version = (string) filemtime($filePath);
            $this->withVersion($version);

            return $version;
        }

        return null === $version
            ? null
            : (string) $version;
    }

    public function withVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string[]
     */
    public function dependencies(): array
    {
        return array_values(array_unique($this->dependencies));
    }

    public function withDependencies(string ...$dependencies): static
    {
        $this->dependencies = array_merge(
            $this->dependencies,
            $dependencies,
        );

        return $this;
    }

    public function location(): int
    {
        return (int) $this->location;
    }

    public function forLocation(int $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function enqueue(): bool
    {
        $enqueue = $this->enqueue;
        if (is_callable($enqueue)) {
            $enqueue = $enqueue();
        }

        return (bool) $enqueue;
    }

    /**
     * @param bool|callable(): bool $enqueue
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function canEnqueue($enqueue): static
    {
        $this->enqueue = $enqueue;

        return $this;
    }

    /**
     * @param class-string<AssetHandler> $handler
     */
    public function useHandler(string $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @return class-string<AssetHandler>
     */
    public function handler(): string
    {
        if (!$this->handler) {
            $this->handler = $this->defaultHandler();
        }

        return $this->handler;
    }

    /**
     * @return class-string<AssetHandler> className of the default handler
     */
    abstract protected function defaultHandler(): string;
}
