<?php
/**
 * Tests for the WP_Mock_Test_Case class.
 *
 * @package GL\Testing\Framework\Tests
 */

namespace GL\Testing\Framework\Tests\WP_Mock;

use GL\Testing\Framework\WP_Mock\WP_Mock_Test_Case;
use WP_Mock;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the WP_Mock_Test_Case class.
 */
class Test_WP_Mock_Test_Case extends TestCase {

    /**
     * Test that WP_Mock is properly integrated.
     */
    public function test_wp_mock_integration() {
        $test_case = new WP_Mock_Test_Case();
        
        // Call setUp to initialize WP_Mock
        $test_case->setUp();
        
        // Set up a WordPress function mock
        WP_Mock::userFunction('wp_kses_post', [
            'args' => ['<p>Test</p>'],
            'return' => '<p>Test</p>',
        ]);
        
        // Call the mocked function
        $result = wp_kses_post('<p>Test</p>');
        
        // Verify the mock works
        $this->assertEquals('<p>Test</p>', $result);
        
        // Call tearDown to clean up WP_Mock
        $test_case->tearDown();
        
        // If we got here without errors, WP_Mock integration is working
        $this->assertTrue(true);
    }
    
    /**
     * Test that setUp and tearDown methods work correctly.
     */
    public function test_setup_teardown() {
        $test_case = new WP_Mock_Test_Case();
        
        // Call setUp
        $test_case->setUp();
        
        // Call tearDown
        $test_case->tearDown();
        
        // If we got here without errors, setUp and tearDown are working
        $this->assertTrue(true);
    }
}
