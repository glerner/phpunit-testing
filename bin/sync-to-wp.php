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

// Include framework functions
require_once dirname(__DIR__) . '/tests/framework/framework-functions.php';

use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;
use function WP_PHPUnit_Framework\get_setting;
use function WP_PHPUnit_Framework\esc_cli;

// Load settings from .env.testing
$env_file = dirname(__DIR__) . '/.env.testing';
global $loaded_settings;
$loaded_settings = load_settings_file($env_file);


// Define paths from settings
$framework_source = get_setting('FRAMEWORK_SOURCE', dirname(__DIR__));

// FILESYSTEM_WP_ROOT is required - no default fallback
$filesystem_wp_root = get_setting('FILESYSTEM_WP_ROOT');
if (empty($filesystem_wp_root)) {
    echo esc_cli("Error: FILESYSTEM_WP_ROOT setting is not set.\n");
    echo esc_cli("Please set this in your .env.testing file or environment.\n");
    exit(1);
}

// Get plugin slug and folder path from settings
$framework_dest_name = \WP_PHPUnit_Framework\get_setting('YOUR_PLUGIN_SLUG', 'gl-phpunit-testing-framework');
$folder_in_wordpress = \WP_PHPUnit_Framework\get_setting('FOLDER_IN_WORDPRESS', 'wp-content/plugins');
$framework_dest = $filesystem_wp_root . '/' . $folder_in_wordpress . '/' . $framework_dest_name;

echo esc_cli("Using paths:\n");
echo esc_cli("  Framework source: $framework_source\n");
echo esc_cli("  WordPress root: $filesystem_wp_root\n");
echo esc_cli("  Framework destination: $framework_dest\n");

// Ensure vendor directory exists in source
if (!is_dir("$framework_source/vendor")) {
    echo esc_cli("Installing composer dependencies in source...\n");
    chdir($framework_source);
    exec('composer install');
}

// Create destination directory if it doesn't exist
// Note: This might fail if we don't have permissions, but rsync will handle this case
if (!is_dir($framework_dest)) {
    @mkdir($framework_dest, 0755, true);
    if (!is_dir($framework_dest)) {
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

// Sync framework files to WordPress plugins directory
$rsync_cmd = "rsync -av --delete $exclude_params '$framework_source/' '$framework_dest/'";
echo esc_cli("Syncing framework files...\n");
echo esc_cli("Command: $rsync_cmd\n");
exec($rsync_cmd, $output, $return_var);

if ($return_var !== 0) {
    echo esc_cli("Error syncing framework files. rsync exited with code $return_var\n");
    echo esc_cli("This might be due to permission issues or the destination directory not existing.\n");
    echo esc_cli("If using Lando, try running this command inside the Lando environment:\n");
    echo esc_cli("  lando ssh -c 'mkdir -p $framework_dest && cd /app && php /app/wp-content/plugins/gl-phpunit-testing-framework/bin/sync-to-wp.php'\n");
    exit(1);
}

// Copy vendor directory separately to preserve symlinks
if (is_dir("$framework_source/vendor")) {
    echo esc_cli("Syncing vendor directory...\n");
    $vendor_cmd = "rsync -av --delete '$framework_source/vendor/' '$framework_dest/vendor/'";
    exec($vendor_cmd);
}

// Run composer dump-autoload in the destination directory
chdir($framework_dest);
echo esc_cli("Regenerating autoloader files...\n");
exec('composer dump-autoload');

// Note: For setting up the WordPress test environment, use the setup-plugin-tests.php script
// Example: php bin/setup-plugin-tests.php

// Return to framework destination directory
echo esc_cli("Framework files synced to: $framework_dest\n");
echo esc_cli("Done (if all went well).\n");
chdir($framework_dest);

// Instructions for running tests
echo esc_cli("\nTo run integration tests:\n");
echo esc_cli("1. Make sure your WordPress test environment is set up\n");
echo esc_cli("2. Run: composer test:integration\n");
echo esc_cli("3. For unit tests: composer test:unit\n");
echo esc_cli("4. For WP-Mock tests: composer test:wp-mock\n\n");
