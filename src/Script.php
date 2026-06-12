<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Handler\ScriptHandler;

class Script extends BaseAsset implements Asset, DataAwareAsset, DependencyExtractionAwareAsset, FilterAwareAsset
{
    use DataAwareTrait;
    use DependencyExtractionTrait;
    use FilterAwareTrait;

    /** @var array<string, mixed> */
    protected array $localize = [];

    /** @var array{after:array<string>, before:array<string>} */
    protected array $inlineScripts = [
        'before' => [],
        'after'  => [],
    ];

    protected bool $inFooter = true;

    /** @var ScriptLoadingStrategy::ASYNC|ScriptLoadingStrategy::DEFER|null */
    protected ?string $loadingStrategy = ScriptLoadingStrategy::DEFER;

    /** @var array{domain:string, path:string|null} */
    protected array $translation = [
        'domain' => '',
        'path'   => null,
    ];

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
    public function localize(): array
    {
        $output = [];
        foreach ($this->localize as $objectName => $data) {
            $output[$objectName] = is_callable($data)
                ? $data()
                : $data;
        }

        return $output;
    }

    /** @param string|int|array<mixed>|callable $data */
    public function withLocalize(string $objectName, $data): static
    {
        $this->localize[$objectName] = $data;

        return $this;
    }

    public function inFooter(): bool
    {
        return $this->inFooter;
    }

    public function isInFooter(): static
    {
        $this->inFooter = true;

        return $this;
    }

    public function isInHeader(): static
    {
        $this->inFooter = false;

        return $this;
    }

    /**
     * Returns the native WordPress loading strategy.
     *
     * `null` means classic render-blocking output.
     *
     * @return ScriptLoadingStrategy::ASYNC|ScriptLoadingStrategy::DEFER|null
     */
    public function loadingStrategy(): ?string
    {
        return $this->loadingStrategy;
    }

    public function withLoadingStrategy(?string $strategy): static
    {
        $this->loadingStrategy = ScriptLoadingStrategy::normalize($strategy);

        return $this;
    }

    public function blocking(): static
    {
        return $this->withLoadingStrategy(ScriptLoadingStrategy::BLOCKING);
    }

    public function defer(): static
    {
        return $this->withLoadingStrategy(ScriptLoadingStrategy::DEFER);
    }

    public function async(): static
    {
        return $this->withLoadingStrategy(ScriptLoadingStrategy::ASYNC);
    }

    /** @return array{before:array<string>, after:array<string>} */
    public function inlineScripts(): array
    {
        return $this->inlineScripts;
    }

    public function prependInlineScript(string $jsCode): static
    {
        $this->inlineScripts['before'][] = $jsCode;

        return $this;
    }

    public function appendInlineScript(string $jsCode): static
    {
        $this->inlineScripts['after'][] = $jsCode;

        return $this;
    }

    /** @return array{domain:string, path:string|null} */
    public function translation(): array
    {
        return $this->translation;
    }

    public function withTranslation(string $domain = 'default', ?string $path = null): static
    {
        $this->translation = ['domain' => $domain, 'path' => $path];

        return $this;
    }

    /**
     * Wrapper function to set async loading.
     *
     * @deprecated use Script::async().
     */
    public function useAsyncFilter(): static
    {
        return $this->async();
    }

    /**
     * Wrapper function to set defer loading.
     *
     * @deprecated use Script::defer().
     */
    public function useDeferFilter(): static
    {
        return $this->defer();
    }

    protected function defaultHandler(): string
    {
        return ScriptHandler::class;
    }

    /**
     * @deprecated when calling Script::version() or Script::dependencies(),
     * we will automatically resolve the dependency extraction plugin files.
     * This method will be removed in future.
     * @see https://github.com/WordPress/gutenberg/tree/master/packages/dependency-extraction-webpack-plugin
     */
    public function useDependencyExtractionPlugin(): static
    {
        return $this;
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
