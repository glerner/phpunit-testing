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
	 * Test that PHP commands are properly formatted
	 */
	public function test_format_php_command(): void {
		// Include the file with the function
		require_once dirname(dirname(__DIR__)) . '/bin/setup-plugin-tests.php';

		// Test basic PHP command formatting
		$command = format_php_command('php', ['/path/to/script.php', 'arg1', 'arg2']);
		$this->assertEquals('php "/path/to/script.php" "arg1" "arg2"', $command);

		// Test Lando PHP command formatting
		$command = format_php_command('lando php', ['/app/path/to/script.php', 'arg with spaces']);
		$this->assertEquals('lando php "/app/path/to/script.php" "arg with spaces"', $command);

		// Test Lando exec command formatting
		$command = format_php_command('lando exec appserver', ['php', '/app/path/to/script.php']);
		$this->assertEquals('lando exec appserver -- php "/app/path/to/script.php"', $command);
	}

	/**
	 * Test that MySQL commands are properly formatted
	 */
	public function test_format_mysql_command(): void {
		// Include the file with the function
		require_once dirname(dirname(__DIR__)) . '/bin/setup-plugin-tests.php';

		// Test basic MySQL command formatting
		$command = format_mysql_command('mysql', 'database', 'user', 'password', 'host', 'SELECT * FROM wp_users');
		$this->assertStringContainsString('mysql', $command);

		// These assertions will pass (correct MySQL format)
		$this->assertStringContainsString('-h host', $command); // MySQL host has a space after -h
		$this->assertStringContainsString('-u user', $command); // MySQL user has a space after -u
		$this->assertStringContainsString('-ppassword', $command); // MySQL password has NO space after -p

		// These assertions will fail (incorrect MySQL format)
		// Uncomment to see the test fail
		// $this->assertStringContainsString('-hhost', $command); // Incorrect - MySQL host needs a space after -h
		// $this->assertStringContainsString('-uuser', $command); // Incorrect - MySQL user needs a space after -u
		// $this->assertStringContainsString('-p password', $command); // Incorrect - MySQL password should not have a space after -p

		$this->assertStringContainsString('database', $command);
		$this->assertStringContainsString('SELECT * FROM wp_users', $command);

		// Test Lando MySQL command formatting
		$command = \WP_PHPUnit_Framework\format_mysql_command('lando mysql', 'wordpress', '', '', '', 'SELECT * FROM wp_posts');
		$this->assertStringContainsString('lando mysql wordpress', $command);
		$this->assertStringContainsString('SELECT * FROM wp_posts', $command);
	}
}
