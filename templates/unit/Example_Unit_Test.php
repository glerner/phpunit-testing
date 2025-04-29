<?php
/**
 * Example Unit Test
 *
 * This is a template for a basic unit test that demonstrates how to use
 * the GL WordPress Testing Framework for isolated unit testing.
 *
 * NOTE: This is a template file and will show IDE errors since the referenced
 * classes don't exist. You should copy this file to your plugin's test directory
 * and modify it to match your plugin's structure before using it.
 *
 * @package GL_WordPress_Testing_Framework
 * @subpackage Examples
 */

declare(strict_types=1);

namespace YourPlugin\Tests\Unit;

use GL\Testing\Framework\Unit\Unit_Test_Case;
use YourPlugin\Example_Class;
use Mockery;

/**
 * Example Unit Test class
 *
 * @covers \YourPlugin\Example_Class
 */
class Example_Unit_Test extends Unit_Test_Case {
	/**
	 * Test instance
	 *
	 * @var Example_Class
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
		$this->instance = new Example_Class();
	}

	/**
	 * Test that add method works correctly
	 *
	 * @return void
	 */
	public function test_add_method(): void {
		// Arrange - set up test data
		$a = 5;
		$b = 10;

		// Act - call the method being tested
		$result = $this->instance->add($a, $b);

		// Assert - verify the result
		$this->assertEquals(15, $result, 'The add method should return the sum of two numbers');
	}

	/**
	 * Test that the class interacts with dependencies correctly
	 *
	 * @return void
	 */
	public function test_process_with_dependency(): void {
		// Create a mock of a dependency
		$dependency = Mockery::mock('YourPlugin\Dependency_Interface');

		// Set expectations on the mock
		$dependency->shouldReceive('process')
			->once()
			->with('test-data')
			->andReturn('processed-data');

		// Create an instance with the mock dependency
		$instance = new Example_Class($dependency);

		// Call the method that uses the dependency
		$result = $instance->process_with_dependency('test-data');

		// Verify the result
		$this->assertEquals('processed-data', $result);
	}

	/**
	 * Test exception is thrown when invalid input is provided
	 *
	 * @return void
	 */
	public function test_exception_is_thrown_for_invalid_input(): void {
		// Expect an exception to be thrown
		$this->expectException(\InvalidArgumentException::class);

		// Call method with invalid input
		$this->instance->validate_input(null);
	}

	/**
	 * Example of a data provider for testing multiple scenarios
	 *
	 * @return array
	 */
	public function provide_validation_scenarios(): array {
		return array(
			'valid email' => array( 'test@example.com', true ),
			'invalid email' => array( 'not-an-email', false ),
			'empty string' => array( '', false ),
		);
	}

	/**
	 * Test validation with multiple scenarios using a data provider
	 *
	 * @dataProvider provide_validation_scenarios
	 * @param string $input    The input to validate
	 * @param bool   $expected The expected result
	 * @return void
	 */
	public function test_validation_with_data_provider( string $input, bool $expected ): void {
		$result = $this->instance->validate($input);
		$this->assertSame($expected, $result);
	}
}
