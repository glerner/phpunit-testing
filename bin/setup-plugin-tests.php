<?php
/**
 * Setup Plugin Tests - Updated with centralized settings
 *
 * Set up the WordPress test environment for PHPUnit testing.
 *
 * @package WP_PHPUnit_Framework
 */

// phpcs:set WordPress.Security.EscapeOutput customEscapingFunctions[] esc_cli
// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.DB.RestrictedFunctions
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// phpcs:disable WordPress.PHP.IniSet.display_errors_Disallowed
// phpcs:disable Squiz.ControlStructures.ControlSignature.SpaceAfterCloseBrace
// phpcs:disable Universal.Operators.DisallowShortTernary.Found



declare(strict_types=1);

namespace WP_PHPUnit_Framework;

use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\display_composer_test_instructions;
use function WP_PHPUnit_Framework\esc_cli;
use function WP_PHPUnit_Framework\find_project_root;
use function WP_PHPUnit_Framework\format_mysql_execution;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\is_lando_environment;

// Include the framework utility functions early so we can use its functions
require_once __DIR__ . '/framework-functions.php';

// Define SCRIPT_DIR. We use this to find the project root.
define('SCRIPT_DIR', __DIR__);

// Find the project root dynamically by looking for README.md, both the project containing WP_PHPUnit_Framework, and WP_PHPUnit_Framework itself, have one.
$default_framework_path = find_project_root(SCRIPT_DIR, 'README.md');
$project_dir = find_project_root(dirname($default_framework_path), 'README.md');
if (null === $project_dir) {
    echo esc_cli(COLOR_RED . 'Error: Could not find project root. Make sure a composer.json file exists in your plugin root.' . COLOR_RESET . "\n");
    exit(1);
}
define('PROJECT_DIR', $project_dir);

// Validate that the script is in the correct directory structure
if (basename(SCRIPT_DIR) !== 'bin') {
    echo esc_cli(COLOR_RED . 'Error: This script must be run from the ' . PROJECT_DIR . '/bin` directory of your plugin.' . COLOR_RESET . "\n");
    echo esc_cli('Expected path: ' . PROJECT_DIR . '/bin' . "\n");
    exit(1);
}

// Validate that the environment file exists
$env_file_path = PROJECT_DIR . '/tests/.env.testing';
echo "Project Dir: ". PROJECT_DIR . " \nScript Dir: " . SCRIPT_DIR . "\n";

if (!file_exists($env_file_path)) {
    echo esc_cli(COLOR_RED . 'Error: Environment file not found at: ' . $env_file_path . COLOR_RESET . "\n");
    echo esc_cli('Please copy or rename .env.sample.testing to .env.testing and configure it.' . "\n");
    exit(1);
}

// Exit if accessed directly, should be run command line
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit;
}

// Set error reporting for CLI
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load settings from .env.testing
$env_file_path = PROJECT_DIR . '/tests/.env.testing';
$GLOBALS['loaded_settings'] = load_settings_file($env_file_path); // Ensure settings are globally available for get_setting()

// Validate that PLUGIN_FOLDER is present.
$required_settings = ['PLUGIN_FOLDER', 'TEST_FRAMEWORK_DIR'];
$missing_settings = [];
foreach ($required_settings as $setting) {
    if (empty(get_setting($setting))) {
        $missing_settings[] = $setting;
    }
}

if (!empty($missing_settings)) {
    echo esc_cli(COLOR_RED . 'Error: The following required settings are missing or empty in ' . $env_file_path . ':' . COLOR_RESET . "\n");
    foreach ($missing_settings as $setting) {
        echo esc_cli(COLOR_RED . '- ' . $setting . COLOR_RESET . "\n");
    }
    echo esc_cli('Please define them and try again.' . "\n");
    exit(1);
}

// Handle TEST_FRAMEWORK_DIR with a helpful error message if not set.
$test_framework_subdir = get_setting('TEST_FRAMEWORK_DIR');
if (empty($test_framework_subdir)) {
    $default_framework_dir = 'gl-phpunit-test-framework';
    $default_framework_path = make_path(PROJECT_DIR, 'tests', $default_framework_dir);

    if (is_dir($default_framework_path)) {
        // The default directory exists, so we can give a very helpful error.
        echo esc_cli(COLOR_RED . "Error: The required setting TEST_FRAMEWORK_DIR is not defined in $env_file_path ." . COLOR_RESET . "\n");
        echo esc_cli(COLOR_YELLOW . "A default directory was found at '{$default_framework_path}'." . COLOR_RESET . "\n");
        echo esc_cli(COLOR_YELLOW . "Please add the following line to your tests/.env.testing file:" . COLOR_RESET . "\n");
        echo esc_cli("TEST_FRAMEWORK_DIR={$default_framework_dir}" . "\n");
    } else {
        // It's not set and the default doesn't exist. This is a fatal error.
        echo esc_cli(COLOR_RED . "Error: The required setting TEST_FRAMEWORK_DIR is not defined in tests/.env.testing." . COLOR_RESET . "\n");
        echo esc_cli(COLOR_RED . "Please set it to the correct path for your test framework source (e.g., gl-phpunit-test-framework)." . COLOR_RESET . "\n");
    }
    exit(1);
}

// Get the plugin slug from settings, with a fallback to the project directory name.
$plugin_slug = get_setting('YOUR_PLUGIN_SLUG');
if (empty($plugin_slug)) {
    $plugin_slug = basename(PROJECT_DIR);
    echo esc_cli(COLOR_YELLOW . "Warning: YOUR_PLUGIN_SLUG is not set in tests/.env.testing. Falling back to the project directory name: '{$plugin_slug}'." . COLOR_RESET . "\n");
}

