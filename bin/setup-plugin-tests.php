<?php
/**
 * Setup Plugin Tests - Updated with centralized settings
 *
 * Set up the WordPress test environment for PHPUnit testing.
 *
 * @package GL_PHPUnit_Testing
 */

declare(strict_types=1);

// This is a test edit

// Exit if accessed directly
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit;
}

// Set error reporting for CLI
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define script constants
define('SCRIPT_DIR', dirname(__FILE__));
define('PROJECT_DIR', dirname(SCRIPT_DIR));

// Define color constants for terminal output
define('COLOR_RESET', "\033[0m");
define('COLOR_RED', "\033[31m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_MAGENTA', "\033[35m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_WHITE', "\033[37m");
define('COLOR_BOLD', "\033[1m");

/**
 * Load settings from .env.testing file
 *
 * @return array Loaded settings
 */
function load_settings_file(): array {
    $settings = [];
    $env_file = PROJECT_DIR . '/.env.testing';

    if (file_exists($env_file)) {
        echo "Loading environment variables from .env.testing at: " . COLOR_CYAN . $env_file . COLOR_RESET . "\n";
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
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                putenv("$key=$value");
                $settings[$key] = $value;
            }
        }
    }

    return $settings;
}

/**
 * Get a configuration value from environment variables, .env file, or default
 *
 * @param string $name Setting name
 * @param mixed $default Default value if not found
 * @return mixed Setting value
 */
