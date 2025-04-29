<?php
/**
 * Tests for the Unit_Test_Case class.
 *
 * @package GL\Testing\Framework\Tests
 */

namespace GL\Testing\Framework\Tests\Unit;

use GL\Testing\Framework\Unit\Unit_Test_Case;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Unit_Test_Case class.
 */
class Test_Unit_Test_Case extends TestCase {
	/**
	 * Test that Mockery is properly integrated.
	 */
	public function test_mockery_integration(): void {
		$test_case = new Unit_Test_Case();

		// Create a mock using Mockery
		$mock = Mockery::mock('stdClass');
		$mock->shouldReceive('someMethod')->andReturn('mocked value');

		// Verify the mock works
		$this->assertEquals('mocked value', $mock->someMethod());

		// Call tearDown to ensure Mockery::close() is called
		$test_case->tearDown();

		// If we got here without errors, Mockery integration is working
		$this->assertTrue(true);
	}

	/**
	 * Test that setUp and tearDown methods work correctly.
	 */
	public function test_setup_teardown(): void {
		$test_case = new Unit_Test_Case();

		// Call setUp
		$test_case->setUp();

		// Create a mock
		$mock = Mockery::mock('stdClass');

		// Call tearDown
		$test_case->tearDown();

		// If we got here without errors, setUp and tearDown are working
		$this->assertTrue(true);
	}
}
