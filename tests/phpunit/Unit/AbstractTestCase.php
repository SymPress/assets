<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    /**
     * Sets up the environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        \SymPress\Assets\IO\RequestFiles::reset();
        Monkey\setUp();
    }

    /**
     * Tears down the environment.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
