<?php
/**
 * Sync Plugin to WordPress and Run Tests
 *
 * This script syncs your plugin to WordPress and runs PHPUnit tests.
 * It provides a simple way to run tests without requiring Composer or Lando command lines.
 *
 * Usage:
 *   php bin/sync-and-test.php [--unit|--wp-mock|--integration|--all] [--file=<file>] [--coverage] [--verbose]
 *
 * Examples:
 *   php bin/sync-and-test.php --unit
 *   php bin/sync-and-test.php --wp-mock --file=tests/wp-mock/specific-test.php
 *   php bin/sync-and-test.php --integration --coverage
 *   php bin/sync-and-test.php --all --verbose
 *
 * @package WP_PHPUnit_Framework
 */

// phpcs:set WordPress.Security.EscapeOutput customEscapingFunctions[] esc_cli
// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.DB.RestrictedFunctions
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Bin;

/* Define script constants as namespace constants
 * SCRIPT_DIR should be your-plugin/tests/bin
 * PROJECT_DIR should be your-plugin
*/
define('SCRIPT_DIR', __DIR__);
define('PROJECT_DIR', dirname(SCRIPT_DIR,2));

// Include the framework utility functions
require_once SCRIPT_DIR . '/framework-functions.php';

use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\esc_cli;
use function WP_PHPUnit_Framework\make_path;

// Set default timezone to avoid warnings
date_default_timezone_set('UTC');

/**
 * Print a colored message to the console
 *
 * @param string $message The message to print
 * @param string $color   The color to use (green, yellow, red)
 * @return void
 */
function colored_message(string $message, string $color = 'normal'): void {
	$colors = [
		'green'  => "\033[0;32m",
		'yellow' => "\033[1;33m",
		'red'    => "\033[0;31m",
		'blue'   => "\033[0;34m",
		'normal' => "\033[0m",
	];

	$start_color = isset($colors[$color]) ? $colors[$color] : $colors['normal'];
	$end_color   = $colors['normal'];

	echo esc_cli($start_color . $message . $end_color . "\n");
}

/**
 * Print usage information
 *
 * @return void
 */
function print_usage(): void {
	colored_message("Usage:", 'blue');
	echo esc_cli("  php bin/sync-and-test.php [options] [--file=<file>]\n\n");

	colored_message("Options:", 'blue');
	echo esc_cli("  --help          Show this help message\n");
	echo esc_cli("  --unit          Run unit tests (tests that don't require WordPress functions)\n");
	echo esc_cli("  --wp-mock       Run WP Mock tests (tests that mock WordPress functions)\n");
	echo esc_cli("  --integration   Run integration tests (tests that require a WordPress database)\n");
	echo esc_cli("  --all           Run all test types\n");
	echo esc_cli("  --coverage      Generate code coverage report in build/coverage directory\n");
	echo esc_cli("  --verbose       Show verbose output\n");
	echo esc_cli("  --file=<file>   Run a specific test file instead of the entire test suite\n\n");

	colored_message("Examples:", 'blue');
	echo esc_cli("  php bin/sync-and-test.php --unit\n");
	echo esc_cli("  php bin/sync-and-test.php --wp-mock --file=tests/wp-mock/specific-test.php\n");
	echo esc_cli("  php bin/sync-and-test.php --integration --coverage\n");
	echo esc_cli("  php bin/sync-and-test.php --all --verbose\n");
}

// Parse command line arguments
$options = [
	'unit'        => false,
	'wp-mock'     => false,
	'integration' => false,
	'all'         => false,
	'multisite'   => false,
	'coverage'    => false,
	'verbose'     => false,
	'help'        => false,
	'file'        => '',
];

foreach ($argv as $arg) {
	if (strpos($arg, '--file=') === 0) {
		$options['file'] = substr($arg, 7);
	} elseif ($arg === '--unit') {
		$options['unit'] = true;
	} elseif ($arg === '--wp-mock') {
		$options['wp-mock'] = true;
	} elseif ($arg === '--integration') {
		$options['integration'] = true;
	} elseif ($arg === '--all') {
		$options['all'] = true;
	} elseif ($arg === '--coverage') {
		$options['coverage'] = true;
	} elseif ($arg === '--verbose') {
		$options['verbose'] = true;
	} elseif ($arg === '--multisite') {
		$options['multisite'] = true;
	} elseif ($arg === '--help' || $arg === '-h') {
		$options['help'] = true;
	}
}

// If only --multisite is given, default to --integration --multisite
if ($options['multisite'] && !$options['unit'] && !$options['wp-mock'] && !$options['integration'] && !$options['all']) {
	$options['integration'] = true;
}

// Show help if requested or if no test type is specified
if ($options['help'] || (!$options['unit'] && !$options['wp-mock'] && !$options['integration'] && !$options['all'])) {
	print_usage();
	exit(0);
}

// Load settings from .env.testing

$env_file = PROJECT_DIR . '/tests/.env.testing';
colored_message("Loading settings from .env.testing...", 'blue');
global $loaded_settings;
$loaded_settings = load_settings_file($env_file);

