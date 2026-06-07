<?php

declare(strict_types=1);

namespace SymPress\Assets\Loader;

use SymPress\Assets\Asset;
use SymPress\Assets\BaseAsset;
use SymPress\Assets\ConfigureAutodiscoverVersionTrait;
use SymPress\Assets\Script;
use SymPress\Assets\ScriptModule;
use SymPress\Assets\Style;
use Symfony\Component\Filesystem\Path;

abstract class AbstractWebpackLoader implements LoaderInterface
{
    use ConfigureAutodiscoverVersionTrait;

    private const array EXTENSIONS_TO_CLASS = [
        'css' => Style::class,
        'js' => Script::class,
        'mjs' => ScriptModule::class,
        'module.js' => ScriptModule::class,
    ];

    private const array LOCATION_BY_FILENAME_MARKER = [
        '-backend' => Asset::BACKEND,
        '-block' => Asset::BLOCK_EDITOR_ASSETS,
        '-login' => Asset::LOGIN,
        '-customizer-preview' => Asset::CUSTOMIZER_PREVIEW,
        '-customizer' => Asset::CUSTOMIZER,
    ];

    protected string $directoryUrl = '';

    private readonly JsonFileReader $jsonFileReader;

    public function __construct(?JsonFileReader $jsonFileReader = null)
    {
        $this->jsonFileReader = $jsonFileReader ?? new JsonFileReader();
    }

    /**
     * @param string $directoryUrl optional directory URL which will be used for the Asset
     */
    public function withDirectoryUrl(string $directoryUrl): static
    {
        $this->directoryUrl = $directoryUrl;

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return Asset[]
     */
    abstract protected function parseData(array $data, string $resource): array;

    /**
     * @return Asset[]
     *
     * @psalm-suppress MixedArgument
     */
    #[\Override]
    #[\NoDiscard]
    public function load(mixed $resource): array
    {
        $resource = $this->jsonFileReader->readableFile($resource);
        $data = $this->jsonFileReader->read($resource);

        return $this->parseData($data, $resource);
    }

    protected function buildAsset(string $handle, string $fileUrl, string $filePath): ?Asset
    {
        $class = $this->resolveClassByExtension($filePath);
        if (!$class) {
            return null;
        }

        /** @var Style|Script|ScriptModule $asset */
        $asset = new $class($handle, $fileUrl, $this->resolveLocation(Path::getFilenameWithoutExtension($filePath)));
        $asset->withFilePath($filePath);
        $asset->canEnqueue(true);

        if ($asset instanceof BaseAsset) {
            $this->autodiscoverVersion
                ? $asset->enableAutodiscoverVersion()
                : $asset->disableAutodiscoverVersion();
        }

        return $asset;
    }

    /**
     * @return class-string<Style|Script|ScriptModule>|null
     */
    protected function resolveClassByExtension(string $filePath): ?string
    {
        $extension = self::isModule(basename($filePath))
            ? 'module.js'
            : Path::getExtension($filePath, true);

        return self::EXTENSIONS_TO_CLASS[$extension] ?? null;
    }

    protected static function isModule(string $fileName): bool
    {
        return str_ends_with($fileName, '.module.js') || str_ends_with($fileName, '.mjs');
    }

    /**
     * The "file"-value can contain:
     *  - URL
     *  - Path to current folder
     *  - Absolute path
     *
     * We try to build a clean path which will be appended to the directoryPath or urlPath.
     */
    protected function sanitizeFileName(string $file): string
    {
        // Check if the given "file"-value is a URL
        $parsedUrl = parse_url($file);

        // the "file"-value can contain "./file.css" or "/file.css".

        return ltrim($parsedUrl['path'] ?? $file, './');
    }

    /**
     * Internal function to sanitize the handle based on the file
     * by taking into consideration that @vendor can be present.
     *
     * @example /path/to/@vendor/script.module.js   -> @vendor/script.module
     * @example /path/to/script.js                  -> script
     * @example @vendor/script.module.js            -> @vendor/script.module
     */
    protected function normalizeHandle(string $file): string
    {
        $vendor = explode('@', Path::getDirectory($file), 2)[1] ?? null;
        $handle = Path::getFilenameWithoutExtension($file);

        if (null !== $vendor) {
            $handle = "@{$vendor}/{$handle}";
        }

        return $handle;
    }

    /**
     * Internal function to resolve a location for a given file name.
     *
     * @example foo-customizer.css  -> Asset::CUSTOMIZER
     * @example foo-block.css       -> Asset::BLOCK_EDITOR_ASSETS
     * @example foo-login.css       -> Asset::LOGIN
     * @example foo.css             -> Asset::FRONTEND
     * @example foo-backend.css     -> Asset::BACKEND
     */
    protected function resolveLocation(string $fileName): int
    {
        $normalizedFileName = strtolower($fileName);

        foreach (self::LOCATION_BY_FILENAME_MARKER as $marker => $location) {
            if (str_contains($normalizedFileName, $marker)) {
                return $location;
            }
        }

        return Asset::FRONTEND;
    }
}
