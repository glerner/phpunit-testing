<?php
namespace WP_PHPUnit_Framework;

// files in bin/ need to Include the Composer autoloader to enable PSR-4 class autoloading
require_once __DIR__ . '/../vendor/autoload.php';

use WP_PHPUnit_Framework\Service\Database_Connection_Manager;

// Instantiate the singleton Database_Connection_Manager
$db_manager = Database_Connection_Manager::get_instance();

// Global counter for query identification
$query_counter = 0;

/**
 * Test MySQL command execution and display results with various quoting styles
 *
 * Run with: php test-mysql-escaping.php
 *
 * This script tests MySQL command execution with various quoting styles
 * and demonstrates environment-aware MySQL command execution.
 *
 * IMPORTANT: All database execution functions (execute_mysqli_query, execute_mysqli_direct,
 * execute_mysqli_lando, and execute_mysql_via_ssh) now use the Database_Connection_Manager
 * for pooled connections, which improves performance by reusing database connections.
 * See /src/GL_Reinvent/Service/Database_Connection_Manager.php for implementation details.
 *
 * The connection manager provides the following benefits:
 * - Connection pooling to reduce overhead of repeated connections
 * - Automatic cleanup of inactive connections
 * - Centralized connection management with standardized error handling
 */

// Define color constants for terminal output
// Define color constants for terminal output
const COLOR_RESET = "\033[0m";
const COLOR_RED = "\033[31m";
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_MAGENTA = "\033[35m";
const COLOR_CYAN = "\033[36m";
const COLOR_WHITE = "\033[37m";
const COLOR_BOLD = "\033[1m";

/**
 * Load settings from a .env file
 *
 * @param string $env_file Path to the .env file
 * @return array Array of environment variables
 */
