<?php
/**
 * Example WP_Mock test for WordPress functions
 *
 * This file demonstrates how to use WP_Mock to test WordPress plugin code
 * that interacts with WordPress core functions without needing a WordPress environment.
 *
 * @package WP_PHPUnit_Framework
 * @subpackage WP_Mock
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\WP_Mock;

use WP_PHPUnit_Framework\WP_Mock\WP_Mock_Test_Case;
use WP_Mock;
use Mockery;

// These are needed to avoid namespace issues with global WordPress functions
use function get_option;
use function update_option;
use function add_action;
use function add_filter;
use function wp_parse_args;

/**
 * Example test case for WordPress functions using WP_Mock
 */
class WordPress_Functions_Test extends WP_Mock_Test_Case {

	/**
	 * Test WordPress get_option function mocking
	 */
	public function test_get_option_mock(): void {
		// Set up expectations for get_option
		WP_Mock::userFunction('get_option')
			->with('my_option')
			->once()
			->andReturn('option_value');

		// Call the function
		$result = get_option('my_option');

		// Verify the result
		$this->assertEquals('option_value', $result);

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}

	/**
	 * Test WordPress update_option function mocking
	 */
	public function test_update_option_mock(): void {
		// Set up expectations for update_option
		WP_Mock::userFunction('update_option')
			->with('my_option', 'new_value')
			->once()
			->andReturn(true);

		// Call the function
		$result = update_option('my_option', 'new_value');

		// Verify the result
		$this->assertTrue($result);

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}

	/**
	 * Test WordPress action hooks
	 */
	public function test_action_hooks(): void {
		// Set up expectations for add_action
		WP_Mock::expectActionAdded('init', 'my_init_function');
		
		// Call the function that would add the action
		add_action('init', 'my_init_function');

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}

	/**
	 * Test WordPress filter hooks
	 */
	public function test_filter_hooks(): void {
		// Set up expectations for add_filter
		WP_Mock::expectFilterAdded('the_content', 'my_content_filter');
		
		// Call the function that would add the filter
		add_filter('the_content', 'my_content_filter');

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}

	/**
	 * Test a function that uses WordPress functions
	 */
	public function test_plugin_function_with_wp_mock(): void {
		// Define a simple plugin function
		if (!function_exists('example_get_settings')) {
			function example_get_settings() {
				$options = get_option('example_settings', []);
				$defaults = [
					'feature_enabled' => false,
					'api_key' => '',
				];
				return wp_parse_args($options, $defaults);
			}
		}

		// Set up expectations
		WP_Mock::userFunction('get_option')
			->with('example_settings', [])
			->once()
			->andReturn(['api_key' => 'test123']);

		WP_Mock::userFunction('wp_parse_args')
			->with(['api_key' => 'test123'], Mockery::type('array'))
			->once()
			->andReturn([
				'feature_enabled' => false,
				'api_key' => 'test123',
			]);

		// Call the function
		$settings = example_get_settings();

		// Verify the result
		$this->assertIsArray($settings);
		$this->assertFalse($settings['feature_enabled']);
		$this->assertEquals('test123', $settings['api_key']);

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}
}
