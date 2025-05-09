<?php
/**
 * Tests for command formatting functions
 *
 * This file demonstrates how to test the framework's utility functions
 * for formatting commands.
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Unit
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Unit;

use WP_PHPUnit_Framework\Unit\Unit_Test_Case;

// Import the functions from the WP_PHPUnit_Framework namespace
use function WP_PHPUnit_Framework\format_php_command;
use function WP_PHPUnit_Framework\format_mysql_command;

/**
 * Test case for command formatting functions
 */
class Format_Command_Test extends Unit_Test_Case {

	/**
	 * Test data for PHP command formatting tests
	 *
	 * @var array
	 */
	private $php_test_data;

	/**
	 * Test data for MySQL command formatting tests
	 *
	 * @var array
	 */
	private $mysql_test_data;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();

		// Define test data for PHP command formatting
		$this->php_test_data = [
			'standard' => [
				'script_path' => '/path/to/script.php',
				'args' => ['arg1', 'arg2'],
				'command_type' => 'standard',
				'expected' => 'php "/path/to/script.php" "arg1" "arg2"'
			],
			'lando_php' => [
				'script_path' => '/app/path/to/script.php',
				'args' => ['arg with spaces'],
				'command_type' => 'lando_php',
				'expected' => 'lando php "/app/path/to/script.php" "arg with spaces"'
			],
			'lando_exec' => [
				'script_path' => '/app/path/to/script.php',
				'args' => [],
				'command_type' => 'lando_exec',
				'expected' => 'lando exec appserver -- php "/app/path/to/script.php"'
			]
		];

		// Define test data for MySQL command formatting
		$this->mysql_test_data = [
			'basic' => [
				'host' => 'test_host',
				'user' => 'test_user',
				'pass' => 'test_password',
				'sql' => 'SELECT * FROM test_table',
				'db' => null,
				'command_type' => 'direct'
			],
			'with_db' => [
				'host' => 'test_host',
				'user' => 'test_user',
				'pass' => 'test_password',
				'sql' => 'SELECT * FROM test_table',
				'db' => 'test_database',
				'command_type' => 'direct'
			],
			'all_params' => [
				'host' => 'test_host',
				'user' => 'test_user',
				'pass' => 'test_password',
				'sql' => 'SELECT * FROM test_table',
				'db' => 'test_database',
				'command_type' => 'direct'
			],
			'lando' => [
				'host' => 'lando_mysql',
				'user' => 'test_database',
				'pass' => '',
				'sql' => 'SELECT * FROM test_posts',
				'db' => '',
				'command_type' => 'direct'
			]
		];
	}

	/**
	 * Test that PHP commands are properly formatted
	 */
	public function test_format_php_command(): void {
		// Test each case defined in the test data
		foreach ($this->php_test_data as $test_name => $test_case) {
			$command = format_php_command(
				$test_case['script_path'],
				$test_case['args'],
				$test_case['command_type']
			);
			$this->assertEquals(
				$test_case['expected'],
				$command,
				"PHP command formatting failed for test case: {$test_name}"
			);
		}
	}

	/**
	 * Test that MySQL commands are properly formatted
	 */
	public function test_format_mysql_command(): void {
		// Test each case defined in the test data
		foreach ($this->mysql_test_data as $test_name => $test_case) {
			$command = format_mysql_command(
				$test_case['host'],
				$test_case['user'],
				$test_case['pass'],
				$test_case['sql'],
				$test_case['db'],
				$test_case['command_type']
			);

			// Verify the command contains the expected elements
			$this->assertStringContainsString('mysql', $command, "Command should contain 'mysql' for test case: {$test_name}");

			// For basic MySQL format tests
			if ($test_case['host'] !== 'lando_mysql') {
				// MySQL host has a space after -h
				$this->assertStringContainsString(
					"-h {$test_case['host']}",
					$command,
					"Host parameter should be formatted as '-h host' for test case: {$test_name}"
				);

				// MySQL user has a space after -u
				$this->assertStringContainsString(
					"-u {$test_case['user']}",
					$command,
					"User parameter should be formatted as '-u user' for test case: {$test_name}"
				);

				// MySQL password has NO space after -p
				if (!empty($test_case['pass'])) {
					$this->assertStringContainsString(
						"-p{$test_case['pass']}",
						$command,
						"Password parameter should be formatted as '-ppassword' for test case: {$test_name}"
					);
				}

				// Database name should be included if specified
				if (!empty($test_case['db'])) {
					$this->assertStringContainsString(
						$test_case['db'],
						$command,
						"Database name should be included for test case: {$test_name}"
					);
				}
			}

			// For Lando MySQL format tests
			if ($test_case['host'] === 'lando_mysql') {
				$this->assertStringContainsString(
					"lando_mysql {$test_case['user']}",
					$command,
					"Lando MySQL command should be formatted correctly for test case: {$test_name}"
				);
			}

			// SQL query should always be included
			$this->assertStringContainsString(
				$test_case['sql'],
				$command,
				"SQL query should be included for test case: {$test_name}"
			);
		}
	}
}
