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
		WP_Mock::assertHooksAdded();
	}

	/**
	 * Assert that all expected WordPress functions were called
	 * This method should be called at the end of each test
	 */
	protected function assertFunctionsCalled(): void {
		WP_Mock::assertActionsCalled();
		WP_Mock::assertFiltersCalled();
	}
}
