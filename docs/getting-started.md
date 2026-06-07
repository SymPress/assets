---
nav_order: 1
---

# Getting Started

Assets werden während `AssetManager::ACTION_SETUP` registriert. So werden
WordPress' Script- und Style-Registries erst genutzt, wenn sie bereit sind.

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

Ohne Symfony-Kernel wird der Bootstrap einmal nach dem Composer-Autoloader
geladen:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/sympress/assets/inc/bootstrap.php';
```

## Assets Erweitern

Geladene Assets können vor der Registrierung ergänzt werden. Das ist praktisch,
wenn Handles aus einem Manifest kommen.

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

`handle`, `type` und `url` gehören zur Identität des Assets und werden über
`extendAsset()` nicht geändert.

## Kernel Integration

Mit `sympress/kernel` wird `AssetsBundle` automatisch als Bundle genutzt.
Services, die `AssetProviderInterface` implementieren, liefern Assets. Services,
die `AssetConfiguratorInterface` implementieren, können diese Assets vor der
Registrierung anpassen.

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