function load_settings_file( string $env_file ): array {
	$settings = [];

	// Load from .env file
	if ( file_exists( $env_file ) ) {
		$file_content = file_get_contents($env_file);
		if ($file_content === false) {
			echo "Warning: Could not read contents of $env_file\n";
			return $settings;
		}

		$lines = file( $env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ($lines === false) {
			echo "Warning: Could not parse lines from $env_file\n";
			return $settings;
		}

		foreach ( $lines as $line ) {
			// Skip comments
			if ( strpos( trim( $line ), '#' ) === 0 ) {
				continue;
			}

			// Parse variable
			$parts = explode( '=', $line, 2 );
			if ( count( $parts ) === 2 ) {
				$key = trim( $parts[0] );
				$value = trim( $parts[1] );

				// Remove quotes if present
				if ( ( strpos( $value, '"' ) === 0 && strrpos( $value, '"' ) === strlen( $value ) - 1 ) ||
					 ( strpos( $value, "'" ) === 0 && strrpos( $value, "'" ) === strlen( $value ) - 1 ) ) {
					$value = substr( $value, 1, -1 );
				}

				$settings[ $key ] = $value;
			}
		}
	} else {
		echo "Warning: Environment file not found at: $env_file\n";
        echo "Called from " . __FILE__ . "\n";
	}

	// For critical paths, try to detect from current directory if not set
	if (empty($settings['FILESYSTEM_WP_ROOT']) || $settings['FILESYSTEM_WP_ROOT'] === '[not set]') {
		$current_dir = getcwd();
		if (strpos($current_dir, '/wp-content/plugins/') !== false) {
			// Extract WordPress root from current path
			$wp_root = substr($current_dir, 0, strpos($current_dir, '/wp-content/plugins/'));
			$settings['FILESYSTEM_WP_ROOT'] = $wp_root;
			echo "Detected FILESYSTEM_WP_ROOT from current directory: $wp_root\n";
		}
	}

	return $settings;
}

/**
 * Get a configuration value from environment variables, .env file, or default
 *
 * @param string $name Setting name
 * @param mixed  $default Default value if not found
 * @return mixed Setting value
 */
function get_setting( string $name, mixed $default = null ): mixed {
    // Create a debug log file for tracking get_setting calls
    $debug_log_file = '/tmp/phpunit-get-setting-debug.log';

    // Get caller information
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[0];
    $caller_function = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'unknown';
    $caller_class = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : 'none';

    // Log the call
    $log_prefix = date('Y-m-d H:i:s') . " | " . getmypid() . " | ";
    error_log($log_prefix . "CALL: get_setting('$name') from " . $caller['file'] . ":" . $caller['line'] . " in $caller_class::$caller_function\n", 3, $debug_log_file);

    // Log the state of $loaded_settings
    global $loaded_settings;
    $settings_count = isset($loaded_settings) ? count($loaded_settings) : 0;
    $settings_status = isset($loaded_settings) ? "SET ($settings_count items)" : "NOT SET";
    error_log($log_prefix . "STATE: \$loaded_settings is $settings_status\n", 3, $debug_log_file);

    // Check environment variables first (highest priority)
    $env_value = getenv($name);
    if ($env_value !== false) {
        error_log($log_prefix . "RESULT: Found '$name' in environment variables, value: '$env_value'\n", 3, $debug_log_file);
        return $env_value;
    }

    // Check our loaded settings (already loaded from .env.testing)
    if (isset($loaded_settings[ $name ])) {
        $value = $loaded_settings[ $name ];
        error_log($log_prefix . "RESULT: Found '$name' in \$loaded_settings, value: '$value'\n", 3, $debug_log_file);
        return $value;
    }

    /* Don't recursively set, if there is an error
    $error_log_file = get_setting('TEST_ERROR_LOG', '/tmp/phpunit-testing.log');
    */
    if (!isset($error_log_file)) {
        $error_log_file = '/tmp/phpunit-testing.log';
    }

    // Silently log critical setting issues to error log without screen output
    if (($name === 'WP_ROOT' || $name === 'FILESYSTEM_WP_ROOT' || $name === 'WP_TESTS_DB_NAME')) {
        if (empty($loaded_settings)) {
            error_log("Warning: \$loaded_settings is empty when requesting '$name' in " . $caller['file'] . ":" . $caller['line'] . "\n\n", 3, $error_log_file);
            error_log($log_prefix . "WARNING: \$loaded_settings is empty when requesting critical setting '$name'\n", 3, $debug_log_file);
        } else if (!isset($loaded_settings[$name])) {
            error_log("Warning: '$name' not found in \$loaded_settings in " . $caller['file'] . ":" . $caller['line'] . "\n\n", 3, $error_log_file);
            error_log($log_prefix . "WARNING: Critical setting '$name' not found in \$loaded_settings\n", 3, $debug_log_file);
        }
    }

    // Return default if not found
    error_log($log_prefix . "RESULT: '$name' not found, returning default value\n", 3, $debug_log_file);
    return $default;
}

/**
 * Get Lando information by running the 'lando list' and 'lando info' commands
 * This works when running from outside a Lando container
 *
 * @return array Lando information or empty array if Lando is not running
 */
function get_lando_info(): array {
    // First check if we're in a Lando environment
    if (!is_lando_environment()) {
        colored_message( 'No running Lando environment detected. Is Lando running?', 'red' );
        colored_message( "Run 'lando start' to start Lando, or see docs/guides/rebuilding-after-system-updates.md if you're having issues after system updates.", 'yellow' );
        return array();
    }

    colored_message( 'Found running Lando containers.', 'green' );

    // Now get the detailed configuration with lando info
    $lando_info_json = shell_exec('lando info --format=json 2>/dev/null');
    if (empty($lando_info_json)) {
        colored_message( 'Lando is running but could not get configuration details.', 'red' );
        return array();
    }

    // Parse JSON output from lando info
    $lando_info = json_decode($lando_info_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($lando_info)) {
        colored_message( 'Error parsing Lando configuration: ' . json_last_error_msg() . '. Skipping Lando settings.', 'red' );
        return array();
    }

    // Show full Lando info if both debug and verbose flags are set
    /* if (has_cli_flag('--debug') && has_cli_flag('--verbose')) {
        colored_message("\n📋 Full Lando Info:", 'cyan');
        print_r($lando_info);
    } */

    colored_message( '✅ Found Lando configuration', 'green' );

    // Display database service information if available
    $db_services = array_filter($lando_info, function($service) {
        return isset($service['service']) &&
               (strpos(strtolower($service['service']), 'mysql') !== false ||
                strpos(strtolower($service['service']), 'mariadb') !== false ||
                strpos(strtolower($service['service']), 'database') !== false);
    });

    if (!empty($db_services)) {
        colored_message("\n📦 Database Services Found:", 'cyan');
        foreach ($db_services as $service) {
            $service_name = $service['service'] ?? 'unknown';
            $service_type = $service['type'] ?? 'unknown';
            colored_message("  • {$service_name} ({$service_type})", 'cyan');

            if (isset($service['creds']) && is_array($service['creds'])) {
                $creds = $service['creds'];
                colored_message("    Host: {$service['internal_connection']['host']}", 'cyan');
                colored_message("    Port: {$service['internal_connection']['port']}", 'cyan');
                colored_message("    Database: " . ($creds['database'] ?? 'not specified'), 'cyan');
                colored_message("    Username: " . ($creds['user'] ?? 'not specified'), 'cyan');
                colored_message("    Password: " . (isset($creds['password']) ? '********' : 'not specified'), 'cyan');
            } else {
                colored_message("    No credentials available for this service", 'yellow');
            }
            echo "\n";
        }
    } else {
        colored_message("⚠️ No database services found in Lando configuration", 'yellow');
    }

    return $lando_info;
}

/**
 * Parse Lando info JSON *
 * @return array|null Lando configuration or null if not in Lando environment
 */
function parse_lando_info(): ?array {

    $lando_info = getenv('LANDO_INFO');
    if (empty($lando_info)) {
        return null;
    }

    $lando_data = json_decode($lando_info, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        colored_message( 'Warning: Failed to parse LANDO_INFO JSON: ' . json_last_error_msg(), 'yellow' );
        return null;
    }

    return $lando_data;
}



/**
 * Checks if a flag or one of its aliases exists in the command-line arguments.
 *
 * This function is case-sensitive. It checks for the presence of simple flags
 * like `--verbose` or `-h`.
 *
 * @param string|array $flags       A single flag (e.g., '--help') or an array of aliases (e.g., ['--help', '-h']).
 * @param array|null   $source_argv Optional source array of arguments. Defaults to the global $argv.
 * @return bool True if the flag or any of its aliases are found, false otherwise.
 */
function has_cli_flag(string|array $flags, ?array $source_argv = null): bool {
    // First try the provided source_argv
    // Then try global $argv if it exists
    // Then try $GLOBALS['argv'] if it exists
    // Finally fall back to empty array
    if ($source_argv !== null) {
        $argv = $source_argv;
    } elseif (isset($GLOBALS['argv'])) {
        $argv = $GLOBALS['argv'];
    } else {
        // If we can't get argv, check environment variables as a fallback
        // for common verbosity flags
        if (in_array('--verbose', (array)$flags, true) &&
            ((bool)getenv('VERBOSE') || (bool)getenv('DEBUG'))) {
            return true;
        }
        $argv = [];
    }

    $flags = (array) $flags;

    foreach ($argv as $arg) {
        if (in_array($arg, $flags, true)) {
            return true;
        }
    }

    return false;
}

/* === DONE with Helper functions === */

/**
 * Set up the test database and user with proper permissions
 *
 * In Lando environments: Uses database credentials from Lando configuration
 * In non-Lando environments: Uses provided credentials and may prompt for root password
 * But, this likely *will not* be creating the WordPress default database, specified in $db_name
 *
 * @param array $connection_settings {
 *     Database connection settings
 *
 *     @type string $db_name    Database name
 *     @type string $db_user    Database username (ignored in Lando)
 *     @type string $db_pass    Database password (ignored in Lando)
 *     @type string $db_root_pass Optional. Root password for non-Lando environments
 * }
 * @return array Standardized response array with success/error information
 */
function mysqli_create_database(array $connection_settings): array {
    // Validate database name to prevent SQL injection
    if (!empty($connection_settings['db_name']) && !is_valid_identifier($connection_settings['db_name'])) {
        return create_db_response(
            success: false,
            error: "Invalid database name: " . htmlspecialchars($connection_settings['db_name'], ENT_QUOTES, 'UTF-8'),
            error_code: 'invalid_db_name',
            data: [],
            meta: ['validation_error' => true]
        );
    }

    // Determine if we're in a Lando environment
    $is_lando = is_lando_environment();

    // Get root credentials
    $root_user = 'root';
    $root_pass = '';

    if ($is_lando) {
        // Get database credentials from Lando
        $lando_info = get_lando_info();

        // Debug: Show the structure of lando_info
        if (has_cli_flag(['--debug'])) {
            colored_message("\n🔍 Debug: Lando Info Structure:", 'blue');
            colored_message(print_r($lando_info, true), 'blue');
        }

        // Find the first database service with credentials
        $db_creds = null;
        foreach ((array)$lando_info as $service) {
            if (isset($service['creds']) && is_array($service['creds'])) {
                $db_creds = $service['creds'];
                break;
            }
        }

        if (empty($db_creds)) {
            return create_db_response(
                success: false,
                error: 'Could not find database credentials in Lando configuration',
                error_code: 'lando_config_error',
                data: [],
                meta: ['lando_error' => true]
            );
        }

        // Use root user with password from MYSQL_ROOT_PASSWORD setting (empty by default in Lando)
        $root_user = 'root';
        $root_pass = get_setting('MYSQL_ROOT_PASSWORD', '');

    } else {
        // For non-Lando, prompt for root password if not provided
        if (empty($connection_settings['db_root_pass'])) {
            echo "Enter MySQL root password (leave empty if no password): ";
            $root_pass = trim(fgets(STDIN));
        } else {
            $root_pass = $connection_settings['db_root_pass'];
        }
    }

    // In Lando, use root user to create database and grant privileges
    if ($is_lando) {
        // Combine all SQL statements into one query for atomic execution
        $sql = sprintf(
            "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n" .
            "GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%';\n" .
            "FLUSH PRIVILEGES;",
            $connection_settings['db_name'],
            $connection_settings['db_name'],
            $connection_settings['db_user']
        );

        // Execute as root user
        $result = execute_mysqli_query(
            sql: $sql,
            user: $root_user,
            pass: $root_pass,
            host: $connection_settings['db_host'],
            db_name: 'none'
        );

        if (!$result['success']) {
            return create_db_response(
                success: false,
                error: "Failed to set up test database in Lando: " . ($result['error'] ?? 'Unknown error'),
                error_code: 'setup_failed',
                data: [],
                meta: ['setup_error' => true]
            );
        }

        return create_db_response(
            success: true,
            data: [],
            error: null,
            error_code: null,
            meta: ['message' => sprintf("Successfully created test database '%s' in Lando", $connection_settings['db_name'])]
        );
    }

    // For non-Lando environments, proceed with the original setup
    $create_db_sql = sprintf(
        "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
        $connection_settings['db_name']
    );

    $create_user_sql = sprintf(
        "CREATE USER IF NOT EXISTS '%s'@'%%' IDENTIFIED BY '%s';",
        $connection_settings['db_user'],
        $connection_settings['db_pass']
    );

    $grant_sql = sprintf(
        "GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%';",
        $connection_settings['db_name'],
        $connection_settings['db_user']
    );

    $flush_sql = "FLUSH PRIVILEGES;";

    try {
        // Execute as root user
        $result = execute_mysqli_query(
            sql: "$create_db_sql\n$create_user_sql\n$grant_sql\n$flush_sql",
            user: $root_user,
            pass: $root_pass,
            host: $connection_settings['db_host'],
            db_name: 'none'
        );

        if (!$result['success']) {
            return create_db_response(
                success: false,
                error: "Failed to set up test database: " . ($result['error'] ?? 'Unknown error'),
                error_code: 'setup_failed',
                data: [],
                meta: ['setup_error' => true]
            );
        }

        return create_db_response(
            success: true,
            data: [],
            error: null,
            error_code: null,
            meta: ['message' => "mysqli_create_database - Test database and user set up successfully"]
        );

    } catch (\Exception $e) {
        return create_db_response(
            success: false,
            error: "Error setting up test environment: " . $e->getMessage(),
            error_code: 'setup_error',
            data: [],
            meta: ['exception' => true]
        );
    }
}

/**
 * Create a MySQL user with specified privileges
 *
 * This function creates a new MySQL user and optionally grants privileges on a specific database.
 * It handles both Lando and non-Lando environments and uses root credentials for user management.
 *
 * @since 1.0.0
 *
 * @param array $user_settings {
 *     User and database connection settings
 *
 *     @type string $username      The username to create (required)
 *     @type string $password      The password for the new user (required)
 *     @type string $database      Optional. Database to grant privileges on (default: none)
 *     @type string $host          Optional. Host for the user (default: '%' for any host)
 *     @type string $privileges    Optional. Privileges to grant (default: 'ALL PRIVILEGES')
 *                                 Common privileges include:
 *                                 - ALL PRIVILEGES: Full access to the database
 *                                 - SELECT: Read data from tables
 *                                 - INSERT: Add new rows to tables
 *                                 - UPDATE: Modify existing rows
 *                                 - DELETE: Remove rows from tables
 *                                 - CREATE: Create new tables/databases
 *                                 - DROP: Delete tables/databases
 *                                 - ALTER: Modify tables
 *                                 - INDEX: Create/drop indexes
 *                                 - CREATE TEMPORARY TABLES: Create temporary tables
 *                                 - LOCK TABLES: Lock tables
 *                                 - EXECUTE: Execute stored procedures
 *                                 - CREATE VIEW: Create views
 *                                 - SHOW VIEW: View view definitions
 *                                 - CREATE ROUTINE: Create stored procedures
 *                                 - ALTER ROUTINE: Modify stored procedures
 *                                 - EVENT: Create/alter events
 *                                 - TRIGGER: Create/alter triggers
 *     @type string $db_host       Optional. Database host (default: 'localhost')
 *     @type string $db_root_user  Optional. Root username (default: 'root')
 *     @type string $db_root_pass  Optional. Root password (default: '')
 * }
 * @return array Standardized response array with success/error information
 */
function mysqli_create_user(array $user_settings): array {
    // Required parameters
    $required = ['username', 'password'];
    foreach ($required as $param) {
        if (empty($user_settings[$param])) {
            return create_db_response(
                success: false,
                error: "Missing required parameter: $param",
                error_code: 'missing_parameter',
                data: [],
                meta: ['validation_error' => true]
            );
        }
    }

    // Set defaults
    $username = $user_settings['username'];
    $password = $user_settings['password'];
    $database = $user_settings['database'] ?? null;
    $host = $user_settings['host'] ?? '%';
    $privileges = $user_settings['privileges'] ?? 'ALL PRIVILEGES';
    $db_host = $user_settings['db_host'] ?? 'localhost';
    $root_user = $user_settings['db_root_user'] ?? 'root';
    $root_pass = $user_settings['db_root_pass'] ?? '';

    // Validate username and database name
    if (!is_valid_identifier($username)) {
        return create_db_response(
            success: false,
            error: "Invalid username: " . htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            error_code: 'invalid_username',
            data: [],
            meta: ['validation_error' => true]
        );
    }

    if ($database !== null && !is_valid_identifier($database)) {
        return create_db_response(
            success: false,
            error: "Invalid database name: " . htmlspecialchars($database, ENT_QUOTES, 'UTF-8'),
            error_code: 'invalid_database',
            data: [],
            meta: ['validation_error' => true]
        );
    }

    // In Lando, get root credentials from environment
    if (is_lando_environment()) {
        $root_user = 'root';
        $root_pass = get_setting('MYSQL_ROOT_PASSWORD', '');
    }

    // Build SQL commands
    $commands = [];

    // Create user with password
    $create_user_sql = sprintf(
        "CREATE USER IF NOT EXISTS '%s'@'%s' IDENTIFIED BY '%s';",
        $username,
        $host,
        $password
    );
    $commands[] = $create_user_sql;

    // Grant privileges if database is specified
    if ($database !== null) {
        $grant_sql = sprintf(
            "GRANT %s ON `%s`.* TO '%s'@'%s';",
            $privileges,
            $database,
            $username,
            $host
        );
        $commands[] = $grant_sql;
    }

    // Flush privileges
    $commands[] = "FLUSH PRIVILEGES;";

    // Execute commands as root
    $result = execute_mysqli_query(
        sql: implode("\n", $commands),
        user: $root_user,
        pass: $root_pass,
        host: $db_host,
        db_name: 'none'
    );

    if (!$result['success']) {
        return create_db_response(
            success: false,
            error: "Failed to create user: " . ($result['error'] ?? 'Unknown error'),
            error_code: 'user_creation_failed',
            data: [],
            meta: ['error_details' => $result]
        );
    }

    return create_db_response(
        success: true,
        data: [],
        error: null,
        error_code: null,
        meta: ['message' => sprintf("Successfully created user '%s'%s",
            $username,
            $database ? " with privileges on database '$database'" : ""
        )]
    );
}

// Load settings from .env.testing
// This file should be in plugin_root/bin
// Check for --env-testing parameter first (highest priority)
$env_file = null;
if (has_cli_flag(['--env-testing'])) {
    $env_file = get_cli_value('--env-testing');
    if ($env_file && file_exists($env_file)) {
        colored_message("Using .env.testing from command line parameter: $env_file\n", 'green');
    } else {
        colored_message("Warning: Specified .env.testing file not found: $env_file\n", 'yellow');
        $env_file = null;
    }
}

// If no valid file from command line, try current working directory first
if (!$env_file) {
    $cwd = getcwd();
    if (file_exists($cwd . '/tests/.env.testing')) {
        $env_file = $cwd . '/tests/.env.testing';
        colored_message("Using .env.testing from current working directory: $env_file\n", 'blue');
    }
    // Then try the script directory
    else if (file_exists(__DIR__ . '/../tests/.env.testing')) {
        $env_file = __DIR__ . '/../tests/.env.testing';
        colored_message("Using .env.testing from script's parent directory: $env_file\n", 'blue');
    }
    // Finally try one level up from script directory
    else {
        $settings_file = dirname(__DIR__);
        if (file_exists($settings_file . '/tests/.env.testing')) {
            $env_file = $settings_file . '/tests/.env.testing';
            colored_message("Using .env.testing from framework directory: $env_file\n", 'blue');
        } else {
            colored_message("Warning: Could not find .env.testing file in any expected location\n", 'yellow');
        }
    }
}

// Load the settings
$env_settings = $env_file ? load_settings_file($env_file) : [];
$GLOBALS['loaded_settings'] = $env_settings;


// Get database settings using get_database_settings function
$wp_root = get_setting('FILESYSTEM_WP_ROOT');
if (empty($wp_root) || !is_dir($wp_root)) {
    die("Error: FILESYSTEM_WP_ROOT is not properly set or is not a valid directory\n");
}

$wp_config_path = rtrim($wp_root, '/') . '/wp-config.php';
if (!file_exists($wp_config_path)) {
    die("Error: wp-config.php not found at: $wp_config_path\n");
}

// Now get database settings
$db_settings = get_database_settings($wp_config_path);

$is_lando = is_lando_environment();

// Environment detection - copied from framework-functions.php
function is_lando_environment(): bool {
    /*  Check if LANDO_INFO environment variable is set;
    is only set if are running in a Lando environment */
    if (!empty(getenv('LANDO_INFO'))) {
        return true;
    }

    // Check if lando command exists and is running
    $lando_exists = shell_exec('which lando 2>/dev/null');
    if (!empty($lando_exists)) {
        // Quick check if any lando containers are running
        $lando_list = shell_exec('lando list --format=json 2>/dev/null');
        if (!empty($lando_list)) {
            $list_data = json_decode($lando_list, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($list_data)) {
                foreach ($list_data as $container) {
                    if (isset($container['running']) && $container['running'] === true) {
                        return true;
                    }
                }
            }
        }
    }

    // Check if SSH_COMMAND is set to use lando but Lando isn't running
    $ssh_cmd = get_setting('SSH_COMMAND', '');
    if (strpos($ssh_cmd, 'lando ssh') === 0) {
        colored_message("Error: Lando is not running. Please start Lando with 'lando start'", 'red');
        exit(1);
    }

    return false;
}

/**
 * Validate MySQL identifier (database, table, or column name)
 *
 * @param string $name The identifier to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_identifier(string $name): bool {
    // MySQL allows letters, numbers, underscore, and dollar sign
    // Must start with a letter or underscore
    // Length between 1 and 64 characters
    return (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_$]{0,63}$/', $name);
}

/**
 * Format SSH command properly based on the SSH_COMMAND setting
 *
 * @param string $ssh_command The SSH command to use
 * @param string $command The command to execute via SSH
 * @return string The properly formatted command
 */
function format_ssh_command(string $ssh_command, string $command): string {
    if (empty($ssh_command) || $ssh_command === 'none') {
        return $command;
    }

    // For Lando commands, ensure the command is properly formatted with -c
    if (strpos($ssh_command, 'lando') !== false) {
        // If the command already has -c, don't add it again
        if (strpos($command, '-c ') !== 0) {
            $command = "-c \"$command\"";
        }
        return "$ssh_command $command";
    }

    // For regular SSH commands, just wrap the command in quotes
    return "$ssh_command \"$command\"";
}

// Output colored message to console
function colored_message(string $message, string $color = 'normal'): void {
    $colors = [
        'green'     => COLOR_GREEN,
        'yellow'    => COLOR_YELLOW,
        'red'       => COLOR_RED,
        'normal'    => COLOR_RESET,
    ];

    $color = strtolower($color);
    $start_color = $colors[$color] ?? $colors['normal'];
    echo $start_color . $message . COLOR_RESET . "\n";
}

/**
 * Format MySQL parameters and SQL query (without the mysql executable)
 *
 * This function formats MySQL command parameters and SQL query, but does NOT include
 * the actual 'mysql' or 'lando mysql' executable in the returned string. It only handles
 * the parameters and SQL escaping. The actual MySQL executable is added by the
 * format_mysql_execution() function.
 *
 * @param string      $host         Database host
 * @param string      $user         Database user
 * @param string      $pass         Database password
 * @param string      $sql          SQL command to execute
 * @param string|null $db           Optional database name to use
 * @param string      $command_type The type of command ('lando_direct', 'ssh', or 'direct')
 * @return string Formatted MySQL parameters and SQL command
 */
function format_mysql_parameters_and_query(string $host, string $user, string $pass, string $sql, ?string $db = null, string $command_type = 'direct'): string {
    // Start building the parameters
    $params = [];

    // Add host if not empty
    if (!empty($host)) {
        $params[] = "-h" . escapeshellarg($host);
    }

    // Add user if not empty
    if (!empty($user)) {
        $params[] = "-u" . escapeshellarg($user);
    }

    // Add password if not empty (note: this is still not secure, but better than nothing)
    if (!empty($pass)) {
        $params[] = "-p" . escapeshellarg($pass);
    }

    // Add database if provided
    if (!empty($db)) {
        $params[] = $db;
    }

    // Handle different command types
    if ($command_type === 'lando_direct') {
        // For Lando, we'll execute the SQL directly with -e
        $params[] = "-e " . escapeshellarg($sql);
    } else {
        // For other types, we'll pass the SQL through the command line
        $params[] = "-e " . escapeshellarg($sql);
    }

    return implode(' ', $params);
}

/**
 * Format and execute a MySQL command using the appropriate method (direct, SSH, or Lando)
 *
 * @deprecated 1.0.0 Use execute_mysqli_query() instead.
 * This function only returns the command string and doesn't provide proper error handling.
 * The mysqli-based functions provide better error reporting and result handling.
 *
 * @param string      $ssh_command The SSH command to use (or 'none' for direct)
 * @param string      $host Database host
 * @param string      $user Database user
 * @param string      $pass Database password
 * @param string      $sql SQL command to execute
 * @param string|null $db Optional database name to use
 * @return string The fully formatted command ready to execute
 * @throws \Exception If the command type is invalid.
 */
function format_mysql_execution(string $ssh_command, string $host, string $user, string $pass, string $sql, ?string $db = null): string {
    // Determine the command type based on the SSH command
    if (strpos($ssh_command, 'lando ssh') === 0) {
        $command_type = 'lando_direct';
    } elseif (empty($ssh_command) || $ssh_command === 'none') {
        $command_type = 'direct';
    } else {
        $command_type = 'ssh';
    }

    // Format the MySQL parameters with the appropriate command type
    $mysql_params = format_mysql_parameters_and_query($host, $user, $pass, $sql, $db, $command_type);

    $cmd = '';

    // Check if this is a Lando environment and we should use lando mysql directly
    if ($command_type === 'lando_direct') {
        // Use lando mysql directly with the parameters
        $cmd = "lando mysql $mysql_params";
    }
    // Use SSH to execute MySQL
    elseif ($command_type === 'ssh') {
        // Use the SSH command function for other SSH commands
        $cmd = format_ssh_command($ssh_command, "mysql $mysql_params");
    }
    // Direct MySQL execution (no SSH)
    else {
        // For direct MySQL commands, use the original format
        $cmd = "mysql $mysql_params";
    }

    return $cmd;
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
 * @param array  $lando_info Lando environment configuration, obtained by executing 'lando info' command
 * @param string $config_file_name Name of the configuration file (default: '.env.testing')
 * @return array Database settings with keys: db_host, db_user, db_pass, db_name, table_prefix
 * @throws \Exception If wp-config.php doesn't exist or if any required database settings are missing.
 */
function get_database_settings(
    string $wp_config_path,
    array $lando_info = array(),
    string $config_file_name = '.env.testing'
): array {
    // Initialize with not set values
    $db_settings = array(
        'db_host' => '[not set]',
        'db_user' => '[not set]',
        'db_pass' => '[not set]',
        'db_name' => '[not set]',
        'table_prefix' => 'wp_', // Default WordPress table prefix
    );

    // 1. Load from wp-config.php (lowest priority)
    if (file_exists($wp_config_path)) {
        colored_message("Reading database settings from $wp_config_path");

        $temp_config_path = tempnam(sys_get_temp_dir(), 'wp_config_');
        $config_content = file_get_contents($wp_config_path);
        if ($config_content === false) {
            throw new \Exception("Could not read wp-config.php at $wp_config_path");
        }

        // Extract database constants
        $db_constants = [];
        if (preg_match_all(
            "/define\s*\(\s*['\"](DB_NAME|DB_USER|DB_PASSWORD|DB_HOST)['\"].*?;/",
            $config_content,
            $constant_matches
        )) {
            $db_constants = $constant_matches[0];
        }

        // Extract table prefix
        $table_prefix_matches = [];
        $table_prefix_found = false;

        // First, try to find the line with table_prefix
        $lines = explode("\n", $config_content);
        $table_prefix_line = null;

        foreach ($lines as $line) {
            if (strpos($line, '$table_prefix') !== false) {
                $table_prefix_line = trim($line);
                break;
            }
        }

        if ($table_prefix_line !== null) {
            // Debug output
            if (has_cli_flag(['--debug', '-d'])) {
                echo "\n🔍 Found table_prefix line: " . $table_prefix_line . "\n";
            }

            // Extract the value using a simple regex
            if (preg_match("/['\"]([^'\"]*)['\"]\s*;/", $table_prefix_line, $matches)) {
                $db_settings['table_prefix'] = $matches[1];
                $table_prefix_matches = [$table_prefix_line, $matches[1]]; // Simulate full match array
                $db_constants[] = $table_prefix_line;
                $table_prefix_found = true;

                if (has_cli_flag(['--debug', '-d'])) {
                    echo "✅ Extracted table prefix: " . $matches[1] . "\n";
                    echo "\n🔍 DEBUG: Table prefix matches:\n";
                    echo "- Full match: " . $table_prefix_line . "\n";
                    echo "- Prefix value: " . $matches[1] . "\n\n";
                }
            } elseif (has_cli_flag(['--debug', '-d'])) {
                echo "⚠️ Could not extract table prefix from line: " . $table_prefix_line . "\n";
            }
        } elseif (has_cli_flag(['--debug', '-d'])) {
            echo "⚠️ Could not find table_prefix line in wp-config.php\n";
        }

        if (!empty($db_constants)) {
            $temp_content = "<?php\n" . implode("\n", $db_constants);

            // Debug output if --debug or -d flag is set
            if (has_cli_flag(['--debug', '-d'])) {
                echo "\n🔍 DEBUG: Extracted database configuration from wp-config.php file:\n";
                echo str_repeat("-", 80) . "\n";
                echo $temp_content . "\n";
                echo str_repeat("-", 80) . "\n\n";
            }

            file_put_contents($temp_config_path, $temp_content);

            try {
                // Include the temporary, sanitized config file
                @include $temp_config_path;

                // Get the database settings from the constants
                if (defined('DB_NAME')) {
                    $db_settings['db_name'] = DB_NAME;
                }
                if (defined('DB_USER')) {
                    $db_settings['db_user'] = DB_USER;
                }
                if (defined('DB_PASSWORD')) {
                    $db_settings['db_pass'] = DB_PASSWORD;
                }
                if (defined('DB_HOST')) {
                    $db_settings['db_host'] = DB_HOST;
                }

                // Get the table prefix from the global variable
                if (isset($table_prefix)) {
                    $db_settings['table_prefix'] = $table_prefix;
                }

            } catch (\Exception $e) {
                colored_message("Warning: Error including temporary config file: {$e->getMessage()}", 'yellow');
            } finally {
                // Clean up the temporary file
                unlink($temp_config_path);
            }
        } else {
             colored_message("Warning: Could not find DB settings in $wp_config_path . Check wp-config.php format.", 'yellow');
        }
    }

    // 2. Load from config file (e.g., .env, .env.testing)
    // Use DB_* for main database settings, not WP_TESTS_*
    $env_file_db_host = get_setting('DB_HOST', null);
    $env_file_db_user = get_setting('DB_USER', null);
    $env_file_db_pass = get_setting('DB_PASSWORD', null);
    $env_file_db_name = get_setting('DB_NAME', null);

    if ($env_file_db_host) {
		$db_settings['db_host'] = $env_file_db_host;
    }
    if ($env_file_db_user) {
		$db_settings['db_user'] = $env_file_db_user;
    }
    if ($env_file_db_pass !== null) {
		$db_settings['db_pass'] = $env_file_db_pass; // Password can be empty
    }
    if ($env_file_db_name) {
		$db_settings['db_name'] = $env_file_db_name;
    }
    // Note: table_prefix is only read from wp-config.php and not from environment variables or config files

    // 3. Load from environment variables
    // Use DB_* for main database settings, not WP_TESTS_*
    $env_var_db_host = getenv('DB_HOST');
    $env_var_db_user = getenv('DB_USER');
    $env_var_db_pass = getenv('DB_PASSWORD');
    $env_var_db_name = getenv('DB_NAME');

    if ($env_var_db_host !== false && $env_var_db_host) {
		$db_settings['db_host'] = $env_var_db_host;
    }
    if ($env_var_db_user !== false && $env_var_db_user) {
		$db_settings['db_user'] = $env_var_db_user;
    }
    if ($env_var_db_pass !== false) {
		$db_settings['db_pass'] = $env_var_db_pass; // Password can be empty
    }
    if ($env_var_db_name !== false && $env_var_db_name) {
		$db_settings['db_name'] = $env_var_db_name;
    }

    // Note: table_prefix is only read from wp-config.php and not from environment variables

    // 4. Load from Lando configuration (highest priority)
    if (!empty($lando_info)) {
        colored_message('Getting Lando internal configuration...');

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

            colored_message("Found Lando database service: {$db_settings['db_host']}");
            // Note: table_prefix is only read from wp-config.php and not from Lando configuration
        } else {
            colored_message('Warning: No MySQL service found in Lando configuration.', 'yellow');
            colored_message('This indicates a potential issue with your Lando setup.');
        }
    }

    // Check if we have all required settings
    $missing_settings = array();
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
    // echo esc_cli("WordPress Database settings:\n");
    // echo esc_cli("- Host: {$db_settings['db_host']}\n");
    // echo esc_cli("- User: {$db_settings['db_user']}\n");
    // echo esc_cli("- Database: {$db_settings['db_name']}\n");
    // echo esc_cli('- Password length: ' . strlen($db_settings['db_pass']) . "\n");

    return $db_settings;
}

// Helper to format and display SQL query result data
function display_sql_data($data) {
    if (empty($data)) {
        echo "No SQL data returned\n";
        return;
    }

    foreach ($data as $row) {
        foreach ($row as $key => $value) {
            echo sprintf("%s: %s\n", $key, $value);
        }
        echo "\n";
    }
}


/**
 * Format and display SQL query result data in a human-readable way
 *
 * @param array $result The result array from execute_mysqli_query()
 */
function display_sql_result(array $result, ?int $query_id = null): void {
    // Get query ID from parameter or result array
    $query_id = $query_id ?? ($result['query_id'] ?? null);
    $query_id_display = $query_id ? "[Q-{$query_id}] " : "";
    // Clear visual separator between query and result with query ID
    echo "\n" . str_repeat('▼', 40) . " QUERY RESULT {$query_id_display}" . str_repeat('▼', 40) . "\n";

    if (empty($result['success'])) {
        colored_message("{$query_id_display}❌ ERROR: " . ($result['error'] ?? 'Unknown error'), 'red');
        if (!empty($result['error_code'])) {
            colored_message("Error code: " . $result['error_code'], 'red');
        }
        return;
    }

    $meta = $result['meta'] ?? [];
    $affected_rows = $meta['affected_rows'] ?? -1;
    $num_rows = $meta['num_rows'] ?? 0;
    $data = $result['data'] ?? [];

    // Display statement-specific information if available
    if (!empty($meta['statements'])) {
        colored_message("📋 Statement Summary:", 'cyan');
        foreach ($meta['statements'] as $idx => $stmt) {
            $stmt_num = $stmt['index'];
            $status = $stmt['error'] ? '❌' : '✅';
            $message = "Statement #{$stmt_num}: {$status} ";

            // Check if there was a MySQL error but don't display it yet
            // We'll return it with the result so it can be displayed with the test results
            if (isset($result['error']) && !empty($result['error'])) {
                $success = false;
            }

            if ($stmt['error']) {
                colored_message($message . "Error: {$stmt['error']}", 'red');
            } else {
                $message .= "Success";
                if ($stmt['affected_rows'] > 0) {
                    $message .= " (Affected rows: {$stmt['affected_rows']})";
                }
                colored_message($message, 'green');
            }
        }
        echo "\n";
    }

    // For INSERT/UPDATE/DELETE queries
    if ($affected_rows > 0) {
        echo "✅ Rows affected: $affected_rows\n";
    }

    // For SELECT queries
    if ($num_rows > 0) {
        $display_count = min(10, $num_rows);
        echo "{$query_id_display}📊 Rows returned: $num_rows\n\n";

        if ($num_rows === 1) {
            // Single row result - show as key: value pairs
            $row = $data[0];
            foreach ($row as $key => $value) {
                echo "$key: $value\n";
            }
        } else {
            // Multiple rows - show as a table
            $headers = array_keys($data[0]);

            // Calculate column widths
            $col_widths = [];
            foreach ($headers as $header) {
                $col_widths[$header] = strlen($header);
                foreach ($data as $row) {
                    $col_widths[$header] = max($col_widths[$header], strlen((string)($row[$header] ?? '')));
                }
                $col_widths[$header] = min($col_widths[$header], 30); // Cap width
            }

            // Print header
            foreach ($headers as $header) {
                echo str_pad($header, $col_widths[$header] + 2);
            }
            echo "\n" . str_repeat('-', array_sum($col_widths) + (count($headers) * 2)) . "\n";

            // Print rows (up to 10)
            $count = 0;
            foreach ($data as $row) {
                if ($count++ >= 10) break;
                foreach ($headers as $header) {
                    $value = $row[$header] ?? '';
                    if (strlen($value) > 30) {
                        $value = substr($value, 0, 27) . '...';
                    }
                    echo str_pad($value, $col_widths[$header] + 2);
                }
                echo "\n";
            }

            if ($num_rows > 10) {
                echo "\n... and " . ($num_rows - 10) . " more rows\n";
            }
        }
    } elseif ($num_rows === 0) {
        echo "{$query_id_display}ℹ️  No rows returned\n";
    }
}

/**
 * Clean up test environment
 *
 * @return array Array of operation results with 'success' and 'message' for each
 */
function cleanup_test_environment() {
    $meta = [
        'operations' => [],
        'tables_dropped' => 0,
        'database_dropped' => false,
        'user_dropped' => false
    ];

    // Cleanup commands to execute in order
    $cleanup_commands = [
        'drop_tables' => [
            'sql' => "SELECT CONCAT('DROP TABLE IF EXISTS `', table_schema, '`.`', table_name, '`;')
                      FROM information_schema.tables
                      WHERE table_schema = 'wordpress_test' AND
                            table_name IN ('test_table', 'special_chars', 'test_operations', 'special_chars')",
            'description' => 'Drop test tables in wordpress_test database'
        ],
        'drop_all_remaining_tables' => [
            'sql' => "SELECT CONCAT('DROP TABLE IF EXISTS `', table_schema, '`.`', table_name, '`;')
                      FROM information_schema.tables
                      WHERE table_schema = 'wordpress_test'",
            'description' => 'Drop any remaining tables in wordpress_test database'
        ],
        'drop_database' => [
            'sql' => "DROP DATABASE IF EXISTS wordpress_test",
            'description' => 'Drop wordpress_test database',
            'ignore_errors' => true  // Ignore if database doesn't exist
        ],
        'drop_user' => [
            'sql' => "DROP USER IF EXISTS 'wordpress_test'@'%'",
            'description' => 'Drop wordpress_test user',
            'ignore_errors' => true  // Ignore if user doesn't exist
        ]
    ];

    try {
        // Process each cleanup command
        foreach ($cleanup_commands as $operation => $command) {
            $result = ['operation' => $operation, 'description' => $command['description']];

            try {
                if ($operation === 'drop_tables') {
                    // Special handling for dropping tables
                    $tables_result = execute_mysqli_query(sql: $command['sql'], db_name: 'none');

                    if ($tables_result['success'] && !empty($tables_result['data'])) {
                        $tables_dropped = 0;
                        $errors = [];

                        foreach ($tables_result['data'] as $row) {
                            $drop_sql = reset($row);
                            $drop_result = execute_mysqli_query(sql: $drop_sql, db_name: 'none');

                            if ($drop_result['success']) {
                                $tables_dropped++;
                            } else {
                                $errors[] = $drop_result['error'] ?? 'Unknown error dropping table';
                            }
                        }

                        $meta['tables_dropped'] = $tables_dropped;
                        $result['tables_dropped'] = $tables_dropped;

                        if (!empty($errors)) {
                            throw new \RuntimeException(implode("\n", $errors));
                        }

                        $result['success'] = true;
                        $result['message'] = "Dropped {$tables_dropped} tables";
                    } else {
                        $result['success'] = true;
                        $result['message'] = 'No tables to drop';
                    }
                } else {
                    // Standard query execution
                    $query_result = execute_mysqli_query(sql: $command['sql'], db_name: 'none');
                    $result['success'] = $query_result['success'];

                    if ($operation === 'drop_database' && $query_result['success']) {
                        $meta['database_dropped'] = true;
                    } elseif ($operation === 'drop_user' && $query_result['success']) {
                        $meta['user_dropped'] = true;
                    }

                    if ($query_result['success']) {
                        $result['message'] = 'Success';
                    } else {
                        // Only throw if we're not ignoring errors for this operation
                        if (empty($command['ignore_errors'])) {
                            throw new \RuntimeException($query_result['error'] ?? 'Query failed');
                        } else {
                            $result['message'] = 'Ignored error: ' . ($query_result['error'] ?? 'Unknown error');
                            $result['success'] = true; // Mark as success since we're ignoring the error
                        }
                    }
                }
            } catch (\Exception $e) {
                $result['success'] = false;
                $result['error'] = $e->getMessage();
            }

            $meta['operations'][] = $result;
        }

        // Verify all operations were successful
        $failed_operations = array_filter($meta['operations'], function($op) {
            return $op['success'] === false;
        });

        if (count($failed_operations) > 0) {
            return create_db_response(
                success: false,
                error: 'Some cleanup operations failed',
                error_code: 'CLEANUP_PARTIAL_FAILURE',
                data: null,
                meta: $meta
            );
        }

        return create_db_response(
            success: true,
            data: 'Cleanup completed successfully',
            error: null,
            error_code: null,
            meta: $meta
        );

    } catch (\Exception $e) {
        return create_db_response(
            success: false,
            error: 'Cleanup failed: ' . $e->getMessage(),
            error_code: 'CLEANUP_FAILED',
            data: null,
            meta: $meta
        );
    }
}

// Verify and clean up after each test
function verify_and_cleanup($test_name, $cleanup = false) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "\n" . str_repeat("▓", 80) . "\n";
    colored_message("VERIFICATION: $test_name", 'yellow');
    echo str_repeat("▓", 80) . "\n";

    // List test databases with details
    $db_check = execute_mysqli_query(sql: "SHOW DATABASES LIKE 'wordpress_test%'; ", db_name: 'none');
    echo "\n" . str_repeat("─", 80) . "\n";
    colored_message("DATABASE STATUS", 'cyan');
    echo str_repeat("─", 80) . "\n";

    if (!empty($db_check['data'])) {
        foreach ($db_check['data'] as $db) {
            $db_name = is_array($db) ? reset($db) : $db;
            // Get database size and table count
            $db_info = execute_mysqli_query(
                sql: "SELECT
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 4) as size_mb,
                    COUNT(*) as table_count
                    FROM information_schema.tables
                    WHERE table_schema = '" . $db_name . "';" ,
                db_name: 'none'
            );

            $size = !empty($db_info['data'][0]['size_mb']) ? $db_info['data'][0]['size_mb'] . ' MB' : 'N/A';
            $tables = !empty($db_info['data'][0]['table_count']) ? $db_info['data'][0]['table_count'] : 0;

            echo "- Database: ";
            colored_message($db_name, 'green');
            echo "\n  Tables: {$tables}\n  Size: {$size}\n";
        }
    } else {
        echo "- No test databases found\n";
    }

    // Check for test users with database-level permissions
    // The information_schema.SCHEMA_PRIVILEGES table requires proper case column names (User, Host, etc.) rather than all uppercase or lowercase.
    $users_check = execute_mysqli_query("
        SELECT
            User,
            Host,
            GROUP_CONCAT(DISTINCT CONCAT(
                PRIVILEGE_TYPE,
                ' ON ',
                TABLE_SCHEMA,
                IF(TABLE_NAME IS NOT NULL, CONCAT('.', TABLE_NAME), '')
            ) ORDER BY PRIVILEGE_TYPE SEPARATOR ', ') as privileges
        FROM information_schema.SCHEMA_PRIVILEGES
        WHERE User IN ('test_user', 'test_helper_user')
        GROUP BY User, Host", db_name: 'none');

    echo "\n";
    echo "\n" . str_repeat("─", 80) . "\n";
    colored_message("USER PERMISSIONS", 'cyan');
    echo str_repeat("─", 80) . "\n";
    echo str_repeat("-", 80) . "\n";

    if (!empty($users_check['data'])) {
        foreach ($users_check['data'] as $user) {
            $user_name = $user['USER'] ?? '';
            $host = $user['HOST'] ?? '';
            $privs = $user['privileges'] ?? 'No specific privileges';

            echo "- User: ";
            colored_message("$user_name@$host", 'green');
            echo "\n  Privileges: {$privs}\n";
        }
    } else {
        echo "- No test users found\n";
    }

    // Clean up test users if cleanup is enabled
    if ($cleanup) {
        echo "\n";
        echo "\n" . str_repeat("─", 80) . "\n";
        colored_message("CLEANING UP TEST USERS...", 'yellow');
        echo str_repeat("─", 80) . "\n";

        $users_to_drop = [
            ["'test_user'@'%'", "Test User"],
            ["'test_helper_user'@'%'", "Test Helper User"]
        ];

        foreach ($users_to_drop as [$user, $display_name]) {
            $result = execute_mysqli_query(
                sql: "DROP USER IF EXISTS $user",
                db_name: 'none',
                user: get_setting('DB_ROOT_USER', 'root'),
                pass: get_setting('DB_ROOT_PASSWORD', '')
            );

            if ($result['success']) {
                colored_message("✅ Dropped $display_name ($user)", 'green');
            } else {
                $error = $result['error'] ?? 'Unknown error';
                colored_message("❌ Failed to drop $display_name: $error", 'red');
            }
        }
    }
}

