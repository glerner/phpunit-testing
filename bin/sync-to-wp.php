<?php
/**
 * Sync PHPUnit Testing Framework to WordPress
 *
 * This script syncs the PHPUnit Testing Framework to a WordPress plugin directory
 * and sets up the testing environment.
 *
 * Usage: php bin/sync-to-wp.php
 */

declare(strict_types=1);

// Load environment variables from .env.testing if it exists
$env_file = dirname(__DIR__) . '/.env.testing';
if (file_exists($env_file)) {
    // Read the file line by line to avoid parse_ini_file issues
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

// Define paths from environment variables
$framework_source = getenv('FRAMEWORK_SOURCE') ?: dirname(__DIR__);

// FILESYSTEM_WP_ROOT is required - no default fallback
$filesystem_wp_root = getenv('FILESYSTEM_WP_ROOT');
if (empty($filesystem_wp_root)) {
    echo "Error: FILESYSTEM_WP_ROOT environment variable is not set.\n";
    echo "Please set this in your .env.testing file or environment.\n";
    exit(1);
}

$framework_dest_name = getenv('FRAMEWORK_DEST_NAME') ?: 'gl-phpunit-testing-framework';
$framework_dest = $filesystem_wp_root . '/wp-content/plugins/' . $framework_dest_name;

echo "Using paths:\n";
echo "  Framework source: $framework_source\n";
echo "  WordPress root: $filesystem_wp_root\n";
echo "  Framework destination: $framework_dest\n";

// Ensure vendor directory exists in source
if (!is_dir("$framework_source/vendor")) {
    echo "Installing composer dependencies in source...\n";
    chdir($framework_source);
    exec('composer install');
}

// Create destination directory if it doesn't exist
// Note: This might fail if we don't have permissions, but rsync will handle this case
if (!is_dir($framework_dest)) {
    @mkdir($framework_dest, 0755, true);
    if (!is_dir($framework_dest)) {
        echo "Warning: Could not create destination directory. This might be a permissions issue.\n";
        echo "If using Lando, you may need to run this command within the Lando environment.\n";
    }
}

// Build rsync command with exclusions
$rsync_exclude = [
    '.git/',
    '.gitignore',
    '.env',
    '.env.testing',
    'node_modules/',
    'vendor/',
    '.lando/',
    '.lando.yml',
    '.lando.local.yml',
];

$exclude_params = '';
foreach ($rsync_exclude as $exclude) {
    $exclude_params .= " --exclude='$exclude'";
}

// Sync framework files to WordPress plugins directory
$rsync_cmd = "rsync -av --delete $exclude_params '$framework_source/' '$framework_dest/'";
echo "Syncing framework files...\n";
echo "Command: $rsync_cmd\n";
exec($rsync_cmd, $output, $return_var);

if ($return_var !== 0) {
    echo "Error syncing framework files. rsync exited with code $return_var\n";
    echo "This might be due to permission issues or the destination directory not existing.\n";
    echo "If using Lando, try running this command inside the Lando environment:\n";
    echo "  lando ssh -c 'mkdir -p $framework_dest && cd /app && php /app/wp-content/plugins/gl-phpunit-testing-framework/bin/sync-to-wp.php'\n";
    exit(1);
}

// Copy vendor directory separately to preserve symlinks
if (is_dir("$framework_source/vendor")) {
    echo "Syncing vendor directory...\n";
    $vendor_cmd = "rsync -av --delete '$framework_source/vendor/' '$framework_dest/vendor/'";
    exec($vendor_cmd);
}

// Run composer dump-autoload in the destination directory
chdir($framework_dest);
echo "Regenerating autoloader files...\n";
exec('composer dump-autoload');

// Set up WordPress test environment for integration tests
if (getenv('SETUP_WP_TESTS') === 'true') {
    echo "Setting up WordPress test environment...\n";

    $wp_tests_dir = getenv('WP_TESTS_DIR');

    // Check if WordPress test library is available or needs to be installed
    if (!is_dir($wp_tests_dir)) {
        echo "WordPress test library not found at: $wp_tests_dir\n";

        // Check if wp-cli is available
        exec('which wp', $wp_output, $wp_return);
        if ($wp_return === 0) {
            echo "Installing WordPress test library using wp-cli...\n";
            exec("wp scaffold plugin-tests --dir='$framework_dest'");
        } else {
            echo "wp-cli not found. Please install WordPress test library manually.\n";
            echo "See: https://developer.wordpress.org/cli/commands/scaffold/plugin-tests/\n";
        }
    } else {
        echo "WordPress test library found at: $wp_tests_dir\n";
    }

    // Set up the test database if install script exists
    $install_script = "$wp_tests_dir/bin/install-wp-tests.sh";
    if (file_exists($install_script)) {
        echo "Setting up test database...\n";
        $db_name = getenv('WP_TESTS_DB_NAME') ?: 'wordpress_test';
        $db_user = getenv('WP_TESTS_DB_USER') ?: 'root';
        $db_pass = getenv('WP_TESTS_DB_PASSWORD') ?: '';
        $db_host = getenv('WP_TESTS_DB_HOST') ?: 'localhost';

        exec("$install_script $db_name $db_user $db_pass $db_host latest true");
    }
}

// Return to framework destination directory
echo "Framework files synced to: $framework_dest\n";
echo "Done (if all went well).\n";
chdir($framework_dest);

// Instructions for running tests
echo "\nTo run integration tests:\n";
echo "1. Make sure your WordPress test environment is set up\n";
echo "2. Run: composer test:integration\n";
echo "3. For unit tests: composer test:unit\n";
echo "4. For WP-Mock tests: composer test:wp-mock\n";
