---
nav_order: 6
---

# Helpers

Das Package lädt nur kleine Helper-Funktionen automatisch.

## Asset Suffix

`withAssetSuffix()` ergänzt `.min`, wenn `SCRIPT_DEBUG` nicht aktiv ist.

```php
<?php

use function SymPress\Assets\withAssetSuffix;

$file = withAssetSuffix('site.js'); // site.min.js
```

Ohne Dateiendung bleibt der Wert unverändert.

## Symlinked Asset Folder

`symlinkedAssetFolder()` veröffentlicht erlaubte Asset-Ordner unter
`wp-content/~sympress-assets/`.

```php
<?php

use function SymPress\Assets\symlinkedAssetFolder;

$url = symlinkedAssetFolder(
    '/project/packages/acme/assets/dist',
    'acme-assets',
    ['/project/packages/acme/assets'],
);
```

Übergib nur öffentliche Build-Assets, keine Package- oder Source-Verzeichnisse.

Der Helper prüft erlaubte Basisverzeichnisse, Dateitypen und Symlink-Ziele. Für
Composer-Packages außerhalb des Webroots sollte immer ein enger erlaubter
Basisordner übergeben werden.
