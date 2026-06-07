---
nav_order: 2
---

# Asset Factory

`AssetFactory` erstellt einzelne Assets aus Array-Konfigurationen. Für
Manifest-Dateien sind die Loader meist die bessere Wahl.

```php
<?php

use SymPress\Assets\Asset;
use SymPress\Assets\AssetFactory;
use SymPress\Assets\Script;
use SymPress\Assets\Style;

$script = AssetFactory::create([
    'type' => Script::class,
    'handle' => 'site',
    'url' => plugin_dir_url(__FILE__) . 'assets/site.js',
    'location' => Asset::FRONTEND,
    'strategy' => 'defer',
]);

$style = AssetFactory::create([
    'type' => Style::class,
    'handle' => 'site',
    'url' => plugin_dir_url(__FILE__) . 'assets/site.css',
    'location' => Asset::FRONTEND,
    'loadingMode' => 'blocking',
]);
```

Pflichtfelder sind `type`, `handle` und `url`.

## Wichtige Config-Keys

| Key | Asset | Zweck |
| --- | --- | --- |
| `location` | alle | WordPress-Ausgabeort, z.B. `Asset::FRONTEND` |
| `dependencies` | alle | Handle-Abhängigkeiten |
| `filePath` | alle | Pfad für Version und Dependency Extraction |
| `attributes` | Script, Style | zusätzliche Tag-Attribute |
| `cacheOptimization` | alle | `true`, `false` oder `CacheOptimizationExclusion` |
| `resourceHints` | alle | `preload`, `preconnect`, `dns-prefetch` |
| `strategy` | Script | `defer`, `async` oder `blocking` |
| `loadingMode` | Style | `blocking` oder `preload` |
| `dependencyExtractionEnabled` | Script, ScriptModule | `.asset.json` lesen |
| `phpDependencyFiles` | Script, ScriptModule | `.asset.php` erlauben |

```php
$script = AssetFactory::create([
    'type' => Script::class,
    'handle' => 'editor',
    'url' => plugin_dir_url(__FILE__) . 'assets/editor.js',
    'filePath' => __DIR__ . '/assets/editor.js',
    'dependencies' => ['wp-element'],
    'cacheOptimization' => true,
    'resourceHints' => [
        ['rel' => 'preload', 'as' => 'script'],
    ],
]);
```
