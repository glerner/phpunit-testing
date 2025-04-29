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
  - **PHP_CodeSniffer**: Code style and standards checking
  - **WordPress Coding Standards**: WordPress-specific standards
  - **PHP Compatibility**: Version compatibility checking
  - **PHP CS Fixer**: Automatic code style fixes
  - **PHPStan**: Static analysis for bug detection
- **Convenient Composer Scripts**: Ready-to-use commands for testing, code quality checks, and development workflows

## Requirements

- PHP 8.1 or higher
- PHPUnit 9.x (Note: As of April 2025, PHPUnit 10.x is not supported due to incompatibility with Yoast PHPUnit Polyfills)
- WordPress 6.1 or higher (for integration tests)
- Composer

## Why Separate Test Types?

The clean separation of test types is a key differentiator of this framework compared to most WordPress testing approaches. This separation provides several important benefits:

- **Clearer Test Organization**: No confusion about which mocking approach to use
- **Simplified Debugging**: Issues in one test type don't affect others
- **Easier Maintenance**: Each test type can evolve independently
- **Better Developer Experience**: Clear, dedicated paths for each testing need
- **Faster Test Execution**: Only load what you need for each test type

Rather than using a single configuration with conditional logic and environment variables, this framework provides dedicated files and directories for each test type, making it more intuitive and less error-prone.

## Documentation

- [Installation Guide](docs/guides/installation-guide.md) - Step-by-step instructions for installing and configuring the framework
- [PHPUnit Testing Tutorial](docs/guides/phpunit-testing-tutorial.md) - Comprehensive guide to writing tests
- [Troubleshooting Guide](docs/guides/troubleshooting-guide.md) - Solutions for common issues and challenges
- [Git and GitHub Setup Guide](docs/git-github-setup-guide.md) - How to set up Git repositories for your projects
- [Technology Choices](docs/technology-choices.md) - Explanation of technology decisions for this project

## Installation Options

### Installing PHPUnit

PHPUnit is installed via Composer as part of the framework's dependencies. The framework requires PHPUnit 9.x due to compatibility requirements with other dependencies.

After setting up the framework, you can run PHPUnit using:

```bash
# From your plugin directory
./vendor/bin/phpunit
```

For detailed instructions on installing and configuring this framework, see the [Installation Guide](docs/guides/installation-guide.md).

For guidance on writing and running tests, see the [PHPUnit Testing Tutorial](docs/guides/phpunit-testing-tutorial.md).

### Option 1: Git Submodule (Recommended for Contributors)

```bash
# From your plugin's root directory
git submodule add https://github.com/glerner/phpunit-testing.git tests/framework
```

### Option 2: Composer Package (Recommended for Standard Usage)

```bash
# From your plugin's root directory
composer require glerner/phpunit-testing --dev
```

## Usage

Basic usage example:

```php
// In your test file
use GL\Testing\Framework\Unit\Unit_Test_Case;

class Test_My_Class extends Unit_Test_Case {
    public function test_something() {
        // Your test code here
        $this->assertTrue(true);
    }
}
```

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

- PHP 7.4 or higher
- Composer
- PHPUnit 9.0 or higher

## License

This project is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.
