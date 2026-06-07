<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Util;

final class ContextStub
{
    public function __construct(
        private readonly bool $isLogin = false,
        private readonly bool $isFrontoffice = false,
        private readonly bool $isBackoffice = false,
        private readonly bool $isWpActivate = false,
    ) {
    }

    public function isLogin(): bool
    {
        return $this->isLogin;
    }

    public function isFrontoffice(): bool
    {
        return $this->isFrontoffice;
    }

    public function isBackoffice(): bool
    {
        return $this->isBackoffice;
    }

    public function isWpActivate(): bool
    {
        return $this->isWpActivate;
    }
}
