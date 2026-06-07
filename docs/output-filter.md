---
nav_order: 5
---

# Output Filter

Output Filter verändern das finale `script`- oder `link`-Tag.

```php
<?php

use SymPress\Assets\Script;

$script = (new Script('site', plugin_dir_url(__FILE__) . 'assets/site.js'))
    ->withAttributes(['crossorigin' => 'anonymous']);
```

## Mitgelieferte Filter

- `AttributesOutputFilter` setzt zusätzliche Attribute.
- `InlineAssetOutputFilter` gibt kleine, erlaubte CSS- oder JS-Dateien inline aus.
- `AsyncStyleOutputFilter` lädt nicht-kritisches CSS per `preload`.

`Style::preload()` setzt `AsyncStyleOutputFilter` automatisch und erzeugt einen
`noscript`-Fallback. Bestehende Attribute wie `media`, `integrity`,
`crossorigin` und `fetchpriority` bleiben erhalten.

Für Scripts sind `AsyncScriptOutputFilter` und `DeferScriptOutputFilter`
deprecated. Nutze stattdessen `Script::async()` oder `Script::defer()`.

## Eigener Filter

```php
<?php

use SymPress\Assets\Asset;
use SymPress\Assets\Script;

$filter = static function (string $html, Asset $asset): string {
    return str_replace('></script>', ' data-handle="' . esc_attr($asset->handle()) . '"></script>', $html);
};

$script = (new Script('site', plugin_dir_url(__FILE__) . 'assets/site.js'))
    ->withFilters($filter);
```
