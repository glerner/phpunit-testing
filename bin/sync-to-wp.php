<?php
/**
 * Sync PHPUnit Testing Framework to WordPress
 *
 * This script syncs the PHPUnit Testing Framework to a WordPress plugin directory
 * and sets up the testing environment.
 *
 * Usage: php bin/sync-to-wp.php
 *
 * @package WP_PHPUnit_Framework
 */

// phpcs:set WordPress.Security.EscapeOutput customEscapingFunctions[] esc_cli
// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.DB.RestrictedFunctions
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Bin;

use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\esc_cli;

/* Define script constants as namespace constants
 * SCRIPT_DIR should be your-plugin/tests/bin
 * PROJECT_DIR should be your-plugin
*/
define('SCRIPT_DIR', __DIR__);
define('PROJECT_DIR', dirname(SCRIPT_DIR,2));

// Include the framework utility functions
require_once SCRIPT_DIR . '/framework-functions.php';

// Load settings from .env.testing
$env_file = dirname(__DIR__) . '/.env.testing';
global $loaded_settings;
$loaded_settings = load_settings_file($env_file);

// Define paths from settings
$plugin_folder = get_setting('PLUGIN_FOLDER', dirname(__DIR__));

// FILESYSTEM_WP_ROOT is required - no default fallback
$filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT');
if (empty($filesystem_wp_root)) {
    echo esc_cli("Error: FILESYSTEM_WP_ROOT setting is not set.\n");
    echo esc_cli("Please set this in your .env.testing file or environment.\n");
    exit(1);
}

// Get plugin slug and folder path from settings
$your_plugin_slug = get_setting('YOUR_PLUGIN_SLUG', 'gl-phpunit-testing-framework');
$folder_in_wordpress = get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');
$your_plugin_dest = $filesystem_wp_root . '/' . $folder_in_wordpress . '/' . $your_plugin_slug;

$test_error_log = get_setting('TEST_ERROR_LOG', '/tmp/phpunit-testing.log');


echo esc_cli("Using paths:\n");
echo esc_cli("  Plugin Folder: $plugin_folder\n");
echo esc_cli("  WordPress root: $filesystem_wp_root\n");
echo esc_cli("  Plugin destination: $your_plugin_dest\n");

// Ensure vendor directory exists in source
// This is a reason are requiring tests be in $plugin_folder/tests
if (!is_dir("$plugin_folder/tests/vendor")) {
    echo esc_cli("Installing composer dependencies in $plugin_folder/tests...\n");
    chdir($plugin_folder . '/tests' );
    exec('composer install');
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
// Should rsync tests/.env.testing
$rsync_exclude = array(
    '.git/',
    '.gitignore',
    '.env',
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
    echo esc_cli("  lando ssh -c 'mkdir -p $your_plugin_dest && cd /app && php /app/wp-content/plugins/gl-phpunit-testing-framework/bin/sync-to-wp.php'\n");
    error_log("Error syncing framework files. rsync exited with code $return_var\n", 3, $test_error_log);
    exit(1);
}

// Copy vendor directory separately to preserve symlinks
if (is_dir("$plugin_folder/tests/vendor")) {
    echo esc_cli("Syncing vendor directory...\n");
    error_log("Syncing vendor directory... $plugin_folder/tests/vendor/ to $your_plugin_dest/tests/vendor/\n", 3, $test_error_log);

    $vendor_cmd = "rsync -av --delete '$plugin_folder/tests/vendor/' '$your_plugin_dest/tests/vendor/'";
    chdir($plugin_folder);
    exec($vendor_cmd);
}

// Run composer dump-autoload in the destination directory
// composer.json for testing programs is in tests/composer.json
chdir($your_plugin_dest . '/tests' );
echo esc_cli("Regenerating autoloader files...\n");
exec('composer dump-autoload');

// Note: For setting up the WordPress test environment, use the setup-plugin-tests.php script
// Example: php bin/setup-plugin-tests.php

// Return to framework destination directory
echo esc_cli("Plugin files synced to: $your_plugin_dest\n");
echo esc_cli("Done (if all went well).\n");
chdir($your_plugin_dest);

// Instructions for running tests
echo esc_cli("\nTo run integration tests:\n");
echo esc_cli("1. Make sure your WordPress test environment is set up\n");
echo esc_cli("2. Run: composer test:integration\n");
echo esc_cli("3. For unit tests: composer test:unit\n");
echo esc_cli("4. For WP-Mock tests: composer test:wp-mock\n\n");
