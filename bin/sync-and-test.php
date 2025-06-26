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

namespace WP_PHPUnit_Framework;

// Define base paths
define('PROJECT_DIR', dirname(__DIR__));
define('SCRIPT_DIR', PROJECT_DIR . DIRECTORY_SEPARATOR . 'bin');
define('TESTS_DIR', PROJECT_DIR . DIRECTORY_SEPARATOR . 'tests');

// First, detect the framework location without using get_setting()
$test_framework_dir = 'gl-phpunit-test-framework'; // Default value
$phpunit_framework_dir = TESTS_DIR . DIRECTORY_SEPARATOR . $test_framework_dir;

// Check if we're in the framework itself (development mode)
if (file_exists(PROJECT_DIR . '/bin/framework-functions.php')) {
    $phpunit_framework_dir = PROJECT_DIR;
}
// Check if installed via Composer
elseif (is_dir(PROJECT_DIR . '/vendor/glerner/phpunit-testing')) {
    $phpunit_framework_dir = PROJECT_DIR . '/vendor/glerner/phpunit-testing';
}

// Define the framework directory constant
define('PHPUNIT_FRAMEWORK_DIR', $phpunit_framework_dir);

// Verify test framework directory exists
if (!is_dir(PHPUNIT_FRAMEWORK_DIR)) {
    echo "Error: Test framework directory not found: " . PHPUNIT_FRAMEWORK_DIR . "\n";
    echo "Searched in:\n";
    echo "- " . PROJECT_DIR . " (self)\n";
    echo "- " . PROJECT_DIR . "/vendor/glerner/phpunit-testing (Composer)\n";
    echo "- " . $phpunit_framework_dir . " (submodule)\n";
    exit(1);
}

// Now we can safely include framework functions
$framework_functions = PROJECT_DIR . '/bin/framework-functions.php';
if (!file_exists($framework_functions)) {
    echo "Error: Framework functions not found at: " . $framework_functions . "\n";
    exit(1);
}

echo "Framework functions: $framework_functions\n";

require_once $framework_functions;

use function WP_PHPUnit_Framework\load_settings_file;
# use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\colored_message;
use function WP_PHPUnit_Framework\display_composer_test_instructions;
use function WP_PHPUnit_Framework\esc_cli;
use function WP_PHPUnit_Framework\get_cli_value;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\has_cli_flag;
use function WP_PHPUnit_Framework\is_lando_environment;
use function WP_PHPUnit_Framework\log;
// Set default timezone to avoid warnings
date_default_timezone_set('UTC');

// Now we can use get_setting() to override the framework dir if needed
if (function_exists('get_setting')) {
    $custom_framework_dir = get_setting('TEST_FRAMEWORK_DIR');
    if ($custom_framework_dir && is_dir(TESTS_DIR . DIRECTORY_SEPARATOR . $custom_framework_dir)) {
        define('PHPUNIT_FRAMEWORK_DIR', TESTS_DIR . DIRECTORY_SEPARATOR . $custom_framework_dir);
    }
}

// Check if Composer autoloader exists
if (!file_exists(PROJECT_DIR . '/vendor/autoload.php')) {
    colored_message("\n⚠️  Composer dependencies not found. Please run 'composer install' or 'composer update' in the project root.\n", 'yellow');
    exit(1);
}

/**
 * Display usage information
 *
 * @return void
 */
