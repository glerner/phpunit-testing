<?php
/**
 * Bootstrap file for WP_Mock tests
 *
 * Handles initialization of testing environment for WP_Mock tests.
 * Sets up WP_Mock and defines common WordPress constants and functions.
 *
 * For more information on WP_Mock usage and strategies, see:
 * @see /docs/guides/phpunit-testing-tutorial.md#mocking-strategies
 * @see /docs/guides/phpunit-testing-tutorial.md#wp-mock-tests
 *
 * @package GL_WordPress_Testing_Framework
 * @subpackage Bootstrap
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Bootstrap;

// Initialize error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');

// Define WordPress constants using get_setting
if (!defined('ABSPATH')) {
	$abspath = get_setting('WP_ROOT', sys_get_temp_dir() . '/wordpress/');
	define('ABSPATH', $abspath . '/');
}

if (!defined('WP_DEBUG')) {
	define('WP_DEBUG', get_setting('WP_DEBUG', true));
}

// Common WordPress constants
define('WPINC', 'wp-includes');

// Use get_setting for content directories if available
$wp_content_dir = get_setting('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('WP_CONTENT_DIR', $wp_content_dir);

$wp_plugin_dir = get_setting('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
define('WP_PLUGIN_DIR', $wp_plugin_dir);

// Initialize WP_Mock
echo "Initializing WP_Mock\n";
\WP_Mock::bootstrap();

// Register shutdown function to verify expectations
register_shutdown_function(function() {
	try {
	    \WP_Mock::tearDown();
	} catch (\Exception $e) {
	    echo "WP_Mock expectations failed: " . $e->getMessage() . "\n";
	    exit(1);
	}
});

echo "WP_Mock bootstrap complete\n";
