<?php

declare(strict_types=1);

namespace SymPress\Assets;

interface DependencyExtractionAwareAsset
{
    public function withDependencyExtraction(bool $enabled = true): static;

    public function withPhpDependencyFiles(bool $allowed = true): static;

    public function withDependencyFileSizeLimit(int $maxBytes): static;
}
