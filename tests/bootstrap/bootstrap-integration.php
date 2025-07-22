<?php
/**
 * Bootstrap file for WordPress Integration tests
 *
 * Handles initialization of testing environment for integration tests.
 * Sets up WordPress Test Suite environment and database.
 *
 * IMPORTANT: This file connects our WP_PHPUnit_Framework with the WordPress Test Suite.
 * These are two separate testing libraries:
 * 1. WP_PHPUnit_Framework - Our custom testing framework (this package)
 * - located in FILESYSTEM_WP_ROOT/wp-content/plugins/yourplugin/tests/TEST_FRAMEWORK_DIR
 * 2. WordPress Test Suite - The official WordPress testing library
 * - located in FILESYSTEM_WP_ROOT/wp-content/plugins/wordpress-develop
 *
 * For more information on integration testing strategies, see:
 * @see /docs/guides/phpunit-testing-tutorial.md#integration-tests
 * @see /docs/guides/phpunit-testing-tutorial.md#determining-the-right-test-type
 *
 * @package GL_WordPress_Testing_Framework
 * @subpackage Bootstrap
 */

/**
 * IMPORTANT: PHPUnit Version Compatibility
 *
 * All Integration tests MUST:
 * - Use the WordPress Test Suite
 * - Use PHPUnit 9.6-compatible syntax
 * - Extend WP_UnitTestCase or similar WordPress test classes
 * - Load through *this* bootstrap file
 *
 * All Unit Tests and WP_Mock Tests MUST:
 * - Use PHPUnit 11 syntax
 * - NOT use the WordPress Test Suite
 * - Extend PHPUnit\Framework\TestCase or WP_Mock\Tools\TestCase
 */


declare(strict_types=1);

namespace WP_PHPUnit_Framework\Bootstrap;

use WP_PHPUnit_Framework\Event\Listener\GlTestRunnerExecutionStartedListener;
use WP_PHPUnit_Framework\Event\Listener\GlTestSuiteStartedListener;
use function WP_PHPUnit_Framework\colored_message;
use function WP_PHPUnit_Framework\debug_message;
use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\get_cli_value;
use function WP_PHPUnit_Framework\esc_cli;

// Define WordPress test environment constants if not already defined
if (!defined('WP_TESTS_MULTISITE')) {
    define('WP_TESTS_MULTISITE', false);
}

if (!defined('WP_TESTS_FORCE_KNOWN_BUGS')) {
    define('WP_TESTS_FORCE_KNOWN_BUGS', false);
}

echo "bootstrap-integration using WordPress Test Suite from: $wordpress_test_suite_path\n";

// Load the WordPress Test Suite bootstrap file

// Set path for PHPUnit Polyfills to satisfy WordPress Test Suite requirements
// Polyfills installed in WordPress Test Suite, *not* WP_PHPUnit_Framework
// because the WordPress Test Suite requires PHPUnit Polyfills,
// but the current version of PHPUnit Polyfills doesn't support PHPUnit 11

// Priority: CLI param > WP_PHPUNIT_POLYFILLS_PATH > standard location in WordPress Test Suite
$polyfills_path = get_cli_value('--phpunit-polyfills-path');
if (empty($polyfills_path)) {
    $polyfills_path = get_setting('WP_PHPUNIT_POLYFILLS_PATH');
}

// If not set by CLI or settings, check standard location in WordPress Test Suite
if (empty($polyfills_path) && !empty($wordpress_test_suite_path)) {
    $standard_path = "$wordpress_test_suite_path/vendor/yoast/phpunit-polyfills";
    if (is_dir($standard_path) && file_exists("$standard_path/phpunitpolyfills-autoload.php")) {
        $polyfills_path = $standard_path;

    }
}