// Define paths from settings

// FILESYSTEM_WP_ROOT is required - no default fallback
$filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT');
if (empty($filesystem_wp_root)) {
	colored_message("Error: FILESYSTEM_WP_ROOT setting is not set.", 'red');
	colored_message("Please set this in your .env.testing file or environment.", 'red');
	exit(1);
}

$your_plugin_slug = get_setting('YOUR_PLUGIN_SLUG', 'gl-phpunit-testing-framework');
$folder_in_wordpress = get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');
$wp_root = get_setting('WP_ROOT', '/app');
$your_plugin_dest = $filesystem_wp_root . '/' . $folder_in_wordpress . '/' . $your_plugin_slug;
$tests_dir = PROJECT_DIR . '/tests';

// Path to plugin/theme tests directory inside the container (for Lando)
// Canonical plugin/theme tests directory, always inside the 'WordPress root' (WP_ROOT)
// This works for both container and host, as long as WP_ROOT is set appropriately in .env.testing
// Canonical plugin/theme tests directory, always inside the 'WordPress root' (WP_ROOT)
// Uses make_path() for normalization
$container_plugin_dest = make_path($wp_root, $folder_in_wordpress, $your_plugin_slug, 'tests');

$test_error_log = get_setting('TEST_ERROR_LOG', '/tmp/phpunit-testing.log');

// Step 1: Sync plugin to WordPress
colored_message("\nStep 1: Syncing project to WordPress...", 'green');

// Ensure vendor directory exists in source
if (!is_dir("$tests_dir/vendor")) {
	colored_message("Missing composer dependencies in $tests_dir/vendor...", 'yellow');
	error_log("Missing composer dependencies in $tests_dir/vendor\n", 3, $test_error_log);
	exit(1);
}

// Create destination directory if it doesn't exist
if (!is_dir($your_plugin_dest)) {
	@mkdir($your_plugin_dest, 0755, true);
	if (!is_dir($your_plugin_dest)) {
		colored_message("Warning: Could not create destination directory. This might be a permissions issue.", 'yellow');
		colored_message("If using Lando, you may need to run this command within the Lando environment.", 'yellow');
	}
}

// Call the existing sync-to-wp.php script
$sync_script = SCRIPT_DIR . '/sync-to-wp.php';
if (!file_exists($sync_script)) {
	colored_message("Error: Could not find sync-to-wp.php script at $sync_script", 'red');
	error_log("Could not find sync-to-wp.php script at $sync_script\n", 3, $test_error_log);
	exit(1);
}

// Execute the sync script
colored_message("Executing sync-to-wp.php...", 'blue');
$sync_cmd = "php $sync_script";
if ($options['verbose']) {
	echo esc_cli("Command: $sync_cmd\n");
}
passthru($sync_cmd, $sync_return);

if ($sync_return !== 0) {
	colored_message("Error: sync-to-wp.php failed with exit code $sync_return", 'red');
	error_log("sync-to-wp.php failed with exit code $sync_return\n", 3, $test_error_log);
	exit($sync_return);
}

// Step 2: Change to the WordPress plugin directory
colored_message("\nStep 2: Changing to WordPress plugin directory tests...", 'green');
if (!chdir($your_plugin_dest . '/tests')) {
	colored_message("Error: Could not change to WordPress plugin directory: $your_plugin_dest/tests", 'red');
	error_log("Error: Could not change to WordPress plugin directory: $your_plugin_dest/tests\n", 3, $test_error_log);
	exit(1);
}
colored_message("Current directory: " . getcwd(), 'blue');

// Step 3: Run the tests
colored_message("\nStep 3: Running tests...", 'green');

// Lando detection logic
$ssh_command = get_setting('SSH_COMMAND', 'none');
$wp_root = get_setting('WP_ROOT', '');
$in_lando = \WP_PHPUnit_Framework\is_lando_environment();

// If WP_ROOT is /app but not in Lando, error
if ($wp_root === '/app' && !$in_lando) {
	colored_message("Error: WP_ROOT is set to /app but not running in a Lando environment.", 'red');
	colored_message("Please run this script inside Lando or set WP_ROOT to your local WordPress path.", 'red');
	exit(1);
}

function run_phpunit_command($cmd, $options, $in_lando, $cmd_dir) {
	if ($in_lando) {
		// Lando: run PHPUnit in the container at the correct directory
		$lando_cmd = "lando ssh -c 'cd $cmd_dir && $cmd'";
		colored_message("Executing in Lando: $lando_cmd", 'blue');
		passthru($lando_cmd, $phpunit_return);
	} else {
		// Host: cd to the correct directory and run the command
		$host_cmd = "cd $cmd_dir && $cmd";
		colored_message("Executing on host: $host_cmd", 'blue');
		passthru($host_cmd, $phpunit_return);
	}
	return $phpunit_return;
}

