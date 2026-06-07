<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Handler;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use SymPress\Assets\Asset;
use SymPress\Assets\Handler\AssetHandler;
use SymPress\Assets\Handler\OutputFilterAwareAssetHandler;
use SymPress\Assets\Handler\ScriptHandler;
use SymPress\Assets\OutputFilter\AsyncScriptOutputFilter;
use SymPress\Assets\OutputFilter\DeferScriptOutputFilter;
use SymPress\Assets\OutputFilter\InlineAssetOutputFilter;
use SymPress\Assets\Script;
use SymPress\Assets\ScriptLoadingStrategy;
use SymPress\Assets\Style;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class ScriptHandlerTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function testBasic(): void
    {
        $scriptsStub = \Mockery::mock('\WP_Scripts');
        $script = new ScriptHandler($scriptsStub);

        static::assertInstanceOf(AssetHandler::class, $script);
        static::assertInstanceOf(OutputFilterAwareAssetHandler::class, $script);
        static::assertSame('script_loader_tag', $script->filterHook());
    }

    /**
     * @test
     */
    public function testRegisterEnqueue(): void
    {
        $data = ['baz' => 'bam'];
        $localize = ['foo' => 'bar'];
        $inline = ['before' => 'before()', 'after' => 'after()'];

        $script = (new Script('handle', 'url', Asset::FRONTEND))
            ->withVersion('version')
            ->isInFooter()
            ->withTranslation('i10n', 'i10n.json')
            ->prependInlineScript($inline['before'])
            ->appendInlineScript($inline['after'])
            ->withLocalize('localize', $localize)
            ->withData($data);

        Functions\expect('wp_register_script')
            ->once()
            ->andReturnUsing(
                static function (
                    string $handle,
                    string $src,
                    array $deps,
                    string $ver,
                    array $args,
                ): bool {
                    static::assertSame('handle', $handle);
                    static::assertSame('url', $src);
                    static::assertSame([], $deps);
                    static::assertSame('version', $ver);
                    static::assertSame(
                        [
                            'in_footer' => true,
                            'strategy' => ScriptLoadingStrategy::DEFER,
                        ],
                        $args,
                    );

                    return true;
                },
            );

        Functions\expect('wp_add_inline_script')
            ->twice()
            ->andReturnUsing(
                static function (string $handle, string $code, string $where) use ($inline): void {
                    static::assertSame('handle', $handle);
                    static::assertContains($where, ['before', 'after']);
                    static::assertSame($inline[$where], $code);
                },
            );

        Functions\expect('wp_set_script_translations')
            ->once()
            ->andReturnUsing(
                static function (string $handle, string $domain, string $path) {
                    static::assertSame('handle', $handle);
                    static::assertSame('i10n', $domain);
                    static::assertSame('i10n.json', $path);
                },
            );

        Functions\expect('wp_localize_script')
            ->once()
            ->andReturnUsing(
                static function (string $handle, string $name, array $data) use ($localize): void {
                    static::assertSame('handle', $handle);
                    static::assertSame('localize', $name);
                    static::assertSame($localize, $data);
                },
            );

        Functions\expect('wp_enqueue_script')->once()->with('handle');

        $scriptsStub = \Mockery::mock('\WP_Scripts');
        $scriptsStub->shouldReceive('add_data')
            ->once()
            ->andReturnUsing(
                static function (string $handle, string $key, string $value) use ($data): void {
                    static::assertSame('handle', $handle);
                    static::assertSame($key, key($data));
                    static::assertSame($value, reset($data));
                },
            );

        static::assertTrue((new ScriptHandler($scriptsStub))->enqueue($script));
    }

    /**
     * @test
     */
    public function testEnqueueNotTrue(): void
    {
        $script = (new Script('handle', 'url'))->canEnqueue('__return_false');

        Functions\when('wp_register_script')->justReturn();
        Functions\expect('wp_localize_script')->never();
        Functions\expect('wp_enqueue_script')->never();

        $scriptsStub = \Mockery::mock('\WP_Scripts');

        static::assertFalse((new ScriptHandler($scriptsStub))->enqueue($script));
    }

    public function testRegisterBlockingScriptWithoutStrategyArgument(): void
    {
        $script = (new Script('handle', 'url'))->blocking();

        Functions\expect('wp_register_script')
            ->once()
            ->andReturnUsing(
                static function (
                    string $handle,
                    string $src,
                    array $deps,
                    ?string $ver,
                    array $args,
                ): void {
                    static::assertSame('handle', $handle);
                    static::assertSame('url', $src);
                    static::assertSame([], $deps);
                    static::assertNull($ver);
                    static::assertSame(['in_footer' => true], $args);
                },
            );

        static::assertTrue((new ScriptHandler(\Mockery::mock('\WP_Scripts')))->register($script));
    }

    public function testSkipsNonScriptAssets(): void
    {
        $handler = new ScriptHandler(\Mockery::mock('\WP_Scripts'));
        $style = new Style('style', 'style.css');

        Functions\expect('wp_register_script')->never();
        Functions\expect('wp_enqueue_script')->never();

        static::assertFalse($handler->register($style));
        static::assertFalse($handler->enqueue($style));
    }

    public function testLocalizeCallablesAreResolvedOnlyOnceDuringRegistration(): void
    {
        $calls = 0;
        $script = (new Script('handle', 'url'))
            ->withLocalize(
                'config',
                static function () use (&$calls): array {
                    ++$calls;

                    return ['enabled' => true];
                },
            );

        Functions\expect('wp_register_script')->once();
        Functions\expect('wp_localize_script')->once()->with('handle', 'config', ['enabled' => true]);

        static::assertTrue((new ScriptHandler(\Mockery::mock('\WP_Scripts')))->register($script));
        static::assertSame(1, $calls);
    }

    /**
     * @test
     */
    public function testFilter(): void
    {
        $return = static function (string $html): string {
            return $html;
        };

        $scriptNoFilters = new Script('a', '');
        $scriptFilterNotCallable = (new Script('b', ''))->withFilters('do not call me');
        $scriptFilterCallable = (new Script('c', ''))->withFilters($return);
        $scriptFilterDefault = (new Script('d', ''))->withFilters(__METHOD__);

        $script = new ScriptHandler(
            \Mockery::mock('\WP_Scripts'),
            [__METHOD__ => $return],
        );

        Filters\expectAdded($script->filterHook())->once();

        static::assertFalse($script->filter($scriptNoFilters));
        static::assertFalse($script->filter($scriptFilterNotCallable));
        static::assertTrue($script->filter($scriptFilterCallable));
        static::assertTrue($script->filter($scriptFilterDefault));
    }

    public function testFilterRegistrationIsDeduplicatedPerAsset(): void
    {
        $asset = (new Script('asset', ''))->withFilters(static fn (string $html): string => $html);
        $script = new ScriptHandler(\Mockery::mock('\WP_Scripts'));

        Filters\expectAdded($script->filterHook())->once();

        static::assertTrue($script->filter($asset));
        static::assertFalse($script->filter($asset));
    }

    public function testFilterRegistrationRejectsDuplicateHandlesWithDifferentAssetClasses(): void
    {
        $filter = static fn (string $html): string => $html;
        $first = (new Script('asset', ''))->withFilters($filter);
        $second = (new class('asset', '') extends Script {
        })->withFilters($filter);
        $script = new ScriptHandler(\Mockery::mock('\WP_Scripts'));

        Filters\expectAdded($script->filterHook())->once();

        static::assertTrue($script->filter($first));

        $this->expectException(\LogicException::class);

        $script->filter($second);
    }

    /**
     * @test
     */
    public function testWithOutputFilter(): void
    {
        $script = new ScriptHandler(\Mockery::mock('\WP_Scripts'));

        $filters = $script->outputFilters();

        static::assertInstanceOf(
            AsyncScriptOutputFilter::class,
            $filters[AsyncScriptOutputFilter::class],
        );

        static::assertInstanceOf(
            DeferScriptOutputFilter::class,
            $filters[DeferScriptOutputFilter::class],
        );

        static::assertInstanceOf(
            InlineAssetOutputFilter::class,
            $filters[InlineAssetOutputFilter::class],
        );

        $custom = static function (string $html) {
            return $html;
        };

        $script->withOutputFilter('custom', $custom);

        static::assertSame($custom, $script->outputFilters()['custom']);
    }
}
