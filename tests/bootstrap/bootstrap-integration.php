<?php
/**
 * Bootstrap file for WordPress Integration tests
 *
 * Handles initialization of testing environment for integration tests.
 * Sets up WordPress test environment and database.
 *
 * For more information on integration testing strategies, see:
 * @see /docs/guides/phpunit-testing-tutorial.md#integration-tests
 * @see /docs/guides/phpunit-testing-tutorial.md#determining-the-right-test-type
 *
 * @package GL_WordPress_Testing_Framework
 * @subpackage Bootstrap
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Bootstrap;

use WP_PHPUnit_Framework\Event\Listener\GlTestRunnerExecutionStartedListener;
use WP_PHPUnit_Framework\Event\Listener\GlTestSuiteStartedListener;
use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\esc_cli;

// Load settings
$filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT');

// Define WordPress test environment constants if not already defined
if (!defined('WP_TESTS_MULTISITE')) {
    define('WP_TESTS_MULTISITE', false);
}

if (!defined('WP_TESTS_FORCE_KNOWN_BUGS')) {
    define('WP_TESTS_FORCE_KNOWN_BUGS', false);
}

// Attempt to locate WordPress test library
echo "\n=== Integration Test Setup ===\n";
echo "Locating WordPress test library...\n";

// Try to get WP_TESTS_DIR from environment or settings
$wp_tests_dir = get_setting('WP_TESTS_DIR');

// If not set, try common locations
if (empty($wp_tests_dir) || !is_dir($wp_tests_dir)) {
    $possible_paths = [
        "$filesystem_wp_root/wp-content/plugins/wordpress-develop/tests/phpunit",
        "/tmp/wordpress-tests-lib",
        "/tmp/wordpress-tests-lib-phpunit",
        dirname(dirname(FRAMEWORK_DIR)) . '/wordpress-develop/tests/phpunit',
    ];
    
    foreach ($possible_paths as $path) {
        if (is_dir($path)) {
            $wp_tests_dir = $path;
            break;
        }
    }
}

// Bail if we couldn't find the tests directory
if (empty($wp_tests_dir) || !is_dir($wp_tests_dir)) {
    echo "ERROR: WordPress test library not found. Tried:\n";
    echo "- " . ($wp_tests_dir ?? '(not set)') . "\n";
    foreach ($possible_paths ?? [] as $path) {
        echo "- $path\n";
    }
    echo "\nPlease set WP_TESTS_DIR in your .env.testing to the path of the WordPress test library.\n";
    echo "This is typically where setup-plugin-tests.php installs it.\n";
    exit(1);
}

echo "Using WordPress test library from: $wp_tests_dir\n";

// The main bootstrap.php file handles autoloading, so it is not needed here.

// Load the WordPress test bootstrap file
echo "Loading WordPress test include from: {$wp_tests_dir}/includes/bootstrap.php\n";
require_once $wp_tests_dir . '/includes/bootstrap.php';

// Register the event subscribers for PHPUnit 11+
if (class_exists('PHPUnit\Event\Facade')) {
    echo "- Registering event subscribers with PHPUnit\n";
    $subscriber = new GlTestRunnerExecutionStartedListener($logDir);
    \PHPUnit\Event\Facade::instance()->registerSubscriber($subscriber);

    $subscriber = new GlTestSuiteStartedListener($logDir);
    \PHPUnit\Event\Facade::instance()->registerSubscriber($subscriber);
}

// Initialize Mockery for integration tests
echo "Setting up Mockery for integration tests\n";
\Mockery::globalHelpers();

// Register shutdown function to clean up Mockery
register_shutdown_function(function() {
	\Mockery::close();
});

echo "Integration test bootstrap complete\n";
