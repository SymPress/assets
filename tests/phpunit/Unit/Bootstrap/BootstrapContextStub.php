<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Bootstrap;

final class BootstrapContextStub
{
    public function isLogin(): bool
    {
        return false;
    }

    public function isFrontoffice(): bool
    {
        return true;
    }

    public function isBackoffice(): bool
    {
        return false;
    }

    public function isWpActivate(): bool
    {
        return false;
    }
}