/**
 * Execute multiple SQL commands with the appropriate execution method
 *
 * @param string $sql_commands SQL commands to execute (can be multiple statements separated by semicolons)
 * @param string|null $database Optional database name to use (defaults to null = WordPress database)
 * @param bool $use_root Whether to use root credentials (defaults to false)
 * @return array Array of results for each command
 */
function execute_multi_sql($sql_commands, $database = null, $use_root = false) {
    global $db_settings;
    $results = [];
    $commands = array_filter(array_map('trim', explode(';', $sql_commands)));

    if (empty($commands)) {
        return [['success' => false, 'error' => 'No SQL commands provided']];
    }

    foreach ($commands as $command) {
        if (empty(trim($command))) continue;

        // Don't add semicolon if the command already ends with one
        $full_command = rtrim($command, ';');

        // Execute with appropriate credentials
        if ($use_root) {
            $root_user = get_setting('DB_ROOT_USER', 'root');
            $root_pass = get_setting('DB_ROOT_PASSWORD', '');
            $result = execute_mysqli_query(
                sql: $full_command,
                user: $root_user,
                pass: $root_pass,
                db_name: $database
            );
        } else {
            $result = execute_mysqli_query(sql: $full_command, db_name: $database);
        }

        $results[] = $result;

        // Only show command execution in debug mode, but don't show errors yet
        // Errors will be displayed with test results
        if (has_cli_flag(['--debug', '-d'])) {
            echo "\n[DEBUG] Executed: $full_command\n";
            echo "Status: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "----------------------------------------\n";
        }
    }

    return $results;
}

/**
 * Test basic MySQL server connectivity without requiring a database
 *
 * @param string $host MySQL host
 * @param string $user MySQL username
 * @param string $pass MySQL password
 * @param bool $quiet If true, suppresses output messages (default: false)
 * @return array {
 *     @type bool $success Whether the connection was successful
 *     @type array $connection Connection details
 *     @type string|null $error Error message if connection failed
 *     @type string|null $error_code Error code if connection failed
 *     @type array $version Version information if connection successful
 *     @type float $latency_ms Connection latency in milliseconds
 *     @type bool $validated Whether the connection was validated with a test query
 * }
 */
