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

mysql -h database -u wordpress -pwordpress -e "DROP DATABASE IF EXISTS wordpress_test; CREATE DATABASE wordpress_test;"
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

### "bash: /usr/bin/composer: No such file or directory"

**Symptoms**:
When running `composer update` or other Composer commands, you get an error like:
```
bash: /usr/bin/composer: No such file or directory
```

**Cause**:
This error occurs when your shell session is looking for Composer in `/usr/bin/composer`, but Composer is installed in a different location (commonly `/usr/local/bin/composer`). This can happen when:

1. Your terminal session has an outdated PATH environment variable
2. Composer was installed after you opened your terminal session
3. Your `.bashrc` or `.bash_profile` was updated but not reloaded

**Solutions**:

1. **Reload your shell configuration**:
   ```bash
   source ~/.bashrc
   # or
   source ~/.bash_profile
   ```

2. **Check where Composer is actually installed**:
   ```bash
   which composer
   ```

3. **Create a symbolic link** (requires admin privileges):
   ```bash
   sudo ln -s $(which composer) /usr/bin/composer
   ```

4. **Start a new terminal session** to ensure it loads the latest environment variables

### "Failed opening required '/app/wp-includes/class-wp-phpmailer.php'"

**Symptoms**:
When running the `setup-plugin-tests.php` script, you encounter errors like:
```
PHP Fatal error: Uncaught Error: Failed opening required '/app/wp-includes/class-wp-phpmailer.php'
```

**Important Note**: This error reflects a version mismatch between the WordPress test framework and your WordPress installation. Around WordPress 5.5 (2019), WordPress changed from using `class-wp-phpmailer.php` to `class-phpmailer.php`, but the test framework still looks for the old filename.

**Cause**:
This error occurs for several possible reasons:

1. Version mismatch between WordPress core and the WordPress test framework
2. The WordPress installation path is incorrect in the setup script
3. The WordPress core files are not where the script expects them to be
4. In Lando environments, the path mapping between host and container is not correctly configured

**Solutions** (in order of preference):

1. **Use a compatible version of the WordPress test framework**:
   The best solution is to use a version of the test framework that matches your WordPress version. The setup script should be updated to download the appropriate version.

2. **Patch the test framework after download**:
   Modify the setup script to patch the test framework's mock-mailer.php file after downloading it, updating the require path to use the current filename.

3. **Create a symbolic link as a temporary workaround**:
   ```bash
   # Inside Lando SSH
   cd /app/wp-includes
   ln -sf class-phpmailer.php class-wp-phpmailer.php
   ```
   Note: This is a temporary workaround, not a proper solution for production environments.

2. **Verify WordPress core files location**:
   ```bash
   # Inside Lando SSH
   ls -la /app/wp-includes/
   ```
   If this directory doesn't exist or is empty, WordPress core files are not where expected.

2. **Check WordPress path in setup script**:
   The script may be using an incorrect path to WordPress. Verify that the WordPress root path is correctly detected.

3. **Manually specify WordPress path**:
   You can edit the `.env.testing` file to explicitly set the WordPress path:
   ```
   WP_ROOT=/correct/path/to/wordpress
   ```

4. **For Lando environments**:
   Ensure your `.lando.yml` file correctly sets the webroot. In Lando, the webroot setting determines what gets mapped to `/app` in the container:
   ```yaml
   # In .lando.yml
   config:
     webroot: .
   ```
   The above example maps the current directory to `/app` in the container.

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

### "Empty Database After Lando Operations"

**Symptoms**:
After running `lando rebuild` or `lando poweroff` followed by `lando start`, your WordPress database is empty and phpMyAdmin shows no tables. MySQL queries return errors like:
```
ERROR 1146 (42S02): Table 'wordpress.wp_users' doesn't exist
```

**Cause**:
Both `lando rebuild` and `lando poweroff` followed by `lando start` recreate the database containers but don't automatically restore the database content. This is because:

1. Lando uses Docker volumes for databases, which may be removed during these operations
2. The database initialization scripts only run when the container is first created
3. No automatic backup/restore mechanism is built into these commands

