<?php

declare(strict_types=1);

namespace SymPress\Assets\Security;

use SymPress\Assets\Asset;
use SymPress\Assets\Script;
use SymPress\Assets\Style;
use Symfony\Component\Filesystem\Path;

final readonly class InlineAssetPolicy
{
    public const int DEFAULT_MAX_BYTES = 32_768;

    private const array DEFAULT_ALLOWED_EXTENSIONS_BY_ASSET_TYPE = [
        Script::class => ['js', 'mjs'],
        Style::class  => ['css'],
    ];

    /** @var array<class-string<Asset>, list<string>> */
    private array $allowedExtensionsByAssetType;

    /** @param array<class-string<Asset>, list<string>>|null $allowedExtensionsByAssetType */
    public function __construct(
        private FilesystemPathPolicy $pathPolicy,
        private int $maxBytes = self::DEFAULT_MAX_BYTES,
        ?array $allowedExtensionsByAssetType = null,
    ) {

        if ($maxBytes < 1) {
            throw new \InvalidArgumentException('The inline asset size limit must be greater than zero.');
        }

        $this->allowedExtensionsByAssetType = $allowedExtensionsByAssetType ?? self::DEFAULT_ALLOWED_EXTENSIONS_BY_ASSET_TYPE;
    }

    /** @param list<string> $additionalAllowedDirectories */
    public static function fromWordPressEnvironment(array $additionalAllowedDirectories = []): self
    {
        return new self(
            FilesystemPathPolicy::fromWordPressAssetDirectories($additionalAllowedDirectories),
        );
    }

    public function allows(Asset $asset, string $filePath): bool
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return false;
        }

        if (!$this->pathPolicy->allowsPath($filePath)) {
            return false;
        }

        $size = filesize($filePath);
        if (!is_int($size) || $size > $this->maxBytes) {
            return false;
        }

        $extension = Path::getExtension($filePath, true);
        foreach ($this->allowedExtensionsByAssetType as $assetType => $allowedExtensions) {
            if (is_a($asset, $assetType) && in_array($extension, $allowedExtensions, true)) {
                return true;
            }
        }

        return false;
    }
}
