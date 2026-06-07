---
nav_order: 4
---

# Assets

The package provides three asset classes:

- `Script` for classic JavaScript files
- `ScriptModule` for WordPress script modules
- `Style` for CSS files

All assets need a `handle`, a `url`, and optionally a `location`.

```php
<?php

use SymPress\Assets\Asset;
use SymPress\Assets\Script;
use SymPress\Assets\Style;

$script = new Script('site', plugin_dir_url(__FILE__) . 'assets/site.js', Asset::FRONTEND);
$style = new Style('site', plugin_dir_url(__FILE__) . 'assets/site.css', Asset::FRONTEND);
```

## Shared Options

| Option | Purpose |
| --- | --- |
| `withDependencies()` | Set WordPress handles as dependencies |
| `withVersion()` | Set a fixed version |
| `withFilePath()` | Set the file path for automatic version detection |
| `canEnqueue()` | Enqueue an asset conditionally |
| `forLocation()` | Set the output location |
| `withAttributes()` | Set additional HTML attributes |
| `excludeFromCacheOptimization()` | Set optimizer exclusions |
| `withResourceHint()` | Add a resource hint |

```php
$script
    ->withDependencies('wp-element')
    ->withVersion('1.0.0')
    ->canEnqueue(static fn (): bool => !is_admin());
```

## Locations

Locations can be combined:

```php
$style->forLocation(Asset::FRONTEND | Asset::BLOCK_EDITOR_ASSETS);
```

Important constants are `FRONTEND`, `BACKEND`, `LOGIN`, `BLOCK_ASSETS`, and
`BLOCK_EDITOR_ASSETS`.

## Scripts

Classic scripts use WordPress' native `defer` strategy by default.

```php
$script
    ->defer()
    ->isInFooter()
    ->withLocalize('SiteData', ['restUrl' => rest_url()])
    ->appendInlineScript('window.SiteReady = true;');
```

Use `async()` for independent scripts. Use `blocking()` for scripts that are
intentionally render-blocking.

## Script Modules

`ScriptModule` registers ES modules through the WordPress script modules API.

```php
use SymPress\Assets\ScriptModule;

$module = (new ScriptModule('@site/gallery', plugin_dir_url(__FILE__) . 'assets/gallery.js'))
    ->withData(['view' => 'gallery']);
```

## Styles

Styles remain blocking by default. Non-critical CSS can be preloaded.

```php
$style
    ->forMedia('screen')
    ->preload()
    ->withInlineStyles('.site-header{position:sticky;}')
    ->withCssVars(':root', ['brand' => '#111']);
```

## Dependency Extraction

Scripts and script modules automatically read matching `.asset.json` files when
`withFilePath()` is set. For security reasons, `.asset.php` files are only
enabled when PHP dependency files are explicitly allowed.

```php
$script
    ->withFilePath(__DIR__ . '/assets/editor.js')
    ->withDependencyExtraction()
    ->withPhpDependencyFiles(false)
    ->withDependencyFileSizeLimit(65536);
```

## Cache Optimization

An asset can protect itself from aggressive optimizers:

```php
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;

$script->excludeFromCacheOptimization();
$style->excludeFromCacheOptimization(CacheOptimizationExclusion::minifyAndCombine());
```

`AssetManager::ignoreCache()` is a broad shortcut for registered assets, but it
does not disable all defer, delay, or async optimizations globally.

Filters are supported for WP Rocket, SiteGround Speed Optimizer, W3 Total Cache,
Autoptimize, and LiteSpeed Cache. Defensive attributes are also set for other
optimizers.

## Resource Hints

```php
use SymPress\Assets\Performance\ResourceHint;

$script->withResourceHint(ResourceHint::preload($script->url(), 'script'));
$script->withPreconnect('https://cdn.example.com', ['crossorigin' => true]);
$style->withDnsPrefetch('https://fonts.example.com');
```
