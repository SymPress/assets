---
nav_order: 4
---

# Assets

Das Package liefert drei Asset-Klassen:

- `Script` für klassische JavaScript-Dateien
- `ScriptModule` für WordPress Script Modules
- `Style` für CSS-Dateien

Alle Assets brauchen `handle`, `url` und optional eine `location`.

```php
<?php

use SymPress\Assets\Asset;
use SymPress\Assets\Script;
use SymPress\Assets\Style;

$script = new Script('site', plugin_dir_url(__FILE__) . 'assets/site.js', Asset::FRONTEND);
$style = new Style('site', plugin_dir_url(__FILE__) . 'assets/site.css', Asset::FRONTEND);
```

## Gemeinsame Optionen

| Option | Zweck |
| --- | --- |
| `withDependencies()` | WordPress-Handles als Abhängigkeiten setzen |
| `withVersion()` | feste Version setzen |
| `withFilePath()` | Dateipfad für automatische Versionserkennung setzen |
| `canEnqueue()` | Asset nur bedingt enqueuen |
| `forLocation()` | Ausgabeort setzen |
| `withAttributes()` | zusätzliche HTML-Attribute setzen |
| `excludeFromCacheOptimization()` | Optimizer-Ausnahmen setzen |
| `withResourceHint()` | Resource Hint ergänzen |

```php
$script
    ->withDependencies('wp-element')
    ->withVersion('1.0.0')
    ->canEnqueue(static fn (): bool => !is_admin());
```

## Locations

Locations können kombiniert werden:

```php
$style->forLocation(Asset::FRONTEND | Asset::BLOCK_EDITOR_ASSETS);
```

Wichtige Konstanten sind `FRONTEND`, `BACKEND`, `LOGIN`, `BLOCK_ASSETS` und
`BLOCK_EDITOR_ASSETS`.

## Scripts

Klassische Scripts nutzen standardmäßig WordPress' native `defer`-Strategie.

```php
$script
    ->defer()
    ->isInFooter()
    ->withLocalize('SiteData', ['restUrl' => rest_url()])
    ->appendInlineScript('window.SiteReady = true;');
```

Für unabhängige Scripts ist `async()` möglich. Für absichtlich blockierende
Scripts gibt es `blocking()`.

## Script Modules

`ScriptModule` registriert ES-Module über die WordPress Script Modules API.

```php
use SymPress\Assets\ScriptModule;

$module = (new ScriptModule('@site/gallery', plugin_dir_url(__FILE__) . 'assets/gallery.js'))
    ->withData(['view' => 'gallery']);
```

## Styles

Styles bleiben standardmäßig blockierend. Nicht-kritisches CSS kann vorgeladen
werden.

```php
$style
    ->forMedia('screen')
    ->preload()
    ->withInlineStyles('.site-header{position:sticky;}')
    ->withCssVars(':root', ['brand' => '#111']);
```

## Dependency Extraction

Scripts und Script Modules lesen passende `.asset.json`-Dateien automatisch,
wenn `withFilePath()` gesetzt ist. `.asset.php` ist aus Sicherheitsgründen nur
aktiv, wenn PHP-Dependency-Dateien explizit erlaubt werden.

```php
$script
    ->withFilePath(__DIR__ . '/assets/editor.js')
    ->withDependencyExtraction()
    ->withPhpDependencyFiles(false)
    ->withDependencyFileSizeLimit(65536);
```

## Cache-Optimierung

Ein Asset kann sich gegen aggressive Optimizer schützen:

```php
use SymPress\Assets\CacheOptimization\CacheOptimizationExclusion;

$script->excludeFromCacheOptimization();
$style->excludeFromCacheOptimization(CacheOptimizationExclusion::minifyAndCombine());
```

`AssetManager::ignoreCache()` ist ein breiter Shortcut für registrierte Assets,
deaktiviert aber nicht pauschal alle Defer-, Delay- oder Async-Optimierungen.

Unterstützt werden Filter für WP Rocket, SiteGround Speed Optimizer, W3 Total
Cache, Autoptimize und LiteSpeed Cache. Zusätzlich werden defensive Attribute
für weitere Optimizer gesetzt.

## Resource Hints

```php
use SymPress\Assets\Performance\ResourceHint;

$script->withResourceHint(ResourceHint::preload($script->url(), 'script'));
$script->withPreconnect('https://cdn.example.com', ['crossorigin' => true]);
$style->withDnsPrefetch('https://fonts.example.com');
```
