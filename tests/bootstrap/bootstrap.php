<?php
/**
 * Main bootstrap file for the WordPress PHPUnit Testing Framework
 *
 * This file serves as the entry point for all test types and handles
 * common initialization tasks.
 *
 * IMPORTANT: PHPUnit Version Compatibility
 *
 * Unit Tests and WP_Mock Tests MUST:
 * - Use PHPUnit 11 syntax
 * - NOT use the WordPress Test Suite
 * - Extend PHPUnit\Framework\TestCase or WP_Mock\Tools\TestCase
 *
 * All Integration tests MUST:
 * - Use the WordPress Test Suite
 * - Use PHPUnit 9.6-compatible syntax
 * - Extend WP_UnitTestCase or similar WordPress test classes
 * - Use bootstrap-integration.php (call this file, with PHPUNIT_BOOTSTRAP_TYPE = 'integration')
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Bootstrap
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Bootstrap;

use function WP_PHPUnit_Framework\colored_message;
use function WP_PHPUnit_Framework\debug_message;
use function WP_PHPUnit_Framework\get_cli_value;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\has_cli_flag;
use function WP_PHPUnit_Framework\load_settings_file;

// Initialize error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');

// Set up error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Don't throw if error reporting is turned off
    if (!(error_reporting() & $errno)) {
        return false;
    }

    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Parse command line arguments for critical settings
$options = getopt('', [
    'filesystem-wp-root:',
    'plugin-slug:',
    'folder-in-wordpress:',
    'wordpress-test-suite-path:',
]);

// Check if we are running the framework's own tests.
if (getenv('TEST_TYPE') === 'framework') {
    // In this context, the framework IS the project.
    define('FRAMEWORK_DIR', dirname(__DIR__, 2));
    define('PROJECT_DIR', FRAMEWORK_DIR);
    require_once FRAMEWORK_DIR . '/bin/framework-functions.php';
} else {
    // Otherwise, we are in a project (e.g., a plugin) that is USING the framework.

    // Define the project root directory. This bootstrap file is expected to be at [PROJECT_DIR]/tests/bootstrap/bootstrap.php
    define('PROJECT_DIR', dirname(__DIR__, 2));

    // The framework's utility functions are expected to be copied to the project's bin directory.
    $functions_file = PROJECT_DIR . '/bin/framework-functions.php';
    if (!file_exists($functions_file)) {
        die(
            "Error: The framework functions file is missing.\n" .
            "Searched at: " . $functions_file . "\n" .
            "Please ensure you have run the sync script to copy the framework files to your project.\n"
        );
    }
    require_once $functions_file;

    // Framework functions are now loaded, now we'll process settings
}

// Process Settings Phase
    echo "\n=== Phase 1: Process Settings ===\n";

    // Load settings from .env.testing
    global $loaded_settings;
    $env_file = PROJECT_DIR . '/tests/.env.testing';

    // Debug output for $loaded_settings before loading
    if (has_cli_flag(['--debug-bootstrap'])) {
        echo "DEBUG: Before loading settings from $env_file\n";
        echo "DEBUG: \$loaded_settings is " . (isset($loaded_settings) ? "set (" . count($loaded_settings) . " items)" : "not set") . "\n";
    }

    $loaded_settings = load_settings_file($env_file);

    // Check for verbose mode from either command line or environment
    $is_verbose = false;

    // Check for verbosity in environment, .env.testing, or command-line flags
    $verbosity_flags = ['--verbose', '-v', '-vv', '-vvv'];
    $is_verbose = get_setting('VERBOSE', false) || has_cli_flag($verbosity_flags);

    // Command line parameters take precedence over .env.testing settings

    // Apply CLI overrides for critical settings
    if (!empty($options['filesystem-wp-root'])) {
        $loaded_settings['FILESYSTEM_WP_ROOT'] = $options['filesystem-wp-root'];
        debug_message("FILESYSTEM_WP_ROOT set from CLI param: '{$loaded_settings['FILESYSTEM_WP_ROOT']}'\n");
    }

    if (!empty($options['plugin-slug'])) {
        $loaded_settings['YOUR_PLUGIN_SLUG'] = $options['plugin-slug'];
        debug_message("YOUR_PLUGIN_SLUG set from CLI param: '{$loaded_settings['YOUR_PLUGIN_SLUG']}'\n");
    }

    if (!empty($options['folder-in-wordpress'])) {
        $loaded_settings['FOLDER_IN_WORDPRESS'] = $options['folder-in-wordpress'];
        debug_message("FOLDER_IN_WORDPRESS set from CLI param: '{$loaded_settings['FOLDER_IN_WORDPRESS']}'\n");
    }

    if (!empty($options['wordpress-test-suite-path'])) {
        $loaded_settings['WORDPRESS_TEST_SUITE_PATH'] = $options['wordpress-test-suite-path'];
        debug_message("WORDPRESS_TEST_SUITE_PATH set from CLI param: '{$loaded_settings['WORDPRESS_TEST_SUITE_PATH']}'\n");
    }

    // Set default values for critical settings if not provided
    if (empty(get_setting('TEST_FRAMEWORK_DIR'))) {
        // Set default if not defined in .env.testing
        $GLOBALS['loaded_settings']['TEST_FRAMEWORK_DIR'] = 'gl-phpunit-test-framework';
        debug_message("WARNING: TEST_FRAMEWORK_DIR not defined in .env.testing, defaulting to 'gl-phpunit-test-framework'\n");
    }

    // Verify TEST_FRAMEWORK_DIR setting is valid
    $framework_dir_path = PROJECT_DIR . '/tests/' . get_setting('TEST_FRAMEWORK_DIR');
    colored_message("Framework path: $framework_dir_path\n", 'red');

    if (!is_dir($framework_dir_path)) {
        die(
            "Error: $framework_dir_path is not a valid directory.\n" .
            "Please define TEST_FRAMEWORK_DIR correctly in your " . $env_file . " file.\n"
        );
    }

    // Debug output for $loaded_settings after loading
    if (has_cli_flag(['--debug-bootstrap'])) {
        echo "DEBUG: After loading settings from $env_file and applying command-line parameters\n";
        echo "DEBUG: \$loaded_settings is " . (isset($loaded_settings) ? "set (" . count($loaded_settings) . " items)" : "not set") . "\n";
        if (isset($loaded_settings) && !empty($loaded_settings)) {
            echo "DEBUG: TEST_FRAMEWORK_DIR = " . ($loaded_settings['TEST_FRAMEWORK_DIR'] ?? 'not set') . "\n";
            echo "DEBUG: FILESYSTEM_WP_ROOT = " . ($loaded_settings['FILESYSTEM_WP_ROOT'] ?? 'not set') . "\n";
        }
    }

    // Environment Setup Phase
    echo "\n=== Phase 2: Environment Setup ===\n";


    // A function to find the Composer autoloader instance from the registered spl_autoload_functions
    function find_composer_autoloader() {
        $autoloaders = spl_autoload_functions();
        $composer_autoloader = null;
        $i = 0;
        $loaders_found = 0;

        debug_message("\n=== DEBUG: Searching for Composer autoloader in memory ===");

        foreach ($autoloaders as $autoloader) {
            $i++;
            if (is_array($autoloader) && isset($autoloader[0]) && is_object($autoloader[0])) {
                $class = get_class($autoloader[0]);
                if (strpos($class, 'Composer\\Autoload\\ClassLoader') !== false) {
                    debug_message("- Found Composer autoloader #{$i} in memory");

                    // Check if this autoloader handles our namespaces
                    if (method_exists($autoloader[0], 'getPrefixesPsr4')) {
                        $prefixes = $autoloader[0]->getPrefixesPsr4();
                        debug_message("  Registered PSR-4 prefixes:");

                        $handles_project_namespace = false;
                        $project_namespace = null;

                        // Extract the project directory name for namespace detection
                        $project_dir_parts = explode('/', PROJECT_DIR);
                        $project_dir_name = end($project_dir_parts);

                        foreach ($prefixes as $prefix => $paths) {
                            // Shorten paths for readability
                            $shortened_paths = [];
                            foreach ($paths as $path) {
                                // Replace common path prefixes with shorter placeholders
                                if (strpos($path, PROJECT_DIR) !== false) {
                                    $shortened_paths[] = str_replace(PROJECT_DIR, 'PLUGIN_DIR', $path);
                                } elseif (strpos($path, PROJECT_DIR . '/tests/' . get_setting('TEST_FRAMEWORK_DIR')) !== false) {
                                    $shortened_paths[] = str_replace(PROJECT_DIR . '/tests/' . get_setting('TEST_FRAMEWORK_DIR'), 'TEST_FRAMEWORK', $path);
                                } elseif (strpos($path, '/app/wp-content/plugins/') !== false) {
                                    // Extract plugin name from path
                                    $plugin_path = preg_replace('|^.*/app/wp-content/plugins/([^/]+)/.*$|', 'WP_PLUGIN/$1', $path);
                                    $shortened_paths[] = $plugin_path;
                                } else {
                                    $shortened_paths[] = $path;
                                }
                            }
                            debug_message("    {$prefix} => " . implode(', ', $shortened_paths));

                            // Look for namespaces that point to the project's src directory
                            foreach ($paths as $path) {
                                if (strpos($path, PROJECT_DIR . '/src') !== false) {
                                    $handles_project_namespace = true;
                                    $project_namespace = $prefix;
                                    debug_message("    ** This autoloader handles the project namespace: {$prefix} **");
                                    break;
                                }
                            }
                        }

                        // If this is the first autoloader we found, or it handles the project namespace, use it
                        if ($composer_autoloader === null || $handles_project_namespace) {
                            $composer_autoloader = $autoloader[0];
                        }
                    }
                }
            }
        }

        return $composer_autoloader;
    }

    // Register the Composer autoloader
    $autoloader = null;
    $wp_plugin_root = dirname(PROJECT_DIR);

    $autoloader = find_composer_autoloader();

    if ($autoloader === null) {
        die("Error: Could not find Composer's autoloader. Please run 'composer install' in the project root.\n");
    }

    // Register framework classes with autoloader if we have a valid autoloader
    if ($autoloader instanceof \Composer\Autoload\ClassLoader) {
        if ($is_verbose) {
            echo "Registering framework PSR-4 prefixes\n";
        }
        // Register framework's own classes
        $autoloader->addPsr4('WP_PHPUnit_Framework\\', PROJECT_DIR . '/tests/' . get_setting('TEST_FRAMEWORK_DIR') . '/src');
        $autoloader->register();
    } else if ($is_verbose) {
        echo "WARNING: Could not register PSR-4 prefixes - no valid autoloader found\n";
    }

    if ($is_verbose) {
        echo "=== Autoloader Debug ===\n";

        // Debug: Show all registered autoload functions
        $autoloaders = spl_autoload_functions();
        echo "Registered autoload functions:\n";
        foreach ($autoloaders as $i => $loader) {
            if (is_array($loader)) {
                $class = is_object($loader[0]) ? get_class($loader[0]) : $loader[0];
                echo sprintf("  [%d] %s::%s\n", $i, $class, $loader[1]);
            } else if (is_string($loader)) {
                echo sprintf("  [%d] %s\n", $i, $loader);
            } else {
                echo sprintf("  [%d] %s\n", $i, gettype($loader));
            }
        }
        echo "\n";
    }




    // Find the WordPress Test Suite
    // Note: This is different from our custom test framework (located in FILESYSTEM_WP_ROOT/wp-content/plugins/yourplugin/tests/TEST_FRAMEWORK_DIR)

    // WP_ROOT is correct whether or not we're running inside a container

    // Get the WordPress test suite path
    // Priority: CLI param > WP_TESTS_DIR_CONTAINER > default path
    $wordpress_test_suite_path = get_cli_value('--wordpress-test-suite-path');
    if (empty($wordpress_test_suite_path)) {
        if (get_setting('WP_TESTS_DIR_CONTAINER')) {
            $wordpress_test_suite_path = get_setting('WP_TESTS_DIR_CONTAINER');
        } else {
            $wordpress_test_suite_path = get_setting('WP_ROOT') . '/wp-content/plugins/wordpress-develop/tests/phpunit';
        }
    }

    if (!$wordpress_test_suite_path || !is_dir($wordpress_test_suite_path)) {
        colored_message("ERROR: WordPress Test Suite not found in $wordpress_test_suite_path\n", 'red');

        echo "From your plugin's root:\n";
        echo "Run `php tests/gl-phpunit-test-framework/bin/setup-plugin-tests.php`\n";
        echo "\nIf you did a custom setup (not recommended), set WP_TESTS_DIR and WP_TESTS_DIR_CONTAINER in your .env.testing to the path of the WordPress Test Suite.\n";
        echo "This is typically at WP_ROOT/wp-content/plugins/wordpress-develop/tests/phpunit\n";
        echo "Note: Do not confuse this with TEST_FRAMEWORK_DIR, which is for the phpunit-testing framework location.\n";
        exit(1);
    }

    // Test Type-specific Bootstrap Phase
    echo "\n=== Phase 3: Test Type-specific Bootstrap ===\n";

    // Load specific bootstrap file based on test type
    $bootstrap_type = get_setting('PHPUNIT_BOOTSTRAP_TYPE', 'unit');
    $bootstrap_folder = PROJECT_DIR . '/tests/bootstrap';
    echo "Loading bootstrap for test type: {$bootstrap_type}\n";

    // Define the log directory for event listeners.
    $logDir = get_setting('WP_PHPUNIT_TEST_LOG_DIR');

