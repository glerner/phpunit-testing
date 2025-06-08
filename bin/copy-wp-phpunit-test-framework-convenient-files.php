<?php
/**
 * Copy WP PHPUnit Test Framework files to convenient locations
 *
 * This script copies framework files from tests/gl-phpunit-test-framework/ to their
 * respective locations in the tests/ directory for easier access and customization.
 *
 * @package WP_PHPUnit_Framework
 */

// Define paths
$project_root = dirname(__DIR__);
$framework_dir = $project_root . '/tests/gl-phpunit-test-framework';
$target_dir = $project_root . '/tests';

// Files to copy (source => destination relative to target_dir)
$files_to_copy = [
    // Config files
    'config/phpunit-unit.xml.dist' => 'config/phpunit-unit.xml.dist',
    'config/phpunit-wp-mock.xml.dist' => 'config/phpunit-wp-mock.xml.dist',
    'config/phpunit-integration.xml.dist' => 'config/phpunit-integration.xml.dist',

    // Bootstrap files from tests/bootstrap/
    'tests/bootstrap/bootstrap.php' => 'bootstrap/bootstrap.php',
    'tests/bootstrap/bootstrap-unit.php' => 'bootstrap/bootstrap-unit.php',
    'tests/bootstrap/bootstrap-wp-mock.php' => 'bootstrap/bootstrap-wp-mock.php',
    'tests/bootstrap/bootstrap-integration.php' => 'bootstrap/bootstrap-integration.php',
    'tests/bootstrap/bootstrap-framework.php' => 'bootstrap/bootstrap-framework.php',

    // copy to .dist to not overwrite any developer changes
    'bin/sync-and-test.php' => 'bin/sync-and-test.php.dist',
    'bin/sync-to-wp.php' => 'bin/sync-to-wp.php.dist',

    // Other framework files that might be needed
    '.env.sample.testing' => '.env.sample.testing',
];

// Create target directories if they don't exist
$directories = [
    $target_dir . '/config',
    $target_dir . '/bootstrap',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Copy files
$copied = 0;
$skipped = 0;

foreach ($files_to_copy as $source => $dest) {
    $source_path = $framework_dir . '/' . $source;
    $dest_path = $target_dir . '/' . $dest;

    // Only copy if source exists and destination doesn't exist or source is newer
    if (file_exists($source_path)) {
        if (!file_exists($dest_path) || filemtime($source_path) > filemtime($dest_path)) {
            if (copy($source_path, $dest_path)) {
                echo "  Updated: $dest\n";
                $copied++;
            } else {
                echo "  Failed to copy: $dest\n";
            }
        } else {
            $skipped++;
        }
    } else {
        echo "  Source not found: $source\n";
    }
}

// echo "Done. Updated $copied files, $skipped files were already up to date.\n";

// Make .env.testing read-only for security
$env_file = $target_dir . '/.env.testing';
if (file_exists($env_file)) {
    chmod($env_file, 0644);
}
