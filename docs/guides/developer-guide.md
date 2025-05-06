# Developer Guide for GL WordPress PHPUnit Testing Framework

## Setting Up the Development Environment

### Prerequisites
- PHP 7.4 or higher
- Composer
- Git
- PHPUnit 9.0 or higher
- For integration tests: WordPress test library

### Environment Configuration

1. Copy the sample testing environment file:
   ```bash
   cp .env.sample.testing .env.testing
   ```

2. Edit `.env.testing` with your local WordPress test library path (for integration tests):
   ```bash
   # Only needed for integration tests
   WP_TESTS_DIR=/path/to/wordpress-tests-lib
   ```

   Alternatively, you can set the WordPress test library path as an environment variable:
   ```bash
   export WP_TESTS_DIR=/path/to/wordpress-tests-lib
   ```

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/glerner/phpunit-testing.git
   cd phpunit-testing
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

## Project Structure

```
phpunit-testing/
├── config/                     # Configuration templates
│   ├── phpunit-unit.xml.dist       # Unit test configuration
│   ├── phpunit-wp-mock.xml.dist     # WP_Mock test configuration
│   ├── phpunit-integration.xml.dist # Integration test configuration
│   └── phpunit-framework-tests.xml.dist # Framework test configuration
├── docs/                       # Documentation
│   ├── guides/
│   │   ├── phpunit-testing-tutorial.md
│   │   └── installation-guide.md
│   └── git-github-setup-guide.md
├── src/                        # Base test classes
│   ├── Unit/
│   │   └── Unit_Test_Case.php
│   ├── WP_Mock/
│   │   └── WP_Mock_Test_Case.php
│   └── Integration/
│       └── Integration_Test_Case.php
├── templates/                  # Example test templates
│   ├── unit/
│   │   └── Example_Unit_Test.php
│   ├── wp-mock/
│   │   └── Example_WP_Mock_Test.php
│   └── integration/
│       └── Example_Integration_Test.php
└── tests/                      # Framework's own tests
    └── bootstrap/              # Bootstrap files
        ├── bootstrap.php
        ├── bootstrap-unit.php
        ├── bootstrap-wp-mock.php
        └── bootstrap-integration.php
```

## Test Types

The framework supports three types of tests:

### 1. Unit Tests
- Extend `WP_PHPUnit_Framework\Unit\Unit_Test_Case`
- Test isolated units of code without WordPress
- Use Mockery for mocking dependencies
- Fast and focused

### 2. WP_Mock Tests
- Extend `WP_PHPUnit_Framework\WP_Mock\WP_Mock_Test_Case`
- Test code that interacts with WordPress functions
- Use WP_Mock and Brain\Monkey to mock WordPress functions
- No WordPress installation required

### 3. Integration Tests
- Extend `WP_PHPUnit_Framework\Integration\Integration_Test_Case`
- Test code that requires a full WordPress environment
- Test interactions with WordPress core, database, and hooks
- Requires WordPress test library

## Using the Framework in Your Plugin

### 1. Installation

Add the framework to your plugin using Composer:

```bash
composer require --dev glerner/phpunit-testing
```

### 2. Configuration

Copy the configuration templates to your plugin:

```bash
cp vendor/glerner/phpunit-testing/config/phpunit*.xml.dist .
```

Modify the configuration files to match your plugin's structure.

### 3. Using with Lando WordPress

If you're using Lando for WordPress development, the framework includes scripts to sync the testing framework to your WordPress environment and run tests there.

First, set up your environment variables:

```bash
# Set up environment variables in .env.testing
cp .env.sample.testing .env.testing

# Edit .env.testing to match your Lando environment
# Especially set the correct paths:
# FILESYSTEM_WP_ROOT=/app
# WP_TESTS_DIR=/app/wp-content/plugins/wordpress-develop/tests/phpunit
```

#### Option 1: Running from inside Lando (Recommended)

The most reliable way to sync and test is to run commands inside the Lando environment:

```bash
# Sync the framework to your WordPress environment
composer lando:sync

# Run tests in the WordPress environment
composer lando:test
```

#### Option 2: Running from your local environment

