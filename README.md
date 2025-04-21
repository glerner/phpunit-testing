# GL WordPress PHPUnit Testing Framework
by George Lerner, GitHub https://github.com/glerner/

A modular, reusable testing framework for WordPress plugins and themes that provides a structured approach to PHPUnit testing.

## Overview

This repository contains a comprehensive testing framework designed specifically for WordPress development. It provides base test classes, directory structures, and configuration files that can be easily integrated into any WordPress plugin or theme project.

## Features

- **Modular Design**: Use only what you need - from basic test classes to complete development environments
- **WordPress Integration**: Specialized tools for testing WordPress hooks, filters, and functions
- **Multiple Test Types**: Support for unit tests, integration tests, and WordPress-specific tests
- **Consistent Structure**: Standardized directory organization and naming conventions
- **Development Tools**: Optional configurations for PHPStan, PHPCS, and other quality assurance tools

## Documentation

Comprehensive documentation is available in the `docs/` directory:

- [PHPUnit Testing Tutorial](docs/guides/phpunit-testing-tutorial.md) - A complete guide to PHPUnit testing for WordPress
- [Git and GitHub Setup Guide](docs/git-github-setup-guide.md) - How to set up Git repositories for your projects
- [Technology Choices](docs/technology-choices.md) - Explanation of technology decisions for this project

## Installation Options

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
