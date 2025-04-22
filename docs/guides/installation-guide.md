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

- PHP 7.4 or higher
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

## Setting Up WordPress Test Library

For integration tests that interact with a real WordPress installation, you'll need the WordPress test library:

```bash
# Using WP-CLI to scaffold the test environment
wp scaffold plugin-tests your-plugin-name
```

This command creates:
- A `tests` directory with bootstrap files
- A `bin/install-wp-tests.sh` script to install the WordPress test library

Run the installation script:

```bash
# Install WordPress test library
bin/install-wp-tests.sh wordpress_test root password localhost latest
```

Replace:
- `wordpress_test` with your test database name
- `root` with your database username
- `password` with your database password
- `localhost` with your database host
- `latest` with a specific WordPress version (optional)

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
        "phpunit/phpunit": "^9.0",
        "10up/wp_mock": "^0.4",
        "brain/monkey": "^2.6",
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
