# PHPUnit and Testing Tools Installation Guide

This guide provides step-by-step instructions for installing PHPUnit and related testing tools for WordPress plugin development.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installing PHPUnit](#installing-phpunit)
- [Installing WP_Mock](#installing-wp_mock)
- [Installing Brain\Monkey](#installing-brainmonkey)
- [Setting Up WordPress Test Library](#setting-up-wordpress-test-library)
- [Configuring Composer](#configuring-composer)
- [Troubleshooting](#troubleshooting)

## Prerequisites

Before installing the testing tools, ensure you have:

- PHP 7.4 or higher (for WordPress, should use PHP 8.0+)
- Composer installed and available in your path
- Git (for cloning repositories)
- MySQL/MariaDB (for integration tests)

## Installing PHPUnit

Start from in your plugin's folder, in your WordPress installation.

PHPUnit is the core testing framework we'll use. We recommend installing it via Composer:

```bash
# use your actual location
cd ~/sites/wordpress/wp-content/plugins/gl-phpunit-testing-framework

# Add PHPUnit as a dev dependency
composer require --dev phpunit/phpunit ^9.0
```

> **Note:** The version of PHPUnit you use should be compatible with your PHP version. For PHP 7.4+, PHPUnit 9.x is recommended.

## Installing WP_Mock

WP_Mock is a library that provides a framework for mocking WordPress functions and classes:

```bash
# Add WP_Mock as a dev dependency
composer require --dev 10up/wp_mock ^0.4
```

## Installing Brain\Monkey

Brain\Monkey complements WP_Mock by providing additional mocking capabilities for WordPress functions:

```bash
# Add Brain\Monkey as a dev dependency
composer require --dev brain/monkey ^2.6
```

Brain\Monkey is particularly useful for:
- Mocking WordPress functions
- Mocking WordPress hooks (actions and filters)
- Testing code that interacts with WordPress core

For more information on using Brain\Monkey, see the [Mocking Strategies](phpunit-testing-tutorial.md#mocking-strategies) section of our testing tutorial.

## Update Dependencies

After adding all the required packages, run `composer update` to ensure all dependencies are properly resolved and installed:

```bash
composer update
```

This command will update all dependencies to their latest versions according to the version constraints in your `composer.json` file.

## Creating the Environment Configuration File

Before setting up the WordPress test library, you should create a `.env.testing` file that contains your environment-specific configuration, if you use other than the default settings. This file is essential for the test framework to locate your WordPress installation and configure the test database.

```bash
# Copy the sample environment file to create your configuration
cp .env.sample.testing .env.testing
```
Note: If you already have a .env.testing, modify it to include any additional settings you may need.

Edit the `.env.testing` file to match your environment. The most important settings are:

### WordPress and Test Library Paths
- `WP_ROOT`: Path to your WordPress installation (e.g., `/app` in Lando or `/home/username/sites/wordpress` on local machine)
- `WP_TESTS_DIR`: Path to the WordPress test library

### Database Configuration
- `WP_TESTS_DB_NAME`: Name of the test database (required by PHPUnit's WordPress testing library, should be `wordpress_test`)
- `WP_TESTS_DB_USER`: Database username
- `WP_TESTS_DB_PASSWORD`: Database password
- `WP_TESTS_DB_HOST`: Database host

### Framework Development (if applicable)
- `FILESYSTEM_WP_ROOT`: Path to WordPress on your local filesystem (without trailing slash)
- `FRAMEWORK_DEST_NAME`: Name of the WordPress plugin directory where the framework will be synced

### Test Configuration
- `TEST_ERROR_LOG`: Path to error log file
- `PHP_MEMORY_LIMIT`: Memory limit for PHP during tests
- `COVERAGE_REPORT_PATH`: Path for coverage reports
- `CLOVER_REPORT_PATH`: Path for Clover XML reports

For Lando environments, you'll typically use:

```
# WordPress paths within container
WP_TESTS_DIR=/app/wp-content/plugins/wordpress-develop/tests/phpunit
WP_ROOT=/app

# Database configuration
WP_TESTS_DB_NAME=wordpress_test
WP_TESTS_DB_USER=wordpress
WP_TESTS_DB_PASSWORD=wordpress
WP_TESTS_DB_HOST=database

# Framework development (if developing the framework itself)
FILESYSTEM_WP_ROOT=/home/username/sites/wordpress  # Your local path, not container path
FRAMEWORK_DEST_NAME=gl-phpunit-testing-framework

# Test configuration
TEST_ERROR_LOG=/tmp/phpunit-testing-error.log
PHP_MEMORY_LIMIT=512M
```

## Setting Up WordPress Test Library

For integration tests that interact with a real WordPress installation, you'll need the WordPress test library.

> **Note:** While WordPress CLI's `wp scaffold plugin-tests` command generates a shell script (`install-wp-tests.sh`), we recommend using our PHP script instead, which provides better environment detection, error handling, and compatibility with different setups including Lando.

This framework provides a PHP script to set up the WordPress test environment. The script needs to access both the file system and database, which may require different environments depending on your setup.

Before running the script, make sure your `.env.testing` file has the correct `SSH_COMMAND` setting for your environment:

```bash
# First, ensure your .env.testing has the correct SSH_COMMAND setting:
# - SSH_COMMAND=none          # For local development with direct DB access
# - SSH_COMMAND=ssh           # Already in an SSH session with DB access (don't launch another SSH session)
# - SSH_COMMAND=lando ssh     # For Lando environments
# - SSH_COMMAND=yourcommand   # Whatever command you need for your specific environment

# Then run the setup script from your plugin directory
php bin/setup-plugin-tests.php
```

This script will:
- Download the WordPress testing suite from the official WordPress develop repository
- Configure the test database (using the SSH_COMMAND setting for database operations)
- Set up necessary configuration files
- Create test directories if they don't exist
- Set up build directories for test logs and coverage reports

> **Important:** The setup script performs both local filesystem operations and database operations. The `SSH_COMMAND` setting tells the script how to get to the correct terminal to run database commands, which may need to be executed in a different environment than the script itself.
>
> **Note on Database Types:** The WordPress test suite is designed to work with MySQL/MariaDB databases. While the script currently uses `mysql` commands, support for other database systems may be added in future versions if there is demand.

### Test Directory Structure

The framework uses a modular approach to organize test files and results:

1. **Plugin-specific test directories**: Each plugin maintains its own separate test files and results
   ```
   your-plugin-directory/
   ├── tests/                 # Your test files
   │   ├── unit/              # Unit tests
   │   ├── integration/       # Integration tests
   │   └── wp-mock/           # WP_Mock tests
   ├── build/                 # Test results (created by setup script)
   │   ├── logs/              # Test logs
   │   └── coverage/          # Coverage reports
   └── ...
   ```

2. **Multiple plugins support**: When testing multiple plugins in a single WordPress installation, each plugin maintains its own isolated test environment and results

3. **Version control**: Since test results are stored within your plugin directory, you can:
   - Include them in version control to track test coverage over time
   - Exclude them by adding `/build/` to your `.gitignore` file
   - Share test configurations while ignoring environment-specific results

### Database Configuration

The script will automatically detect your environment (including Lando) and use the appropriate database settings. If you need to specify custom database settings, you can set these environment variables before running the script:

```bash
# Optional: Set database environment variables (using your settings if needed)
export TEST_DB_HOST=database
export TEST_DB_USER=wordpress
export TEST_DB_PASS=wordpress
export TEST_DB_NAME=wordpress_test

# Then run the setup script
php bin/setup-plugin-tests.php
```

### Lando Environment

If you're using Lando, set your `.env.testing` file with the correct Lando settings:

```
# Set SSH_COMMAND for Lando
SSH_COMMAND=lando ssh

# Database settings for Lando
WP_TESTS_DB_HOST=database
WP_TESTS_DB_USER=wordpress
WP_TESTS_DB_PASSWORD=wordpress
WP_TESTS_DB_NAME=wordpress_test
```

Then run the setup script from your plugin directory on your local machine:

```bash
# First sync your files if working outside the WordPress directory
composer sync:wp

# Then run the setup script
php bin/setup-plugin-tests.php
```

The script will use `lando ssh` automatically for database operations while performing filesystem operations locally.

## Configuring Composer

Here's a sample `composer.json` configuration for a WordPress plugin with testing tools:

```json
{
    "name": "your-vendor/your-plugin",
    "description": "Your WordPress Plugin Description",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "10up/wp_mock": "^0.4",
        "brain/monkey": "^2.6",
        "mockery/mockery": "^1.4",
        "yoast/phpunit-polyfills": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "YourVendor\\YourPlugin\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "YourVendor\\YourPlugin\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite=unit",
        "test:integration": "phpunit --testsuite=integration"
    }
}
```

## Included Composer Configuration

The framework includes a comprehensive `composer.json` with a variety of development tools to support different testing approaches and code quality standards:

### Testing Libraries

- **PHPUnit** (`phpunit/phpunit`): The core testing framework
- **WP_Mock** (`10up/wp_mock`): WordPress function mocking library
- **Brain\Monkey** (`brain/monkey`): WordPress hooks and functions mocking
- **Mockery** (`mockery/mockery`): General-purpose mocking framework
- **PHPUnit Polyfills** (`yoast/phpunit-polyfills`): Compatibility layer for different PHPUnit versions

### Code Quality Tools

- **PHP_CodeSniffer** (`squizlabs/php_codesniffer`): Code style and standards checking
- **WordPress Coding Standards** (`wp-coding-standards/wpcs`): WordPress-specific coding standards
- **PHP Compatibility** (`phpcompatibility/phpcompatibility-wp`): PHP version compatibility checking
- **PHP CS Fixer** (`friendsofphp/php-cs-fixer`): Automatically fix code style issues
- **PHPStan** (`phpstan/phpstan`): Static analysis tool to find bugs
- **PHPStan WordPress** (`szepeviktor/phpstan-wordpress`): WordPress-specific static analysis rules

### Composer Scripts

The framework also includes convenient scripts for common tasks:

```bash
# Run all tests
composer test

# Run specific test suites
composer test:unit         # Unit tests only
composer test:wp-mock      # WP_Mock tests only
composer test:integration  # Integration tests only

# Code quality tools
composer phpcs            # Check code style
composer phpcbf           # Fix code style issues
composer analyze          # Run static analysis

# Framework development
composer sync:wp          # Sync framework to WordPress
```

These tools and scripts provide a comprehensive testing and quality assurance environment for WordPress plugin development.

## Daily Usage

### Verifying Installation

To verify that the installation was successful, you can run the test commands defined in the framework:

```bash
# From your plugin directory
# Run all tests
composer test

# Run specific test suites
composer test:unit        # Unit tests only
composer test:wp-mock     # WP_Mock tests only
composer test:integration # Integration tests only
```

You can also run PHPUnit directly:

```bash
# From your plugin directory
./vendor/bin/phpunit -c config/phpunit.xml.dist
```

### Reinstalling or Updating

If you need to reinstall or update the framework, follow these steps:

### Development Workflow

If you're developing the framework itself, you'll need to sync your development folder to a WordPress installation for testing. This keeps your development files separate from WordPress for clarity and better organization.

```bash
# Set folder paths
DEV_FOLDER=~/sites/phpunit-testing                         # Development folder (outside WordPress)
WP_PLUGIN_FOLDER=~/sites/wordpress/wp-content/plugins/gl-phpunit-testing-framework  # WordPress plugin folder

# Create/update .env.testing in the development folder
cp $DEV_FOLDER/.env.sample.testing $DEV_FOLDER/.env.testing
# Edit .env.testing to set your specific paths:
#
# FILESYSTEM_WP_ROOT=/home/username/sites/wordpress        # Path to WordPress root (no trailing slash)
# FRAMEWORK_DEST_NAME=gl-phpunit-testing-framework         # Plugin folder name in WordPress

# Run the sync script from the development folder
cd $DEV_FOLDER
php bin/sync-to-wp.php                                     # Syncs to WordPress plugin folder

# Now switch to the WordPress plugin folder for testing
cd $WP_PLUGIN_FOLDER
# The sync script copies files to FILESYSTEM_WP_ROOT/wp-content/plugins/FRAMEWORK_DEST_NAME

# After syncing, you'll need a .env.testing file in the WordPress plugin folder too
# (The sync script doesn't copy .env.testing to maintain separate configurations)
cp $WP_PLUGIN_FOLDER/.env.sample.testing $WP_PLUGIN_FOLDER/.env.testing
# Edit this file to set appropriate paths for the WordPress environment

# Clean and update dependencies
rm -rf vendor/ composer.lock .phpunit.result.cache
composer update

# Run the setup script inside SSH
lando ssh  # or ssh for non-Lando environments

## Within SSH
# Navigate to your plugin directory (if not already there)
# Note: Inside Lando, /app is the WordPress root
cd /app/wp-content/plugins/gl-phpunit-testing-framework  # Path inside container

# Run the setup script
php bin/setup-plugin-tests.php
```

## Troubleshooting

### Common Issues

#### PHPUnit Not Found

If you get a "command not found" error when running PHPUnit:

```bash
# Run PHPUnit using vendor/bin path
vendor/bin/phpunit
```

#### Memory Limit Errors

If you encounter memory limit errors:

```bash
# Increase PHP memory limit
php -d memory_limit=512M vendor/bin/phpunit
```

#### WordPress Test Library Not Found

If the WordPress test library can't be found:

```bash
# Set the WP_TESTS_DIR environment variable
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
```

For more detailed information on using these testing tools, refer to:
- [PHPUnit Testing Tutorial](phpunit-testing-tutorial.md)
- [Mocking Strategies](phpunit-testing-tutorial.md#mocking-strategies)
- [Determining the Right Test Type](phpunit-testing-tutorial.md#determining-the-right-test-type)
