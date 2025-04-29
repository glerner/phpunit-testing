<?php
/**
 * Bootstrap file for Unit tests
 *
 * Handles initialization of testing environment for unit tests.
 * Sets up Mockery and other dependencies for isolated unit testing.
 *
 * For more information on mocking strategies, see:
 * @see /docs/guides/phpunit-testing-tutorial.md#mocking-strategies
 *
 * @package GL_WordPress_Testing_Framework
 * @subpackage Bootstrap
 */

declare(strict_types=1);

namespace GL\Testing\Framework\Bootstrap;

// Initialize Mockery
echo "Setting up Mockery for unit tests\n";
\Mockery::globalHelpers();

// Load any additional unit test dependencies
echo "Loading unit test dependencies\n";

// Set up Brain\Monkey if available
if (class_exists('\Brain\Monkey')) {
	echo "Setting up Brain\\Monkey\n";
	\Brain\Monkey\setUp();
	
	// Register teardown function to clean up Brain\Monkey
	register_shutdown_function(function() {
	    \Brain\Monkey\tearDown();
	});
	
	echo "For Brain\Monkey usage examples, see:\n";
	echo "  /docs/guides/phpunit-testing-tutorial.md#using-brain-monkey-for-wordpress-functions\n";
}

echo "Unit test bootstrap complete\n";
