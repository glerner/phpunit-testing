<?php
/**
 * Stub for WordPress WP_UnitTestCase class
 *
 * This class is a stub for the WordPress WP_UnitTestCase class, which is used
 * for static analysis and development. When the framework is used in a WordPress
 * environment, the real WP_UnitTestCase class from WordPress will be used instead.
 *
 * @package GL\Testing\Framework\Stubs
 * @codeCoverageIgnore
 */

declare(strict_types=1);

namespace GL\Testing\Framework\Stubs;

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_UnitTestCase')) {
    /**
     * WordPress Unit Test Case stub class
     */
    class WP_UnitTestCase extends TestCase {
        /**
         * Factory for creating WordPress data like posts, users, etc.
         *
         * @var object
         */
        public $factory;

        /**
         * Set up the test environment
         *
         * @return void
         */
        protected function setUp(): void {
            parent::setUp();
            $this->factory = new \stdClass();
        }

        /**
         * Clean up after each test
         *
         * @return void
         */
        protected function tearDown(): void {
            parent::tearDown();
        }

        /**
         * Stub for assertQueryTrue method
         *
         * @return void
         */
        public function assertQueryTrue(): void {
            // Stub implementation
        }

        /**
         * Stub for assertWPError method
         *
         * @param mixed $actual The value to check
         * @return void
         */
        public function assertWPError($actual): void {
            // Stub implementation
        }

        /**
         * Stub for assertNotWPError method
         *
         * @param mixed $actual The value to check
         * @return void
         */
        public function assertNotWPError($actual): void {
            // Stub implementation
        }

        /**
         * Stub for assertIXRError method
         *
         * @param mixed $actual The value to check
         * @return void
         */
        public function assertIXRError($actual): void {
            // Stub implementation
        }

        /**
         * Stub for assertNotIXRError method
         *
         * @param mixed $actual The value to check
         * @return void
         */
        public function assertNotIXRError($actual): void {
            // Stub implementation
        }
    }
}
