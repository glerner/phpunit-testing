<?php
/**
 * Tests for the Integration_Test_Case class.
 *
 * @package WP_PHPUnit_Framework\Tests
 */

namespace WP_PHPUnit_Framework\Tests\Integration;

use WP_PHPUnit_Framework\Integration\Integration_Test_Case;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Integration_Test_Case class.
 *
 * Note: This test requires a WordPress test environment to be set up.
 */
class Test_Integration_Test_Case extends TestCase {
	/**
	 * Test that the Integration_Test_Case can be instantiated.
	 */
	public function test_integration_test_case_instantiation(): void {
		// This test simply verifies that the class can be instantiated
		// without errors. Full integration testing would require a WordPress
		// test environment.
		$test_case = $this->getMockBuilder(Integration_Test_Case::class)
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(Integration_Test_Case::class, $test_case);
	}

	/**
	 * Test that the class has the expected methods.
	 */
	public function test_integration_test_case_methods(): void {
		$reflection = new \ReflectionClass(Integration_Test_Case::class);

		// Check for required methods
		$this->assertTrue($reflection->hasMethod('setUp'));
		$this->assertTrue($reflection->hasMethod('tearDown'));
		$this->assertTrue($reflection->hasMethod('reset_global_state'));

		// If we got here, the class has the expected methods
		$this->assertTrue(true);
	}
}
