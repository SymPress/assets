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

Call `loadFromArray()` when the application has already decoded and validated
the manifest, for example after filtering invalid optional entrypoints:

```php
$assets = (new EncoreEntrypointsLoader())
    ->withDirectoryUrl('https://example.com/theme/build/')
    ->loadFromArray([
        'entrypoints' => [
            'theme' => ['css' => ['css/app.css'], 'js' => ['js/app.js']],
        ],
    ], __DIR__ . '/build/entrypoints.json');
```

The second argument supplies the manifest path used to resolve asset paths. It
does not trigger a file read and the manifest need not exist. The caller must
validate the input shape and apply any application-specific path restrictions.
The loader preserves the file loader's handles, chunk dependencies, URL mapping
and version-discovery settings. Existing file-access and inline-content policies
still apply when the assets are consumed. Consumers do not need to subclass the
loader or call its protected parsing method.

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
