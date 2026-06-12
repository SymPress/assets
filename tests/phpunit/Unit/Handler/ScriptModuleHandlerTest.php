<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Handler;

use Brain\Monkey\Functions;
use SymPress\Assets\Handler\AssetHandler;
use SymPress\Assets\Handler\ScriptModuleHandler;
use SymPress\Assets\Script;
use SymPress\Assets\ScriptModule;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class ScriptModuleHandlerTest extends AbstractTestCase
{
    /** @test */
    public function testBasic(): void
    {
        $handler = new ScriptModuleHandler();

        static::assertInstanceOf(AssetHandler::class, $handler);
    }

    /** @test */
    public function testRegisterEnqueue(): void
    {
        $handler = new class extends ScriptModuleHandler {
            protected static function scriptModulesSupported(): bool
            {
                return true;
            }
        };

        $scriptModule = (new ScriptModule('@my-plugin/module', 'module.js'))
            ->withVersion('1.0.0')
            ->withDependencies('@wordpress/interactivity', '@wordpress/element');

        Functions\expect('wp_register_script_module')
            ->once()
            ->andReturnUsing(
                static function (
                    string $id,
                    string $src,
                    array $deps,
                    ?string $version,
                ): void {
                    static::assertSame('@my-plugin/module', $id);
                    static::assertSame('module.js', $src);
                    static::assertSame(['@wordpress/interactivity', '@wordpress/element'], $deps);
                    static::assertSame('1.0.0', $version);
                },
            );

        Functions\expect('wp_enqueue_script_module')
            ->once()
            ->with('@my-plugin/module');

        $result = $handler->enqueue($scriptModule);

        static::assertTrue($result);
    }

    /** @test */
    public function testSkipEnqueueIfScriptModulesNotSupported(): void
    {
        $handler = new ScriptModuleHandler();

        $scriptModule = (new ScriptModule('@my-plugin/module', 'module.js'))
            ->withVersion('1.0.0')
            ->withDependencies('@wordpress/interactivity');

        Functions\expect('wp_register_script_module')->never();
        Functions\expect('wp_enqueue_script_module')->never();

        $result = $handler->enqueue($scriptModule);

        static::assertFalse($result);
    }

    public function testSkipRegisterIfScriptModulesNotSupported(): void
    {
        $handler = new ScriptModuleHandler();

        $scriptModule = (new ScriptModule('@my-plugin/module', 'module.js'))
            ->withVersion('1.0.0')
            ->withDependencies('@wordpress/interactivity');

        Functions\expect('wp_register_script_module')->never();
        Functions\expect('wp_enqueue_script_module')->never();

        $result = $handler->register($scriptModule);

        static::assertFalse($result);
    }

    public function testSkipNonScriptModuleRegistration(): void
    {
        $handler = new ScriptModuleHandler();
        $scriptModule = new Script('@my-plugin/script', 'script.js');

        Functions\expect('wp_register_script_module')->never();

        $result = $handler->register($scriptModule);

        static::assertFalse($result);
    }

    public function testSkipScriptModulesWithoutHandle(): void
    {
        $handler = new class extends ScriptModuleHandler {
            protected static function scriptModulesSupported(): bool
            {
                return true;
            }
        };
        $scriptModule = new ScriptModule('', 'module.js');

        Functions\expect('wp_register_script_module')->never();
        Functions\expect('wp_enqueue_script_module')->never();

        static::assertFalse($handler->register($scriptModule));
        static::assertFalse($handler->enqueue($scriptModule));
    }

    public function testDataSharedViaFilter(): void
    {
        $handler = new class extends ScriptModuleHandler {
            protected static function scriptModulesSupported(): bool
            {
                return true;
            }
        };

        $scriptModule = (new ScriptModule('@my-plugin/module', 'module.js'))
            ->withVersion('1.0.0')
            ->withDependencies('@wordpress/interactivity')
            ->withData(['key' => 'value']);

        Functions\expect('wp_register_script_module')->once();
        $callback = null;
        Functions\expect('add_filter')
            ->once()
            ->andReturnUsing(static function (
                string $hook,
                callable $filter,
                int $priority,
                int $acceptedArgs,
            ) use (&$callback): bool {
                static::assertSame('script_module_data_@my-plugin/module', $hook);
                static::assertSame(PHP_INT_MAX - 10, $priority);
                static::assertSame(1, $acceptedArgs);
                $callback = $filter;

                return true;
            });

        $result = $handler->register($scriptModule);

        static::assertTrue($result);
        static::assertIsCallable($callback);
        static::assertSame(
            ['existing' => true, 'key' => 'value'],
            $callback(['existing' => true]),
        );
    }
}
