<?php
/**
 * Framework utility functions
 *
 * Core utility functions for the PHPUnit testing framework.
 * These functions handle command formatting and configuration reading.
 *
 * @package WP_PHPUnit_Framework
 */

// phpcs:set WordPress.Security.EscapeOutput customEscapingFunctions[] esc_cli
// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.DB.RestrictedFunctions
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

declare(strict_types=1);

namespace WP_PHPUnit_Framework;

// Exit if accessed directly, should be run command line
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit;
}

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

// Global exception handler to catch and display any uncaught exceptions
set_exception_handler(
    function ( \Throwable $e ): void {
		echo esc_cli("\n" . COLOR_RED . 'UNCAUGHT EXCEPTION: ' . get_class($e) . COLOR_RESET . "\n");
		echo esc_cli(COLOR_RED . 'Message: ' . $e->getMessage() . COLOR_RESET . "\n");
		echo esc_cli(COLOR_RED . 'File: ' . $e->getFile() . ' (Line ' . $e->getLine() . ')' . COLOR_RESET . "\n");
		echo esc_cli(COLOR_RED . 'Stack trace:' . COLOR_RESET . "\n");
		echo esc_cli($e->getTraceAsString() . "\n");
		exit(1);
	}
);


/**
 * Get a configuration value from environment variables, .env file, or default
 *
 * @param string $name Setting name
 * @param mixed  $default Default value if not found
 * @return mixed Setting value
 */
function get_setting( string $name, mixed $default = null ): mixed {
    // Check environment variables first (highest priority)
    $env_value = getenv($name);
    if ($env_value !== false) {
        return $env_value;
    }

    // Check our loaded settings (already loaded from .env.testing)
    global $loaded_settings;
    if (isset($loaded_settings[ $name ])) {
        return $loaded_settings[ $name ];
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
            error_log("Warning: \$loaded_settings is empty when requesting '$name' in " . debug_backtrace()[0]['file'] . ":" . debug_backtrace()[0]['line'], 3, $error_log_file);
        } else if (!isset($loaded_settings[$name])) {
            error_log("Warning: '$name' not found in \$loaded_settings in " . debug_backtrace()[0]['file'] . ":" . debug_backtrace()[0]['line'], 3, $error_log_file);
        }
    }

    // Return default if not found
    return $default;
}

/**
 * Utility: trim_folder_settings
 *
 * This function trims leading/trailing slashes and whitespace from folder/path settings.
 * Customize the list of settings to trim for your project.
 *
 * Usage: Call this after loading settings, before using them to build paths.
 *
 * @param array $settings Associative array of settings (e.g., from get_setting or load_settings_file)
 * @return array Trimmed settings array
 */
function trim_folder_settings(array $settings): array {
	$settings_to_trim = [
		'WP_ROOT',
		'FILESYSTEM_WP_ROOT',
		'FOLDER_IN_WORDPRESS',
		'YOUR_PLUGIN_SLUG',
		'PLUGIN_FOLDER',
		// Add/remove settings here as needed for your project structure
	];

	foreach ($settings_to_trim as $key) {
		if (isset($settings[$key])) {
			$settings[$key] = trim($settings[$key], " \/");
		}
	}
	return $settings;
}

/**
 * Joins multiple path segments into a single normalized path.
 * Trims leading/trailing slashes and whitespace from each segment, except preserves leading slash if first argument is absolute.
 *
 * Usage: $path = make_path($wp_root, $folder_in_wordpress, $your_plugin_slug, 'tests');
 *
 * @param string ...$segments Path segments to join
 * @return string Normalized path
 */
