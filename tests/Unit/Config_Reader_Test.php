<?php
/**
 * Tests for configuration file reading
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Unit
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Unit;

use WP_PHPUnit_Framework\Unit\Unit_Test_Case;

/**
 * Test case for configuration file reading
 */
class Config_Reader_Test extends Unit_Test_Case {

	/**
	 * Temporary .env.testing file path
	 *
	 * @var string
	 */
	private $temp_env_file;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Create a temporary .env.testing file for testing
		$this->temp_env_file = sys_get_temp_dir() . '/test-env-' . uniqid() . '.testing';
		$env_content = <<<EOT
# WordPress paths
FILESYSTEM_WP_ROOT=/path/to/wordpress
FILESYSTEM_PLUGIN_ROOT=/path/to/plugin
FRAMEWORK_DEST_NAME=test-plugin

# Database settings
DB_NAME=wordpress
DB_USER=wp_user
DB_PASSWORD=wp_password
DB_HOST=localhost
DB_CHARSET=utf8
DB_COLLATE=
TABLE_PREFIX=wp_

# Test database settings
TEST_DB_NAME=wordpress_test
TEST_TABLE_PREFIX=wp_test_
EOT;
		file_put_contents($this->temp_env_file, $env_content);
	}

	/**
	 * Clean up test environment
	 */
	protected function tearDown(): void {
		// Remove temporary file
		if (file_exists($this->temp_env_file)) {
			unlink($this->temp_env_file);
		}
		
		parent::tearDown();
	}

	/**
	 * Test that environment variables are loaded correctly from .env.testing file
	 */
	public function test_load_env_file(): void {
		// Include the file with the function
		require_once dirname(dirname(__DIR__)) . '/bin/setup-plugin-tests.php';
		
		// Mock the function to use our temporary file
		$settings = load_env_file($this->temp_env_file);
		
		// Test that settings were loaded correctly
		$this->assertIsArray($settings);
		$this->assertEquals('/path/to/wordpress', $settings['FILESYSTEM_WP_ROOT']);
		$this->assertEquals('/path/to/plugin', $settings['FILESYSTEM_PLUGIN_ROOT']);
		$this->assertEquals('test-plugin', $settings['FRAMEWORK_DEST_NAME']);
		$this->assertEquals('wordpress', $settings['DB_NAME']);
		$this->assertEquals('wp_user', $settings['DB_USER']);
		$this->assertEquals('wp_password', $settings['DB_PASSWORD']);
		$this->assertEquals('localhost', $settings['DB_HOST']);
		$this->assertEquals('wordpress_test', $settings['TEST_DB_NAME']);
		$this->assertEquals('wp_test_', $settings['TEST_TABLE_PREFIX']);
	}

	/**
	 * Test that database settings are properly extracted
	 */
	public function test_get_phpunit_database_settings(): void {
		// Include the file with the function
		require_once dirname(dirname(__DIR__)) . '/bin/setup-plugin-tests.php';
		
		// Create mock settings
		$settings = [
			'DB_HOST' => 'localhost',
			'DB_USER' => 'wp_user',
			'DB_PASSWORD' => 'wp_password',
			'TEST_DB_NAME' => 'wordpress_test',
			'TEST_TABLE_PREFIX' => 'wp_test_',
		];
		
		// Get database settings
		$db_settings = get_phpunit_database_settings($settings);
		
		// Test that database settings were extracted correctly
		$this->assertIsArray($db_settings);
		$this->assertEquals('localhost', $db_settings['db_host']);
		$this->assertEquals('wp_user', $db_settings['db_user']);
		$this->assertEquals('wp_password', $db_settings['db_pass']);
		$this->assertEquals('wordpress_test', $db_settings['db_name']);
		$this->assertEquals('wp_test_', $db_settings['table_prefix']);
	}
}
