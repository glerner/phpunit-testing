<?php
/**
 * Stub for WP_UnitTestCase
 *
 * This is a stub class used for development and static analysis e.g. PHPCS
 * It will be replaced by the real WP_UnitTestCase when running in WordPress
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Stubs
 * @codeCoverageIgnore
 */

declare(strict_types=1);

// Create base class if it doesn't exist
if (!class_exists('WP_UnitTestCase_Base')) {
	/**
	 * Base class for WordPress unit tests
	 */
	class WP_UnitTestCase_Base extends PHPUnit\Framework\TestCase {
		// Minimal implementation for static analysis
	}
}

// Only declare the stub if the real class doesn't exist
if (!class_exists('WP_UnitTestCase')) {
	/**
	 * Stub implementation of WP_UnitTestCase
	 */
	class WP_UnitTestCase extends WP_UnitTestCase_Base {
		// Minimal implementation for static analysis
	}
}
