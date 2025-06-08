<?php
/**
 * Sync This Plugin to WordPress
 *
 * This script syncs the plugin to a WordPress plugin directory
 * and sets up the testing environment.
 *
 * Usage: php bin/sync-to-wp.php
 * also included in `php bin/sync-and-test.php`
 *
 * @package Sync_To_WP
 */

declare(strict_types=1);

namespace Sync_To_WP;

use stdClass;

// phpcs:set WordPress.Security.EscapeOutput customEscapingFunctions[] esc_cli
// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.DB.RestrictedFunctions
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound


// Debug: Show which file is being executed
echo "[DEBUG] Executing: " . __FILE__ . "\n";


/**
 * Utility functions
 *
 * These functions handle command formatting and configuration reading.
 */

// phpcs:set WordPress.Security.EscapeOutput customEscapingFunctions[] esc_cli
// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.DB.RestrictedFunctions
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

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
 * Escape a string for CLI output
 *
 * @param string $text Text to escape
 * @return string
 */
function esc_cli( string $text ): string {
    return $text;
}

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

    // Check our loaded settings (already loaded from .env file)
    global $loaded_settings;
    if (isset($loaded_settings[ $name ])) {
        return $loaded_settings[ $name ];
    }

	// Check loaded settings (ensure it's an array)
	if (is_array($loaded_settings) && isset($loaded_settings[$name])) {
		return $loaded_settings[$name];
	}

    /* Don't recursively set, if there is an error
    $error_log_file = get_setting('TEST_ERROR_LOG', '/tmp/phpunit-testing.log');
    */
    if (!isset($error_log_file)) {
        $error_log_file = '/tmp/phpunit-testing.log';
    }


    // Silently log critical setting issues to error log without screen output
    if (($name === 'WP_ROOT' || $name === 'FILESYSTEM_WP_ROOT' || $name === 'YOUR_PLUGIN_SLUG')) {
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
 * Load settings from a .env file
 *
 * @param string $env_file Path to the .env file
 * @return array Array of settings variables
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


// No composer.json merging needed - using the one from the source directory

// ================= MAIN SYNC LOGIC =================

function main() {
    define('SCRIPT_DIR', __DIR__);
    define('PROJECT_DIR', dirname(__DIR__,1));

    // Load settings from environment file
    $env_file = get_setting('ENV_FILE', PROJECT_DIR . '/.env.ini');
    global $loaded_settings;
    $loaded_settings = load_settings_file($env_file);

    // Define paths from settings
    $plugin_folder = get_setting('PLUGIN_FOLDER', PROJECT_DIR);

    // FILESYSTEM_WP_ROOT is required - no default fallback
    $filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT');
    if (empty($filesystem_wp_root)) {
        echo esc_cli("Error: FILESYSTEM_WP_ROOT setting is not set.\n");
        echo esc_cli("Please set this in your .env.testing file or environment.\n");
        exit(1);
    }

    // Get plugin slug and folder path from settings
    $your_plugin_slug = get_setting('YOUR_PLUGIN_SLUG', 'my-wordpress-plugin');
    $folder_in_wordpress = get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');
    $your_plugin_dest = $filesystem_wp_root . '/' . $folder_in_wordpress . '/' . $your_plugin_slug;

    echo esc_cli("Using paths:\n");
    echo esc_cli("  Plugin Folder: $plugin_folder\n");
    echo esc_cli("  WordPress root: $filesystem_wp_root\n");
    echo esc_cli("  Plugin destination: $your_plugin_dest\n");

    // Ensure vendor directory exists in source
    // Set up source and destination paths
    $source = PROJECT_DIR;
    $destination = $your_plugin_dest;

    // Create destination directory if it doesn't exist
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    // Debug: Show current working directory and paths
    echo "Current working directory: " . getcwd() . "\n";
    echo "Source directory: $source\n";
    echo "Destination directory: $destination\n";

    // Copy composer.json to destination
    $composer_src = $source . '/composer.json';
    $composer_dest = $destination . '/composer.json';

    if (file_exists($composer_src)) {
        if (!copy($composer_src, $composer_dest)) {
            echo "Warning: Could not copy composer.json to $composer_dest\n";
        } else {
            echo "Copied composer.json to $composer_dest\n";
        }
    }

    // Create destination directory if it doesn't exist
    // Note: This might fail if we don't have permissions, but rsync will handle this case
    if (!is_dir($your_plugin_dest)) {
        @mkdir($your_plugin_dest, 0755, true);
        if (!is_dir($your_plugin_dest)) {
            echo esc_cli("Warning: Could not create destination directory. This might be a permissions issue.\n");
            echo esc_cli("If using Lando, you may need to run this command within the Lando environment.\n");
        }
    }

    // Build rsync command with exclusions
    $rsync_exclude = array(
        '.git/',
        '.gitignore',
        '.env',
        '.env.testing',
        'node_modules/',
        'vendor/',
        '.lando/',
        '.lando.yml',
        '.lando.local.yml',
    );

    $exclude_params = '';
    foreach ($rsync_exclude as $exclude) {
        $exclude_params .= " --exclude='$exclude'";
    }

    // Sync project files to WordPress plugins directory
    chdir($plugin_folder);
    $rsync_cmd = "rsync -av --delete $exclude_params '$plugin_folder/' '$your_plugin_dest/'";
    echo esc_cli("Syncing framework files...\n");
    echo esc_cli("Command: $rsync_cmd\n");
    exec($rsync_cmd, $output, $return_var);

    if ($return_var !== 0) {
        echo esc_cli("Error syncing framework files. rsync exited with code $return_var\n");
        echo esc_cli("This might be due to permission issues or the destination directory not existing.\n");
        echo esc_cli("If using Lando, try running this command inside the Lando environment:\n");
        echo esc_cli("  lando ssh -c 'mkdir -p $your_plugin_dest && cd /app && php /app/wp-content/plugins/$your_plugin_slug/bin/sync-to-wp.php'\n");
        exit(1);
    }

    // Copy vendor directory separately to preserve symlinks
    if (is_dir("$plugin_folder/tests/vendor")) {
        echo esc_cli("Syncing vendor directory...\n");
        error_log("Syncing vendor directory... $plugin_folder/tests/vendor/ to $your_plugin_dest/tests/vendor/", 3, '/tmp/phpunit-settings-debug.log');

        $vendor_cmd = "rsync -av --delete '$plugin_folder/tests/vendor/' '$your_plugin_dest/tests/vendor/'";
        chdir($plugin_folder);
        exec($vendor_cmd);
    }

    // Run composer dump-autoload in the destination directory
    if (file_exists($your_plugin_dest . '/composer.json')) {
        $cwd = getcwd();
        chdir($your_plugin_dest);
        echo esc_cli("Regenerating autoloader files...\n");
        exec('composer dump-autoload');
        chdir($cwd);
    }

    // Note: For setting up the WordPress test environment, use the setup-plugin-tests.php script
    // Example: php bin/setup-plugin-tests.php

    // Return to framework destination directory
    echo esc_cli("Plugin files synced to: $your_plugin_dest\n");
    echo esc_cli("Done (if all went well).\n");
    chdir($your_plugin_dest);

    // Instructions for running tests
    echo esc_cli("\nTo run tests, use the appropriate Composer script from $your_plugin_dest:\n");
    echo esc_cli("composer test:integration  # For integration tests\n");
    echo esc_cli("composer test:unit        # For unit tests\n");
    echo esc_cli("composer test:wp-mock     # For WP-Mock tests\n");
    echo esc_cli("composer test             # To run all test types\n\n");
}

main();
