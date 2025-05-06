<?php
/**
 * Example Integration test for WordPress database functionality
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Integration
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests\Integration;

use WP_PHPUnit_Framework\Integration\Integration_Test_Case;

/**
 * Example test case for WordPress database functionality
 */
class WordPress_Database_Test extends Integration_Test_Case {

	/**
	 * Test post creation and retrieval
	 */
	public function test_post_crud_operations(): void {
		// Create a test post
		$post_id = wp_insert_post([
			'post_title'   => 'Test Post',
			'post_content' => 'This is test content.',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'post',
		]);

		// Verify post was created
		$this->assertIsInt($post_id);
		$this->assertGreaterThan(0, $post_id);

		// Retrieve the post
		$post = get_post($post_id);

		// Verify post data
		$this->assertInstanceOf(\WP_Post::class, $post);
		$this->assertEquals('Test Post', $post->post_title);
		$this->assertEquals('This is test content.', $post->post_content);
		$this->assertEquals('publish', $post->post_status);

		// Update the post
		$updated = wp_update_post([
			'ID'         => $post_id,
			'post_title' => 'Updated Test Post',
		]);

		// Verify update was successful
		$this->assertEquals($post_id, $updated);

		// Retrieve updated post
		$updated_post = get_post($post_id);
		$this->assertEquals('Updated Test Post', $updated_post->post_title);

		// Delete the post
		wp_delete_post($post_id, true);

		// Verify post was deleted
		$deleted_post = get_post($post_id);
		$this->assertNull($deleted_post);
	}

	/**
	 * Test WordPress options API
	 */
	public function test_options_api(): void {
		// Set a test option
		$option_name = 'test_framework_option';
		$option_value = ['setting1' => 'value1', 'setting2' => 'value2'];
		
		// Add the option
		$result = add_option($option_name, $option_value);
		$this->assertTrue($result);

		// Get the option
		$retrieved = get_option($option_name);
		$this->assertEquals($option_value, $retrieved);

		// Update the option
		$updated_value = ['setting1' => 'new_value', 'setting2' => 'value2'];
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
	 */
	public function test_user_functions(): void {
		// Create a test user
		$username = 'testuser_' . time();
		$user_id = wp_create_user(
			$username,
			'password123',
			'test_' . time() . '@example.com'
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
			'first_name' => 'Test',
			'last_name'  => 'User',
		]);

		// Verify update
		$this->assertEquals($user_id, $updated);
		$updated_user = get_user_by('id', $user_id);
		$this->assertEquals('Test', $updated_user->first_name);
		$this->assertEquals('User', $updated_user->last_name);

		// Delete user
		$deleted = wp_delete_user($user_id, true);
		$this->assertTrue($deleted);

		// Verify deletion
		$deleted_user = get_user_by('id', $user_id);
		$this->assertFalse($deleted_user);
	}
}
