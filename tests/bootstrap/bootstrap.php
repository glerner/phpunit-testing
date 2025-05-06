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

// Display initialization information
echo "\n=== WordPress PHPUnit Testing Framework Bootstrap ===\n";
echo "\n=== Phase 1: Composer Autoloader ===\n";

// Determine the framework root directory
$framework_root = dirname(__DIR__, 2);

// Load Composer autoloader
$autoloader_path = $framework_root . '/vendor/autoload.php';
if (file_exists($autoloader_path)) {
	echo "Loading Composer autoloader from framework\n";
	$autoloader = require $autoloader_path;
} else {
	// Try to find autoloader in parent directories (when used as a submodule)
	$autoloader_path = dirname($framework_root, 2) . '/vendor/autoload.php';
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
	$autoloader->addPsr4('WP_PHPUnit_Framework\\', $framework_root . '/src');
	$autoloader->register();
}

echo "\n=== Phase 2: Environment Setup ===\n";

// Initialize error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');

// Define common constants
if (!defined('WP_PHPUNIT_FRAMEWORK_DIR')) {
	define('WP_PHPUNIT_FRAMEWORK_DIR', $framework_root . '/');
}

// Load settings from .env.testing
$env_file = $framework_root . '/.env.testing';
$loaded_settings = [];

if (file_exists($env_file)) {
    echo "Loading environment variables from .env.testing at: {$env_file}\n";
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse valid setting lines
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*?)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            $loaded_settings[$key] = $value;
        }
    }
}

/**
 * Get a configuration value from environment variables, .env file, or default
 *
 * @param string $name Setting name
 * @param mixed  $default Default value if not found
 * @return mixed Setting value
 */
function get_setting(string $name, mixed $default = null): mixed {
    // Check environment variables first (highest priority)
    $env_value = getenv($name);
    if ($env_value !== false) {
        return $env_value;
    }

    // Check our loaded settings (already loaded from .env.testing)
    global $loaded_settings;
    if (isset($loaded_settings[$name])) {
        return $loaded_settings[$name];
    }

    // Return default if not found
    return $default;
}

// Load specific bootstrap file based on test type
$bootstrap_type = get_setting('PHPUNIT_BOOTSTRAP_TYPE', 'unit');
echo "Loading bootstrap for test type: {$bootstrap_type}\n";

switch ($bootstrap_type) {
	case 'unit':
	    require_once __DIR__ . '/bootstrap-unit.php';
	    break;
	case 'wp-mock':
	    require_once __DIR__ . '/bootstrap-wp-mock.php';
	    break;
	case 'integration':
	    require_once __DIR__ . '/bootstrap-integration.php';
	    break;
	default:
	    echo "ERROR: Unknown bootstrap type: {$bootstrap_type}\n";
	    exit(1);
}

echo "\n=== Bootstrap Complete ===\n";