// Define source and destination paths for framework files
$framework_source_base = make_path(PROJECT_DIR, 'tests', $test_framework_subdir);
$framework_bin_source    = make_path($framework_source_base, 'bin');
$framework_bootstrap_source = make_path($framework_source_base, 'tests', 'bootstrap');

echo "DEBUG: PROJECT_DIR: " . PROJECT_DIR . " \nframework_source_base: $framework_source_base \nDefault_framework_path: $default_framework_path\n";

$dest_tests_dir  = make_path(PROJECT_DIR, 'tests');
$dest_bin_dir    = make_path($dest_tests_dir, 'bin');
$dest_bootstrap_dir = make_path($dest_tests_dir, 'bootstrap');

// Verify that the determined framework source directory exists
if (!is_dir($default_framework_path)) {
    echo esc_cli(COLOR_RED . "Error: Test framework source directory not found at: {$default_framework_path}" . COLOR_RESET . "\n");
    echo esc_cli(COLOR_RED . "Please ensure TEST_FRAMEWORK_DIR in {$env_file_path} is set correctly and the directory exists." . COLOR_RESET . "\n");
    exit(1);
}

// Ensure destination directories exist
if (!is_dir($dest_bin_dir)) {
    if (!mkdir($dest_bin_dir, 0755, true)) {
        echo esc_cli(COLOR_RED . "Error: Failed to create destination directory: {$dest_bin_dir}" . COLOR_RESET . "\n");
        exit(1);
    }
}
if (!is_dir($dest_bootstrap_dir)) {
    if (!mkdir($dest_bootstrap_dir, 0755, true)) {
        echo esc_cli(COLOR_RED . "Error: Failed to create destination directory: {$dest_bootstrap_dir}" . COLOR_RESET . "\n");
        exit(1);
    }
}

// Files to copy from framework bin to project's tests/bin
// Excludes setup-plugin-tests.php itself to prevent overwriting the running script if it's already in the destination.
$bin_files_to_copy = ['framework-functions.php', 'phpcbf.sh', 'sync-and-test.php', 'sync-to-wp.php', 'test-env-requirements.php'];

echo esc_cli("Copying framework bin files to: {$dest_bin_dir}\n");
foreach ($bin_files_to_copy as $file) {
    $source_file = make_path($framework_bin_source, $file);
    $dest_file   = make_path($dest_bin_dir, $file);
    if (file_exists($source_file)) {
        if (copy($source_file, $dest_file)) {
            echo esc_cli(COLOR_GREEN . "  Copied: {$file}" . COLOR_RESET . "\n");
        } else {
            echo esc_cli(COLOR_RED . "  Error copying: {$file} to {$dest_file}" . COLOR_RESET . "\n");
        }
    } else {
        echo esc_cli(COLOR_YELLOW . "  Warning: Source file not found, skipped: {$source_file}" . COLOR_RESET . "\n");
    }
}

// Copy bootstrap config files (e.g., phpunit.xml)
if (is_dir($framework_bootstrap_source)) {
    echo esc_cli("Copying framework bootstrap files to: {$dest_bootstrap_dir}\n");
    foreach (glob(make_path($framework_bootstrap_source, '*.xml')) as $config_file_source) {
        $dest_file = make_path($dest_bootstrap_dir, basename($config_file_source));
        if (copy($config_file_source, $dest_file)) {
            echo esc_cli(COLOR_GREEN . "  Copied: " . basename($config_file_source) . COLOR_RESET . "\n");
        } else {
            echo esc_cli(COLOR_RED . "  Error copying: " . basename($config_file_source) . " to {$dest_file}" . COLOR_RESET . "\n");
        }
    }
} else {
    echo esc_cli(COLOR_YELLOW . "Warning: Framework bootstrap source directory not found, skipped: {$framework_bootstrap_source}" . COLOR_RESET . "\n");
}

$test_error_log = get_setting('TEST_ERROR_LOG', '/tmp/phpunit-testing.log');


/**
 * Check system requirements.
 *
 * This function checks for the availability of Git, verifies that Lando is running (if applicable),
 * and tests the MySQL database connection.
 *
 * @param string $ssh_command The command used for SSH connections (e.g., 'lando ssh', 'none').
 * @param array  $db_settings An array of database connection settings.
 * @return bool True if all requirements are met, false otherwise.
 */
function check_system_requirements(string $ssh_command, array $db_settings): bool {
    echo esc_cli("Checking system requirements...\n");
    $all_ok = true;

    // 1. Check for Git
    exec('git --version', $git_output, $git_return_code);
    if ($git_return_code !== 0) {
        echo esc_cli(COLOR_RED . '❌ Git is not installed or not in PATH.' . COLOR_RESET . "\n");
        $all_ok = false;
    } else {
        echo esc_cli(COLOR_GREEN . '✅ Git is available.' . COLOR_RESET . "\n");
    }

    // 2. If using Lando, check if it's running
    if (strpos($ssh_command, 'lando') !== false) {
        if (!is_lando_environment()) {
            echo esc_cli(COLOR_RED . '❌ Lando environment is not running. Please start it with `lando start`.' . COLOR_RESET . "\n");
            $all_ok = false;
        } else {
            echo esc_cli(COLOR_GREEN . '✅ Lando is running.' . COLOR_RESET . "\n");
        }
    }

    // 3. Check MySQL connection
    $mysql_check_cmd = format_mysql_execution($ssh_command, $db_settings['db_host'], $db_settings['db_user'], $db_settings['db_pass'], 'SELECT 1');
    exec($mysql_check_cmd, $mysql_output, $mysql_return_code);

    if ($mysql_return_code !== 0) {
        echo esc_cli(COLOR_RED . '❌ MySQL connection failed. Please check your database credentials and that the server is running.' . COLOR_RESET . "\n");
        echo esc_cli('Error details: ' . implode("\n", $mysql_output) . "\n");
        $all_ok = false;
    } else {
        echo esc_cli(COLOR_GREEN . '✅ MySQL connection successful.' . COLOR_RESET . "\n");
    }

    if (!$all_ok) {
        echo esc_cli(COLOR_YELLOW . "\nPlease resolve the issues above before proceeding.\n" . COLOR_RESET);
    }

    return $all_ok;
}


