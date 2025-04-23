# GL WordPress PHPUnit Testing Framework - Troubleshooting Guide

This guide provides solutions for common issues when setting up and using the GL WordPress PHPUnit Testing Framework.

## Table of Contents

1. [Development Workflow](#development-workflow)
2. [Installation and Setup](#installation-and-setup)
3. [Removing Old Installations](#removing-old-installations)
4. [Database Troubleshooting](#database-troubleshooting)
5. [Common Errors and Solutions](#common-errors-and-solutions)

## Development Workflow

### Developing the Testing Framework

If you're developing the GL WordPress PHPUnit Testing Framework itself (not just using it in a plugin), follow these steps:

1. Make changes to the framework code in your development environment
2. Run the merge script to sync changes to your WordPress environment:

```bash
# From the framework development directory
php bin/sync-to-wp.php /path/to/wordpress/wp-content/plugins/gl-phpunit-testing-framework
```

### Using the Framework in a Plugin

When using the framework in a plugin:

1. Install the framework (see [Installation and Setup](#installation-and-setup))
2. Create your test files in the appropriate directories
3. Run tests using the provided commands

## Installation and Setup

The installation process involves more than just copying files. For complete installation instructions, please refer to:

- [PHPUnit Testing Tutorial](../phpunit-testing-tutorial.md) - Comprehensive setup guide
- [Installation Guide](../installation-guide.md) - Detailed installation steps

In brief, the process involves:

1. Install the framework via Composer or as a Git submodule
2. Configure your plugin's test environment
3. Run the setup script to prepare the WordPress test suite:

```bash
# From your WordPress directory
cd wp-content/plugins/your-plugin
php bin/setup-plugin-tests.php
```

The setup script will:
- Download WordPress testing suite
- Configure the test database
- Set up necessary configuration files
- Create test directories if they don't exist

For detailed instructions on verifying your installation and running tests, please refer to the [Installation Guide](../installation-guide.md#daily-usage).

## Redoing the Testing Framework

To verify that the PHPUnit testing framework installation process works properly, you need to delete any old installation. You can safely delete the following in your WordPress installation:

### Remove Test Directories

```bash
# Remove WordPress PHPUnit test directory
# Use your path to WordPress, e.g. ~/sites/wordpress
rm -rf ~/sites/wordpress/wordpress-phpunit

# Remove plugin test directory (if it exists)
rm -rf ~/sites/wordpress/wp-content/plugins/your-plugin/tests
```

### Drop Test Database

Note: no space between -p and the password

```bash
# Using Lando
lando ssh -c 'mysql -h database -u wordpress -pwordpress -e "DROP DATABASE IF EXISTS wordpress_test; CREATE DATABASE wordpress_test;"'

# Or if you are not using Lando, from SSH:

mysql -h database -u wordpress -pwordpress -e "DROP DATABASE IF EXISTS wordpress_test; CREATE DATABASE wordpress_test;"'
```

### Clean Configuration Files

```bash
# Remove test configuration file
rm -f ~/sites/wordpress/wp-content/plugins/your-plugin/wp-tests-config.php
```

These deletions won't affect your main WordPress installation or database, only the test environment.

### Reinstall the Framework

After removing these directories, you can run the setup script again to verify that it correctly:

1. Downloads the WordPress PHPUnit develop repository
2. Sets up the test database
3. Creates the necessary configuration files

For the complete process to reinstall the framework, please refer to the [Installation Guide](../installation-guide.md#reinstalling-or-updating).

Following these steps will give you a fresh installation of the testing framework with the database connection fixes we implemented.

## Database Troubleshooting

Database connection issues are common when setting up the testing framework.

### Verify Lando Database Configuration

If using Lando, ensure your `.lando.yml` file has a properly configured database service:

```yaml
services:
  database:
    type: mysql:8.0
    healthcheck: mysql -uroot --silent --execute "SHOW DATABASES;"
```

Also check that your Lando environment variables are correctly set:

```yaml
services:
  appserver:
    overrides:
      environment:
        TEST_DB_HOST: database
        TEST_DB_USER: wordpress
        TEST_DB_PASS: wordpress
        TEST_DB_NAME: wordpress_test
```

### Test Database Connection

You can test the database connection directly:

```bash
# Using Lando
lando ssh -c 'mysql -h database -u wordpress -pwordpress -e "SELECT 1"'

# Not using Lando (or in Lando SSH)
mysql -h database -u wordpress -pwordpress -e "SELECT 1"
```

If this fails, your database configuration may be incorrect.

### Common Database Issues

1. **Wrong hostname**: In Lando, use `database` as the hostname, not `localhost` (unless changed in .lando.yml)
2. **Incorrect credentials**: Verify username and password
3. **Missing test database**: Ensure `wordpress_test` database exists, created by phpunit-testing/bin/setup-plugin-tests.php
4. **Permission issues**: Ensure the user has privileges on the test database

## Dependency Management

### PHPUnit Version Constraints

As of April 2025, the GL WordPress PHPUnit Testing Framework uses PHPUnit 9.x rather than PHPUnit 10.x due to compatibility requirements with other dependencies, particularly the Yoast PHPUnit Polyfills package.

**Issue**: If you try to use PHPUnit 10.x, you'll encounter this error:

```
Your requirements could not be resolved to an installable set of packages.

  Problem 1
    - Root composer.json requires yoast/phpunit-polyfills ^1.0 -> satisfiable by yoast/phpunit-polyfills[1.0.0, ..., 1.1.4].
    - yoast/phpunit-polyfills[1.0.0, ..., 1.1.4] require phpunit/phpunit ^4.8.36 || ^5.7.21 || ^6.0 || ^7.0 || ^8.0 || ^9.0 -> found phpunit/phpunit[4.8.36, 5.7.21, ..., 9.6.22] but it conflicts with your root composer.json require (^10.0).
```

**Solution**: Use PHPUnit 9.x in your `composer.json` file:

```json
"require-dev": {
    "phpunit/phpunit": "^9.0",
    "yoast/phpunit-polyfills": "^1.0"
}
```

### Other Dependency Conflicts

When working with WordPress testing tools, you may encounter other dependency conflicts due to the WordPress ecosystem's compatibility requirements with older PHP versions and libraries.

**Common issues**:
- Conflicts between WordPress core requirements and modern PHP packages
- Incompatibilities between testing libraries
- Version constraints in transitive dependencies

**Solution**: Check the compatibility matrix of all your dependencies and adjust version constraints accordingly.

## Common Errors and Solutions

### "Cannot connect to MySQL server"

**Symptoms**:
```
Error: Cannot connect to MySQL server. Full error output above.
```

**Solutions**:
- Verify database hostname (default is `database` in Lando)
- Check database credentials
- Ensure MySQL service is running
- Check for network/firewall issues

### "WordPress develop repository clone failed"

**Symptoms**:
```
Error: Failed to clone WordPress develop repository.
```

**Solutions**:
- Check internet connectivity
- Verify Git is installed
- Try with a different Git repository URL
- Check disk space

### "wp-tests-config.php not found"

**Symptoms**:
```
Warning: wp-tests-config.php not found
```

**Solutions**:
- Run the setup script again `phpunit-testing/bin/setup-plugin-tests.php`
- Check file permissions
- Manually create the file based on the template

### PHPUnit Not Found

**Symptoms**:
```
Command not found: phpunit
```

**Solutions**:
- Install PHPUnit via Composer
- Use the full path to PHPUnit: `./vendor/bin/phpunit`
- Check Composer installation

---

For additional help, please refer to the other documentation files or open an issue on the GitHub repository.
