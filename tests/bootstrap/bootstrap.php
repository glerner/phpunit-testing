<?php
/**
 * Main bootstrap file for the WordPress PHPUnit Testing Framework
 *
 * This file serves as the entry point for all test types and handles
 * common initialization tasks.
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Bootstrap
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Bootstrap;

use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\esc_cli;

/* in Bootstrap:
 * __DIR__ is your-plugin/tests/bootstrap
 * SCRIPT_DIR should be your-plugin/tests/bin
 * PROJECT_DIR should be your-plugin
*/
define('SCRIPT_DIR', dirname(__DIR__, 2) . '/tests/bin');
define('PROJECT_DIR', dirname(__DIR__, 2));

echo "Script dir: " . SCRIPT_DIR . "\n";
echo "Project dir: " . PROJECT_DIR . "\n";
// Include the framework utility functions
require_once SCRIPT_DIR . '/framework-functions.php';

// Display initialization information
echo "\n=== WordPress PHPUnit Testing Framework Bootstrap ===\n";
echo "\n=== Phase 1: Composer Autoloader ===\n";

// Determine the framework root directory
$wp_plugin_root = dirname(__DIR__, 2);
echo "Your Plugin root directory in WordPress: $wp_plugin_root\n";

// Load Composer autoloader
$autoloader_path = $wp_plugin_root . '/tests/vendor/autoload.php';
echo "Loading Composer autoloader from $autoloader_path\n";
if (file_exists($autoloader_path)) {
	$autoloader = require $autoloader_path;
} else {
	// Try to find autoloader in parent directories (when used as a submodule)
	$autoloader_path = dirname($wp_plugin_root, 2) . '/vendor/autoload.php';
	echo "Loading Composer autoloader from $autoloader_path\n";
	if (file_exists($autoloader_path)) {
	    echo "Loading Composer autoloader from parent project\n";
	    $autoloader = require $autoloader_path;
	} else {
	    echo "ERROR: Composer autoloader not found\n";
	    echo "Please run 'composer install' in the project root\n";
	    exit(1);
	}
}

// Register framework classes with autoloader if needed
if ($autoloader instanceof \Composer\Autoload\ClassLoader) {
	echo "Registering framework PSR-4 prefixes\n";
	$autoloader->addPsr4('WP_PHPUnit_Framework\\', $wp_plugin_root . '/src');
	$autoloader->register();
}

echo "\n=== Phase 2: Environment Setup ===\n";

// Initialize error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');

// Define common constants
if (!defined('WP_PHPUNIT_FRAMEWORK_DIR')) {
	define('WP_PHPUNIT_FRAMEWORK_DIR', $wp_plugin_root . '/');
}

// Load settings from .env.testing
global $loaded_settings;
$env_file = PROJECT_DIR . '/tests/.env.testing';
$loaded_settings = load_settings_file($env_file);

// Find the WordPress test library
$wp_tests_dir = get_setting('WP_TESTS_DIR', get_setting('FILESYSTEM_WP_ROOT') . '/wp-content/plugins/wordpress-develop/tests/phpunit');

if (!$wp_tests_dir || !is_dir($wp_tests_dir)) {
	echo "ERROR: WordPress test library not found in $wp_tests_dir.\n";
	echo "Please set WP_TESTS_DIR in your .env.testing to the path of the WordPress test library.\n";
	echo "That is where setup-plugin-tests.php installs it.\n";
	exit(1);
}

// Load specific bootstrap file based on test type
$bootstrap_type = get_setting('PHPUNIT_BOOTSTRAP_TYPE', 'unit');
$bootstrap_folder = PROJECT_DIR . '/tests/bootstrap';
echo "Loading bootstrap $bootstrap_folder for test type: {$bootstrap_type}\n";

switch ($bootstrap_type) {
	case 'unit':
	    require_once $bootstrap_folder . '/bootstrap-unit.php';
	    break;
	case 'wp-mock':
	    require_once $bootstrap_folder . '/bootstrap-wp-mock.php';
	    break;
	case 'integration':
	    require_once $bootstrap_folder . '/bootstrap-integration.php';
	    break;
	default:
	    echo "ERROR: Unknown bootstrap type: {$bootstrap_type}\n";
	    exit(1);
}

echo "\n=== Bootstrap Complete ===\n";
