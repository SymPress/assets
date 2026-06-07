<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Bootstrap;

use Brain\Monkey\Functions;
use SymPress\Assets\AssetManager;
use SymPress\Assets\Bootstrap\AssetBootstrapper;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use SymPress\Assets\Util\AssetHookResolver;
use PHPUnit\Framework\Attributes\Test;

final class AssetBootstrapperTest extends AbstractTestCase
{
    #[Test]
    public function itBootstrapsOnlyOnce(): void
    {
        Functions\when('did_action')->justReturn(false);
        Functions\when('doing_action')->justReturn(false);
        Functions\expect('add_action')->times(3);

        $manager = new AssetManager(new AssetHookResolver(new BootstrapContextStub()));
        $bootstrapper = new AssetBootstrapper();

        self::assertTrue($bootstrapper->bootstrap($manager));
        self::assertFalse($bootstrapper->bootstrap($manager));
    }

    #[Test]
    public function itCanBeResetWithoutSharedGlobalState(): void
    {
        Functions\when('did_action')->justReturn(false);
        Functions\when('doing_action')->justReturn(false);
        Functions\expect('add_action')->times(6);

        $firstManager = new AssetManager(new AssetHookResolver(new BootstrapContextStub()));
        $secondManager = new AssetManager(new AssetHookResolver(new BootstrapContextStub()));
        $bootstrapper = new AssetBootstrapper();

        self::assertTrue($bootstrapper->bootstrap($firstManager));

        $bootstrapper->reset();

        self::assertTrue($bootstrapper->bootstrap($secondManager));
    }

    #[Test]
    public function itCanRetryAfterSetupDidNotRegisterAnyHooks(): void
    {
        Functions\when('did_action')->justReturn(false);
        Functions\when('doing_action')->justReturn(false);
        Functions\expect('add_action')->times(3);

        $inactiveManager = new AssetManager(new AssetHookResolver(new class {
            public function isLogin(): bool
            {
                return false;
            }

            public function isFrontoffice(): bool
            {
                return false;
            }

            public function isBackoffice(): bool
            {
                return false;
            }

            public function isWpActivate(): bool
            {
                return false;
            }
        }));
        $activeManager = new AssetManager(new AssetHookResolver(new BootstrapContextStub()));
        $bootstrapper = new AssetBootstrapper();

        self::assertFalse($bootstrapper->bootstrap($inactiveManager));
        self::assertTrue($bootstrapper->bootstrap($activeManager));
    }
}
