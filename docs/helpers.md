---
nav_order: 6
---

# Helpers

The package only loads small helper functions automatically.

## Asset Suffix

`withAssetSuffix()` appends `.min` when `SCRIPT_DEBUG` is not enabled.

```php
<?php

use function SymPress\Assets\withAssetSuffix;

$file = withAssetSuffix('site.js'); // site.min.js
```

Values without a file extension are returned unchanged.

## Symlinked Asset Folder

`symlinkedAssetFolder()` publishes allowed asset folders under
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

Only pass public build assets, not package or source directories.

The helper validates allowed base directories, file types, and symlink targets.
For Composer packages outside the webroot, always pass a narrow allowed base
directory.
