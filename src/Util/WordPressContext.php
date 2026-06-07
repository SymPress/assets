<?php

declare(strict_types=1);

namespace SymPress\Assets\Util;

final class WordPressContext
{
    private const string AJAX = 'ajax';
    private const string BACKOFFICE = 'backoffice';
    private const string CLI = 'wpcli';
    private const string CORE = 'core';
    private const string CRON = 'cron';
    private const string FRONTOFFICE = 'frontoffice';
    private const string INSTALLING = 'installing';
    private const string LOGIN = 'login';
    private const string REST = 'rest';
    private const string XML_RPC = 'xml-rpc';
    private const string WP_ACTIVATE = 'wp-activate';

    private const array ALL = [
        self::AJAX,
        self::BACKOFFICE,
        self::CLI,
        self::CORE,
        self::CRON,
        self::FRONTOFFICE,
        self::INSTALLING,
        self::LOGIN,
        self::REST,
        self::XML_RPC,
        self::WP_ACTIVATE,
    ];

    /**
     * @param array<string, bool> $data
     */
    private function __construct(
        private array $data,
    ) {
    }

    public static function determine(): self
    {
        $installing = function_exists('wp_installing')
            ? wp_installing()
            : (defined('WP_INSTALLING') && true === constant('WP_INSTALLING'));
        $xmlRpc = defined('XMLRPC_REQUEST') && XMLRPC_REQUEST;
        $isCore = defined('ABSPATH');
        $isCli = defined('WP_CLI');
        $notInstalling = $isCore && !$installing;
        $isAjax = $notInstalling && function_exists('wp_doing_ajax') && wp_doing_ajax();
        $isAdmin = $notInstalling && function_exists('is_admin') && is_admin() && !$isAjax;
        $isCron = $notInstalling && function_exists('wp_doing_cron') && wp_doing_cron();
        $isWpActivate = $installing
            && function_exists('is_multisite')
            && is_multisite()
            && self::isWpActivateRequest();
        $undetermined = $notInstalling && !$isAdmin && !$isCron && !$isCli && !$xmlRpc && !$isAjax;
        $isRest = $undetermined && self::isRestRequest();
        $isLogin = $undetermined && !$isRest && self::isLoginRequest();
        $isFront = $undetermined && !$isRest && !$isLogin;

        $context = new self(
            [
                self::AJAX => $isAjax,
                self::BACKOFFICE => $isAdmin,
                self::CLI => $isCli,
                self::CORE => ($isCore || $xmlRpc) && (!$installing || $isWpActivate),
                self::CRON => $isCron,
                self::FRONTOFFICE => $isFront,
                self::INSTALLING => $installing && !$isWpActivate,
                self::LOGIN => $isLogin,
                self::REST => $isRest,
                self::XML_RPC => $xmlRpc && !$installing,
                self::WP_ACTIVATE => $isWpActivate,
            ],
        );

        $context->addActionHooks();

        return $context;
    }

    public function isFrontoffice(): bool
    {
        return $this->is(self::FRONTOFFICE);
    }

    public function isBackoffice(): bool
    {
        return $this->is(self::BACKOFFICE);
    }

    public function isLogin(): bool
    {
        return $this->is(self::LOGIN);
    }

    public function isWpActivate(): bool
    {
        return $this->is(self::WP_ACTIVATE);
    }

    private function is(string $context): bool
    {
        return ($this->data[$context] ?? false) === true;
    }

    private static function isRestRequest(): bool
    {
        if ((defined('REST_REQUEST') && REST_REQUEST) || !empty($_GET['rest_route'])) {
            return true;
        }

        if (
            !function_exists('get_option')
            || !function_exists('add_query_arg')
            || !function_exists('get_rest_url')
        ) {
            return false;
        }

        if (!get_option('permalink_structure')) {
            return false;
        }

        if (empty($GLOBALS['wp_rewrite']) && class_exists(\WP_Rewrite::class)) {
            $GLOBALS['wp_rewrite'] = new \WP_Rewrite();
        }

        $currentPath = trim(
            (string) parse_url((string) add_query_arg([]), PHP_URL_PATH),
            '/',
        ) . '/';
        $restPath = trim((string) parse_url((string) get_rest_url(), PHP_URL_PATH), '/') . '/';

        return str_starts_with($currentPath, $restPath);
    }

    private static function isLoginRequest(): bool
    {
        if (function_exists('is_login')) {
            return false !== is_login();
        }

        if (!empty($_REQUEST['interim-login'])) {
            return true;
        }

        if (!function_exists('wp_login_url')) {
            return false;
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (!is_string($scriptName)) {
            return false;
        }

        $loginUrl = wp_login_url();
        if (!is_string($loginUrl)) {
            return false;
        }

        return false !== stripos($loginUrl, $scriptName);
    }

    private static function isWpActivateRequest(): bool
    {
        if (!function_exists('network_site_url')) {
            return false;
        }

        return self::isPageNow('wp-activate.php', network_site_url('wp-activate.php'));
    }

    private static function isPageNow(string $page, string $url): bool
    {
        $pageNow = $GLOBALS['pagenow'] ?? '';
        if (!is_string($pageNow)) {
            return false;
        }

        if ('' !== $pageNow && basename($pageNow) === $page) {
            return true;
        }

        if (!function_exists('add_query_arg')) {
            return false;
        }

        $currentPath = (string) parse_url((string) add_query_arg([]), PHP_URL_PATH);
        $targetPath = (string) parse_url($url, PHP_URL_PATH);

        return trim($currentPath, '/') === trim($targetPath, '/');
    }

    private function addActionHooks(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        add_action('login_init', function (): void {
            $this->force(self::LOGIN);
        }, PHP_INT_MIN);
        add_action('rest_api_init', function (): void {
            $this->force(self::REST);
        }, PHP_INT_MIN);
        add_action('activate_header', function (): void {
            $this->force(self::WP_ACTIVATE);
        }, PHP_INT_MIN);
        add_action('template_redirect', function (): void {
            $this->force(self::FRONTOFFICE);
        }, PHP_INT_MIN);
        add_action(
            'current_screen',
            function (mixed $screen): void {
                $callback = [$screen, 'in_admin'];

                if (is_callable($callback) && (bool) $callback()) {
                    $this->force(self::BACKOFFICE);
                }
            },
            PHP_INT_MIN,
        );
    }

    private function force(string $context): self
    {
        $data = array_fill_keys(self::ALL, false);
        $data[$context] = true;

        if (!in_array($context, [self::INSTALLING, self::CLI, self::CORE], true)) {
            $data[self::CORE] = true;
        }

        $this->data = $data;

        return $this;
    }
}