/**
 * Download WordPress test suite
 *
 * @param string $wp_tests_dir Directory to install tests
 * @return bool True if successful, false otherwise
 */
function download_wp_tests( string $wp_tests_dir ): bool {
    echo esc_cli("Setting up WordPress test suite in: $wp_tests_dir\n");

    // Create tests directory if it doesn't exist
    if (!is_dir($wp_tests_dir)) {
        if (!mkdir($wp_tests_dir, 0755, true)) {
            echo esc_cli("Error: Failed to create tests directory: $wp_tests_dir\n");
            return false;
        }
    }

    // Check if test suite is already installed
    if (is_dir("$wp_tests_dir/includes") && file_exists("$wp_tests_dir/includes/functions.php")) {
        echo esc_cli("WordPress test suite already installed.\n");
        return true;
    }

    echo esc_cli("Downloading WordPress test suite...\n");

    // Create temporary directory
    $tmp_dir = "$wp_tests_dir/tmp";
    if (is_dir($tmp_dir)) {
        system("rm -rf $tmp_dir");
    }

    // Clone WordPress develop repository
    $cmd = "git clone --depth=1 https://github.com/WordPress/wordpress-develop.git $tmp_dir";
    echo esc_cli("Running: $cmd\n");
    system($cmd, $return_var);

    if ($return_var !== 0) {
        echo esc_cli("Error: Failed to `git clone` WordPress develop repository.\n");
        return false;
    }

    // Copy required directories
    if (!is_dir("$tmp_dir/tests/phpunit")) {
        echo esc_cli("Error: WordPress test suite not found in cloned repository.\n");
        system("rm -rf $tmp_dir");
        return false;
    }

    // Create required directories
    foreach (array( 'includes', 'data', 'tests' ) as $dir) {
        if (!is_dir("$wp_tests_dir/$dir")) {
            mkdir("$wp_tests_dir/$dir", 0755, true);
        }
    }

    // Copy files preserving directory structure
    system("cp -r $tmp_dir/tests/phpunit/includes/* $wp_tests_dir/includes/");
    system("cp -r $tmp_dir/tests/phpunit/data/* $wp_tests_dir/data/");
    system("cp -r $tmp_dir/tests/phpunit/tests/* $wp_tests_dir/tests/");

    // Cleanup
    system("rm -rf $tmp_dir");

    // Verify files exist
    if (!file_exists("$wp_tests_dir/includes/functions.php") || !file_exists("$wp_tests_dir/includes/install.php")) {
        echo esc_cli("Error: Failed to download WordPress test suite files.\n");
        return false;
    }

    echo esc_cli(COLOR_GREEN . '✅ WordPress test suite downloaded successfully.' . COLOR_RESET . "\n");
    return true;
}

/**
 * Install PHPUnit Polyfills for WordPress Test Suite only
 *
 * This function installs the PHPUnit Polyfills library specifically for the WordPress Test Suite.
 * The polyfills are NOT used by WP_PHPUnit_Framework or user code, which use PHPUnit 11+.
 * They are only installed to satisfy WordPress Test Suite requirements for integration tests.
 *
 * @param string $wp_tests_dir Directory where tests are installed
 * @return bool True if successful, false otherwise
 */
function install_phpunit_polyfills_for_wp_tests( string $wp_tests_dir ): bool {
    echo esc_cli("Installing PHPUnit Polyfills for WordPress Test Suite...\n");
    
    // Create vendor directory if it doesn't exist
    $vendor_dir = "$wp_tests_dir/vendor";
    if (!is_dir($vendor_dir)) {
        if (!mkdir($vendor_dir, 0755, true)) {
            echo esc_cli(COLOR_RED . "Error: Failed to create vendor directory: $vendor_dir" . COLOR_RESET . "\n");
            return false;
        }
    }
    
    // Define the polyfills directory
    $polyfills_dir = "$vendor_dir/yoast/phpunit-polyfills";
    
    // Check if polyfills are already installed
    if (is_dir($polyfills_dir) && file_exists("$polyfills_dir/src/TestCases/TestCase.php")) {
        echo esc_cli("PHPUnit Polyfills already installed.\n");
        return true;
    }
    
    // Create temporary directory
    $tmp_dir = "$wp_tests_dir/tmp-polyfills";
    if (is_dir($tmp_dir)) {
        system("rm -rf $tmp_dir");
    }
    mkdir($tmp_dir, 0755, true);
    
    // Clone PHPUnit Polyfills repository
    $cmd = "git clone --depth=1 --branch=2.0.0 https://github.com/Yoast/PHPUnit-Polyfills.git $tmp_dir";
    echo esc_cli("Running: $cmd\n");
    system($cmd, $return_var);
    
    if ($return_var !== 0) {
        echo esc_cli(COLOR_RED . "Error: Failed to clone PHPUnit Polyfills repository." . COLOR_RESET . "\n");
        system("rm -rf $tmp_dir");
        return false;
    }
    
    // Create polyfills directory
    if (!is_dir($polyfills_dir)) {
        mkdir($polyfills_dir, 0755, true);
    }
    
    // Copy files
    system("cp -r $tmp_dir/* $polyfills_dir/");
    
    // Cleanup
    system("rm -rf $tmp_dir");
    
    // Verify files exist
    if (!file_exists("$polyfills_dir/src/TestCases/TestCase.php")) {
        echo esc_cli(COLOR_RED . "Error: Failed to install PHPUnit Polyfills." . COLOR_RESET . "\n");
        return false;
    }
    
    echo esc_cli(COLOR_GREEN . '✅ PHPUnit Polyfills installed successfully.' . COLOR_RESET . "\n");
    return true;
}

