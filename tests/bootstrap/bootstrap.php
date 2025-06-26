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
use function WP_PHPUnit_Framework\has_cli_flag;
use function WP_PHPUnit_Framework\esc_cli;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Check if we are running the framework's own tests.
if (getenv('TEST_TYPE') === 'framework') {
    // In this context, the framework IS the project.
    define('FRAMEWORK_DIR', dirname(__DIR__, 2));
    define('PROJECT_DIR', FRAMEWORK_DIR);
    require_once FRAMEWORK_DIR . '/bin/framework-functions.php';
} else {
    // Otherwise, we are in a project (e.g., a plugin) that is USING the framework.

    // Define the project root directory. This bootstrap file is expected to be at [PROJECT_DIR]/tests/bootstrap/bootstrap.php
    define('PROJECT_DIR', dirname(__DIR__, 2));

    // The framework's utility functions are expected to be copied to the project's bin directory.
    $functions_file = PROJECT_DIR . '/bin/framework-functions.php';
    if (!file_exists($functions_file)) {
        die(
            "Error: The framework functions file is missing.\n" .
            "Searched at: " . $functions_file . "\n" .
            "Please ensure you have run the sync script to copy the framework files to your project.\n"
        );
    }
    require_once $functions_file;

    // Load settings from .env.testing, which is required to find the framework directory.
    $env_file = PROJECT_DIR . '/tests/.env.testing';
    load_settings_file($env_file);

    // Define FRAMEWORK_DIR from the settings file.
    $framework_dir_path = PROJECT_DIR . '/tests/' . get_setting('FRAMEWORK_DIR');
    if (empty($framework_dir_path) || !is_dir($framework_dir_path)) {
        die(
            "Error: FRAMEWORK_DIR is not defined or is not a valid directory.\n" .
            "Please define FRAMEWORK_DIR in your " . $env_file . " file.\n" .
            "It should point to the root of the gl-phpunit-test-framework directory.\n"
        );
    }
    define('FRAMEWORK_DIR', $framework_dir_path);
}

// Optional debug output
if (has_cli_flag(['--debug-bootstrap'])) {
    echo "=== Framework Bootstrap Debug ===\n";
    echo "Current file: " . __FILE__ . "\n";
    echo "PROJECT_DIR: " . PROJECT_DIR . "\n";
    echo "FRAMEWORK_DIR: " . FRAMEWORK_DIR . "\n";
    echo "Current working directory: " . getcwd() . "\n\n";
}

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

// A function to find the Composer autoloader instance from the registered spl_autoload_functions
function find_composer_autoloader() {
    foreach (spl_autoload_functions() as $loader) {
        if (is_array($loader) && $loader[0] instanceof \Composer\Autoload\ClassLoader) {
            return $loader[0];
        }
    }
    return null;
}

// First, check if the autoloader is already in memory.
$autoloader = find_composer_autoloader();

// If not found in memory, search the filesystem, require the file, and then check again.
if ( ! $autoloader) {
    if (get_setting('VERBOSE', false)) {
        echo "NOTICE: Composer autoloader not found in memory. Searching filesystem.\n";
    }

    // Try to find the Composer autoloader in common locations
    $possible_autoloaders = [
        // The framework's own autoloader is the top priority.
        FRAMEWORK_DIR . '/vendor/autoload.php',
        // Next, check for the project's (the plugin's) autoloader.
        PROJECT_DIR . '/vendor/autoload.php',
        // Fallback to searching in parent directories for other setups.
        $wp_plugin_root . '/vendor/autoload.php',
        $wp_plugin_root . '/../../vendor/autoload.php',
        $wp_plugin_root . '/../../../vendor/autoload.php',
        $wp_plugin_root . '/../../../../vendor/autoload.php',
    ];

    $autoloader_found_path = false;
    foreach ($possible_autoloaders as $autoloader_path) {
        if (file_exists($autoloader_path)) {
            require_once $autoloader_path;
            $autoloader_found_path = true;
            break;
        }
    }

    // If we loaded it from a file, find the instance again.
    if ($autoloader_found_path) {
        $autoloader = find_composer_autoloader();
    }
}

if ($autoloader === null) {
    die("Error: Could not find Composer's autoloader. Please run 'composer install' in the project root.\n");
}

// Check for verbose mode from either command line or environment
$is_verbose = false;

// Check for verbosity in environment, .env.testing, or command-line flags
$verbosity_flags = ['--verbose', '-v', '-vv', '-vvv'];
$is_verbose = get_setting('VERBOSE', false) || has_cli_flag($verbosity_flags);

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

// Define the log directory for event listeners.
$logDir = get_setting('WP_PHPUNIT_TEST_LOG_DIR');

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
