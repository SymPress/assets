<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$testsDir = str_replace('\\', '/', __DIR__);
$libDir = dirname($testsDir, 2);
$vendorDirs = [
    "{$libDir}/vendor",
    dirname($libDir, 2) . '/vendor',
];

$vendorDir = null;
$autoload = null;

foreach ($vendorDirs as $candidate) {
    $candidateAutoload = "{$candidate}/autoload.php";

    if (!is_file($candidateAutoload)) {
        continue;
    }

    $vendorDir = $candidate;
    $autoload = $candidateAutoload;
    break;
}

if ($vendorDir === null || $autoload === null) {
    exit('Please install via Composer before running tests.');
}

putenv('TESTS_DIR=' . $testsDir);
putenv('LIB_DIR=' . $libDir);
putenv('VENDOR_DIR=' . $vendorDir);

error_reporting(E_ALL); // phpcs:ignore

$patchwork = "{$vendorDir}/antecedent/patchwork/Patchwork.php";

if (is_file($patchwork)) {
    require_once $patchwork;
}

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $autoload);
    $loader = require $autoload;
} else {
    $loader = require $autoload;
}

if ($loader instanceof ClassLoader) {
    $loader->addPsr4('SymPress\\Assets\\', ["{$libDir}/src", "{$libDir}/bundle"], true);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', "{$vendorDir}/johnpbloch/wordpress-core/");
}

$wpError = "{$vendorDir}/johnpbloch/wordpress-core/wp-includes/class-wp-error.php";

if (is_file($wpError)) {
    require_once $wpError;
}

require_once __DIR__ . '/Support/WP_Error.php';
require_once __DIR__ . '/Support/WP_HTML_Tag_Processor.php';
require_once __DIR__ . '/Support/WordPressStubs.php';

unset($testsDir, $libDir, $vendorDir, $autoload, $loader);