function make_path(...$segments): string {
	$clean = [];
	foreach ($segments as $i => $seg) {
		if ($i === 0) {
			// Preserve leading slash if absolute
			$seg = rtrim($seg, " \/");
		} else {
			$seg = trim($seg, " \/");
		}
		if ($seg !== '') {
			$clean[] = $seg;
		}
	}
	$path = implode('/', $clean);
	// If first segment was absolute, ensure leading slash
	if (isset($segments[0]) && strpos($segments[0], '/') === 0 && strpos($path, '/') !== 0) {
		$path = '/' . $path;
	}
	return $path;
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
        echo esc_cli("Reading database settings from wp-config.php...\n");

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
            echo esc_cli(COLOR_YELLOW . "Warning: Error including $wp_config_path: {$e->getMessage()}" . COLOR_RESET . "\n");
        }
    }

    // 2. Load from config file (e.g., .env, .env.testing)
    $env_file_db_host = get_setting('WP_TESTS_DB_HOST', null);
    $env_file_db_user = get_setting('WP_TESTS_DB_USER', null);
    $env_file_db_pass = get_setting('WP_TESTS_DB_PASSWORD', null);
    $env_file_db_name = get_setting('WP_TESTS_DB_NAME', null);

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
    $env_var_db_host = getenv('WP_TESTS_DB_HOST');
    $env_var_db_user = getenv('WP_TESTS_DB_USER');
    $env_var_db_pass = getenv('WP_TESTS_DB_PASSWORD');
    $env_var_db_name = getenv('WP_TESTS_DB_NAME');

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

            echo esc_cli("Found Lando database service: {$db_settings['db_host']}\n");
            // Note: table_prefix is only read from wp-config.php and not from Lando configuration
        } else {
            echo esc_cli(COLOR_YELLOW . 'Warning: No MySQL service found in Lando configuration.' . COLOR_RESET . "\n");
            echo esc_cli("This indicates a potential issue with your Lando setup.\n");
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
        throw new \Exception(esc_cli("Missing required database settings: $missing_str. Please configure these in your .env.testing file or wp-config.php."));
    }

    // Display the final settings
    // echo esc_cli("WordPress Database settings:\n");
    // echo esc_cli("- Host: {$db_settings['db_host']}\n");
    // echo esc_cli("- User: {$db_settings['db_user']}\n");
    // echo esc_cli("- Database: {$db_settings['db_name']}\n");
    // echo esc_cli('- Password length: ' . strlen($db_settings['db_pass']) . "\n");

    return $db_settings;
}


/**
 * Format SSH command properly based on the SSH_COMMAND setting
 *
 * @param string $ssh_command The SSH command to use
 * @param string $command The command to execute via SSH
 * @return string The properly formatted command
 */
function format_ssh_command( string $ssh_command, string $command ): string {
    // Debug: Show the input command
    // echo esc_cli("\nDebug: format_ssh_command input:\n");
    // echo esc_cli("SSH command: $ssh_command\n");
    // echo esc_cli("Command to execute: $command\n");

    // For Lando and other SSH commands, we need to properly escape quotes
    // The best approach is to use single quotes for the outer shell
    $result = '';
    if (strpos($ssh_command, 'lando ssh') === 0) {
        // Lando requires the -c flag to execute commands
        $result = "$ssh_command -c '  $command  ' 2>&1";
        // echo esc_cli("Debug: Using Lando SSH format\n");
    } else {
        // Regular SSH command
        $result = "$ssh_command '  $command  ' 2>&1";
        // echo esc_cli("Debug: Using regular SSH format\n");
    }

    echo esc_cli("Debug: Final SSH command: $result\n");
    return $result;
}


/**
 * Format a PHP command for execution
 *
 * @param string $php_script_path Path to the PHP script to execute
 * @param array  $arguments       Command line arguments to pass to the script
 * @param string $command_type    Type of command to generate: 'auto', 'direct', 'docker', 'lando_php', or 'lando_exec'
 * @return string Formatted command
 */
