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

// Guard against being loaded more than once from different paths.
if (defined('WP_PHPUNIT_FRAMEWORK_FUNCTIONS_LOADED')) {
    echo "\n\nWP_PHPUNIT_FRAMEWORK_FUNCTIONS_LOADED already defined\n\n";
	return;
}
const WP_PHPUNIT_FRAMEWORK_FUNCTIONS_LOADED = true;

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

// If the file has already been loaded, do not load it again.
if (function_exists('WP_PHPUnit_Framework\colored_message')) {
    try {
        $reflection = new \ReflectionFunction('WP_PHPUnit_Framework\colored_message');
        $first_declared_in = $reflection->getFileName();
    } catch (\ReflectionException $e) {
        $first_declared_in = 'unknown location';
    }

    // Log to a predictable file in the temp directory.
    $log_file = sys_get_temp_dir() . '/phpunit-testing-framework-load.log';

    echo "Framework Functions: " . __FILE__ . " \ntemp log $log_file\n";

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $second_request_from = $backtrace[1]['file'] ?? 'unknown file';

    $log_message = sprintf(
        "NOTICE: In '%s', framework-functions.php already loaded from '%s'. Second attempt to load from '%s'. Ignoring duplicate.",
        __DIR__,
        $first_declared_in,
        $second_request_from
    );

    error_log($log_message . "\n", 3, $log_file);

    return; // Silently exit to prevent fatal error.
}

// Exit if accessed directly, should be run command line
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit;
}

/**
 * Print a colored message to the console
 * Does esc_cli the text
 *
 * @param string $message The message to print
 * @param string $color   The color to use (green, yellow, red, blue, purple, cyan, light_gray, white, normal)
 * @return void
 */
function colored_message(string $message, string $color = 'normal'): void {
	$colors = [
		'green'     => COLOR_GREEN,
		'yellow'    => COLOR_YELLOW,
		'red'       => COLOR_RED,
		'blue'      => COLOR_BLUE,
		'purple'    => COLOR_MAGENTA,
        'magenta'    => COLOR_MAGENTA,
		'cyan'      => COLOR_CYAN,
		'light_gray' => COLOR_WHITE,
		'white'     => COLOR_WHITE,
		'normal'    => COLOR_RESET,
	];
	$color = strtolower($color);
		$start_color = isset($colors[$color]) ? $colors[$color] : $colors['normal'];
	$end_color   = COLOR_RESET;
	echo esc_cli($start_color . $message . $end_color . "\n");
}