function test_mysql_connectivity($host, $user, $pass, $quiet = false) {
    $start_time = microtime(true);

    // Check if we're in a Lando environment
    if (is_lando_environment()) {
        // For Lando, use execute_mysqli_query which will route through execute_mysqli_lando
        $db_settings = [
            'db_host' => $host,
            'db_user' => $user,
            'db_pass' => $pass
        ];

        // Execute a simple test query via Lando
        $result = execute_mysqli_query('SELECT 1 AS test_value, VERSION() AS version', $user, $pass, $host, 'none');

        $latency = round((microtime(true) - $start_time) * 1000, 2);

        if (!$result['success']) {
            if (!$quiet) {
                colored_message("❌ Lando connection failed: {$result['error']}", 'red');
                colored_message("  Host: $host", 'yellow');
                colored_message("  User: $user\n", 'yellow');
            }

            return [
                'success' => false,
                'connection' => [
                    'host' => $host,
                    'user' => $user,
                    'latency_ms' => $latency,
                    'environment' => 'lando'
                ],
                'error' => $result['error'] ?? 'Connection failed',
                'error_code' => $result['error_code'] ?? 'CONNECTION_FAILED',
                'version' => null,
                'latency_ms' => $latency,
                'validated' => false
            ];
        }

        // Extract version from the result
        $version = [];
        if (!empty($result['data']) && isset($result['data'][0]['version'])) {
            $version['server'] = $result['data'][0]['version'];
        }
        if (empty($version['server'])) {
            $version['server'] = 'unknown';
        }

        if (!$quiet) {
            colored_message("✅ Lando connection successful to $host as $user", 'green');
            colored_message("  MySQL Server Version: " . $version['server'] , 'green');
            colored_message("  Latency: {$latency}ms\n", 'green');
        }

        return [
            'success' => true,
            'connection' => [
                'host' => $host,
                'user' => $user,
                'latency_ms' => $latency,
                'environment' => 'lando'
            ],
            'error' => null,
            'error_code' => null,
            'version' => $version,
            'latency_ms' => $latency,
            'validated' => true
        ];
    }

    // For non-Lando environments, use direct mysqli connection
    // First try a simple connection test
    $mysqli = @new \mysqli($host, $user, $pass);

    if ($mysqli->connect_error) {
        $error = $mysqli->connect_error;
        $error_code = $mysqli->connect_errno;
        $latency = round((microtime(true) - $start_time) * 1000, 2);

        if (!$quiet) {
            colored_message("❌ Connection failed: $error (Error #$error_code)", 'red');
            colored_message("  Host: $host", 'yellow');
            colored_message("  User: $user\n", 'yellow');
        }

        return [
            'success' => false,
            'connection' => [
                'host' => $host,
                'user' => $user,
                'latency_ms' => $latency
            ],
            'error' => $error,
            'error_code' => 'CONNECTION_FAILED',
            'version' => null,
            'latency_ms' => $latency,
            'validated' => false
        ];
    }

    // Connection successful, now validate with a test query
    $test_query = 'SELECT 1 AS test_value, VERSION() AS version';
    $result = $mysqli->query($test_query);
    $latency = round((microtime(true) - $start_time) * 1000, 2);

    if ($result === false) {
        $error = $mysqli->error;
        $error_code = $mysqli->errno;
        $mysqli->close();

        if (!$quiet) {
            colored_message("⚠️  Connection established but validation query failed: $error (Error #$error_code)", 'yellow');
            colored_message("  Host: $host", 'yellow');
            colored_message("  User: $user\n", 'yellow');
        }

        return [
            'success' => false,
            'connection' => [
                'host' => $host,
                'user' => $user,
                'latency_ms' => $latency
            ],
            'error' => $error,
            'error_code' => 'VALIDATION_QUERY_FAILED',
            'version' => null,
            'latency_ms' => $latency,
            'validated' => false
        ];
    }

    // Extract version from the result
    $version = [];
    $row = $result->fetch_assoc();
    $result->free();
    $mysqli->close();

    if (isset($row['version'])) {
        $version['server'] = $row['version'];
    }

    if (!$quiet) {
        colored_message("✅ Connection successful to $host as $user", 'green');
        colored_message("  MySQL Server Version: {$version['server']}", 'green');
        colored_message("  Latency: {$latency}ms\n", 'green');
    }

    return [
        'success' => true,
        'connection' => [
            'host' => $host,
            'user' => $user,
            'latency_ms' => $latency
        ],
        'error' => null,
        'error_code' => null,
        'version' => $version,
        'latency_ms' => $latency,
        'validated' => true
    ];
}



// Check database connectivity
function check_database_connection() {
    global $db_settings;

    // Determine if we're in a Lando environment
    $is_lando = is_lando_environment();
    $env_type = $is_lando ? 'Lando' : 'Direct';

    echo "\nTesting database connection ($env_type environment)...\n";

    // Test MySQL connectivity (is Lando-aware)
    $connectivity = test_mysql_connectivity(
        $db_settings['db_host'] ?? 'localhost',
        $db_settings['db_user'] ?? '',
        $db_settings['db_pass'] ?? ''
    );

    // Display connection info
    $conn = $connectivity['connection'];
    echo "Host: {$conn['host']}\n";
    echo "User: {$conn['user']}\n";
    if (isset($conn['environment'])) {
        echo "Environment: {$conn['environment']}\n";
    }

    // Display result
    if (!$connectivity['success']) {
        echo "❌ Connection failed: " . ($connectivity['error'] ?? "\n");
    }

    echo str_repeat("-", 50) . "\n";

    if (!$connectivity['success']) {
        colored_message("⚠️  Could not connect to MySQL server: " . ($connectivity['error'] ?? 'Unknown error'), 'yellow');
        colored_message("ℹ️  This script is designed to run in a Lando environment or with proper MySQL access.", 'blue');
        return false;
    }

    // If we get here, MySQL connectivity is good, now check databases
    $result = execute_mysqli_query(sql: "SHOW DATABASES;", db_name: 'none');

    // Show available databases
    // This is a good example of how to access query results directly
    // (Note: In normal operation, we would use display_sql_result() instead)
    /*
    $database_count = count($result['data'] ?? []);
    colored_message("\n📊 Available MySQL databases:", 'cyan');
    colored_message(str_repeat("-", 80), 'cyan');

    if ($database_count > 0) {
        foreach ($result['data'] as $db) {
            $db_name = $db['Database'] ?? 'unknown';
            colored_message("- $db_name", 'white');
        }
        colored_message(str_repeat("-", 80), 'cyan');
        $database_count = count($result['data'] ?? []);
        colored_message("Total databases: $database_count\n", 'cyan');
    }
    } else {
        colored_message("No databases found\n", 'yellow');
    }
    */

    $database_count = count($result['data'] ?? []);
    colored_message("Total databases: $database_count\n", 'cyan');
    display_sql_result($result);
    return true;
}

// Track whether environment info has been displayed
$environment_info_displayed = false;

/**
 * Display environment information once
 *
 * @param array $db_settings Database settings array
 * @return void
 */
function display_environment_info(array $db_settings): void {
    global $environment_info_displayed;

    // Only display environment info once
    if ($environment_info_displayed) {
        return;
    }

    // Display environment information header
    echo "\n" . str_repeat("═", 80) . "\n";
    colored_message("ENVIRONMENT INFORMATION", 'green');
    echo str_repeat("═", 80) . "\n";
    colored_message("Environment:    " . (is_lando_environment() ? 'Lando' : 'Local'), 'cyan');
    colored_message("Database Host:  " . ($db_settings['db_host'] ?? 'Not set'), 'cyan');
    colored_message("Database Name:  " . ($db_settings['db_name'] ?? 'Not set'), 'cyan');
    colored_message("Database User:  " . ($db_settings['db_user'] ?? 'Not set'), 'cyan');
    colored_message("Lando Detected: " . (is_lando_environment() ? 'Yes' : 'No'), 'cyan');

    // Display filesystem paths only once
    $filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT', '[not set]');
    $wp_root = get_setting('WP_ROOT', '[not set]');
    $folder_in_wordpress = get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');
    $plugin_slug = get_setting('YOUR_PLUGIN_SLUG', '[not set]');

    colored_message("Filesystem WP Root: " . $filesystem_wp_root, 'cyan');
    colored_message("WP Root:           " . $wp_root, 'cyan');
    colored_message("Plugin Location:   " . $folder_in_wordpress . '/' . $plugin_slug, 'cyan');
    colored_message(str_repeat("=", 80) . "\n", 'cyan');

    // Mark as displayed
    $environment_info_displayed = true;
}

/**
 * Generate temporary file paths for MySQL query execution
 *
 * @param string $temp_file_prefix Optional prefix for the temp file name
 * @return array Array containing all necessary file paths in a nested structure
 */
function get_temp_file_paths(string $temp_file_prefix = 'temp_mysql_exec_'): array {
    // Create a unique identifier for this test run
    $temp_file = $temp_file_prefix . uniqid();

    // Get filesystem and container paths from settings
    $filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT', '');
    $wp_root = get_setting('WP_ROOT', '');
    $folder_in_wordpress = get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');
    $plugin_slug = get_setting('YOUR_PLUGIN_SLUG', '');
    $test_framework_dir = get_setting('TEST_FRAMEWORK_DIR', 'gl-phpunit-test-framework');

    // Build paths for both filesystem and container
    $base_dir = $filesystem_wp_root . '/' . $folder_in_wordpress . '/' . $plugin_slug . '/tests/' . $test_framework_dir . '/bin';
    $container_base_dir = $wp_root . '/' . $folder_in_wordpress . '/' . $plugin_slug . '/tests/' . $test_framework_dir . '/bin';

    return [
        'filesystem' => [
            'php' => $base_dir . '/' . $temp_file . '.php',
            'output' => $base_dir . '/' . $temp_file . '.json',
            'error' => $base_dir . '/' . $temp_file . '.error',
            'base_dir' => $base_dir,
        ],
        'container' => [
            'output' => $container_base_dir . '/' . $temp_file . '.json',
            'error' => $container_base_dir . '/' . $temp_file . '.error',
            'base_dir' => $container_base_dir,
        ],
        'temp_file' => $temp_file,
    ];
}

// Main test runner
/**
 * Run a series of MySQL tests with improved reporting
 */
