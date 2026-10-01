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

`Style::preload()` requests `AsyncStyleOutputFilter`. Supply the page's intentional
per-request CSP nonce through `sympress_assets_csp_nonce` (value, asset). When a
nonempty nonce and WP_HTML_Tag_Processor are available, output contains a preload
link plus a nonce-bearing script using addEventListener, with no onload attribute.
The nonce must match the page's script-src policy; putting a nonce on a link does
not authorize inline event handlers. The script handles already completed/cache-hit
loads, and a noscript copy keeps the original stylesheet. Existing media, integrity,
crossorigin and fetchpriority are preserved. Without a nonce the original blocking
stylesheet is returned, so strict script-src-attr 'none' remains supported.

Inline files must satisfy the allowed canonical path/extensions and 32KB limit,
including the actual read bytes. Raw JavaScript containing closing/opening script
prefixes, HTML comment openings, or U+2028/U+2029 falls back to its external tag.
There is no semantics-preserving universal rewrite for strings, regexps and tagged
templates; external fallback keeps the exact program bytes. CSS closing style
prefixes are escaped case-insensitively. Inline output retains nonce and removes
external-only integrity/src/loading attributes.

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

## Supplied SRI and request snapshots

Loader configuration and dependency JSON may provide integrity (space-separated
sha256/384/512 base64 digests) and crossorigin (anonymous/use-credentials). Digest
format/decoded length is validated and anonymous is the default. Hashes must come
from a trusted build for the exact bytes served. The library does not synthesize
integrity from URLs/versions or fetch remote files; no remote integrity guarantee
is possible without supplied build metadata. Inline tags omit external integrity.

RequestFiles shares canonical path/stat/content/JSON snapshots across assets and
loaders. Repeated reads and missing paths reuse a request snapshot, while allowed
root policy decisions remain local to their policy context. Call
SymPress\Assets\IO\RequestFiles::reset() at **every** request boundary in a
long-running worker, and recreate per-request asset objects. Normal PHP request
teardown already clears this memory. New/changed manifests and symlink targets
are observed after reset; do not modify build files partway through a request.
PHP dependency execution remains separately opt-in and size-bounded.
