<?php
/**
 * Example WP_Mock Test
 *
 * This is a template for a WP_Mock test that demonstrates how to use
 * the WP PHPUnit Framework for testing code that interacts
 * with WordPress functions and hooks.
 *
 * NOTE: This is a template file and will show IDE errors since the referenced
 * classes don't exist. You should copy this file to your plugin's test directory
 * and modify it to match your plugin's structure before using it.
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Examples
 */

declare(strict_types=1);

// This PHPCS comment disables the namespace prefix check for this example file.
// You should remove this comment once you've replaced the namespaces with your own.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

// IMPORTANT: When copying this template to your plugin:
// 1. Replace 'YourPlugin' with your plugin's namespace (e.g., 'My_Plugin')
// 2. Replace 'WordPress_Integration' with your actual class name to test
// 3. The recommended structure is: YourPluginName\Tests\WP_Mock\Classes
namespace YourPlugin\Tests\WP_Mock\Classes;

// Import the base test case from the WP_PHPUnit_Framework
use WP_PHPUnit_Framework\WP_Mock\WP_Mock_Test_Case;
use YourPlugin\Classes\WordPress_Integration;
use WP_Mock;
use Brain\Monkey\Functions;

/**
 * Example WP_Mock Test class
 *
 * @covers \YourPlugin\Classes\WordPress_Integration
 */
class Example_WP_Mock_Test extends WP_Mock_Test_Case {
	/**
	 * Test instance
	 *
	 * @var WordPress_Integration
	 */
	private $instance;

	/**
	 * Set up the test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create an instance of the class being tested
		$this->instance = new WordPress_Integration();
	}

	/**
	 * Test that hooks are registered correctly
	 *
	 * @return void
	 */
	public function test_register_hooks(): void {
		// Expect WordPress add_action to be called with specific parameters
		WP_Mock::expectActionAdded('init', array( $this->instance, 'initialize' ));
		WP_Mock::expectActionAdded('wp_enqueue_scripts', array( $this->instance, 'enqueue_assets' ));

		// Expect WordPress add_filter to be called with specific parameters
		WP_Mock::expectFilterAdded('the_content', array( $this->instance, 'filter_content' ), 10, 1);

		// Call the method that should register these hooks
		$this->instance->register_hooks();

		// WP_Mock will automatically verify that the expected actions and filters were added
	}

	/**
	 * Test WordPress function mocking
	 *
	 * @return void
	 */
	public function test_get_option_integration(): void {
		// Mock WordPress get_option function
		Functions\expect('get_option')
			->once()
			->with('your_plugin_setting', false)
			->andReturn('test-value');

		// Call the method that uses get_option
		$result = $this->instance->get_setting();

		// Verify the result
		$this->assertEquals('test-value', $result);
	}

	/**
	 * Test content filtering
	 *
	 * @return void
	 */
	public function test_filter_content(): void {
		// Test data
		$content = 'Original content';

		// Mock WordPress function is_single
		Functions\expect('is_single')
			->once()
			->andReturn(true);

		// Call the filter method
		$filtered_content = $this->instance->filter_content($content);

		// Verify the content was modified
		$this->assertStringContainsString('Original content', $filtered_content);
		$this->assertStringContainsString('Additional content', $filtered_content);
	}

	/**
	 * Test asset enqueueing
	 *
	 * @return void
	 */
	public function test_enqueue_assets(): void {
		// Mock WordPress functions for asset enqueueing
		Functions\expect('wp_enqueue_style')
			->once()
			->with(
				'your-plugin-style',
				\Mockery::type('string'),
				\Mockery::any(),
				\Mockery::any()
			);

		Functions\expect('wp_enqueue_script')
			->once()
			->with(
				'your-plugin-script',
				\Mockery::type('string'),
				\Mockery::any(),
				\Mockery::any(),
				true
			);

		Functions\expect('wp_localize_script')
			->once()
			->with(
				'your-plugin-script',
				'yourPluginData',
				\Mockery::type('array')
			);

		// Call the method that should enqueue assets
		$this->instance->enqueue_assets();
	}

	/**
	 * Test WordPress capability checking
	 *
	 * @return void
	 */
	public function test_user_can_access(): void {
		// Mock WordPress current_user_can function
		Functions\expect('current_user_can')
			->once()
			->with('edit_posts')
			->andReturn(true);

		// Call the method that checks capabilities
		$result = $this->instance->user_can_access();

		// Verify the result
		$this->assertTrue($result);
	}
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