if ($options['unit']) {
	colored_message("\n=== Running unit tests...", 'blue');
	$phpunit_cmd = build_phpunit_command('phpunit-unit.xml.dist', 'unit', $options, $container_plugin_dest);
	colored_message("Executing: $phpunit_cmd", 'blue');
	$phpunit_return = run_phpunit_command($phpunit_cmd, $options, $in_lando, $container_plugin_dest);
} elseif ($options['wp-mock']) {
	colored_message("\n=== Running WP Mock tests...", 'blue');
	$phpunit_cmd = build_phpunit_command('phpunit-wp-mock.xml.dist', 'wp-mock', $options, $container_plugin_dest);
	colored_message("Executing: $phpunit_cmd", 'blue');
	$phpunit_return = run_phpunit_command($phpunit_cmd, $options, $in_lando, $container_plugin_dest);
} elseif ($options['integration']) {
	colored_message("\n=== Running integration tests...", 'blue');
	// Use multisite config if --multisite is set
	$integration_config = $options['multisite'] ? 'phpunit-multisite.xml.dist' : 'phpunit-integration.xml.dist';
	$phpunit_cmd = build_phpunit_command($integration_config, 'integration', $options, $container_plugin_dest);
	colored_message("Executing: $phpunit_cmd", 'blue');
	$phpunit_return = run_phpunit_command($phpunit_cmd, $options, $in_lando, $container_plugin_dest);
} elseif ($options['all']) {
	colored_message("\n=== Running all tests sequentially...", 'green');

	// Run unit tests
	colored_message("\n=== Running unit tests...", 'blue');
	$unit_cmd = build_phpunit_command('phpunit-unit.xml.dist', 'unit', $options, $container_plugin_dest);
	colored_message("Executing: $unit_cmd", 'blue');
	$unit_return = run_phpunit_command($unit_cmd, $options, $in_lando, $container_plugin_dest);

	// Run WP Mock tests
	colored_message("\n=== Running WP Mock tests...", 'blue');
	$wp_mock_cmd = build_phpunit_command('phpunit-wp-mock.xml.dist', 'wp-mock', $options, $container_plugin_dest);
	colored_message("Executing: $wp_mock_cmd", 'blue');
	$wp_mock_return = run_phpunit_command($wp_mock_cmd, $options, $in_lando, $container_plugin_dest);

	// Run integration tests
	colored_message("\n=== Running integration tests...", 'blue');
	$integration_cmd = build_phpunit_command('phpunit-integration.xml.dist', 'integration', $options, $container_plugin_dest);
	colored_message("Executing: $integration_cmd", 'blue');
	$integration_return = run_phpunit_command($integration_cmd, $options, $in_lando, $container_plugin_dest);

	// Check if any test suite failed
	if ($unit_return !== 0 || $wp_mock_return !== 0 || $integration_return !== 0) {
		colored_message("\nSome tests failed:", 'red');
		if ($unit_return !== 0) colored_message("  - Unit tests failed with exit code $unit_return", 'red');
		if ($wp_mock_return !== 0) colored_message("  - WP Mock tests failed with exit code $wp_mock_return", 'red');
		if ($integration_return !== 0) colored_message("  - Integration tests failed with exit code $integration_return", 'red');
		exit(1);
	}

	colored_message("\nAll test suites completed successfully! 🎉", 'green');

	// Skip the regular PHPUnit execution since we've already run all test types
	exit(0);
}

// Check if tests passed
if ($phpunit_return === 0) {
	colored_message("\nTests completed successfully! 🎉", 'green');

	// Show coverage report path if generated
	if ($options['coverage']) {
		$coverage_path = $plugin_dest . '/build/coverage/index.html';
		colored_message("Code coverage report is available at:", 'blue');
		colored_message($coverage_path, 'yellow');
		colored_message("You can view this report by opening it in a web browser.", 'blue');
	}
} else {
	colored_message("\nTests failed with exit code $phpunit_return", 'red');
}

exit($phpunit_return);

/**
 * Build a PHPUnit command with the appropriate options
 *
 * @param string $config_file PHPUnit XML config file name (e.g. phpunit-unit.xml.dist)
 * @param string $test_type   The test type (unit, wp-mock, integration)
 * @param array  $options     Command line options
 * @param string $test_dir    Path to the tests directory (container or host, depending on context)
 * @return string             The full PHPUnit command to execute
 *
 * Uses tests/vendor/bin/phpunit as the executable path because Composer's vendor-dir is set to tests/vendor.
 * This ensures the correct project-specific PHPUnit version is used, per Composer best practices.
 */
function build_phpunit_command($config_file, $test_type, $options, $test_dir) {
	global $test_error_log;
	// Build command using canonical test directory, not getcwd()
	$cmd = "$test_dir/vendor/bin/phpunit -c $test_dir/config/{$config_file}";

	// Add verbose option if requested
	if ($options['verbose']) {
		$cmd .= ' --verbose';
	}

	// Add coverage option if requested
	if ($options['coverage']) {
		$cmd .= " --coverage-html build/coverage-{$test_type}";
	}

	// Add specific file if provided
	if (!empty($options['file'])) {
		$cmd .= ' ' . $options['file'];
	}

	error_log("PHPUnit command: $cmd\n",3,$test_error_log);

	return $cmd;
}