/**
 * Generate wp-tests-config.php
 *
 * @param string $wp_tests_dir Directory where tests are installed
 * @param string $wp_root WordPress root directory
 * @param string $db_name Database name
 * @param string $db_user Database user
 * @param string $db_pass Database password
 * @param string $db_host Database host
 * @param string $plugin_dir Plugin directory
 * @return bool True if successful, false otherwise
 */
function generate_wp_tests_config(
    string $wp_tests_dir,
    string $wp_root,
    string $db_name,
    string $db_user,
    string $db_pass,
    string $db_host,
    string $plugin_dir
): bool {
    echo "Generating wp-tests-config.php...\n";

    $config_content = <<<EOT
<?php
/**
 * WordPress Test Suite Configuration
 *
 * This file is automatically generated by setup-plugin-tests.php
 * Do not edit this file directly as changes will be overwritten.
 *
 * Note: Constants in this file are intentionally defined in the global namespace
 * without prefixes to match WordPress core testing requirements. PHPCS errors about
 * non-prefixed globals can be ignored, as this file is excluded from those rules
 * in the phpcs.xml.dist configuration.
 */

/* Path to the WordPress codebase you'd like to test. Add a forward slash in the end. */
if (!defined('ABSPATH')) {
    define('ABSPATH', '$wp_root/');
}

/* Test with WordPress debug mode on */
define('WP_DEBUG', true);

/* Database settings */
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASSWORD', '$db_pass');
define('DB_HOST', '$db_host');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');

define('WP_PHP_BINARY', 'php');

// Define the path to PHPUnit Polyfills for WordPress Test Suite
define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', '$wp_tests_dir/vendor/yoast/phpunit-polyfills');

\$table_prefix = 'wptests_';
EOT;

    // Write config file
    if (file_put_contents("$wp_tests_dir/wp-tests-config.php", $config_content) === false) {
        echo "Error: Failed to write wp-tests-config.php.\n";
        return false;
    }

    // Copy wp-tests-config.php to the tests directory
    $tests_dir = "$plugin_dir/tests";

    // Create tests directory if it doesn't exist
    if (!is_dir($tests_dir)) {
        mkdir($tests_dir, 0755, true);
    }

    // Remove existing symlink if it exists
    if (file_exists("$tests_dir/wp-tests-config.php")) {
        unlink("$tests_dir/wp-tests-config.php");
    }

    // Instead of a symlink, copy the file directly
    // This is more reliable, especially in containerized environments
    if (copy("$wp_tests_dir/wp-tests-config.php", "$tests_dir/wp-tests-config.php")) {
        echo COLOR_GREEN . '✅ Copied wp-tests-config.php to tests directory' . COLOR_RESET . "\n";
    } else {
        echo "Warning: Failed to copy wp-tests-config.php. You may need to copy the file manually.\n";
        // Continue anyway, this is not critical
    }

    echo COLOR_GREEN . '✅ wp-tests-config.php generated successfully.' . COLOR_RESET . "\n";
    return true;
}

/**
 * Install test database
 *
 * @param string $wp_tests_dir Directory where tests are installed
 * @param string $db_name Database name
 * @param string $db_user Database user
 * @param string $db_pass Database password
 * @param string $db_host Database host
 * @return bool True if successful, false otherwise
 */
