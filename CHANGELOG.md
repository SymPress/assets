# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
where applicable.

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
