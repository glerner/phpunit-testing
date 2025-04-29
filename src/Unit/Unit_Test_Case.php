<?php
/**
 * Base class for unit tests
 *
 * This is an abstract base class for unit tests and is not meant to contain
 * any tests itself. It provides common functionality for all unit test classes.
 *
 * @package GL_WordPress_Testing_Framework
 * @subpackage Unit
 * @codeCoverageIgnore
 */

declare(strict_types=1);

namespace GL\Testing\Framework\Unit;

use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Base Test Case class that provides Mockery integration
 */
class Unit_Test_Case extends PHPUnit_TestCase {
	use MockeryPHPUnitIntegration;

	/**
	 * Set up the test environment
	 */
	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Clean up the test environment
	 */
	protected function tearDown(): void {
		\Mockery::close();
		parent::tearDown();
	}
}
