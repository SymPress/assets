---
nav_order: 5
---

# Output Filters

Output filters modify the final `script` or `link` tag.

```php
<?php

use SymPress\Assets\Script;

$script = (new Script('site', plugin_dir_url(__FILE__) . 'assets/site.js'))
    ->withAttributes(['crossorigin' => 'anonymous']);
```

## Included Filters

- `AttributesOutputFilter` sets additional attributes.
- `InlineAssetOutputFilter` emits small, allowed CSS or JS files inline.
- `AsyncStyleOutputFilter` loads non-critical CSS through `preload`.

`Style::preload()` sets `AsyncStyleOutputFilter` automatically and creates a
`noscript` fallback. Existing attributes such as `media`, `integrity`,
`crossorigin`, and `fetchpriority` are preserved.

For scripts, `AsyncScriptOutputFilter` and `DeferScriptOutputFilter` are
deprecated. Use `Script::async()` or `Script::defer()` instead.

## Custom Filter

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
