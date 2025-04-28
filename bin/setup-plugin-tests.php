<?php
/**
 * Setup Plugin Tests - Updated with centralized settings
 *
 * Set up the WordPress test environment for PHPUnit testing.
 *
 * @package WP_PHPUnit_Framework
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework;

// Exit if accessed directly, should be run command line
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

// Global exception handler to catch and display any uncaught exceptions
set_exception_handler(function(\Throwable $e) {
    echo "\n" . COLOR_RED . "UNCAUGHT EXCEPTION: " . get_class($e) . COLOR_RESET . "\n";
    echo COLOR_RED . "Message: " . $e->getMessage() . COLOR_RESET . "\n";
    echo COLOR_RED . "File: " . $e->getFile() . " (Line " . $e->getLine() . ")" . COLOR_RESET . "\n";
    echo COLOR_RED . "Stack trace:" . COLOR_RESET . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
});

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

/**
 * Retrieves WordPress database connection settings from multiple sources in a specific priority order.
 * Its purpose is to determine the database settings (host, user, password, name, and table prefix)
 * that should be used for WordPress plugin testing.
 *
 * Priority Order:
 * 1. wp-config.php (lowest priority)
 * 2. Config file (.env.testing by default)
 * 3. Environment variables
 * 4. Lando configuration (highest priority)
 *
 * Note: The table_prefix is only read by WordPress from wp-config.php and cannot be overridden.
 *
 * @param string $wp_config_path Path to WordPress configuration file
 * @param array $lando_info Lando environment configuration, obtained by executing 'lando info' command
 * @param string $config_file_name Name of the configuration file (default: '.env.testing')
 * @return array Database settings with keys: db_host, db_user, db_pass, db_name, table_prefix
 * @throws Exception If wp-config.php doesn't exist or if any required database settings are missing
 */
