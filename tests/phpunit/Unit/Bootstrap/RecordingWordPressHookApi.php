<?php

declare(strict_types=1);

namespace SymPress\Assets\Tests\Unit\Bootstrap;

use SymPress\Assets\Bootstrap\WordPressHookApi;

final class RecordingWordPressHookApi implements WordPressHookApi
{
    /**
     * @var list<array{hook: string, callback: string, priority: int, acceptedArgs: int}>
     */
    public array $registrations = [];

    public function __construct(
        private readonly bool $available = false,
        private readonly bool $loadable = false,
    ) {
    }

    public function available(): bool
    {
        return $this->available;
    }

    public function load(): bool
    {
        return $this->loadable;
    }

    public function addAction(string $hook, string $callback, int $priority, int $acceptedArgs): void
    {
        $this->registrations[] = [
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'acceptedArgs' => $acceptedArgs,
        ];
    }
}