// Optional debug output
if (has_cli_flag(['--debug-bootstrap'])) {
    echo "=== Framework Bootstrap Debug ===\n";
    echo "Current file: " . __FILE__ . "\n";
    echo "Project location: " . PROJECT_DIR . "\n";
    echo "Test Framework location: " . PROJECT_DIR . '/tests/' . get_setting('TEST_FRAMEWORK_DIR') . "\n";
    echo "Current working directory: " . getcwd() . "\n\n";
}

// Register the Composer autoloader
$autoloader = null;
$wp_plugin_root = dirname(PROJECT_DIR);

// First, check if the autoloader is already in memory.
$autoloader = find_composer_autoloader();

// If not found in memory, search the filesystem, require the file, and then check again.
if ( ! $autoloader) {
    debug_message("NOTICE: Composer autoloader not found in memory. Searching filesystem.\n");

    // Try to find the Composer autoloader in common locations
    $possible_autoloaders = [
        // The framework's own autoloader is the top priority.
        PROJECT_DIR . '/tests/' . get_setting('TEST_FRAMEWORK_DIR') . '/vendor/autoload.php',
        // Next, check for the project's (the plugin's) autoloader.
        PROJECT_DIR . '/vendor/autoload.php',
        // Fallback to searching in parent directories for other setups.
        $wp_plugin_root . '/vendor/autoload.php',
        $wp_plugin_root . '/../../vendor/autoload.php',
        $wp_plugin_root . '/../../../vendor/autoload.php',
        $wp_plugin_root . '/../../../../vendor/autoload.php',
    ];

    // Debug output for autoloader search paths
    echo "\n=== DEBUG: Autoloader Search Paths ===\n";
    echo "TEST_FRAMEWORK_DIR: " . PROJECT_DIR . '/tests/' . get_setting('TEST_FRAMEWORK_DIR') . "\n";
    echo "PROJECT_DIR: " . PROJECT_DIR . "\n";
    echo "wp_plugin_root: " . $wp_plugin_root . "\n";
    echo "\nSearching for autoloader in these locations:\n";

    $autoloader_found_path = false;
    foreach ($possible_autoloaders as $autoloader_path) {
        $exists = file_exists($autoloader_path) ? "EXISTS" : "NOT FOUND";
        echo "- {$autoloader_path} ... {$exists}\n";

        if (file_exists($autoloader_path)) {
            echo "  Loading autoloader from: {$autoloader_path}\n";
            require_once $autoloader_path;
            $autoloader_found_path = true;
            break;
        }
    }

    // If we loaded it from a file, find the instance again.
    if ($autoloader_found_path) {
        $autoloader = find_composer_autoloader();
    }
}

