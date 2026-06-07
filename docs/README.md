---
title: Home
nav_order: 0
permalink: /
---

# SymPress Assets

Kleine Composer-Library für WordPress-Assets: Scripts, Script Modules und
Styles. Das Package kann direkt in WordPress laufen oder über die optionale
Symfony-Kernel-Integration eingebunden werden.

## Features

- Asset-API für Scripts, Script Modules und Styles
- Loader für Webpack Manifest, Encore Entry Points, Arrays und PHP-Dateien
- Dependency Extraction für WordPress-Builds
- Resource Hints und Style-Preload
- Cache-Optimizer-Exclusions für gängige Performance-Plugins
- Kernel-Integration mit Asset-Providern und Configurators

## Installation

```bash
composer require sympress/assets
```

## Einstieg

- [Getting started](./getting-started.md)
- [Assets](./assets.md)
- [Loaders](./loaders.md)
- [Helpers](./helpers.md)

## Lizenz

Das Package steht unter `GPL-2.0-or-later`. Herkunfts- und Änderungshinweise
stehen in [`NOTICE.md`](../NOTICE.md).
