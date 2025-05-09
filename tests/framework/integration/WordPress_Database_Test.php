<?php
/**
 * Example Integration test for WordPress database functionality
 * This is a framework test, not a real integration test
 *
 * @package WP_PHPUnit_Framework\Tests\Framework\Integration
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Framework\Integration;

use PHPUnit\Framework\TestCase;

// Mock WordPress functions and classes for framework tests
// These are only for testing the framework itself and are completely independent from WordPress

/**
 * Mock WP_Post class
 */
class WP_Post {
	public $ID;
	public $post_title;
	public $post_content;
	public $post_status;
	
	public function __construct(int $id, string $title = '', string $content = '', string $status = 'publish') {
		$this->ID = $id;
		$this->post_title = $title;
		$this->post_content = $content;
		$this->post_status = $status;
	}
}

/**
 * Mock function for wp_insert_post
 */
function wp_insert_post(array $post_data): int {
	return 123; // Mock post ID
}

/**
 * Mock function for get_post
 */
function get_post(int $post_id): WP_Post {
	return new WP_Post(
		$post_id,
		'Test Post Title',
		'Test Post Content',
		'publish'
	);
}

/**
 * Mock function for wp_update_post
 */
function wp_update_post(array $post_data): int {
	return $post_data['ID'] ?? 123;
}

/**
 * Mock function for wp_delete_post
 */
function wp_delete_post(int $post_id, bool $force_delete = false): bool {
	return true;
}

/**
 * Mock function for add_option
 */
function add_option(string $option, $value): bool {
	return true;
}

/**
 * Mock function for get_option
 */
function get_option(string $option, $default = false) {
	return 'test_value';
}

/**
 * Mock function for update_option
 */
function update_option(string $option, $value): bool {
	return true;
}

/**
 * Mock function for delete_option
 */
function delete_option(string $option): bool {
	return true;
}

/**
 * Mock function for wp_create_user
 */
function wp_create_user(string $username, string $password, string $email = ''): int {
	return 456; // Mock user ID
}

/**
 * Mock function for get_user_by
 */
function get_user_by(string $field, $value): object {
	$user = new \stdClass();
	$user->ID = 456;
	$user->user_login = 'testuser';
	$user->user_email = 'test@example.com';
	return $user;
}

/**
 * Mock function for wp_update_user
 */
function wp_update_user(array $user_data): int {
	return $user_data['ID'] ?? 456;
}

/**
 * Mock function for wp_delete_user
 */
function wp_delete_user(int $user_id, int $reassign = 0): int {
	return 1; // Return 1 for success
}

/**
 * Mock function for wp_cache_flush
 */
function wp_cache_flush(): bool {
	return true;
}

/**
 * Example test case for WordPress database functionality
 * This is a framework test that mocks WordPress functions
 */
class WordPress_Database_Test extends TestCase {

	/**
	 * Test data for WordPress database tests
	 * Using non-standard values to ensure the code doesn't make assumptions
	 */
	protected array $test_data;

	/**
	 * Set up test data before each test
	 */
	protected function setUp(): void {
		parent::setUp();

		// Define test data with non-standard values
		$this->test_data = [
			'post' => [
				'title' => 'XYZ Test Post 123',
				'content' => 'This is non-standard test content with unique text @#$%^&*().',
				'status' => 'publish',
				'type' => 'post',
				'updated_title' => 'UPDATED: XYZ Test Post 123'
			],
			'option' => [
				'name' => 'test_framework_xyz_option',
				'value' => ['setting1' => 'value_abc', 'setting2' => 'value_xyz'],
				'updated_value' => ['setting1' => 'new_value_123', 'setting2' => 'value_xyz']
			],
			'user' => [
				'login_prefix' => 'test_xyz_user_',
				'email_domain' => 'example-test.org',
				'password' => 'test_pass_XYZ_123!@#',
				'first_name' => 'Test_XYZ',
				'last_name' => 'User_ABC'
			]
		];
	}

