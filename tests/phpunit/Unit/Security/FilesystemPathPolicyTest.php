<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Security;

use SymPress\Assets\Security\FilesystemPathPolicy;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

class FilesystemPathPolicyTest extends AbstractTestCase
{
    private string $root;

    public function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/sympress-path-policy-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/allowed', 0777, true);
        mkdir($this->root . '/allowed-neighbor', 0777, true);
        file_put_contents($this->root . '/allowed/file.js', '');
        file_put_contents($this->root . '/allowed-neighbor/file.js', '');
    }

    protected function tearDown(): void
    {
        unlink($this->root . '/allowed/file.js');
        unlink($this->root . '/allowed-neighbor/file.js');
        rmdir($this->root . '/allowed');
        rmdir($this->root . '/allowed-neighbor');
        rmdir($this->root);
        parent::tearDown();
    }

    public function testAllowsFilesInsideAllowedDirectory(): void
    {
        $policy = new FilesystemPathPolicy([$this->root . '/allowed']);

        static::assertTrue($policy->allowsPath($this->root . '/allowed/file.js'));
    }

    public function testRejectsPrefixNeighborDirectories(): void
    {
        $policy = new FilesystemPathPolicy([$this->root . '/allowed']);

        static::assertFalse($policy->allowsPath($this->root . '/allowed-neighbor/file.js'));
    }
}
