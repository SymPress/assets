<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SymPress\Assets\Asset;
use SymPress\Assets\AssetConfigurationApplier;
use SymPress\Assets\Exception\InvalidArgumentException;
use SymPress\Assets\Performance\ResourceHint;
use SymPress\Assets\Script;
use SymPress\Assets\ScriptLoadingStrategy;
use SymPress\Assets\Style;
use SymPress\Assets\StyleLoadingMode;

final class AssetConfigurationApplierTest extends AbstractTestCase
{
    #[Test]
    public function itAppliesCommonAndScriptSpecificConfiguration(): void
    {
        $asset = new Script('app', 'https://example.test/app.js');

        $configured = (new AssetConfigurationApplier())->apply(
            $asset,
            [
                'attributes'   => ['defer' => true],
                'condition'    => 'lt IE 11',
                'dependencies' => 'vendor',
                'enqueue'      => false,
                'filePath'     => '/tmp/app.js',
                'handler'      => AssetConfigurationApplierHandler::class,
                'inFooter'     => false,
                'inline'       => [
                    'before' => ['window.before = true;'],
                    'after'  => ['window.after = true;'],
                ],
                'localize'     => [
                    'appConfig' => ['enabled' => true],
                ],
                'location'     => Asset::BACKEND,
                'strategy'     => ScriptLoadingStrategy::ASYNC,
                'translation'  => [
                    'domain' => 'assets',
                    'path'   => '/languages',
                ],
                'version'      => '1.2.3',
            ],
        );

        self::assertSame($asset, $configured);
        self::assertSame(['defer' => true], $asset->attributes());
        self::assertSame(['conditional' => 'lt IE 11'], $asset->data());
        self::assertSame(['vendor'], $asset->dependencies());
        self::assertFalse($asset->enqueue());
        self::assertSame('/tmp/app.js', $asset->filePath());
        self::assertSame(AssetConfigurationApplierHandler::class, $asset->handler());
        self::assertFalse($asset->inFooter());
        self::assertSame(['before' => ['window.before = true;'], 'after' => ['window.after = true;']], $asset->inlineScripts());
        self::assertSame(['appConfig' => ['enabled' => true]], $asset->localize());
        self::assertSame(Asset::BACKEND, $asset->location());
        self::assertSame(ScriptLoadingStrategy::ASYNC, $asset->loadingStrategy());
        self::assertSame(['domain' => 'assets', 'path' => '/languages'], $asset->translation());
        self::assertSame('1.2.3', $asset->version());
    }

    #[Test]
    public function itAppliesStyleSpecificConfigurationAndIgnoresInvalidDependencies(): void
    {
        $asset = new Style('app', 'https://example.test/app.css');

        (void) (new AssetConfigurationApplier())->apply(
            $asset,
            [
                'dependencies' => ['reset', 42, new \stdClass()],
                'inlineStyles' => '.app{display:block;}',
                'loadingMode'  => StyleLoadingMode::PRELOAD,
                'media'        => 'screen',
            ],
        );

        self::assertSame(['reset', '42'], $asset->dependencies());
        self::assertSame(['.app{display:block;}'], $asset->inlineStyles());
        self::assertSame(StyleLoadingMode::PRELOAD, $asset->loadingMode());
        self::assertSame('screen', $asset->media());
    }

    #[Test]
    public function itAppliesResourceHintsConfiguration(): void
    {
        $asset = new Script('app', 'https://example.test/app.js');

        (void) (new AssetConfigurationApplier())->apply(
            $asset,
            [
                'resourceHints' => [
                    [
                        'relation'      => ResourceHint::PRELOAD,
                        'as'            => 'script',
                        'fetchpriority' => 'high',
                    ],
                    ResourceHint::preconnect('https://cdn.example.test', ['crossorigin' => true]),
                ],
            ],
        );

        self::assertCount(2, $asset->resourceHints());
        self::assertSame(ResourceHint::PRELOAD, $asset->resourceHints()[0]->relation());
        self::assertSame('https://example.test/app.js', $asset->resourceHints()[0]->href());
        self::assertSame(['as' => 'script', 'fetchpriority' => 'high'], $asset->resourceHints()[0]->attributes());
        self::assertSame(ResourceHint::PRECONNECT, $asset->resourceHints()[1]->relation());
    }

    #[Test]
    public function itAppliesScalarInlineScriptConfiguration(): void
    {
        $asset = new Script('app', 'https://example.test/app.js');

        (void) (new AssetConfigurationApplier())->apply(
            $asset,
            [
                'inline' => [
                    'before' => 'window.before = true;',
                    'after'  => 'window.after = true;',
                ],
            ],
        );

        self::assertSame(
            ['before' => ['window.before = true;'], 'after' => ['window.after = true;']],
            $asset->inlineScripts(),
        );
    }

    #[Test]
    public function itRejectsInvalidStrictConfigurationValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (void) (new AssetConfigurationApplier())->apply(
            new Script('app', 'https://example.test/app.js'),
            [
                'cacheOptimization' => 'yes',
            ],
        );
    }
}