**Solutions**:

1. **Import your database backup using WP-CLI**:
   ```bash
   # Replace backup-file.sql with your actual backup file
   lando wp db import --user=wordpress backup-file.sql
   ```

2. **Import using direct MySQL command** (inside Lando SSH):
   ```bash
   mysql -h database -u wordpress -pwordpress wordpress < backup-file.sql
   ```
   Note: There is no space between `-p` and the password.

3. **Restore from a Lando database backup** (if you created one):
   ```bash
   lando db-import lando-database-backup.sql.gz
   ```

4. **Recreate the database from scratch**:
   If you don't have a backup, you may need to reinstall WordPress:
   ```bash
   lando wp core install --url=https://yoursite.lndo.site --title="Your Site" --admin_user=admin --admin_password=password --admin_email=your@email.com
   ```
5. **Verify have WordPress database tables**:
   ```bash
   lando mysql -h database -u wordpress -pwordpress -e "USE wordpress; SHOW TABLES;"
   ```

### "foreach() argument must be of type array|object, string given"

**Symptoms**:
When running the `setup-plugin-tests.php` script, you see errors like:
```
PHP Warning: foreach() argument must be of type array|object, string given in .../setup-plugin-tests.php
WARNING: Database service not found in Lando configuration!
```

**Cause**:
This typically happens when:
1. Lando is not fully running or is in a partially started state
2. The script detects it's in a Lando environment (`$in_lando = TRUE`), but Lando services aren't accessible
3. The `lando info` command is failing to return proper configuration data

**Solutions**:

1. **Ensure Lando is running**:
   ```bash
   # Check if Lando is running
   lando list

   # If not running, start it
   lando start
   ```

2. **Run the script inside Lando SSH**:
   ```bash
   lando ssh
   cd /app/wp-content/plugins/your-plugin
   php bin/setup-plugin-tests.php
   ```

3. **Manually set database configuration**:
   If Lando detection is failing, you can manually configure the database settings in your `.env.testing` file:
   ```
   WP_TESTS_DB_NAME=wordpress_test
   WP_TESTS_DB_USER=wordpress
   WP_TESTS_DB_PASSWORD=wordpress
   WP_TESTS_DB_HOST=database
   ```

### Path Issues in `.env.testing` When Running `setup-plugin-tests.php`

**Symptoms**:
When running the `setup-plugin-tests.php` script, you encounter errors like:
```
PHP Warning: mkdir(): Permission denied in .../setup-plugin-tests.php
Error: Failed to create tests directory: /app/wp-content/plugins/wordpress-develop/tests/phpunit
```

**Cause**:
The script is trying to use paths from your `.env.testing` file that may be valid in one environment (e.g., inside a container) but invalid in your current environment. For example:

1. Paths starting with `/app/` are typically valid inside containers (Lando, Docker) but not on local machines
2. The script is running in a different environment than what the paths in `.env.testing` are configured for

**Solutions**:

1. **Let the script detect paths automatically**:
   If you're unsure about the correct paths, remove the `WP_TESTS_DIR` and `WP_ROOT` entries from your `.env.testing` file and let the script try to detect them automatically.

2. **Run the script in the correct environment**:
   If your `.env.testing` uses container paths (like `/app/`), run the script inside that container:
   ```bash
   # For Lando
   lando ssh -c "cd /app/wp-content/plugins/your-plugin && php bin/setup-plugin-tests.php"
   ```

3. **Update `.env.testing` with correct paths for your environment**:
   Edit the `.env.testing` file to use paths appropriate for where you're running the script:

   For local machine:
   ```
   WP_TESTS_DIR=/home/username/sites/wordpress/wp-content/plugins/wordpress-develop/tests/phpunit
   WP_ROOT=/home/username/sites/wordpress
   ```

   For container (e.g., Lando):
   ```
   WP_TESTS_DIR=/app/wp-content/plugins/wordpress-develop/tests/phpunit
   WP_ROOT=/app
   ```

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
