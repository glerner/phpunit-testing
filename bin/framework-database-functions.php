<?php
/**
 * PHPUnit Testing Framework Database Functions
 *
 * This file contains all database-related functions for the PHPUnit Testing Framework.
 * Functions are extracted from test-mysql-escaping.php and framework-functions.php.
 *
 * @package WP_PHPUnit_Framework
 */

namespace WP_PHPUnit_Framework;

use WP_PHPUnit_Framework\View\Database_Test_View;

// Prevent direct access
if (!defined('ABSPATH') && !defined('WP_PHPUNIT_FRAMEWORK')) {
    define('WP_PHPUNIT_FRAMEWORK', true);
}

// Include required files if not already included
if (!function_exists('WP_PHPUnit_Framework\\colored_message')) {
    require_once __DIR__ . '/framework-functions.php';
}

// Define constants for quotes to avoid escaping issues
if (!defined('DOUBLE_QUOTE')) {
    define('DOUBLE_QUOTE', '"');
}
if (!defined('SINGLE_QUOTE')) {
    define('SINGLE_QUOTE', "'");
}

/**
 * Execute a MySQL prepared statement in a Lando environment
 *
 * @param string $query SQL query with placeholders
 * @param array $params Array of parameter values
 * @param array $types Array of parameter types ('s' for string, 'i' for integer, 'd' for double, 'b' for blob)
 * @param array $db_settings Database connection settings
 * @param string|null $db_name Optional database name
 * @param Database_Test_View|null $view Optional view object for output
 * @return array Standardized response array with success/error information
 */