// Global exception handler to catch and display any uncaught exceptions
set_exception_handler(
    function ( \Throwable $e ): void {
		colored_message('UNCAUGHT EXCEPTION: ' . get_class($e), 'red');
		colored_message('Message: ' . $e->getMessage(), 'red');
		colored_message('File: ' . $e->getFile() . ' (Line ' . $e->getLine() . ')', 'red');
		colored_message('Stack trace:', 'red');
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
 * Finds the project root directory by searching upwards from a starting directory for a marker file.
 *
 * @param string $start_dir The directory to start searching from.
 * @param string $marker The marker file to look for (e.g., 'composer.json', '.git').
 * @return string|null The path to the project root, or null if not found.
 */
function find_project_root(string $start_dir, string $marker = 'composer.json'): ?string {
    $dir = $start_dir;
    while ($dir !== '/' && $dir !== '.' && !empty($dir)) {
        if (file_exists($dir . DIRECTORY_SEPARATOR . $marker)) {
            return $dir;
        }
        $parent = dirname($dir);
        if ($parent === $dir) { // Reached fs root and not found.
            return null;
        }
        $dir = $parent;
    }
    return null;
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
 * @paramls string $config_file_name Name of the configuration file (default: '.env.testing')
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

        // Safely extract only DB definitions and table_prefix to avoid loading all of WordPress
        $pattern = "/(define\s*\(\s*['\"](DB_NAME|DB_USER|DB_PASSWORD|DB_HOST)['\"].*?;|\$table_prefix\s*=\s*['\"].*?['\"];)/m";
        preg_match_all($pattern, $config_content, $matches);

        if (!empty($matches[0])) {
            $temp_content = "<?php\n" . implode("\n", $matches[0]);
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

/**
 * Displays instructions for running tests via Composer and the sync script.
 *
 * This should be renamed, since got rid of "composer test:*", only using sync-and-test.php
 *
 * @since 1.0.0
 *
 * @param bool $is_lando Whether this is a Lando environment.
 */
function display_composer_test_instructions( bool $is_lando, string $plugin_dir ): void {
	$lando_prefix = $is_lando ? 'lando ' : '';

	// In a Lando environment, the user runs `lando` commands from the project root on their host machine.
	// The $plugin_dir should be the path on the filesystem.
	$cd_command = "cd " . escapeshellarg( $plugin_dir );

	colored_message( "\nTo run the tests, execute these commands from your terminal:", 'yellow' );
    colored_message( "1. Navigate to your plugin directory:", 'cyan' );
    $cd_command = "cd " . escapeshellarg( dirname(__DIR__ ));
	colored_message( "   $cd_command", 'light_gray' );
	$sync_script_path = 'bin/sync-and-test.php';
	colored_message( "   php $sync_script_path --integration # For integration tests", 'light_gray' );
	colored_message( "   php $sync_script_path --unit        # For unit tests", 'light_gray' );
	colored_message( "   php $sync_script_path --wp-mock     # For WP-Mock tests", 'light_gray' );
	colored_message( "   php $sync_script_path --all         # To run all test types\n", 'light_gray' );
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

    // Show debug output only when --debug flag is set
    if (has_cli_flag(['--debug'])) {
        colored_message("Debug: Final SSH command: $result", 'blue');
    }
    return $result;
}

/**
 * Logs a message to the console and a specified log file.
 *
 * This function displays a colored and icon-prefixed message to the console and
 * writes a timestamped, uncolored version to the log file defined by the
 * 'TEST_ERROR_LOG' environment setting.
 *
 * @param string $message The message to log.
 * @param string $type    The type of message (info, success, warning, error, debug).
 *                        Determines the icon and color of the console output.
 * @param bool   $display_on_console Whether to display the message on the console. Default is true.
 * @return void
 */
function log_message(string $message, string $type = 'info', bool $display_on_console = true): void {
    $type = strtolower($type);
    $icons = [
        'info'    => 'ℹ️ ',
        'success' => '✅ ',
        'warning' => '⚠️ ',
        'error'   => '❌ ',
        'debug'   => '🐞 ',
    ];
    $colors = [
        'info'    => 'cyan',
        'success' => 'green',
        'warning' => 'yellow',
        'error'   => 'red',
        'debug'   => 'purple',
    ];

    $icon = $icons[$type] ?? 'ℹ️ ';
    $color = $colors[$type] ?? 'normal';

    // Console output
    if ($display_on_console) {
        colored_message($icon . $message, $color);
    }

    // File logging
    $log_file = get_setting('TEST_ERROR_LOG', '/tmp/phpunit-testing.log');
    if ($log_file) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = sprintf(
            "[%s] [%s]: %s\n",
            $timestamp,
            strtoupper($type),
            $message
        );
        // The '3' means append to the file specified in the third argument.
        error_log($log_entry, 3, $log_file);
    }
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
        // Per Lando best practices, use lando exec appserver -- php for all Lando PHP execution
        $base_command = 'lando exec appserver -- php';
        $args = array_merge([$php_script_path], $arguments);
        $command = $base_command;
        foreach ($args as $arg) {
            $escaped_arg = str_replace('"', '\"', $arg);
            $command .= ' "' . $escaped_arg . '"';
        }
	} elseif ( 'lando_exec' === $command_type ) {
        // Same as above, but explicit for lando_exec
        $base_command = 'lando exec appserver -- php';
        $args = array_merge([$php_script_path], $arguments);
        $command = $base_command;
        foreach ($args as $arg) {
            $escaped_arg = str_replace('"', '\"', $arg);
            $command .= ' "' . $escaped_arg . '"';
        }

	} elseif ( 'docker' === $command_type ) {
		$command = 'php ' . $php_script_path;
	} else {
		$command = 'php "' . $php_script_path . '"';
	}

	// Add arguments if provided
    if ( 'docker' === $command_type || 'direct' === $command_type ) {
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
	}

	// Show the constructed command when debug flag is set
	if (has_cli_flag(['--debug'])) {
		colored_message("Debug: Constructed PHP command: " . $command, 'blue');
	}

	return $command;
}

/**
 * Formats a general-purpose Lando exec command for execution.
 *
 * This function is for running non-PHP commands like composer, npm, or wp-cli.
 * It correctly constructs the `lando exec` command with the service name and the '--' separator,
 * and it safely escapes all arguments.
 *
 * @param array  $command_parts An array of the command and its arguments. Can be provided as separate parts
 *                              (e.g., ['composer', 'install', '--no-dev']) or as a single string
 *                              (e.g., ['composer install --no-dev']).
 * @param string $service       The Lando service to run the command on. Defaults to 'appserver'.
 * @param string $debug_flag    Optional debug flag to add to the command when --debug is set (e.g., '--verbose', '-vvv').
 * @return string The formatted Lando exec command string.
 */
function format_lando_exec_command(array $command_parts, string $service = 'appserver', string $debug_flag = ''): string
{
    // If the command is passed as a single string in the array, split it into parts.
    if (count($command_parts) === 1
    && strpos($command_parts[0], ' ') !== false) {
        $command_parts = explode(' ', $command_parts[0]);
    }

    // Escape each part of the command to prevent shell injection issues.
    $escaped_parts = array_map('escapeshellarg', $command_parts);

    // Add debug flag if --debug is set and a debug flag was provided
    if (!empty($debug_flag) && has_cli_flag(['--debug'])) {
        $escaped_parts[] = escapeshellarg($debug_flag);
    }

    $command_string = implode(' ', $escaped_parts);

    // Return the full lando exec command.
    // Note: The service name is NOT escaped here because it's a controlled value (e.g., 'appserver', 'database')
    // and not user input. Escaping it would add quotes that break the lando command.
    $full_command = sprintf('lando exec %s -- %s', $service, $command_string);

    // Show the constructed command when debug flag is set
    if (has_cli_flag(['--debug'])) {
        colored_message("Debug: Constructed Lando exec command: " . $full_command, 'blue');
    }

    return $full_command;
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
    if ( $command_type === 'lando_direct' ) {
        // don't include host
    	$connection_params = " -u " . escapeshellarg( $user );
    } else {
	$connection_params = "-h " . escapeshellarg( $host ) . " -u " . escapeshellarg( $user );
    }

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
	// echo "\nDebug: mysql parameters and query details:\n";
	// echo "Original SQL:\n$sql\n";
	// echo "Escaped SQL:\n$escaped_sql\n";
	// echo "Full MySQL command:\n$formatted_command\n";

	return $formatted_command;
}

/**
 * Future likely enhancement. Needs to be designed to cover all environments using, and all parameters it might have to handle
 *
 * Executes a given command in the target environment (local or via SSH).
 *
 * @param string $ssh_command The SSH command string from settings (e.g., 'none', 'ssh', 'lando ssh appserver --').
 * @param string $command_to_run The actual command to execute (e.g., 'which mysql').
 * @return array An array containing 'output' (array of lines) and 'status' (int exit code).
 */
function execute_command_in_target_env(string $ssh_command, string $command_to_run): array {
    $full_command_string = '';
    $output_lines = [];
    $exit_status = -1;

    // The command_to_run is simple (e.g., 'which mysql'), so direct use is generally safe here.
    // For more complex commands with user input, more robust escaping would be needed.

    if (empty($ssh_command) || $ssh_command === 'none' || $ssh_command === 'ssh') {
        // Execute directly on the current host or if already in an SSH session.
        // 'ssh' implies we are already in the target environment.
        $full_command_string = $command_to_run;
    } else {
        // Prepend SSH command. We need to ensure the $command_to_run is executed by the remote shell.
        // $ssh_command might be 'lando ssh appserver --' or 'ssh user@host'.
        // The command needs to be quoted for the remote shell.
        // Using bash -c ensures that the command is interpreted by a shell, which handles 'which' correctly.
        $escaped_command_for_bash_c = escapeshellarg($command_to_run);
        $full_command_string = $ssh_command . " bash -c " . $escaped_command_for_bash_c;
    }

    // Show command being executed when debug flag is set
    if (has_cli_flag(['--debug'])) {
        colored_message("Debug: Executing in target env: " . $full_command_string, 'blue');
    }

    exec($full_command_string . ' 2>&1', $output_lines, $exit_status); // Capture stderr too

    return ['output' => $output_lines, 'status' => $exit_status];
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
 * Output a debug message if verbose/debug mode is enabled
 *
 * @param string $message The message to output
 * @param bool $force_output Whether to force output even if not in verbose mode
 * @return void
 */
function debug_message(string $message, bool $force_output = false): void {
    static $is_verbose = null;

    $verbosity_flags = ['--verbose', '-v', '-vv', '-vvv', '--debug'];

    // Initialize $is_verbose only once
    if ($is_verbose === null) {
        $is_verbose = get_setting('VERBOSE', false) || has_cli_flag($verbosity_flags);
    }
    if ($is_verbose || $force_output) {
        colored_message("[DEBUG] " . $message, 'cyan');
    }
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

    // Show raw lando info output for troubleshooting when debug flag is set
    if (has_cli_flag(['--debug'])) {
        colored_message("Debug: Raw lando info output:", 'blue');
        colored_message($lando_info_json, 'blue');
    }

    // Parse JSON output from lando info
    $lando_info = json_decode($lando_info_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($lando_info)) {
        colored_message( 'Error parsing Lando configuration: ' . json_last_error_msg() . '. Skipping Lando settings.', 'red' );
        return array();
    }

    colored_message( 'Found Lando configuration.', 'green' );
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
    // Determine the command type based on the SSH command
    if (strpos($ssh_command, 'lando ssh') === 0) {
        $command_type = 'lando_direct';
    } elseif (empty($ssh_command) || $ssh_command === 'none') { // Using empty() for clarity
        $command_type = 'direct';
    } else { // Handles all other cases, making 'ssh' explicit
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
 * Should this be modified to echo $text, like colored_message and debug_message do?
 *
 * @param string $text Text to escape
 * @return string
 */
function esc_cli( string $text ): string {
    return $text;
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

/**
 * Gets the value of a command-line argument.
 *
 * This function can retrieve values from arguments in both `--option=value` and
 * `--option value` formats.
 *
 * @param string|array $flags       A single flag (e.g., '--file') or an array of aliases (e.g., ['--file', '-f']).
 * @param array|null   $source_argv Optional source array of arguments. Defaults to the global $argv.
 * @return string|null The value of the argument, or null if the flag is not found.
 */
function get_cli_value(string|array $flags, ?array $source_argv = null): ?string {
    $argv = $source_argv ?? $GLOBALS['argv'] ?? [];
    $flags = (array) $flags;

    foreach ($argv as $i => $arg) {
        // Check for --option=value format
        foreach ($flags as $flag) {
            if (strpos($arg, $flag . '=') === 0) {
                return substr($arg, strlen($flag) + 1);
            }
        }

        // Check for --option value format
        if (in_array($arg, $flags, true)) {
            // Ensure the next element exists and is not another flag
            if (isset($argv[$i + 1]) && strpos($argv[$i + 1], '-') !== 0) {
                return $argv[$i + 1];
            }
            // The flag was present but had no value
            return '';
        }
    }

    return null;
}
