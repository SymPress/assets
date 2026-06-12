<?php

declare(strict_types=1);

namespace SymPress\Assets\Handler;

use SymPress\Assets\Asset;
use SymPress\Assets\ScriptModule;

class ScriptModuleHandler implements AssetHandler
{
    public function enqueue(Asset $asset): bool
    {
        if (!$asset instanceof ScriptModule) {
            return false;
        }
        if (!static::scriptModulesSupported()) {
            return false;
        }

        if (!$this->register($asset)) {
            return false;
        }

        if ($asset->enqueue()) {
            wp_enqueue_script_module($asset->handle());

            return true;
        }

        return false;
    }

    public function register(Asset $asset): bool
    {
        if (!$asset instanceof ScriptModule) {
            return false;
        }
        if (!static::scriptModulesSupported()) {
            return false;
        }

        $handle = $asset->handle();
        if ($handle === '') {
            return false;
        }

        $this->shareData($asset);

        wp_register_script_module(
            $handle,
            $asset->url(),
            $asset->dependencies(),
            $asset->version(),
        );

        return true;
    }

    protected static function scriptModulesSupported(): bool
    {
        return class_exists('WP_Script_Modules');
    }

    protected function shareData(ScriptModule $asset): void
    {
        $handle = $asset->handle();
        if ($handle === '') {
            return;
        }

        $data = $asset->data();
        if (!$data) {
            return;
        }

        add_filter(
            "script_module_data_{$handle}",
            static function (mixed $existing = []) use ($data): array {
                return array_replace(is_array($existing) ? $existing : [], $data);
            },
            PHP_INT_MAX - 10,
            1,
        );
    }
}
