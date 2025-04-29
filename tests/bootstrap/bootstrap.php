<?php
/**
 * Main bootstrap file for the GL WordPress Testing Framework
 *
 * This file serves as the entry point for all test types and handles
 * common initialization tasks.
 *
 * @package GL_WordPress_Testing_Framework
 * @subpackage Bootstrap
 */

declare(strict_types=1);

namespace GL\Testing\Framework\Bootstrap;

// Display initialization information
echo "\n=== GL WordPress Testing Framework Bootstrap ===\n";
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
	$autoloader->addPsr4('GL\\Testing\\Framework\\', $framework_root . '/src');
	$autoloader->register();
}

echo "\n=== Phase 2: Environment Setup ===\n";

// Initialize error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');

// Define common constants
if (!defined('GL_TESTING_FRAMEWORK_DIR')) {
	define('GL_TESTING_FRAMEWORK_DIR', $framework_root . '/');
}

// Load specific bootstrap file based on test type
$bootstrap_type = getenv('PHPUNIT_BOOTSTRAP_TYPE') ?: 'unit';
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