function execute_mysqli_prepared_statement_lando(
    string $query,
    array $params,
    array $types,
    array $db_settings,
    ?string $db_name = null,
    ?Database_Test_View $view = null
): array {
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
            if ($view) {
                // Show full paths only in debug mode, otherwise just show filenames
                if (has_cli_flag(['--debug', '-d'])) {
                    $view->display_debug('Temporary Files Created', [
                        'php_file' => $temp_php_file,
                        'output_file' => $output_file,
                        'error_file' => $error_file,
                        'container_output_file' => $container_output_file,
                        'container_error_file' => $container_error_file
                    ]);
                } else {
                    $view->display_debug('Temporary Files Created', [
                        'php_file' => basename($temp_php_file),
                        'output_file' => basename($output_file),
                        'error_file' => basename($error_file),
                        'note' => 'Use --debug flag to see full paths'
                    ]);
                }
            } else {
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
        if ($view) {
            $view->error_message($e->getMessage());
        }
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
 * Helper function to get temporary file paths for MySQL execution
 *
 * @param string $prefix Prefix for temporary files
 * @return array Array containing filesystem and container paths
 */
function get_temp_file_paths(string $prefix = 'temp_mysql_'): array {
    // Create a unique temporary file name
    $temp_file = uniqid($prefix, true);

    // Get the system temporary directory
    $temp_dir = sys_get_temp_dir();

    // Ensure the temporary directory path ends with a slash
    if (substr($temp_dir, -1) !== DIRECTORY_SEPARATOR) {
        $temp_dir .= DIRECTORY_SEPARATOR;
    }

    // Create paths for the host filesystem
    $filesystem_paths = [
        'php' => $temp_dir . $temp_file . '.php',
        'output' => $temp_dir . $temp_file . '.output',
        'error' => $temp_dir . $temp_file . '.error',
    ];

    // Create paths for the container
    // In Lando, /tmp is mapped to the same location inside the container
    $container_paths = [
        'php' => '/tmp/' . basename($filesystem_paths['php']),
        'output' => '/tmp/' . basename($filesystem_paths['output']),
        'error' => '/tmp/' . basename($filesystem_paths['error']),
    ];

    return [
        'filesystem' => $filesystem_paths,
        'container' => $container_paths,
        'temp_file' => $temp_file,
    ];
}

/**
 * Get database settings from WordPress configuration or environment variables
 *
 * This function attempts to read database settings from various sources in the following order:
 * 1. wp-config.php (if available)
 * 2. Environment variables
 * 3. Config files
 * 4. Lando configuration
 *
 * @param bool $use_env Whether to use environment variables
 * @param bool $use_config Whether to use config files
 * @param bool $use_lando Whether to use Lando configuration
 * @param bool $use_wp_config Whether to use wp-config.php
 * @param string|null $wp_config_path Optional path to wp-config.php
 * @param Database_Test_View|null $view Optional view object for output
 * @return array Database settings array with host, user, password, and name
 */
function get_database_settings(
    bool $use_env = true,
    bool $use_config = true,
    bool $use_lando = true,
    bool $use_wp_config = true,
    ?string $wp_config_path = null,
    ?Database_Test_View $view = null
): array {
    $db_settings = [
        'db_host' => '',
        'db_user' => '',
        'db_pass' => '',
        'db_name' => '',
        'source' => 'default',
    ];

    // Try to get settings from wp-config.php
    if ($use_wp_config) {
        if ($view) {
            $view->debug_message("Attempting to read database settings from wp-config.php...");
        }

        // Find wp-config.php
        if ($wp_config_path === null) {
            // Look for wp-config.php in the current directory and up to 5 levels up
            $max_levels = 5;
            $current_dir = getcwd();
            $wp_config_found = false;

            for ($i = 0; $i <= $max_levels; $i++) {
                $test_path = $current_dir . '/wp-config.php';
                if (file_exists($test_path)) {
                    $wp_config_path = $test_path;
                    $wp_config_found = true;
                    break;
                }

                // Move up one directory
                $parent_dir = dirname($current_dir);
                if ($parent_dir === $current_dir) {
                    // We've reached the root directory
                    break;
                }
                $current_dir = $parent_dir;
            }

            if (!$wp_config_found) {
                if ($view) {
                    $view->warning_message("Could not find wp-config.php in the current directory or parent directories.");
                }
            }
        }

        if ($wp_config_path !== null && file_exists($wp_config_path)) {
            if ($view) {
                $view->display_message("Found wp-config.php at: {$wp_config_path}");
            }

            // Extract database settings from wp-config.php
            $wp_config_content = file_get_contents($wp_config_path);
            if ($wp_config_content !== false) {
                // Extract DB_HOST
                if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $wp_config_content, $matches)) {
                    $db_settings['db_host'] = $matches[1];
                }

                // Extract DB_USER
                if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $wp_config_content, $matches)) {
                    $db_settings['db_user'] = $matches[1];
                }

                // Extract DB_PASSWORD
                if (preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $wp_config_content, $matches)) {
                    $db_settings['db_pass'] = $matches[1];
                }

                // Extract DB_NAME
                if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $wp_config_content, $matches)) {
                    $db_settings['db_name'] = $matches[1];
                }

                // Check if all settings were found
                if (!empty($db_settings['db_host']) && !empty($db_settings['db_user']) && !empty($db_settings['db_pass']) && !empty($db_settings['db_name'])) {
                    $db_settings['source'] = 'wp-config.php';
                    if ($view) {
                        $view->success_message("Successfully read database settings from wp-config.php");
                    }
                    return $db_settings;
                } else {
                    if ($view) {
                        $view->warning_message("Could not extract all database settings from wp-config.php");
                    }
                }
            } else {
                if ($view) {
                    $view->warning_message("Could not read wp-config.php file");
                }
            }
        }
    }

    // Try to get settings from environment variables
    if ($use_env) {
        if ($view) {
            $view->debug_message("Attempting to read database settings from environment variables...");
        }

        $env_host = getenv('DB_HOST');
        $env_user = getenv('DB_USER');
        $env_pass = getenv('DB_PASSWORD');
        $env_name = getenv('DB_NAME');

        if ($env_host !== false && $env_user !== false && $env_pass !== false && $env_name !== false) {
            $db_settings['db_host'] = $env_host;
            $db_settings['db_user'] = $env_user;
            $db_settings['db_pass'] = $env_pass;
            $db_settings['db_name'] = $env_name;
            $db_settings['source'] = 'environment';

            if ($view) {
                $view->success_message("Successfully read database settings from environment variables");
            }
            return $db_settings;
        } else {
            if ($view) {
                $view->warning_message("Could not find all required database settings in environment variables");
            }
        }
    }

    // Try to get settings from .env file
    if ($use_config) {
        if ($view) {
            $view->debug_message("Attempting to read database settings from .env file...");
        }

        // Look for .env file in the current directory and up to 5 levels up
        $max_levels = 5;
        $current_dir = getcwd();
        $env_file_path = null;

        for ($i = 0; $i <= $max_levels; $i++) {
            $test_path = $current_dir . '/.env';
            if (file_exists($test_path)) {
                $env_file_path = $test_path;
                break;
            }

            // Move up one directory
            $parent_dir = dirname($current_dir);
            if ($parent_dir === $current_dir) {
                // We've reached the root directory
                break;
            }
            $current_dir = $parent_dir;
        }

        if ($env_file_path !== null) {
            if ($view) {
                $view->display_message("Found .env file at: {$env_file_path}");
            }

            // Parse .env file
            $env_content = file_get_contents($env_file_path);
            if ($env_content !== false) {
                $lines = explode("\n", $env_content);
                $env_vars = [];

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) {
                        continue; // Skip comments and empty lines
                    }

                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $key = trim($parts[0]);
                        $value = trim($parts[1]);
                        // Remove quotes if present
                        $value = trim($value, DOUBLE_QUOTE . SINGLE_QUOTE);
                        $env_vars[$key] = $value;
                    }
                }

                // Check for database settings
                if (isset($env_vars['DB_HOST']) && isset($env_vars['DB_USER']) &&
                    isset($env_vars['DB_PASSWORD']) && isset($env_vars['DB_NAME'])) {
                    $db_settings['db_host'] = $env_vars['DB_HOST'];
                    $db_settings['db_user'] = $env_vars['DB_USER'];
                    $db_settings['db_pass'] = $env_vars['DB_PASSWORD'];
                    $db_settings['db_name'] = $env_vars['DB_NAME'];
                    $db_settings['source'] = '.env file';

                    if ($view) {
                        $view->success_message("Successfully read database settings from .env file");
                    }
                    return $db_settings;
                } else {
                    if ($view) {
                        $view->warning_message("Could not find all required database settings in .env file");
                    }
                }
            } else {
                if ($view) {
                    $view->warning_message("Could not read .env file");
                }
            }
        } else {
            if ($view) {
                $view->warning_message("Could not find .env file");
            }
        }
    }

    // Try to get settings from Lando configuration
    if ($use_lando) {
        if ($view) {
            $view->debug_message("Attempting to read database settings from Lando configuration...");
        }

        // Check if we're running in a Lando environment
        if (getenv('LANDO') === 'ON' || getenv('LANDO_INFO')) {
            // Get Lando info
            $lando_info = getenv('LANDO_INFO');
            if ($lando_info) {
                $lando_config = json_decode($lando_info, true);
                if ($lando_config && isset($lando_config['database'])) {
                    $db_config = $lando_config['database'];

                    // Extract database settings
                    if (isset($db_config['internal_connection']['host']) &&
                        isset($db_config['creds']['user']) &&
                        isset($db_config['creds']['password']) &&
                        isset($db_config['creds']['database'])) {

                        $db_settings['db_host'] = $db_config['internal_connection']['host'];
                        $db_settings['db_user'] = $db_config['creds']['user'];
                        $db_settings['db_pass'] = $db_config['creds']['password'];
                        $db_settings['db_name'] = $db_config['creds']['database'];
                        $db_settings['source'] = 'lando';

                        if ($view) {
                            $view->success_message("Successfully read database settings from Lando configuration");
                        }
                        return $db_settings;
                    } else {
                        if ($view) {
                            $view->warning_message("Could not find all required database settings in Lando configuration");
                        }
                    }
                } else {
                    if ($view) {
                        $view->warning_message("Could not parse Lando configuration or database section not found");
                    }
                }
            } else {
                if ($view) {
                    $view->warning_message("LANDO_INFO environment variable not found");
                }
            }
        } else {
            if ($view) {
                $view->warning_message("Not running in a Lando environment");
            }
        }
    }

    // If we get here, we couldn't find any database settings
    if ($view) {
        $view->error_message("Could not find database settings from any source");
    }

    // Return default settings
    $db_settings['source'] = 'default';
    return $db_settings;
}


