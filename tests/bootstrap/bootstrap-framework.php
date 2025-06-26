<?php
/**
 * Bootstrap file for Framework tests
 *
 * Handles initialization of testing environment for framework tests.
 * This bootstrap is specifically for testing the framework itself,
 * not for use by developers using the framework.
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Bootstrap
 */

declare(strict_types=1);
namespace WP_PHPUnit_Framework\Bootstrap;

// Initialize error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');

// First, include the framework-functions.php to get access to the load_settings_file function
// and any other functions that stubs might depend on
// Include the framework utility functions
require_once dirname(dirname(__DIR__)) . '/bin/framework-functions.php';

// Load only the WP_UnitTestCase stub which is needed for all framework tests
// Other stubs are loaded on demand in specific tests
require_once dirname(__DIR__) . '/framework/stubs/WP_UnitTestCase.php';

// Load environment variables from .env.testing first
// First try the current directory where the script is being run from
$current_dir_env_file = getcwd() . '/.env.testing';
$framework_dir_env_file = dirname(dirname(__DIR__)) . '/.env.testing';

// Try current directory first, then framework directory
if (file_exists($current_dir_env_file)) {
    $env_file = $current_dir_env_file;
    echo "Loading environment variables from current directory: $env_file\n";
} else {
    $env_file = $framework_dir_env_file;
    echo "Loading environment variables from framework directory: $env_file\n";
}

// Use the existing load_settings_file function
$settings = \WP_PHPUnit_Framework\load_settings_file($env_file);

// Set the global $loaded_settings variable for get_setting() function to use
global $loaded_settings;
$loaded_settings = $settings;

// Now include the setup-plugin-tests.php file
require_once dirname(dirname(__DIR__)) . '/bin/setup-plugin-tests.php';

// Initialize Mockery
echo "Setting up Mockery for framework tests\n";
\Mockery::globalHelpers();

// Load any additional test dependencies
echo "Loading framework test dependencies\n";

// Set up Brain\Monkey if available
if (class_exists('\Brain\Monkey')) {
	echo "Setting up Brain\\Monkey\n";
	\Brain\Monkey\setUp();

	// Register teardown function to clean up Brain\Monkey
	register_shutdown_function(function() {
		\Brain\Monkey\tearDown();
	});

	echo "For Brain\Monkey usage examples, see:\n";
	echo "  /docs/guides/phpunit-testing-tutorial.md#using-brain-monkey-for-wordpress-functions\n";
}

echo "Framework test bootstrap complete\n";