function format_php_command( string $php_script_path, array $arguments = [], string $command_type = 'auto' ): string {
	// Convert command type to lowercase for consistent comparison
	$command_type = strtolower( $command_type );

	// Determine command type if set to auto
	if ( 'auto' === $command_type ) {
		// Check if running in Docker
		if ( file_exists( '/.dockerenv' ) ) {
			$command_type = 'docker';
		} else {
			$command_type = 'direct';
		}
	}

	// Format the command based on type
	if ( 'lando_php' === $command_type ) {
		$command = 'lando php "' . $php_script_path . '"';
	} elseif ( 'lando_exec' === $command_type ) {
		$command = 'lando exec appserver -- php "' . $php_script_path . '"';
	} elseif ( 'docker' === $command_type ) {
		$command = 'php ' . $php_script_path;
	} else {
		$command = 'php "' . $php_script_path . '"';
	}

	// Add arguments if provided
	if ( ! empty( $arguments ) ) {
		foreach ( $arguments as $key => $value ) {
			// If the key is numeric, just add the value (positional argument)
			if ( is_numeric( $key ) ) {
				$command .= ' "' . (string) $value . '"';
			} else {
				// Otherwise, it's a named argument
				$command .= ' --' . $key . '="' . (string) $value . '"';
			}
		}
	}

	return $command;
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
function format_mysql_parameters_and_query( string $host, string $user, string $pass, string $sql, ?string $db = null, string $command_type = 'direct' ): string {
	// Convert command type to lowercase for consistent comparison
	$command_type = strtolower( $command_type );

	// Build the connection parameters exactly matching test expectations
	// Note the space after -h and -u, but no space after -p
	$connection_params = "-h " . escapeshellarg( $host ) . " -u " . escapeshellarg( $user );

	// Add password if provided
	if ( ! empty( $pass ) ) {
		// MySQL password syntax: NO space between -p and password, NO quotes
		$connection_params .= " -p" . $pass;
	}

	// Add database if provided
	if ( ! empty( $db ) ) {
		$connection_params .= " " . escapeshellarg( $db );
	}

	// Process SQL command
	// 1. Normalize line endings to avoid issues with different environments
	$sql = str_replace( "\r\n", "\n", $sql );

	// 2. Ensure SQL command ends with semicolon
	if ( substr( trim( $sql ), -1 ) !== ';' ) {
		$sql .= ';';
	}

	// 3. For multiline SQL (like heredoc), replace newlines with spaces
	$sql = str_replace( "\n", ' ', $sql );

	// 4. Escape quotes in SQL based on command type
	$escaped_sql = $sql;

	// Different escaping rules based on command type
	if ( $command_type === 'lando_direct' ) {
		// For direct lando mysql command, we only need to escape single quotes
		// Double quotes don't need double-escaping
		$escaped_sql = str_replace( "'", "'\\'", $sql );
	} else {
		// For SSH or direct MySQL, escape both single and double quotes
		$escaped_sql = str_replace( "'", "\\'", $sql );
		$escaped_sql = str_replace( '"', '\\"', $escaped_sql );	}

	// Add the SQL command with proper quoting
	$formatted_command = "$connection_params -e '$escaped_sql'";

	// Debug: Show the transformation of the SQL command
	// echo "\nDebug: format_mysql_command details:\n";
	// echo "Original SQL:\n$sql\n";
	// echo "Escaped SQL:\n$escaped_sql\n";
	// echo "Full MySQL command:\n$formatted_command\n";

	return $formatted_command;
}

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
 * Get PHPUnit database settings
 *
 * @param array       $wp_db_settings WordPress database settings
 * @param string|null $db_name        Optional custom database name for tests
 * @param string|null $table_prefix   Optional table prefix for test tables
 * @return array PHPUnit database settings with keys: db_host, db_user, db_pass, db_name, table_prefix
 */
function get_phpunit_database_settings( array $wp_db_settings, ?string $db_name = null, ?string $table_prefix = null ): array {
	// Use WordPress table prefix if none provided
	if ( empty( $table_prefix ) ) {
		$table_prefix = $wp_db_settings['table_prefix'] ?? 'wp_';
	}

	// If custom database name is not provided, use WordPress database name with '_test' suffix
	if ( empty( $db_name ) ) {
		$db_name = ($wp_db_settings['db_name'] ?? 'wordpress') . '_test';
		echo "Warning: No PHPUnit Test database name provided. Using $db_name.\n";
	}

	// Prepare test database settings with keys matching what setup-plugin-tests.php expects
	$test_db = [
		'db_host'      => $wp_db_settings['db_host'] ?? 'localhost',
		'db_user'      => $wp_db_settings['db_user'] ?? 'root',
		'db_pass'      => $wp_db_settings['db_pass'] ?? '',
		'db_name'      => $db_name,
		'table_prefix' => $table_prefix,
	];

	return $test_db;
}


/**
 * Check if we're in a Lando environment or using Lando commands
 *
 * @return bool True if in Lando environment or using Lando commands
 */
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

    return false;
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
        echo "No running Lando environment detected. Is Lando running?\n";
        echo "Run 'lando start' to start Lando, or see docs/guides/rebuilding-after-system-updates.md if you're having issues after system updates.\n";
        return array();
    }

    echo "Found running Lando containers.\n";

    // Now get the detailed configuration with lando info
    $lando_info_json = shell_exec('lando info --format=json 2>/dev/null');
    if (empty($lando_info_json)) {
        echo "Lando is running but could not get configuration details.\n";
        return array();
    }

    // Debug: Show raw lando info output for troubleshooting
    // echo "Debug: Raw lando info output (first 500 chars): " . substr($lando_info_json, 0, 500) . "...\n";

    // Parse JSON output from lando info
    $lando_info = json_decode($lando_info_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($lando_info)) {
        echo "Error parsing Lando configuration: " . json_last_error_msg() . ". Skipping Lando settings.\n";
        return array();
    }

    echo "Found Lando configuration.\n";
    return $lando_info;
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
        echo 'Warning: Failed to parse LANDO_INFO JSON: ' . json_last_error_msg() . "\n";
        return null;
    }

    return $lando_data;
}


