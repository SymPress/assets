<?php

declare(strict_types=1);

namespace SymPress\Assets\IO;

final class RequestFiles
{
    private static ?self $shared = null;

    /** @var array<string, mixed> */
    private array $values = [];
    private int $observations = 0;

    public static function shared(): self
    {
        return self::$shared ??= new self();
    }

    public static function reset(): void
    {
        self::$shared = null;
        clearstatcache();
    }

    public function observations(): int
    {
        return $this->observations;
    }

    /**
     * @template T
     * @param callable(): T $read
     * @return T
     */
    public function remember(string $key, callable $read): mixed
    {
        if (!array_key_exists($key, $this->values)) {
            $this->values[$key] = $read();
        }
        /** @var T $value */
        $value = $this->values[$key];
        return $value;
    }

    /** @return array{canonical: string|null, size: int, modified: int, readable: bool, file: bool} */
    public function info(string $path): array
    {
        return $this->remember('info:' . $path, function () use ($path): array {
            ++$this->observations;
            clearstatcache(true, $path);
            $canonical = realpath($path);
            $stat = @stat($path);
            return [
                'canonical' => $canonical === false ? null : $canonical,
                'size' => $stat === false ? 0 : $stat['size'],
                'modified' => $stat === false ? 0 : $stat['mtime'],
                'readable' => is_readable($path),
                'file' => is_file($path),
            ];
        });
    }

    public function contents(string $path): ?string
    {
        $info = $this->info($path);
        $canonical = $info['canonical'];
        if ($canonical === null && $info['file'] && $info['readable'] && str_contains($path, '://')) {
            $canonical = $path;
        }
        if ($canonical === null) {
            return null;
        }
        return $this->remember('contents:' . $canonical, function () use ($canonical): ?string {
            ++$this->observations;
            $content = file_get_contents($canonical);
            return $content === false ? null : $content;
        });
    }
}