// Even if we found an autoloader, it might not include the plugin's classes

// Get the plugin directory name from PROJECT_DIR path
$project_dir_parts = explode('/', PROJECT_DIR);
$plugin_dir_name = end($project_dir_parts);

// Construct paths for autoloader search
debug_message("\n=== DEBUG: Checking for project autoloader ===\n");

// Define possible autoloader paths in priority order
$possible_autoloader_paths = [
    // First check in the deployed plugin directory
    $wp_plugin_root . '/' . $plugin_dir_name . '/vendor/autoload.php',
    // Then check in the project directory
    PROJECT_DIR . '/vendor/autoload.php',
    // Then check in parent directories
    dirname(PROJECT_DIR) . '/vendor/autoload.php',
    dirname(dirname(PROJECT_DIR)) . '/vendor/autoload.php',
    $wp_plugin_root . '/vendor/autoload.php',
    $wp_plugin_root . '/../vendor/autoload.php'
];

$project_autoloader_loaded = false;

foreach ($possible_autoloader_paths as $path) {
    debug_message("- Looking for project autoloader at: {$path}");

    if (file_exists($path)) {
        debug_message("- Project autoloader found, loading it...");
        require_once $path;
        debug_message("- Project autoloader loaded successfully");
        $project_autoloader_loaded = true;
        break;
    } else {
        debug_message("- Project autoloader NOT found at {$path}");
    }
}

