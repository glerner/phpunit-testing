# GL PHPUnit and Testing Tools Installation Guide

This guide provides step-by-step instructions for installing PHPUnit and related testing tools for WordPress plugin development.

## Table of Contents

- [Included Tools](#included-tools)
- [Prerequisites](#prerequisites)
- [Development Workflow](#development-workflow)
- [Installing PHPUnit](#installing-phpunit)
- [Installing WP_Mock](#installing-wp_mock)
- [Installing Brain\Monkey](#installing-brainmonkey)
- [Setting Up WordPress Test Library](#setting-up-wordpress-test-library)
- [Configuring Composer](#configuring-composer)
- [Troubleshooting](#troubleshooting)

## Included Tools

The framework includes a comprehensive set of development tools to support different testing approaches and code quality standards:

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

## Prerequisites

### Composer Configuration

To prevent class loading conflicts between your project and the testing framework, add this to your project's `composer.json`:

```json
"autoload-dev": {
    "exclude-from-classmap": [
        "**/tests/gl-phpunit-test-framework/vendor/"
    ]
}
```

This ensures the testing framework's dependencies won't conflict with your project's dependencies.

### System Requirements

Before installing the testing tools, ensure you have:

- PHP 8.0 or higher (for WordPress, should use PHP 8.0+)
- Composer installed and available in your path
- Git (for cloning repositories)
- MySQL/MariaDB (for integration tests)

## Development Workflow

### Recommended: Develop Outside WordPress

We strongly recommend developing your plugin **outside** your WordPress installation for several reasons:

1. **Better Git Management**: Keeps WordPress core files separate from your plugin repository
2. **Safer Development**: Prevents accidental deletion of your plugin code when rebuilding WordPress
3. **Cleaner Environment**: Provides better separation of concerns

With this approach, you'll:
1. Develop your plugin in a separate directory
2. Use `composer sync:wp` to sync changes to your WordPress installation
3. Run tests against your WordPress installation

### Alternative: Develop Inside WordPress

Alternatively, you can develop directly in your WordPress installation's plugins directory, though this is not recommended for the reasons mentioned above.

## Installing This GL WordPress PHPUnit Testing Framework

### Option 1: Git Submodule (Recommended for Contributors)

> There must be a local .git repository already (at minimum, run `git init`). See `docs/git-github-setup-guide.md` for proper setup of your local Git repository.

```bash
# Always from your plugin's root directory
git submodule add https://github.com/glerner/phpunit-testing.git tests/gl-phpunit-test-framework
```

#### Update to the latest PHPUnit Testing Framework

To pull updates from the PHPUnit testing framework submodule and use the latest version:

```bash
# Update the submodule to the latest commit from upstream
# Always from your plugin's root directory
git submodule update --remote --merge

# Stage the updated submodule pointer in your main repo
git add tests/gl-phpunit-test-framework

#### Prevent Accidental Submodule Edits

To avoid accidentally editing files in the submodule directory, you can set up a pre-commit hook.
Add (or append to existing) the following to your .git/hooks/pre-commit file:

```bash
# From your plugin's root directory
cat > .git/hooks/pre-commit << 'EOL'
#!/bin/bash

# Check if any file in the submodule is modified
if git diff --cached --name-only | grep -q '^tests/gl-phpunit-test-framework/'; then
    echo "Error: Direct modifications to submodule files detected!"
    echo "Please make changes in the main repository instead."
    echo "Modified files:"
    git diff --cached --name-only | grep '^tests/gl-phpunit-test-framework/'
    exit 1
fi
EOL

# Make the hook executable
chmod +x .git/hooks/pre-commit
```

This hook will prevent you from accidentally committing changes directly to the submodule. Instead, you should:
1. Make changes in the main repository
2. Sync them to the submodule directory
3. Commit and push from the submodule directory


# Commit the update in your main repo
git commit -m "Update phpunit-testing submodule to latest upstream"
```

Alternatively, you can update manually:

```bash
cd tests/gl-phpunit-test-framework
git checkout main      # or master, or whichever branch you want
git pull origin main   # fetch latest changes
cd ../..
git add tests/gl-phpunit-test-framework
git commit -m "Update phpunit-testing submodule"
```

Always commit the submodule pointer change in your main repo after updating.

You should make a dedicated git commit in your main project specifically for the PHPUnit Framework submodule update. This is considered best practice for clarity and history tracking.

- Submodule updates are independent of your main project’s code/content changes.
- Keeping the update in a separate commit makes it easy to see when and why the test framework was updated.
- This helps collaborators and reviewers quickly understand the change and roll back if needed.


### Option 2: Composer Package (Recommended for Standard Usage)

```bash
# Always from your plugin's root directory
composer require glerner/phpunit-testing --dev
```
When you run the setup script later, your composer.json and the installed dependencies will be copied to your WordPress plugin directory.

The next step is to copy composer.json
> Note: if you already have a composer.json, you should carefully merge what is in this composer.json into your own.

```bash
cp tests/gl-phpunit-test-framework/composer.json tests/composer.json
```

#### Keeping the PHPUnit Testing Framework Up to Date

To update the phpunit-testing Composer package to the latest version:

```bash
# From your plugin's root directory
cd tests/gl-phpunit-test-framework/
composer update
```

This will update the package and its dependencies to the latest versions allowed by your composer.json constraints.

After updating, you should commit the changes to your lock file and any updated dependencies:

```bash
# From your plugin's root directory
git add composer.lock
# (and any other files changed by the update)
git commit -m "Update phpunit-testing Composer package to latest version"
```

It is best practice to make this a dedicated commit, separate from your application code changes, for clarity and history tracking.



## Copy the Setup script

From your plugin root folder:

```bash
mkdir tests/bin
cp tests/gl-phpunit-test-framework/bin/setup-plugin-tests.php tests/bin/setup-plugin-tests.php
cp tests/gl-phpunit-test-framework/config/* ./config/
```
- Your most frequently used scripts will be in your tests/bin folder.
- Your tests will be in folders of your tests/ folder.
- You won't often need anything in your tests/gl-phpunit-test-framework folder.

## Installing PHPUnit

PHPUnit is installed automatically by this package.

PHPUnit is the core testing framework we'll use. We recommend installing it via Composer in your plugin development directory (outside WordPress) as this framework also includes tools that will work before testing in WordPress, such as static code quality tests.

This package requires PHPUnit 11.x due to compatibility requirements with other dependencies.

If you wanted to install it manually, you would:

```bash
# Navigate to your plugin project directory (outside WordPress)
cd ~/sites/your-plugin-project

# Add PHPUnit as a dev dependency
# This will create or update your composer.json
composer require --dev phpunit/phpunit ^11.0
```
*Do Not* run the default PHPUnit installation instructions. This package has much better installation, instructions below.

> **Note:** The version of PHPUnit you use should be compatible with your PHP version. For PHP 7.4+ and PHP 8.0+, PHPUnit 11.x is recommended.

## Installing WP_Mock

This is installed automatically by this package.

WP_Mock is a library that provides a framework for mocking WordPress functions and classes.

If you wanted to install it manually, you would run:

```bash
# Add WP_Mock as a dev dependency
composer require --dev 10up/wp_mock ^0.4
```

## Installing Brain\Monkey

This is installed automatically by this package.

Brain\Monkey complements WP_Mock by providing additional mocking capabilities for WordPress functions.

If you wanted to install it manually, you would run:

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
# From your plugin's root directory
cd tests
composer update
```

This command will update all dependencies to their latest versions according to the version constraints in your `composer.json` file.

## .gitignore Best Practices for WordPress Plugin Testing Projects

A well-configured `.gitignore` keeps your repository clean by ignoring files and directories that are generated, environment-specific, or not needed in version control.

**Recommended entries:**

```gitignore
# Composer dependencies and lock files
/vendor/
/composer.lock
/tests/vendor/
/tests/composer.lock
/tests/composer.json

# PHPUnit and test artifacts
/build/
/dist/
/.phpunit.result.cache
/phpunit.xml
/tests/wp-tests-config.php
/tests/bootstrap/
/tests/config/
/tests/bin/
/tests/gl-phpunit-test-framework/.git
/tests/gl-phpunit-test-framework/.github
/tests/gl-phpunit-test-framework/.gitignore
/tests/gl-phpunit-test-framework/.DS_Store
/tests/gl-phpunit-test-framework/.idea/
/tests/gl-phpunit-test-framework/node_modules/
/tests/gl-phpunit-test-framework/vendor/
/tests/gl-phpunit-test-framework/build/
/tests/gl-phpunit-test-framework/coverage/
/tests/gl-phpunit-test-framework/logs/
/tests/gl-phpunit-test-framework/*.log
/tests/gl-phpunit-test-framework/*.xml
/tests/gl-phpunit-test-framework/*.lock
/tests/gl-phpunit-test-framework/*
!/tests/gl-phpunit-test-framework/.gitmodules
!/tests/gl-phpunit-test-framework

# Coverage reports
coverage/
build/
logs/
*.log

# Test environment files (keep only samples)
/tests/.env.testing
/tests/.env.local
/tests/.env
.env
.env.*
!.env.sample*
!/tests/.env.sample.testing

# OS and editor junk
.DS_Store
Thumbs.db
*.swp
*.swo
*.bak
*.tmp
*.log
*.orig
*~

# IDE files
.idea/
.vscode/
*.iml

# WordPress test library
/tmp/
```

**Notes:**
- `/tests/composer.json` is usually ignored because dependencies for the test framework are managed by the framework itself or the main plugin project.
- The submodule reference for the test framework is kept, but its internal files are ignored.
- Adjust entries as needed for your project structure.

## Creating the Environment Configuration File

The `.env.testing` file holds all environment-specific configuration for running tests. This file is required for the test framework to locate your WordPress installation, set up the test database, and know where your plugin or theme is installed for testing.

> Note: If you are testing multiple plugins and/or themes, you can use the same database, with a different WP_PHPUNIT_TABLE_PREFIX for each.

### 1. Create Your `.env.testing` File

> If you already have a `.env.testing`, review and update it to include any new settings.

Start by copying the sample file:

```bash
cp .env.sample.testing .env.testing
```

### 2. Configure Database Settings for Tests

Set these variables to match your test database:
- `WP_TESTS_DB_NAME`: Name of the test database (required by PHPUnit's WordPress testing library, should be `wordpress_test`)
- `WP_TESTS_DB_USER`: Database username
- `WP_TESTS_DB_PASSWORD`: Database password
- `WP_TESTS_DB_HOST`: Database host (e.g., `localhost` or `mariadb`)
- The user, password, host database connection parameters could be the same as for your WordPress database.
- The test database name *should be different* than the WordPress database name; you will be wiping out the wordpress_test database often when you run the tests.
- `WP_PHPUNIT_TABLE_PREFIX` Table prefix,
    - Often `yourplugin_test_` customized for each project you are testing.
    - Should have a trailing '_'

### 3. Configure Plugin/Theme Installation Paths

- `WP_ROOT`: Path to your WordPress installation (e.g., `/app` in Lando or `/home/username/sites/wordpress` on local machine)
- `FILESYSTEM_WP_ROOT`: Absolute path to your WordPress installation (no trailing slash)
  - Example: `/home/youruser/sites/wordpress`
- `FOLDER_IN_WORDPRESS`: Path within WordPress where your code will be installed
  - Default: `wp-content/plugins` (for plugins)
  - Alternatives: `wp-content/themes` (for themes), or any custom path
  - No leading or trailing slashes
- `YOUR_PLUGIN_SLUG`: Name of your plugin or theme directory (your slug)
  - Example: `my-awesome-plugin`

- `WP_TESTS_DIR`: Full path to the WordPress test library where PHPUnit will be installed
    - Will use FILESYSTEM_WP_ROOT/wp-content/plugins/wordpress-develop/tests/phpunit unless specified in .env.testing


**Your code will be installed for testing at:**
```
${FILESYSTEM_WP_ROOT}/${FOLDER_IN_WORDPRESS}/${YOUR_PLUGIN_SLUG}
```
Example:
```
/home/youruser/sites/wordpress/wp-content/plugins/my-awesome-plugin
```

### 4. Configure Command Line or SSH
```bash
# Ensure your .env.testing has the correct SSH_COMMAND setting:
# - SSH_COMMAND=none          # For local development with direct DB access
# - SSH_COMMAND=ssh           # Already in an SSH session with DB access (don't launch another SSH session)
# - SSH_COMMAND=lando ssh     # For Lando environments
# - SSH_COMMAND=yourcommand   # Whatever command you need for your specific environment
SSH_COMMAND=lando ssh
```

### 5. Additional Test Configuration (Optional)

- `TEST_ERROR_LOG`: Path to error log file
- `PHP_MEMORY_LIMIT`: Memory limit for PHP during tests
- `COVERAGE_REPORT_PATH`: Path for coverage reports
- `CLOVER_REPORT_PATH`: Path for Clover XML reports

Refer to `.env.sample.testing` for all available settings and further examples.


## For Lando environments, you'll typically use:

```
# WordPress paths within container
WP_TESTS_DIR=/app/wp-content/plugins/wordpress-develop/tests/phpunit
WP_ROOT=/app

# Database configuration
WP_TESTS_DB_NAME=wordpress_test
WP_TESTS_DB_USER=wordpress
WP_TESTS_DB_PASSWORD=wordpress
WP_TESTS_DB_HOST=database

# Test configuration
TEST_ERROR_LOG=/tmp/phpunit-testing-error.log
PHP_MEMORY_LIMIT=512M
```

## Setting Up WordPress Test Library

For integration tests that interact with a real WordPress installation, you'll need the WordPress test library.

> **Note:** While WordPress CLI's `wp scaffold plugin-tests` command generates a shell script (`install-wp-tests.sh`), we recommend using our PHP script instead, which provides better environment detection, error handling, and compatibility with different setups including Lando.

This framework provides a PHP script to set up the WordPress test environment. The script needs to access both the file system and database, which may require different environments depending on your setup.

Before running the script, make sure your `.env.testing` file has the correct `SSH_COMMAND` setting for your environment.


Then run the setup script from your plugin development directory. This will install the test framework in your WordPress plugin directory:
```bash
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

The framework uses a modular approach to organize test files and results. Each plugin maintains its own separate test files and results in the `tests/` directory:

1. **Plugin-specific test directories**: The framework adds the following structure to your plugin for your own test files:
   ```
   your-plugin-directory/
   ├── tests/                 # Your test files
   │   ├── Integration/      # Integration tests
   │   ├── Unit/             # Unit tests
   │   └── WP_Mock/          # WP_Mock tests
   └── ...
   ```

   After installation, the testing framework creates this structure in your WordPress plugin directory:

   ```
   wp-content/plugins/your-plugin-name/
   ├── tests/                       # Test directory
   │   ├── bootstrap.php           # Test initialization file
   │   ├── Integration/            # Integration tests directory
   │   │   └── SampleTest.php      # Sample integration test
   │   ├── Unit/                   # Unit tests directory
   │   │   └── SampleTest.php      # Sample unit test
   │   └── WP_Mock/                # WP_Mock tests directory
   │       └── SampleTest.php      # Sample WP_Mock test
   ├── phpunit.xml                 # PHPUnit configuration
   ├── phpcs.xml.dist              # PHP CodeSniffer configuration
   ├── phpstan.neon.dist           # PHPStan configuration
   ├── composer.json               # Composer configuration with testing tools
   └── build/                      # Test results (created by PHPUnit)
       ├── logs/                   # Test logs
       └── coverage/               # Coverage reports
   ```

2. **Multiple plugins support**: When testing multiple plugins in a single WordPress installation, each plugin maintains its own isolated test folders, test database and test results. Add this to each plugin's .env.testing file:

```
WP_PHPUNIT_TABLE_PREFIX=yourplugin_test_
```

3. **Version control**: Since test results are stored within your plugin directory, you can:
   - Include them in version control to track test coverage over time
   - Exclude them by adding `/build/` to your `.gitignore` file
   - Share test configurations while ignoring environment-specific results

4. **Customizable code quality tools**: The framework provides template configuration files that you can customize:
   - `phpcs.xml.dist` is the template for PHP CodeSniffer rules
   - Create your own `phpcs.xml` based on this template for custom rules
   - PHP CodeSniffer will use your custom `phpcs.xml` if it exists, otherwise it falls back to `phpcs.xml.dist`

### Customizing PHP CodeSniffer Configuration

To customize PHP CodeSniffer configuration, you can create a `phpcs.xml` file in your plugin's root directory by copying `phpcs.xml.dist` to `phpcs.xml`. This file will override the default `phpcs.xml.dist` configuration.

Here are the key sections you should *customize* in your `phpcs.xml` file:

```xml
<ruleset name="YOUR PLUGIN's NAME">
    <description>Coding standards for YOUR PLUGIN</description>

    <!-- Set text domain - CUSTOMIZE THIS FOR YOUR PROJECT -->
    <config name="text_domain" value="your-plugin-text-domain"/>

    <!-- Set prefixes for checking naming conventions - CUSTOMIZE THESE FOR YOUR PROJECT -->
    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array">
                <element value="Your_Plugin"/><!-- For constants and class names -->
                <element value="your_plugin"/><!-- For functions and global variables -->
                <element value="Your_Plugin\\"/><!-- For namespaces -->
            </property>
        </properties>
    </rule>
</ruleset>
```

These customizations ensure your plugin follows WordPress coding standards while using your specific text domain and prefixes for functions, classes, and namespaces.

**Note the different prefix formats required by WordPress standards:**

1. `Your_Plugin` (PascalCase with underscores)
   - Used for class names: `class Your_Plugin_Admin {}`
   - Used for constants: `const YOUR_PLUGIN_VERSION = '1.0.0';`
   - Follows WordPress class naming convention (not PSR-12)

2. `your_plugin` (snake_case)
   - Used for functions: `function your_plugin_init() {}`
   - Used for global variables: `global $your_plugin_settings;`
   - Follows WordPress function naming convention

3. `Your_Plugin\\` (PascalCase with double backslash)
   - Used for namespaces: `namespace Your_Plugin\Admin;`
   - The double backslash is needed in XML (one backslash is the escape character)
   - In your PHP code, you'll use a single backslash: `namespace Your_Plugin\Admin;`

You can also customize the PHP CodeSniffer configuration by adding your own rules or modifying the existing ones.

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

## Setting Up Your Plugin for Testing

### Standard Development Workflow

Follow these steps for normal plugin development:

1. Configure your `.env.testing` file with the correct WordPress paths, as covered above.

2. Sync your plugin files to WordPress and set up the test suite:

```bash
# First sync your plugin files to the WordPress plugins directory
composer sync:wp

# Then run the setup script from your plugin development directory
php bin/setup-plugin-tests.php
```

The setup script will:
- Install the WordPress test framework in your WordPress installation
- Configure the test database
- Install compatibility files if needed

When using Lando, the script will automatically use `lando ssh` for database operations while performing filesystem operations locally.

## Development Workflow Options

This framework supports several development workflows:

1. **Local Development** (Recommended)
   - Develop your plugin in a separate directory outside WordPress
   - Run code quality tools:
     ```bash
     # Fix coding standards issues automatically
     composer run phpcbf

     # Check for remaining coding standards issues
     composer run phpcs
     ```
   - Use `composer sync:wp` to copy files to your local WordPress installation
   - Run tests against your local WordPress database
   - Lando or Local (by Flywheel) make it easy to test in different WordPress/PHP/MySQL environments

2. **Team Development**
   - Follow the same local development workflow
   - Use version control (Git) to share code with team members
   - Each team member can test locally before committing changes

3. **Advanced: Remote/Staging Testing**
   - Configure `.env.testing` with appropriate SSH and database settings
   - Use the same setup script with remote paths
   - The framework will handle the different environment automatically

The configuration options in `.env.testing` are flexible enough to support all these workflows without additional customization.

## Configuring Composer

Here's a sample `composer.json` configuration for a WordPress plugin with testing tools:

```json
{
    "name": "your-vendor/your-plugin",
    "description": "Your WordPress Plugin Description",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=8.2"
    },
    "autoload-dev": {
        "psr-4": {
            "WP_PHPUnit_Framework\\": "tests/gl-phpunit-test-framework/src/"
        }
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "10up/wp_mock": "^0.4",
        "brain/monkey": "^2.6",
        "glerner/phpunit-testing": "dev-main"
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

## Composer Scripts

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
DEV_FOLDER=~/sites/phpunit-testing  # Development folder (outside WordPress)
WP_PLUGIN_FOLDER=~/sites/wordpress/wp-content/plugins/gl-phpunit-testing-framework  # WordPress plugin folder

# Create/update .env.testing in the development folder
# Important: If you already have a .env.testing file, modify it to include any additional settings you may need; do not simply replace it.

# Edit .env.testing to set your specific paths

# Run the sync script from the development folder
cd $DEV_FOLDER

# Run the setup script, always from your development folder
php bin/setup-plugin-tests.php

php bin/sync-to-wp.php  # Syncs to WordPress plugin folder
# The sync script copies files to FILESYSTEM_WP_ROOT/FOLDER_IN_WORDPRESS/YOUR_PLUGIN_SLUG

# After syncing, you'll need a .env.testing file in the WordPress plugin folder too
# (The sync script doesn't copy .env.testing to maintain separate configurations)
cp $DEV_FOLDER/.env.testing $WP_PLUGIN_FOLDER/.env.testing
# Edit this file to set appropriate paths for the WordPress environment

# Now switch to the WordPress plugin folder for refreshing Composer
cd $WP_PLUGIN_FOLDER

# Clean and update dependencies
rm -rf vendor/ composer.lock .phpunit.result.cache
composer update

# Run tests
cd $WP_PLUGIN_FOLDER
composer test
composer test:unit
composer test:wp-mock
composer test:integration

# or
cd $DEV_FOLDER
php bin/sync-and-test.php
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
- [PHPCS & PHPCBF Guide](../tools/PHPCS-PHPCBF-Guide.md)

## Customizing PHPCS Configuration

This framework includes a `phpcs.xml.dist` file with default configurations for WordPress coding standards. The file includes prefix settings with placeholder values that you'll need to customize for your project:

```xml
<!-- Set prefixes for checking naming conventions - CUSTOMIZE THESE FOR YOUR PROJECT -->
<rule ref="WordPress.NamingConventions.PrefixAllGlobals">
    <properties>
        <property name="prefixes" type="array">
            <element value="Your_Plugin"/><!-- For constants and class names -->
            <element value="your_plugin"/><!-- For functions and global variables -->
            <element value="Your_Plugin\\"/><!-- For namespaces -->
        </property>
    </properties>
</rule>
```

### Understanding the Three Prefix Values

WordPress coding standards require three different prefix formats for different code elements:

1. **PascalCase with underscores** (e.g., `Your_Plugin`):
   - Used for class names: `class Your_Plugin_Admin {}`
   - Used for constants: `define('YOUR_PLUGIN_VERSION', '1.0.0');`

2. **Lowercase with underscores** (e.g., `your_plugin`):
   - Used for functions: `function your_plugin_init() {}`
   - Used for global variables: `global $your_plugin_settings;`

3. **Namespace format** (e.g., `Your_Plugin\`):
   - Used for PHP namespaces: `namespace Your_Plugin\Admin;`
   - Note the double backslash in the XML configuration (escaping)

### Setting Up Your Configuration

If you're using this framework in your own plugin or theme, you should:

1. Copy `phpcs.xml.dist` to `phpcs.xml` (which is gitignored)
2. Update the prefixes to match your plugin's naming convention
3. Adjust any other rules to match your project's coding standards

This ensures that PHPCS will correctly identify unprefixed functions, variables, and namespaces in your code.