if (!empty($polyfills_path)) {
    if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
        /* Do not define WP_TESTS_PHPUNIT_POLYFILLS_PATH here, is defined in
        plugins/wordpress-develop/tests/phpunit/wp-tests-config.php
        */
        debug_message("Using PHPUnit Polyfills from: {$polyfills_path}\n");
    }
} else {
    colored_message("Warning: PHPUnit Polyfills not found. Integration tests may fail.\n", 'yellow');
    colored_message("The polyfills should be installed in {$wordpress_test_suite_path}/vendor/yoast/phpunit-polyfills\n", 'yellow');
    colored_message("You can also set WP_PHPUNIT_POLYFILLS_PATH in .env.testing to override the location.\n", 'yellow');
}

echo "Loading WordPress Test Suite bootstrap from: {$wordpress_test_suite_path}/includes/bootstrap.php\n";

// Check if WP_TESTS_PHPUNIT_PATH is already defined (likely in wp-tests-config.php)
if (defined('WP_TESTS_PHPUNIT_PATH')) {
    debug_message("WP_TESTS_PHPUNIT_PATH is already defined as: " . WP_TESTS_PHPUNIT_PATH);
} else {
    // First check for our parallel PHPUnit 9.6 installation
    $phpunit96_path = dirname(__DIR__, 2) . '/phpunit96/vendor/bin/phpunit';
    if (file_exists($phpunit96_path)) {
        define('WP_TESTS_PHPUNIT_PATH', $phpunit96_path);
        debug_message("Using PHPUnit 9.6 from parallel installation: $phpunit96_path");
    } else {
        // If not found, check if WP_TESTS_DIR_CONTAINER is set
        $wp_tests_dir_container = get_setting('WP_TESTS_DIR_CONTAINER');

        if (!empty($wp_tests_dir_container)) {
            // Use WP_TESTS_DIR_CONTAINER if available
            define('WP_TESTS_PHPUNIT_PATH', "$wp_tests_dir_container/vendor/bin/phpunit");
            debug_message("Setting WP_TESTS_PHPUNIT_PATH from WP_TESTS_DIR_CONTAINER: $wp_tests_dir_container/vendor/bin/phpunit");
        } else {
            // Fallback to constructing from WP_ROOT
            $wp_root = get_setting('WP_ROOT');
            if (!empty($wp_root)) {
                define('WP_TESTS_PHPUNIT_PATH', "$wp_root/wp-content/plugins/wordpress-develop/tests/phpunit/vendor/bin/phpunit");
                debug_message("Setting WP_TESTS_PHPUNIT_PATH from WP_ROOT: $wp_root/wp-content/plugins/wordpress-develop/tests/phpunit/vendor/bin/phpunit");
            }
        }
    }
}

debug_message("Before loading WordPress Test Suit, WP_TESTS_PHPUNIT_PATH is: " . WP_TESTS_PHPUNIT_PATH . "\n");

// require_once $wordpress_test_suite_path . '/includes/bootstrap.php';

// Register the event subscribers for PHPUnit 11+ only
// Skip for PHPUnit 9.6 which doesn't have the Event\Facade class
$phpunit_version = defined('PHPUNIT_VERSION') ? PHPUNIT_VERSION : (defined('PHPUnit\Runner\Version::VERSION') ? PHPUnit\Runner\Version::VERSION : '');
if (version_compare($phpunit_version, '10.0.0', '>=') && class_exists('PHPUnit\Event\Facade')) {
    echo "- Registering event subscribers with PHPUnit $phpunit_version\n";
    $subscriber = new GlTestRunnerExecutionStartedListener($logDir);
    \PHPUnit\Event\Facade::instance()->registerSubscriber($subscriber);

    $subscriber = new GlTestSuiteStartedListener($logDir);
    \PHPUnit\Event\Facade::instance()->registerSubscriber($subscriber);
} else {
    echo "- Skipping event subscribers for PHPUnit $phpunit_version (only available in PHPUnit 10+)\n";
}

// Initialize Mockery for integration tests
echo "Setting up Mockery for integration tests\n";
\Mockery::globalHelpers();

// Register shutdown function to clean up Mockery
register_shutdown_function(function() {
	\Mockery::close();
});

echo "Integration test bootstrap complete\n";
