<?php
/**
 * Test Environment Requirements Check
 *
 * Validates that all required environment settings are properly configured
 * for running tests. This script should be called before running any tests
 * or setup scripts.
 *
 * @package WP_PHPUnit_Framework
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework;

// Exit if accessed directly, should be run command line
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit(1);
}

// Load framework functions. Assumes framework-functions.php is in the same directory
// as this script (e.g., both copied to PROJECT_DIR/bin/).
require_once __DIR__ . '/framework-functions.php';

/**
 * Main function to validate environment requirements
 */
function validate_environment(): bool {
    $is_valid = true;
    $verbose = has_cli_flag('--verbose');

    // Define all settings and their validation rules
    $settings_validation = [
        // Required settings
        'PLUGIN_FOLDER' => [
            'required' => true,
            'validate' => 'is_dir',
            'message' => 'must be a valid directory containing your plugin',
        ],
        'FILESYSTEM_WP_ROOT' => [
            'required' => true,
            'validate' => 'is_dir',
            'message' => 'must be a valid WordPress root directory',
        ],
        'WP_ROOT' => [
            'required' => true,
            'validate' => function($value) {
                // Can be either a valid directory or a container path like /app
                return is_dir($value) || $value === '/app';
            },
            'message' => 'must be a valid directory or container path (e.g., /app)',
        ],
        'FOLDER_IN_WORDPRESS' => [
            'required' => true,
            'validate' => 'is_string',
            'message' => 'must be a valid path segment (e.g., wp-content/plugins)',
        ],
        'YOUR_PLUGIN_SLUG' => [
            'required' => true,
            'validate' => function($value) {
                return is_string($value) && !empty($value) &&
                       preg_match('/^[a-z0-9-]+$/', $value) === 1;
            },
            'message' => 'must be a valid plugin slug (lowercase letters, numbers, and hyphens only)',
        ],
        'TEST_FRAMEWORK_DIR' => [
            'required' => true,
            'validate' => function($value) {
                $full_path = make_path(PROJECT_DIR, 'tests', $value);
                return is_dir($full_path) && file_exists($full_path . '/src/Unit/Unit_Test_Case.php');
            },
            'message' => 'must be a valid test framework directory containing src/Unit/Unit_Test_Case.php',
        ],
        'WP_TESTS_DB_NAME' => [
            'required' => true,
            'validate' => 'is_string',
            'message' => 'must be a valid database name',
        ],
        'WP_TESTS_TABLE_PREFIX' => [
            'required' => true,
            'validate' => function($value) {
                return is_string($value) && !empty($value) &&
                       substr($value, -1) === '_' &&
                       preg_match('/^[a-z0-9_]+$/', $value) === 1;
            },
            'message' => 'must be a valid table prefix (lowercase letters, numbers, and underscores, ending with _)',
        ],
        'SSH_COMMAND' => [
            'required' => true,
            'validate' => 'is_string',
            'message' => 'must be a valid SSH command (use "none" for local development)',
        ],
        'WP_TESTS_DB_USER' => [
            'required' => true,
            'validate' => 'is_string',
            'message' => 'must be a valid database username',
        ],
        'WP_TESTS_DB_PASSWORD' => [
            'required' => true,
            'validate' => 'is_string',
            'message' => 'must be a valid database password',
        ],
        'WP_TESTS_DB_HOST' => [
            'required' => true,
            'validate' => 'is_string',
            'message' => 'must be a valid database host',
        ],
        'WP_TESTS_DOMAIN' => [
            'required' => true,
            'validate' => function($value) {
                return filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
            },
            'message' => 'must be a valid domain name',
        ],
        'WP_TESTS_EMAIL' => [
            'required' => true,
            'validate' => 'is_email',
            'message' => 'must be a valid email address',
        ],
        'WP_TESTS_TITLE' => [
            'required' => true,
            'validate' => 'is_string',
            'message' => 'must be a valid site title',
        ],
        'WP_TESTS_NETWORK_TITLE' => [
            'required' => true,
            'validate' => 'is_string',
            'message' => 'must be a valid network title',
        ],
        'WP_TESTS_SUBDOMAIN_INSTALL' => [
            'required' => true,
            'validate' => function($value) {
                return in_array(strtolower($value), ['0', '1', 'true', 'false', 'yes', 'no'], true);
            },
            'message' => 'must be a valid boolean value (0/1, true/false, yes/no)',
        ],
        // Optional settings with validation
        'WP_TESTS_DIR' => [
            'required' => false,
            'validate' => null,
            'message' => 'must be a valid directory or FILESYSTEM_WP_ROOT must be set',
            'format' => null
        ],
        'TEST_ERROR_LOG' => [
            'required' => false,
            'validate' => function($value) {
                // Check if directory is writable
                $dir = dirname($value);
                return is_writable($dir) || (is_dir($dir) && is_writable($dir));
            },
            'message' => 'parent directory must be writable',
        ],
        'PHP_MEMORY_LIMIT' => [
            'required' => false,
            'validate' => function($value) {
                return (bool)preg_match('/^\d+[KMG]?$/', $value);
            },
            'message' => 'must be a valid memory limit (e.g., 256M, 1G, 512M)',
        ],
        'COVERAGE_REPORT_PATH' => [
            'required' => false,
            'validate' => function($value) {
                // Check if directory is writable or can be created
                return is_writable(dirname($value)) ||
                       (is_dir(dirname($value)) && is_writable(dirname($value)));
            },
            'message' => 'parent directory must be writable',
        ],
        'CLOVER_REPORT_PATH' => [
            'required' => false,
            'validate' => function($value) {
                // Check if directory is writable or can be created
                return is_writable(dirname($value)) ||
                       (is_dir(dirname($value)) && is_writable(dirname($value)));
            },
            'message' => 'parent directory must be writable',
        ],
    ];

    // Find the project root dynamically.
    $project_dir = find_project_root(__DIR__);
    if (null === $project_dir) {
        echo esc_cli(COLOR_RED . 'Error: Could not find project root. Make sure a composer.json file exists in your plugin root.' . COLOR_RESET . "\n");
        return false;
    }
    // Define for use in validation callbacks.
    if (!defined('PROJECT_DIR')) {
        define('PROJECT_DIR', $project_dir);
    }

    // Validate that the environment file exists.
    $env_file = PROJECT_DIR . '/tests/.env.testing';
    if (!file_exists($env_file)) {
        echo esc_cli(COLOR_RED . 'Error: Environment file not found at: ' . $env_file . COLOR_RESET . "\n");
        echo esc_cli('Please copy or rename .env.sample.testing to .env.testing and configure it.' . "\n");
        return false;
    }

    // Load settings from .env.testing. This populates the global $loaded_settings used by get_setting().
    $settings = load_settings_file($env_file);
    $GLOBALS['loaded_settings'] = $settings;

    // Filter settings_validation to only include those that are required or actually present in the loaded $settings
    $active_settings_to_validate = array_filter($settings_validation, function($key) use ($settings, $settings_validation) {
        return $settings_validation[$key]['required'] || array_key_exists($key, $settings);
    }, ARRAY_FILTER_USE_KEY);

    // Check required settings
    foreach ($active_settings_to_validate as $setting => $config) {
        $value = $settings[$setting] ?? null;
        $is_set = $value !== null && $value !== '';

        if ($verbose) {
            echo "Checking {$setting}: " . ($is_set ? "[SET]" : "[NOT SET]") . "\n";
        }

        if (!$is_set && $config['required']) {
            echo COLOR_RED . "ERROR: {$setting} is required but not set" . COLOR_RESET . "\n";
            $is_valid = false;
            continue;
        }

        if ($is_set && isset($config['validate'])) {
            $is_valid_value = is_callable($config['validate'])
                ? $config['validate']($value)
                : $config['validate']($value);

            if (!$is_valid_value) {
                echo COLOR_RED . "ERROR: {$setting} {$config['message']}" . COLOR_RESET . "\n";
                if ($verbose) {
                    echo "  Value: " . esc_cli($value) . "\n";
                    if (is_callable($config['validate'])) {
                        $full_path = make_path($project_dir, 'tests', $value);
                        echo "  Full path: {$full_path}\n";
                        echo "  Directory exists: " . (is_dir($full_path) ? 'yes' : 'no') . "\n";
                        echo "  Unit_Test_Case exists: " . (file_exists($full_path . '/src/Unit/Unit_Test_Case.php') ? 'yes' : 'no') . "\n";
                    }
                }
                $is_valid = false;
            } else if ($verbose) {
                echo COLOR_GREEN . "✓ {$setting} is valid" . COLOR_RESET . "\n";
            }
        }
    }

    // Check WP_TESTS_DIR or construct it from FILESYSTEM_WP_ROOT
    if (!isset($settings['WP_TESTS_DIR']) || empty($settings['WP_TESTS_DIR'])) {
        $wp_tests_dir = rtrim($settings['FILESYSTEM_WP_ROOT'], '/\\') . '/wp-content/plugins/wordpress-develop/tests/phpunit';
        if ($verbose) {
            echo "WP_TESTS_DIR not set, using default: {$wp_tests_dir}\n";
        }
    } else {
        $wp_tests_dir = $settings['WP_TESTS_DIR'];
    }

    // Verify WordPress test suite directory
    if (!is_dir($wp_tests_dir)) {
        echo COLOR_YELLOW . "WARNING: WordPress test suite directory not found: {$wp_tests_dir}" . COLOR_RESET . "\n";
        echo "  This might be okay if you're planning to download it later using setup-plugin-tests.php\n";
    } else if ($verbose) {
        echo COLOR_GREEN . "✓ WordPress test suite directory exists: {$wp_tests_dir}" . COLOR_RESET . "\n";
    }

    return $is_valid;
}

// Run the validation
$is_valid = validate_environment();

exit($is_valid ? 0 : 1);
