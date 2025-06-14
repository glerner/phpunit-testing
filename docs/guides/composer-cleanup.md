# Composer Cleanup and Rebuild Guide

## Table of Contents
- [Quick Reference](#quick-reference)
  - [Basic Refresh](#basic-refresh-most-common)
  - [Full Clean Rebuild](#full-clean-rebuild-when-needed)
- [Detailed Instructions](#detailed-instructions)
  - [When to Perform a Full Rebuild](#when-to-perform-a-full-rebuild)
  - [Understanding composer.lock](#understanding-composerlock)
  - [Sync-to-WP Behavior](#sync-to-wp-behavior)
  - [Verifying Your Environment](#verifying-your-environment)
- [Troubleshooting](#troubleshooting)
  - [Diagnosing Issues](#diagnosing-issues)
  - [Common Issues](#common-issues)
  - [Lando-Specific Issues](#lando-specific-issues)
  - [Advanced Cleanup](#advanced-cleanup-commands)

## Introduction

This guide provides comprehensive instructions for cleaning up and rebuilding your Composer dependencies, which is particularly useful when:

This guide provides comprehensive instructions for cleaning up and rebuilding your Composer dependencies, which is particularly useful when:
- Changing dependencies in `composer.json`
- Debugging Composer-related issues
- Experiencing inconsistent behavior from vendor packages
- Switching between different PHP versions
- After major dependency updates

## Quick Reference

### Basic Refresh (Most Common)
```bash
# In your plugin directory
composer update --no-interaction
composer dump-autoload -o
```

### Full Clean Rebuild (When Needed)

Use this comprehensive process when you need to completely reset your development environment, such as after major dependency changes or when experiencing persistent issues.

#### Standard Workflow (For Most Users)

```bash
# 1. Clean and update your plugin directory
cd /path/to/your/plugin-directory
rm -rf vendor/ composer.lock
composer clear-cache
composer install --no-cache --optimize-autoloader --prefer-dist
composer dump-autoload

# 2. Update the testing framework (if using as a submodule)
git submodule update --init --recursive

# 3. Sync to WordPress (if using a sync script)
php bin/sync-to-wp.php
```

#### Development Workflow (For Framework Developers)

This workflow is only for developers working on the PHPUnit Testing Framework itself:

```bash
# 1. Clean and update framework source
cd /path/to/phpunit-testing
rm -rf vendor/ composer.lock
composer clear-cache
composer install --no-cache --optimize-autoloader --prefer-dist
composer dump-autoload

# 2. In your plugin that uses the framework:
cd /path/to/your/plugin
rm -rf vendor/ composer.lock
composer clear-cache
composer install --no-cache --optimize-autoloader --prefer-dist

# 3. Update the framework in your plugin (choose one method):
# Option A: If using git submodule
git submodule update --remote
# Option B: If using Composer
composer update your-vendor/phpunit-testing

# 4. Sync to WordPress (if applicable)
php bin/sync-to-wp.php
```

### Container Management (For Lando/Docker Users)

If you're using Lando or Docker, you may need to rebuild your containers:

```bash
# Rebuild Lando environment
cd /path/to/your/wordpress
lando poweroff
rm -rf .lando.local.yml .lando.json .lando/
lando --clear
lando rebuild -y

# If rebuild fails, try with a more aggressive cleanup
lando rebuild --clean -y

# Clean up Docker resources
lando update
docker system prune -f
docker image prune -f
docker builder prune -f

# Verify Lando is running
lando info
```

### When to Use a Full Rebuild

Perform a full rebuild when:
- Major dependency versions have changed
- After switching PHP versions
- When experiencing persistent "class not found" or autoloading issues
- After significant changes to the testing framework
- When Lando or Docker containers are behaving unexpectedly

### Verifying Your Environment

After running the rebuild, verify everything is working:

```bash
# Check PHP version
lando php -v

# Verify PHPUnit version
cd /app/wp-content/plugins/your-plugin
./vendor/bin/phpunit --version

# Run a single test file to verify
./vendor/bin/phpunit -c tests/bootstrap/phpunit-unit.xml.dist tests/Unit/Example_Test.php
```

## WordPress Directory Update (Alternative)

If you only need to update the WordPress directory dependencies:

```bash
cd ~/sites/wordpress
composer update
composer dump-autoload -o
```

## Detailed Instructions

### When to Perform a Full Rebuild
Perform a full rebuild when you:
- Update PHP versions
- Change major dependencies
- See "class not found" errors
- Experience inconsistent behavior between environments
- Are setting up a fresh development environment

### Understanding `composer.lock`
- `composer.lock` is environment-specific
- It should be generated fresh in each environment
- The WordPress test environment (Lando, Local, XAMPP) may use a different PHP version than your local environment (Linux Mint, Windows, OS/X)
- Never commit environment-specific `composer.lock` files to Git

### Sync-to-WP Behavior
- `bin/sync-to-wp.php` only copies `composer.json` (not `composer.lock`)
- This is intentional and correct behavior
- Each environment should generate its own `composer.lock`
- This ensures compatibility with the specific PHP version of each environment

### Verifying Your Environment

#### From Your Local Machine (Host OS)
These commands check your host system's environment:

```bash
# Check Composer version
composer --version

# Check Docker version
docker --version

# Check Lando version (if using Lando)
lando version
```

#### From Inside Development Container
Access your development container first (e.g., `lando ssh` or `docker-compose exec app bash`), then run:

```bash
# Check PHP version
php -v

# Check PHP extensions
php -m

# Check Composer's platform requirements
composer check-platform-reqs

# Check PHPUnit version
composer show phpunit/phpunit

# Check why a specific PHP version won't work
# Correct syntax: composer why-not php 8.0
composer why-not php 8.0

# Run tests
composer test           # All tests
composer test:unit      # Only unit tests
composer test:integration  # Only integration tests
```

## Troubleshooting

### Diagnosing Issues

Before performing a full cleanup, try these diagnostic commands:

```bash
# Validate composer.json
composer validate --strict

# Check platform requirements
composer check-platform-reqs

# See what would be updated
composer update --dry-run

# Check for dependency issues
composer why package/name         # Why a package is installed
composer why-not package version  # Why a version can't be installed (note: no slash between package and version)

# Check for autoloading issues
composer dump-autoload -v
```

### Common Issues

#### Class Not Found Errors
1. Try `composer dump-autoload -o` first
2. If that doesn't work, perform a full rebuild

#### Version Conflicts
```bash
composer why package/name  # See why a package is installed
composer why-not package/name  # See why a version can't be installed
```

### Advanced Cleanup Commands

For stubborn issues, try these advanced cleanup steps:

```bash
# Remove all Composer caches
rm -rf ~/.cache/composer/*
rm -rf ~/.composer/cache/*

# Reset file permissions (Lando)
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Clear PHP opcache if enabled
php -r 'opcache_reset();'

# Check for file ownership issues
ls -la vendor/ | grep root  # Look for files owned by root
```

### After System Updates

If you've recently updated your system (Linux kernel, Docker, or other system components), you may need to perform a complete environment rebuild. See the [Rebuilding After System Updates](../rebuilding-after-system-updates.md) guide for detailed instructions.

### Lando-Specific Issues
```bash
# Rebuild Lando completely
cd ~/sites/wordpress && \
rm -rf vendor/ composer.lock .phpunit.result.cache && \
lando destroy -y && \
lando start && \
lando rebuild -y
```

Remember to back up your database before running destructive commands:

```bash
cd ~/sites/wordpress && \
lando db-export database.sql
```
