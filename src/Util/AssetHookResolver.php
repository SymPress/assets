<?php

declare(strict_types=1);

namespace SymPress\Assets\Util;

use SymPress\Assets\Asset;

final readonly class AssetHookResolver
{
    private object $context;

    /** @param object|null $context any context object exposing the WpContext query methods */
    public function __construct(?object $context = null)
    {
        $this->context = $context ?? self::determineContext();
    }

    /**
     * Resolving to the current location/page in WordPress all current hooks.
     *
     * @return array<string>
     */
    public function resolve(): array
    {
        $isLogin = $this->contextFlag('isLogin');
        $isFront = $this->contextFlag('isFrontoffice');
        $isActivate = $this->contextFlag('isWpActivate');

        if (!$isActivate && !$isLogin && !$isFront && !$this->contextFlag('isBackoffice')) {
            return [];
        }

        if ($isLogin) {
            return [Asset::HOOK_LOGIN];
        }

        if ($isActivate) {
            return [Asset::HOOK_ACTIVATE];
        }

        // These hooks might be fired in both front and back office.
        $assets = [Asset::HOOK_BLOCK_ASSETS];

        if ($isFront) {
            $assets[] = Asset::HOOK_FRONTEND;
            $assets[] = Asset::HOOK_CUSTOMIZER_PREVIEW;

            return $assets;
        }

        $assets[] = Asset::HOOK_BLOCK_EDITOR_ASSETS;
        $assets[] = Asset::HOOK_CUSTOMIZER;
        $assets[] = Asset::HOOK_BACKEND;

        return $assets;
    }

    public function lastHook(): ?string
    {
        return match (true) {
            $this->contextFlag('isLogin') => Asset::HOOK_LOGIN,
            $this->contextFlag('isFrontoffice') => Asset::HOOK_FRONTEND,
            $this->contextFlag('isBackoffice') => Asset::HOOK_BACKEND,
            $this->contextFlag('isWpActivate') => Asset::HOOK_ACTIVATE,
            default => null,
        };
    }

    private static function determineContext(): object
    {
        return WordPressContext::determine();
    }

    private function contextFlag(string $method): bool
    {
        $callback = [$this->context, $method];

        if (!is_callable($callback)) {
            throw new \LogicException(sprintf('WordPress context method "%s" is not callable.', $method));
        }

        return (bool) $callback();
    }
}
