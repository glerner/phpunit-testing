<?php
/**
 * Stub for WP_UnitTestCase for framework tests
 * 
 * This stub is specifically for testing the framework itself,
 * not for use by developers using the framework.
 * It is not part of the framework's public API
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Tests\Framework\Stubs
 * @codeCoverageIgnore
 */

declare(strict_types=1);

// These classes must be in the global namespace to be recognized by WordPress

// Create base class if it doesn't exist
if (!class_exists('WP_UnitTestCase_Base')) {
	/**
	 * Base class for WordPress unit tests
	 * This is a placeholder for framework tests only
	 */
	class WP_UnitTestCase_Base extends \PHPUnit\Framework\TestCase {
		// Minimal implementation for framework tests
	}
}

// Create WP_UnitTestCase if it doesn't exist
if (!class_exists('WP_UnitTestCase')) {
	/**
	 * WordPress unit test case
	 * This is a placeholder for framework tests only
	 */
	class WP_UnitTestCase extends WP_UnitTestCase_Base {
		// Minimal implementation for framework tests
	}
}
