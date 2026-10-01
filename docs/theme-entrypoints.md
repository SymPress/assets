# Local theme entrypoints

`EncoreEntrypointsLoader::fromFile($manifest)` reads and validates an Encore
manifest before building assets. Each entry is validated independently: invalid
optional editor files do not discard a valid frontend entry. Missing/broken
manifests return an empty asset list. CSS and JS entries must have their matching
extension; files must be readable and remain inside the manifest directory after
canonicalization. Absolute paths, remote URLs, traversal and escaping symlinks
are rejected. The existing general-purpose `load()` API remains unchanged.

```php
$loader = (new EncoreEntrypointsLoader())->withDirectoryUrl($themeUrl . '/build/');
$inline = new SmallStyleConfigurator(new FilesystemPathPolicy([$themePath . '/build']));
foreach ($loader->fromFile($themePath . '/build/entrypoints.json') as $asset) {
    $inline->configure($asset);
    $manager->register($asset);
}
foreach ($loader->editorStyles($themePath . '/build/entrypoints.json', 'theme-editor') as $css) {
    add_editor_style($css);
}
```

`editorStyles()` returns relative CSS paths, prefixed with `build/` by default;
the prefix is configurable. Select only the frontend entry handles before
registering assets if the manifest also contains editor entries.

`SmallStyleConfigurator` is opt-in and implements `AssetConfiguratorInterface`.
It only configures CSS filenames containing a hexadecimal hash of at least eight
characters. Files must be within the supplied path policy, no larger than 16 KiB
(configurable), and contain neither `url()` nor `@import`. Existing inline-content
and filesystem policies still apply when WordPress outputs the asset. The helper
has no kernel dependency and works in standalone WordPress.
