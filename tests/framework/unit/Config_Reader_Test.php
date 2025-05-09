<?php
/**
 * Tests for configuration reading functions
 *
 * This file demonstrates how to test the framework's utility functions
 * for reading configuration files.
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Unit
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Unit;

use WP_PHPUnit_Framework\Unit\Unit_Test_Case;

// Import the functions from the WP_PHPUnit_Framework namespace
use function WP_PHPUnit_Framework\load_settings_file;
use function WP_PHPUnit_Framework\get_phpunit_database_settings;

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
	 * Test configuration values
	 *
	 * @var array
	 */
	private $test_config;

	/**
	 * Mock WordPress database settings
	 *
	 * @var array
	 */
	private $wp_db_settings;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();

		/* Define test configuration values
		 * Deliberately not using "real" values to ensure the function works with any valid values
		 */
		$this->test_config = [
			'FILESYSTEM_WP_ROOT' => '/path/to/wordpress',
			'FILESYSTEM_PLUGIN_ROOT' => '/path/to/plugin',
			'FRAMEWORK_DEST_NAME' => 'test-plugin',
			'DB_NAME' => 'asdf_db',
			'DB_USER' => 'asdf_user',
			'DB_PASSWORD' => 'asdf_password',
			'DB_HOST' => 'asdf_host',
			'DB_CHARSET' => 'utf8',
			'DB_COLLATE' => '',
			'TABLE_PREFIX' => 'asdf_prefix_',
			'TEST_DB_NAME' => 'asdf_test',
			'TEST_TABLE_PREFIX' => 'asdf_test_'
		];

		// Create a temporary .env.testing file for testing
		$this->temp_env_file = sys_get_temp_dir() . '/test-env-' . uniqid() . '.testing';

		// Build the file content from the test configuration
		$env_content = "# WordPress paths\n";
		$env_content .= "FILESYSTEM_WP_ROOT={$this->test_config['FILESYSTEM_WP_ROOT']}\n";
		$env_content .= "FILESYSTEM_PLUGIN_ROOT={$this->test_config['FILESYSTEM_PLUGIN_ROOT']}\n";
		$env_content .= "FRAMEWORK_DEST_NAME={$this->test_config['FRAMEWORK_DEST_NAME']}\n\n";

		$env_content .= "# Database settings\n";
		$env_content .= "DB_NAME={$this->test_config['DB_NAME']}\n";
		$env_content .= "DB_USER={$this->test_config['DB_USER']}\n";
		$env_content .= "DB_PASSWORD={$this->test_config['DB_PASSWORD']}\n";
		$env_content .= "DB_HOST={$this->test_config['DB_HOST']}\n";
		$env_content .= "DB_CHARSET={$this->test_config['DB_CHARSET']}\n";
		$env_content .= "DB_COLLATE={$this->test_config['DB_COLLATE']}\n";
		$env_content .= "TABLE_PREFIX={$this->test_config['TABLE_PREFIX']}\n\n";

		$env_content .= "# Test database settings\n";
		$env_content .= "TEST_DB_NAME={$this->test_config['TEST_DB_NAME']}\n";
		$env_content .= "TEST_TABLE_PREFIX={$this->test_config['TEST_TABLE_PREFIX']}\n";

		// Write the file
		file_put_contents($this->temp_env_file, $env_content);

		// Create mock WordPress database settings
		$this->wp_db_settings = [
			'db_host' => $this->test_config['DB_HOST'],
			'db_user' => $this->test_config['DB_USER'],
			'db_pass' => $this->test_config['DB_PASSWORD'],
			'db_name' => $this->test_config['DB_NAME'],
			'table_prefix' => $this->test_config['TABLE_PREFIX']
		];
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
	 * Test loading configuration settings from the .env.testing file
	 */
	public function test_load_settings_file(): void {

		// Use the function with our temporary file
		$settings = load_settings_file($this->temp_env_file);

		// Test that settings were loaded correctly
		$this->assertIsArray($settings);

		// Test key settings against the expected values
		foreach ($this->test_config as $key => $expected_value) {
			$this->assertEquals($expected_value, $settings[$key], "$key setting was not loaded correctly");
		}
	}

	/**
	 * Test that database settings are properly extracted
	 */
	public function test_get_phpunit_database_settings(): void {
		// Test case 1: With default table prefix (should use WordPress table prefix)
		$db_settings = get_phpunit_database_settings(
			$this->wp_db_settings,
			$this->test_config['TEST_DB_NAME']
		);

		// Test that database settings were extracted correctly
		$this->assertIsArray($db_settings);
		$this->assertEquals($this->wp_db_settings['db_host'], $db_settings['db_host'], 'Host should match settings');
		$this->assertEquals($this->wp_db_settings['db_user'], $db_settings['db_user'], 'User should match settings');
		$this->assertEquals($this->wp_db_settings['db_pass'], $db_settings['db_pass'], 'Password should match settings');
		$this->assertEquals($this->test_config['TEST_DB_NAME'], $db_settings['db_name'], 'Database name should be the provided test database name');
		$this->assertEquals($this->wp_db_settings['table_prefix'], $db_settings['table_prefix'], 'Table prefix should match settings when not explicitly provided');

		// Test case 2: With explicit table prefix
		$db_settings_custom_prefix = get_phpunit_database_settings(
			$this->wp_db_settings,
			$this->test_config['TEST_DB_NAME'],
			$this->test_config['TEST_TABLE_PREFIX']
		);

		// Test that custom table prefix is used when explicitly provided
		$this->assertEquals(
			$this->test_config['TEST_TABLE_PREFIX'],
			$db_settings_custom_prefix['table_prefix'],
			'Table prefix should match explicitly provided value'
		);
	}
}
