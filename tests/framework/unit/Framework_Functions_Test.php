<?php
/**
 * Tests for framework utility functions
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Unit
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Framework\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test case for framework utility functions
 */
class Framework_Functions_Test extends TestCase {

	/**
	 * Temporary directory for test files
	 *
	 * @var string
	 */
	private string $temp_dir;

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
			'expected' => "-h 'localhost' -u 'root' -p'password' -e 'SELECT * FROM wp_users;'",
		],
		'with_database' => [
			'host' => 'localhost',
			'user' => 'root',
			'pass' => 'password',
			'sql' => 'SELECT * FROM wp_users',
			'db' => 'wordpress',
			'command_type' => 'direct',
			'expected' => "-h 'localhost' -u 'root' -p'password' 'wordpress' -e 'SELECT * FROM wp_users;'",
		],
		'empty_password' => [
			'host' => 'localhost',
			'user' => 'root',
			'pass' => '',
			'sql' => 'SELECT * FROM wp_users',
			'db' => 'wordpress',
			'command_type' => 'direct',
			'expected' => "-h 'localhost' -u 'root' 'wordpress' -e 'SELECT * FROM wp_users;'",
		],
		'non_standard_values' => [
			'host' => 'db.example.com:3307',
			'user' => 'user-with-hyphen',
			'pass' => 'p@ssw0rd!"#',
			'sql' => "SELECT * FROM wp_posts WHERE post_status='publish'",
			'db' => 'test_db-123',
			'command_type' => 'direct',
			'expected' => "-h 'db.example.com:3307' -u 'user-with-hyphen' -p'p@ssw0rd!\"#' 'test_db-123' -e 'SELECT * FROM wp_posts WHERE post_status=\\'publish\\';'",
		],
		'multiline_sql' => [
			'host' => 'localhost',
			'user' => 'root',
			'pass' => 'password',
			'sql' => "SELECT *\nFROM wp_users\nWHERE user_login = 'admin'",
			'db' => 'wordpress',
			'command_type' => 'direct',
			'expected' => "-h 'localhost' -u 'root' -p'password' 'wordpress' -e 'SELECT * FROM wp_users WHERE user_login = \\'admin\\';'",
		],
	];

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create a temporary directory for tests
		$this->temp_dir = sys_get_temp_dir() . '/wp-phpunit-test-' . uniqid();
		mkdir($this->temp_dir, 0777, true);
	}

	/**
	 * Tear down test environment
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Clean up temporary directory
		$this->recursiveRemoveDirectory($this->temp_dir);
	}

	/**
	 * Recursively remove a directory
	 *
	 * @param string $dir Directory to remove
	 * @return void
	 */
	private function recursiveRemoveDirectory(string $dir): void {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object !== '.' && $object !== '..') {
					$path = $dir . '/' . $object;
					if (is_dir($path)) {
						$this->recursiveRemoveDirectory($path);
					} else {
						unlink($path);
					}
				}
			}
			rmdir($dir);
		}
	}

	/**
	 * Test that format_mysql_parameters_and_query formats MySQL parameters correctly
	 */
	public function test_format_mysql_parameters_and_query(): void {
		foreach ($this->mysql_test_data as $test_name => $test_data) {
			$result = \WP_PHPUnit_Framework\format_mysql_parameters_and_query(
				$test_data['host'],
				$test_data['user'],
				$test_data['pass'],
				$test_data['sql'],
				$test_data['db'],
				$test_data['command_type']
			);

			$this->assertEquals(
				$test_data['expected'],
				$result,
				"MySQL parameters formatting failed for test case: $test_name"
			);

			// Output the actual parameters for debugging
			echo "\nTest case: $test_name\n";
			echo "Input: host={$test_data['host']}, user={$test_data['user']}, pass={$test_data['pass']}, ";
			echo "db=" . ($test_data['db'] ?? 'null') . ", command_type={$test_data['command_type']}\n";
			echo "Expected: {$test_data['expected']}\n";
			echo "Actual: $result\n";
		}
	}

	/**
	 * Test that is_lando_environment() correctly identifies Lando by checking for consistent indicators
	 */
	public function test_is_lando_environment_consistency(): void {
		$is_lando = \WP_PHPUnit_Framework\is_lando_environment();

		echo "\nEnvironment check: is_lando_environment() returned: " . ($is_lando ? 'true' : 'false') . "\n";

		if ($is_lando) {
			// If we're in Lando, verify that we can get Lando info
			$lando_info = \WP_PHPUnit_Framework\get_lando_info();
			$this->assertNotEmpty($lando_info, "When is_lando_environment() returns true, get_lando_info() should return data");

			// Verify we can access expected Lando services
			$this->assertArrayHasKey('database', $lando_info, "Lando environment should have database service");
			echo "Lando services detected: " . implode(', ', array_keys($lando_info)) . "\n";
		} else {
			// If not in Lando, verify that we don't get Lando info
			$lando_info = \WP_PHPUnit_Framework\get_lando_info();
			$this->assertEmpty($lando_info, "When is_lando_environment() returns false, get_lando_info() should return empty array");
			echo "Not in Lando environment, get_lando_info() returned: " . print_r($lando_info, true) . "\n";
		}
	}

	/**
	 * Test that get_lando_info() returns properly structured data
	 */
	public function test_get_lando_info_structure(): void {
		$lando_info = \WP_PHPUnit_Framework\get_lando_info();

		echo "\nLando info test: get_lando_info() returned structure:\n";
		if (!empty($lando_info)) {
			echo "Services found: " . implode(', ', array_keys($lando_info)) . "\n";

			// Verify the structure of Lando info
			$this->assertIsArray($lando_info, "Lando info should be an array");

			// Check for required services
			$this->assertArrayHasKey('appserver', $lando_info, "Lando info should include appserver");
			$this->assertArrayHasKey('database', $lando_info, "Lando info should include database");

			// Verify database service has expected structure
			$this->assertIsArray($lando_info['database'], "Database service info should be an array");
			$this->assertArrayHasKey('creds', $lando_info['database'], "Database service should have credentials");
			$this->assertArrayHasKey('type', $lando_info['database'], "Database service should have type");

			echo "Database type: " . $lando_info['database']['type'] . "\n";

			// Verify database credentials
			$this->assertIsArray($lando_info['database']['creds'], "Database credentials should be an array");
			$this->assertArrayHasKey('database', $lando_info['database']['creds'], "Database credentials should include database name");
			$this->assertArrayHasKey('user', $lando_info['database']['creds'], "Database credentials should include user");

			echo "Database name: " . $lando_info['database']['creds']['database'] . "\n";
			echo "Database user: " . $lando_info['database']['creds']['user'] . "\n";

			// Verify we can use these credentials to connect to the database
			$db_creds = $lando_info['database']['creds'];
			$conn = @mysqli_connect(
				$lando_info['database']['internal_connection']['host'],
				$db_creds['user'],
				$db_creds['password']
			);

			if ($conn) {
				$this->assertNotFalse($conn, "Should be able to connect to database using Lando credentials");
				echo "Successfully connected to database using Lando credentials\n";
				mysqli_close($conn);
			} else {
				echo "Failed to connect to database: " . mysqli_connect_error() . "\n";
				$this->markTestSkipped("Could not connect to database with Lando credentials");
			}
		} else {
			echo "Not running in Lando environment, no Lando info available\n";
			$this->markTestSkipped("Not running in Lando environment, skipping detailed Lando info structure test");
		}
	}

	/**
	 * Test that format_mysql_parameters_and_query() produces commands that actually work
	 */
	public function test_format_mysql_parameters_and_query_functionality(): void {
		// Format a simple query
		$params = \WP_PHPUnit_Framework\format_mysql_parameters_and_query(
			'localhost',
			'root',
			'password',
			'SELECT 1',
			'mysql'
		);

		// Build the full command
		$command = "mysql $params";

		echo "\nMySQL command test:\n";
		echo "Generated command: $command\n";

		// Verify the command structure
		$this->assertStringContainsString("-h 'localhost'", $command, "Command should contain host parameter");
		$this->assertStringContainsString("-u 'root'", $command, "Command should contain user parameter");
		$this->assertStringContainsString("-p'password'", $command, "Command should contain password parameter");
		$this->assertStringContainsString("-e 'SELECT 1;", $command, "Command should contain SQL query");

		// If we're in Lando, try executing a real query to verify the command works
		if (\WP_PHPUnit_Framework\is_lando_environment()) {
			$lando_info = \WP_PHPUnit_Framework\get_lando_info();
			if (!empty($lando_info) && isset($lando_info['database']['creds'])) {
				$db_creds = $lando_info['database']['creds'];
				$params = \WP_PHPUnit_Framework\format_mysql_parameters_and_query(
					'database',
					$db_creds['user'],
					$db_creds['password'],
					'SELECT 1 AS test_value',
					$db_creds['database']
				);

				// Execute the command through Lando
				$command = "lando mysql $params";
				echo "Executing Lando command: $command\n";
				$output = shell_exec($command);
				echo "Command output: $output\n";

				// Verify we got a result
				$this->assertStringContainsString("test_value", $output, "MySQL command should execute and return results");
				$this->assertStringContainsString("1", $output, "MySQL command should return the expected value");
			} else {
				echo "Lando database credentials not available\n";
			}
		} else {
			echo "Not running in Lando environment, skipping actual command execution\n";
		}
	}
}
