<?php

declare(strict_types=1);

namespace SymPress\Assets;

use SymPress\Assets\Handler\AssetHandler;

interface Asset
{
    // Location types
    public const int FRONTEND = 2;
    public const int BACKEND = 4;
    public const int CUSTOMIZER = 8;
    public const int LOGIN = 16;
    public const int BLOCK_EDITOR_ASSETS = 32;
    public const int BLOCK_ASSETS = 64;
    public const int CUSTOMIZER_PREVIEW = 128;
    public const int ACTIVATE = 256;
    // Hooks
    public const string HOOK_FRONTEND = 'wp_enqueue_scripts';
    public const string HOOK_BACKEND = 'admin_enqueue_scripts';
    public const string HOOK_LOGIN = 'login_enqueue_scripts';
    public const string HOOK_CUSTOMIZER = 'customize_controls_enqueue_scripts';
    public const string HOOK_CUSTOMIZER_PREVIEW = 'customize_preview_init';
    public const string HOOK_BLOCK_ASSETS = 'enqueue_block_assets';
    public const string HOOK_BLOCK_EDITOR_ASSETS = 'enqueue_block_editor_assets';
    public const string HOOK_ACTIVATE = 'activate_wp_head';
    /**
     * Hooks to Locations map.
     */
    public const array HOOK_TO_LOCATION = [
        Asset::HOOK_FRONTEND            => Asset::FRONTEND,
        Asset::HOOK_BACKEND             => Asset::BACKEND,
        Asset::HOOK_LOGIN               => Asset::LOGIN,
        Asset::HOOK_CUSTOMIZER          => Asset::CUSTOMIZER,
        Asset::HOOK_CUSTOMIZER_PREVIEW  => Asset::CUSTOMIZER_PREVIEW,
        Asset::HOOK_BLOCK_ASSETS        => Asset::BLOCK_ASSETS,
        Asset::HOOK_BLOCK_EDITOR_ASSETS => Asset::BLOCK_EDITOR_ASSETS,
        Asset::HOOK_ACTIVATE            => Asset::ACTIVATE,
    ];

    /**
     * Contains the full url to file.
     */
    public function url(): string;

    /**
     * Returns the full file path to the asset.
     */
    public function filePath(): string;

    /**
     * Define the full filePath to the Asset.
     */
    public function withFilePath(string $filePath): static;

    /**
     * Name of the given asset.
     */
    public function handle(): string;

    /**
     * A list of handle-dependencies.
     *
     * @return array<string>
     */
    public function dependencies(): array;

    public function withDependencies(string ...$dependencies): static;

    /**
     * The current version of the asset.
     */
    public function version(): ?string;

    public function withVersion(string $version): static;

    public function enqueue(): bool;

    /** @param bool|callable $enqueue */
    public function canEnqueue($enqueue): static;

    /**
     * Location where the asset is enqueued.
     *
     * @example Asset::FRONTEND | Asset::BACKEND
     * @example Asset::FRONTEND
     */
    public function location(): int;

    /**
     * Define a location based on Asset location types.
     */
    public function forLocation(int $location): static;

    /**
     * Name of the handler class to register and enqueue the asset.
     *
     * @return class-string<AssetHandler>
     */
    public function handler(): string;

    /** @param class-string<AssetHandler> $handler */
    public function useHandler(string $handler): static;
}
