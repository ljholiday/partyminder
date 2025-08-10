<?php
/**
 * PHPUnit Bootstrap File for PartyMinder Plugin Tests
 */

// Composer autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Plugin constants (only define if not already defined)
if (!defined('PARTYMINDER_VERSION')) {
    define('PARTYMINDER_VERSION', '1.0.0');
}
if (!defined('PARTYMINDER_PLUGIN_DIR')) {
    define('PARTYMINDER_PLUGIN_DIR', dirname(__DIR__) . '/');
}
if (!defined('PARTYMINDER_PLUGIN_URL')) {
    define('PARTYMINDER_PLUGIN_URL', 'http://example.org/wp-content/plugins/partyminder/');
}
if (!defined('PARTYMINDER_PLUGIN_FILE')) {
    define('PARTYMINDER_PLUGIN_FILE', dirname(__DIR__) . '/partyminder.php');
}

// Determine if we're running unit tests or integration tests
$is_unit_test = false;
$argv = $_SERVER['argv'] ?? [];
foreach ($argv as $arg) {
    if (strpos($arg, '--testsuite=unit') !== false || strpos($arg, 'unit') !== false) {
        $is_unit_test = true;
        break;
    }
}

// Also check environment variable for explicit control
if (getenv('PARTYMINDER_TEST_TYPE') === 'unit') {
    $is_unit_test = true;
}

if ($is_unit_test) {
    // Unit tests - use Brain\Monkey to mock WordPress
    require_once __DIR__ . '/bootstrap-unit.php';
} else {
    // Integration tests - use WordPress test suite
    require_once __DIR__ . '/bootstrap-integration.php';
}