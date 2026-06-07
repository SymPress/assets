# SymPress Assets

Asset-Management für WordPress-Packages, Plugins und Themes von SymPress.

Das Package registriert klassische Scripts, Script Modules und Styles über eine
Composer-Library. Es läuft standalone in WordPress oder über die optionale
Symfony-Kernel-Integration.

## Installation

```bash
composer require sympress/assets
```

## Features

- Scripts, Script Modules und Styles mit gemeinsamer Asset-API
- Webpack-Manifest-, Encore-, Array- und PHP-File-Loader
- automatische Versionen über Dateipfade oder Dependency-Extraction-Dateien
- native Script-Loading-Strategien: `defer`, `async`, `blocking`
- Style-Preload für nicht-kritisches CSS
- Resource Hints für `preload`, `preconnect` und `dns-prefetch`
- Schutz vor Cache-, Minify-, Combine-, Defer- und Delay-Optimierungen
- optionale Symfony-Kernel-Integration mit Asset-Providern

## Beispiel

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

Klassische Scripts nutzen standardmäßig WordPress' native `defer`-Strategie.
Styles bleiben blockierend, außer `Style::preload()` wird für nicht-kritisches
CSS gesetzt.

## Standalone WordPress

Wenn der Symfony-Kernel nicht genutzt wird, lade `inc/bootstrap.php` einmal nach
dem Composer-Autoloader.

## Lizenz

Dieses Package steht unter `GPL-2.0-or-later`.

Es enthält Code, der von `inpsyde/assets` abgeleitet ist. Details stehen in
[NOTICE.md](./NOTICE.md).
