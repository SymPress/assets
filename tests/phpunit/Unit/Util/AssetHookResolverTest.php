<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Util;

use SymPress\Assets\Asset;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use SymPress\Assets\Util\AssetHookResolver;

class AssetHookResolverTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function testResolveNothingWhenNotNeeded(): void
    {
        $hookResolver = new AssetHookResolver(new ContextStub());

        static::assertSame([], $hookResolver->resolve());
    }

    /**
     * @test
     */
    public function testResolveActivate(): void
    {
        $context = new ContextStub(isWpActivate: true);
        $hookResolver = new AssetHookResolver($context);

        static::assertSame([Asset::HOOK_ACTIVATE], $hookResolver->resolve());
    }

    /**
     * @test
     */
    public function testResolveLogin(): void
    {
        $context = new ContextStub(isLogin: true);
        $hookResolver = new AssetHookResolver($context);

        static::assertSame([Asset::HOOK_LOGIN], $hookResolver->resolve());
    }

    /**
     * @test
     */
    public function testResolveFrontend(): void
    {
        $context = new ContextStub(isFrontoffice: true);
        $hookResolver = new AssetHookResolver($context);

        static::assertSame(
            [Asset::HOOK_BLOCK_ASSETS, Asset::HOOK_FRONTEND, Asset::HOOK_CUSTOMIZER_PREVIEW],
            $hookResolver->resolve(),
        );
    }

    /**
     * @test
     */
    public function testResolveBackend(): void
    {
        $context = new ContextStub(isBackoffice: true);
        $hookResolver = new AssetHookResolver($context);

        static::assertSame(
            [
                Asset::HOOK_BLOCK_ASSETS,
                Asset::HOOK_BLOCK_EDITOR_ASSETS,
                Asset::HOOK_CUSTOMIZER,
                Asset::HOOK_BACKEND,
            ],
            $hookResolver->resolve(),
        );
    }

    /**
     * @test
     *
     * @dataProvider provideLastHook
     */
    public function testResolveLastHook(ContextStub $context, $expected): void
    {
        $hookResolver = new AssetHookResolver($context);

        static::assertSame(
            $expected,
            $hookResolver->lastHook(),
        );
    }

    public static function provideLastHook(): \Generator
    {
        yield 'not matching' => [
            new ContextStub(),
            null,
        ];

        yield 'login' => [
            new ContextStub(isLogin: true),
            Asset::HOOK_LOGIN,
        ];

        yield 'frontend' => [
            new ContextStub(isFrontoffice: true),
            Asset::HOOK_FRONTEND,
        ];

        yield 'backend' => [
            new ContextStub(isBackoffice: true),
            Asset::HOOK_BACKEND,
        ];

        yield 'wp-activate.php' => [
            new ContextStub(isWpActivate: true),
            Asset::HOOK_ACTIVATE,
        ];
    }
}
