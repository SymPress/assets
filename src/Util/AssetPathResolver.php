<?php

declare(strict_types=1);

namespace SymPress\Assets\Util;

use Symfony\Component\Filesystem\Path;

class AssetPathResolver
{
    /**
     * Attempt to resolve an assets path based on its URL.
     */
    public static function resolve(string $url): ?string
    {
        $normalizedUrl = set_url_scheme($url);

        return self::resolveForThemeUrl($normalizedUrl)
            ?? self::resolveForPluginUrl($normalizedUrl)
            ?? self::resolveForVendorUrl($normalizedUrl)
            ?? null;
    }

    /**
     * @psalm-suppress PossiblyFalseArgument
     */
    public static function resolveForVendorUrl(string $normalizedUrl): ?string
    {
        // Now let's see if it's inside vendor.
        // This is problematic, this is why vendor assets should be "published".

        if (!defined('ABSPATH')) {
            return null;
        }

        $fullVendorPath = FilesystemPath::canonical(__DIR__ . '/../../../');
        if (null === $fullVendorPath) {
            return null;
        }

        $abspath = constant('ABSPATH');
        if (!is_string($abspath)) {
            return null;
        }

        $abspath = FilesystemPath::normalize($abspath);
        $abspathParent = Path::getDirectory($abspath);

        $relativeVendorPath = null;
        if (FilesystemPath::isBasePath($abspath, $fullVendorPath)) {
            $relativeVendorPath = substr($fullVendorPath, strlen($abspath));
        } elseif (FilesystemPath::isBasePath($abspathParent, $fullVendorPath)) {
            $relativeVendorPath = substr($fullVendorPath, strlen($abspathParent));
        }

        if (!$relativeVendorPath) {
            // vendor is not inside ABSPATH, nor inside its parent
            return null;
        }

        $relativeVendorPath = trim($relativeVendorPath, '/');

        // problematic, as said above: we are assuming vendor URL, but this assumption isn't safe
        $vendorUrl = network_site_url("/{$relativeVendorPath}");

        $relative = self::relativeUrlPath($normalizedUrl, $vendorUrl);

        return null === $relative
            ? null
            : self::resolveRelativeFile($fullVendorPath, $relative);
    }

    public static function resolveForThemeUrl(string $normalizedUrl): ?string
    {
        if (
            !function_exists('get_template_directory_uri')
            || !function_exists('get_stylesheet_directory_uri')
            || !function_exists('get_template_directory')
            || !function_exists('get_stylesheet_directory')
        ) {
            return null;
        }

        $themeUrl = get_template_directory_uri();
        $childUrl = get_stylesheet_directory_uri();

        $base = '';
        $relativeThemeUrl = null;
        $relativeChildUrl = self::relativeUrlPath($normalizedUrl, $childUrl);
        $relativeParentUrl = self::relativeUrlPath($normalizedUrl, $themeUrl);

        if (null !== $relativeChildUrl) {
            $base = get_stylesheet_directory();
            $relativeThemeUrl = $relativeChildUrl;
        } elseif (null !== $relativeParentUrl) {
            $base = get_template_directory();
            $relativeThemeUrl = $relativeParentUrl;
        }

        return null === $relativeThemeUrl
            ? null
            : self::resolveRelativeFile($base, $relativeThemeUrl);
    }

    public static function resolveForPluginUrl(string $normalizedUrl): ?string
    {
        if (!function_exists('plugins_url') || !defined('WP_PLUGIN_DIR') || !defined('WPMU_PLUGIN_DIR')) {
            return null;
        }

        $pluginsUrl = plugins_url('');
        $muPluginsUrl = plugins_url('', WPMU_PLUGIN_DIR . '/file.php');

        $basePath = '';
        $relativePluginUrl = null;
        $relativePluginsUrl = self::relativeUrlPath($normalizedUrl, $pluginsUrl);
        $relativeMuPluginsUrl = self::relativeUrlPath($normalizedUrl, $muPluginsUrl);

        if (null !== $relativePluginsUrl) {
            $basePath = WP_PLUGIN_DIR;
            $relativePluginUrl = $relativePluginsUrl;
        } elseif (null !== $relativeMuPluginsUrl) {
            $basePath = WPMU_PLUGIN_DIR;
            $relativePluginUrl = $relativeMuPluginsUrl;
        }

        return null === $relativePluginUrl
            ? null
            : self::resolveRelativeFile($basePath, $relativePluginUrl);
    }

    private static function relativeUrlPath(string $url, string $baseUrl): ?string
    {
        $baseUrl = rtrim($baseUrl, '/');
        if ($url === $baseUrl) {
            return '';
        }

        if (!str_starts_with($url, $baseUrl . '/')) {
            return null;
        }

        $relative = rawurldecode(substr($url, strlen($baseUrl) + 1));
        $relative = strtok($relative, '?#');
        if (!is_string($relative)) {
            return null;
        }

        $relative = trim($relative, '/');
        if ('' === $relative) {
            return '';
        }

        foreach (explode('/', $relative) as $segment) {
            if ('..' === $segment || '' === $segment || str_contains($segment, '\\')) {
                return null;
            }
        }

        return $relative;
    }

    private static function resolveRelativeFile(string $basePath, string $relativePath): ?string
    {
        if ('' === $relativePath) {
            return null;
        }

        $basePath = FilesystemPath::canonical($basePath);
        if (null === $basePath) {
            return null;
        }

        $candidate = FilesystemPath::canonical(FilesystemPath::join($basePath, ltrim($relativePath, '/\\')));
        if (null === $candidate) {
            return null;
        }

        return FilesystemPath::isBasePath($basePath, $candidate)
            ? $candidate
            : null;
    }
}
