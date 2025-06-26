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
 * @package WP_PHPUnit_Framework
 * @subpackage Bootstrap
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Bootstrap;

use WP_PHPUnit_Framework\Event\Listener\GlTestRunnerExecutionStartedListener;
use WP_PHPUnit_Framework\Event\Listener\GlTestSuiteStartedListener;

use function WP_PHPUnit_Framework\get_setting;

// Display test type header
echo "\n=== Unit Test Setup ===\n";
echo "Initializing unit test environment...\n";

// The main bootstrap.php file handles autoloading, so it is not needed here.

// Register the event subscribers for PHPUnit 11+
if (class_exists('PHPUnit\Event\Facade')) {
    echo "- Registering event subscribers with PHPUnit\n";
    $subscriber = new GlTestRunnerExecutionStartedListener($logDir);
    \PHPUnit\Event\Facade::instance()->registerSubscriber($subscriber);

    $subscriber = new GlTestSuiteStartedListener($logDir);
    \PHPUnit\Event\Facade::instance()->registerSubscriber($subscriber);
}

// Initialize Mockery
echo "- Setting up Mockery\n";
\Mockery::globalHelpers();

// Set up Brain\Monkey if available
if (class_exists('\Brain\Monkey')) {
    echo "- Setting up Brain\\Monkey\n";
    \Brain\Monkey\setUp();

    // Register teardown function to clean up Brain\Monkey
    register_shutdown_function(function() {
        \Brain\Monkey\tearDown();
    });

    echo "  For Brain\\Monkey usage examples, see:\n";
    echo "  /docs/guides/phpunit-testing-tutorial.md#using-brain-monkey-for-wordpress-functions\n";
}

// Load any additional unit test dependencies
$test_dependencies = [
    // Add paths to any additional test dependencies here
];

foreach ($test_dependencies as $dep) {
    if (file_exists($dep)) {
        echo "- Loading test dependency: $dep\n";
        require_once $dep;
    }
}

echo "\n=== Unit Test Environment Ready ===\n\n";