function install_test_suite(
    string $wp_tests_dir,
    string $db_name,
    string $db_user,
    string $db_pass,
    string $db_host
): bool {
    // Check if mysql command is available
    exec('which mysql', $output, $return_var);
    if ($return_var !== 0) {
        echo "Error: The mysql command-line client is not installed or not in PATH.\n";
        echo "Please install it with: sudo apt-get install mysql-client\n";
        return false;
    }

    // Check MySQL connection
    echo "Attempting to connect to MySQL...\n";

    // Get SSH command from settings with priority order
    $ssh_command = get_setting('SSH_COMMAND', 'none');

    // Prepare the mysql command based on SSH_COMMAND setting
    $mysql_cmd = 'mysql';
    $use_ssh = false;


/** This whole "determine how to execute" section is probably messed up. What about ssh to a remote,  so SSH_COMMNAD !='ssh'?  */
    // Determine how to execute database commands
    echo 'Database access method from .env.testing: ' . COLOR_CYAN . 'SSH_COMMAND=' . ( $ssh_command ?: 'not set' ) . COLOR_RESET . "\n";
    if ($ssh_command === 'none') {
        echo "Using mysql directly (no SSH needed)\n";
    } elseif ($ssh_command === 'ssh') {
        // Already in an SSH session, use mysql directly
        echo "Already in SSH session, using mysql directly\n";
    } else {
        // Use the specified SSH command to access the database
        echo "Using SSH command: $ssh_command\n";
        $use_ssh = true;
    }

    // Determine if we're targeting a Lando environment based on SSH_COMMAND, which could have connection parameters
    $targeting_lando = strpos($ssh_command, 'lando ssh') === 0;

    if ($targeting_lando) {
        echo "Using standard Lando database configuration...\n";
        echo "Host: $db_host, User: $db_user, Password: $db_pass\n";
    }

    // Verify the connection using the parameters that will be in wp-tests-config.php
    echo "Verifying database connection to $db_host...\n";

    // format and execute the MySQL command
    $cmd = format_mysql_execution($ssh_command, $db_host, $db_user, $db_pass, 'SELECT 1;');

    echo "Debug: Executing command: $cmd\n";
    // Add shell redirection (2>&1) to capture both standard output and error streams
    exec("$cmd 2>&1", $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Cannot connect to MySQL server.\n";
        echo 'Output: ' . implode("\n", $output) . "\n";
        return false;
    }

    echo COLOR_GREEN . "✅ Connected to MySQL on host: $db_host" . COLOR_RESET . "\n";

    echo COLOR_GREEN . '✅ MySQL connection successful' . COLOR_RESET . "\n";

    /* for now, commenting out for testing multiple plugin capability
    // Try to drop database if exists
    echo "Attempting to drop existing database...\n";

    // format and execute the MySQL command
    if ($targeting_lando) {
        echo "Using root user to drop database in Lando environment...\n";
        $cmd = format_mysql_execution($ssh_command, $db_host, 'root', '', "DROP DATABASE IF EXISTS $db_name;");
    } else {
        // In local environment, use provided user
        $cmd = format_mysql_execution($ssh_command, $db_host, $db_user, $db_pass, "DROP DATABASE IF EXISTS $db_name;");
    }

    echo "Debug: Executing command: $cmd\n";
    // Add shell redirection (2>&1) to capture both standard output and error streams
    exec("$cmd 2>&1", $output, $return_var);

    if ($return_var !== 0) {
        echo "Warning: Failed to drop test database.\n";
        echo 'Output: ' . implode("\n", $output) . "\n";
        echo "Continuing anyway, as the database might not exist yet...\n";
    } else {
        echo COLOR_GREEN . '✅ Existing database dropped (if it existed)' . COLOR_RESET . "\n";
    }

    end testing
    */

    // Create database and grant permissions

    // Build the SQL command using heredoc for better readability
    // Write SQL exactly as you would type it directly into MySQL
    // The format_mysql_execution function will handle all necessary escaping
    $sql_command = <<<DB_SETUP
CREATE DATABASE IF NOT EXISTS $db_name;
CREATE USER IF NOT EXISTS "$db_user"@"%" IDENTIFIED BY "$db_pass";
GRANT ALL PRIVILEGES ON $db_name.* TO "$db_user"@"%";
FLUSH PRIVILEGES;
DB_SETUP;

    if ($targeting_lando) {
        echo "Creating database and granting permissions (Lando environment)...\n";
    } else {
        echo "Creating database and granting permissions (local environment)...\n";
    }

    // format and execute the MySQL command
    $cmd = format_mysql_execution($ssh_command, $db_host, 'root', '', $sql_command);

    echo "Debug: Executing command: $cmd\n";
    // Add shell redirection (2>&1) to capture both standard output and error streams
    exec("$cmd 2>&1", $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Failed to create test database.\n";
        echo 'Output: ' . implode("\n", $output) . "\n";
        echo "\nDebug: Full SQL command:\n$sql_command\n";
        echo "\nDebug: Try running this command manually to see the error:\n";
        echo "$cmd\n";
        return false;
    }

    echo COLOR_GREEN . '✅ Database created successfully' . COLOR_RESET . "\n";

    // Verify database exists and is accessible
    echo "Verifying database access...\n";
    $cmd = format_mysql_execution($ssh_command, $db_host, $db_user, $db_pass, "SHOW DATABASES LIKE \"$db_name\";");

    echo "Debug: Executing command: $cmd\n";
    // Add shell redirection (2>&1) to capture both standard output and error streams
    exec("$cmd 2>&1", $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Cannot access test database after creation.\n";
        echo 'Output: ' . implode("\n", $output) . "\n";
        return false;
    }

    echo COLOR_GREEN . '✅ Test database created and verified' . COLOR_RESET . "\n";

    // Install WordPress test framework
    echo "Installing WordPress test framework...\n";

    // Verify required files exist
    if (!file_exists("$wp_tests_dir/includes/functions.php") || !file_exists("$wp_tests_dir/includes/install.php")) {
        echo "Error: WordPress test framework files not found. Please check the installation.\n";
        return false;
    }

    // Create a temporary PHP script to run the installation
    $install_script = <<<EOT
<?php
\$_SERVER['argv'] = array(
    'install-wp-tests.php',
    '$wp_tests_dir/wp-tests-config.php'
);
require_once '$wp_tests_dir/includes/functions.php';
require_once '$wp_tests_dir/includes/install.php';

echo "Installing...\n";
tests_install('$wp_tests_dir/data');
EOT;

    file_put_contents("$wp_tests_dir/install-wp-tests.php", $install_script);

    // Execute the PHP script
    echo "Running WordPress test installation...\n";

    // Debug information
    echo "Debug: WordPress test directory: $wp_tests_dir\n";

    // Check if files exist
    echo "Debug: Checking if files exist:\n";
    echo '- install.php exists: ' . ( file_exists("$wp_tests_dir/includes/install.php") ? 'Yes' : 'No' ) . "\n";
    echo '- wp-tests-config.php exists: ' . ( file_exists("$wp_tests_dir/wp-tests-config.php") ? 'Yes' : 'No' ) . "\n";

    // Check database configuration in wp-tests-config.php
    if (file_exists("$wp_tests_dir/wp-tests-config.php")) {
        $config_content = file_get_contents("$wp_tests_dir/wp-tests-config.php");

        // Extract database settings
        preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config_content, $db_name_match);
        preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config_content, $db_user_match);
        preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config_content, $db_host_match);

        // Check if the database settings match what we expect
        $config_db_name = $db_name_match[1] ?? '';
        $config_db_user = $db_user_match[1] ?? '';
        $config_db_host = $db_host_match[1] ?? '';

        $matches = ($config_db_name === $db_name && $config_db_user === $db_user && $config_db_host === $db_host);
        echo "Checking $wp_tests_dir/wp-tests-config.php: " . ($matches ? "✅ Matches" . COLOR_RESET : "❌ Doesn't Match") . "\n";
    }

    // Determine which PHP to use based on environment
    $php_command = 'php';
    $install_path = "$wp_tests_dir/includes/install.php";
    $config_path = "$wp_tests_dir/wp-tests-config.php";

    if ($targeting_lando) {
        echo "Debug: Using Lando PHP for installation...\n";
        $php_command = 'lando php';
        // When using Lando for WordPress, we use the database in Lando, so we need to use "lando php" and container paths
        $wp_root = get_setting('WP_ROOT', '/app');
        $install_path = "$wp_root/wp-content/plugins/wordpress-develop/tests/phpunit/includes/install.php";
        $config_path = "$wp_root/wp-content/plugins/wordpress-develop/tests/phpunit/wp-tests-config.php";
    } else {
        echo "Debug: Using local PHP for installation...\n";
    }

    // Capture output for debugging
    $output = array();

    // For Lando PHP commands, we need to be careful with quotes and paths
    if ($targeting_lando) {
        // Use lando php directly, which *only* works from *outside* the container
        echo "Using lando php to execute the installation script...\n";
        $command = "lando php \"/app/wp-content/plugins/wordpress-develop/tests/phpunit/includes/install.php\" \"/app/wp-content/plugins/wordpress-develop/tests/phpunit/wp-tests-config.php\"";
    } else {
        $command = format_php_command($install_path, array( $config_path ), $php_command);
    }

    echo "Debug: PHP command to execute: $command\n";

    // Add shell redirection (2>&1) to capture both standard output and error streams
    // See docs/guides/lando-php-command-execution.md for details on proper command execution with redirection
    exec("$command 2>&1", $output, $return_var);

    // Check for common Lando errors
    if ($return_var !== 0) {
        $output_str = implode("\n", $output);
        echo COLOR_RED . "Command output:\n" . COLOR_RESET . $output_str . "\n";

        if ($targeting_lando && strpos($output_str, 'Usage:') !== false && strpos($output_str, 'lando <command>') !== false) {
            echo COLOR_RED . "Error: Lando command failed. Make sure Lando is running with 'lando start'" . COLOR_RESET . "\n";
        }
    }

    // Install compatibility files for modern WordPress
    echo "Installing compatibility files for modern WordPress...\n";

    // Get the path to the compatibility files
    $compat_dir = dirname(__DIR__) . '/compat';

    // Get the filesystem WordPress root path
    $filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT');

    // Check if WordPress version requires compatibility files
    // Modern WordPress (6.x+) uses namespaced PHPMailer but the test suite expects the old structure
    $wp_includes_dir = "$filesystem_wp_root/wp-includes";

    // Check if class-wp-phpmailer.php exists, if not, copy our compatibility version
    if (!file_exists("$wp_includes_dir/class-wp-phpmailer.php") && file_exists("$compat_dir/wp-includes/class-wp-phpmailer.php")) {
        echo "Installing PHPMailer compatibility shim...\n";
        copy("$compat_dir/wp-includes/class-wp-phpmailer.php", "$wp_includes_dir/class-wp-phpmailer.php");
        echo "✅ PHPMailer compatibility shim installed\n";
    } else {
        echo "PHPMailer compatibility shim not needed or already exists\n";
    }

    // Clean up
    unlink("$wp_tests_dir/install-wp-tests.php");

    if ($return_var !== 0) {
        echo "Error: Failed to install WordPress test framework.\n";
        return false;
    }

    echo COLOR_GREEN . '✅ WordPress test framework installed successfully.' . COLOR_RESET . "\n";
    return true;
}

