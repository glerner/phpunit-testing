<?php
/**
 * Tests for Integration_Test_Case
 *
 * @package WP_PHPUnit_Framework\Tests\Framework\Integration
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Framework\Integration;

use PHPUnit\Framework\TestCase;

// Load the specific stub needed for this test
require_once dirname(dirname(__DIR__)) . '/framework/stubs/Framework_Integration_Test_Case.php';
use WP_PHPUnit_Framework\Tests\Framework\Stubs\Framework_Integration_Test_Case;

// Framework stub is now in tests/framework/stubs/Framework_Integration_Test_Case.php

/**
 * Test case for the Framework_Integration_Test_Case class.
 *
 * This test uses the Framework_Integration_Test_Case stub to ensure framework tests
 * are completely independent from other test types.
 */
class Test_Integration_Test_Case extends TestCase {
	/**
	 * Test that the Framework_Integration_Test_Case can be instantiated.
	 */
	public function test_integration_test_case_instantiation(): void {
		// This test simply verifies that the framework stub can be instantiated
		// without errors. Framework tests should be independent from other test types.
		$test_case = new Framework_Integration_Test_Case();

		$this->assertInstanceOf(Framework_Integration_Test_Case::class, $test_case);
	}

	/**
	 * Test that the Framework_Integration_Test_Case has the expected methods.
	 */
	public function test_integration_test_case_methods(): void {
		$reflection = new \ReflectionClass(Framework_Integration_Test_Case::class);

		// Check for required methods
		$this->assertTrue($reflection->hasMethod('setUp'));
		$this->assertTrue($reflection->hasMethod('tearDown'));
		$this->assertTrue($reflection->hasMethod('reset_global_state'));

		// If we got here, the class has the expected methods
		$this->assertTrue(true);
	}
}
