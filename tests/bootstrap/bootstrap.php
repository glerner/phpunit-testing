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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

// When this file is loaded from within the framework itself
if (!defined('FRAMEWORK_DIR')) {
    // The framework root is three directories up from this file
    define('FRAMEWORK_DIR', dirname(__DIR__, 3));
}

// The project root is one level up from the framework directory
define('PROJECT_DIR', dirname(FRAMEWORK_DIR, 2));

// Debug output
echo "=== Framework Bootstrap Debug ===\n";
echo "Current file: " . __FILE__ . "\n";
echo "FRAMEWORK_DIR: " . FRAMEWORK_DIR . "\n";
echo "PROJECT_DIR: " . PROJECT_DIR . "\n";
echo "Current working directory: " . getcwd() . "\n\n";

// Verify the framework directory exists
if (!is_dir(FRAMEWORK_DIR)) {
    die("Error: Could not find the PHPUnit Testing Framework at: " . FRAMEWORK_DIR . "\n");
}

namespace WP_PHPUnit_Framework\Bootstrap;

use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\esc_cli;

/*
 * Bootstrap file for the WordPress PHPUnit Testing Framework
 *
 * Paths:
 * - __DIR__: your-plugin/tests/gl-phpunit-test-framework/tests/bootstrap
 * - FRAMEWORK_DIR: your-plugin/tests/gl-phpunit-test-framework (or vendor/glerner/phpunit-testing)
 * - PROJECT_DIR: your-plugin
 * - SCRIPT_DIR: FRAMEWORK_DIR/bin
 */

// Define the project root (your-plugin)
// Goes up from tests/bootstrap to project root
define('PROJECT_DIR', dirname(__DIR__, 2));

// Find the framework root (could be in tests/gl-phpunit-test-framework or vendor/glerner/phpunit-testing)
$framework_dir = null;
$possible_paths = [
    PROJECT_DIR . '/tests/gl-phpunit-test-framework',
    PROJECT_DIR . '/vendor/glerner/phpunit-testing'
];

foreach ($possible_paths as $path) {
    if (is_dir($path)) {
        $framework_dir = $path;
        break;
    }
}

// Verify framework directory exists
if (!$framework_dir || !is_dir($framework_dir)) {
    $msg = "Error: Could not find the PHPUnit Testing Framework.\n" .
           "Searched in:\n" .
           "- " . PROJECT_DIR . "/tests/gl-phpunit-test-framework\n" .
           "- " . PROJECT_DIR . "/vendor/glerner/phpunit-testing\n" .
           "\nCurrent PROJECT_DIR: " . PROJECT_DIR . "\n" .
           "Current working directory: " . getcwd() . "\n" .
           "\nPlease ensure the framework is installed as a submodule or via Composer.\n";
    die($msg);
}

define('FRAMEWORK_DIR', $framework_dir);

// Set up error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Don't throw if error reporting is turned off
    if (!(error_reporting() & $errno)) {
        return false;
    }

    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Register the Composer autoloader
$autoloader = null;
$wp_plugin_root = dirname(PROJECT_DIR);

// Try to find the Composer autoloader in common locations
$possible_autoloaders = [
    $wp_plugin_root . '/vendor/autoload.php',
    $wp_plugin_root . '/../../vendor/autoload.php',
    $wp_plugin_root . '/../../../vendor/autoload.php',
    $wp_plugin_root . '/../../../../vendor/autoload.php',
];

foreach ($possible_autoloaders as $autoloader_path) {
    if (file_exists($autoloader_path)) {
        $autoloader = require_once $autoloader_path;
        break;
    }
}

if ($autoloader === null) {
    die("Error: Could not find Composer's autoloader. Please run 'composer install' in the project root.\n");
}

// Check for verbose mode from either command line or environment
$is_verbose = false;

// Check for VERBOSE in environment or .env.testing
if (get_setting('VERBOSE', false)) {
    $is_verbose = true;
}
// Check for command line verbose flags
else if (isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) {
    $verbosity_flags = ['--verbose', '-v', '-vv', '-vvv'];
    $is_verbose = count(array_intersect($verbosity_flags, $GLOBALS['argv'])) > 0;
}

// Register framework classes with autoloader if we have a valid autoloader
if ($autoloader instanceof \Composer\Autoload\ClassLoader) {
    if ($is_verbose) {
        echo "Registering framework PSR-4 prefixes\n";
    }
    // Register framework's own classes
    $autoloader->addPsr4('WP_PHPUnit_Framework\\', FRAMEWORK_DIR . '/src');
    // Register plugin's classes
    $autoloader->addPsr4('GL_Reinvent\\', $wp_plugin_root . '/src');
    $autoloader->register();
} else if ($is_verbose) {
    echo "WARNING: Could not register PSR-4 prefixes - no valid autoloader found\n";
}

if ($is_verbose) {
    echo "=== Autoloader Debug ===\n";

    // Debug: Show all registered autoload functions
    $autoloaders = spl_autoload_functions();
    echo "Registered autoload functions:\n";
    foreach ($autoloaders as $i => $loader) {
        if (is_array($loader)) {
            $class = is_object($loader[0]) ? get_class($loader[0]) : $loader[0];
            echo sprintf("  [%d] %s::%s\n", $i, $class, $loader[1]);
        } else if (is_string($loader)) {
            echo sprintf("  [%d] %s\n", $i, $loader);
        } else {
            echo sprintf("  [%d] %s\n", $i, gettype($loader));
        }
    }
    echo "\n";
}

// Register plugin classes with autoloader
if (isset($autoloader) && $autoloader instanceof \Composer\Autoload\ClassLoader) {
    if ($is_verbose) {
        echo "Registering plugin PSR-4 prefixes\n";
    }

    $autoloader->addPsr4('GL_Reinvent\\', $wp_plugin_root . '/src');

    if ($is_verbose) {
        echo "=== After Plugin PSR-4 \n";
        echo "Autoloader paths:\n";
        print_r($autoloader->getPrefixesPsr4());
        echo "\n";
    }
} else if ($is_verbose) {
    echo "=== WARNING: Could not register plugin PSR-4 - autoloader not available\n\n";
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
