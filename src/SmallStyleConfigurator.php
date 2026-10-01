<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Security\FilesystemPathPolicy;

/** Opt-in standalone configurator; the existing inline policy remains authoritative at output time. */
final readonly class SmallStyleConfigurator implements AssetConfiguratorInterface
{
    public function __construct(private FilesystemPathPolicy $paths, private int $maxBytes = 16_384)
    {
    }

    public function supports(Asset $asset): bool
    {
        return $asset instanceof Style;
    }

    public function configure(Asset $asset): Asset
    {
        if (!$asset instanceof Style) {
            return $asset;
        }
        $path = $asset->filePath();
        if (!$this->paths->allowsPath($path) || !preg_match('/\.[a-f0-9]{8,}\.css$/', $path) || !is_readable($path) || filesize($path) > $this->maxBytes) {
            return $asset;
        }
        $css = file_get_contents($path);
        if ($css !== false && !preg_match('/\burl\s*\(|@import\b/i', $css)) {
            $asset->useInlineFilter();
        }
        return $asset;
    }
}
