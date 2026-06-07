# SymPress Assets

Asset management for SymPress WordPress packages, plugins, and themes.

The package registers classic scripts, script modules, and styles through a
Composer library. It can run standalone in WordPress or through the optional
Symfony kernel integration.

## Installation

```bash
composer require sympress/assets
```

## Features

- Scripts, script modules, and styles with one shared asset API
- Webpack manifest, Encore, array, and PHP file loaders
- Automatic versions from file paths or dependency extraction files
- Native script loading strategies: `defer`, `async`, `blocking`
- Style preload for non-critical CSS
- Resource hints for `preload`, `preconnect`, and `dns-prefetch`
- Protection from cache, minify, combine, defer, and delay optimizations
- Optional Symfony kernel integration with asset providers

## Example

```php
<?php

use SymPress\Assets\AssetManager;
use SymPress\Assets\Script;
use SymPress\Assets\Style;

add_action(
    AssetManager::ACTION_SETUP,
    static function (AssetManager $assets): void {
        $assets->register(
            new Script('site', plugin_dir_url(__FILE__) . 'assets/site.js'),
            new Style('site', plugin_dir_url(__FILE__) . 'assets/site.css'),
        );
    }
);
```

Classic scripts use WordPress' native `defer` strategy by default. Styles remain
blocking unless `Style::preload()` is enabled for non-critical CSS.

## Standalone WordPress

When the Symfony kernel is not used, load `inc/bootstrap.php` once after the
Composer autoloader.

## License

This package is licensed under `GPL-2.0-or-later`.

It contains code derived from `inpsyde/assets`. See [NOTICE.md](./NOTICE.md) for
details.
