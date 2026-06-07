---
nav_order: 2
---

# Asset Factory

`AssetFactory` creates individual assets from array configuration. For manifest
files, loaders are usually the better choice.

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

Required fields are `type`, `handle`, and `url`.

## Important Configuration Keys

| Key | Asset | Purpose |
| --- | --- | --- |
| `location` | all | WordPress output location, for example `Asset::FRONTEND` |
| `dependencies` | all | Handle dependencies |
| `filePath` | all | Path for versions and dependency extraction |
| `attributes` | Script, Style | Additional tag attributes |
| `cacheOptimization` | all | `true`, `false`, or `CacheOptimizationExclusion` |
| `resourceHints` | all | `preload`, `preconnect`, `dns-prefetch` |
| `strategy` | Script | `defer`, `async`, or `blocking` |
| `loadingMode` | Style | `blocking` or `preload` |
| `dependencyExtractionEnabled` | Script, ScriptModule | Read `.asset.json` |
| `phpDependencyFiles` | Script, ScriptModule | Allow `.asset.php` |

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
