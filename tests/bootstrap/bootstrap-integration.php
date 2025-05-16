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

use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\esc_cli;

$filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT');

// Define WordPress test environment constants if not already defined
if (!defined('WP_TESTS_MULTISITE')) {
	define('WP_TESTS_MULTISITE', false);
}

if (!defined('WP_TESTS_FORCE_KNOWN_BUGS')) {
	define('WP_TESTS_FORCE_KNOWN_BUGS', false);
}

// Attempt to locate WordPress test library
echo "Locating WordPress test library\n";

// Use get_setting function from bootstrap.php to get WP_TESTS_DIR
$wp_tests_dir = get_setting('WP_TESTS_DIR', "$filesystem_wp_root/wp-content/plugins/wordpress-develop/tests/phpunit");

// Bail if we couldn't find the tests directory
if (!$wp_tests_dir || !is_dir($wp_tests_dir)) {
	echo "ERROR: WordPress test library not found in $wp_tests_dir.\n";
	echo "Please set WP_TESTS_DIR in your .env.testing to the path of the WordPress test library.\n";
	echo "That is where setup-plugin-tests.php installs it.\n";
	exit(1);
}

// Load the WordPress test bootstrap file
echo "Loading WordPress test include from: {$wp_tests_dir}/includes/bootstrap.php\n";
require_once $wp_tests_dir . '/includes/bootstrap.php';

// Initialize Mockery for integration tests
echo "Setting up Mockery for integration tests\n";
\Mockery::globalHelpers();

// Register shutdown function to clean up Mockery
register_shutdown_function(function() {
	\Mockery::close();
});

echo "Integration test bootstrap complete\n";
