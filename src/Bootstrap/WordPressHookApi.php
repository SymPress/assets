<?php

declare(strict_types=1);

namespace SymPress\Assets\Bootstrap;

interface WordPressHookApi
{
    public function available(): bool;

    public function load(): bool;

    public function addAction(string $hook, string $callback, int $priority, int $acceptedArgs): void;
}
