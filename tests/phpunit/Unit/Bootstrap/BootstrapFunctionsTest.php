<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Bootstrap;

use Brain\Monkey\Functions;
use SymPress\Assets\AssetManager;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use SymPress\Assets\Util\AssetHookResolver;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;

final class BootstrapFunctionsTest extends AbstractTestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itReusesTheStandaloneBootstrapperInstance(): void
    {
        require_once (string) getenv('LIB_DIR') . '/inc/bootstrap.php';

        Functions\when('did_action')->justReturn(false);
        Functions\when('doing_action')->justReturn(false);
        Functions\expect('add_action')->times(3);

        $manager = new AssetManager(new AssetHookResolver(new BootstrapContextStub()));

        self::assertTrue(\SymPress\Assets\bootstrap($manager));
        self::assertFalse(\SymPress\Assets\bootstrap($manager));
    }
}
