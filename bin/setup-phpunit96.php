<?php

namespace WP_PHPUnit_Framework;
use function WP_PHPUnit_Framework\format_lando_exec_command;

// Get global variables from the parent script
global $wp_root, $filesystem_wp_root, $plugin_slug;
global $ssh_command, $original_dir;
global $wp_tests_dir_setting;  // WordPress Test Suite Path, e.g. FILESYSTEM_WP_ROOT/wp-content/plugins/wordpress-develop/tests/phpunit


// Define the path to PHPUnit 9.6 directory
$phpunit96_dir = "$filesystem_wp_root/wp-content/plugins/$plugin_slug/tests/phpunit96";

echo "\nInstalling PHPUnit 9.6 for WordPress Test Suite integration tests in $phpunit96_dir\n";
echo "Plugin slug: $plugin_slug\n";
echo "Original dir: $original_dir\n";

// Create directory if it doesn't exist
if (!is_dir($phpunit96_dir)) {
    mkdir($phpunit96_dir, 0755, true);
}

// Create composer.json if it doesn't exist
$composer_json = "$phpunit96_dir/composer.json";
if (!file_exists($composer_json)) {
    file_put_contents($composer_json, json_encode([
        'name' => 'wp-phpunit-framework/phpunit96',
        'description' => 'PHPUnit 9.6 for WordPress Test Suite integration tests',
        'require' => [
            'phpunit/phpunit' => '^9.6'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Install PHPUnit 9.6
// format_lando_exec_command(array $command_parts, string $service = 'appserver', string $debug_flag = '')
// Check if we're using Lando
if (strpos($ssh_command, 'lando ssh') === 0) {
    // Run composer inside the container
    echo "Running composer install inside container, from $phpunit96_dir\n";
    chdir($phpunit96_dir);
    $lando_command = format_lando_exec_command(['composer', 'install', '--no-dev']);
    echo "Executing: $lando_command\n";
    system($lando_command);
    chdir($original_dir);
} else {
    // Run composer directly
    $cwd = getcwd();
    chdir($phpunit96_dir);
    system('composer install --no-dev');
    chdir($cwd);
}

echo "PHPUnit 9.6 installed successfully at {$phpunit96_dir}/vendor/bin/phpunit\n";
