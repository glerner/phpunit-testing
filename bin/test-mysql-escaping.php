<?php
namespace WP_PHPUnit_Framework;

// files in bin/ need to Include the Composer autoloader to enable PSR-4 class autoloading
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/framework-functions.php';
require_once __DIR__ . '/framework-database-functions.php';

use WP_PHPUnit_Framework\Service\Database_Connection_Manager;

// Instantiate the singleton Database_Connection_Manager
$db_manager = Database_Connection_Manager::get_instance();

// Instantiate the view for consistent output formatting
$view = new \WP_PHPUnit_Framework\View\Database_Test_View();

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
    
    // If in Lando, load and display Lando details here
    if (is_lando_environment()) {
        $lando_info = get_lando_info();
        if (!empty($lando_info)) {
            colored_message("\n📦 Lando Database Services:", 'cyan');
            $db_services = array_filter($lando_info, function($service) {
                return isset($service['service']) &&
                    (strpos(strtolower($service['service']), 'mysql') !== false ||
                     strpos(strtolower($service['service']), 'mariadb') !== false ||
                     strpos(strtolower($service['service']), 'database') !== false);
            });

            if (!empty($db_services)) {
                foreach ($db_services as $service) {
                    $service_name = $service['service'] ?? 'unknown';
                    $service_type = $service['type'] ?? 'unknown';
                    colored_message("  • {$service_name} ({$service_type})", 'cyan');

                    if (isset($service['creds']) && is_array($service['creds'])) {
                        $creds = $service['creds'];
                        if (isset($service['internal_connection'])) {
                            colored_message("    Host: " . ($service['internal_connection']['host'] ?? 'unknown'), 'cyan');
                            colored_message("    Port: " . ($service['internal_connection']['port'] ?? 'unknown'), 'cyan');
                        }
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

            // Optionally show raw/full Lando info in debug mode
            if (has_cli_flag(['--debug'])) {
                colored_message("\nDebug: Full Lando info (parsed):", 'blue');
                // Pretty-print a summarized structure to avoid huge dumps in normal runs
                print_r($lando_info);
            }
        } else {
            colored_message("⚠️ Lando detected but no configuration details could be loaded.", 'yellow');
        }
    }

    colored_message(str_repeat("=", 80) . "\n", 'cyan');

    // Mark as displayed
    $environment_info_displayed = true;
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
$prepared_statement_tests_passed = run_prepared_statement_test();
