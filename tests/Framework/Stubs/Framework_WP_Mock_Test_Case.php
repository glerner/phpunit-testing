<?php
/**
 * Framework stub for WP_Mock_Test_Case
 *
 * This stub is specifically for testing the framework itself,
 * not for use by developers using the framework.
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Tests\Framework\Stubs
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Framework\Stubs;

use PHPUnit\Framework\TestCase;

/**
 * Framework stub for WP_Mock_Test_Case
 * 
 * This class is used only in framework tests to avoid dependencies on actual WP-Mock tests.
 * It is not part of the framework's public API.
 *
 * @codeCoverageIgnore
 */
class Framework_WP_Mock_Test_Case extends TestCase {
	/**
	 * Set up the test environment
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * Tear down the test environment
	 */
	public function tearDown(): void {
		parent::tearDown();
	}
	
	/**
	 * Mock method for assertConditionsMet
	 */
	public function assertConditionsMet(): void {
		// This is a stub method
	}
}