/**
 * Remove test database and files
 *
 * @param string $wp_tests_dir Directory where tests are installed
 * @param string $db_name Database name
 * @param string $db_host Database host
 * @param string $ssh_command SSH command if using remote connection
 * @return bool True if successful, false otherwise
 */
function remove_test_suite(
    string $wp_tests_dir,
    string $db_name,
    string $db_host,
    string $ssh_command = ''
): bool {
    echo "Removing WordPress test suite...\n";

    // Check if we need to use SSH
    $use_ssh = !empty($ssh_command);
    $targeting_lando = strpos($ssh_command, 'lando ssh') === 0;

    // Get MySQL command
    $mysql_cmd = 'mysql';

    echo "Dropping test database...\n";

    // Build the MySQL command to drop the database using heredoc
    $sql_command = <<<DROP_DB
DROP DATABASE IF EXISTS $db_name;
DROP_DB;

    // Format and execute the MySQL command
    $cmd = format_mysql_execution($ssh_command, $db_host, 'root', '', $sql_command);

    echo "Debug: Executing command: $cmd\n";
    // Add shell redirection (2>&1) to capture both standard output and error streams
    exec("$cmd 2>&1", $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Failed to drop test database.\n";
        echo 'Output: ' . implode("\n", $output) . "\n";
        // Continue anyway to remove files
    } else {
        echo COLOR_GREEN . '✅ Database dropped successfully' . COLOR_RESET . "\n";
    }

    // Remove test files if they exist
    if (file_exists($wp_tests_dir)) {
        echo "Removing test files from $wp_tests_dir...\n";
        system("rm -rf $wp_tests_dir");
        echo COLOR_GREEN . '✅ Test files removed successfully' . COLOR_RESET . "\n";
    } else {
        echo "No test files found at $wp_tests_dir\n";
    }

    return true;
}

/**
 * Display help information
 */
