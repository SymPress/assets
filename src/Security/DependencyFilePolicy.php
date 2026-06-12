<?php

declare(strict_types=1);

namespace SymPress\Assets\Security;

final readonly class DependencyFilePolicy
{
    public const int DEFAULT_MAX_BYTES = 65_536;

    public function __construct(
        private bool $phpFilesAllowed = false,
        private int $maxBytes = self::DEFAULT_MAX_BYTES,
    ) {

        if ($maxBytes < 1) {
            throw new \InvalidArgumentException('The dependency file size limit must be greater than zero.');
        }
    }

    public function allows(\SplFileInfo $file): bool
    {
        if (!$file->isFile() || !$file->isReadable()) {
            return false;
        }

        if ($file->getSize() > $this->maxBytes) {
            return false;
        }

        return match ($file->getExtension()) {
            'json' => true,
            'php' => $this->phpFilesAllowed,
            default => false,
        };
    }
}
