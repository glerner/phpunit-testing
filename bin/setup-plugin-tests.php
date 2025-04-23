<?php
/**
 * Setup Plugin Tests
 *
 * Set up the WordPress test environment for PHPUnit testing.
 *
 * @package GL_PHPUnit_Testing
 */

declare(strict_types=1);

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

// Load environment variables from .env.testing if it exists
$env_file = PROJECT_DIR . '/.env.testing';
if (file_exists($env_file)) {
    echo "Loading environment variables from .env.testing...\n";
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
    echo "✅ System requirements met\n";
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

    echo "✅ WordPress test suite downloaded successfully.\n";
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
        echo "✅ Copied wp-tests-config.php to tests directory\n";
    } else {
        echo "Warning: Failed to copy wp-tests-config.php. You may need to copy the file manually.\n";
        // Continue anyway, this is not critical
    }

    echo "✅ wp-tests-config.php generated successfully.\n";
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
    $env_db_host = getenv('TEST_DB_HOST');
    $env_db_user = getenv('TEST_DB_USER');
    $env_db_pass = getenv('TEST_DB_PASS');
    $env_db_name = getenv('TEST_DB_NAME');
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

    // Inside container or local, we use mysql directly
    echo "Using mysql with connection details...\n";
    $mysql_cmd = "mysql";

    // In Lando environments, we use the standard WordPress test configuration
    $lando_info = parse_lando_info();
    if ($lando_info !== null) {
        echo "Using standard Lando database configuration...\n";

        // Find the database service
        $db_service = null;
        foreach ($lando_info as $service_name => $service_info) {
            if (isset($service_info['type']) && ($service_info['type'] === 'mysql' || $service_info['type'] === 'mariadb')) {
                $db_service = $service_info;
                break;
            }
        }

        // Environment variables were already loaded at the function start

        // Get the database connection details
        if ($db_service !== null) {
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
        } else {
            // Default values if we can't find the service - from Lando environment variables
            // See TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS in .lando.yml
            $db_host = 'database';
            $db_user = 'wordpress';
            $db_pass = 'wordpress';
        }

        echo "Host: $db_host, User: $db_user, Password: $db_pass\n";
    }

    // Verify the connection using the parameters that will be in wp-tests-config.php
    echo "Verifying database connection to $db_host...\n";
    $cmd = "$mysql_cmd -h \"$db_host\" -u \"$db_user\" -p\"$db_pass\" -e \"SELECT 1\" 2>&1";
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Cannot connect to MySQL server.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        return false;
    }

    echo "✅ Connected to MySQL on host: $db_host\n";

    echo "✅ MySQL connection successful\n";

    // Try to drop database if exists
    echo "Attempting to drop existing database...\n";

    // In Lando, we know the root user has no password
    if ($lando_info !== null) {
        echo "Using root user to drop database in Lando environment...\n";
        $cmd = "$mysql_cmd -h \"$db_host\" -uroot -e \"DROP DATABASE IF EXISTS $db_name\" 2>&1";
    } else {
        // In local environment, use provided user
        $cmd = "$mysql_cmd -h \"$db_host\" -u \"$db_user\" -p\"$db_pass\" -e \"DROP DATABASE IF EXISTS $db_name\" 2>&1";
    }

    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Warning: Failed to drop test database.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        echo "Continuing anyway, as the database might not exist yet...\n";
    } else {
        echo "✅ Existing database dropped (if it existed)\n";
    }

    // Create database and grant permissions
    echo "Creating database...\n";

    // In Lando, we know the root user has no password
    if ($lando_info !== null) {
        echo "Creating database and granting permissions (Lando environment)...\n";
        $cmd = "$mysql_cmd -h \"$db_host\" -uroot -e \"CREATE DATABASE IF NOT EXISTS $db_name; GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user'@'%';\" 2>&1";
    } else {
        // In local environment, use provided user
        $cmd = "$mysql_cmd -h \"$db_host\" -u \"$db_user\" -p\"$db_pass\" -e \"CREATE DATABASE IF NOT EXISTS $db_name\" 2>&1";
    }

    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Failed to create test database.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        return false;
    }

    echo "✅ Database created successfully\n";

    // Verify database exists and is accessible
    echo "Verifying database access...\n";
    $cmd = "$mysql_cmd -h \"$db_host\" -u \"$db_user\" -p\"$db_pass\" -e \"USE $db_name; SELECT DATABASE();\" 2>&1";
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo "Error: Cannot access test database after creation.\n";
        echo "Output: " . implode("\n", $output) . "\n";
        return false;
    }

    echo "✅ Test database created and verified\n";

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

    echo "✅ WordPress test framework installed successfully.\n";
    return true;
}

/**
 * Main execution
 */
echo "Setting up WordPress plugin tests...\n";

// Check system requirements
if (!check_system_requirements()) {
    exit(1);
}

// Set up paths and configuration
$plugin_dir = PROJECT_DIR;
$plugin_slug = basename($plugin_dir);

// Get WordPress root path
$wp_root = getenv('FILESYSTEM_WP_ROOT') ?: '/home/george/sites/wordpress';
$lando_webroot = getenv('LANDO_WEBROOT');

// Check if we're running in a Lando environment
$lando_info = getenv('LANDO_INFO');
$in_lando = $lando_info !== false;

// Get database settings from environment variables if available
// These will be used as the highest priority source for database settings
$env_db_host = getenv('TEST_DB_HOST');
$env_db_user = getenv('TEST_DB_USER');
$env_db_pass = getenv('TEST_DB_PASS');
$env_db_name = getenv('TEST_DB_NAME');

// Clean up any path with './' in it
if ($lando_webroot !== false && strpos($lando_webroot, './') !== false) {
    $lando_webroot = str_replace('./', '', $lando_webroot);
}

// Check for Lando environment
if ($in_lando) {
    echo "Using Lando configuration...\n";
    // Clean up the path by removing any ./ in the path
    $wp_root = $lando_webroot ? rtrim(str_replace('/./','/', $lando_webroot), '/') : '/app';
} else {
    // For local environment, try to find WordPress root
    echo "Not using Lando configuration, assuming local environment...\n";
    $local_root = find_wordpress_root($plugin_dir);
    if ($local_root !== null) {
        $wp_root = $local_root;
    } else {
        echo "Warning: Could not find WordPress root directory (wp-config.php not found).\n";
        echo "Using configured path: $wp_root\n";
    }
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
        echo "\033[31mWARNING: Database service not found in Lando configuration!\033[0m\n";
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

// Set up WordPress test suite directory
$wp_tests_dir = getenv('WP_TESTS_DIR') ?: "$wp_root/wp-content/plugins/wordpress-develop/tests/phpunit";
echo "Using WordPress test directory: $wp_tests_dir\n";

// Download and set up test suite
if (!download_wp_tests($wp_tests_dir)) {
    exit(1);
}

// Generate config file
if (!generate_wp_tests_config($wp_tests_dir, $wp_root, $db_name, $db_user, $db_pass, $db_host, $plugin_dir)) {
    exit(1);
}

// Create build directories for test coverage reports
echo "Creating build directories for test coverage...\n";
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
