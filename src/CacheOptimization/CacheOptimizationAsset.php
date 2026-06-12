<?php

declare(strict_types=1);

namespace SymPress\Assets\CacheOptimization;

use SymPress\Assets\Util\FilesystemPath;

final readonly class CacheOptimizationAsset
{
    /** @param list<string> $fileIdentifiers */
    public function __construct(
        public string $handle,
        public string $url,
        public string $filePath,
        public CacheOptimizationExclusion $exclusion,
        /** @var list<string> */
        private array $fileIdentifiers,
    ) {
    }

    /** @return list<string> */
    public function fileIdentifiers(): array
    {
        return $this->fileIdentifiers;
    }

    public function matchesFileReference(string $reference): bool
    {
        foreach ($this->exactFileReferences() as $assetReference) {
            foreach ($this->referenceCandidates($reference) as $candidate) {
                if ($candidate === $assetReference) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> */
    private function exactFileReferences(): array
    {
        return array_values(array_unique(array_filter([
            $this->withoutQuery($this->url),
            $this->urlPath($this->url),
            $this->normalizePath($this->filePath),
        ], static fn (string $reference): bool => $reference !== '')));
    }

    /** @return list<string> */
    private function referenceCandidates(string $reference): array
    {
        $reference = html_entity_decode(trim($reference), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return array_values(array_unique(array_filter([
            $this->withoutQuery($reference),
            $this->urlPath($reference),
            $this->normalizePath($reference),
        ], static fn (string $candidate): bool => $candidate !== '')));
    }

    private function withoutQuery(string $reference): string
    {
        return strtok($reference, '?#') ?: $reference;
    }

    private function urlPath(string $reference): string
    {
        $path = parse_url($reference, PHP_URL_PATH);

        return is_string($path) ? rawurldecode($path) : '';
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        return FilesystemPath::normalize($path);
    }
}