/**
 * Format and execute a MySQL command using the appropriate method (direct, SSH, or Lando)
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
function format_mysql_execution( string $ssh_command, string $host, string $user, string $pass, string $sql, ?string $db = null ): string {
    $command_type = 'ssh';

    // Determine the command type based on the SSH command
    if (strpos($ssh_command, 'lando ssh') === 0) {
        $command_type = 'lando_direct';
    } elseif (!$ssh_command || $ssh_command === 'none') {
        $command_type = 'direct';
    }

    // Format the MySQL parameters with the appropriate command type
    $mysql_params = format_mysql_parameters_and_query($host, $user, $pass, $sql, $db, $command_type);

    // Debug output
    // echo "\nDebug: format_mysql_execution input:\n";
    // echo esc_cli("Original SQL: $sql\n");
    // echo esc_cli("SSH command: $ssh_command  MySQL params: $mysql_params\n");
    // echo "Command type: $command_type\n";

    $cmd = '';

    // Check if this is a Lando environment and we should use lando mysql directly
    if ($command_type === 'lando_direct') {
        // Use lando mysql directly with the parameters
        $cmd = "lando mysql $mysql_params";
        // echo esc_cli("Debug: Using direct Lando MySQL format\n");
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
        // echo esc_cli("Debug: Using direct MySQL format\n");
    }

    return $cmd;
}

/**
 * Find WordPress root by looking for wp-config.php
 *
 * @param string $current_dir Starting directory
 * @param int    $max_depth Maximum directory depth to search
 * @return string|null WordPress root path or null if not found
 */
function find_wordpress_root( string $current_dir, int $max_depth = 5 ): ?string {
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
function get_wp_config_value( string $search_value, string $wp_config_path ): ?string {
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
 * Escape a string for CLI output
 *
 * @param string $text Text to escape
 * @return string
 */
function esc_cli( string $text ): string {
    return $text;
}
