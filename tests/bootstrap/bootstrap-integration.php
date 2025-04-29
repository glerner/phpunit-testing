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

namespace GL\Testing\Framework\Bootstrap;

// Define WordPress test environment constants if not already defined
if (!defined('WP_TESTS_MULTISITE')) {
	define('WP_TESTS_MULTISITE', false);
}

if (!defined('WP_TESTS_FORCE_KNOWN_BUGS')) {
	define('WP_TESTS_FORCE_KNOWN_BUGS', false);
}

// Attempt to locate WordPress test library
echo "Locating WordPress test library\n";

$wp_tests_dir = getenv('WP_TESTS_DIR');

// Try to find the WordPress test library in common locations
if (!$wp_tests_dir) {
	$possible_locations = [
	    // As installed by composer wordpress-dev package
	    dirname(__DIR__, 4) . '/vendor/wordpress/wordpress-develop/tests/phpunit',
	    // As installed via wp-cli scaffold
	    dirname(__DIR__, 4) . '/wp-content/plugins/wordpress-develop/tests/phpunit',
	    // Standard locations
	    '/tmp/wordpress-tests-lib',
	    '/var/www/wordpress-develop/tests/phpunit',
	    '/wordpress-develop/tests/phpunit',
	    // Allow custom path via environment variable
	    getenv('WP_DEVELOP_DIR') . '/tests/phpunit',
	];

	foreach ($possible_locations as $location) {
	    if (is_dir($location)) {
	        $wp_tests_dir = $location;
	        break;
	    }
	}
}

// Bail if we couldn't find the tests directory
if (!$wp_tests_dir || !is_dir($wp_tests_dir)) {
	echo "ERROR: WordPress test library not found.\n";
	echo "Please set the WP_TESTS_DIR environment variable to the path of the WordPress test library.\n";
	echo "See: https://developer.wordpress.org/cli/commands/scaffold/plugin-tests/\n";
	exit(1);
}

// Load the WordPress test bootstrap file
echo "Loading WordPress test bootstrap from: {$wp_tests_dir}\n";
require_once $wp_tests_dir . '/includes/bootstrap.php';

// Initialize Mockery for integration tests
echo "Setting up Mockery for integration tests\n";
\Mockery::globalHelpers();

// Register shutdown function to clean up Mockery
register_shutdown_function(function() {
	\Mockery::close();
});

echo "Integration test bootstrap complete\n";
