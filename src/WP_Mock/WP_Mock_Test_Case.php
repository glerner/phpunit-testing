<?php
/**
 * Base Test Case for WordPress Mock Tests
 *
 * @package GL_WordPress_Testing_Framework
 * @subpackage WP_Mock
 * @codeCoverageIgnore
 */

declare(strict_types=1);

namespace GL\Testing\Framework\WP_Mock;

use GL\Testing\Framework\Unit\Unit_Test_Case;
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
    protected $mocked_functions = [];

    /**
     * Set up the test environment
     * Initializes WP_Mock for each test
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();
    }

    /**
     * Clean up the test environment
     * Tears down WP_Mock after each test
     *
     * @return void
     */
    protected function tearDown(): void {
        WP_Mock::tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Assert that all expected hooks were called
     * This method should be called at the end of each test
     *
     * @return void
     */
    protected function assertHooksWereCalled(): void {
        WP_Mock::assertHooksAdded();
    }

    /**
     * Assert that all expected WordPress functions were called
     * This method should be called at the end of each test
     *
     * @return void
     */
    protected function assertFunctionsCalled(): void {
        WP_Mock::assertActionsCalled();
        WP_Mock::assertFiltersCalled();
    }
}