function display_help(): void {
    echo "\nGL WordPress PHPUnit Testing Framework - Setup Script\n";
    echo "=================================================\n\n";
    echo "Usage: php setup-plugin-tests.php [options]\n\n";
    echo "Options:\n";
    echo "  --help, -h           Display this help message\n";
    echo "  --remove-all, --remove  Remove test database and files\n";
    echo "\n";
    echo "Description:\n";
    echo "  This script sets up the WordPress testing environment for PHPUnit tests.\n";
    echo "  It creates a test database, downloads the WordPress test suite, and\n";
    echo "  configures everything needed to run PHPUnit tests for your plugin.\n\n";
    echo "  The --remove-all option can be used to clean up the test environment\n";
    echo "  by dropping the test database and removing test files.\n\n";
    echo "Configuration:\n";
    echo "  The script uses settings from .env.testing in the project root.\n";
    echo "  See .env.sample.testing for available configuration options.\n\n";
}

/**
 * Main execution
 */

// Parse command line arguments using the new helper functions
$remove_all = has_cli_flag(['--remove-all', '--remove']);
$show_help = has_cli_flag(['--help', '-h']);

// Display help if requested
if ($show_help) {
    display_help();
    exit(0);
}

// Store original directory for later restoration
$original_dir = getcwd();

// Get WordPress root directory from settings
$wp_root = get_setting('FILESYSTEM_WP_ROOT', '');

// Change to WordPress root directory if it exists (for Lando commands)
if (!empty($wp_root) && is_dir($wp_root)) {
    echo "Changing to WordPress root directory: $wp_root\n";
    chdir($wp_root);
}

echo "Setting up WordPress plugin tests...\n";

// SSH command for database operations
$ssh_command = get_setting('SSH_COMMAND', 'none');

// Get WordPress configuration path
$wp_config_path = "$wp_root/wp-config.php";

// Get Lando info by executing 'lando info' command
$lando_info_array = get_lando_info();
// if (!empty($lando_info_array)) {
    // echo "Using Lando database configuration for WordPress testing.\n";
// }

// Get WordPress database settings
$wp_db_settings = get_database_settings($wp_config_path, $lando_info_array);

echo "Checking system requirements\n";

// Check system requirements
if (!check_system_requirements($ssh_command, $wp_db_settings)) {
    exit(1);
}

// Check PHPUnit version consistency
echo "Checking PHPUnit version...\n";
$composer_json_path = PROJECT_DIR . '/composer.json';
if (file_exists($composer_json_path)) {
    $composer_config = json_decode(file_get_contents($composer_json_path), true);
    $required_version_constraint = $composer_config['require-dev']['phpunit/phpunit'] ?? '';

    if (preg_match('/\\^(\\d+)/', $required_version_constraint, $matches)) {
        $required_major_version = $matches[1];

        // Use `composer show` as it's more reliable in a potentially broken environment, than `vendor/bin/phpunit --version`
        $composer_command = 'composer show phpunit/phpunit';
        if (is_lando_environment()) {
            $composer_command = "lando ssh -c '{$composer_command}' 2>/dev/null";
        }

        $installed_version_output = shell_exec($composer_command);

        // Parse 'versions' line from composer show output
        if ($installed_version_output && preg_match('/versions\\s*:\\s*\\*\\s*(\\d+)/', $installed_version_output, $matches)) {
            $installed_major_version = $matches[1];

            if ($required_major_version !== $installed_major_version) {
                echo esc_cli(COLOR_RED . 'ERROR: PHPUnit version mismatch.' . COLOR_RESET . "\n");
                echo "Your composer.json requires PHPUnit version ^" . $required_major_version . ", but version " . $installed_major_version . " is installed in the environment.\n";
                echo "This can happen after a 'lando rebuild'.\n\n";
                echo "To fix this, please run the following commands from your project root:\n";
                echo esc_cli(COLOR_YELLOW . '1. lando ssh -c "composer install"' . COLOR_RESET . "\n");
                echo esc_cli(COLOR_YELLOW . '2. php bin/setup-plugin-tests.php --remove-all' . COLOR_RESET . "\n");
                echo esc_cli(COLOR_YELLOW . '3. php bin/setup-plugin-tests.php' . COLOR_RESET . "\n\n");
                exit(1);
            } else {
                 echo esc_cli(COLOR_GREEN . '✅ PHPUnit version ' . $installed_major_version . ' matches composer.json.' . COLOR_RESET . "\n");
            }
        } else {
            echo esc_cli(COLOR_YELLOW . 'Warning: Could not determine installed PHPUnit version via composer. Skipping check.' . COLOR_RESET . "\n");
        }
    }
}

// Set up paths and configuration
$plugin_dir = PROJECT_DIR;

// Get plugin slug from settings, with a fallback to the directory name
$plugin_slug = get_setting('YOUR_PLUGIN_SLUG');
if (empty($plugin_slug)) {
    $plugin_slug = basename($plugin_dir);
    echo esc_cli(COLOR_YELLOW . "Warning: YOUR_PLUGIN_SLUG is not set in tests/.env.testing. Falling back to directory name '{$plugin_slug}'." . COLOR_RESET . "\n");
    echo esc_cli(COLOR_YELLOW . "It is recommended to set YOUR_PLUGIN_SLUG explicitly." . COLOR_RESET . "\n");
}

// Load all settings once at the beginning

// WordPress paths
$wp_root = get_setting('WP_ROOT', '[not set]'); // Container path (/app)
$filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT', '[not set]'); // Host path
$wp_tests_dir_setting = get_setting('WP_TESTS_DIR', '[not set]'); // WordPress test directory

// For local environment, try to find WordPress root if not specified in settings
if (empty($wp_root)) {
    echo "WordPress root not specified in settings, attempting to detect...\n";
    $local_root = find_wordpress_root($plugin_dir);
    if ($local_root !== null) {
        $wp_root = $local_root;
        echo "Found WordPress root at: $wp_root\n";
    } else {
        echo COLOR_RED . 'ERROR: Could not find WordPress root directory (wp-config.php not found).' . COLOR_RESET . "\n";
        echo "Please specify FILESYSTEM_WP_ROOT in your .env.testing file.\n";
        exit(1);
    }
} else {
    echo "Using WordPress root from settings: $wp_root\n";
    echo "Using Filesystem path: $filesystem_wp_root\n";
}

