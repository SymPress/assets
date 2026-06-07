<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Bootstrap;

use SymPress\Assets\Bootstrap\WordPressHookRegistrar;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WordPressHookRegistrarTest extends TestCase
{
    private bool $hadPreviousWpFilter = false;

    private mixed $previousWpFilter = null;

    protected function setUp(): void
    {
        parent::setUp();

        global $wp_filter;

        $this->hadPreviousWpFilter = array_key_exists('wp_filter', $GLOBALS);
        $this->previousWpFilter = $wp_filter ?? null;
        unset($GLOBALS['wp_filter']);
    }

    protected function tearDown(): void
    {
        if ($this->hadPreviousWpFilter) {
            $GLOBALS['wp_filter'] = $this->previousWpFilter;
        } else {
            unset($GLOBALS['wp_filter']);
        }

        parent::tearDown();
    }

    #[Test]
    public function itRegistersFallbackHookWhenWordPressHookApiIsUnavailable(): void
    {
        if (function_exists('add_action')) {
            self::markTestSkipped('WordPress hook API is already available.');
        }

        (new WordPressHookRegistrar())->register(__METHOD__, 13);

        self::assertSame(
            [
                'function' => __METHOD__,
                'accepted_args' => 0,
            ],
            $GLOBALS['wp_filter']['wp_loaded'][13][__METHOD__],
        );
    }

    #[Test]
    public function itRegistersNativeHookWithoutPassingWordPressActionArguments(): void
    {
        $hookApi = new RecordingWordPressHookApi(available: true);

        (new WordPressHookRegistrar($hookApi))->register(__METHOD__, 13);

        self::assertSame(
            [
                'hook' => 'wp_loaded',
                'callback' => __METHOD__,
                'priority' => 13,
                'acceptedArgs' => 0,
            ],
            $hookApi->registrations[0] ?? null,
        );
    }
}
