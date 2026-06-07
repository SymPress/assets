---
nav_order: 3
---

# Loaders

Loaders create multiple assets from build or PHP configuration.

## Webpack Manifest

```json
{
  "site.js": "/assets/site.123.js",
  "site.css": "/assets/site.123.css",
  "gallery.module.js": "/assets/gallery.123.module.js"
}
```

```php
<?php

use SymPress\Assets\Loader\WebpackManifestLoader;

$loader = (new WebpackManifestLoader())
    ->withDirectoryUrl(plugin_dir_url(__FILE__));

$assets = $loader->load(__DIR__ . '/manifest.json');
```

Files ending in `.mjs` or `.module.js` are loaded as `ScriptModule`.

## Manifest with Configuration

Manifest values may also be objects. `filePath` points to the built file, and
all other keys are treated like `AssetFactory` configuration.

```json
{
  "site": {
    "filePath": "/assets/site.123.js",
    "location": ["frontend"],
    "strategy": "defer",
    "cacheOptimization": true,
    "resourceHints": [
      { "rel": "preload", "as": "script" }
    ]
  }
}
```

## Encore Entrypoints

```php
<?php

use SymPress\Assets\Loader\EncoreEntrypointsLoader;

$assets = (new EncoreEntrypointsLoader())
    ->load(__DIR__ . '/entrypoints.json');
```

Chunks from an entrypoint are registered in the correct order with
dependencies.

## Array Loader

```php
<?php

use SymPress\Assets\Asset;
use SymPress\Assets\Loader\ArrayLoader;
use SymPress\Assets\Script;

$assets = (new ArrayLoader())->load([
    [
        'type' => Script::class,
        'handle' => 'site',
        'url' => plugin_dir_url(__FILE__) . 'assets/site.js',
        'location' => Asset::FRONTEND,
    ],
]);
```

## PHP File Loader

```php
<?php

use SymPress\Assets\Loader\PhpFileLoader;

$assets = (new PhpFileLoader())
    ->load(__DIR__ . '/config/assets.php');
```

PHP configuration should only be loaded from trusted application code.

## Versions

All loaders can disable automatic version detection:

```php
$loader->disableAutodiscoverVersion();
```