function get_database_settings(
    string $wp_config_path,
    array $lando_info = [],
    string $config_file_name = '.env.testing'
): array {
    // Initialize with not set values
    $db_settings = [
        'db_host' => '[not set]',
        'db_user' => '[not set]',
        'db_pass' => '[not set]',
        'db_name' => '[not set]',
        'table_prefix' => 'wp_' // Default WordPress table prefix
    ];

    // 1. Load from wp-config.php (lowest priority)
    if (file_exists($wp_config_path)) {
        echo "Reading database settings from wp-config.php...\n";

        // Include the wp-config.php file directly
        try {
            // Suppress warnings/notices that might come from wp-config.php
            @include_once $wp_config_path;

            // Get the database settings from the constants
            if (defined('DB_NAME') && DB_NAME) {
                $db_settings['db_name'] = DB_NAME;
            }

            if (defined('DB_USER') && DB_USER) {
                $db_settings['db_user'] = DB_USER;
            }

            if (defined('DB_PASSWORD')) { // Password can be empty
                $db_settings['db_pass'] = DB_PASSWORD;
            }

            if (defined('DB_HOST') && DB_HOST) {
                $db_settings['db_host'] = DB_HOST;
            }

            // Get the table prefix from the global variable
            global $table_prefix;
            if (isset($table_prefix)) {
                $db_settings['table_prefix'] = $table_prefix;
            }
        } catch (\Exception $e) {
            echo COLOR_YELLOW . "Warning: Error including $wp_config_path: {$e->getMessage()}" . COLOR_RESET . "\n";
        }
    }

    // 2. Load from config file (e.g., .env, .env.testing)
    $env_file_db_host = get_setting('WP_TESTS_DB_HOST', null);
    $env_file_db_user = get_setting('WP_TESTS_DB_USER', null);
    $env_file_db_pass = get_setting('WP_TESTS_DB_PASSWORD', null);
    $env_file_db_name = get_setting('WP_TESTS_DB_NAME', null);

    if ($env_file_db_host) $db_settings['db_host'] = $env_file_db_host;
    if ($env_file_db_user) $db_settings['db_user'] = $env_file_db_user;
    if ($env_file_db_pass !== null) $db_settings['db_pass'] = $env_file_db_pass; // Password can be empty
    if ($env_file_db_name) $db_settings['db_name'] = $env_file_db_name;
    // Note: table_prefix is only read from wp-config.php and not from environment variables or config files

    // 3. Load from environment variables
    $env_var_db_host = getenv('WP_TESTS_DB_HOST');
    $env_var_db_user = getenv('WP_TESTS_DB_USER');
    $env_var_db_pass = getenv('WP_TESTS_DB_PASSWORD');
    $env_var_db_name = getenv('WP_TESTS_DB_NAME');

    if ($env_var_db_host !== false && $env_var_db_host) $db_settings['db_host'] = $env_var_db_host;
    if ($env_var_db_user !== false && $env_var_db_user) $db_settings['db_user'] = $env_var_db_user;
    if ($env_var_db_pass !== false) $db_settings['db_pass'] = $env_var_db_pass; // Password can be empty
    if ($env_var_db_name !== false && $env_var_db_name) $db_settings['db_name'] = $env_var_db_name;
    // Note: table_prefix is only read from wp-config.php and not from environment variables

    // 4. Load from Lando configuration (highest priority)
    if (!empty($lando_info)) {
        echo "Getting Lando internal configuration...\n";

        // Find the database service
        $db_service = null;
        foreach ($lando_info as $service_name => $service_info) {
            if (isset($service_info['type']) && $service_info['type'] === 'mysql') {
                $db_service = $service_info;
                break;
            }
        }

        // If we found a database service, use its credentials
        if ($db_service !== null && isset($db_service['creds'])) {
            $creds = $db_service['creds'];

            // In Lando, we trust the Lando configuration completely
            if (isset($db_service['internal_connection']['host'])) {
                $db_settings['db_host'] = $db_service['internal_connection']['host'];
            }
            if (isset($creds['user'])) {
                $db_settings['db_user'] = $creds['user'];
            }
            if (isset($creds['password'])) {
                $db_settings['db_pass'] = $creds['password'];
            }
            if (isset($creds['database'])) {
                $db_settings['db_name'] = $creds['database'];
            }

            echo "Found Lando database service: {$db_settings['db_host']}\n";
            // Note: table_prefix is only read from wp-config.php and not from Lando configuration
        } else {
            echo COLOR_YELLOW . "Warning: No MySQL service found in Lando configuration." . COLOR_RESET . "\n";
            echo "This indicates a potential issue with your Lando setup.\n";
        }
    }

    // Check if we have all required settings
    $missing_settings = [];
    foreach ($db_settings as $key => $value) {
        if ($value === '[not set]') {
            $missing_settings[] = strtoupper($key);
        }
    }

    if (!empty($missing_settings)) {
        $missing_str = implode(', ', $missing_settings);
        throw new \Exception("Missing required database settings: $missing_str. Please configure these in your .env.testing file or wp-config.php.");
    }

    // Display the final settings
    echo "Database settings (final):\n";
    echo "- Host: {$db_settings['db_host']}\n";
    echo "- User: {$db_settings['db_user']}\n";
    echo "- Database: {$db_settings['db_name']}\n";
    echo "- Password length: " . strlen($db_settings['db_pass']) . "\n";

    return $db_settings;
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
 * @param string $command_type The type of command ('lando_direct', 'ssh', or 'direct')
 * @return string Formatted MySQL command
 */
function format_mysql_command(string $host, string $user, string $pass, string $sql, ?string $db = null, string $command_type = 'ssh'): string {
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

    // 4. Escape quotes in SQL based on command type
    $escaped_sql = $sql;

    // Different escaping rules based on command type
    if ($command_type === 'lando_direct') {
        // For direct lando mysql command, we only need to escape single quotes
        // Double quotes don't need double-escaping
        $escaped_sql = str_replace("'", "'\\'", $sql);
    } else {
        // For SSH or direct MySQL, escape both single and double quotes
        $escaped_sql = str_replace("'", "\\'", $sql);
        $escaped_sql = str_replace('"', '\\"', $escaped_sql);
    }

    // Add the SQL command with proper quoting
    $formatted_command = "$connection_params -e '$escaped_sql'";

    // Debug: Show the transformation of the SQL command
    # echo "\nDebug: format_mysql_command details:\n";
    # echo "Original SQL:\n$sql\n";
    # echo "Escaped SQL:\n$escaped_sql\n";
    # echo "Full MySQL command:\n$formatted_command\n";

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
    $command_type = 'ssh';

    // Determine the command type based on the SSH command
    if (strpos($ssh_command, 'lando ssh') === 0) {
        $command_type = 'lando_direct';
    } elseif (!$ssh_command || $ssh_command === 'none') {
        $command_type = 'direct';
    }

    // Format the MySQL parameters with the appropriate command type
    $mysql_params = format_mysql_command($host, $user, $pass, $sql, $db, $command_type);

    // Debug output
    # echo "\nDebug: format_mysql_execution input:\n";
    echo "Original SQL: $sql\n";
    echo "SSH command: $ssh_command  MySQL params: $mysql_params\n";
    # echo "Command type: $command_type\n";

    $cmd = '';

    // Check if this is a Lando environment and we should use lando mysql directly
    if ($command_type === 'lando_direct') {
        // Use lando mysql directly with the parameters
        $cmd = "lando mysql $mysql_params 2>&1";
        echo "Debug: Using direct Lando MySQL format\n";
    }
    // Use SSH to execute MySQL
    elseif ($command_type === 'ssh') {
        // Use the SSH command function for other SSH commands
        $cmd = format_ssh_command($ssh_command, "mysql $mysql_params");
    }
    // Direct MySQL execution (no SSH)
    else {
        // For direct MySQL commands, use the original format
        $cmd = "mysql $mysql_params 2>&1";
        echo "Debug: Using direct MySQL format\n";
    }

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

    // Debug information
    echo "Debug: WordPress test directory: $wp_tests_dir\n";

    // Check if files exist
    echo "Debug: Checking if files exist:\n";
    echo "- install.php exists: " . (file_exists("$wp_tests_dir/includes/install.php") ? 'Yes' : 'No') . "\n";
    echo "- wp-tests-config.php exists: " . (file_exists("$wp_tests_dir/wp-tests-config.php") ? 'Yes' : 'No') . "\n";

    // Check database configuration in wp-tests-config.php
    if (file_exists("$wp_tests_dir/wp-tests-config.php")) {
        echo "Debug: Checking database configuration in wp-tests-config.php\n";
        $config_content = file_get_contents("$wp_tests_dir/wp-tests-config.php");

        // Extract database settings
        preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config_content, $db_name_match);
        preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config_content, $db_user_match);
        preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config_content, $db_host_match);

        echo "- DB_NAME: " . ($db_name_match[1] ?? 'Not found') . "\n";
        echo "- DB_USER: " . ($db_user_match[1] ?? 'Not found') . "\n";
        echo "- DB_HOST: " . ($db_host_match[1] ?? 'Not found') . "\n";

        // Check if the database settings match what we expect
        echo "Debug: Comparing with our database settings:\n";
        echo "- Our DB_NAME: $db_name\n";
        echo "- Our DB_USER: $db_user\n";
        echo "- Our DB_HOST: $db_host\n";
    }

    // Determine which PHP to use based on environment
    $php_command = "php";
    $install_path = "$wp_tests_dir/includes/install.php";
    $config_path = "$wp_tests_dir/wp-tests-config.php";

    if ($targeting_lando) {
        echo "Debug: Using Lando PHP for installation...\n";
        $php_command = "lando php";
        // When using Lando for WordPress, we use the database in Lando, so we need to use "lando php" and container paths
        $wp_root = get_setting('WP_ROOT', '/app');
        $install_path = "$wp_root/wp-content/plugins/wordpress-develop/tests/phpunit/includes/install.php";
        $config_path = "$wp_root/wp-content/plugins/wordpress-develop/tests/phpunit/wp-tests-config.php";
    } else {
        echo "Debug: Using local PHP for installation...\n";
    }

    // Capture output for debugging
    $output = [];
    $command = "$php_command $install_path $config_path 2>&1";
    echo "Debug: PHP command: $php_command $install_path $config_path\n";
    echo "Debug: Executing: $command\n";
    exec($command, $output, $return_var);

    // Display output
    echo "Debug: Command output:\n";
    echo implode("\n", $output) . "\n";
    echo "Debug: Return code: $return_var\n";

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