function run_mysql_tests() {
    global $db_settings;

    $test_results = [];

    // Display environment information using the centralized function
    display_environment_info($db_settings);

    // Check database connection first
    if (!check_database_connection()) {
        colored_message("❌ Database connection could not be established", 'red');
        colored_message("To run these tests, please ensure you're in a Lando environment or have MySQL properly configured.", 'yellow');
        return false;
    }

    // Set up test database using our new function
    echo "\n" . str_repeat("─", 80) . "\n";
    colored_message("SETTING UP TEST DATABASE 'wordpress_test'", 'cyan');
    echo str_repeat("─", 80) . "\n";
    $setup_result = setup_test_database('wordpress_test');

    if (!$setup_result['success']) {
        colored_message("❌ Failed to set up test database: " . ($setup_result['error'] ?? 'Unknown error'), 'red');
        return false;
    }

    colored_message("✅ run_mysql_tests Test database and user set up successfully\n", 'green');
    $result = execute_mysqli_query("SHOW DATABASES", db_name: 'none');

    if (has_cli_flag(['--debug', '-d']) && has_cli_flag(['--verbose', '-v'])) {
        colored_message("Full SHOW DATABASES result:\n");
        print_r($result);
    }

    // Show available databases
    $database_count = count($result['data'] ?? []);
    echo "\n" . str_repeat("─", 80) . "\n";
    colored_message("📊 AVAILABLE MYSQL DATABASES", 'cyan');
    echo str_repeat("─", 80) . "\n";
    if ($database_count > 0) {
        foreach ($result['data'] as $db) {
            $db_name = $db['Database'] ?? 'unknown';
            colored_message("- $db_name", 'white');
        }
        colored_message(str_repeat("-", 80), 'cyan');
        colored_message("Total databases: $database_count\n", 'cyan');
    } else {
        colored_message("No databases found\n", 'yellow');
    }


    // Define test cases for comprehensive MySQL testing
    $tests = [
        [
            'name' => '1. Basic Connectivity Test',
            'sql' => "SELECT 'Connection successful' AS status;",
            'expected' => true,
            'description' => 'Verifies basic connectivity to the MySQL server.'
        ],
        [
            'name' => '2. WordPress Database Access',
            'database' => null,  // Implicitly specify the WordPress database
            'sql' => "
                -- Verify we can access WordPress tables
                USE `{$db_settings['db_name']}`;
                SELECT COUNT(*) AS post_count FROM `{$db_settings['table_prefix']}posts`;
                SELECT option_name, option_value
                FROM `{$db_settings['table_prefix']}options`
                WHERE option_name = 'home' OR option_name = 'siteurl';
            ",
            'expected' => true,
            'description' => 'Tests reading from WordPress core tables.'

        ],
        [
            'name' => '2.5. Create Test User',
            'database' => 'none',  // Use the system database for user management
            'use_root' => true,
            'sql' => "
                -- Create a test user with minimal privileges
                CREATE USER IF NOT EXISTS 'test_user'@'%' IDENTIFIED BY 'test_password';
                GRANT ALL PRIVILEGES ON `wordpress_test`.* TO 'test_user'@'%';
                FLUSH PRIVILEGES;
            ",
            'expected' => true,
            'description' => 'Creates a test user with access to the test database.'
        ],
        [
            'name' => '2.6. Create Test User with mysqli_create_user',
            'type' => 'function',
            'function' => function() {
                global $db_settings;

                // Create a test user using the new helper function
                $result = mysqli_create_user([
                    'username' => 'test_helper_user',
                    'password' => 'test_helper_pass',
                    'database' => 'wordpress_test',
                    'db_host' => $db_settings['db_host'] ?? 'localhost',
                    'privileges' => 'SELECT, INSERT, UPDATE, DELETE',
                    'db_root_user' => get_setting('DB_ROOT_USER', 'root'),
                    'db_root_pass' => get_setting('DB_ROOT_PASSWORD', '')
                ]);

                if (!$result['success']) {
                    return [
                        'success' => false,
                        'error' => $result['error'] ?? 'Unknown error creating user',
                        'error_code' => $result['error_code'] ?? 'unknown_error'
                    ];
                }

                // Verify the user can connect
                $test_conn = test_mysql_connectivity(
                    $db_settings['db_host'] ?? 'localhost',
                    'test_helper_user',
                    'test_helper_pass',
                    'wordpress_test'
                );

                if (!$test_conn['success']) {
                    return [
                        'success' => false,
                        'error' => 'Failed to connect with new user: ' . ($test_conn['error'] ?? 'Unknown error'),
                        'error_code' => 'connection_failed'
                    ];
                }

                return ['success' => true];
            },
            'expected' => true,
            'description' => 'Demonstrates using the mysqli_create_user helper function to create a test user with specific privileges.'
        ],
        [
            'name' => '3. Test Database Operations',
            'database' => 'wordpress_test',
            'sql' => "
                -- Create a test database if it doesn't exist
                CREATE DATABASE IF NOT EXISTS `wordpress_test`;

                -- Create a test table without any prefix
                DROP TABLE IF EXISTS `test_operations`;
                CREATE TABLE `test_operations` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    operation_type VARCHAR(50) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                -- Test INSERT
                INSERT INTO `wordpress_test`.`test_operations` (name, email)
                VALUES ('Test User', 'test@example.com');

                -- Test SELECT
                SELECT * FROM `wordpress_test`.`test_operations` WHERE name = 'Test User';

                -- Test UPDATE
                UPDATE `wordpress_test`.`test_operations`
                SET email = 'updated@example.com'
                WHERE name = 'Test User';

                -- Verify UPDATE
                SELECT * FROM `wordpress_test`.`test_operations`
                WHERE email = 'updated@example.com';

                -- Test DELETE
                DELETE FROM `wordpress_test`.`test_operations`
                WHERE name = 'Test User';

                -- Verify DELETE
                SELECT COUNT(*) AS count FROM `wordpress_test`.`test_operations`
                WHERE name = 'Test User';
            ",
            'expected' => true,
            'description' => 'Tests CRUD operations in the test database.'
        ],
        [
            'name' => '4. Permission Denied Test',
            'sql' => "
                -- This should fail with permission denied
                DROP DATABASE IF EXISTS `wordpress_production`;
            ",
            'expected' => false,
            'expect_error' => true,
            'error_contains' => ['DROP command denied', 'Access denied'],
            'description' => 'Verifies that restricted operations fail with appropriate errors.'
        ],
        // Test case removed: '5. SQL Injection Protection' using MySQL's native PREPARE/EXECUTE syntax
        // This test was removed because it was unreliable due to session state issues with MySQL's native prepared statements
        // A more comprehensive test using PHP's mysqli prepared statement API has been added at the end of this file
        // See the run_prepared_statement_test() function for the new implementation


        [
            'name' => '6. Error Handling',
            'database' => 'wordpress_test',
            'sql' => "
                -- Non-existent table
                SELECT * FROM `non_existent_table`;

                -- Invalid SQL syntax
                SELEC * FROM `test_operations`;
            ",
            'expected' => false,
            'expect_error' => true,
            'description' => 'Tests that invalid SQL produces appropriate error messages.'
        ],
        [
            'name' => '7. Special Character Handling',
            'database' => 'wordpress_test',
            'sql' => "
                -- Create table with special characters
                CREATE TABLE IF NOT EXISTS `special_chars` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    content TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                -- Insert data with special characters
                INSERT INTO `special_chars` (content) VALUES
                ('Single\' quote'),
                ('Double\" quote'),
                ('Back`tick'),
                ('Special: !@#$%^&*()'),
                ('Emoji: 😊👍🔥'),
                ('HTML: <script>alert(\'xss\')</script>'),
                ('SQL: DROP TABLE users; --');

                -- Verify data was stored correctly
                SELECT * FROM `special_chars`;
            ",
            'expected' => true,
            'description' => 'Tests proper handling of special characters and potential injection attempts.'
        ]
    ];

    // Run tests grouped by functionality
    $test_count = count($tests);
    colored_message("Running $test_count test cases...\n", 'cyan');

    $test_results = [];
    $group_num = 1;
    $current_group = null;

    foreach ($tests as $i => $test) {
        $test_num = $i + 1;
        $total_tests = count($tests);

        // Group 1: Environment Setup
        if ($test_num <= 2) {
            if ($current_group !== 'env_setup') {
                $current_group = 'env_setup';
                echo "\n" . str_repeat("═", 80) . "\n";
                colored_message("GROUP $group_num: ENVIRONMENT SETUP", 'green');
                echo str_repeat("═", 80) . "\n";
                $group_num++;
            }
        }
        // Group 2: User Management
        elseif ($test_num <= 4) {
            if ($current_group !== 'user_mgmt') {
                $current_group = 'user_mgmt';
                echo "\n" . str_repeat("═", 80) . "\n";
                colored_message("GROUP $group_num: USER MANAGEMENT", 'green');
                echo str_repeat("═", 80) . "\n";
                $group_num++;
            }
        }
        // Group 3: Database Operations
        elseif ($test_num <= 9) {
            if ($current_group !== 'db_ops') {
                $current_group = 'db_ops';
                colored_message("\n=== GROUP $group_num: Database Operations ===\n", 'yellow');
                $group_num++;
            }
        }
        // Group 4: Data Handling
        else {
            if ($current_group !== 'data_handling') {
                $current_group = 'data_handling';
                echo "\n" . str_repeat("═", 80) . "\n";
                colored_message("GROUP $group_num: DATA HANDLING", 'green');
                echo str_repeat("═", 80) . "\n";
            }
        }

        // Create a visually distinct test header with consistent formatting
        echo "\n" . str_repeat("▓", 80) . "\n";
        colored_message("TEST {$test_num}/{$total_tests}: {$test['name']}", 'yellow');
        colored_message("Description: {$test['description']}", 'cyan');
        echo str_repeat("▓", 80) . "\n";

        // Display test SQL with syntax highlighting
        echo "🔍 Testing SQL:\n";
        if (empty($test['sql'])) { $test['sql'] = 'none'; }
        $sql_lines = explode("\n", trim($test['sql']));
        foreach ($sql_lines as $line) {
            colored_message('  ' . trim($line) . "\n", 'white');
        }
        echo "\n";

        // Handle function-based tests
        if (isset($test['function']) && is_callable($test['function'])) {
            try {
                $result = $test['function']();
                $success = $result['success'] ?? false;
                $error_message = $result['error'] ?? '';
            } catch (\Exception $e) {
                $success = false;
                $error_message = $e->getMessage();
                echo "Test function threw an exception: " . $error_message . "\n";
            }
        } else {
            // Handle SQL-based tests
            $database = $test['database'] ?? null;
            $use_root = $test['use_root'] ?? false;
            $results = execute_multi_sql($test['sql'], database: $database, use_root: $use_root);
            $success = true;
            $error_message = '';

            // Check each command's result
            foreach ($results as $result) {
                if (!isset($result['success']) || !$result['success']) {
                    $success = false;
                    if (isset($result['error'])) {
                        $error_message = $result['error'];
                        echo "Command failed: " . $error_message . "\n";
                    }
                    break;
                }
            }
        }

        // Track test result
        $test_passed = ($success === $test['expected']);
        $test_results[] = [
            'name' => $test['name'],
            'passed' => $test_passed,
            'error' => $test_passed ? null : $error_message
        ];

        // Display test result
        if ($test_passed) {
            colored_message("✅ TEST {$test_num} PASSED: {$test['name']}", 'green');
        } else {
            colored_message("❌ TEST {$test_num} FAILED: {$test['name']}", 'red');

            // Display error message immediately after the test result
            if ($error_message) {
                colored_message("   Error: {$error_message}", 'red');
            }

            // Display detailed MySQL errors if available
            if (isset($results) && is_array($results)) {
                foreach ($results as $idx => $result) {
                    // Display MySQL errors
                    if (isset($result['error']) && !empty($result['error'])) {
                        $cmd_num = $idx + 1;
                        colored_message("   MySQL Error in command #{$cmd_num}: {$result['error']}", 'red');
                    }

                    // Display PHP execution errors if available
                    if (isset($result['meta']['php_error']) && !empty($result['meta']['php_error'])) {
                        $cmd_num = $idx + 1;
                        colored_message("   PHP Error in command #{$cmd_num}:", 'red');
                        foreach (explode("\n", $result['meta']['php_error']) as $line) {
                            if (trim($line)) {
                                colored_message("     $line", 'red');
                            }
                        }
                    }

                    // Display command execution errors if available
                    if (isset($result['meta']['command_error']) && !empty($result['meta']['command_error'])) {
                        $cmd_num = $idx + 1;
                        colored_message("   Command Execution Error in command #{$cmd_num}:", 'red');
                        colored_message("     {$result['meta']['command_error']}", 'red');
                        if (isset($result['meta']['return_code'])) {
                            colored_message("     Exit code: {$result['meta']['return_code']}", 'red');
                        }
                    }

                    // Display JSON parsing errors if available
                    if (isset($result['error_code']) && $result['error_code'] === 'json_parse_error') {
                        $cmd_num = $idx + 1;
                        colored_message("   JSON Parsing Error in command #{$cmd_num}:", 'red');
                        colored_message("     {$result['error']}", 'red');
                        if (has_cli_flag(['--debug', '-d'])) {
                            colored_message("     Raw JSON (first 100 chars):", 'red');
                            colored_message("     " . substr($result['meta']['raw_json'] ?? '', 0, 100) . "...", 'red');
                        }
                    }
                }
            }
        }

        // Verify at the end of each group
        if ($i === 1 || $i === 4 || $i === 9) {
            verify_and_cleanup("After Group " . ($group_num - 1) . " Tests", false);
        }
    }

    // Calculate test statistics
    $passed_count = count(array_filter($test_results, fn($t) => $t['passed']));
    $failed_count = count($test_results) - $passed_count;
    // Display test summary
    colored_message("\n" . str_repeat("=", 80), 'cyan');
    colored_message("TEST SUMMARY", 'cyan');
    colored_message(str_repeat("-", 80), 'cyan');

    echo "\n✅ Passed: $passed_count" . str_repeat(" ", 10) . "❌ Failed: $failed_count\n\n";

    // Display detailed results for failed tests
    if ($failed_count > 0) {
        colored_message("Failed tests:", 'red');
        foreach ($test_results as $test) {
            if (!$test['passed']) {
                echo "- {$test['name']}: " . ($test['error'] ?? 'Unknown error') . "\n";
            }
        }
    }

    colored_message("\nTest execution complete. " .
                  ($failed_count === 0 ? "All tests passed!" : "Some tests failed."),
                  $failed_count === 0 ? 'green' : 'red');
    colored_message(str_repeat("=", 80) . "\n", 'cyan');

    // Final verification and cleanup
    verify_and_cleanup("After Test Suite Completion", true);

    return $failed_count === 0;
}

/**
 * Create a standardized response array
 *
 * @param bool $success Whether the operation was successful
 * @param mixed $data The result data (if any)
 * @param string|null $error Error message if operation failed
 * @param string|null $error_code Standardized error code
 * @param array $meta Additional metadata about the operation
 * @return array
 */
function create_db_response(bool $success = true, $data = null, ?string $error = null, ?string $error_code = null, array $meta = []): array {
    return [
        'success' => $success,
        'error' => $error,
        'error_code' => $error_code,
        'data' => $data ?? [],
        'meta' => array_merge([
            'affected_rows' => 0,
            'insert_id' => 0,
            'num_rows' => 0,
            'warnings' => []
        ], $meta)
    ];
}



/**
 * Execute a MySQL query using MySQLi
 *
 * @param string $sql The SQL query to execute (can include multiple statements)
 * @return array [
 *     'success' => bool,
 *     'error' => string|null,
 *     'error_code' => string|null,
 *     'data' => array,
 *     'meta' => [
 *         'affected_rows' => int,
 *         'insert_id' => int,
 *         'num_rows' => int,
 *         'warnings' => array
 *     ]
 * ]
 */
/**
 * Execute a MySQL query in a Lando environment
 *
 * @internal This function is meant to be called by execute_mysqli_query() and not directly.
 * Use execute_mysqli_query() instead, which automatically handles Lando environments.
 *
 * @param string $sql The SQL query to execute
 * @param array $db_settings Database connection settings
 * @param string|null $db_name Optional database name (defaults to WordPress database)
 * @return array Standardized response array
 */
function execute_mysqli_lando(string $sql, array $db_settings, ?string $db_name = null): array {
    $temp_file = null;
    $temp_php_file = null;
    $output_file = null;
    $error_file = null;

    try {
        // Determine which database to use based on the provided parameters
        switch (true) {
            case $db_name === 'none':
                $db_to_use = 'none';  // Explicitly don't use any database, generated code will have check for 'none'
                break;
            case $db_name !== null:
                $db_to_use = $db_name;  // Use the provided database name
                break;
            default:
                $db_to_use = $db_settings['db_name'];  // Fall back to WordPress settings
        }

    // Validate database name if provided and not 'none'
    if ($db_name !== 'none' && $db_name !== null && !is_valid_identifier($db_name)) {
        return create_db_response(
            success: false,
            error: "Invalid database name: " . htmlspecialchars($db_to_use, ENT_QUOTES, 'UTF-8'),
            error_code: 'invalid_db_name',
            data: [],
            meta: ['validation_error' => true]
        );
    }

    // Get temporary file paths using the centralized function
    $file_paths = get_temp_file_paths('temp_mysql_exec_');

    // Extract file paths
    $temp_php_file = $file_paths['filesystem']['php'];
    $output_file = $file_paths['filesystem']['output'];
    $error_file = $file_paths['filesystem']['error'];
    $container_output_file = $file_paths['container']['output'];
    $container_error_file = $file_paths['container']['error'];
    $temp_file = $file_paths['temp_file']; // Store the temp file base name for reference

    // Display file paths in debug mode
    if (has_cli_flag(['--debug', '-d']) || has_cli_flag(['--verbose', '-v'])) {
        echo "\n🔍 DEBUG: Temporary files created:\n";

        // Show full paths only in debug mode, otherwise just show filenames
        if (has_cli_flag(['--debug', '-d'])) {
            echo "  PHP File: {$temp_php_file}\n";
            echo "  Output File: {$output_file}\n";
            echo "  Error File: {$error_file}\n";
            echo "  Container Output File: {$container_output_file}\n";
            echo "  Container Error File: {$container_error_file}\n";
        } else {
            echo "  PHP File: " . basename($temp_php_file) . "\n";
            echo "  Output File: " . basename($output_file) . "\n";
            echo "  Error File: " . basename($error_file) . "\n";
            echo "  (Use --debug flag to see full paths)\n";
        }
    }

        // Escape values for PHP code
        $db_host = addslashes($db_settings['db_host']);
        $db_user = addslashes($db_settings['db_user']);
        $db_pass = addslashes($db_settings['db_pass']);

        // Properly escape SQL for inclusion in PHP string
        // This handles all special characters including quotes, backslashes, and Unicode
        $escaped_sql = addslashes($sql);

        // Create PHP code that will execute the query and output JSON
        $php_code = <<<EOD
<?php
// Disable error output to prevent corrupting JSON
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Function to create a standardized response
function create_db_response(\$success = true, \$data = null, \$error = null, \$error_code = null, \$meta = []) {
    return [
        'success' => \$success,
        'error' => \$error,
        'error_code' => \$error_code,
        'data' => \$data ?? [],
        'meta' => array_merge([
            'affected_rows' => 0,
            'insert_id' => 0,
            'num_rows' => 0,
            'warnings' => []
        ], \$meta)
    ];
}

// Function to write JSON to a file
function write_json_to_file(\$data, \$file_path) {
    \$json = json_encode(\$data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR);
    if (\$json === false) {
        \$json = json_encode(['success' => false, 'error' => 'JSON encoding error: ' . json_last_error_msg(), 'error_code' => 'json_encoding_failed']);
    }
    file_put_contents(\$file_path, \$json);
    return \$json;
}

try {
    // Database connection settings
    \$db_host = '{$db_host}';
    \$db_user = '{$db_user}';
    \$db_pass = '{$db_pass}';
    \$sql = '{$escaped_sql}';

    // Create connection with conditional database parameter
    \$db_to_use = '{$db_to_use}';
    // Create a direct mysqli connection
    if (\$db_to_use === 'none') {
        // Connect without selecting a database
        \$mysqli = new \mysqli(\$db_host, \$db_user, \$db_pass);
    } else {
        // Connect with a specific database
        \$mysqli = new \mysqli(\$db_host, \$db_user, \$db_pass, \$db_to_use);
    }

    if (\$mysqli->connect_error) {
        \$output_file = '$container_output_file';
        write_json_to_file(create_db_response(
            success: false,
            data: [],
            error: \$mysqli->connect_error,
            error_code: 'connection_failed',
            meta: ['exception' => true]
        ), \$output_file);
        throw new Exception('Connection failed: ' . \$mysqli->connect_error, 1);
    }

    // Connection error already checked above

    // Set proper character encoding for handling all Unicode characters including emojis
    \$mysqli->set_charset('utf8mb4');

    // Ensure proper collation for special characters
    \$mysqli->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    \$mysqli->query("SET CHARACTER SET utf8mb4");

    // Enable MySQL prepared statement preservation
    try {
        // Attempt to set the prepared statement count
        \$mysqli->query("SET SESSION max_prepared_stmt_count=1000");
    } catch (\Exception \$e) {
        \$meta['warnings'][] = "Could not set max_prepared_stmt_count: " . \$e->getMessage();
    }
    \$mysqli->query("SET SESSION autocommit=1");

    // Execute the query
    \$result = \$mysqli->multi_query(\$sql);

    if (\$result === false) {
        throw new Exception('Query failed: ' . \$mysqli->error, 2);
    }

    \$meta = [
        'insert_id' => \$mysqli->insert_id,
        'affected_rows' => \$mysqli->affected_rows
    ];
    \$data = [];

    // Process all result sets with improved error handling and debugging
    \$statement_count = 0;
    // IMPORTANT: Debug output must be disabled by default in the generated PHP code
    // Any direct output (echo statements) will corrupt the JSON response
    // This causes 'headers already sent' warnings and makes the JSON invalid
    // Only enable for local debugging, never in production
    \$debug_enabled = false; // Set to false to prevent corrupting JSON output

    // Parse SQL statements for debugging
    \$debug_parsed_statements = [];
    if (\$debug_enabled) {
        // Try to split the SQL into individual statements for debugging
        \$temp_sql = trim(\$sql);
        if (!empty(\$temp_sql)) {
            // Simple split by semicolon - not perfect but helps for debugging
            \$debug_parsed_statements = array_filter(array_map('trim', explode(';', \$temp_sql)));
            echo "\n[DEBUG] Parsed " . count(\$debug_parsed_statements) . " SQL statements for execution\n";
            foreach (\$debug_parsed_statements as \$idx => \$stmt) {
                echo "[DEBUG] Statement #" . (\$idx + 1) . ": " . substr(\$stmt, 0, 100) . (strlen(\$stmt) > 100 ? "..." : "") . "\n";
            }
        }
    }

    do {
        \$statement_count++;
        if (\$debug_enabled) {
            echo "\n[DEBUG] Processing statement #\$statement_count\n";
        }

        if (\$result = \$mysqli->store_result()) {
            while (\$row = \$result->fetch_assoc()) {
                \$data[] = \$row;
            }
            \$meta['num_rows'] = \$result->num_rows;
            if (\$debug_enabled) {
                echo "[DEBUG] Statement #\$statement_count completed successfully with {\$meta['num_rows']} rows\n";
            }
            \$result->free();
        } elseif (\$mysqli->errno) {
            // Capture errors that occur during result processing
            \$error_msg = "Query failed in statement #\$statement_count: {\$mysqli->error}";
            if (\$debug_enabled) {
                echo "[DEBUG] ERROR: \$error_msg\n";
            }
            throw new Exception(\$error_msg, 2);
        } else {
            // No result set but also no error (e.g., for INSERT, UPDATE, etc.)
            if (\$debug_enabled) {
                echo "[DEBUG] Statement #\$statement_count executed with no result set (affected rows: {\$mysqli->affected_rows})\n";
            }
        }
    } while (\$mysqli->more_results() && \$mysqli->next_result());

    // Final error check after all results are processed
    if (\$mysqli->error) {
        throw new Exception('Query failed: ' . \$mysqli->error, 2);
    }

    // Check for any remaining prepared statements and clean them up
    try {
        \$mysqli->query("DEALLOCATE PREPARE IF EXISTS safe_stmt");
    } catch (Exception \$e) {
        // Ignore errors from cleanup attempts
    }

    // Don't close the connection manually - let the manager handle it
    // \$mysqli->close();
    // Write JSON response to the output file
    \$output_file = '$container_output_file';
    \$result = write_json_to_file(create_db_response(
        success: true,
        data: \$data,
        error: null,
        error_code: null,
        meta: \$meta
    ), \$output_file);

} catch (Exception \$e) {
    \$error_code = match(\$e->getCode()) {
        1 => 'connect_failed',
        2 => 'query_failed',
        default => 'unknown_error'
    };
    // Write error JSON response to the output file
    \$output_file = '$container_output_file';
    \$result = write_json_to_file(create_db_response(
        success: false,
        data: [],
        error: \$e->getMessage(),
        error_code: \$error_code,
        meta: ['exception' => true]
    ), \$output_file);
}
?>
EOD;

        // Write the PHP code to the temporary file
        if (file_put_contents($temp_php_file, $php_code) === false) {
            throw new \RuntimeException('Failed to create temporary PHP file', 100, null);
        }

        // Debug output for generated PHP code (only shown with both --debug and --verbose flags)
        if (has_cli_flag(['--debug', '-d']) && has_cli_flag(['--verbose', '-v'])) {
            colored_message("\n🔍 DEBUG: Generated PHP Code for Lando Execution", 'cyan');
            colored_message(str_repeat('-', 80), 'cyan');
            echo $php_code . "\n";
            colored_message(str_repeat('-', 80), 'cyan');

            // Check PHP syntax of the temporary file
            if (has_cli_flag(['--debug', '-d'])) {
                colored_message("\n🔍 DEBUG: Checking PHP syntax...", 'cyan');
                $output = [];
                $return_var = 0;
                exec("php -l " . escapeshellarg($temp_php_file) . " 2>&1", $output, $return_var);

                if ($return_var === 0) {
                    colored_message("✅ PHP syntax is valid", 'green');
                } else {
                    colored_message("❌ PHP syntax error:", 'red');
                    foreach ($output as $line) {
                        colored_message("  $line", 'red');
                    }
                }
            }
        }

        // Execute the PHP file using Lando
        // No need to redirect stdout since we're writing directly to the output file
        // We still redirect stderr to capture any PHP errors
        $command = sprintf(
            'cd %s && lando php -d display_errors=0 -d log_errors=0 %s 2> %s',
            escapeshellarg(__DIR__),
            escapeshellarg(basename($temp_php_file)),
            escapeshellarg(basename($error_file))
        );

        exec($command, $output, $return_var);

        // Read and decode the JSON response
        if (!file_exists($output_file)) {
            $error = file_exists($error_file) ? file_get_contents($error_file) : 'No output file was created';
            // Don't throw an exception, return a structured error response
            return create_db_response(
                false,
                null,
                "Failed to execute Lando PHP command",
                'command_execution_error',
                ['command_error' => $error, 'return_code' => $return_var]
            );
        }

        $json = file_get_contents($output_file);

        // Debug output of JSON response (only in debug/verbose mode)
        if (has_cli_flag(['--debug', '-d']) || has_cli_flag(['--verbose', '-v'])) {
            echo "\n=== JSON Response ===\n";
            echo "SQL Query: $sql\n";
            echo json_encode(json_decode($json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo "\n=== End JSON Response ===\n\n";
        }

        // Try to find JSON in the output (in case there are warnings)
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $json, $matches)) {
            $json = $matches[0];
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Don't throw an exception, just return a structured error response
            // This will be displayed with the test results
            return create_db_response(
                false,
                null,
                "Invalid JSON response from Lando: " . json_last_error_msg(),
                'json_parse_error',
                ['raw_json' => $json]
            );
        }

        return $decoded;

    } catch (\Exception $e) {
        // If we have an error file, include its contents in the error message
        $error_details = '';
        if (file_exists($error_file)) {
            $error_details = file_get_contents($error_file);
        }

        // Don't display the error immediately - just capture it in the response
        // It will be displayed with the test results
        return create_db_response(
            false,
            null,
            $e->getMessage(),
            $e->getCode() ?: 'unknown_error',
            ['php_error' => $error_details] // Store PHP error separately for better formatting
        );
    } finally {
        // Clean up temporary files (unless in debug mode)
        if (!has_cli_flag(['--debug', '-d'])) {
            foreach ([$temp_php_file, $output_file, $error_file] as $file) {
                if ($file && file_exists($file)) {
                    @unlink($file);
                }
            }
        } else {
            // Use more concise debug output that only shows filenames, not full paths
            echo "\n🔍 DEBUG: Temporary files preserved for debugging:\n";

            // Show full paths only in debug mode with verbose flag, otherwise just show filenames
            if (has_cli_flag(['--debug', '-d']) && has_cli_flag(['--verbose', '-v'])) {
                echo "  PHP File: {$temp_php_file}\n";
                echo "  Output File: {$output_file}\n";
                echo "  Error File: {$error_file}\n";
            } else {
                echo "  PHP File: " . basename($temp_php_file) . "\n";
                echo "  Output File: " . basename($output_file) . "\n";
                echo "  Error File: " . basename($error_file) . "\n";
                echo "  (Use --debug --verbose flags to see full paths)\n";
            }
        }
    }
}