// If we loaded an autoloader, verify that the project's classes are available
if ($project_autoloader_loaded) {
    // Use reflection to find the first class in the project's namespace
    $autoloader = find_composer_autoloader();
    if ($autoloader) {
        $prefixes = $autoloader->getPrefixesPsr4();
        $project_namespaces = [];

        foreach ($prefixes as $prefix => $paths) {
            // Find namespaces that point to the project's src directory
            foreach ($paths as $path) {
                if (strpos($path, PROJECT_DIR . '/src') !== false) {
                    $project_namespaces[] = $prefix;
                    break;
                }
            }
        }

        if (!empty($project_namespaces)) {
            $test_namespace = rtrim($project_namespaces[0], '\\');
            debug_message("- Found project namespace: {$test_namespace}");

            // Check if any classes from this namespace are available by scanning the src directory
            $src_dir = PROJECT_DIR . '/src';
            $namespace_loaded = false;

            if (is_dir($src_dir)) {
                debug_message("- Checking for classes in {$src_dir}");

                // Try to find any PHP class file in the src directory
                $class_files = glob($src_dir . '/*/*.php');
                if (empty($class_files)) {
                    $class_files = glob($src_dir . '/*.php'); // Try root of src
                }

                if (!empty($class_files)) {
                    $sample_file = $class_files[0];
                    $relative_path = str_replace($src_dir . '/', '', $sample_file);
                    $relative_path = str_replace('.php', '', $relative_path);
                    $class_path = str_replace('/', '\\', $relative_path);

                    $test_class = $test_namespace . '\\' . $class_path;
                    debug_message("- Testing if {$test_class} is now available: ", false);
                    $class_exists = class_exists($test_class);
                    debug_message($class_exists ? "YES" : "NO");

                    $namespace_loaded = $class_exists;
                } else {
                    debug_message("- No class files found in {$src_dir}");
                }
            } else {
                debug_message("- Source directory {$src_dir} not found");
            }

            if (!$namespace_loaded) {
                debug_message("- WARNING: Could not verify that project classes are available");
            }
        } else {
            debug_message("- Could not determine project namespace");
        }
    }
}

