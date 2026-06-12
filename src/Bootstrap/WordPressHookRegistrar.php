<?php

declare(strict_types=1);

namespace SymPress\Assets\Bootstrap;

final class WordPressHookRegistrar
{
    private const int ACCEPTED_ARGS = 0;

    private const int DEFAULT_PRIORITY = 99;

    private readonly WordPressHookApi $hookApi;

    public function __construct(?WordPressHookApi $hookApi = null)
    {
        $this->hookApi = $hookApi ?? new NativeWordPressHookApi();
    }

    public function register(string $callback, int $priority = self::DEFAULT_PRIORITY): void
    {
        if ($this->hookApi->available() || $this->hookApi->load()) {
            $this->hookApi->addAction('wp_loaded', $callback, $priority, self::ACCEPTED_ARGS);

            return;
        }

        $this->registerFallback($callback, $priority);
    }

    private function registerFallback(string $callback, int $priority): void
    {
        global $wp_filter;

        if (!is_array($wp_filter)) {
            $wp_filter = [];
        }

        /** @var array<string, array<int, array<string, array{function: string, accepted_args: int}>>> $wp_filter */
        if (!isset($wp_filter['wp_loaded'])) {
            $wp_filter['wp_loaded'] = [];
        }

        if (!isset($wp_filter['wp_loaded'][$priority])) {
            $wp_filter['wp_loaded'][$priority] = [];
        }

        $wp_filter['wp_loaded'][$priority][$callback] = [
            'function'      => $callback,
            'accepted_args' => self::ACCEPTED_ARGS,
        ];
    }
}
