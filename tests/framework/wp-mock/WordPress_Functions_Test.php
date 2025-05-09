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
	 * Test data for WordPress function tests
	 * Deliberately using non-standard values to ensure functions work with any valid input
	 */
	protected array $test_data;

	/**
	 * Set up test data before each test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->test_data = [
			'option_name' => 'test_mock_option',
			'option_value' => 'mock_value_xyz',
			'settings_name' => 'test_example_settings',
			'api_key' => 'mock_api_key_12345',
			'hook_name' => 'test_mock_hook',
			'callback_name' => 'test_mock_callback'
		];
	}

	/**
	 * Test WordPress get_option function mocking
	 */
	public function test_get_option_mock(): void {
		// Set up expectations for get_option with test data
		WP_Mock::userFunction('get_option')
			->with($this->test_data['option_name'])
			->once()
			->andReturn($this->test_data['option_value']);

		// Call the function
		$result = get_option($this->test_data['option_name']);

		// Verify the result
		$this->assertEquals($this->test_data['option_value'], $result);

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}

	/**
	 * Test WordPress update_option function mocking
	 */
	public function test_update_option_mock(): void {
		// Create a new value for testing
		$new_value = $this->test_data['option_value'] . '_updated';

		// Set up expectations for update_option
		WP_Mock::userFunction('update_option')
			->with($this->test_data['option_name'], $new_value)
			->once()
			->andReturn(true);

		// Call the function
		$result = update_option($this->test_data['option_name'], $new_value);

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
		// Set up expectations for add_action using test data
		WP_Mock::expectActionAdded($this->test_data['hook_name'], $this->test_data['callback_name']);
		
		// Call the function that would add the action
		add_action($this->test_data['hook_name'], $this->test_data['callback_name']);

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}

	/**
	 * Test WordPress filter hooks
	 */
	public function test_filter_hooks(): void {
		// Create filter-specific test data
		$filter_name = 'test_content_filter';
		$filter_callback = 'test_filter_callback';

		// Set up expectations for add_filter
		WP_Mock::expectFilterAdded($filter_name, $filter_callback);
		
		// Call the function that would add the filter
		add_filter($filter_name, $filter_callback);

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}

	/**
	 * Test a function that uses WordPress functions
	 * This tests the full Create, Read, Update, Delete (CRUD) cycle with WordPress options
	 */
	public function test_plugin_function_with_wp_mock(): void {
		// Define a simple plugin function
		if (!function_exists('example_get_settings')) {
			function example_get_settings($settings_name) {
				$options = get_option($settings_name, []);
				$defaults = [
					'feature_enabled' => false,
					'api_key' => '',
				];
				return wp_parse_args($options, $defaults);
			}
		}

		// Create expected return values using test data
		$mock_options = ['api_key' => $this->test_data['api_key']];
		$expected_settings = [
			'feature_enabled' => false,
			'api_key' => $this->test_data['api_key'],
		];

		// Set up expectations
		WP_Mock::userFunction('get_option')
			->with($this->test_data['settings_name'], [])
			->once()
			->andReturn($mock_options);

		WP_Mock::userFunction('wp_parse_args')
			->with($mock_options, Mockery::type('array'))
			->once()
			->andReturn($expected_settings);

		// Call the function
		$settings = example_get_settings($this->test_data['settings_name']);

		// Verify the result
		$this->assertIsArray($settings);
		$this->assertFalse($settings['feature_enabled']);
		$this->assertEquals($this->test_data['api_key'], $settings['api_key']);

		// Verify all expectations were met
		$this->assertHooksWereCalled();
		$this->assertFunctionsCalled();
	}
}
