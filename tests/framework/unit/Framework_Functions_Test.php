<?php
/**
 * Tests for framework utility functions
 *
 * @package WP_PHPUnit_Framework
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Framework\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test case for framework utility functions
 */
class Framework_Functions_Test extends TestCase {

	/**
	 * Test data for PHP command formatting
	 *
	 * @var array
	 */
	private array $php_test_data = [
		'basic_command' => [
			'script_path' => '/path/to/script.php',
			'arguments' => [],
			'command_type' => 'direct',
			'expected' => 'php /path/to/script.php',
		],
		'with_arguments' => [
			'script_path' => '/path/to/script.php',
			'arguments' => ['foo' => 'bar', 'baz' => 'qux'],
			'command_type' => 'direct',
			'expected' => "php /path/to/script.php --foo='bar' --baz='qux'",
		],
		'with_positional_args' => [
			'script_path' => '/path/to/script.php',
			'arguments' => [0 => 'first', 1 => 'second'],
			'command_type' => 'direct',
			'expected' => "php /path/to/script.php 'first' 'second'",
		],
		'docker_command' => [
			'script_path' => '/path/to/script.php',
			'arguments' => [],
			'command_type' => 'docker',
			'expected' => 'php /path/to/script.php',
		],
	];

	/**
	 * Test data for WordPress database settings
	 * 
	 * @var array
	 */
	private array $wp_settings = [
		'host' => 'db.example.com',
		'name' => 'wordpress',
		'user' => 'wp_user',
		'password' => 'secret',
		'table_prefix' => 'wp_',
	];

	/**
	 * Test data for MySQL command formatting
	 *
	 * @var array
	 */
	private array $mysql_test_data = [
		'basic_command' => [
			'host' => 'localhost',
			'user' => 'root',
			'pass' => 'password',
			'sql' => 'SELECT * FROM wp_users',
			'db' => null,
			'command_type' => 'direct',
			'expected' => "mysql -h'localhost' -u'root' -p'password' -e 'SELECT * FROM wp_users'",
		],
		'with_database' => [
			'host' => 'localhost',
			'user' => 'root',
			'pass' => 'password',
			'sql' => 'SELECT * FROM wp_users',
			'db' => 'wordpress',
			'command_type' => 'direct',
			'expected' => "mysql -h'localhost' -u'root' -p'password' 'wordpress' -e 'SELECT * FROM wp_users'",
		],
		'empty_password' => [
			'host' => 'localhost',
			'user' => 'root',
			'pass' => '',
			'sql' => 'SELECT * FROM wp_users',
			'db' => 'wordpress',
			'command_type' => 'direct',
			'expected' => "mysql -h'localhost' -u'root' 'wordpress' -e 'SELECT * FROM wp_users'",
		],
	];

	/**
	 * Test that PHP commands are properly formatted
	 */
	public function test_format_php_command(): void {
		// Test each case defined in the test data
		foreach ($this->php_test_data as $test_name => $test_case) {
			$command = \WP_PHPUnit_Framework\format_php_command(
				$test_case['script_path'],
				$test_case['arguments'],
				$test_case['command_type']
			);
			
			$this->assertEquals(
				$test_case['expected'],
				$command,
				"Failed formatting PHP command for test case: {$test_name}"
			);
		}
	}

	/**
	 * Test that MySQL commands are properly formatted
	 */
	public function test_format_mysql_command(): void {
		// Test each case defined in the test data
		foreach ($this->mysql_test_data as $test_name => $test_case) {
			$command = \WP_PHPUnit_Framework\format_mysql_command(
				$test_case['host'],
				$test_case['user'],
				$test_case['pass'],
				$test_case['sql'],
				$test_case['db'],
				$test_case['command_type']
			);
			
			$this->assertEquals(
				$test_case['expected'],
				$command,
				"Failed formatting MySQL command for test case: {$test_name}"
			);
		}
	}

	/**
	 * Test loading settings from a file
	 */
	public function test_load_settings_file(): void {
		// Create a temporary file with test settings
		$temp_file = sys_get_temp_dir() . '/test-env-' . uniqid() . '.txt';
		$test_content = "
# This is a comment
KEY1=value1
KEY2=\"quoted value\"
KEY3='single quoted'
EMPTY=
";
		file_put_contents($temp_file, $test_content);

		// Test loading the settings
		$settings = \WP_PHPUnit_Framework\load_settings_file($temp_file);

		// Verify the results
		$this->assertArrayHasKey('KEY1', $settings);
		$this->assertEquals('value1', $settings['KEY1']);
		
		$this->assertArrayHasKey('KEY2', $settings);
		$this->assertEquals('quoted value', $settings['KEY2']);
		
		$this->assertArrayHasKey('KEY3', $settings);
		$this->assertEquals('single quoted', $settings['KEY3']);
		
		$this->assertArrayHasKey('EMPTY', $settings);
		$this->assertEquals('', $settings['EMPTY']);

		// Clean up
		unlink($temp_file);
	}

	/**
	 * Test getting PHPUnit database settings
	 */
	public function test_get_phpunit_database_settings(): void {
		// Test with complete WP settings
		$db_settings = \WP_PHPUnit_Framework\get_phpunit_database_settings($this->wp_settings);

		$this->assertEquals($this->wp_settings['host'], $db_settings['host']);
		$this->assertEquals('wordpress_test', $db_settings['name']); // Note: This should be the test DB name
		$this->assertEquals($this->wp_settings['user'], $db_settings['user']);
		$this->assertEquals($this->wp_settings['password'], $db_settings['password']);
		$this->assertEquals($this->wp_settings['table_prefix'], $db_settings['prefix']);

		// Test with custom prefix
		$custom_prefix = 'custom_';
		$db_settings = \WP_PHPUnit_Framework\get_phpunit_database_settings($this->wp_settings, $custom_prefix);
		$this->assertEquals($custom_prefix, $db_settings['prefix']);

		// Test with minimal settings
		$minimal_settings = [];
		$db_settings = \WP_PHPUnit_Framework\get_phpunit_database_settings($minimal_settings);
		
		$this->assertEquals('localhost', $db_settings['host']);
		$this->assertEquals('wordpress_test', $db_settings['name']);
		$this->assertEquals('root', $db_settings['user']);
		$this->assertEquals('', $db_settings['password']);
		$this->assertEquals('wp_', $db_settings['prefix']);
	}
}
