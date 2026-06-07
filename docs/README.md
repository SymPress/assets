---
title: Home
nav_order: 0
permalink: /
---

# SymPress Assets

A small Composer library for WordPress assets: scripts, script modules, and
styles. The package can run directly in WordPress or be wired through the
optional Symfony kernel integration.

## Features

- Asset API for scripts, script modules, and styles
- Loaders for Webpack manifests, Encore entrypoints, arrays, and PHP files
- Dependency extraction for WordPress builds
- Resource hints and style preload
- Cache optimizer exclusions for common performance plugins
- Kernel integration with asset providers and configurators

## Installation

```bash
composer require sympress/assets
```

## Start Here

- [Getting started](./getting-started.md)
- [Assets](./assets.md)
- [Loaders](./loaders.md)
- [Helpers](./helpers.md)

## License

The package is licensed under `GPL-2.0-or-later`. Origin and change notes are
available in [`NOTICE.md`](../NOTICE.md).