if (!function_exists('WP_PHPUnit_Framework\create_db_response')) {
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
}

if (!function_exists('WP_PHPUnit_Framework\execute_mysqli_direct')) {
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

        $view = new Database_Test_View();

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
                    $view->display_message("\n▶ Executing statement #$statement_count", 'yellow');
                }

                // Check for errors after each statement
                if ($mysqli->error) {
                    $has_error = true;
                    $error_message = 'Query failed in statement #' . $statement_count . ': ' . $mysqli->error;

                    if ($debug) {
                        $view->display_message("\n❌ ERROR: $error_message", 'red');
                    }

                    throw new \RuntimeException($error_message, 2, null);
                }

                // Process result set if available
                $had_rows = false;
                $rows_before = count($data);
                if ($result = $mysqli->store_result()) {
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row;
                    }
                    $meta['num_rows'] = $result->num_rows;
                    $had_rows = ($result->num_rows > 0);

                    // Debug output for successful result set
                    if ($debug) {
                        $view->display_message("  ✅ Result set with {$result->num_rows} row(s)", 'green');
                    }

                    $result->free();
                } else if ($mysqli->affected_rows > 0 && $debug) {
                    // Debug output for statements with affected rows but no result set
                    $view->display_message("  ✅ Statement affected {$mysqli->affected_rows} row(s)", 'green');
                } else if ($debug) {
                    // Debug output for statements with no result set and no affected rows
                    $view->display_message("  ✅ Statement executed successfully", 'green');
                }

                // Store statement-specific metadata
                $meta['statements'][] = [
                    'index' => $statement_count,
                    'affected_rows' => $mysqli->affected_rows,
                    'insert_id' => $mysqli->insert_id,
                    'error' => $mysqli->error ?: null,
                    'statement_type' => ($had_rows ? 'query' : 'non-query')
                ];

            // @phpstan-ignore-next-line - runtime-controlled multi-statement iteration
            } while (!$has_error && $mysqli->more_results() && $mysqli->next_result());

            // Final error check after all statements
            // @phpstan-ignore-next-line - defensive check for late errors
            if ($mysqli->error && !$has_error) {
                $error_message = 'Query failed after processing: ' . $mysqli->error;

                if ($debug) {
                    $view->display_message("\n❌ FINAL ERROR: $error_message", 'red');
                }

                throw new \RuntimeException($error_message, 2, null);
            }

            // Debug summary of all statements
            if ($debug) {
                $view->display_message("\n📋 Processed $statement_count statement(s) in total", 'cyan');
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
}

