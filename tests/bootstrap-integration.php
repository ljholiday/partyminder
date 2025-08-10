<?php
/**
 * Integration Tests Bootstrap - Uses WordPress Test Suite
 */

// WordPress test suite configuration
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    // Try our local download first, then fallback to temp directory
    $_tests_dir = dirname(__DIR__) . '/tmp/wordpress-tests-lib/tests/phpunit';
    if (!file_exists($_tests_dir . '/includes/functions.php')) {
        $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
    }
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find WordPress test suite at $_tests_dir\n";
    echo "Please set WP_TESTS_DIR environment variable or install WordPress test suite.\n";
    echo "Run: wp scaffold plugin-tests partyminder\n";
    exit(1);
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    require dirname(__DIR__) . '/partyminder.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load Polyfills for PHPUnit cross-version compatibility
if (class_exists('Yoast\PHPUnitPolyfills\Autoload')) {
    require_once dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
}