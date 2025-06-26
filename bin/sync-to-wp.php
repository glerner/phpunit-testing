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

namespace WP_PHPUnit_Framework\Bin;

use stdClass;

// Ensure the framework functions are available.
// Using require_once ensures it's loaded only once, even if this script is included by another.
require_once __DIR__ . '/framework-functions.php';

use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\colored_message;
use function WP_PHPUnit_Framework\display_composer_test_instructions;
use function WP_PHPUnit_Framework\esc_cli;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\is_lando_environment;

// Global exception handler to catch and display any uncaught exceptions.
set_exception_handler(
	function ( \Throwable $e ): void {
		// These constants and functions are defined in the included framework-functions.php
		colored_message( 'UNCAUGHT EXCEPTION: ' . get_class( $e ), 'red' );
		colored_message( 'Message: ' . $e->getMessage(), 'red' );
		colored_message( 'File: ' . $e->getFile() . ' (Line ' . $e->getLine() . ')', 'red' );
		colored_message( 'Stack trace:', 'red' );
		echo esc_cli( $e->getTraceAsString() . "\n" );
		exit( 1 );
	}
);

// phpcs:set WordPress.Security.EscapeOutput customEscapingFunctions[] esc_cli
// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.DB.RestrictedFunctions
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound


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
    $rsync_cmd = "rsync -av --delete --itemize-changes $exclude_params '$plugin_folder/' '$your_plugin_dest/'";
    echo esc_cli("Syncing framework files...\n");
    echo esc_cli("Command: $rsync_cmd\n");
    passthru($rsync_cmd, $return_var);

    if ($return_var !== 0) {
        colored_message("Error syncing project files. rsync exited with code $return_var", 'red');
        if (!empty($output)) {
            colored_message("rsync output:\n" . implode("\n", $output), 'yellow');
        }
        exit($return_var);
    }

    // Check for .env.testing in the destination and provide instructions if it's missing.
    $dest_env_file = $your_plugin_dest . '/tests/.env.testing';
    if (!file_exists($dest_env_file)) {
        $source_env_file = $plugin_folder . '/tests/.env.testing';
        $source_sample_env_file = $plugin_folder . '/tests/.env.sample.testing';

        colored_message("Warning: The configuration file .env.testing is missing in the destination.", 'yellow');
        colored_message("Your tests will fail without it. Please create it.", 'yellow');

        if (file_exists($source_env_file)) {
            colored_message("Your source project contains a .env.testing file. You can copy it by running:", 'cyan');
            echo "cp " . escapeshellarg($source_env_file) . " " . escapeshellarg($dest_env_file) . "\nThen edit it to make sure it is correct for your local WordPress database and path settings\n\n";
        } elseif (file_exists($source_sample_env_file)) {
            colored_message("Your source project contains a sample configuration. You can copy it by running:", 'cyan');
            echo "cp " . escapeshellarg($source_sample_env_file) . " " . escapeshellarg($dest_env_file) . "\n";
            colored_message("IMPORTANT: You must edit the new .env.testing file with your local database and path settings.", 'red');
        } else {
            colored_message("Could not find .env.testing or .env.sample.testing in your source project.", 'red');
            colored_message("Please create " . $dest_env_file . " manually.", 'red');
        }
    }

    // Copy vendor directory separately to preserve symlinks
    if (is_dir("$plugin_folder/tests/vendor")) {
        echo esc_cli("Syncing vendor directory...\n");
        error_log("Syncing vendor directory... $plugin_folder/tests/vendor/ to $your_plugin_dest/tests/vendor/", 3, '/tmp/phpunit-settings-debug.log');

        $vendor_cmd = "rsync -av --delete --itemize-changes '$plugin_folder/tests/vendor/' '$your_plugin_dest/tests/vendor/'";
        passthru($vendor_cmd, $return_var);
        if ($return_var !== 0) {
            colored_message("Error syncing vendor directory. rsync exited with code $return_var", 'red');
            exit($return_var);
        }
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

}

main();
