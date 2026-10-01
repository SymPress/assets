<?php

declare(strict_types=1);

namespace SymPress\Assets\Loader;

use SymPress\Assets\Security\FilesystemPathPolicy;

final class EncoreManifest
{
    /**
     * @return array<string, array<string, list<string>>>|null
     */
    public static function read(string $file): ?array
    {
        if (!\SymPress\Assets\IO\RequestFiles::shared()->info($file)['readable']) {
            return null;
        }
        $contents = \SymPress\Assets\IO\RequestFiles::shared()->contents($file);
        if ($contents === null) {
            return null;
        }
        try {
            $manifest = \SymPress\Assets\IO\RequestFiles::shared()->remember('json:' . $file, static fn (): mixed => json_decode($contents, true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException) {
            return null;
        }
        $entries = is_array($manifest) ? ($manifest['entrypoints'] ?? null) : null;
        if (!is_array($entries)) {
            return null;
        }
        $valid = [];
        foreach ($entries as $name => $entry) {
            if (!is_string($name) || !is_array($entry)) {
                continue;
            }
            $assets = [];
            foreach (['css', 'js'] as $type) {
                if (!isset($entry[$type])) {
                    continue;
                }
                if (!self::validFiles($entry[$type], dirname($file), $type)) {
                    continue 2;
                }
                $assets[$type] = $entry[$type];
            }
            if (array_filter($assets) === []) {
                continue;
            }

            $valid[$name] = $assets;
        }
        return $valid === [] ? null : $valid;
    }

    /** @phpstan-assert-if-true list<string> $files */
    private static function validFiles(mixed $files, string $directory, string $type): bool
    {
        if (!is_array($files) || !array_is_list($files)) {
            return false;
        }
        foreach ($files as $asset) {
            if (
                !is_string($asset) || !preg_match('~^(?:\./)?(?:[a-zA-Z0-9_-][a-zA-Z0-9_.-]*/)*[a-zA-Z0-9_-][a-zA-Z0-9_.-]*\.(?:css|js)$~', $asset)
                || pathinfo($asset, PATHINFO_EXTENSION) !== $type
                || !is_readable($directory . '/' . $asset)
                || !(new FilesystemPathPolicy([$directory]))->allowsPath($directory . '/' . $asset)
            ) {
                return false;
            }
        }
        return true;
    }
}
