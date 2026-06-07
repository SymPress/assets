<?php

declare(strict_types=1);

namespace SymPress\Assets\Bootstrap;

final class NativeWordPressHookApi implements WordPressHookApi
{
    public function available(): bool
    {
        return function_exists('add_action');
    }

    public function load(): bool
    {
        if (!defined('ABSPATH') || !defined('WP_INC')) {
            return false;
        }

        $abspath = constant('ABSPATH');
        $wpInc = constant('WP_INC');
        if (!is_string($abspath) || !is_string($wpInc)) {
            return false;
        }

        $pluginApi = $abspath . $wpInc . '/plugin.php';

        if (!is_file($pluginApi)) {
            return false;
        }

        require_once $pluginApi;

        return $this->available();
    }

    public function addAction(string $hook, string $callback, int $priority, int $acceptedArgs): void
    {
        if (!is_callable($callback)) {
            return;
        }

        add_action($hook, $callback, $priority, $acceptedArgs);
    }
}