/**
 * Execute MySQL query using direct MySQLi connection
 *
 * @internal This function is meant to be called by execute_mysqli_query() and not directly.
 * Use execute_mysqli_query() instead, which automatically handles the appropriate execution method.
 *
 * @param string $host Database host
 * @param string $user Database username
 * @param string $pass Database password
 * @param string $sql SQL query to execute
 * @param string|null $db_name Optional database name (defaults to WordPress database)
 * @return array Standardized response array
 */
function execute_mysqli_direct(string $host, string $user, string $pass, string $sql, ?string $db_name = null): array {
    global $db_settings, $db_manager;

    // Determine which database to use based on the provided parameters
    switch (true) {
        case $db_name === 'none':
            $db_name = null;  // Explicitly don't use any database
            break;
        case $db_name === null:
            $db_name = $db_settings['db_name'] ?? '';  // Fall back to WordPress settings
            break;
        // else use the provided $db_name as-is
    }

    // Validate database name if provided
    if (!empty($db_name) && !is_valid_identifier($db_name)) {
        return create_db_response(
            false,
            null,
            "Invalid database name: " . htmlspecialchars($db_name, ENT_QUOTES, 'UTF-8'),
            'invalid_db_name'
        );
    }

    // Use Database_Connection_Manager to get a connection
    try {
        // Using Database_Connection_Manager for pooled connections (see class-database-connection-manager.php)
        try {
            $mysqli = $db_manager->get_connection($host, $user, $pass, $db_name);
        } catch (\RuntimeException $e) {
            return create_db_response(
                false,
                null,
                $e->getMessage(),
                'connection_failed',
                ['exception' => true]
            );
        }

        if ($mysqli->connect_error) {
            throw new \RuntimeException('Connection failed: ' . $mysqli->connect_error, 1, null);
        }

        $mysqli->set_charset('utf8mb4');
        $result = $mysqli->multi_query($sql);

        if ($result === false) {
            throw new \RuntimeException('Query failed: ' . $mysqli->error, 2, null);
        }

        $meta = [
            'insert_id' => $mysqli->insert_id,
            'affected_rows' => $mysqli->affected_rows,
            'num_rows' => 0,
            'warnings' => [],
            'statements' => [] // Track individual statement results
        ];

        $data = [];

        // Process all result sets
        $statement_count = 0;
        $has_error = false;
        $debug = has_cli_flag(['--debug', '-d', '--verbose', '-v']);

        do {
            $statement_count++;

            // Debug output for each statement execution
            if ($debug) {
                colored_message("\n▶ Executing statement #$statement_count", 'yellow');
            }

            // Check for errors after each statement
            if ($mysqli->error) {
                $has_error = true;
                $error_message = 'Query failed in statement #' . $statement_count . ': ' . $mysqli->error;

                if ($debug) {
                    colored_message("\n❌ ERROR: $error_message", 'red');
                }

                throw new \RuntimeException($error_message, 2, null);
            }

            // Process result set if available
            if ($result = $mysqli->store_result()) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $meta['num_rows'] = $result->num_rows;

                // Debug output for successful result set
                if ($debug) {
                    colored_message("  ✅ Result set with {$result->num_rows} row(s)", 'green');
                }

                $result->free();
            } else if ($mysqli->affected_rows > 0 && $debug) {
                // Debug output for statements with affected rows but no result set
                colored_message("  ✅ Statement affected {$mysqli->affected_rows} row(s)", 'green');
            } else if ($debug) {
                // Debug output for statements with no result set and no affected rows
                colored_message("  ✅ Statement executed successfully", 'green');
            }

            // Store statement-specific metadata
            $meta['statements'][] = [
                'index' => $statement_count,
                'affected_rows' => $mysqli->affected_rows,
                'insert_id' => $mysqli->insert_id,
                'error' => $mysqli->error ?: null,
                'statement_type' => $mysqli->field_count ? 'query' : 'non-query'
            ];

        } while (!$has_error && $mysqli->more_results() && $mysqli->next_result());

        // Final error check after all statements
        if ($mysqli->error && !$has_error) {
            $error_message = 'Query failed after processing: ' . $mysqli->error;

            if ($debug) {
                colored_message("\n❌ FINAL ERROR: $error_message", 'red');
            }

            throw new \RuntimeException($error_message, 2, null);
        }

        // Debug summary of all statements
        if ($debug) {
            colored_message("\n📋 Processed $statement_count statement(s) in total", 'cyan');
        }

        // Don't close the connection manually - let the connection manager handle it
        // $mysqli->close(); - Removed to allow connection pooling by the manager

        return create_db_response(
            success: true,
            data: $data,
            error: null,
            error_code: null,
            meta: $meta
        );
    } catch (\Exception $e) {
        return create_db_response(
            false,
            null,
            $e->getMessage(),
            $e->getCode() ?: 'unknown_error'
        );
    }
}

/**
 * Execute a MySQL prepared statement with direct connection
 *
 * @param string $host Database host
 * @param string $user Database username
 * @param string $pass Database password
 * @param string $query SQL query with placeholders
 * @param array $params Array of parameter values
 * @param array $types Array of parameter types ('s' for string, 'i' for integer, 'd' for double, 'b' for blob)
 * @param string|null $db_name Optional database name
 * @return array Standardized response array with success/error information
 */
function execute_mysqli_prepared_statement_direct(string $host, string $user, string $pass, string $query, array $params, array $types, ?string $db_name = null): array {
    global $db_settings, $db_manager;

    // Determine which database to use based on the provided parameters
    switch (true) {
        case $db_name === 'none':
            $db_name = null;  // Explicitly don't use any database
            break;
        case $db_name === null:
            $db_name = $db_settings['db_name'] ?? '';  // Fall back to WordPress settings
            break;
        // else use the provided $db_name as-is
    }

    // Validate database name if provided
    if (!empty($db_name) && !is_valid_identifier($db_name)) {
        return create_db_response(
            false,
            null,
            "Invalid database name: " . htmlspecialchars($db_name, ENT_QUOTES, 'UTF-8'),
            'invalid_db_name'
        );
    }

    // Use Database_Connection_Manager to get a connection
    try {
        // Using Database_Connection_Manager for pooled connections
        try {
            $mysqli = $db_manager->get_connection($host, $user, $pass, $db_name);
        } catch (\RuntimeException $e) {
            return create_db_response(
                false,
                null,
                $e->getMessage(),
                'connection_failed',
                ['exception' => true]
            );
        }

        if ($mysqli->connect_error) {
            throw new \RuntimeException('Connection failed: ' . $mysqli->connect_error, 1, null);
        }

        $mysqli->set_charset('utf8mb4');

        // Prepare the statement
        $stmt = $mysqli->prepare($query);
        if ($stmt === false) {
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error, 2, null);
        }

        // Create the type string from the types array
        $type_string = implode('', $types);

        // Bind parameters
        if (!empty($params)) {
            // Create reference array for bind_param
            $bind_params = array($type_string);
            for ($i = 0; $i < count($params); $i++) {
                $bind_params[] = &$params[$i];
            }

            // Call bind_param with dynamic parameters
            call_user_func_array(array($stmt, 'bind_param'), $bind_params);
        }

        // Execute the statement
        $result = $stmt->execute();

        if ($result === false) {
            throw new \RuntimeException('Execute failed: ' . $stmt->error, 2, null);
        }

        // Get the result
        $result_set = $stmt->get_result();

        $meta = [
            'insert_id' => $mysqli->insert_id,
            'affected_rows' => $stmt->affected_rows,
            'num_rows' => $result_set ? $result_set->num_rows : 0,
            'warnings' => []
        ];

        $data = [];

        // Process result set if available
        if ($result_set) {
            while ($row = $result_set->fetch_assoc()) {
                $data[] = $row;
            }
            $result_set->free();
        }

        // Close the statement
        $stmt->close();

        // Don't close the connection manually - let the connection manager handle it

        return create_db_response(
            success: true,
            data: $data,
            error: null,
            error_code: null,
            meta: $meta
        );
    } catch (\Exception $e) {
        return create_db_response(
            false,
            null,
            $e->getMessage(),
            $e->getCode() ?: 'unknown_error'
        );
    }
}

/**
 * Execute a MySQL prepared statement in a Lando environment
 *
 * @param string $query SQL query with placeholders
 * @param array $params Array of parameter values
 * @param array $types Array of parameter types ('s' for string, 'i' for integer, 'd' for double, 'b' for blob)
 * @param array $db_settings Database connection settings
 * @param string|null $db_name Optional database name
 * @return array Standardized response array with success/error information
 */
