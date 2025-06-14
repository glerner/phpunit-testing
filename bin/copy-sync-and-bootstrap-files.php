<?php
/**
 * Install WP PHPUnit Test Framework files
 *
 * This script installs framework files from tests/gl-phpunit-test-framework/ to their
 * required locations in the tests/ directory.
 *
 * @package WP_PHPUnit_Framework
 */

// Define paths
$project_root = dirname(__DIR__);
$framework_dir = is_dir($project_root . '/vendor/glerner/phpunit-testing')
    ? $project_root . '/vendor/glerner/phpunit-testing'
    : $project_root . '/tests/gl-phpunit-test-framework';
$target_dir = $project_root; // Base directory is now project root

// Files to copy (source => destination relative to target_dir)
$files_to_copy = [
    // Bootstrap files go to tests/bootstrap/
    'tests/bootstrap/bootstrap.php' => 'tests/bootstrap/bootstrap.php',
    'tests/bootstrap/bootstrap-unit.php' => 'tests/bootstrap/bootstrap-unit.php',
    'tests/bootstrap/bootstrap-wp-mock.php' => 'tests/bootstrap/bootstrap-wp-mock.php',
    'tests/bootstrap/bootstrap-integration.php' => 'tests/bootstrap/bootstrap-integration.php',
    'tests/bootstrap/bootstrap-framework.php' => 'tests/bootstrap/bootstrap-framework.php',
    'tests/bootstrap/TestListener.php' => 'tests/bootstrap/TestListener.php',

    // PHPUnit config files go to tests/bootstrap/
    'tests/bootstrap/phpunit-unit.xml.dist' => 'tests/bootstrap/phpunit-unit.xml.dist',
    'tests/bootstrap/phpunit-wp-mock.xml.dist' => 'tests/bootstrap/phpunit-wp-mock.xml.dist',
    'tests/bootstrap/phpunit-integration.xml.dist' => 'tests/bootstrap/phpunit-integration.xml.dist',
    'tests/bootstrap/phpunit-multisite.xml.dist' => 'tests/bootstrap/phpunit-multisite.xml.dist',
    'tests/bootstrap/phpunit-framework-tests.xml.dist' => 'tests/bootstrap/phpunit-framework-tests.xml.dist',

    // Bin files
    'bin/sync-and-test.php' => 'bin/sync-and-test.php.dist',
    'bin/sync-to-wp.php' => 'bin/sync-to-wp.php.dist',
    'bin/copy-sync-and-bootstrap-files.php' => 'bin/copy-sync-and-bootstrap-files.php.copy',

    // Other framework files
    '.env.sample.testing' => 'tests/.env.sample.testing',
];

// Create target directories if they don't exist
$directories = [
    $target_dir . '/tests/bootstrap',
    $target_dir . '/bin',
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
                echo "  Updated: $dest_path\n";
                $copied++;
            } else {
                echo "  Failed to copy: $dest_path\n";
            }
        } else {
            $skipped++;
        }
    } else {
        echo "  Source not found: $source_path\n";
    }
}

echo "Done. Updated $copied files, $skipped files were already up to date.\n";

// Make .env.testing read-only for security
$env_file = $target_dir . '/tests/.env.testing';
if (file_exists($env_file)) {
    chmod($env_file, 0644);
    echo "\nNote: $env_file permissions set to 0644 for security.\n";
}

echo "\nTo run tests, use (from plugin root):\n";
echo "  tests/vendor/bin/phpunit -c tests/bootstrap/phpunit-unit.xml.dist\n";
echo "  tests/vendor/bin/phpunit -c tests/bootstrap/phpunit-wp-mock.xml.dist\n";
echo "  tests/vendor/bin/phpunit -c tests/bootstrap/phpunit-integration.xml.dist\n";

echo "\nFor verbose output, add -v or -vvv to the command. Example:\n";
echo "  tests/vendor/bin/phpunit -vvv -c tests/bootstrap/phpunit-unit.xml.dist\n";
