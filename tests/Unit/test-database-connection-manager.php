<?php
/**
 * Unit tests for Database_Connection_Manager
 *
 * @package GL_Reinvent\Tests\Unit
 * @covers \GL_Reinvent\Service\Database_Connection_Manager
 */

declare(strict_types=1);

namespace GL_Reinvent\Tests\Unit;

use GL_Reinvent\Service\Database_Connection_Manager;
use Mockery;
use WP_PHPUnit_Framework\Unit\Unit_Test_Case;
use mysqli;
use ReflectionClass;
use RuntimeException;
class Test_DatabaseConnectionManager extends Unit_Test_Case {
    /**
     * Test instance
     *
     * @var Database_Connection_Manager
     */
    private Database_Connection_Manager $manager;

    /**
     * Set up the test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->manager = Database_Connection_Manager::get_instance();
        $this->resetSingletonInstance();
    }

    /**
     * Tear down the test
     */
    protected function tearDown(): void {
        $this->resetSingletonInstance();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Reset the singleton instance between tests
     */
    private function resetSingletonInstance(): void {
        $reflection = new ReflectionClass(Database_Connection_Manager::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        $instance->setAccessible(false);
    }

    /**
     * Test that get_instance() returns the same instance.
     *
     * @covers \GL_Reinvent\Service\Database_Connection_Manager::get_instance
     */
    public function test_singleton_pattern(): void {
        $instance1 = Database_Connection_Manager::get_instance();
        $instance2 = Database_Connection_Manager::get_instance();

        $this->assertSame($instance1, $instance2, 'Multiple calls to getInstance() should return the same instance');
    }

    /**
     * Test that get_connection() returns a valid mysqli connection.
     *
     * @covers \GL_Reinvent\Service\Database_Connection_Manager::get_connection
     */
    public function test_get_new_connection(): void {
        $host = $_ENV['TEST_DB_HOST'] ?? 'localhost';
        $user = $_ENV['TEST_DB_USER'] ?? 'root';
        $pass = $_ENV['TEST_DB_PASS'] ?? '';

        $connection = $this->manager->get_connection($host, $user, $pass);

        $this->assertInstanceOf(
            mysqli::class,
            $connection,
            'Should return a mysqli connection'
        );
        $this->assertFalse(
            $connection->connect_error,
            'Connection should be established without errors'
        );
    }

    /**
     * Test that subsequent get_connection() calls return the same instance.
     *
     * @covers \GL_Reinvent\Service\Database_Connection_Manager::get_connection
     */
    public function test_connection_reuse(): void {
        $host = $_ENV['TEST_DB_HOST'] ?? 'localhost';
        $user = $_ENV['TEST_DB_USER'] ?? 'root';
        $pass = $_ENV['TEST_DB_PASS'] ?? '';

        $connection1 = $this->manager->get_connection($host, $user, $pass);
        $connection2 = $this->manager->get_connection($host, $user, $pass);

        $this->assertSame(
            $connection1,
            $connection2,
            'Subsequent calls with same credentials should return the same connection'
        );
    }

    /**
     * Test that a closed connection is detected and a new one is created.
     *
     * @covers \GL_Reinvent\Service\Database_Connection_Manager::get_connection
     * @covers \GL_Reinvent\Service\Database_Connection_Manager::validate_connection
     */
    public function test_connection_validation(): void {
        $host = $_ENV['TEST_DB_HOST'] ?? 'localhost';
        $user = $_ENV['TEST_DB_USER'] ?? 'root';
        $pass = $_ENV['TEST_DB_PASS'] ?? '';

        // Get initial connection
        $connection1 = $this->manager->get_connection($host, $user, $pass);

        // Force close the connection
        $connection1->close();

        // Should detect the closed connection and create a new one
        $connection2 = $this->manager->get_connection($host, $user, $pass);

        $this->assertNotSame(
            $connection1,
            $connection2,
            'Should create new connection when previous one is closed'
        );
        $this->assertFalse(
            $connection2->connect_error,
            'New connection should be valid'
        );
    }

    /**
     * Test that invalid credentials throw an exception.
     *
     * @covers \GL_Reinvent\Service\Database_Connection_Manager::get_connection
     */
    public function test_invalid_credentials(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to connect to database');

        $this->manager->get_connection('invalid_host', 'invalid_user', 'invalid_pass');
    }

    /**
     * Test that connections are properly cleaned up.
     *
     * @covers \GL_Reinvent\Service\Database_Connection_Manager::close_all_connections
     * @covers \GL_Reinvent\Service\Database_Connection_Manager::get_connection
     */
    public function test_connection_cleanup(): void {
        $reflection = new ReflectionClass($this->manager);
        $connections = $reflection->getProperty('connections');
        $connections->setAccessible(true);

        // Get initial count
        $initialCount = count($connections->getValue($this->manager));

        // Create a connection
        $host = $_ENV['TEST_DB_HOST'] ?? 'localhost';
        $user = $_ENV['TEST_DB_USER'] ?? 'root';
        $pass = $_ENV['TEST_DB_PASS'] ?? '';

        $this->manager->get_connection($host, $user, $pass);

        // Verify connection was added
        $this->assertCount(
            $initialCount + 1,
            $connections->getValue($this->manager),
            'Should add new connection to pool'
        );

        // Close all connections
        $this->manager->close_all_connections();

        // Verify connections were removed
        $this->assertCount(
            0,
            $connections->getValue($this->manager),
            'Should close all connections'
        );
    }
}
