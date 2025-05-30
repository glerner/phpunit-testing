# GL WordPress PHPUnit Testing Framework
by George Lerner, GitHub https://github.com/glerner/

Repository: https://github.com/glerner/phpunit-testing.git

A modular, reusable testing framework for WordPress plugins and themes that provides a structured approach to PHPUnit testing.

## Overview

This repository contains a comprehensive testing framework designed specifically for WordPress development. It provides base test classes, directory structures, and configuration files for PHPUnit testing and Code Quality Tools that can be easily integrated into any WordPress plugin or theme project.

## Features

- **Modular Design**: Use only what you need - from basic test classes to complete development environments
- **WordPress Integration**: Specialized tools for testing WordPress hooks, filters, and functions
- **Clean Separation of Test Types**: Unlike most WordPress testing approaches, this framework maintains complete separation between different test types:
  - Dedicated directories for each test type (`tests/unit/`, `tests/wp-mock/`, `tests/integration/`)
  - Specialized configuration files for each test type (`phpunit-unit.xml.dist`, etc.)
  - Type-specific bootstrap files that load only what's needed
  - Simple execution through dedicated Composer scripts (`test:unit`, `test:wp-mock`, `test:integration`)
- **Multiple Test Types**: Support for unit tests, integration tests, and WordPress-specific tests
  - See [phpunit-testing-tutorial.md](docs/guides/phpunit-testing-tutorial.md) for detailed guidance
  - **Unit Tests**: For isolated testing of functions and classes without WordPress
  - **WP_Mock Tests**: For testing code that interacts with WordPress functions
  - **Integration Tests**: For testing against a real WordPress database and environment
  - Any class can have multiple test types (e.g., Unit tests for specific data returns, WP_Mock tests for WordPress function interactions, and Integration tests for database operations)
- **Consistent Structure**: Standardized directory organization and naming conventions
- **Comprehensive Testing Libraries**:
  - **PHPUnit**: Core testing framework
  - **WP_Mock**: WordPress function mocking
  - **Brain\Monkey**: WordPress hooks and functions mocking
  - **Mockery**: General-purpose mocking
  - **PHPUnit Polyfills**: Cross-version compatibility
- **Code Quality Tools**:
  - **PHP_CodeSniffer (PHPCS/PHPCBF)**: Code style checking and automatic fixing
    - Configured with practical rule exclusions for WordPress developers
    - Includes fixes for common PHPCBF issues that prevent it from running successfully
    - Custom scripts to handle spaces-to-tabs conversion efficiently
  - **WordPress Coding Standards**: WordPress-specific coding standards
  - **PHP Compatibility**: PHP version compatibility checking
  - **PHPStan**: Static analysis for finding bugs and type errors
- **Convenient Composer Scripts**: Ready-to-use commands for testing, code quality checks, and development workflows

## Requirements

- PHP 8.1 or higher
- PHPUnit 9.x (Note: As of April 2025, PHPUnit 10.x is not supported due to incompatibility with Yoast PHPUnit Polyfills)
- WordPress 6.1 or higher (for integration tests)
- Composer

## Why Separate PHPUnit Test Types?

The clean separation of PHPUnit test types is a key differentiator of this framework compared to most WordPress testing approaches. This separation provides several important benefits:

- **Clearer Test Organization**: No confusion about which mocking approach to use; ability to run multiple test types on the same file (e.g., unit tests for specific returns from known inputs, and integration tests to verify WordPress database and Admin page interactions)
- **Simplified Debugging**: Issues in one test type don't affect others
- **Easier Maintenance**: Each test type can evolve independently
- **Better Developer Experience**: Clear, dedicated paths for each testing need
- **Faster Test Execution**: Only load what you need for each test type

Rather than using a single configuration with conditional logic and environment variables, this framework provides dedicated files and directories for each test type, making it more intuitive and less error-prone.

## Documentation