// Load all settings once at the beginning

// WordPress paths
$wp_root = get_setting('WP_ROOT', '[not set]'); // Container path (/app)
$filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT', '[not set]'); // Host path
$wp_tests_dir_setting = get_setting('WP_TESTS_DIR', '[not set]'); // WordPress test directory

// SSH command for database operations
$ssh_command = get_setting('SSH_COMMAND', 'none');

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
    echo "Using Filesystem path: $filesystem_wp_root\n";
}

// Get WordPress configuration path
$wp_config_path = "$wp_root/wp-config.php";

/**
 * Get Lando information by running the 'lando info' command
 * This works when running from outside a Lando container
 *
 * @return array Lando information or empty array if Lando is not running
 */
function get_lando_info(): array {
    // Check if lando command exists
    $lando_exists = shell_exec('which lando 2>/dev/null');
    if (empty($lando_exists)) {
        echo "Lando command not found. Skipping Lando configuration.\n";
        return [];
    }

    // Run lando info command
    echo "Checking for Lando configuration...\n";
    $lando_info_json = shell_exec('lando info --format=json 2>/dev/null');
    if (empty($lando_info_json)) {
        echo "No Lando configuration found. Is Lando running? (`lando start` command, if should be running)\n";
        return [];
    }

    // Parse JSON output
    $lando_info = json_decode($lando_info_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($lando_info)) {
        echo "Error parsing Lando configuration. Skipping Lando settings.\n";
        return [];
    }

    echo "Found Lando configuration.\n";
    return $lando_info;
}

