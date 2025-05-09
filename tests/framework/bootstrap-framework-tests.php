<?php
/**
 * Bootstrap file for Framework test tests
 *
 * This file is specifically for testing the framework itself,
 * not for use by developers using the framework.
 *
 * @package WP_PHPUnit_Framework
 * @subpackage Tests
 */

declare(strict_types=1);

namespace WP_PHPUnit_Framework\Tests;

// Create placeholder classes for WordPress test classes when running framework tests
if (!class_exists('WP_UnitTestCase_Base')) {
    /**
     * Base class for WordPress unit tests
     * This is a placeholder for framework tests only
     */
    class WP_UnitTestCase_Base extends \PHPUnit\Framework\TestCase {
        // Minimal implementation for framework tests
    }
}

if (!class_exists('WP_UnitTestCase')) {
    /**
     * WordPress unit test case
     * This is a placeholder for framework tests only
     */
    class WP_UnitTestCase extends WP_UnitTestCase_Base {
        // Minimal implementation for framework tests
    }
}

// Now include the regular bootstrap file
require_once __DIR__ . '/../bootstrap/bootstrap-framework.php';
