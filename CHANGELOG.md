# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
where applicable.

## 1.2.6 - 2026-11-16

- Reject `withCssVars()` values containing `{`, `}`, `;`, `<`, `\` or control characters such as newlines. Such values could close the custom property declaration and inject arbitrary CSS into the inline style. Passing them now throws an `InvalidArgumentException`.

## 1.2.5 - 2026-11-16

- Keep the admin context when the block editor preloads REST data. `rest_api_init` now switches the context to REST only for actual REST requests, so block editor scripts registered for `enqueue_block_editor_assets` load again.

## 1.2.4 - 2026-11-06

- Require Symfony Filesystem 8.1.11 or later for asset path normalization (GHSA-6prc-h956-hxjq).

## 1.2.3 - 2026-10-19

### Fixed

- Avoid PHP stat warnings when probing missing optional asset files, preventing
  an unnecessary gap above the WordPress admin menu when debug display is enabled.
- Preserve any existing PHP error when probing a missing optional asset file.

## 1.2.0 - 2026-10-01

### Added

- `EncoreEntrypointsLoader::fromFile()` validates local manifests, including
  extensions, traversal and symlink confinement, while preserving valid entries
  when optional entries are broken.
- `EncoreEntrypointsLoader::editorStyles()` and `SmallStyleConfigurator` share
  editor stylesheet discovery and opt-in inlining of small hashed CSS files.

### Changed

- Adopt shared SymPress QA tooling for package scripts and development dependencies.

### Fixed

- Normalize script module dependencies to WordPress script module dependency descriptors.