// Additional database functions will be moved here from test-mysql-escaping.php

if (!function_exists('WP_PHPUnit_Framework\mysqli_create_database')) {
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
     *     @type string $db_host    Database host
     *     @type string $db_root_pass Optional. Root password for non-Lando environments
     * }
     * @param Database_Test_View|null $view View object for output (optional, creates new instance if null)
     * @return array Standardized response array with success/error information
     */
    function mysqli_create_database(array $connection_settings, ?Database_Test_View $view = null): array {
        // Use provided view or create a new one
        if ($view === null) {
            $view = new Database_Test_View();
        }

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
                $view->display_message("\n🔍 Debug: Lando Info Structure:", 'blue');
                $view->display_message(print_r($lando_info, true), 'blue');
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
                $view->display_message("Enter MySQL root password (leave empty if no password): ", 'normal');
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
}

if (!function_exists('WP_PHPUnit_Framework\mysqli_create_user')) {
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
     * @param Database_Test_View|null $view View object for output (optional, creates new instance if null)
     * @return array Standardized response array with success/error information
     */
    function mysqli_create_user(array $user_settings, ?Database_Test_View $view = null): array {
        // Use provided view or create a new one
        if ($view === null) {
            $view = new Database_Test_View();
        }

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
}

if (!function_exists('WP_PHPUnit_Framework\format_mysql_parameters_and_query')) {
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
            // Not sure what this should be, untested
            $params[] = "-e " . escapeshellarg($sql);
        }

        return implode(' ', $params);
    }
}