function execute_mysqli_prepared_statement_lando(string $query, array $params, array $types, array $db_settings, ?string $db_name = null): array {
    $temp_file = null;
    $temp_php_file = null;
    $output_file = null;
    $error_file = null;

    try {
        // Determine which database to use based on the provided parameters
        switch (true) {
            case $db_name === 'none':
                $db_to_use = 'none';  // Explicitly don't use any database
                break;
            case $db_name !== null:
                $db_to_use = $db_name;  // Use the provided database name
                break;
            default:
                $db_to_use = $db_settings['db_name'];  // Fall back to WordPress settings
        }

        // Validate database name if provided and not 'none'
        if ($db_name !== 'none' && $db_name !== null && !is_valid_identifier($db_name)) {
            return create_db_response(
                success: false,
                error: "Invalid database name: " . htmlspecialchars($db_to_use, ENT_QUOTES, 'UTF-8'),
                error_code: 'invalid_db_name',
                data: [],
                meta: ['validation_error' => true]
            );
        }

        // Get temporary file paths using the centralized function
        $file_paths = get_temp_file_paths('temp_mysql_exec_');

        // Extract file paths
        $temp_php_file = $file_paths['filesystem']['php'];
        $output_file = $file_paths['filesystem']['output'];
        $error_file = $file_paths['filesystem']['error'];
        $container_output_file = $file_paths['container']['output'];
        $container_error_file = $file_paths['container']['error'];
        $temp_file = $file_paths['temp_file']; // Store the temp file base name for reference

        // Display file paths in debug mode
        if (has_cli_flag(['--debug', '-d']) || has_cli_flag(['--verbose', '-v'])) {
            echo "\n🔍 DEBUG: Temporary files created:\n";

            // Show full paths only in debug mode, otherwise just show filenames
            if (has_cli_flag(['--debug', '-d'])) {
                echo "  PHP File: {$temp_php_file}\n";
                echo "  Output File: {$output_file}\n";
                echo "  Error File: {$error_file}\n";
                echo "  Container Output File: {$container_output_file}\n";
                echo "  Container Error File: {$container_error_file}\n";
            } else {
                echo "  PHP File: " . basename($temp_php_file) . "\n";
                echo "  Output File: " . basename($output_file) . "\n";
                echo "  Error File: " . basename($error_file) . "\n";
                echo "  (Use --debug flag to see full paths)\n";
            }
        }

        // Escape values for PHP code
        $db_host = addslashes($db_settings['db_host']);
        $db_user = addslashes($db_settings['db_user']);
        $db_pass = addslashes($db_settings['db_pass']);

        // Properly escape query for inclusion in PHP string
        $escaped_query = addslashes($query);

        // Prepare parameters for PHP code
        $params_json = json_encode($params);
        $types_json = json_encode($types);

        // Create PHP code that will execute the prepared statement and output JSON
        $php_code = <<<EOD
<?php
// Disable error output to prevent corrupting JSON
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Function to create a standardized response
function create_db_response(\$success = true, \$data = null, \$error = null, \$error_code = null, \$meta = []) {
    return [
        'success' => \$success,
        'error' => \$error,
        'error_code' => \$error_code,
        'data' => \$data ?? [],
        'meta' => array_merge([
            'affected_rows' => 0,
            'insert_id' => 0,
            'num_rows' => 0,
            'warnings' => []
        ], \$meta)
    ];
}

// Function to write JSON to a file
function write_json_to_file(\$data, \$file_path) {
    \$json = json_encode(\$data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR);
    if (\$json === false) {
        \$json = json_encode(['success' => false, 'error' => 'JSON encoding error: ' . json_last_error_msg(), 'error_code' => 'json_encoding_failed']);
    }
    file_put_contents(\$file_path, \$json);
    return \$json;
}

try {
    // Database connection settings
    \$db_host = '{$db_host}';
    \$db_user = '{$db_user}';
    \$db_pass = '{$db_pass}';
    \$query = '{$escaped_query}';
    \$params = {$params_json};
    \$types = {$types_json};

    // Create connection with conditional database parameter
    \$db_to_use = '{$db_to_use}';

    // Create a direct mysqli connection
    if (\$db_to_use === 'none') {
        // Connect without selecting a database
        \$mysqli = new \mysqli(\$db_host, \$db_user, \$db_pass);
    } else {
        // Connect with a specific database
        \$mysqli = new \mysqli(\$db_host, \$db_user, \$db_pass, \$db_to_use);
    }

    if (\$mysqli->connect_error) {
        \$output_file = '$container_output_file';
        write_json_to_file(create_db_response(
            success: false,
            data: [],
            error: \$mysqli->connect_error,
            error_code: 'connection_failed',
            meta: ['exception' => true]
        ), \$output_file);
        throw new Exception('Connection failed: ' . \$mysqli->connect_error, 1);
    }

    // Set proper character encoding for handling all Unicode characters including emojis
    \$mysqli->set_charset('utf8mb4');

    // Ensure proper collation for special characters
    \$mysqli->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    \$mysqli->query("SET CHARACTER SET utf8mb4");

    // Prepare the statement
    \$stmt = \$mysqli->prepare(\$query);
    if (\$stmt === false) {
        throw new Exception('Prepare failed: ' . \$mysqli->error, 2);
    }

    // Create the type string from the types array
    \$type_string = implode('', \$types);

    // Bind parameters
    if (!empty(\$params)) {
        // Create reference array for bind_param
        \$bind_params = array(\$type_string);
        for (\$i = 0; \$i < count(\$params); \$i++) {
            \$bind_params[] = &\$params[\$i];
        }

        // Call bind_param with dynamic parameters
        call_user_func_array(array(\$stmt, 'bind_param'), \$bind_params);
    }

    // Execute the statement
    \$result = \$stmt->execute();

    if (\$result === false) {
        throw new Exception('Execute failed: ' . \$stmt->error, 2);
    }

    // Get the result
    \$result_set = \$stmt->get_result();

    \$meta = [
        'insert_id' => \$mysqli->insert_id,
        'affected_rows' => \$stmt->affected_rows,
        'num_rows' => \$result_set ? \$result_set->num_rows : 0,
        'warnings' => []
    ];

    \$data = [];

    // Process result set if available
    if (\$result_set) {
        while (\$row = \$result_set->fetch_assoc()) {
            \$data[] = \$row;
        }
        \$result_set->free();
    }

    // Close the statement
    \$stmt->close();

    // Close the connection
    \$mysqli->close();

    // Write JSON response to the output file
    \$output_file = '$container_output_file';
    \$result = write_json_to_file(create_db_response(
        success: true,
        data: \$data,
        error: null,
        error_code: null,
        meta: \$meta
    ), \$output_file);

} catch (Exception \$e) {
    \$output_file = '$container_output_file';
    write_json_to_file(create_db_response(
        success: false,
        data: [],
        error: \$e->getMessage(),
        error_code: \$e->getCode() ?: 'unknown_error',
        meta: ['exception' => true]
    ), \$output_file);
}
EOD;

        // Write the PHP code to a temporary file
        file_put_contents($temp_php_file, $php_code);

        // Execute the PHP code via lando
        $lando_command = "lando php {$temp_php_file} 2> {$container_error_file}";
        exec($lando_command, $output, $return_code);

        // Check for execution errors
        if ($return_code !== 0) {
            $error_message = file_exists($error_file) ? file_get_contents($error_file) : 'Unknown error executing PHP via Lando';
            throw new \RuntimeException("Lando PHP execution failed: $error_message", 3);
        }

        // Read the output file
        if (!file_exists($output_file)) {
            throw new \RuntimeException("Output file not found: $output_file", 4);
        }

        $output_content = file_get_contents($output_file);
        if ($output_content === false) {
            throw new \RuntimeException("Failed to read output file: $output_file", 5);
        }

        // Parse the JSON response
        $result = json_decode($output_content, true);
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON response: " . json_last_error_msg(), 6);
        }

        return $result;
    } catch (\Exception $e) {
        return create_db_response(
            false,
            null,
            $e->getMessage(),
            $e->getCode() ?: 'unknown_error'
        );
    } finally {
        // Clean up temporary files
        foreach ([$temp_php_file, $output_file, $error_file] as $file) {
            if ($file && file_exists($file)) {
                @unlink($file);
            }
        }
    }
}

/**
 * Execute a MySQL prepared statement with appropriate environment detection
 *
 * @param string $query SQL query with placeholders
 * @param array $params Array of parameter values
 * @param array $types Array of parameter types ('s' for string, 'i' for integer, 'd' for double, 'b' for blob)
 * @param string|null $user Optional database username
 * @param string|null $pass Optional database password
 * @param string|null $host Optional database host
 * @param string|null $db_name Optional database name
 * @return array Standardized response array with success/error information
 */
function execute_mysqli_prepared_statement(string $query, array $params, array $types, ?string $user = null, ?string $pass = null, ?string $host = null, ?string $db_name = null): array {
    global $db_settings;

    // Use provided credentials or fall back to WordPress settings
    $db_user = $user ?? $db_settings['db_user'] ?? '';
    $db_pass = $pass ?? $db_settings['db_pass'] ?? '';
    $db_host = $host ?? $db_settings['db_host'] ?? '';

    // Check if we're in a Lando environment
    if (is_lando_environment()) {
        return execute_mysqli_prepared_statement_lando($query, $params, $types, $db_settings, $db_name);
    } else {
        return execute_mysqli_prepared_statement_direct($db_host, $db_user, $db_pass, $query, $params, $types, $db_name);
    }
}

/**
 * Execute a MySQL query over SSH
 *
 * @param string $host Database host
 * @param string $user Database username
 * @param string $pass Database password
 * @param string $sql SQL query to execute
 * @param string|null $db_name Optional database name (defaults to WordPress database)
 * @return array Standardized response array
 */
function execute_mysql_via_ssh(string $host, string $user, string $pass, string $sql, ?string $db_name = null): array {
    global $db_settings, $db_manager;

    // Determine which database to use based on the provided parameters
    switch (true) {
        case $db_name === 'none':
            $db_name = null;  // Explicitly don't use any database
            break;
        case $db_name === null:
            $db_name = $db_settings['db_name'] ?? '';  // Fall back to WordPress settings
            break;
        // else use the provided $db_name as-is
    }

    // Validate database name if provided
    if (!empty($db_name) && !is_valid_identifier($db_name)) {
        return create_db_response(
            success: false,
            error: "Invalid database name: " . htmlspecialchars($db_name, ENT_QUOTES, 'UTF-8'),
            error_code: 'invalid_db_name',
            data: [],
            meta: ['validation_error' => true]
        );
    }

    try {
        $ssh_command = get_setting('SSH_COMMAND', '');

        if (empty($ssh_command) || $ssh_command === 'none') {
            // If SSH command is not configured, fall back to direct connection using the manager
            try {
                // Using Database_Connection_Manager for pooled connections (see class-database-connection-manager.php)
                try {
                    $mysqli = $db_manager->get_connection($host, $user, $pass, $db_name);
                } catch (\RuntimeException $e) {
                    return create_db_response(
                        false,
                        null,
                        $e->getMessage(),
                        'connection_failed',
                        ['exception' => true]
                    );
                }

                if ($mysqli->connect_error) {
                    throw new \RuntimeException('Connection failed: ' . $mysqli->connect_error, 1);
                }

                $mysqli->set_charset('utf8mb4');
                $result = $mysqli->multi_query($sql);

                if ($result === false) {
                    throw new \RuntimeException('Query failed: ' . $mysqli->error, 2);
                }

                $meta = [
                    'insert_id' => $mysqli->insert_id,
                    'affected_rows' => $mysqli->affected_rows,
                    'num_rows' => 0,
                    'warnings' => []
                ];

                $data = [];

                // Process all result sets
                do {
                    if ($result = $mysqli->store_result()) {
                        while ($row = $result->fetch_assoc()) {
                            $data[] = $row;
                        }
                        $meta['num_rows'] = $result->num_rows;
                        $result->free();
                    }
                } while ($mysqli->more_results() && $mysqli->next_result());

                if ($mysqli->error) {
                    throw new \RuntimeException('Query failed: ' . $mysqli->error, 2);
                }

                return create_db_response(
                    success: true,
                    data: $data,
                    error: null,
                    error_code: null,
                    meta: $meta
                );

            } catch (\Exception $e) {
                return create_db_response(
                    false,
                    null,
                    $e->getMessage(),
                    $e->getCode() ?: 'unknown_error'
                );
            }
        }

        // Escape the SQL for shell execution
        $escaped_sql = escapeshellarg($sql);

        // Build the MySQL command with proper escaping
        $mysql_cmd = sprintf(
            'mysql -h%s -u%s -p%s -e %s --ssl-mode=DISABLED --batch --skip-column-names',
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($pass),
            $escaped_sql
        );

        // Format the full SSH command
        $full_cmd = format_ssh_command($ssh_command, $mysql_cmd);

        // Execute the command
        $output = [];
        $return_var = 0;
        exec($full_cmd . ' 2>&1', $output, $return_var);

        $output_str = implode("\n", $output);

        if ($return_var !== 0) {
            throw new \RuntimeException(
                "SSH command failed with code $return_var: " . $output_str,
                $return_var,
                null,
                'ssh_command_failed'
            );
        }

        // Parse the output
        $data = [];
        $meta = [
            'affected_rows' => 0,
            'insert_id' => 0,
            'num_rows' => 0,
            'warnings' => []
        ];

        // For SELECT queries, parse the output
        if (stripos(trim($sql), 'SELECT') === 0) {
            $lines = explode("\n", trim($output_str));
            if (!empty($lines)) {
                $headers = str_getcsv($lines[0], "\t");
                $meta['num_rows'] = count($lines) - 1; // Subtract header row

                for ($i = 1; $i < count($lines); $i++) {
                    $values = str_getcsv($lines[$i], "\t");
                    if (count($headers) === count($values)) {
                        $data[] = array_combine($headers, $values);
                    }
                }
            }
        } else {
            // For non-SELECT queries, try to extract affected rows
            if (preg_match('/Rows matched: (\d+)\s+Changed: (\d+)/', $output_str, $matches)) {
                $meta['affected_rows'] = (int)$matches[2];
            } elseif (preg_match('/^Query OK, (\d+)/', $output_str, $matches)) {
                $meta['affected_rows'] = (int)$matches[1];
            }
        }

        return create_db_response(
            success: true,
            data: $data,
            error: null,
            error_code: null,
            meta: $meta
        );

    } catch (\Exception $e) {
        return create_db_response(
            false,
            null,
            $e->getMessage(),
            $e->getCode() ?: 'unknown_error'
        );
    }
}

/**
 * Output SQL debugging information
 */
function debug_sql(string $sql, array $params, ?array $result = null): void {
    if (!has_cli_flag(['--debug', '-d'])) {
        return;
    }

    static $query_count = 0;
    $query_count++;

    echo "\n" . str_repeat('=', 80) . "\n";
    colored_message("SQL DEBUG #$query_count - " . ($result ? 'RESULT' : 'QUERY'), 'cyan');
    echo str_repeat('-', 80) . "\n";

    if (!$result) {
        // Start of query
        echo trim($sql) . "\n";
        echo "Host: " . ($params['host'] ?? 'default') . "\n";
        echo "User: " . ($params['user'] ?? 'default') . "\n";
        if (!empty($params['db'])) {
            echo "DB: " . $params['db'] . "\n";
        }
    } else {
        // Query results
        if (isset($result['error'])) {
            echo "ERROR: " . $result['error'] . "\n";
            return;
        }

        $num_rows = $result['meta']['num_rows'] ?? 0;
        $affected_rows = $result['meta']['affected_rows'] ?? 0;

        if ($affected_rows > 0) {
            echo "✅ Rows affected: " . $affected_rows . "\n";
        }

        if ($num_rows > 0) {
            $display_count = min(10, $num_rows);
            echo "📊 Rows returned: " . $num_rows . " (showing first " . $display_count . " rows)\n\n";

            if (!empty($result['data'])) {
                // Get column headers
                $first_row = $result['data'][0] ?? [];
                $headers = array_keys($first_row);

                // Calculate column widths
                $col_widths = [];
                foreach ($headers as $header) {
                    $col_widths[$header] = strlen($header);
                    foreach ($result['data'] as $row) {
                        $col_widths[$header] = max($col_widths[$header], strlen((string)($row[$header] ?? '')));
                    }
                    $col_widths[$header] = min($col_widths[$header], 50); // Cap width at 50 chars
                }

                // Print header
                foreach ($headers as $header) {
                    echo str_pad(substr($header, 0, $col_widths[$header]), $col_widths[$header] + 2);
                }
                echo "\n" . str_repeat('-', array_sum($col_widths) + (count($headers) * 2)) . "\n";

                // Print rows (up to 10)
                $count = 0;
                foreach ($result['data'] as $row) {
                    if ($count++ >= 10) break;
                    foreach ($headers as $header) {
                        $value = $row[$header] ?? '';
                        if (strlen($value) > 50) {
                            $value = substr($value, 0, 47) . '...';
                        }
                        echo str_pad($value, $col_widths[$header] + 2);
                    }
                    echo "\n";
                }

                if ($num_rows > 10) {
                    echo "\n... and " . ($num_rows - 10) . " more rows\n";
                }
            }
        }
    }
    echo str_repeat('=', 80) . "\n\n";
}

/**
 * Execute a MySQL query using the appropriate connection method
 *
 * @param string $sql The SQL query to execute (can include multiple statements)
 * @param string|null $user Optional database username (defaults to WordPress DB user)
 * @param string|null $pass Optional database password (defaults to WordPress DB password)
 * @param string|null $host Optional database host (defaults to WordPress DB host)
 * @param string|null $db_name Optional database name:
 *                           - null: Use WordPress default database
 *                           - 'none': Don't use any database (for CREATE DATABASE, etc.)
 *                           - string: Use the specified database
 * @return array Standardized response array
 */



