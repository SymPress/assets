<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Performance;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\Test;
use SymPress\Assets\Performance\ResourceHint;
use SymPress\Assets\Performance\ResourceHintHandler;
use SymPress\Assets\Script;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

final class ResourceHintHandlerTest extends AbstractTestCase
{
    #[Test]
    public function itRegistersAndReturnsConfiguredResourceHints(): void
    {
        $handler = new ResourceHintHandler();
        $asset = (new Script('app', 'https://example.test/app.js'))
            ->withPreconnect('https://cdn.example.test', ['crossorigin' => true])
            ->withDnsPrefetch('https://api.example.test')
            ->withPreloadResource('script', ['fetchpriority' => 'high']);

        Functions\expect('add_filter')->twice()->andReturn(true);

        self::assertTrue($handler->run([Script::class => ['app' => $asset]]));
        self::assertSame(
            ['https://existing.example.test', 'https://cdn.example.test'],
            $handler->resourceHints(['https://existing.example.test'], ResourceHint::PRECONNECT),
        );
        self::assertSame(
            ['https://api.example.test'],
            $handler->resourceHints([], ResourceHint::DNS_PREFETCH),
        );
        self::assertSame(
            [
                [
                    'href'          => 'https://example.test/app.js',
                    'as'            => 'script',
                    'fetchpriority' => 'high',
                ],
            ],
            $handler->preloadResources([]),
        );
    }

    #[Test]
    public function itDoesNotRegisterFiltersWithoutResourceHints(): void
    {
        Functions\expect('add_filter')->never();

        self::assertFalse((new ResourceHintHandler())->run([Script::class => ['app' => new Script('app', 'app.js')]]));
    }
}
