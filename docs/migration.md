---
nav_order: 7
---

# Migration

## From `inpsyde/assets`

Existing code usually only needs a few changes:

- Change the Composer package to `sympress/assets`.
- Change the namespace from `Inpsyde\Assets` to `SymPress\Assets`.
- Explicitly load the standalone bootstrap through `inc/bootstrap.php`.
- If configuration arrays are used, `type` contains the asset class and
  `location` contains the WordPress output location.

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

## Internal Changes

This variant adds Symfony kernel integration, safer symlink publishing, cache
optimizer exclusions, resource hints, and more modern script and style defaults.
