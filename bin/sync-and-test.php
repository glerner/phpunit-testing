<?php
/**
 * Sync Plugin to WordPress and Run Tests
 *
 * This script syncs your plugin to WordPress and runs PHPUnit tests.
 * It provides a simple way to run tests without requiring Composer or Lando.
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

// Set default timezone to avoid warnings
date_default_timezone_set('UTC');

/**
 * Escape a string for CLI output
 *
 * @param string $text Text to escape
 * @return string
 */
function esc_cli(string $text): string {
	return $text;
}

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
	} elseif ($arg === '--help' || $arg === '-h') {
		$options['help'] = true;
	}
}

// Show help if requested or if no test type is specified
if ($options['help'] || (!$options['unit'] && !$options['wp-mock'] && !$options['integration'] && !$options['all'])) {
	print_usage();
	exit(0);
}

// Load environment variables from .env.testing if it exists
$env_file = dirname(__DIR__) . '/.env.testing';
if (file_exists($env_file)) {
	colored_message("Loading environment variables from .env.testing...", 'blue');
	
	// Read the file line by line to avoid parse_ini_file issues
	$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		// Skip comments
		if (strpos(trim($line), '#') === 0) {
			continue;
		}

		// Parse valid environment variable lines
		if (strpos($line, '=') !== false) {
			list($key, $value) = explode('=', $line, 2);
			$key = trim($key);
			$value = trim($value);

			// Remove quotes if present
			if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
				$value = $matches[2];
			}

			putenv("$key=$value");
		}
	}
}

// Define paths from environment variables
$framework_source = getenv('FRAMEWORK_SOURCE') ? getenv('FRAMEWORK_SOURCE') : dirname(__DIR__);

// FILESYSTEM_WP_ROOT is required - no default fallback
$filesystem_wp_root = getenv('FILESYSTEM_WP_ROOT');
if (empty($filesystem_wp_root)) {
	colored_message("Error: FILESYSTEM_WP_ROOT environment variable is not set.", 'red');
	colored_message("Please set this in your .env.testing file or environment.", 'red');
	exit(1);
}

$framework_dest_name = getenv('FRAMEWORK_DEST_NAME') ? getenv('FRAMEWORK_DEST_NAME') : 'gl-phpunit-testing-framework';
$framework_dest = $filesystem_wp_root . '/wp-content/plugins/' . $framework_dest_name;

colored_message("Using paths:", 'blue');
echo esc_cli("  Framework source: $framework_source\n");
echo esc_cli("  WordPress root: $filesystem_wp_root\n");
echo esc_cli("  Framework destination: $framework_dest\n");

// Step 1: Sync plugin to WordPress
colored_message("\nStep 1: Syncing plugin to WordPress...", 'green');

// Ensure vendor directory exists in source
if (!is_dir("$framework_source/vendor")) {
	colored_message("Installing composer dependencies in source...", 'yellow');
	chdir($framework_source);
	exec('composer install');
}

// Create destination directory if it doesn't exist
if (!is_dir($framework_dest)) {
	@mkdir($framework_dest, 0755, true);
	if (!is_dir($framework_dest)) {
		colored_message("Warning: Could not create destination directory. This might be a permissions issue.", 'yellow');
		colored_message("If using Lando, you may need to run this command within the Lando environment.", 'yellow');
	}
}

// Call the existing sync-to-wp.php script
$sync_script = dirname(__FILE__) . '/sync-to-wp.php';
if (!file_exists($sync_script)) {
	colored_message("Error: Could not find sync-to-wp.php script at $sync_script", 'red');
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
	exit($sync_return);
}

// Step 2: Change to the WordPress plugin directory
colored_message("\nStep 2: Changing to WordPress plugin directory...", 'green');
if (!chdir($framework_dest)) {
	colored_message("Error: Could not change to WordPress plugin directory: $framework_dest", 'red');
	exit(1);
}
colored_message("Current directory: " . getcwd(), 'blue');

// Step 3: Run the tests
colored_message("\nStep 3: Running tests...", 'green');

/**
 * Build a PHPUnit command with the appropriate options
 *
 * @param string $config_file The PHPUnit configuration file to use
 * @param string $test_type   The type of test (unit, wp-mock, integration)
 * @param array  $options     Command line options
 * @return string The complete PHPUnit command
 */
function build_phpunit_command($config_file, $test_type, $options) {
	$cmd = "./vendor/bin/phpunit -c config/{$config_file}";
	
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
	
	return $cmd;
}

// Execute tests based on the selected type
if ($options['unit']) {
	// Run unit tests
	colored_message("Running unit tests...", 'blue');
	$phpunit_cmd = build_phpunit_command('phpunit-unit.xml.dist', 'unit', $options);
	colored_message("Executing: $phpunit_cmd", 'blue');
	passthru($phpunit_cmd, $phpunit_return);
} elseif ($options['wp-mock']) {
	// Run WP Mock tests
	colored_message("Running WP Mock tests...", 'blue');
	$phpunit_cmd = build_phpunit_command('phpunit-wp-mock.xml.dist', 'wp-mock', $options);
	colored_message("Executing: $phpunit_cmd", 'blue');
	passthru($phpunit_cmd, $phpunit_return);
} elseif ($options['integration']) {
	// Run integration tests
	colored_message("Running integration tests...", 'blue');
	$phpunit_cmd = build_phpunit_command('phpunit-integration.xml.dist', 'integration', $options);
	colored_message("Executing: $phpunit_cmd", 'blue');
	passthru($phpunit_cmd, $phpunit_return);
} elseif ($options['all']) {
	colored_message("Running all tests sequentially...", 'green');
	
	// Run unit tests
	colored_message("\nRunning unit tests...", 'blue');
	$unit_cmd = build_phpunit_command('phpunit-unit.xml.dist', 'unit', $options);
	colored_message("Executing: $unit_cmd", 'blue');
	passthru($unit_cmd, $unit_return);
	
	// Run WP Mock tests
	colored_message("\nRunning WP Mock tests...", 'blue');
	$wp_mock_cmd = build_phpunit_command('phpunit-wp-mock.xml.dist', 'wp-mock', $options);
	colored_message("Executing: $wp_mock_cmd", 'blue');
	passthru($wp_mock_cmd, $wp_mock_return);
	
	// Run integration tests
	colored_message("\nRunning integration tests...", 'blue');
	$integration_cmd = build_phpunit_command('phpunit-integration.xml.dist', 'integration', $options);
	colored_message("Executing: $integration_cmd", 'blue');
	passthru($integration_cmd, $integration_return);
	
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
		$coverage_path = $framework_dest . '/build/coverage/index.html';
		colored_message("Code coverage report is available at:", 'blue');
		colored_message($coverage_path, 'yellow');
		colored_message("You can view this report by opening it in a web browser.", 'blue');
	}
} else {
	colored_message("\nTests failed with exit code $phpunit_return", 'red');
}

exit($phpunit_return);
