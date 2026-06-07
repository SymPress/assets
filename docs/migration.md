---
nav_order: 7
---

# Migration

## Von `inpsyde/assets`

Für bestehenden Code sind meist nur wenige Änderungen nötig:

- Composer-Package auf `sympress/assets` ändern.
- Namespace von `Inpsyde\Assets` auf `SymPress\Assets` ändern.
- Standalone-Bootstrap über `inc/bootstrap.php` explizit laden.
- Falls Konfigurationen genutzt werden: `type` enthält die Asset-Klasse,
  `location` enthält den WordPress-Ausgabeort.

```php
<?php

use SymPress\Assets\Asset;
use SymPress\Assets\AssetFactory;
use SymPress\Assets\Style;

$style = AssetFactory::create([
    'type' => Style::class,
    'handle' => 'site',
    'url' => plugin_dir_url(__FILE__) . 'assets/site.css',
    'location' => Asset::FRONTEND,
]);
```

## Interne Änderungen

Diese Variante ergänzt Symfony-Kernel-Integration, sichereres Symlink-Publishing,
Cache-Optimizer-Exclusions, Resource Hints und modernere Script-/Style-Defaults.
