<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Util;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use SymPress\Assets\Util\WordPressContext;

class WordPressContextTest extends AbstractTestCase
{
    /** @var array<string, callable> */
    private array $callbacks = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->callbacks = [];
        Functions\when('wp_installing')->justReturn(false);
        Functions\when('wp_doing_ajax')->justReturn(false);
        Functions\when('wp_doing_cron')->justReturn(false);
        Functions\when('is_admin')->justReturn(true);
        Actions\expectAdded('rest_api_init')->whenHappen(function (callable $callback): void {
            $this->callbacks['rest_api_init'] = $callback;
        });
        unset($_GET['rest_route']);
    }

    protected function tearDown(): void
    {
        unset($_GET['rest_route']);
        parent::tearDown();
    }

    public function testBlockEditorRestPreloadKeepsTheAdminContext(): void
    {
        $context = WordPressContext::determine();
        static::assertTrue($context->isBackoffice());

        // edit-form-blocks.php preloads REST responses, which fires rest_api_init inside the admin request.
        ($this->callbacks['rest_api_init'])();

        static::assertTrue($context->isBackoffice());
    }

    public function testARestRouteQueryInAnAdminRequestKeepsTheAdminContext(): void
    {
        $context = WordPressContext::determine();
        $_GET['rest_route'] = '/wp/v2/posts';

        ($this->callbacks['rest_api_init'])();

        static::assertTrue($context->isBackoffice());
    }

    public function testAnActualRestRequestStillSwitchesToRest(): void
    {
        Functions\when('is_admin')->justReturn(false);
        $context = WordPressContext::determine();
        static::assertTrue($context->isFrontoffice());
        $_GET['rest_route'] = '/wp/v2/posts';

        ($this->callbacks['rest_api_init'])();

        static::assertFalse($context->isBackoffice());
        static::assertFalse($context->isFrontoffice());
    }
}
