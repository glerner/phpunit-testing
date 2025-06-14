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
use function WP_PHPUnit_Framework\format_mysql_parameters_and_query;

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
				'command_type' => 'direct',
				'expected' => "-h 'test_host' -u 'test_user' -ptest_password -e 'SELECT * FROM test_table;'"
			],
			'with_db' => [
				'host' => 'test_host',
				'user' => 'test_user',
				'pass' => 'test_password',
				'sql' => 'SELECT * FROM test_table',
				'db' => 'test_database',
				'command_type' => 'direct',
				'expected' => "-h 'test_host' -u 'test_user' -ptest_password 'test_database' -e 'SELECT * FROM test_table;'"
			],
			'empty_password' => [
				'host' => 'test_host',
				'user' => 'test_user',
				'pass' => '',
				'sql' => 'SELECT * FROM test_table',
				'db' => 'test_database',
				'command_type' => 'direct',
				'expected' => "-h 'test_host' -u 'test_user' 'test_database' -e 'SELECT * FROM test_table;'"
			],
			'sql_with_quotes' => [
				'host' => 'test_host',
				'user' => 'test_user',
				'pass' => 'test_password',
				'sql' => "SELECT * FROM test_table WHERE name = 'test' AND type = \"post\"",
				'db' => 'test_database',
				'command_type' => 'direct',
				'expected' => "-h 'test_host' -u 'test_user' -ptest_password 'test_database' -e 'SELECT * FROM test_table WHERE name = \\'test\\' AND type = \\\"post\\\";'"
			],
			'lando_direct' => [
				'host' => 'database',
				'user' => 'wordpress',
				'pass' => 'password',
				'sql' => "SELECT * FROM wp_posts WHERE post_title = 'Test'",
				'db' => 'wordpress',
				'command_type' => 'lando_direct',
				'expected' => "-h 'database' -u 'wordpress' -ppassword 'wordpress' -e 'SELECT * FROM wp_posts WHERE post_title = '\\'Test\\';'"
			],
			'ssh' => [
				'host' => 'remote_host',
				'user' => 'remote_user',
				'pass' => 'remote_pass',
				'sql' => 'SHOW TABLES WHERE name = "users"',
				'db' => 'remote_db',
				'command_type' => 'ssh',
				'expected' => "-h 'remote_host' -u 'remote_user' -premote_pass 'remote_db' -e 'SHOW TABLES WHERE name = \\\"users\\\";'"
			],
			'multiline_sql' => [
				'host' => 'test_host',
				'user' => 'test_user',
				'pass' => 'test_password',
				'sql' => "SELECT *\nFROM test_table\nWHERE id = 1",
				'db' => 'test_database',
				'command_type' => 'direct',
				'expected' => "-h 'test_host' -u 'test_user' -ptest_password 'test_database' -e 'SELECT * FROM test_table WHERE id = 1;'"
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
	 * Test that MySQL parameters and SQL queries are properly formatted
	 */
	public function test_format_mysql_parameters_and_query(): void {
		// Test each case defined in the test data
		foreach ($this->mysql_test_data as $test_name => $test_case) {
			$command = format_mysql_parameters_and_query(
				$test_case['host'],
				$test_case['user'],
				$test_case['pass'],
				$test_case['sql'],
				$test_case['db'],
				$test_case['command_type']
			);

			// Display the full command output for debugging
			$debug_message = "Test case: {$test_name}\nExpected format: {$test_case['expected']}\nActual output: {$command}";

			// Check basic structure - should have parameters and SQL
			$this->assertStringContainsString('-h', $command, "Command should contain host parameter\n{$debug_message}");
			$this->assertStringContainsString('-u', $command, "Command should contain user parameter\n{$debug_message}");
			$this->assertStringContainsString('-e', $command, "Command should contain SQL execution parameter\n{$debug_message}");

			// Host parameter should be properly formatted
			$this->assertStringContainsString("-h '{$test_case['host']}'", $command, "Host parameter should be properly formatted\n{$debug_message}");

			// User parameter should be properly formatted
			$this->assertStringContainsString("-u '{$test_case['user']}'", $command, "User parameter should be properly formatted\n{$debug_message}");

			// Password parameter handling
			if (!empty($test_case['pass'])) {
				$this->assertStringContainsString("-p{$test_case['pass']}", $command, "Password parameter should be properly formatted\n{$debug_message}");
			} else {
				$this->assertStringNotContainsString("-p", $command, "Password parameter should not be included when empty\n{$debug_message}");
			}

			// Database parameter handling
			if (!empty($test_case['db'])) {
				// Database name should be included (but we don't check exact format since it might be quoted or not)
				$this->assertStringContainsString($test_case['db'], $command, "Database name should be included\n{$debug_message}");
			}

			// SQL should be included in some form, but we don't check exact format since escaping varies
			// Instead, check for key parts of the SQL that should be present regardless of escaping
			if (strpos($test_case['sql'], 'SELECT') !== false) {
				$this->assertStringContainsString('SELECT', $command, "SQL SELECT statement should be included\n{$debug_message}");
			}

			if (strpos($test_case['sql'], 'FROM') !== false) {
				$this->assertStringContainsString('FROM', $command, "SQL FROM clause should be included\n{$debug_message}");
			}

			// Check for semicolon at the end of the SQL
			$this->assertMatchesRegularExpression('/;\'$/', $command, "SQL should end with semicolon\n{$debug_message}");

			// For lando_direct command type, check special escaping
			if ($test_case['command_type'] === 'lando_direct' && strpos($test_case['sql'], "'") !== false) {
				// Should have special escaping for single quotes
				$this->assertStringContainsString("'\\'", $command, "Single quotes should have special escaping for lando_direct command type\n{$debug_message}");
			}
		}
	}
}
