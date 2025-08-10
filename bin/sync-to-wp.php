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
use function WP_PHPUnit_Framework\format_lando_exec_command;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\has_cli_flag;
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

colored_message("==== Started sync-to-wp main() =====\n");

    // Parse command line arguments
    $options = getopt('', [
        'filesystem-wp-root:',
        'plugin-slug:',
        'folder-in-wordpress:',
        'project-dir:',
    ]);

    // Load settings from environment file
    $env_file = get_setting('ENV_FILE', PROJECT_DIR . '/.env.ini');
    global $loaded_settings;
    $loaded_settings = load_settings_file($env_file);

    // Define paths from settings, prioritizing command-line arguments
    $project_dir = $options['project-dir'] ?? PROJECT_DIR;
    $plugin_folder = get_setting('PLUGIN_FOLDER', $project_dir);

    // FILESYSTEM_WP_ROOT is required - check command-line first, then settings
    $filesystem_wp_root = $options['filesystem-wp-root'] ?? get_setting('FILESYSTEM_WP_ROOT');
    if (empty($filesystem_wp_root)) {
        echo esc_cli("Error: FILESYSTEM_WP_ROOT setting is not set.\n");
        echo esc_cli("Please set this in your .env.testing file or environment.\n");
        exit(1);
    }

    // Get plugin slug and folder path from command-line or settings
    $your_plugin_slug = $options['plugin-slug'] ?? get_setting('YOUR_PLUGIN_SLUG', 'my-wordpress-plugin');
    $folder_in_wordpress = $options['folder-in-wordpress'] ?? get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');
    $your_plugin_dest = $filesystem_wp_root . '/' . $folder_in_wordpress . '/' . $your_plugin_slug;
    
    colored_message("Using parameters from command-line and/or settings file", 'blue');

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
        colored_message("See rsync's output above for details.", 'yellow');
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

    colored_message("==== About to sync vendor directory =====\n");

    // Determine the correct vendor directory path using the framework setting
    $test_framework_dir = get_setting('TEST_FRAMEWORK_DIR', 'gl-phpunit-test-framework');
    $source_vendor_dir = "$plugin_folder/tests/$test_framework_dir/vendor";
    $dest_vendor_dir = "$your_plugin_dest/tests/$test_framework_dir/vendor";

    // Copy vendor directory separately to preserve symlinks
    colored_message("Syncing test framework vendor directory to $dest_vendor_dir\n", 'blue');


    if (is_dir($source_vendor_dir)) {

        // Ensure the parent directory exists in the destination
        if (!is_dir(dirname($dest_vendor_dir))) {
            mkdir(dirname($dest_vendor_dir), 0755, true);
        }

        $vendor_cmd = "rsync -av --delete --itemize-changes '$source_vendor_dir/' '$dest_vendor_dir/'";

        if (has_cli_flag('verbose')) {
            colored_message("Executing: $vendor_cmd", 'cyan');
        }
        passthru($vendor_cmd, $return_var);

        if ($return_var !== 0) {
            colored_message("Error syncing vendor directory. rsync exited with code $return_var", 'red');
            exit($return_var);
        }
    } else {
        if (has_cli_flag('verbose')) {
            colored_message("Test framework vendor directory not found at $source_vendor_dir. Skipping sync.", 'yellow');
        }
    }

    // Run composer dump-autoload in the destination directory
    if (file_exists($your_plugin_dest . '/composer.json')) {
        $cwd = getcwd();
        chdir($your_plugin_dest);
        colored_message("Regenerating autoloader files in: " . getcwd(), 'yellow');

        // Check if lando command is available in the PATH
        $lando_available = !empty(shell_exec('which lando 2>/dev/null'));
        
        if (!$lando_available) {
            colored_message("ERROR: The 'lando' command was not found in your PATH.", 'red');
            colored_message("Please ensure Lando is installed and in your PATH by running:", 'yellow');
            colored_message("  source ~/.bashrc", 'cyan');
            colored_message("Then run this script again.", 'yellow');
            chdir($cwd);
            exit(1);
        }

        // Use the dedicated helper function to build the Lando exec command.
        $command = format_lando_exec_command(['composer install']);
        echo esc_cli("Executing: $command\n");

        // Execute the command, capturing all output for verification.
        exec("$command 2>&1", $output, $return_var);
        echo "\n";

        // Display the results.
        echo esc_cli(implode("\n", $output));
        echo "\n";

        echo esc_cli("Command finished with exit code: $return_var\n");
        chdir($cwd);
    }

    // Note: For setting up the WordPress test environment, use the setup-plugin-tests.php script from the framework, do *not* copy to bin
    // Example: php tests/gl-phpunit-test-framework/bin/setup-plugin-tests.php


    // Return to framework destination directory
    echo esc_cli("Plugin files synced to: $your_plugin_dest\n");
    echo esc_cli("Done (if all went well).\n");
    chdir($your_plugin_dest);

}

main();
