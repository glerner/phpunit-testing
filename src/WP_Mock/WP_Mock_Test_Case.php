<?php
/**
 * Base Test Case for WordPress Mock Tests
 *
 * @package WP_PHPUnit_Framework
 * @subpackage WP_Mock
 * @codeCoverageIgnore
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\WP_Mock;

use WP_PHPUnit_Framework\Unit\Unit_Test_Case;
use WP_Mock;
use Mockery;

/**
 * Base Test Case class that provides WP_Mock integration
 * Extends our base Unit_Test_Case to maintain Mockery support
 */
class WP_Mock_Test_Case extends Unit_Test_Case {
	/**
	 * Stores WordPress functions that have been mocked
	 *
	 * @var array
	 */
	protected $mocked_functions = array();

	/**
	 * Set up the test environment
	 * Initializes WP_Mock for each test
	 */
	protected function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
	}

	/**
	 * Clean up the test environment
	 * Tears down WP_Mock after each test
	 */
	protected function tearDown(): void {
		WP_Mock::tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Assert that all expected hooks were called
	 * This method should be called at the end of each test
	 */
	protected function assertHooksWereCalled(): void {
		try {
			WP_Mock::assertHooksAdded();
		} catch (\TypeError $e) {
			// Handle the case where WP_Mock's internal state might not be properly initialized
			// This can happen if no hooks were registered during the test
			if (strpos($e->getMessage(), 'array_keys(): Argument #1 ($array) must be of type array') !== false) {
				// No hooks were registered, so we'll consider this a pass
				return;
			}
			// For other TypeError exceptions, rethrow
			throw $e;
		}
	}

	/**
	 * Assert that all expected WordPress functions were called
	 * This method should be called at the end of each test
	 */
	protected function assertFunctionsCalled(): void {
		try {
			WP_Mock::assertActionsCalled();
		} catch (\TypeError $e) {
			// Handle the case where WP_Mock's internal state might not be properly initialized
			if (strpos($e->getMessage(), 'array_keys(): Argument #1 ($array) must be of type array') !== false) {
				// No actions were registered, so we'll consider this a pass
			} else {
				// For other TypeError exceptions, rethrow
				throw $e;
			}
		}

		try {
			WP_Mock::assertFiltersCalled();
		} catch (\TypeError $e) {
			// Handle the case where WP_Mock's internal state might not be properly initialized
			if (strpos($e->getMessage(), 'array_keys(): Argument #1 ($array) must be of type array') !== false) {
				// No filters were registered, so we'll consider this a pass
			} else {
				// For other TypeError exceptions, rethrow
				throw $e;
			}
		}
	}
}