if ($autoloader === null) {
    die("Error: Could not find Composer's autoloader. Please run 'composer install' in the project root.\n");
}

// Check for verbose mode from either command line or environment
$is_verbose = false;

// Check for verbosity in environment, .env.testing, or command-line flags
$verbosity_flags = ['--verbose', '-v', '-vv', '-vvv'];
$is_verbose = get_setting('VERBOSE', false) || has_cli_flag($verbosity_flags);

// Register framework classes with autoloader if we have a valid autoloader
if ($autoloader instanceof \Composer\Autoload\ClassLoader) {
    if ($is_verbose) {
        echo "Registering framework PSR-4 prefixes\n";
    }
    // Register framework's own classes
    $autoloader->addPsr4('WP_PHPUnit_Framework\\', PROJECT_DIR . '/tests/' . get_setting('TEST_FRAMEWORK_DIR') . '/src');
    // The project's autoloader (loaded above) is responsible for registering its own classes.
    $autoloader->register();
} else if ($is_verbose) {
    echo "WARNING: Could not register PSR-4 prefixes - no valid autoloader found\n";
}

if ($is_verbose) {
    echo "=== Autoloader Debug ===\n";

    // Debug: Show all registered autoload functions
    $autoloaders = spl_autoload_functions();
    echo "Registered autoload functions:\n";
    foreach ($autoloaders as $i => $loader) {
        if (is_array($loader)) {
            $class = is_object($loader[0]) ? get_class($loader[0]) : $loader[0];
            echo sprintf("  [%d] %s::%s\n", $i, $class, $loader[1]);
        } else if (is_string($loader)) {
            echo sprintf("  [%d] %s\n", $i, $loader);
        } else {
            echo sprintf("  [%d] %s\n", $i, gettype($loader));
        }
    }
    echo "\n";
}

// Load specific bootstrap file based on test type
$bootstrap_type = get_setting('PHPUNIT_BOOTSTRAP_TYPE', 'unit');
$bootstrap_folder = PROJECT_DIR . '/tests/bootstrap';
echo "Loading bootstrap $bootstrap_folder for test type: {$bootstrap_type}\n";

// Define the log directory for event listeners.
$logDir = get_setting('WP_PHPUNIT_TEST_LOG_DIR');

switch ($bootstrap_type) {
    case 'unit':
        require_once $bootstrap_folder . '/bootstrap-unit.php';
        break;
    case 'wp-mock':
        require_once $bootstrap_folder . '/bootstrap-wp-mock.php';
        break;
    case 'integration':
        require_once $bootstrap_folder . '/bootstrap-integration.php';
        break;
    default:
        echo "ERROR: Unknown bootstrap type: {$bootstrap_type}\n";
        exit(1);
}

echo "\n=== Bootstrap Complete ===\n";