	/**
	 * Test post creation, retrieval, update and deletion
	 * (Create, Read, Update, Delete operations)
	 */
	public function test_post_crud_operations(): void {
		// Create a test post with non-standard values
		$post_id = wp_insert_post([
			'post_title'   => $this->test_data['post']['title'],
			'post_content' => $this->test_data['post']['content'],
			'post_status'  => $this->test_data['post']['status'],
			'post_author'  => 1,
			'post_type'    => $this->test_data['post']['type'],
		]);

		// Verify post was created
		$this->assertIsInt($post_id);
		$this->assertGreaterThan(0, $post_id);

		// Retrieve the post
		$post = get_post($post_id);

		// Verify post data
		$this->assertInstanceOf(\WP_Post::class, $post);
		$this->assertEquals($this->test_data['post']['title'], $post->post_title);
		$this->assertEquals($this->test_data['post']['content'], $post->post_content);
		$this->assertEquals($this->test_data['post']['status'], $post->post_status);

		// Update the post
		$updated = wp_update_post([
			'ID'         => $post_id,
			'post_title' => $this->test_data['post']['updated_title'],
		]);

		// Verify update was successful
		$this->assertEquals($post_id, $updated);

		// Retrieve updated post
		$updated_post = get_post($post_id);
		$this->assertEquals($this->test_data['post']['updated_title'], $updated_post->post_title);

		// Delete the post
		wp_delete_post($post_id, true);

		// Verify post was deleted
		$deleted_post = get_post($post_id);
		$this->assertNull($deleted_post);
	}

	/**
	 * Test WordPress options API
	 * Tests creating, reading, updating, and deleting WordPress options
	 */
	public function test_options_api(): void {
		// Get option name and values from test data
		$option_name = $this->test_data['option']['name'];
		$option_value = $this->test_data['option']['value'];
		
		// Add the option
		$result = add_option($option_name, $option_value);
		$this->assertTrue($result);

		// Get the option
		$retrieved = get_option($option_name);
		$this->assertEquals($option_value, $retrieved);

		// Update the option
		$updated_value = $this->test_data['option']['updated_value'];
		$update_result = update_option($option_name, $updated_value);
		$this->assertTrue($update_result);

		// Verify the update
		$retrieved_updated = get_option($option_name);
		$this->assertEquals($updated_value, $retrieved_updated);

		// Delete the option
		$delete_result = delete_option($option_name);
		$this->assertTrue($delete_result);

		// Verify deletion
		$retrieved_after_delete = get_option($option_name, 'default');
		$this->assertEquals('default', $retrieved_after_delete);
	}

	/**
	 * Test WordPress user functions
	 * Tests creating, reading, updating, and deleting WordPress users
	 */
	public function test_user_functions(): void {
		// Create a test user with non-standard values
		$username = $this->test_data['user']['login_prefix'] . time();
		$user_id = wp_create_user(
			$username,
			$this->test_data['user']['password'],
			'test_' . time() . '@' . $this->test_data['user']['email_domain']
		);

		// Verify user was created
		$this->assertIsInt($user_id);
		$this->assertGreaterThan(0, $user_id);

		// Get user
		$user = get_user_by('id', $user_id);
		$this->assertInstanceOf(\WP_User::class, $user);
		$this->assertEquals($username, $user->user_login);

		// Update user
		$updated = wp_update_user([
			'ID'         => $user_id,
			'first_name' => $this->test_data['user']['first_name'],
			'last_name'  => $this->test_data['user']['last_name'],
		]);

		// Verify update
		$this->assertEquals($user_id, $updated);
		$updated_user = get_user_by('id', $user_id);
		$this->assertEquals($this->test_data['user']['first_name'], $updated_user->first_name);
		$this->assertEquals($this->test_data['user']['last_name'], $updated_user->last_name);

		// Delete user
		$deleted = wp_delete_user($user_id, 0);
		$this->assertEquals(1, $deleted);

		// Verify deletion
		$deleted_user = get_user_by('id', $user_id);
		$this->assertFalse($deleted_user);
	}
}
