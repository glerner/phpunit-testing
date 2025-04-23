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

PHPUnit is the core testing framework we'll use. We recommend installing it via Composer:

```bash
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

## Setting Up WordPress Test Library

For integration tests that interact with a real WordPress installation, you'll need the WordPress test library.

> **Note:** While WordPress CLI's `wp scaffold plugin-tests` command generates a shell script (`install-wp-tests.sh`), we recommend using our PHP script instead, which provides better environment detection, error handling, and compatibility with different setups including Lando.

This framework provides a PHP script to set up the WordPress test environment:

```bash
# Run the setup script provided by this framework
php bin/setup-plugin-tests.php
```

This script will:
- Download the WordPress testing suite from the official WordPress develop repository
- Configure the test database
- Set up necessary configuration files
- Create test directories if they don't exist

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

If you're using Lando, the script will automatically detect your Lando environment and use the correct database settings. You can run the script inside Lando SSH:

```bash
lando ssh -c 'cd /app/wp-content/plugins/your-plugin && php bin/setup-plugin-tests.php'
```

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

For a more comprehensive configuration, refer to the `composer.json` included in this repository, which contains additional development tools and configuration options.

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

```bash
# Sync the framework to WordPress (if developing the framework itself)
cd ~/sites/phpunit-testing
php bin/sync-to-wp.php

# Set the plugin folder path
PLUGIN_FOLDER=~/sites/wordpress/wp-content/plugins/gl-phpunit-testing-framework
# or for your own plugin
# PLUGIN_FOLDER=~/sites/wordpress/wp-content/plugins/your-plugin

cd $PLUGIN_FOLDER

# Clean and update dependencies
rm -rf vendor/ composer.lock .phpunit.result.cache
composer update

# Run the setup script inside SSH
lando ssh  # or ssh for non-Lando environments

## Within SSH
APP_FOLDER=/app/wp-content/plugins/gl-phpunit-testing-framework
cd $APP_FOLDER && php $APP_FOLDER/bin/setup-plugin-tests.php
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