// Get Lando info by executing 'lando info' command
$lando_info_array = get_lando_info();
if (!empty($lando_info_array)) {
    echo "Using Lando database configuration for WordPress testing.\n";
}

/**
 * Configure PHPUnit database settings based on WordPress database settings
 *
 * @param array $wp_db_settings WordPress database settings from get_database_settings()
 * @param string|null $test_db_name Complete database name for tests (default: null, will use WP db name + '_test')
 * @param string|null $test_table_prefix Table prefix for tests (default: null, will use WordPress table prefix)
 * @return array PHPUnit database settings
 */
function get_phpunit_database_settings(
    array $wp_db_settings,
    ?string $test_db_name = null,
    ?string $test_table_prefix = null
): array {
    // Start with WordPress database settings
    $phpunit_db_settings = $wp_db_settings;

    // Set database name for tests
    if ($test_db_name !== null) {
        // Use specified test database name
        $phpunit_db_settings['db_name'] = $test_db_name;
    } else {
        // Default: append '_test' to WordPress database name
        $phpunit_db_settings['db_name'] = $wp_db_settings['db_name'] . '_test';
    }

    // Set table prefix for tests
    if ($test_table_prefix !== null) {
        // Use specified test table prefix
        $phpunit_db_settings['table_prefix'] = $test_table_prefix;
    }
    // else: keep WordPress table prefix (already in $phpunit_db_settings)

    echo "PHPUnit database settings:\n";
    echo "  - Host: {$phpunit_db_settings['db_host']}\n";
    echo "  - User: {$phpunit_db_settings['db_user']}\n";
    echo "  - Database: {$phpunit_db_settings['db_name']}\n";
    echo "  - Table prefix: {$phpunit_db_settings['table_prefix']}\n";

    return $phpunit_db_settings;
}

// Get WordPress database settings
$wp_db_settings = get_database_settings($wp_config_path, $lando_info_array);

// Get custom PHPUnit database settings from environment variables
$test_db_name = get_setting('WP_PHPUNIT_DB_NAME', null);
$test_table_prefix = get_setting('WP_PHPUNIT_TABLE_PREFIX', null);

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
    echo COLOR_RED . "ERROR: The detected WordPress root does not appear to be a valid WordPress installation." . COLOR_RESET . "\n";
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

echo COLOR_GREEN . "✅ Valid WordPress installation detected" . COLOR_RESET . "\n";
echo "  - Container path: $wp_root\n";
echo "  - Filesystem path: $filesystem_wp_root\n";

// Set up WordPress test suite directory
// Always use the detected WordPress root to build the test directory path
$wp_tests_dir = "$filesystem_wp_root/wp-content/plugins/wordpress-develop/tests/phpunit";
echo "Using WordPress test directory: $wp_tests_dir\n";

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
