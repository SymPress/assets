---
nav_order: 3
---

# Loaders

Loader erzeugen mehrere Assets aus Build- oder PHP-Konfigurationen.

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

Dateien mit `.mjs` oder `.module.js` werden als `ScriptModule` geladen.

## Manifest mit Konfiguration

Manifest-Werte dürfen auch Objekte sein. `filePath` zeigt auf die gebaute Datei,
alle weiteren Keys werden wie `AssetFactory`-Konfiguration behandelt.

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

Chunks aus einem Entrypoint werden in der richtigen Reihenfolge mit
Abhängigkeiten registriert.

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

PHP-Konfigurationen sollten nur aus vertrauenswürdigem Anwendungscode geladen
werden.

## Versionen

Alle Loader können die automatische Versionserkennung deaktivieren:

```php
$loader->disableAutodiscoverVersion();
```