// Get custom PHPUnit database settings from environment variables
$test_db_name = get_setting('WP_TESTS_DB_NAME', null);
$test_table_prefix = get_setting('WP_TESTS_TABLE_PREFIX', null);

// Get PHPUnit database settings
$phpunit_db_settings = get_phpunit_database_settings($wp_db_settings, $test_db_name, $test_table_prefix);

// Extract database settings for use in the script
$db_host = $phpunit_db_settings['db_host'];
$db_user = $phpunit_db_settings['db_user'];
$db_pass = $phpunit_db_settings['db_pass'];
$db_name = $phpunit_db_settings['db_name'];

// Validate that we have a proper WordPress installation
// Always use filesystem_wp_root for validation since that's the path on the host machine
$validation_path = $filesystem_wp_root;

if (!file_exists("$validation_path/wp-includes") || !file_exists("$validation_path/wp-admin") || !file_exists("$validation_path/wp-content")) {
    echo COLOR_RED . 'ERROR: The detected WordPress root does not appear to be a valid WordPress installation.' . COLOR_RESET . "\n";
    echo "Could not find one or more of the following directories:\n";
    echo "  - $validation_path/wp-includes\n";
    echo "  - $validation_path/wp-admin\n";
    echo "  - $validation_path/wp-content\n\n";
    echo "Please check your configuration:\n";
    echo "  - WP_ROOT: $wp_root\n";
    echo "  - FILESYSTEM_WP_ROOT: $filesystem_wp_root\n\n";
    echo "When running in WordPress in Lando, FILESYSTEM_WP_ROOT should be the path on your host machine; WP_ROOT should be the path in the container.\n";
    exit(1);
}

echo COLOR_GREEN . '✅ Valid WordPress installation detected' . COLOR_RESET . "\n";
echo "  - Container path: $wp_root\n";
echo "  - Filesystem path: $filesystem_wp_root\n";

// Set up WordPress test suite directory
// Always use the detected WordPress root to build the test directory path
$wp_tests_dir = get_setting('WP_TESTS_DIR', "$filesystem_wp_root/wp-content/plugins/wordpress-develop/tests/phpunit");
/* Other locations possible, but *always* put the location in WP_TESTS_DIR:
    // As installed by setup-plugin-tests.php
    $filesystem_wp_root . '/wp-content/plugins/wordpress-develop/tests/phpunit',
    // As installed by composer wordpress-dev package
    $filesystem_wp_root . '/vendor/wordpress/wordpress-develop/tests/phpunit',
    // As installed via wp-cli scaffold
    // https://developer.wordpress.org/cli/commands/scaffold/plugin-tests/
    $filesystem_wp_root . '/wp-content/plugins/wordpress-develop/tests/phpunit',
    // Standard locations
    '/tmp/wordpress-tests-lib',
    '/var/www/wordpress-develop/tests/phpunit',
    '/wordpress-develop/tests/phpunit',
*/

echo "Using WordPress test directory: $wp_tests_dir\n";

// If --remove-all flag is set, remove test suite and exit
if ($remove_all) {
    if (remove_test_suite($wp_tests_dir, $db_name, $db_host, $ssh_command)) {
        echo "\n" . COLOR_GREEN . '✅ WordPress test suite successfully removed!' . COLOR_RESET . "\n";
        exit(0);
    } else {
        echo "\n" . COLOR_RED . '❌ Failed to completely remove WordPress test suite.' . COLOR_RESET . "\n";
        exit(1);
    }
}

// Download and set up test suite
if (!download_wp_tests($wp_tests_dir)) {
    exit(1);
}

// Install PHPUnit Polyfills for WordPress Test Suite
if (!install_phpunit_polyfills_for_wp_tests($wp_tests_dir)) {
    echo esc_cli(COLOR_YELLOW . "Warning: Failed to install PHPUnit Polyfills. Integration tests may not work correctly." . COLOR_RESET . "\n");
    // Continue anyway as this is not critical for all test types
}

// Generate config file
if (!generate_wp_tests_config($wp_tests_dir, $wp_root, $db_name, $db_user, $db_pass, $db_host, $plugin_dir)) {
    exit(1);
}

// Create build directories for test coverage reports
echo "Creating build directories for test coverage in $plugin_dir/tests\n";
$build_dirs = array( "$plugin_dir/tests/build/logs", "$plugin_dir/tests/build/coverage" );
foreach ($build_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}
system("chmod -R 777 $plugin_dir/tests/build");

// Install test suite
if (!install_test_suite($wp_tests_dir, $db_name, $db_user, $db_pass, $db_host)) {
    exit(1);
}

// Handle permissions for Lando
$lando_path = exec('which lando');
if (!empty($lando_path)) {
    echo "Setting permissions using Lando...\n";
    system("lando ssh -c 'chown -R www-data:www-data /app/wp-content/plugins/$plugin_slug'");
} else {
    echo "Please set appropriate permissions for your environment on: $plugin_dir\n";
}

echo "WordPress plugin test setup completed successfully.\n";

// Always change back to original directory at the end
echo "Changing back to original directory: $original_dir\n";
chdir($original_dir);

// Instructions for running tests.
// We calculate the destination path here to provide accurate instructions for the user.
$folder_in_wordpress = get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');
$plugin_dest_dir     = make_path($filesystem_wp_root, $folder_in_wordpress, $plugin_slug);
display_composer_test_instructions( is_lando_environment(), $plugin_dest_dir );