function display_help(): void {
    $script = basename(__FILE__);

    colored_message("Usage:", 'blue');
    colored_message("  php $script [options] [--file=<file>]\n\n");

    colored_message("Test Types:", 'blue');
    colored_message(sprintf("  %-12s %s\n", "--unit", "Run fast unit tests (no WordPress)"));
    colored_message(sprintf("  %-12s %s\n", "--wp-mock", "Run WP Mock tests (mocked WordPress)"));
    colored_message(sprintf("  %-12s %s\n", "--integration", "Run integration tests (full WordPress)"));
    colored_message(sprintf("  %-12s %s\n\n", "--all", "Run all test types"));

    colored_message("Options:", 'blue');
    echo "  --file=<file>          Run a specific test file\n";
    echo "  --coverage             Generate HTML coverage report\n";
    echo "  --verbose              Use TestDox for PHPUnit output and show script debug messages.\n";
    echo "  --help                 Show this help\n\n";

    colored_message("Environment Variables:", 'blue');
    echo esc_cli(sprintf("  %-25s %s\n", "WP_TESTS_DIR", "Path to WordPress test library"));
    echo esc_cli(sprintf("  %-25s %s\n", "WP_ROOT", "Path to WordPress root (with wp-content/)"));
    echo esc_cli(sprintf("  %-25s %s\n", "TEST_FRAMEWORK_DIR", "Test framework directory"));
    echo esc_cli(sprintf("  %-25s %s\n\n", "  (default: gl-phpunit-test-framework)", ""));

    colored_message("Examples:", 'blue');
    echo esc_cli("  # Run all tests with coverage\n");
    echo esc_cli("  php $script --all --coverage\n\n");

    echo esc_cli("  # Run a specific test file\n");
    echo esc_cli("  php $script --file=tests/Unit/ExampleTest.php\n\n");

    echo esc_cli("  # Run with verbose script output and TestDox test format\n");
    echo esc_cli("  php $script --unit --verbose\n");
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

$options['file']        = get_cli_value(['--file']);
$options['unit']        = has_cli_flag('--unit');
$options['wp-mock']     = has_cli_flag('--wp-mock');
$options['integration'] = has_cli_flag('--integration');
$options['all']         = has_cli_flag('--all');
$options['coverage']    = has_cli_flag('--coverage');
$options['verbose']     = has_cli_flag('--verbose');
$options['help']        = has_cli_flag(['--help', '-h']);

// Show help if requested or if no test type is specified
if ($options['help'] || (!$options['unit'] && !$options['wp-mock'] && !$options['integration'] && !$options['all'])) {
	display_help();
	exit(0);
}

// Load settings from .env.testing
$env_file = PROJECT_DIR . '/tests/.env.testing';
if ($options['verbose']) {
    colored_message("Loading settings from $env_file", 'blue');
}
global $loaded_settings;
$loaded_settings = load_settings_file($env_file);

// Define paths from settings

// Get and validate required settings
$filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT');
if (empty($filesystem_wp_root)) {
    colored_message("Error: FILESYSTEM_WP_ROOT setting is not set.", 'red');
    colored_message("This should point to your local WordPress installation directory (the one with wp-content/)", 'yellow');
    colored_message("Please set this in your .env.testing file or environment.", 'red');
    exit(1);
}

// WP_ROOT is required - must be set in .env.testing
$wp_root = get_setting('WP_ROOT');
if (empty($wp_root)) {
    colored_message("Error: WP_ROOT setting is not set.", 'red');
    colored_message("This should be the path WordPress uses to access itself (often same as FILESYSTEM_WP_ROOT)", 'yellow');
    colored_message("Please set this in your .env.testing file or environment.", 'red');
    exit(1);
}

// Get optional settings with defaults
$your_plugin_slug = \WP_PHPUnit_Framework\get_setting('YOUR_PLUGIN_SLUG', 'gl-phpunit-testing-framework');
$folder_in_wordpress = \WP_PHPUnit_Framework\get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');

if ($options['verbose']) {
    colored_message("\nEnvironment Settings:", 'blue');
    echo esc_cli(sprintf("  %-25s %s\n", "FILESYSTEM_WP_ROOT:", $filesystem_wp_root));
    echo esc_cli(sprintf("  %-25s %s\n", "WP_ROOT:", $wp_root));
    echo esc_cli(sprintf("  %-25s %s\n", "YOUR_PLUGIN_SLUG:", $your_plugin_slug));
    echo esc_cli(sprintf("  %-25s %s\n", "FOLDER_IN_WORDPRESS:", $folder_in_wordpress));
    echo esc_cli(sprintf("  %-25s %s\n", "TEST_FRAMEWORK_DIR:", $test_framework_dir));
    echo "\n";
}

// Set up paths with proper path concatenation
$your_plugin_dest = rtrim($filesystem_wp_root, '/') . '/' . ltrim($folder_in_wordpress, '/') . '/' . $your_plugin_slug;
$test_run_path = rtrim($wp_root, '/') . '/' . ltrim($folder_in_wordpress, '/') . '/' . $your_plugin_slug . '/tests';

colored_message("Using paths:", 'blue');
echo esc_cli("  Project source: " . PROJECT_DIR . "\n");
echo esc_cli("  Filesystem WordPress root: " . $filesystem_wp_root . "\n");
echo esc_cli("  WordPress container root: " . $wp_root . "\n");
echo esc_cli("  Plugin destination: " . $your_plugin_dest . "\n");
echo esc_cli("  Test run path: " . $test_run_path . "\n");

echo "sync-and-test folder: " . __DIR__ . "\n";

// After tests run, show instructions.
if ( ! has_cli_flag( '--no-instructions' ) ) {
	$is_lando   = is_lando_environment();
	echo "\n--- Test Instructions ---\n";
	display_composer_test_instructions( $is_lando, $your_plugin_dest );
	echo "-------------------------\n";
}

// Check if we're targeting Lando via SSH_COMMAND
$ssh_command = get_setting('SSH_COMMAND', '');
$targeting_lando = strpos($ssh_command, 'lando ssh') === 0;
$is_lando = false;

if ($targeting_lando) {
    if (!is_lando_environment()) {
        colored_message("Error: Lando is not running. Please start Lando and try again.", 'red');
        exit(1);
    }
    $is_lando = true;
    colored_message("Running with Lando (detected from SSH_COMMAND)", 'yellow');
}

// Step 0: Update framework files if needed
colored_message("Updating WP PHPUnit Test Framework files...", 'blue');
require_once __DIR__ . '/copy-sync-and-bootstrap-files.php';

// Step 1: Sync plugin to WordPress
colored_message("\nStep 1: Syncing project to WordPress...", 'green');

// All dependencies are now managed by the main project's composer.json
colored_message("Using main project's Composer dependencies...", 'blue');

// Create destination directory if it doesn't exist
if (!is_dir($your_plugin_dest)) {
    if ($options['verbose']) {
        colored_message("Creating destination directory: $your_plugin_dest", 'blue');
    }

    @mkdir($your_plugin_dest, 0755, true);
    if (!is_dir($your_plugin_dest)) {
        $error = error_get_last();
        $error_msg = $error ? $error['message'] : 'Unknown error';

        colored_message("Error: Could not create destination directory: $your_plugin_dest", 'red');
        colored_message("Error details: $error_msg", 'yellow');
        colored_message("This might be a permissions issue. Try running:", 'yellow');
        colored_message("  mkdir -p " . escapeshellarg(dirname($your_plugin_dest)), 'cyan');
        colored_message("  chmod 755 " . escapeshellarg(dirname($your_plugin_dest)), 'cyan');
        exit(1);
    } elseif ($options['verbose']) {
        colored_message("Created directory: $your_plugin_dest", 'green');
    }
} elseif ($options['verbose']) {
    colored_message("Destination directory exists: $your_plugin_dest", 'blue');
}

// Call the existing sync-to-wp.php script
$sync_script = SCRIPT_DIR . '/sync-to-wp.php';
if (!file_exists($sync_script)) {
	colored_message("Error: Could not find sync-to-wp.php script at $sync_script", 'red');
        exit(1);
    }

// Execute the sync script - always use filesystem PHP
colored_message("Executing $sync_script", 'blue');
$sync_cmd = "php " . escapeshellarg($sync_script);
if ($options['verbose']) {
    echo esc_cli("Command: $sync_cmd\n");
}

$output = [];
$sync_return = 0;
exec($sync_cmd . ' 2>&1', $output, $sync_return);

// Output the command output
if (!empty($output)) {
    echo esc_cli(implode("\n", $output)) . "\n";
}

if ($sync_return !== 0) {
    $error_msg = "Error: sync-to-wp.php failed with exit code $sync_return";
    if (!empty($output)) {
        $error_msg .= ":\n" . implode("\n", array_slice($output, -5)); // Show last 5 lines of output
    }
    colored_message($error_msg, 'red');
    exit($sync_return);
}

// Step 2: Change to the WordPress plugin, tests directory
$tests_dir = $your_plugin_dest . DIRECTORY_SEPARATOR . 'tests';
colored_message("\nStep 2: Changing to WordPress plugin Tests directory...", 'green');

if (!chdir($tests_dir)) {
    $error = error_get_last();
    $error_msg = $error ? $error['message'] : 'Unknown error';

    colored_message("Error: Could not change to WordPress plugin Tests directory: $tests_dir", 'red');
    colored_message("Error details: $error_msg", 'yellow');

    if (!is_dir($tests_dir)) {
        colored_message("The tests directory does not exist. Make sure sync-to-wp.php ran successfully.", 'yellow');
    } else {
        colored_message("Check directory permissions and try again.", 'yellow');
    }

    exit(1);
}

if ($options['verbose']) {
    colored_message("Successfully changed to directory: " . getcwd(), 'green');
}

// Step 3: Run the tests
colored_message("\nStep 3: Running tests...", 'green');
check_phpunit_exists($test_run_path, $your_plugin_dest, $targeting_lando);

/**
 * Build a PHPUnit command with the appropriate options
 *
 * @param string $test_type     The type of test (unit, wp-mock, integration)
 * @param array  $options       Command line options
 * @param string $test_run_path The path where tests will be run from
 * @return string The complete PHPUnit command
 */
function build_phpunit_command($test_type, $options, $test_run_path) {
    global $is_lando, $your_plugin_dest, $wp_root, $folder_in_wordpress, $your_plugin_slug;

    // Determine the PHP command
    $php_command = $is_lando ? 'lando php' : 'php';
    $base_name = 'phpunit-' . $test_type;
    $config_dir = $your_plugin_dest . '/tests/bootstrap/';

    // Look for config file in the filesystem
    $config_file = '';
    $possible_files = [
        $config_dir . $base_name . '.xml',
        $config_dir . $base_name . '.xml.dist'
    ];

    // Find the first existing config file
    foreach ($possible_files as $file) {
        if (file_exists($file)) {
            $config_file = $file;
            break;
        }
    }

    if (empty($config_file)) {
        throw new \RuntimeException("Could not find PHPUnit config file. Tried:\n" .
            "- " . implode("\n- ", $possible_files));
    }

    // Get just the filename for the config file
    $config_filename = basename($config_file);

    // Build the path that PHPUnit will use
    $config_path = $is_lando
        ? rtrim($wp_root, '/') . '/' . ltrim($folder_in_wordpress, '/') . '/' . $your_plugin_slug . '/tests/bootstrap/' . $config_filename
        : $config_file; // Use full path for local


    // Use the PHPUnit from the test framework's vendor directory
    $test_framework_dir = get_setting('TEST_FRAMEWORK_DIR', 'gl-phpunit-test-framework');
    $phpunit_path = $is_lando
        ? $wp_root . DIRECTORY_SEPARATOR . $folder_in_wordpress . DIRECTORY_SEPARATOR . $your_plugin_slug .
          DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $test_framework_dir .
          DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit'
        : __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR .
          $test_framework_dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';


    $cmd = $php_command . ' ' . escapeshellarg($phpunit_path) . ' -c ' . escapeshellarg($config_path);

    /* unknown option $cmd .= ' --display-deprecations';
    $cmd .= ' --display-notices';
    $cmd .= ' --display-warnings';
    */

    // Add verbose option if requested
    if ($options['verbose']) {
        $cmd .= ' --testdox';
    }

    // Add test filter if provided
    if (!empty($options['filter'])) {
        $cmd .= ' --filter ' . escapeshellarg($options['filter']);
    }

    return $cmd;
}

/**
 * Check if the PHPUnit executable exists and exit with an error if it doesn't.
 *
 * @param string $test_run_path The base path for the test execution.
 * @param string $your_plugin_dest The destination directory of the plugin.
 * @param bool $targeting_lando Whether the test is targeting a Lando environment.
 * @return void
 */
function check_phpunit_exists($test_run_path, $your_plugin_dest, $targeting_lando)
{
    $framework_dir_setting = get_setting('TEST_FRAMEWORK_DIR');

    // Use the HOST path for file_exists()
    $host_composer_dir = $your_plugin_dest . '/tests/' . $framework_dir_setting;
    $host_phpunit_path = $host_composer_dir . '/vendor/bin/phpunit';

    if (file_exists($host_phpunit_path)) {
        return;
    }

    // Use the CONTAINER path for the error message
    $container_composer_dir = $test_run_path . '/' . $framework_dir_setting;

    colored_message("PHPUnit executable not found on the host at: $host_phpunit_path with Host Composer Dir $host_composer_dir", 'red');
    colored_message('Your Composer dependencies may be missing or out of date in the testing environment.', 'yellow');

    if ($targeting_lando) {
        colored_message('To fix this, run the following command inside your Lando container:', 'yellow');
        colored_message("cd $container_composer_dir && composer install", 'cyan');
    } else {
        colored_message("Please run 'composer install' or 'composer update' in the following directory inside your testing environment:", 'yellow');
        colored_message($host_composer_dir, 'cyan');
    }

    $docs_path = 'tests/' . $framework_dir_setting . '/docs/guides/';
    colored_message('For more detailed instructions, see the documentation in your project at:', 'yellow');
    colored_message('- ' . $docs_path . 'composer-cleanup.md', 'cyan');
    colored_message('- ' . $docs_path . 'rebuilding-after-system-updates.md', 'cyan');
    exit(1);
}
// Execute tests based on the selected type
if ($options['unit']) {
    // Run unit tests
    colored_message("Running unit tests...", 'blue');

    $phpunit_cmd = build_phpunit_command('unit', $options, $test_run_path);
    colored_message("Executing: $phpunit_cmd", 'blue');
    passthru($phpunit_cmd, $phpunit_return);
} elseif ($options['wp-mock']) {
	// Run WP_Mock tests
	colored_message("Running WP_Mock tests...", 'blue');
	$phpunit_cmd = build_phpunit_command('wp-mock', $options, $test_run_path);
	colored_message("Executing: $phpunit_cmd", 'blue');
	passthru($phpunit_cmd, $phpunit_return);
} elseif ($options['integration']) {
	// Run integration tests
	colored_message("Running integration tests...", 'blue');
	$phpunit_cmd = build_phpunit_command('integration', $options, $test_run_path);
	colored_message("Executing: $phpunit_cmd", 'blue');
	passthru($phpunit_cmd, $phpunit_return);
} elseif ($options['all']) {
	colored_message("Running all tests sequentially...", 'green');

	// Run unit tests
	colored_message("\nRunning unit tests...", 'blue');
	$unit_cmd = build_phpunit_command('unit', $options, $test_run_path);
	colored_message("Executing: $unit_cmd", 'blue');
	passthru($unit_cmd, $unit_return);

	// Run WP Mock tests
	colored_message("\nRunning WP Mock tests...", 'blue');
	$wp_mock_cmd = build_phpunit_command('wp-mock', $options, $test_run_path);
	colored_message("Executing: $wp_mock_cmd", 'blue');
	passthru($wp_mock_cmd, $wp_mock_return);

	// Run integration tests
	colored_message("\nRunning integration tests...", 'blue');
	$integration_cmd = build_phpunit_command('integration', $options, $test_run_path);
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
		$coverage_path = $plugin_dest . '/build/coverage/index.html';
		colored_message("Code coverage report is available at:", 'blue');
		colored_message($coverage_path, 'yellow');
		colored_message("You can view this report by opening it in a web browser.", 'blue');
	}
} else {
	colored_message("\nTests failed with exit code $phpunit_return", 'red');
}

exit($phpunit_return);
