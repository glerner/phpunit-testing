<?php
/**
 * Example Integration Test
 *
 * This is a template for an integration test that demonstrates how to use
 * the WP PHPUnit Framework for testing code that interacts
 * with a real WordPress environment.
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
// 2. Replace 'Plugin_Class' with your actual class name to test
// 3. The recommended structure is: YourPluginName\Tests\Integration\Classes

namespace YourPlugin\Tests\Integration\Classes;

// Import the base test case from the WP_PHPUnit_Framework
use WP_PHPUnit_Framework\Integration\Integration_Test_Case;
use YourPlugin\Classes\Plugin_Class;

/**
 * Example Integration Test class
 *
 * @covers \YourPlugin\Classes\Plugin_Class
 */
class Example_Integration_Test extends Integration_Test_Case {
	/**
	 * Test instance
	 *
	 * @var Plugin_Class
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
		$this->instance = new Plugin_Class();
	}

	/**
	 * Test WordPress post creation
	 *
	 * @return void
	 */
	public function test_create_post(): void {
		// Test data
		$post_data = array(
			'post_title'   => 'Test Post',
			'post_content' => 'This is test content',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		);

		// Call the method that creates a post
		$post_id = $this->instance->create_post($post_data);

		// Verify the post was created
		$this->assertIsInt($post_id);
		$this->assertGreaterThan(0, $post_id);

		// Verify the post data
		$post = get_post($post_id);
		$this->assertEquals('Test Post', $post->post_title);
		$this->assertEquals('This is test content', $post->post_content);
		$this->assertEquals('publish', $post->post_status);
	}

	/**
	 * Test WordPress option saving and retrieving
	 *
	 * @return void
	 */
	public function test_save_and_get_option(): void {
		// Test data
		$option_name  = 'your_plugin_test_option';
		$option_value = array(
		'setting1' => 'value1',
		'setting2' => 'value2',
		);

		// Call the method that saves an option
		$result = $this->instance->save_option($option_name, $option_value);

		// Verify the option was saved
		$this->assertTrue($result);

		// Call the method that retrieves an option
		$retrieved_value = $this->instance->get_option($option_name);

		// Verify the retrieved value
		$this->assertEquals($option_value, $retrieved_value);
	}

	/**
	 * Test WordPress shortcode registration and rendering
	 *
	 * @return void
	 */
	public function test_shortcode_rendering(): void {
		// Register the shortcode
		$this->instance->register_shortcodes();

		// Verify the shortcode is registered
		$this->assertTrue(shortcode_exists('your_plugin_shortcode'));

		// Test shortcode with attributes
		$atts = array(
			'title' => 'Test Title',
			'color' => 'blue',
		);

		// Get the shortcode output
		$output = do_shortcode('[your_plugin_shortcode title="Test Title" color="blue"]');

		// Verify the output
		$this->assertStringContainsString('Test Title', $output);
		$this->assertStringContainsString('blue', $output);
	}

	/**
	 * Test WordPress database interaction using wpdb
	 *
	 * @return void
	 */
	public function test_database_interaction(): void {
		global $wpdb;

		// Test data
		$table_name = $wpdb->prefix . 'your_plugin_table';
		$data = array(
			'name'       => 'Test Name',
			'value'      => 'Test Value',
			'created_at' => current_time('mysql'),
		);

		// Call the method that interacts with the database
		$result = $this->instance->insert_record($data);

		// Verify the record was inserted
		$this->assertTrue($result);

		// Retrieve the record directly from the database
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE name = %s",
			$data['name']
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$record = $wpdb->get_row($query);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		// Verify the record data
		$this->assertNotNull($record);
		$this->assertEquals('Test Name', $record->name);
		$this->assertEquals('Test Value', $record->value);
	}

	/**
	 * Test WordPress hook integration
	 *
	 * @return void
	 */
	public function test_action_hook_integration(): void {
		// Set up a test action
		$test_value = null;
		add_action(
			'your_plugin_test_action',
			function ( $value ) use ( &$test_value ): void {
				$test_value = $value;
			}
		);

		// Call the method that triggers the action
		$this->instance->trigger_action('test_data');

		// Verify the action was triggered with the correct data
		$this->assertEquals('test_data', $test_value);
	}

	/**
	 * Clean up after the test
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Any specific cleanup for this test

		parent::tearDown();
	}
}