function get_setting(string $name, $default = null) {
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

// Load settings from .env.testing
$loaded_settings = load_settings_file();

/**
 * Format SSH command properly based on the SSH_COMMAND setting
 *
 * @param string $ssh_command The SSH command to use
 * @param string $command The command to execute via SSH
 * @return string The properly formatted command
 */
function format_ssh_command(string $ssh_command, string $command): string {
    // Debug: Show the input command
    echo "\nDebug: format_ssh_command input:\n";
    echo "SSH command: $ssh_command\n";
    echo "Command to execute: $command\n";

    // For Lando and other SSH commands, we need to properly escape quotes
    // The best approach is to use single quotes for the outer shell
    $result = '';
    if (strpos($ssh_command, 'lando ssh') === 0) {
        // Lando requires the -c flag to execute commands
        $result = "$ssh_command -c '  $command  ' 2>&1";
        echo "Debug: Using Lando SSH format\n";
    } else {
        // Regular SSH command
        $result = "$ssh_command '  $command  ' 2>&1";
        echo "Debug: Using regular SSH format\n";
    }

    echo "Debug: Final SSH command: $result\n";
    return $result;
}

/**
 * Format MySQL command with proper parameters and SQL command
 *
 * @param string $host Database host
 * @param string $user Database user
 * @param string $pass Database password
 * @param string $sql SQL command to execute
 * @param string|null $db Optional database name to use
 * @return string Formatted MySQL command
 */
function format_mysql_command(string $host, string $user, string $pass, string $sql, ?string $db = null): string {
    // Build the connection parameters
    $connection_params = "-h $host -u $user";

    // Add password if provided
    if (!empty($pass)) {
        $connection_params .= " -p$pass";
    }

    // Add database if provided
    if (!empty($db)) {
        $connection_params .= " $db";
    }

    // Process SQL command
    // 1. Normalize line endings to avoid issues with different environments
    $sql = str_replace("\r\n", "\n", $sql);

    // 2. Ensure SQL command ends with semicolon
    if (substr(trim($sql), -1) !== ';') {
        $sql .= ';';
    }

    // 3. For multiline SQL (like heredoc), replace newlines with spaces
    $sql = str_replace("\n", " ", $sql);

    // 4. Escape quotes in SQL for shell compatibility
    // We're using single quotes for the -e parameter, so we need to escape any single quotes in the SQL
    // We also need to escape double quotes for the shell
    $escaped_sql = str_replace("'", "\\'", $sql);
    $escaped_sql = str_replace('"', '\\"', $escaped_sql);

    // Add the SQL command with proper quoting
    $formatted_command = "$connection_params -e '$escaped_sql'";

    // Debug: Show the transformation of the SQL command
    echo "\nDebug: format_mysql_command details:\n";
    echo "Original SQL:\n$sql\n";
    echo "Escaped SQL:\n$escaped_sql\n";
    echo "Full MySQL command:\n$formatted_command\n";

    return $formatted_command;
}

/**
 * Format and execute a MySQL command using the appropriate method (direct, SSH, or Lando)
 *
 * @param string $ssh_command The SSH command to use (or 'none' for direct)
 * @param string $host Database host
 * @param string $user Database user
 * @param string $pass Database password
 * @param string $sql SQL command to execute
 * @param string|null $db Optional database name to use
 * @return string The fully formatted command ready to execute
 */
function format_mysql_execution(string $ssh_command, string $host, string $user, string $pass, string $sql, ?string $db = null): string {
    // Format the MySQL parameters
    $mysql_params = format_mysql_command($host, $user, $pass, $sql, $db);

    // Debug output
    echo "\nDebug: format_mysql_execution input:\n";
    echo "SSH command: $ssh_command\n";
    echo "MySQL params: $mysql_params\n";
    echo "Original SQL: $sql\n";

    $cmd = '';

    // Check if this is a Lando environment and we should use lando mysql directly
    if (strpos($ssh_command, 'lando ssh') === 0) {
        // Use lando mysql directly with the parameters
        $cmd = "lando mysql $mysql_params 2>&1";
        echo "Debug: Using direct Lando MySQL format\n";
    }
    // Use SSH to execute MySQL
    elseif ($ssh_command && $ssh_command !== 'none') {
        // Use the SSH command function for other SSH commands
        $cmd = format_ssh_command($ssh_command, "mysql $mysql_params");
    }
    // Direct MySQL execution (no SSH)
    else {
        // For direct MySQL commands, use the original format
        $cmd = "mysql $mysql_params 2>&1";
        echo "Debug: Using direct MySQL format\n";
    }

    echo "Debug: Final command: $cmd\n";
    return $cmd;
}

/**
 * Check system requirements
 *
 * @return bool True if all requirements are met, false otherwise
 */
function check_system_requirements(): bool {
    echo "Checking system requirements...\n";

    // Check if git is available
    if (!is_executable(exec('which git'))) {
        echo "Error: git is required but not installed.\n";
        return false;
    }

    // Check if mysql client is available
    if (!is_executable(exec('which mysql'))) {
        echo "Error: mysql client is required but not installed.\n";
        return false;
    }

    // Check if PHP is available (obviously it is if we're running this script)
    echo COLOR_GREEN . "✅ System requirements met" . COLOR_RESET . "\n";
    return true;
}

/**
 * Find WordPress root by looking for wp-config.php
 *
 * @param string $current_dir Starting directory
 * @param int $max_depth Maximum directory depth to search
 * @return string|null WordPress root path or null if not found
 */
function find_wordpress_root(string $current_dir, int $max_depth = 5): ?string {
    $depth = 0;

    while ($depth < $max_depth) {
        if (file_exists($current_dir . '/wp-config.php')) {
            return realpath($current_dir);
        }
        $current_dir = dirname($current_dir);
        $depth++;
    }

    return null;
}

/**
 * Get WordPress config value
 *
 * @param string $search_value Config constant name
 * @param string $wp_config_path Path to wp-config.php
 * @return string|null Config value or null if not found
 */
function get_wp_config_value(string $search_value, string $wp_config_path): ?string {
    if (!file_exists($wp_config_path)) {
        return null;
    }

    $wp_config_content = file_get_contents($wp_config_path);
    if (preg_match("/define\s*\(\s*['\"]" . preg_quote($search_value, '/') . "['\"].*,\s*['\"]?([^'\"]*)['\"]?\s*\)/", $wp_config_content, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Parse Lando info JSON
 *
 * @return array|null Lando configuration or null if not in Lando environment
 */
function parse_lando_info(): ?array {
    $lando_info = getenv('LANDO_INFO');
    if (empty($lando_info)) {
        return null;
    }

    $lando_data = json_decode($lando_info, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Warning: Failed to parse LANDO_INFO JSON: " . json_last_error_msg() . "\n";
        return null;
    }

    return $lando_data;
}

/**
 * Download WordPress test suite
 *
 * @param string $wp_tests_dir Directory to install tests
 * @return bool True if successful, false otherwise
 */
function download_wp_tests(string $wp_tests_dir): bool {
    echo "Setting up WordPress test suite in: $wp_tests_dir\n";

    // Create tests directory if it doesn't exist
    if (!is_dir($wp_tests_dir)) {
        if (!mkdir($wp_tests_dir, 0755, true)) {
            echo "Error: Failed to create tests directory: $wp_tests_dir\n";
            return false;
        }
    }

    // Check if test suite is already installed
    if (is_dir("$wp_tests_dir/includes") && file_exists("$wp_tests_dir/includes/functions.php")) {
        echo "WordPress test suite already installed.\n";
        return true;
    }

    echo "Downloading WordPress test suite...\n";

    // Create temporary directory
    $tmp_dir = "$wp_tests_dir/tmp";
    if (is_dir($tmp_dir)) {
        system("rm -rf $tmp_dir");
    }

    // Clone WordPress develop repository
    $cmd = "git clone --depth=1 https://github.com/WordPress/wordpress-develop.git $tmp_dir";
    echo "Running: $cmd\n";
    system($cmd, $return_var);

    if ($return_var !== 0) {
        echo "Error: Failed to clone WordPress develop repository.\n";
        return false;
    }

    // Copy required directories
    if (!is_dir("$tmp_dir/tests/phpunit")) {
        echo "Error: WordPress test suite not found in cloned repository.\n";
        system("rm -rf $tmp_dir");
        return false;
    }

    // Create required directories
    foreach (['includes', 'data', 'tests'] as $dir) {
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
        echo "Error: Failed to download WordPress test suite files.\n";
        return false;
    }

    echo COLOR_GREEN . "✅ WordPress test suite downloaded successfully." . COLOR_RESET . "\n";
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
        echo COLOR_GREEN . "✅ Copied wp-tests-config.php to tests directory" . COLOR_RESET . "\n";
    } else {
        echo "Warning: Failed to copy wp-tests-config.php. You may need to copy the file manually.\n";
        // Continue anyway, this is not critical
    }

    echo COLOR_GREEN . "✅ wp-tests-config.php generated successfully." . COLOR_RESET . "\n";
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
    // Get database settings from environment variables if available
    // Get database settings with priority order
    $db_host = get_setting('WP_TESTS_DB_HOST', $db_host);
    $db_user = get_setting('WP_TESTS_DB_USER', $db_user);
    $db_pass = get_setting('WP_TESTS_DB_PASSWORD', $db_pass);
    $db_name = get_setting('WP_TESTS_DB_NAME', $db_name);
    echo "Setting up test database...\n";
    echo "Debug: Database parameters:\n";
    echo "  Host: $db_host\n";
    echo "  User: $db_user\n";
    echo "  Name: $db_name\n";
    echo "  Password length: " . strlen($db_pass) . "\n";

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
    $mysql_cmd = "mysql";
    $use_ssh = false;

    // Determine how to execute database commands
    echo "Database access method from .env.testing: " . COLOR_CYAN . "SSH_COMMAND=" . ($ssh_command ?: 'not set') . COLOR_RESET . "\n";
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

    // Determine if we're targeting a Lando environment based on SSH_COMMAND
    $targeting_lando = $ssh_command === 'lando ssh';

    if ($targeting_lando) {
        echo "Using standard Lando database configuration...\n";

        // For Lando environments, use standard database settings
        // These are the default values in a standard Lando WordPress setup

        // Use get_setting with fallbacks for Lando environment
        $db_host = get_setting('WP_TESTS_DB_HOST', 'database');
        $db_user = get_setting('WP_TESTS_DB_USER', 'wordpress');
        $db_pass = get_setting('WP_TESTS_DB_PASSWORD', 'wordpress');

        echo "Host: $db_host, User: $db_user, Password: $db_pass\n";
    }

    // Verify the connection using the parameters that will be in wp-tests-config.php
    echo "Verifying database connection to $db_host...\n";

    // format and execute the MySQL command
    $cmd = format_mysql_execution($ssh_command, $db_host, $db_user, $db_pass, 'SELECT 1;');

    echo "Debug: Executing command: $cmd\n";
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Cannot connect to MySQL server.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        return false;
    }

    echo COLOR_GREEN . "✅ Connected to MySQL on host: $db_host" . COLOR_RESET . "\n";

    echo COLOR_GREEN . "✅ MySQL connection successful" . COLOR_RESET . "\n";

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
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Warning: Failed to drop test database.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        echo "Continuing anyway, as the database might not exist yet...\n";
    } else {
        echo COLOR_GREEN . "✅ Existing database dropped (if it existed)" . COLOR_RESET . "\n";
    }

    // Create database and grant permissions
    echo "Creating database...\n";

    // Build the SQL command using heredoc for better readability
    // Write SQL exactly as you would type it directly into MySQL
    // The format_mysql_command function will handle all necessary escaping
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
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Failed to create test database.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        echo "\nDebug: Full SQL command:\n$sql_command\n";
        echo "\nDebug: Try running this command manually to see the error:\n";
        echo "$cmd\n";
        return false;
    }

    echo COLOR_GREEN . "✅ Database created successfully" . COLOR_RESET . "\n";

    // Verify database exists and is accessible
    echo "Verifying database access...\n";
    $cmd = format_mysql_execution($ssh_command, $db_host, $db_user, $db_pass, "SHOW DATABASES LIKE \"$db_name\";");

    echo "Debug: Executing command: $cmd\n";
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Cannot access test database after creation.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        return false;
    }

    echo COLOR_GREEN . "✅ Test database created and verified" . COLOR_RESET . "\n";

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
    system("php $wp_tests_dir/includes/install.php $wp_tests_dir/wp-tests-config.php", $return_var);

    // Clean up
    unlink("$wp_tests_dir/install-wp-tests.php");

    if ($return_var !== 0) {
        echo "Error: Failed to install WordPress test framework.\n";
        return false;
    }

    echo COLOR_GREEN . "✅ WordPress test framework installed successfully." . COLOR_RESET . "\n";
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
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Failed to drop test database.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        // Continue anyway to remove files
    } else {
        echo COLOR_GREEN . "✅ Database dropped successfully" . COLOR_RESET . "\n";
    }

    // Remove test files if they exist
    if (file_exists($wp_tests_dir)) {
        echo "Removing test files from $wp_tests_dir...\n";
        system("rm -rf $wp_tests_dir");
        echo COLOR_GREEN . "✅ Test files removed successfully" . COLOR_RESET . "\n";
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

// Parse command line arguments
$remove_all = false;
$show_help = false;

foreach ($argv as $arg) {
    if ($arg === '--remove-all' || $arg === '--remove') {
        $remove_all = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        $show_help = true;
    }
}

// Display help if requested
if ($show_help) {
    display_help();
    exit(0);
}

echo "Setting up WordPress plugin tests...\n";

// Check system requirements
if (!check_system_requirements()) {
    exit(1);
}

// Set up paths and configuration
$plugin_dir = PROJECT_DIR;
$plugin_slug = basename($plugin_dir);

// Check if we're running in a Lando environment
$lando_info = getenv('LANDO_INFO');
$in_lando = $lando_info !== false;

// This script should be run from the host machine, not inside Lando
if ($in_lando) {
    echo COLOR_RED . "ERROR: This script should NOT be run from inside a Lando container!" . COLOR_RESET . "\n";
    echo "Please run this script from your host machine instead.\n";
    echo "The script will use 'lando ssh' automatically for database operations\n";
    echo "based on your SSH_COMMAND setting in .env.testing.\n";
    exit(1);
}

// Get WordPress root path from settings
$wp_root = get_setting('FILESYSTEM_WP_ROOT', '');

// Get database settings from environment variables if available
// These will be used as the highest priority source for database settings
$env_db_host = get_setting('WP_TESTS_DB_HOST', '');
$env_db_user = get_setting('WP_TESTS_DB_USER', '');
$env_db_pass = get_setting('WP_TESTS_DB_PASSWORD', '');
$env_db_name = get_setting('WP_TESTS_DB_NAME', '');

// For local environment, try to find WordPress root if not specified in settings
if (empty($wp_root)) {
    echo "WordPress root not specified in settings, attempting to detect...\n";
    $local_root = find_wordpress_root($plugin_dir);
    if ($local_root !== null) {
        $wp_root = $local_root;
        echo "Found WordPress root at: $wp_root\n";
    } else {
        echo COLOR_RED . "ERROR: Could not find WordPress root directory (wp-config.php not found)." . COLOR_RESET . "\n";
        echo "Please specify FILESYSTEM_WP_ROOT in your .env.testing file.\n";
        exit(1);
    }
} else {
    echo "Using WordPress root from settings: $wp_root\n";
}

// Get WordPress configuration
$wp_config_path = "$wp_root/wp-config.php";
if (file_exists($wp_config_path)) {
    echo "Reading WordPress configuration from $wp_config_path\n";
    $db_name = get_wp_config_value('DB_NAME', $wp_config_path) ?: 'wordpress_test';
    $db_user = get_wp_config_value('DB_USER', $wp_config_path) ?: 'root';
    $db_pass = get_wp_config_value('DB_PASSWORD', $wp_config_path) ?: '';
    $db_host = get_wp_config_value('DB_HOST', $wp_config_path) ?: 'localhost';
} else {
    echo "Warning: wp-config.php not found at $wp_config_path\n";
    // Fallback values
    $db_name = 'wordpress_test';
    $db_user = 'root';
    $db_pass = '';
    $db_host = 'localhost';
}

// Override with Lando database configuration if available
if ($in_lando) {
    echo "Getting Lando internal configuration...\n";

    // Find the database service
    $db_service = null;
    foreach ($lando_info as $service_name => $service_info) {
        if (isset($service_info['type']) && ($service_info['type'] === 'mysql' || $service_info['type'] === 'mariadb')) {
            $db_service = $service_info;
            break;
        }
    }

    if ($db_service !== null) {
        // Environment variables were already loaded at the script level

        // First try environment variables (highest priority)
        if ($env_db_host !== false) {
            $db_host = $env_db_host;
        } elseif (isset($db_service['internal_connection']['host'])) {
            // Then try Lando info
            $db_host = $db_service['internal_connection']['host'];
        } else {
            // Final fallback
            $db_host = 'database';
        }

        // Get credentials - first from environment variables
        if ($env_db_user !== false) {
            $db_user = $env_db_user;
        } elseif (isset($db_service['creds']['user'])) {
            // Then from Lando info
            $db_user = $db_service['creds']['user'];
        }

        if ($env_db_pass !== false) {
            $db_pass = $env_db_pass;
        } elseif (isset($db_service['creds']['password'])) {
            // Then from Lando info
            $db_pass = $db_service['creds']['password'];
        }

        // Get database name from environment or use default
        if ($env_db_name !== false) {
            $db_name = $env_db_name;
        } else {
            // Use the actual database name for tests
            $db_name = "wordpress_test";
        }

        echo "Using Lando database configuration:\n";
        echo "  Host: $db_host\n";
        echo "  User: $db_user\n";
        echo "  Test Database will be: $db_name\n";

        // Override paths for Lando environment
        $wp_root = "/app";
        $wp_config_path = "$wp_root/wp-config.php";
    } else {
        echo COLOR_YELLOW . "WARNING: Database service not found in Lando configuration!" . COLOR_RESET . "\n";
        echo "This indicates a potential issue with your Lando setup.\n";
        echo "Please check that your .lando.yml file has a valid database service configured.\n";
        echo "Example configuration:\n";
        echo "  database:\n";
        echo "    type: mysql:8.0\n";
        echo "    healthcheck: mysql -uroot --silent --execute \"SHOW DATABASES;\"\n\n";

        // Continue with the current database settings
        echo "Current database settings being used:\n";
        echo "  Host: $db_host\n";
        echo "  User: $db_user\n";
        echo "  Password: [hidden]\n";
        echo "  Test Database: $db_name\n\n";

        echo "Using default Lando database configuration:\n";
        echo "  Host: $db_host\n";
        echo "  User: $db_user\n";
        echo "  Test Database will be: $db_name\n";

        // Override paths for Lando environment
        $wp_root = "/app";
        $wp_config_path = "$wp_root/wp-config.php";
    }
}

// Validate that we have a proper WordPress installation
if (!file_exists("$wp_root/wp-includes") || !file_exists("$wp_root/wp-admin") || !file_exists("$wp_root/wp-content")) {
    echo COLOR_RED . "ERROR: The detected WordPress root ($wp_root) does not appear to be a valid WordPress installation." . COLOR_RESET . "\n";
    echo "Could not find one or more of the following directories:\n";
    echo "  - $wp_root/wp-includes\n";
    echo "  - $wp_root/wp-admin\n";
    echo "  - $wp_root/wp-content\n\n";
    echo "Please ensure you're running this script from within a WordPress plugin directory.\n";
    exit(1);
}

echo COLOR_GREEN . "✅ Valid WordPress installation detected at: $wp_root" . COLOR_RESET . "\n";

// Set up WordPress test suite directory
// Always use the detected WordPress root to build the test directory path
$wp_tests_dir = "$wp_root/wp-content/plugins/wordpress-develop/tests/phpunit";
echo "Using WordPress test directory: $wp_tests_dir\n";

// Get SSH command if available
$ssh_command = get_setting('SSH_COMMAND', '');

// If --remove-all flag is set, remove test suite and exit
if ($remove_all) {
    if (remove_test_suite($wp_tests_dir, $db_name, $db_host, $ssh_command)) {
        echo "\n" . COLOR_GREEN . "✅ WordPress test suite successfully removed!" . COLOR_RESET . "\n";
        exit(0);
    } else {
        echo "\n" . COLOR_RED . "❌ Failed to completely remove WordPress test suite." . COLOR_RESET . "\n";
        exit(1);
    }
}

// Download and set up test suite
if (!download_wp_tests($wp_tests_dir)) {
    exit(1);
}

// Generate config file
if (!generate_wp_tests_config($wp_tests_dir, $wp_root, $db_name, $db_user, $db_pass, $db_host, $plugin_dir)) {
    exit(1);
}

// Create build directories for test coverage reports
echo "Creating build directories for test coverage in $plugin_dir\n";
$build_dirs = ["$plugin_dir/build/logs", "$plugin_dir/build/coverage"];
foreach ($build_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}
system("chmod -R 777 $plugin_dir/build");

// Install test suite
if (!install_test_suite($wp_tests_dir, $db_name, $db_user, $db_pass, $db_host)) {
    exit(1);
}

// Handle permissions for Lando
$lando_path = exec('which lando');
if (!empty($lando_path)) {
    echo "Setting permissions using Lando...\n";
    chdir($wp_root);
    system("lando ssh -c 'chown -R www-data:www-data /app/wp-content/plugins/$plugin_slug'");
} else {
    echo "Please set appropriate permissions for your environment on: $plugin_dir\n";
}

echo "WordPress plugin test setup completed successfully.\n";

// Instructions for running tests
echo "\nTo run integration tests:\n";
echo "1. Make sure your WordPress test environment is set up\n";
echo "2. Run: composer test:integration\n";
echo "3. For unit tests: composer test:unit\n";
echo "4. For WP-Mock tests: composer test:wp-mock\n";