Alternatively, you can try running from your local environment, but this may encounter permission issues:

```bash
# Sync the framework to your WordPress environment
composer sync:wp

# Run tests in the WordPress environment
composer wp:test
```

This process will:
1. Sync the framework files to your WordPress plugins directory
2. Set up the WordPress test environment if needed
3. Run tests in the WordPress environment

### 4. Create Test Classes

Copy and adapt the example templates for your plugin:

```bash
# Create directories
mkdir -p tests/Unit tests/WP_Mock tests/Integration

# Copy example templates
cp vendor/glerner/phpunit-testing/templates/unit/Example_Unit_Test.php tests/Unit/Your_Feature_Test.php
cp vendor/glerner/phpunit-testing/templates/wp-mock/Example_WP_Mock_Test.php tests/WP_Mock/Your_WP_Feature_Test.php
cp vendor/glerner/phpunit-testing/templates/integration/Example_Integration_Test.php tests/Integration/Your_Integration_Test.php
```

Modify the test files to match your plugin's structure and functionality.

### 5. Run Tests

```bash
# Run all tests (each type separately)
vendor/bin/phpunit -c config/phpunit-unit.xml.dist && \
vendor/bin/phpunit -c config/phpunit-wp-mock.xml.dist && \
vendor/bin/phpunit -c config/phpunit-integration.xml.dist

# Or use the composer script
composer test

# Run only unit tests
vendor/bin/phpunit -c config/phpunit-unit.xml.dist

# Run only WP_Mock tests
vendor/bin/phpunit -c phpunit-wp-mock.xml.dist

# Run only integration tests
vendor/bin/phpunit -c phpunit-integration.xml.dist
```

## Development Workflow

### Code Standards

This project follows PSR-12 coding standards and WordPress coding standards where appropriate.

```bash
# Check coding standards
composer run-script phpcs

# Fix coding standards issues
composer run-script phpcbf
```

> **Note:** Using Composer's phpcbf command works more reliably than trying to install and configure Visual Studio Code extensions for PHP code formatting. The command-line approach ensures consistent formatting across all development environments.

### Testing

Run tests before submitting pull requests:

```bash
# Run all tests
composer run-script test

# Run specific test types
composer run-script test:unit
composer run-script test:wp-mock
composer run-script test:integration
```

### Git Workflow

See [Git and GitHub Setup Guide](../git-github-setup-guide.md) for detailed information on:
- Setting up Git
- Creating branches
- Making commits
- Submitting pull requests

## Recommended IDE Setup

### Visual Studio Code

#### Extensions

1. **PHP Intelephense** (bmewburn.vscode-intelephense-client)
   - Provides PHP code intelligence including:
     - Code completion and IntelliSense
     - Error detection
     - Code navigation and refactoring tools

2. **PHP DocBlocker** (neilbrayfield.php-docblocker)
   - Automatically generates PHP docblocks
   - Helps maintain consistent documentation

3. **PHP CS Fixer** (junstyle.php-cs-fixer)
   - Helps format code according to standards
   - Can be configured to use the project's ruleset

4. **PHPUnit Test Explorer** (recca0120.vscode-phpunit)
   - Run and debug PHPUnit tests from within VS Code
   - View test results in the editor

#### Settings

Add these settings to your `.vscode/settings.json`:

```json
{
    "php.suggest.basic": false,
    "phpcs.enable": true,
    "phpcs.standard": "./phpcs.xml.dist",
    "php-cs-fixer.config": "./.php-cs-fixer.dist.php",
    "php-cs-fixer.executablePath": "${workspaceFolder}/vendor/bin/php-cs-fixer",
    "php-cs-fixer.onsave": false,
    "intelephense.environment.includePaths": [
        "./vendor"
    ]
}
```

## Additional Resources

- [PHPUnit Testing Tutorial](phpunit-testing-tutorial.md)
- [Installation Guide](installation-guide.md)
- [Contributing Guidelines](../../CONTRIBUTING.md)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WP_Mock Documentation](https://github.com/10up/wp_mock)
- [Brain\Monkey Documentation](https://brain-wp.github.io/BrainMonkey/)