- [Installation Guide](docs/guides/installation-guide.md) - Step-by-step instructions for installing and configuring the framework
- [PHPUnit Testing Tutorial](docs/guides/phpunit-testing-tutorial.md) - Comprehensive guide to writing tests
- [PHPCS & PHPCBF Guide](docs/tools/PHPCS-PHPCBF-Guide.md) - How to use code quality tools effectively
- [Troubleshooting Guide](docs/guides/troubleshooting-guide.md) - Solutions for common issues and challenges
- [Git and GitHub Setup Guide](docs/git-github-setup-guide.md) - How to set up Git repositories for your projects
- [Technology Choices](docs/technology-choices.md) - Explanation of technology decisions for this project

## Installation Options

### Installing GL WordPress PHPUnit Testing Framework

For detailed instructions on installing and configuring this framework, see the [Installation Guide](docs/guides/installation-guide.md).

## Usage

### Writing Tests

Basic usage example:

```php
// In your test file
use WP_PHPUnit_Framework\Unit\Unit_Test_Case;

class Test_My_Class extends Unit_Test_Case {
    public function test_something() {
        // Your test code here
        $this->assertTrue(true);
    }
}
```
For guidance on writing and running tests using this full package, see the [PHPUnit Testing Tutorial](docs/guides/phpunit-testing-tutorial.md).


### Running Tests

To run tests, you'll need to sync your plugin to the WordPress installation first, then navigate to your plugin directory inside WordPress and run the tests:

```bash
# Step 1: From your plugin development directory, sync to WordPress
cd ~/sites/your-plugin/
php bin/sync-to-wp.php

# Step 2: IMPORTANT - Navigate to your plugin directory INSIDE WordPress
cd ~/sites/wordpress/wp-content/plugins/your-plugin/

# Step 3: Run tests using Composer
composer test:unit         # Run unit tests
composer test:wp-mock      # Run WP_Mock tests
composer test:integration  # Run integration tests
composer test              # Run all tests
```

If you're using Lando, you can run tests directly with Lando commands:

```bash
# After syncing your plugin
cd ~/sites/wordpress/wp-content/plugins/your-plugin/
lando test:unit         # Run unit tests
lando test:wp-mock      # Run WP_Mock tests
lando test:mock         # Run mock tests
lando test:integration  # Run integration tests
lando test:coverage     # Generate code coverage report
```

> **IMPORTANT**: Tests must be run from within the WordPress environment. Only code quality tools like PHPCS or PHPStan can be run directly from your plugin development directory.

#### Using the Sync-and-Test Script

For a simpler approach, you can use the included sync-and-test.php script that handles all steps in one command:

```bash
# From your plugin development directory
cd ~/sites/your-plugin/
php bin/sync-and-test.php --unit
```

The script supports all the same options as the individual commands:

```bash
php bin/sync-and-test.php --wp-mock      # Run WP_Mock tests
php bin/sync-and-test.php --integration  # Run integration tests
php bin/sync-and-test.php --all          # Run all test types
php bin/sync-and-test.php --coverage     # Generate code coverage
```

For more detailed instructions on running tests, including advanced configuration options, see the [PHPUnit Testing Tutorial](docs/guides/phpunit-testing-tutorial.md).

## Directory Structure

```
phpunit-testing/
├── src/                 # Source code for the testing framework
│   ├── Unit/            # Base classes for unit tests
│   ├── WP_Mock/         # Base classes for WordPress mock tests
│   └── Integration/     # Base classes for integration tests
├── config/              # Configuration templates
│   ├── phpunit/         # PHPUnit configuration templates
│   ├── phpstan/         # PHPStan configuration templates
│   └── phpcs/           # PHPCS configuration templates
├── docs/                # Documentation
│   └── guides/          # Detailed guides and tutorials
└── templates/           # Template files for test creation
```

## Requirements

- PHP 8.1 or higher (probably works with lower)
- Composer
- PHPUnit 9.0 or higher
- Meant for WordPress version 6.1 or higher

## License

This project is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.