function execute_mysqli_query(string $sql, ?string $user = null, ?string $pass = null, ?string $host = null, ?string $db_name = null): array {
    global $query_counter;

    // Increment and assign a unique query ID
    $query_id = ++$query_counter;
    global $db_settings;

    // Use provided credentials or fall back to WordPress settings
    $user = $user ?? $db_settings['db_user'];
    $pass = $pass ?? $db_settings['db_pass'];
    $host = $host ?? $db_settings['db_host'];

    // Debug the query start
    $debug = has_cli_flag(['--debug', '-d', '--verbose', '-v']);

    if ($debug) {
        $db_display = match(true) {
            $db_name === 'none' => '[NONE] (no database selected)',
            $db_name === null => '[DEFAULT] ' . ($db_settings['db_name'] ?? 'wordpress'),
            default => $db_name
        };

        $debug_info = [
            'query_id' => "Q-{$query_id}",
            'host' => $host,
            'user' => $user,
            'database' => $db_display,
            'query_type' => strtoupper(strtok(trim($sql), ' ')),
            'query_length' => strlen($sql),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        colored_message("\n🔍 DEBUG: Executing MySQL Query", 'cyan');
        foreach ($debug_info as $key => $value) {
            colored_message(sprintf("  %-15s: %s", ucfirst($key), $value), 'cyan');
        }

        if (strlen($sql) < 500) {
            colored_message("\n" . $sql . "\n", 'white');
        } else {
            colored_message("\n[Query is too long to display (" . strlen($sql) . " characters)]\n", 'white');
        }
    }

    // Validate database name if provided and not 'none'
    if ($db_name !== null && $db_name !== 'none' && !is_valid_identifier($db_name)) {
        return create_db_response(
            success: false,
            error: "Invalid database name: " . htmlspecialchars($db_name, ENT_QUOTES, 'UTF-8'),
            error_code: 'invalid_db_name',
            data: [],
            meta: ['validation_error' => true]
        );
    }

    // Check if we're in a Lando environment - handle this first
    if (is_lando_environment()) {
        $result = execute_mysqli_lando($sql, array_merge($db_settings, [
            'db_user' => $user,
            'db_pass' => $pass,
            'db_host' => $host
        ]), $db_name);

        // Add query ID to result for reference
        $result['query_id'] = $query_id;

        // Show formatted results
        display_sql_result($result);

        // Show detailed debug info if debug is enabled
        if ($debug) {
            debug_sql($sql, [
                'host' => $host,
                'user' => $user,
                'db' => $db_name ?? '[NONE]'
            ], $result);
        }

        return $result;
    }

    // Check if we should use SSH for this connection
    $ssh_command = get_setting('SSH_COMMAND', '');

    // Only use SSH if explicitly configured and not a Lando command
    if (!empty($ssh_command) &&
        $ssh_command !== 'none' &&
        strpos($ssh_command, 'lando') === false) {
        $result = execute_mysql_via_ssh(
            $host,
            $user,
            $pass,
            $sql,
            $db_name
        );

        // Add query ID to result for reference
        $result['query_id'] = $query_id;

        // Show formatted results
        display_sql_result($result);

        // Show detailed debug info if debug is enabled
        if ($debug) {
            debug_sql($sql, [
                'host' => $host,
                'user' => $user,
                'db' => $db_name ?? '[NONE]'
            ], $result);
        }

        return $result;
    }

    // Default to direct MySQLi connection
    $result = execute_mysqli_direct(
        $host,
        $user,
        $pass,
        $sql,
        $db_name
    );

    // Add query ID to result for reference
    $result['query_id'] = $query_id;

    // Always show formatted results
    display_sql_result($result, $query_id);

    // Show detailed debug info if debug is enabled
    if ($debug) {
        debug_sql($sql, [
            'host' => $host,
            'user' => $user,
            'db' => $effective_db ?? '[NONE]'
        ], $result);
    }

    return $result;
}

/**
 * Test querying WordPress posts table
 *
 * @param string|null $db_name Optional database name (defaults to WordPress database)
 * @return bool True if test passed, false otherwise
 */
function test_wordpress_posts_query(?string $db_name = null) {
    global $db_settings;

    $test_name = "WordPress Posts Query";
    colored_message("\n=== TEST: $test_name ===", 'cyan');

    // Display test configuration
    echo "\n🔧 Test Configuration:";
    echo "\n- Database: " . ($db_settings['db_name'] ?? 'Not set');
    echo "\n- Table Prefix: " . ($db_settings['table_prefix'] ?? 'Not set');

    if (empty($db_settings['table_prefix'])) {
        colored_message("\n❌ Error: Could not determine table prefix from database settings", 'red');
        return false;
    }

    $posts_table = $db_settings['table_prefix'] . 'posts';
    $sql = "SELECT ID, post_title, post_date, post_type, post_status
            FROM `$posts_table`
            WHERE post_type = 'post' AND post_status = 'publish'
            LIMIT 5";

    echo "\n\n🔍 Executing query:";
    echo "\n" . str_repeat("-", 80);
    echo "\n" . wordwrap($sql, 80, "\n  ") . "\n";
    echo "\n" . str_repeat("-", 80) . "\n";

    $result = execute_mysqli_query(sql: $sql, db_name: $db_name);

    if ($result['success']) {
        $row_count = count($result['data']);
        $status = $row_count > 0 ? 'PASSED' : 'WARNING';
        $color = $row_count > 0 ? 'green' : 'yellow';

        colored_message("\n✅ [$status] $test_name", $color);
        echo "\n- Found: $row_count posts";

        if ($row_count > 0) {
            colored_message("\n\n📋 Query Results:", 'yellow');

            // Calculate column widths
            $widths = [
                'ID' => 10,
                'post_date' => 20,
                'post_title' => 50,
                'post_type' => 10,
                'post_status' => 10
            ];

            // Print headers
            echo "\n" . str_repeat("-", 105) . "\n";
            foreach ($widths as $field => $width) {
                echo str_pad(ucfirst(str_replace('_', ' ', $field)), $width) . " | ";
            }
            echo "\n" . str_repeat("-", 105) . "\n";

            // Print rows
            foreach ($result['data'] as $row) {
                foreach ($widths as $field => $width) {
                    $value = $row[$field] ?? '';
                    if ($field === 'post_title') {
                        $value = mb_strimwidth($value, 0, $width - 3, '...');
                    }
                    echo str_pad($value, $width) . " | ";
                }
                echo "\n";
            }
            echo str_repeat("-", 105) . "\n";
        }
    } else {
        $error = $result['error'] ?? 'Unknown error';
        colored_message("\n❌ FAILED: $test_name", 'red');
        echo "\n- Error: " . $error;
    }

    echo "\n";
    return $result['success'] ?? false;
}

/**
 * Set up a test database with the specified name
 *
 * @param string $test_db_name The name of the test database to create (default: 'wordpress_test')
 * @return array Standardized response array with success/error information
 */
function setup_test_database(string $test_db_name = 'wordpress_test'): array {
    global $db_settings;

    // Create a copy of the database settings with the test database name
    $test_db_settings = $db_settings;
    $test_db_settings['db_name'] = $test_db_name;

    // Call mysqli_create_database with the test database settings
    return mysqli_create_database($test_db_settings);
}

// Run the tests
$tests_passed = run_mysql_tests();

// Test root user in Lando environment
if (is_lando_environment()) {
    echo "\n" . str_repeat("▓", 80) . "\n";
    colored_message("🔍 TESTING ROOT USER (EMPTY PASSWORD)", 'cyan');
    echo str_repeat("▓", 80) . "\n";
    test_mysql_connectivity('database', 'root', '');
}
// Deliberately incorrect:
// Test root user in Lando environment
if (is_lando_environment()) {
    echo "\n" . str_repeat("▓", 80) . "\n";
    colored_message("🔍 TESTING ROOT USER (WRONG PASSWORD)", 'cyan');
    echo str_repeat("▓", 80) . "\n";
    test_mysql_connectivity('database', 'root', 'wrongpassword');
}

// Run WordPress posts query test if the basic tests passed
if ($tests_passed !== false) {
    $wp_test_passed = test_wordpress_posts_query();
    if ($wp_test_passed) {
        colored_message("\n✅ All tests passed successfully!", 'green');
    } else {
        colored_message("\n❌ WordPress posts query test failed!", 'red');
    }
}

// Perform final cleanup after all tests
echo "\nPerforming final cleanup...\n";
$cleanup_result = cleanup_test_environment();
if ($cleanup_result['success']) {
    colored_message("✅ Cleanup completed successfully", 'green');
} else {
    colored_message("❌ Cleanup failed: " . ($cleanup_result['error'] ?? 'Unknown error'), 'red');
}
colored_message("TESTING COMPLETE", 'cyan');
colored_message(str_repeat("-", 80), 'cyan');
echo "\n";

if ($tests_passed === false) {
    colored_message("❌ Initialization tests failed. WordPress posts test was not run.", 'red');
    colored_message("To run these tests, please ensure you're in a Lando environment or have MySQL properly configured.", 'yellow');
} else {
    colored_message("Note: Check the output above for detailed test results.", 'blue');
    if ($wp_test_passed) {
        colored_message("✅ All tests completed successfully!", 'green');
    } else {
        colored_message("⚠️  Some tests completed with warnings or errors.", 'yellow');
    }
}

colored_message("\nNote: Check the output above for detailed test results.", 'blue');
colored_message(str_repeat("=", 80) . "\n", 'cyan');

/**
 * Add documentation about prepared statement implementation
 */
function add_prepared_statement_documentation(): void {
    echo "\n";
    colored_message(str_repeat("▓", 80), 'cyan');
    colored_message("📚 PREPARED STATEMENT DOCUMENTATION", 'cyan');
    colored_message(str_repeat("▓", 80), 'cyan');

    echo "\n";
    colored_message("Why Use PHP's mysqli Prepared Statement API Instead of MySQL's PREPARE/EXECUTE Syntax:", 'yellow');
    echo "\n";

    $benefits = [
        "Session State Correctness" => "PHP's mysqli prepared statements maintain proper session state and connection context, avoiding issues with statement handles being lost between queries.",
        "Type Safety" => "PHP's prepared statements handle type binding properly, ensuring integers, strings, and other data types are correctly passed to MySQL.",
        "Security" => "PHP's implementation provides better protection against SQL injection by handling parameter binding at a lower level.",
        "Compatibility" => "Works consistently across different MySQL versions and configurations without depending on specific MySQL server settings.",
        "Error Handling" => "Provides better error reporting and exception handling through PHP's error system."
    ];

    foreach ($benefits as $title => $description) {
        colored_message("• $title:", 'green');
        echo "  $description\n\n";
    }

    colored_message("Implementation Notes:", 'yellow');
    echo "\n";
    echo "This framework provides three functions for prepared statements:\n\n";
    echo "1. execute_mysqli_prepared_statement() - Main wrapper function that detects environment\n";
    echo "2. execute_mysqli_prepared_statement_direct() - For direct MySQL connections\n";
    echo "3. execute_mysqli_prepared_statement_lando() - For Lando environments\n\n";

    colored_message("Usage Example:", 'yellow');
    echo "\n";
    echo '$query = "INSERT INTO test_table (name, value) VALUES (?, ?)";' . "\n";
    echo '$params = ["test_name", 42];' . "\n";
    echo '$types = ["s", "i"];  // string, integer' . "\n";
    echo '$result = execute_mysqli_prepared_statement($query, $params, $types);' . "\n\n";

    colored_message(str_repeat("▓", 80), 'cyan');
}

/**
 * Run tests for prepared statement functionality
 *
 * @return bool True if all tests pass, false otherwise
 */
function run_prepared_statement_test(): bool {
    global $db_settings;

    $test_db = 'wordpress_test';
    $test_table = 'prepared_statement_test';
    $all_tests_passed = true;

    echo "\n";
    colored_message(str_repeat("▓", 80), 'cyan');
    colored_message("🧪 TESTING PREPARED STATEMENTS", 'cyan');
    colored_message(str_repeat("▓", 80), 'cyan');

    // Step 1: Create test table
    colored_message("\nStep 1: Creating test table...", 'blue');

    $create_table_sql = "DROP TABLE IF EXISTS `$test_table`;
        CREATE TABLE `$test_table` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

    $result = execute_mysqli_query(sql: $create_table_sql, db_name: $test_db);

    if (!$result['success']) {
        colored_message("❌ Failed to create test table: " . ($result['error'] ?? 'Unknown error'), 'red');
        return false;
    }

    colored_message("✅ Test table created successfully", 'green');

    // Step 2: Insert data using prepared statement
    colored_message("\nStep 2: Inserting data with prepared statements...", 'blue');

    $test_data = [
        ["Regular Name", "Regular Value"],
        ["Name with ' apostrophe", "Value with \" quotes"],
        ["Name with ; semicolon", "Value with -- comment"],
        ["Special chars: ñáéíóú", "More special: 你好，世界"],
    ];

    $insert_query = "INSERT INTO `$test_table` (name, value) VALUES (?, ?)";

    foreach ($test_data as $index => $data) {
        $result = execute_mysqli_prepared_statement(
            query: $insert_query,
            params: $data,
            types: ['s', 's'],
            db_name: $test_db
        );

        if (!$result['success']) {
            colored_message("❌ Failed to insert row $index: " . ($result['error'] ?? 'Unknown error'), 'red');
            $all_tests_passed = false;
            continue;
        }

        colored_message("✅ Row $index inserted successfully (ID: " . $result['meta']['insert_id'] . ")", 'green');
    }

    // Step 3: Select data with prepared statement
    colored_message("\nStep 3: Selecting data with prepared statements...", 'blue');

    $select_query = "SELECT * FROM `$test_table` WHERE name LIKE ?";
    $result = execute_mysqli_prepared_statement(
        query: $select_query,
        params: ['%special%'],
        types: ['s'],
        db_name: $test_db
    );

    if (!$result['success']) {
        colored_message("❌ Failed to select data: " . ($result['error'] ?? 'Unknown error'), 'red');
        $all_tests_passed = false;
    } else {
        colored_message("✅ Selected " . count($result['data']) . " rows with 'special' in name", 'green');

        // Display the results
        if (!empty($result['data'])) {
            echo "\nResults:\n";
            echo str_repeat("-", 80) . "\n";
            echo sprintf("%-5s %-30s %-30s %s\n", "ID", "Name", "Value", "Created At");
            echo str_repeat("-", 80) . "\n";

            foreach ($result['data'] as $row) {
                echo sprintf("%-5s %-30s %-30s %s\n",
                    $row['id'],
                    mb_substr($row['name'], 0, 28),
                    mb_substr($row['value'], 0, 28),
                    $row['created_at']
                );
            }
            echo str_repeat("-", 80) . "\n";
        }
    }

    // Step 4: Update data with prepared statement
    colored_message("\nStep 4: Updating data with prepared statements...", 'blue');

    $update_query = "UPDATE `$test_table` SET value = ? WHERE id = ?";
    $result = execute_mysqli_prepared_statement(
        query: $update_query,
        params: ['Updated value with injection attempt: \'; DROP TABLE users; --', 1],
        types: ['s', 'i'],
        db_name: $test_db
    );

    if (!$result['success']) {
        colored_message("❌ Failed to update data: " . ($result['error'] ?? 'Unknown error'), 'red');
        $all_tests_passed = false;
    } else {
        colored_message("✅ Updated row successfully (Affected rows: " . $result['meta']['affected_rows'] . ")", 'green');

        // Verify the update
        $verify_query = "SELECT * FROM `$test_table` WHERE id = ?";
        $verify_result = execute_mysqli_prepared_statement(
            query: $verify_query,
            params: [1],
            types: ['i'],
            db_name: $test_db
        );

        if ($verify_result['success'] && !empty($verify_result['data'])) {
            colored_message("✅ Verified update: Value is now '" . mb_substr($verify_result['data'][0]['value'], 0, 30) . "...'", 'green');
        }
    }

    // Step 5: Delete data with prepared statement
    colored_message("\nStep 5: Deleting data with prepared statements...", 'blue');

    $delete_query = "DELETE FROM `$test_table` WHERE id = ?";
    $result = execute_mysqli_prepared_statement(
        query: $delete_query,
        params: [2],
        types: ['i'],
        db_name: $test_db
    );

    if (!$result['success']) {
        colored_message("❌ Failed to delete data: " . ($result['error'] ?? 'Unknown error'), 'red');
        $all_tests_passed = false;
    } else {
        colored_message("✅ Deleted row successfully (Affected rows: " . $result['meta']['affected_rows'] . ")", 'green');

        // Verify the deletion
        $count_query = "SELECT COUNT(*) as total FROM `$test_table`";
        $count_result = execute_mysqli_query(sql: $count_query, db_name: $test_db);

        if ($count_result['success'] && !empty($count_result['data'])) {
            $remaining = $count_result['data'][0]['total'];
            colored_message("✅ Verified deletion: $remaining rows remaining in table", 'green');
        }
    }

    // Final cleanup
    colored_message("\nStep 6: Cleaning up test table...", 'blue');
    $drop_table = execute_mysqli_query(sql: "DROP TABLE IF EXISTS `$test_table`", db_name: $test_db);

    if ($drop_table['success']) {
        colored_message("✅ Test table dropped successfully", 'green');
    } else {
        colored_message("⚠️ Could not drop test table: " . ($drop_table['error'] ?? 'Unknown error'), 'yellow');
    }

    // Summary
    echo "\n";
    colored_message(str_repeat("=", 80), 'cyan');

    if ($all_tests_passed) {
        colored_message("✅ All prepared statement tests passed successfully!", 'green');
    } else {
        colored_message("❌ Some prepared statement tests failed!", 'red');
    }

    colored_message(str_repeat("=", 80), 'cyan');
    echo "\n";

    return $all_tests_passed;
}

// Run the prepared statement tests and documentation
add_prepared_statement_documentation();
$prepared_statement_tests_passed = run_prepared_statement_test();
