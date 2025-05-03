<?php
/**
 * Integration Test Case base class
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Integration
 * @codeCoverageIgnore
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Integration;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

// Use the stub WP_UnitTestCase during development, but use the real one in WordPress
if (!class_exists('WP_UnitTestCase')) {
	// For development and static analysis
	require_once dirname(__DIR__) . '/Stubs/WP_UnitTestCase.php';

	// Add a phpcs suppression for the undefined WordPress function
	// phpcs:disable WordPress.WP.AlternativeFunctions
}

/**
 * Base Test Case class for WordPress integration tests
 * Extends WP_UnitTestCase to provide full WordPress test environment
 */
class Integration_Test_Case extends \WP_UnitTestCase {
	use MockeryPHPUnitIntegration;

	/**
	 * Set up the test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
	    parent::setUp();

	    // Reset global WordPress state
	    global $post, $wp_query, $wp_the_query;
	    $post         = null;
	    $wp_query     = null;
	    $wp_the_query = null;

	    // Clear any cached data
	    wp_cache_flush();

	    // Reset any modifications to the test database
	    $this->clean_test_db();
	}

	/**
	 * Clean up after each test
	 *
	 * @return void
	 */
	protected function tearDown(): void {
	    parent::tearDown();

	    // Clean up any Mockery expectations
	    \Mockery::close();
	}

	/**
	 * Clean the test database
	 *
	 * @return void
	 */
	protected function clean_test_db(): void {
	    global $wpdb;

	    // Delete all posts
	    $wpdb->query("DELETE FROM {$wpdb->posts}");
	    $wpdb->query("DELETE FROM {$wpdb->postmeta}");

	    // Delete all terms
	    $wpdb->query("DELETE FROM {$wpdb->terms}");
	    $wpdb->query("DELETE FROM {$wpdb->term_taxonomy}");
	    $wpdb->query("DELETE FROM {$wpdb->term_relationships}");

	    // Delete all comments
	    $wpdb->query("DELETE FROM {$wpdb->comments}");
	    $wpdb->query("DELETE FROM {$wpdb->commentmeta}");

	    // Reset sequences
	    $wpdb->query("ALTER TABLE {$wpdb->posts} AUTO_INCREMENT = 1");
	    $wpdb->query("ALTER TABLE {$wpdb->terms} AUTO_INCREMENT = 1");
	    $wpdb->query("ALTER TABLE {$wpdb->comments} AUTO_INCREMENT = 1");
	}
}
