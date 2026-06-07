---
nav_order: 1
---

# Getting Started

Assets are registered during `AssetManager::ACTION_SETUP`. This ensures
WordPress' script and style registries are only used once they are ready.

```php
<?php

use SymPress\Assets\AssetManager;
use SymPress\Assets\Script;
use SymPress\Assets\ScriptModule;
use SymPress\Assets\Style;

add_action(
    AssetManager::ACTION_SETUP,
    static function (AssetManager $assets): void {
        $assets->register(
            new Script('site', plugin_dir_url(__FILE__) . 'assets/site.js'),
            new ScriptModule('@site/gallery', plugin_dir_url(__FILE__) . 'assets/gallery.js'),
            new Style('site', plugin_dir_url(__FILE__) . 'assets/site.css'),
        );
    }
);
```

## Standalone Bootstrap

Without the Symfony kernel, load the bootstrap once after the Composer
autoloader:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/sympress/assets/inc/bootstrap.php';
```

## Extending Assets

Loaded assets can be extended before registration. This is useful when handles
come from a manifest.

```php
<?php

use SymPress\Assets\AssetManager;
use SymPress\Assets\Loader\WebpackManifestLoader;
use SymPress\Assets\Script;
use SymPress\Assets\Style;

add_action(
    AssetManager::ACTION_SETUP,
    static function (AssetManager $assets): void {
        $assets->extendAsset('site', Script::class, [
            'enqueue' => static fn (): bool => !is_admin(),
            'strategy' => 'defer',
        ]);

        $assets->extendAsset('site', Style::class, [
            'loadingMode' => 'preload',
        ]);

        $loader = new WebpackManifestLoader();
        $assets->register(...$loader->load(__DIR__ . '/manifest.json'));
    }
);
```

`handle`, `type`, and `url` are part of the asset identity and are not changed
through `extendAsset()`.

## Kernel Integration

With `sympress/kernel`, `AssetsBundle` is used as a bundle automatically.
Services that implement `AssetProviderInterface` provide assets. Services that
implement `AssetConfiguratorInterface` can adjust those assets before
registration.

```php
<?php

use SymPress\Assets\AssetProviderInterface;
use SymPress\Assets\Script;

final class ThemeAssets implements AssetProviderInterface
{
    public function assets(): iterable
    {
        yield new Script('site', get_template_directory_uri() . '/assets/site.js');
    }
}
```