if (!function_exists('WP_PHPUnit_Framework\format_mysql_execution')) {
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
     * @param Database_Test_View|null $view View object for output (optional, creates new instance if null)
     * @return string The fully formatted command ready to execute
     * @throws \Exception If the command type is invalid.
     */
    function format_mysql_execution(string $ssh_command, string $host, string $user, string $pass, string $sql, ?string $db = null, ?Database_Test_View $view = null): string {
        // Use provided view or create a new one
        if ($view === null) {
            $view = new Database_Test_View();
        }

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
}

if (!function_exists('WP_PHPUnit_Framework\is_valid_identifier')) {
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
}

if (!function_exists('WP_PHPUnit_Framework\execute_mysql_via_ssh')) {
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
    function execute_mysql_via_ssh(string $host, string $user, string $pass, string $sql, ?string $db_name = null, ?Database_Test_View $view = null): array {
        global $db_settings, $db_manager;

        // Use provided view or create a new one
        if ($view === null) {
            $view = new Database_Test_View();
        }

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
                    $return_var
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
                $trimmed = trim($output_str);
                if ($trimmed !== '') {
                    $lines = explode("\n", $trimmed);
                    $headers = str_getcsv($lines[0], "\t");
                    $meta['num_rows'] = max(count($lines) - 1, 0); // Subtract header row safely

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
}

if (!function_exists('WP_PHPUnit_Framework\execute_mysqli_prepared_statement_direct')) {
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
     * @param Database_Test_View|null $view View object for output (optional, creates new instance if null)
     * @return array Standardized response array with success/error information
     */
    function execute_mysqli_prepared_statement_direct(string $host, string $user, string $pass, string $query, array $params, array $types, ?string $db_name = null, ?Database_Test_View $view = null): array {
        global $db_settings, $db_manager;

        // Use provided view or create a new one
        if ($view === null) {
            $view = new Database_Test_View();
        }

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

            // Display debug information about the prepared statement
            if (has_cli_flag(['--debug', '-d', '--verbose', '-v'])) {
                $view->display_debug('Executing MySQL Prepared Statement', [
                    'host' => $host,
                    'user' => $user,
                    'database' => $db_name ?: '[NONE]',
                    'params_count' => count($params),
                    'query' => $query
                ]);
            }

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

            // Display results using the view
            if (has_cli_flag(['--debug', '-d', '--verbose', '-v'])) {
                $view->display_message("✅ Prepared statement executed successfully", 'green');
                if ($meta['affected_rows'] > 0) {
                    $view->display_message("Rows affected: {$meta['affected_rows']}", 'green');
                }
                if ($meta['num_rows'] > 0) {
                    $view->display_message("Rows returned: {$meta['num_rows']}", 'green');
                    if (!empty($data)) {
                        $view->display_sql_result(['data' => $data, 'meta' => $meta]);
                    }
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
            // Display error using the view
            $view->display_message("❌ Error: {$e->getMessage()}", 'red');

            return create_db_response(
                false,
                null,
                $e->getMessage(),
                $e->getCode() ?: 'unknown_error'
            );
        }
    }
}

if (!function_exists('WP_PHPUnit_Framework\debug_sql')) {
    /**
     * Output SQL debugging information
     *
     * @param string $sql SQL query
     * @param array $params Parameters for the query
     * @param array|null $result Result of the query
     * @return void
     */
    function debug_sql(string $sql, array $params, ?array $result = null): void {
        if (!has_cli_flag(['--debug', '-d'])) {
            return;
        }

        $view = new Database_Test_View();
        static $query_count = 0;
        $query_count++;

        $view->display_message("\n" . str_repeat('=', 80), 'normal');
        $view->display_message("SQL DEBUG #$query_count - " . ($result ? 'RESULT' : 'QUERY'), 'cyan');
        $view->display_message(str_repeat('-', 80), 'normal');

        if (!$result) {
            // Start of query
            $view->display_message(trim($sql), 'normal');
            $view->display_message("Host: " . ($params['host'] ?? 'default'), 'normal');
            $view->display_message("User: " . ($params['user'] ?? 'default'), 'normal');
            if (!empty($params['db'])) {
                $view->display_message("DB: " . $params['db'], 'normal');
            }
        } else {
            // Query results
            if (isset($result['error'])) {
                $view->display_message("ERROR: " . $result['error'], 'red');
                return;
            }

            $num_rows = $result['meta']['num_rows'] ?? 0;
            $affected_rows = $result['meta']['affected_rows'] ?? 0;

            if ($affected_rows > 0) {
                $view->display_message("✅ Rows affected: " . $affected_rows, 'green');
            }

            if ($num_rows > 0) {
                $display_count = min(10, $num_rows);
                $view->display_message("📊 Rows returned: " . $num_rows . " (showing first " . $display_count . " rows)\n", 'green');

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
                    $header_line = '';
                    foreach ($headers as $header) {
                        $header_line .= str_pad(substr($header, 0, $col_widths[$header]), $col_widths[$header] + 2);
                    }
                    $view->display_message($header_line, 'normal');
                    $view->display_message(str_repeat('-', array_sum($col_widths) + (count($headers) * 2)), 'normal');

                    // Print rows (up to 10)
                    $count = 0;
                    foreach ($result['data'] as $row) {
                        if ($count++ >= 10) break;
                        $row_line = '';
                        foreach ($headers as $header) {
                            $value = $row[$header] ?? '';
                            if (strlen($value) > 50) {
                                $value = substr($value, 0, 47) . '...';
                            }
                            $row_line .= str_pad($value, $col_widths[$header] + 2);
                        }
                        $view->display_message($row_line, 'normal');
                    }

                    if ($num_rows > 10) {
                        $view->display_message("\n... and " . ($num_rows - 10) . " more rows", 'normal');
                    }
                }
            }
        }
        $view->display_message(str_repeat('=', 80) . "\n", 'normal');
    }
}

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
    $view = new Database_Test_View();
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
            $view->display_message("\n🔍 DEBUG: Temporary files created:\n", 'cyan');

            // Show full paths only in debug mode, otherwise just show filenames
            if (has_cli_flag(['--debug', '-d'])) {
                $view->display_message("  PHP File: {$temp_php_file}", 'normal');
                $view->display_message("  Output File: {$output_file}", 'normal');
                $view->display_message("  Error File: {$error_file}", 'normal');
                $view->display_message("  Container Output File: {$container_output_file}", 'normal');
                $view->display_message("  Container Error File: {$container_error_file}", 'normal');
            } else {
                $view->display_message("  PHP File: " . basename($temp_php_file), 'normal');
                $view->display_message("  Output File: " . basename($output_file), 'normal');
                $view->display_message("  Error File: " . basename($error_file), 'normal');
                $view->display_message("  (Use --debug flag to see full paths)", 'normal');
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
EOD;

        // Write the PHP code to the temporary file
        if (file_put_contents($temp_php_file, $php_code) === false) {
            throw new \RuntimeException('Failed to create temporary PHP file', 100, null);
        }

        // Debug output for generated PHP code (only shown with both --debug and --verbose flags)
        if (has_cli_flag(['--debug', '-d']) && has_cli_flag(['--verbose', '-v'])) {
            $view->display_message("\n🔍 DEBUG: Generated PHP Code for Lando Execution", 'cyan');
            $view->display_message(str_repeat('-', 80), 'cyan');
            $view->display_message($php_code . "\n", 'normal');
            $view->display_message(str_repeat('-', 80), 'cyan');

            // Check PHP syntax of the temporary file
            $view->display_message("\n🔍 DEBUG: Checking PHP syntax...", 'cyan');
            $output = [];
            $return_var = 0;
            exec("php -l " . escapeshellarg($temp_php_file) . " 2>&1", $output, $return_var);

            if ($return_var === 0) {
                $view->display_message("✅ PHP syntax check passed", 'green');
            } else {
                $view->display_message("❌ PHP syntax check failed:", 'red');
                foreach ($output as $line) {
                    $view->display_message("  $line", 'red');
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
            $view->display_message("\n=== JSON Response ===", 'cyan');
            $view->display_message("SQL Query: $sql", 'normal');
            $view->display_message(json_encode(json_decode($json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'normal');
            $view->display_message("\n=== End JSON Response ===\n", 'cyan');
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
            $view->display_message("\n🔍 DEBUG: Temporary files preserved for debugging:\n", 'cyan');
            if ($temp_php_file && file_exists($temp_php_file)) {
                $view->display_message("  PHP File: " . basename($temp_php_file), 'normal');
            }
            if ($output_file && file_exists($output_file)) {
                $view->display_message("  Output File: " . basename($output_file), 'normal');
            }
            if ($error_file && file_exists($error_file)) {
                $view->display_message("  Error File: " . basename($error_file), 'normal');
            }
        }
    }
}


/**
 * PHPUnit Testing Framework Database Functions - execute_mysqli_query
 *
 * This file contains the execute_mysqli_query function to be added to framework-database-functions.php
 */

if (!function_exists('WP_PHPUnit_Framework\\execute_mysqli_query')) {
    /**
     * Execute a MySQL query using the appropriate method based on environment
     *
     * This function automatically determines whether to use direct MySQLi connection,
     * SSH tunnel, or Lando environment for executing the query.
     *
     * @param string $sql SQL query to execute
     * @param string|null $user Database username (optional, defaults to WordPress settings)
     * @param string|null $pass Database password (optional, defaults to WordPress settings)
     * @param string|null $host Database host (optional, defaults to WordPress settings)
     * @param string|null $db_name Database name (optional, defaults to WordPress settings, 'none' for no database)
     * @param Database_Test_View|null $view View object for output (optional)
     * @return array Standardized response array
     */
    function execute_mysqli_query(string $sql, ?string $user = null, ?string $pass = null, ?string $host = null, ?string $db_name = null, ?Database_Test_View $view = null): array {
        global $query_counter, $db_settings;

        // Create view object if not provided
        if ($view === null) {
            $view = new Database_Test_View();
        }

        // Initialize query counter if not set
        if (!isset($GLOBALS['query_counter'])) {
            $GLOBALS['query_counter'] = 0;
        }

        // Increment and assign a unique query ID
        $query_id = ++$GLOBALS['query_counter'];

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

            $view->display_debug('Executing MySQL Query', $debug_info);

            if (strlen($sql) < 500) {
                $view->display_sql_query($sql);
            } else {
                $view->display_message("\n[Query is too long to display (" . strlen($sql) . " characters)]\n", 'white');
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
            $view->display_sql_result($result, $query_id);

            // Show detailed debug info if debug is enabled
            if ($debug) {
                $view->display_sql_debug($sql, [
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
                $db_name,
                $view
            );

            // Add query ID to result for reference
            $result['query_id'] = $query_id;

            // Show formatted results
            $view->display_sql_result($result, $query_id);

            // Show detailed debug info if debug is enabled
            if ($debug) {
                $view->display_sql_debug($sql, [
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
        $view->display_sql_result($result, $query_id);

        // Show detailed debug info if debug is enabled
        if ($debug) {
            $view->display_sql_debug($sql, [
                'host' => $host,
                'user' => $user,
                'db' => $db_name ?? '[NONE]'
            ], $result);
        }

        return $result;
    }
}


/**
 * PHPUnit Testing Framework Database Functions - execute_mysqli_prepared_statement
 *
 * This file contains the execute_mysqli_prepared_statement function to be added to framework-database-functions.php
 */

/**
 * PREPARED STATEMENT DOCUMENTATION
 * ===============================
 *
 * Why Use PHP's mysqli Prepared Statement API Instead of MySQL's PREPARE/EXECUTE Syntax:
 * -------------------------------------------------------------------------------
 *
 * • Session State Correctness:
 *   PHP's mysqli prepared statements maintain proper session state and connection context,
 *   avoiding issues with statement handles being lost between queries.
 *
 * • Type Safety:
 *   PHP's prepared statements handle type binding properly, ensuring integers, strings,
 *   and other data types are correctly passed to MySQL.
 *
 * • Security:
 *   PHP's implementation provides better protection against SQL injection by handling
 *   parameter binding at a lower level.
 *
 * • Compatibility:
 *   Works consistently across different MySQL versions and configurations without
 *   depending on specific MySQL server settings.
 *
 * • Error Handling:
 *   Provides better error reporting and exception handling through PHP's error system.
 *
 * Implementation Notes:
 * -------------------
 * This framework provides three functions for prepared statements:
 *
 * 1. execute_mysqli_prepared_statement() - Main wrapper function that detects environment
 * 2. execute_mysqli_prepared_statement_direct() - For direct MySQL connections (only called by main wrapper)
 * 3. execute_mysqli_prepared_statement_lando() - For Lando environments (only called by main wrapper)
 *
 * Usage Example:
 * -------------
 * $query = "INSERT INTO test_table (name, value) VALUES (?, ?)";
 * $params = ["test_name", 42];
 * $types = ["s", "i"];  // string, integer
 * $result = execute_mysqli_prepared_statement($query, $params, $types);
 */

if (!function_exists('WP_PHPUnit_Framework\execute_mysqli_prepared_statement')) {
    /**
     * Execute a MySQL prepared statement using the appropriate connection method based on environment
     *
     * @param string $query SQL query with placeholders
     * @param array $params Array of parameter values
     * @param array $types Array of parameter types ('s' for string, 'i' for integer, 'd' for double, 'b' for blob)
     * @param string|null $user MySQL username (optional, defaults to settings)
     * @param string|null $pass MySQL password (optional, defaults to settings)
     * @param string|null $host MySQL host (optional, defaults to settings)
     * @param string|null $db_name Database name (optional, defaults to settings)
     * @param Database_Test_View|null $view View object for output (optional, creates new instance if null)
     * @return array Standardized response array
     */
    function execute_mysqli_prepared_statement(string $query, array $params, array $types, ?string $user = null, ?string $pass = null, ?string $host = null, ?string $db_name = null, ?Database_Test_View $view = null): array {
        global $db_settings;

        // Use provided view or create a new one
        if ($view === null) {
            $view = new Database_Test_View();
        }

        // Use provided credentials or fall back to WordPress settings
        $db_user = $user ?? $db_settings['db_user'] ?? '';
        $db_pass = $pass ?? $db_settings['db_pass'] ?? '';
        $db_host = $host ?? $db_settings['db_host'] ?? '';

        // Display debug information if in debug mode
        if (has_cli_flag(['--debug', '-d', '--verbose', '-v'])) {
            $view->display_debug('Executing MySQL Prepared Statement', [
                'host' => $db_host,
                'user' => $db_user,
                'database' => $db_name ?: '[DEFAULT]',
                'params_count' => count($params),
                'types_count' => count($types),
                'query' => $query
            ]);
        }

        // Check if we're in a Lando environment
        if (is_lando_environment()) {
            return execute_mysqli_prepared_statement_lando($query, $params, $types, $db_settings, $db_name, $view);
        } else {
            return execute_mysqli_prepared_statement_direct($db_host, $db_user, $db_pass, $query, $params, $types, $db_name, $view);
        }
    }
}


/**
 * PHPUnit Testing Framework Database Functions - test_mysql_connectivity
 *
 * This file contains the test_mysql_connectivity function to be added to framework-database-functions.php
 */

if (!function_exists('WP_PHPUnit_Framework\test_mysql_connectivity')) {
    /**
     * Test basic MySQL server connectivity without requiring a database
     *
     * @param string $host MySQL host
     * @param string $user MySQL username
     * @param string $pass MySQL password
     * @param bool $quiet If true, suppresses output messages (default: false)
     * @param Database_Test_View|null $view View object for output (optional, creates new instance if null)
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
    function test_mysql_connectivity(string $host, string $user, string $pass, bool $quiet = false, ?Database_Test_View $view = null): array {
        // Use provided view or create a new one
        if ($view === null) {
            $view = new Database_Test_View();
        }

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
            $result = execute_mysqli_query('SELECT 1 AS test_value, VERSION() AS version', $user, $pass, $host, 'none', $view);

            $latency = round((microtime(true) - $start_time) * 1000, 2);

            if (!$result['success']) {
                if (!$quiet) {
                    $view->display_message("❌ Lando connection failed: {$result['error']}", 'red');
                    $view->display_message("  Host: $host", 'yellow');
                    $view->display_message("  User: $user\n", 'yellow');
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
                $view->display_message("✅ Lando connection successful to $host as $user", 'green');
                $view->display_message("  MySQL Server Version: " . $version['server'] , 'green');
                $view->display_message("  Latency: {$latency}ms\n", 'green');
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
                $view->display_message("❌ Connection failed: $error (Error #$error_code)", 'red');
                $view->display_message("  Host: $host", 'yellow');
                $view->display_message("  User: $user\n", 'yellow');
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
                $view->display_message("⚠️  Connection established but validation query failed: $error (Error #$error_code)", 'yellow');
                $view->display_message("  Host: $host", 'yellow');
                $view->display_message("  User: $user\n", 'yellow');
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
            $view->display_message("✅ Connection successful to $host as $user", 'green');
            $view->display_message("  MySQL Server Version: {$version['server']}", 'green');
            $view->display_message("  Latency: {$latency}ms\n", 'green');
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
}
