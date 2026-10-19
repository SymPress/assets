<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\IO;

use SymPress\Assets\IO\RequestFiles;
use SymPress\Assets\Loader\JsonFileReader;
use SymPress\Assets\Security\FilesystemPathPolicy;
use SymPress\Assets\Security\IntegrityMetadata;
use SymPress\Assets\Script;
use SymPress\Assets\Tests\Unit\AbstractTestCase;

final class RequestFilesTest extends AbstractTestCase
{
    public function testMissingFileDoesNotLeaveAWarningForWordPress(): void
    {
        $path = sys_get_temp_dir() . '/assets-missing-' . bin2hex(random_bytes(5)) . '.asset.json';
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Exercise PHP's native last-error state, not PHPUnit's handler.
        set_error_handler(static fn (): bool => false);
        try {
            error_clear_last();
            $info = RequestFiles::shared()->info($path);
            $error = error_get_last();
        } finally {
            restore_error_handler();
            error_clear_last();
        }
        self::assertNull($error);
        self::assertSame([
            'canonical' => null,
            'size' => 0,
            'modified' => 0,
            'readable' => false,
            'file' => false,
        ], $info);
    }

    public function testMissingFilePreservesAnExistingPhpError(): void
    {
        $path = sys_get_temp_dir() . '/assets-missing-' . bin2hex(random_bytes(5)) . '.asset.json';
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Exercise PHP's native last-error state, not PHPUnit's handler.
        set_error_handler(static fn (): bool => false);
        try {
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Seed a pre-existing warning without printing test output.
            @trigger_error('Existing application warning', E_USER_WARNING);
            $before = error_get_last();
            RequestFiles::shared()->info($path);
            $after = error_get_last();
        } finally {
            restore_error_handler();
            error_clear_last();
        }
        self::assertNotNull($before);
        self::assertSame($before, $after);
    }

    public function testRepeatedReadsAndNegativePathsShareSnapshotUntilRequestReset(): void
    {
        $directory = sys_get_temp_dir() . '/assets-snapshot-' . bin2hex(random_bytes(5));
        mkdir($directory, 0700);
        $file = $directory . '/manifest.json';
        file_put_contents($file, '{"value":"first"}');
        try {
            $snapshot = RequestFiles::shared();
            self::assertSame(['value' => 'first'], (new JsonFileReader())->read($file));
            $count = $snapshot->observations();
            file_put_contents($file, '{"value":"other"}');
            self::assertSame(['value' => 'first'], (new JsonFileReader())->read($file));
            self::assertSame($count, $snapshot->observations());
            self::assertNull($snapshot->info($directory . '/missing')['canonical']);
            $count = $snapshot->observations();
            self::assertNull($snapshot->info($directory . '/missing')['canonical']);
            self::assertSame($count, $snapshot->observations());
            RequestFiles::reset();
            self::assertSame(['value' => 'other'], (new JsonFileReader())->read($file));
            self::assertTrue((new FilesystemPathPolicy([$directory]))->allowsPath($file));
            self::assertFalse((new FilesystemPathPolicy([$directory . '/other']))->allowsPath($file));
        } finally {
            unlink($file);
            rmdir($directory);
        }
    }

    public function testSuppliedIntegrityHasValidatedDigestBytesAndCrossorigin(): void
    {
        $asset = new Script('app', 'https://cdn.example.test/app.js');
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encode fixture SRI digest, never executable source.
        $hash = 'sha384-' . base64_encode(hash('sha384', 'build bytes', true));
        IntegrityMetadata::apply($asset, $hash);
        self::assertSame($hash, $asset->attributes()['integrity']);
        self::assertSame('anonymous', $asset->attributes()['crossorigin']);
        $this->expectException(\SymPress\Assets\Exception\InvalidResourceException::class);
        IntegrityMetadata::apply($asset, 'sha384-not-a-digest');
    }
}
