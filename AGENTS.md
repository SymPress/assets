# SymPress Assets

## Scope and entry points

- Start with `docs/getting-started.md`; use the topic guides under `docs/` for public behavior.
- `src/AssetManager.php` orchestrates setup, WordPress hooks, handlers, output filters, and resource hints.
- `src/AssetFactory.php` and the asset value types define the public construction API.
- `src/Bootstrap/` and `inc/bootstrap.php` implement standalone WordPress startup; `bundle/` is the optional kernel adapter.

## Verification

- Fast behavior check: `composer tests`.
- Full required check: `composer qa`.
- Use the stubs in `tests/phpunit/Support` for unit tests; use `tests/Kernel` only for kernel wiring.

## Invariants

- The runtime core must remain usable without `sympress/kernel`; kernel stays an optional integration.
- Asset identity includes handle, type, and URL. Registration happens during `AssetManager::ACTION_SETUP`.
- Keep `src/`, `bundle/`, and `inc/` boundaries intact; do not move standalone behavior into the bundle.
- Do not bypass `src/Security` policies for paths, symlinks, dependency files, or inline content.
- Preserve the documented provenance and migration behavior from `inpsyde/assets` in `NOTICE.md` and `docs/migration.md`.

## Cross-repository impact and done

- `extra.kernel` exposes `AssetsBundle`; consumers may also call the standalone bootstrap directly.
- Changes to provider/configurator interfaces or global helper functions affect downstream packages.
- A change is done when the focused unit or kernel test and `composer qa` pass and the relevant guide remains accurate.
