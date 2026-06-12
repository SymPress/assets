<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Handler\ScriptModuleHandler;

class ScriptModule extends BaseAsset implements Asset, DependencyExtractionAwareAsset
{
    use DependencyExtractionTrait;

    /** @var array<string, mixed> */
    protected array $data = [];

    public function __construct(
        string $handle,
        string $url,
        int $location = Asset::FRONTEND | Asset::ACTIVATE,
        bool $dependencyExtractionEnabled = true,
    ) {

        parent::__construct($handle, $url, $location);
        $this->dependencyExtractionEnabled = $dependencyExtractionEnabled;
    }

    #[\Override]
    public function withFilePath(string $filePath): static
    {
        parent::withFilePath($filePath);
        $this->resolvedDependencyExtractionPlugin = false;

        return $this;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }

    /** @param array<string, mixed> $data */
    public function withData(array $data): static
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    protected function defaultHandler(): string
    {
        return ScriptModuleHandler::class;
    }

    public function version(): ?string
    {
        if ($this->dependencyExtractionEnabled) {
            $this->resolveDependencyExtractionPlugin();
        }

        return parent::version();
    }

    /**
     * {@inheritDoc}
     */
    public function dependencies(): array
    {
        if ($this->dependencyExtractionEnabled) {
            $this->resolveDependencyExtractionPlugin();
        }

        return parent::dependencies();
    }
}
