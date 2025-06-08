# Composer Cleanup and Rebuild Guide

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
```bash
# 1. Clean and update plugin directory
cd ~/sites/your-plugin-directory && \
rm -rf vendor/ composer.lock .phpunit.result.cache && \
composer clear-cache --no-interaction && \
composer update --no-interaction && \
composer check-platform-reqs && \
composer dump-autoload -o

# 2. Sync plugin to WordPress directory
php bin/sync-to-wp.php

# 3. Update WordPress directory dependencies
cd ~/sites/wordpress && \
composer update && \
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
- The WordPress test environment (Lando) may use a different PHP version than your local environment
- Never commit environment-specific `composer.lock` files

### Sync-to-WP Behavior
- `sync-to-wp.sh` only copies `composer.json` (not `composer.lock`)
- This is intentional and correct behavior
- Each environment should generate its own `composer.lock`
- This ensures compatibility with the specific PHP version of each environment

### Verifying Your Environment
After a rebuild, verify your setup:

```bash
# Check PHPUnit version
composer show phpunit/phpunit

# Check PHP version
php -v

# Run tests
composer test           # All tests
composer test:unit      # Only unit tests
composer test:integration  # Only integration tests
```

## Troubleshooting

### Common Issues

#### Class Not Found Errors
1. Try `composer dump-autoload -o` first
2. If that doesn't work, perform a full rebuild

#### Version Conflicts
```bash
composer why package/name  # See why a package is installed
composer why-not package/name  # See why a version can't be installed
```

#### Lando-Specific Issues
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
