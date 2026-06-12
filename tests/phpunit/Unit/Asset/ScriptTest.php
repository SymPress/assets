<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Asset;

use SymPress\Assets\Asset;
use SymPress\Assets\Exception\InvalidResourceException;
use SymPress\Assets\Handler\ScriptHandler;
use SymPress\Assets\Script;
use SymPress\Assets\ScriptLoadingStrategy;
use SymPress\Assets\Tests\Unit\AbstractTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ScriptTest extends AbstractTestCase
{
    private vfsStreamDirectory $root;

    public function setUp(): void
    {
        $this->root = vfsStream::setup('tmp');
        parent::setUp();
    }

    /** @test */
    public function testBasic(): void
    {
        $script = new Script('foo', 'foo.js');

        static::assertTrue($script->inFooter());
        static::assertEmpty($script->localize());
        static::assertSame(ScriptLoadingStrategy::DEFER, $script->loadingStrategy());
        static::assertSame(ScriptHandler::class, $script->handler());
        static::assertSame(Asset::FRONTEND | Asset::ACTIVATE, $script->location());
    }

    /** @test */
    public function testWithTranslation(): void
    {
        $script = new Script('handle', 'script.js');

        static::assertEmpty($script->translation()['domain']);
        static::assertNull($script->translation()['path']);

        $expectedDomain = 'foo';
        $expectedPath = '/path/to/some/file.json';

        $script->withTranslation($expectedDomain, $expectedPath);

        static::assertSame(
            ['domain' => $expectedDomain, 'path' => $expectedPath],
            $script->translation(),
        );
    }

    /**
     * @test
     * @dataProvider provideLocalized
     */
    public function testWithLocalize(string $objectName, $objectValue, $expected): void
    {
        $script = new Script('handle', 'script.js');

        static::assertEmpty($script->localize());

        $script->withLocalize($objectName, $objectValue);

        static::assertSame($expected, $script->localize());
    }

    /** @test */
    public function testLocalizedSingleClosure(): void
    {
        $expected = ['foo' => ['bar' => 'baz']];
        $objectName = 'localizeObjectName';
        $script = new Script('handle', 'script.js', Asset::FRONTEND);
        $script->withLocalize(
            $objectName,
            static function () use ($expected): array {
                return $expected;
            },
        );

        static::assertSame([$objectName => $expected], $script->localize());
    }

    /** @test */
    public function testInFooter(): void
    {
        $script = new Script('handle', 'script.js');

        // default is true
        static::assertTrue($script->inFooter());

        $script->isInHeader();
        static::assertFalse($script->inFooter());

        $script->isInFooter();
        static::assertTrue($script->inFooter());
    }

    /** @test */
    public function testLocalizeCallable(): void
    {
        $expectedKey = 'foo';
        $expectedValue = ['bar' => 'baz'];
        $expected = [$expectedKey => $expectedValue];

        $script = new Script('handle', 'script.js', Asset::FRONTEND);
        $script->withLocalize(
            $expectedKey,
            static function () use ($expectedValue): array {
                return $expectedValue;
            },
        );

        static::assertSame($expected, $script->localize());
    }

    public function testEnqueueCallable(): void
    {
        $expected = random_int(0, 100) > 50;

        $script = new Script('handle', 'script.js', Asset::FRONTEND);
        $script->canEnqueue(
            static function () use ($expected): bool {
                return $expected;
            },
        );

        static::assertSame($expected, $script->enqueue());
    }

    /** @test */
    public function testInlineScripts(): void
    {
        $script = new Script('handle', 'foo.js');

        $expectedAppended = 'foo';
        $expectedPrepended = 'foo';

        static::assertEmpty($script->inlineScripts()['before']);
        static::assertEmpty($script->inlineScripts()['after']);

        $script->appendInlineScript($expectedAppended);
        $script->prependInlineScript($expectedPrepended);

        static::assertEquals(
            ['before' => [$expectedAppended], 'after' => [$expectedPrepended]],
            $script->inlineScripts(),
        );
    }

    /**
     * @test
     * @deprecated
     */
    public function testUseAsyncFilter(): void
    {
        $script = new Script('handle', 'foo.js');
        static::assertEmpty($script->filters());

        $script->useAsyncFilter();
        static::assertSame(ScriptLoadingStrategy::ASYNC, $script->loadingStrategy());
        static::assertSame([], $script->attributes());
    }

    /**
     * @test
     * @deprecated
     */
    public function testUseDeferFilter(): void
    {
        $script = new Script('handle', 'foo.js');
        static::assertEmpty($script->filters());

        $script->useDeferFilter();
        static::assertSame(ScriptLoadingStrategy::DEFER, $script->loadingStrategy());
        static::assertSame([], $script->attributes());
    }

    public function testNativeLoadingStrategies(): void
    {
        $script = new Script('handle', 'foo.js');

        static::assertSame(ScriptLoadingStrategy::DEFER, $script->loadingStrategy());

        $script->async();
        static::assertSame(ScriptLoadingStrategy::ASYNC, $script->loadingStrategy());

        $script->blocking();
        static::assertNull($script->loadingStrategy());

        $script->withLoadingStrategy(null);
        static::assertNull($script->loadingStrategy());
    }

    /** @return \Generator<string, array> */
    public static function provideLocalized(): \Generator
    {
        yield 'string value' => [
            'objectName',
            'objectValue',
            ['objectName' => 'objectValue'],
        ];

        yield 'int value' => [
            'objectName',
            2,
            ['objectName' => 2],
        ];

        $expectedValue = ['foo', 'bar' => 'baz'];
        yield 'array value' => [
            'objectName',
            $expectedValue,
            ['objectName' => $expectedValue],
        ];

        yield 'closure' => [
            'objectName',
            static function (): string {
                return 'objectValue';
            },
            ['objectName' => 'objectValue'],
        ];
    }

    /**
     * @test
     * @dataProvider provideAssetsFile
     */
    public function testDependencyExtractionPlugin(
        string $scriptFile,
        string $depsFileName,
        string $depsFileContent,
        array $expectedDependencies,
        string $expectedVersion,
    ): void {

        vfsStream::newFile($depsFileName)
            ->withContent($depsFileContent)
            ->at($this->root);

        $expectedFile = vfsStream::newFile($scriptFile)->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());

        static::assertEqualsCanonicalizing(
            $expectedDependencies,
            $testee->dependencies(),
        );

        static::assertSame($testee->version(), $expectedVersion);
    }

    /** @return \Generator<string, array> */
    public static function provideAssetsFile(): \Generator
    {
        $expectedDependencies = ['foo', 'bar', 'baz'];
        $expectedVersion = '1.0';
        $dependencies = ['dependencies' => $expectedDependencies, 'version' => $expectedVersion];
        $fileHash1 = md5((string) time());
        $fileHash2 = md5((string) (time() + 1));

        $scriptFiles = [
            'script file'                                       => [
                'script.js',
                'script.asset.',
            ],
            'script & deps file same hash'                      => [
                'script.' . $fileHash1 . '.js',
                'script.' . $fileHash1 . '.asset.',
            ],
            'script & deps file different hash'                 => [
                'script.' . $fileHash1 . '.js',
                'script.' . $fileHash2 . '.asset.',
            ],
            'script file with multiple dots'                    => [
                'admin.dashboard.main.js',
                'admin.dashboard.main.asset.',
            ],
            'script file with multiple dots and different hash' => [
                'admin.dashboard.main.' . $fileHash1 . '.js',
                'admin.dashboard.main.' . $fileHash2 . '.asset.',
            ],
        ];

        foreach ($scriptFiles as $message => $files) {
            yield 'json - ' . $message => [
                $files[0], // script.js or script.{hash}.js
                $files[1] . 'json', // script(.{hash}).asset.json
                json_encode($dependencies),
                $expectedDependencies,
                $expectedVersion,
            ];
        }
    }

    /** @test */
    public function testDependencyExtractionPluginUniqueDependencies(): void
    {
        $expectedDependencies = ['foo', 'bar', 'baz'];

        vfsStream::newFile('script.asset.json')
            ->withContent(json_encode(['dependencies' => $expectedDependencies]))
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        // Adding "foo" in first place should result in
        // just having "foo" once as dependency
        $testee->withDependencies('foo');
        $testee->withFilePath($expectedFile->url());

        static::assertEqualsCanonicalizing(
            $expectedDependencies,
            $testee->dependencies(),
        );
    }

    public function testDependencyExtractionIsResolvedAgainAfterFilePathChanges(): void
    {
        $testee = new Script('script', '');

        static::assertSame([], $testee->dependencies());

        vfsStream::newFile('script.asset.json')
            ->withContent(json_encode(['dependencies' => ['late'], 'version' => 'late-version']))
            ->at($this->root);
        $file = vfsStream::newFile('script.js')->at($this->root);

        $testee->withFilePath($file->url());

        static::assertSame(['late'], $testee->dependencies());
        static::assertSame('late-version', $testee->version());
    }

    /** @test */
    public function testDependencyExtractionPluginWithDependencies(): void
    {
        $jsonDependencies = ['foo', 'bar', 'baz'];
        $registeredDependencies = ['bam'];

        $expectedDependencies = array_merge($jsonDependencies, $registeredDependencies);

        vfsStream::newFile('script.asset.json')
            ->withContent(json_encode(['dependencies' => $jsonDependencies]))
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')
            ->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withDependencies(...$registeredDependencies);
        $testee->withFilePath($expectedFile->url());

        static::assertEqualsCanonicalizing(
            $expectedDependencies,
            $testee->dependencies(),
        );
    }

    /**
     * @test
     * @dataProvider provideVersions
     */
    public function testDependencyExtractionPluginWithVersion(
        ?string $withVersion,
        string $dependencyExtractionPluginVersion,
        string $expectedVersion,
    ): void {

        vfsStream::newFile('script.asset.json')
            ->withContent(
                json_encode(
                    [
                        'dependencies' => [],
                        'version'      => $dependencyExtractionPluginVersion,
                    ],
                ),
            )
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')
            ->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        if ($withVersion) {
            $testee->withVersion($withVersion);
        }
        $testee->withFilePath($expectedFile->url());

        static::assertSame($expectedVersion, $testee->version());
    }

    /** @return \Generator<string, array> */
    public static function provideVersions(): \Generator
    {
        yield 'version already set' => [
            '1.0',
            'foo',
            '1.0',
        ];

        yield 'version not set and resolved' => [
            null,
            '1.0',
            '1.0',
        ];
    }

    public function testDependencyExtractionRejectsMalformedJson(): void
    {
        vfsStream::newFile('script.asset.json')
            ->withContent('{"dependencies":')
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());

        $this->expectException(InvalidResourceException::class);

        $testee->dependencies();
    }

    public function testDependencyExtractionRejectsPhpFilesWithoutArrayReturn(): void
    {
        vfsStream::newFile('script.asset.php')
            ->withContent('<?php return "invalid";')
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());
        $testee->withPhpDependencyFiles();

        $this->expectException(InvalidResourceException::class);

        $testee->dependencies();
    }

    public function testDependencyExtractionLoadsPhpDependencyFilesWhenExplicitlyAllowed(): void
    {
        vfsStream::newFile('script.asset.php')
            ->withContent('<?php return ["dependencies" => ["php"], "version" => "php-version"];')
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());
        $testee->withPhpDependencyFiles();

        static::assertSame(['php'], $testee->dependencies());
        static::assertSame('php-version', $testee->version());
    }

    public function testDependencyExtractionPrefersJsonOverPhpDependencyFiles(): void
    {
        vfsStream::newFile('script.asset.json')
            ->withContent(json_encode(['dependencies' => ['json'], 'version' => 'json-version']))
            ->at($this->root);

        vfsStream::newFile('script.asset.php')
            ->withContent('<?php return ["dependencies" => ["php"], "version" => "php-version"];')
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());
        $testee->withPhpDependencyFiles();

        static::assertSame(['json'], $testee->dependencies());
        static::assertSame('json-version', $testee->version());
    }

    public function testDependencyExtractionCanDisablePhpDependencyFiles(): void
    {
        vfsStream::newFile('script.asset.php')
            ->withContent('<?php return ["dependencies" => ["php"], "version" => "php-version"];')
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());
        $testee->withPhpDependencyFiles(false);

        static::assertSame([], $testee->dependencies());
    }

    public function testDependencyExtractionDisablesPhpDependencyFilesByDefault(): void
    {
        vfsStream::newFile('script.asset.php')
            ->withContent('<?php return ["dependencies" => ["php"], "version" => "php-version"];')
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());

        static::assertSame([], $testee->dependencies());
        static::assertNotSame('php-version', $testee->version());
    }

    public function testDependencyExtractionIgnoresFilesAboveSizeLimit(): void
    {
        vfsStream::newFile('script.asset.json')
            ->withContent(json_encode(['dependencies' => ['too-large']]))
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());
        $testee->withDependencyFileSizeLimit(4);

        static::assertSame([], $testee->dependencies());
    }

    /** @test */
    public function testDependencyExtractionCanBeDisabled(): void
    {
        $expectedDependencies = ['foo', 'bar', 'baz'];
        $expectedVersion = '1.0';

        vfsStream::newFile('script.asset.json')
            ->withContent(
                json_encode(
                    [
                        'dependencies' => $expectedDependencies,
                        'version'      => $expectedVersion,
                    ],
                ),
            )
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url(), Asset::FRONTEND, false);
        $testee->withFilePath($expectedFile->url());

        // Dependencies should not be loaded from the .asset.json file
        static::assertEmpty($testee->dependencies());

        // Version is still autodiscovered from file modification time, not from .asset.json
        // To verify it's not from .asset.json, we check it's not the expected version
        static::assertNotEquals($expectedVersion, $testee->version());
    }

    /** @test */
    public function testDependencyExtractionEnabledByDefault(): void
    {
        $expectedDependencies = ['foo', 'bar', 'baz'];

        vfsStream::newFile('script.asset.json')
            ->withContent(json_encode(['dependencies' => $expectedDependencies]))
            ->at($this->root);

        $expectedFile = vfsStream::newFile('script.js')->at($this->root);

        $testee = new Script('script', $expectedFile->url());
        $testee->withFilePath($expectedFile->url());

        // Should load dependencies by default
        static::assertEqualsCanonicalizing(
            $expectedDependencies,
            $testee->dependencies(),
        );
    }
}
