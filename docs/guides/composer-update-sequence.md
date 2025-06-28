# Composer Update Workflow

This guide outlines the correct sequence for updating Composer dependencies across the testing framework, your plugin, and WordPress. Running `composer update` in the correct order is critical to prevent conflicts.

## Table of Contents

- [Quick Reference: Frequent Steps](#quick-reference-frequent-steps)
- [Role-Based Update Workflows](#role-based-update-workflows)
  - [Framework Developer Workflow](#framework-developer-workflow)
  - [Plugin Developer Workflow](#plugin-developer-workflow)
  - [WordPress Site Administrator Workflow](#wordpress-site-administrator-workflow)
- [Dependency Management Explained](#dependency-management-explained)
  - [Composer Update Sequence](#composer-update-sequence)
  - [Autoloading Configuration](#autoloading-configuration)
  - [Locations to Avoid Running Composer Update](#locations-to-avoid-running-composer-update)
- [Troubleshooting Common Issues](#troubleshooting-common-issues)

## Quick Reference: Frequent Steps

If you're developing both the framework and plugin simultaneously:

1. **Update Framework Source:**
   ```bash
   cd ~/sites/phpunit-testing/
   composer update
   # Make your changes, and Git commit them
   ```

2. **Copy to Plugin Submodule:**
   ```bash
   cd ~/sites/reinvent/
   php update-framework.php
   ```

3. **Update Plugin Dependencies:**
   ```bash
   cd ~/sites/reinvent/
   composer update
   ```

4. **Sync to WordPress for Testing:**
   ```bash
   cd ~/sites/reinvent/
   php bin/sync-and-test.php
   ```

## Part 1: Framework Maintainer Workflow

As the maintainer, you update the core framework.

1.  **Develop:** Make code changes in the framework's source repository (`~/sites/phpunit-testing/`).

2.  **Update & Commit:** Run `composer update` in the framework's root, then commit all changes (`composer.json`, `composer.lock`, and source files) to Git.

    ```bash
    # Navigate to the framework directory and update
    cd ~/sites/phpunit-testing/
    composer update
    ```

3.  **Publish:** Push your Git changes and publish the new version to the Composer repository (Packagist).

---

## Part 2: Framework User Workflow

As a developer using the testing framework, your goal is to pull in the latest updates and integrate them into your plugin.

### Step 1: Update Dependencies in Your Plugin

Navigate to your plugin's root directory (`~/sites/your-plugin/`) and follow the appropriate instructions.

**A) If you use a Git Submodule:**

First, pull the latest changes for the submodule, then run `composer update` to resolve your plugin's other dependencies against the updated framework.

```bash
# Navigate to your plugin's root directory
cd ~/sites/your-plugin/

# Ensure you're on the correct branch before updating
git checkout main  # or your development branch

# Pull the latest framework code (fetches from the remote repository)
git submodule update --remote tests/gl-phpunit-test-framework

# If you need a specific branch of the framework
cd tests/gl-phpunit-test-framework
git checkout branch-name  # Optional: only if you need a specific branch
cd ../..

# Update your plugin's other dependencies
composer update
```

> **Important:** Never run `composer update` inside the submodule directory itself. Always run it from your plugin's root directory.

**B) If you use Composer:**

Simply run `composer update`. This command will fetch the latest version of the framework and all other dependencies at once.

```bash
# Navigate to your plugin's root directory and update
cd ~/sites/your-plugin/
composer update
```

### Step 2: Sync Your Plugin to WordPress

From your plugin's root directory, run the sync script to deploy the changes to your local WordPress environment.

```bash
# From ~/sites/your-plugin/
php bin/sync-and-test.php
```

#### How Sync Scripts Handle the Framework

The `sync-and-test.php` script performs several important tasks:

1. **Framework Detection:** It automatically detects the location of the testing framework using the `TEST_FRAMEWORK_DIR` setting in `.env.testing`. This allows it to work with both submodule and Composer installations.

2. **File Synchronization:** It copies your plugin's source files to the WordPress plugins directory for testing.

3. **Container Awareness:** When running in a Lando environment, it executes commands inside the container using `lando exec`.

4. **Dependency Management:** If needed, it runs `composer install` in the deployed plugin directory to ensure all dependencies are available for testing.

5. **Lando Container Integration:** When running in a Lando environment, the script automatically:
   - Detects the Lando container
   - Runs Composer updates inside the container using `lando composer update`
   - Updates both the plugin and submodule dependencies within the container environment
   - Ensures proper dependency resolution in the containerized PHP environment

### Step 3: Update the WordPress Root Installation

Finally, update the global development tools in your WordPress root directory.

**Reasoning:** This step should always be last. It ensures your entire plugin environment is stable before you update the tools (`phpcs`, `phpstan`) used to analyze it.

```bash
# Navigate to the WordPress root directory and update
cd ~/sites/wordpress/
composer update
```

---

## Dependency Management Explained

### Composer Update Sequence

When working with multiple projects that depend on each other, the order of running `composer update` matters:

> **Why update in source AND deployed directories?**
> 
> You might wonder why we run `composer update` in the source directory (`~/sites/reinvent/`) when the sync script will also handle dependencies in the deployed directory (`~/sites/wordpress/wp-content/plugins/gl-reinvent/`). The reasons are:
> 
> 1. **Source of Truth:** The source directory is the canonical location for your code and its dependencies. Your Git repository tracks these files.
> 2. **Development Tools:** Many IDE integrations and development tools rely on the vendor directory in your source folder.
> 3. **Sync Efficiency:** The sync script primarily ensures dependencies are available for testing, but maintaining updated dependencies in your source ensures consistency across environments.

1. **Framework Source First:** Always update the framework source repository first
2. **Plugin Source Second:** Then update your plugin's dependencies
3. **WordPress Root Last:** Finally, update the WordPress root dependencies

This sequence ensures that each layer builds upon a stable foundation.

### Autoloading Configuration

Understanding how autoloading works between projects is crucial for proper dependency management:

- **Framework Source (`~/sites/phpunit-testing/`):**
  ```json
  "autoload": {
      "psr-4": {
          "WP_PHPUnit_Framework\\": "src/"
      }
  }
  ```
  The framework's classes are autoloaded from its `src/` directory.

- **Plugin Source with Submodule (`~/sites/your-plugin/`):**
  ```json
  "autoload-dev": {
      "psr-4": {
          "WP_PHPUnit_Framework\\": "tests/gl-phpunit-test-framework/src/"
      }
  }
  ```
  The plugin maps the same namespace to the submodule's `src/` directory.

This configuration ensures that when you run tests in your plugin, it uses the framework classes from the submodule.

### Locations to AVOID Running `composer update`

-   **The Testing Framework Submodule:** `~/sites/your-plugin/tests/gl-phpunit-test-framework/`
    -   **Reason:** It's a Git submodule. Its dependencies are managed from its source repository.
    -   **Correct Approach:** Make changes in the framework source (`~/sites/phpunit-testing/`), then update the submodule reference.

-   **The Deployed Plugin Directory:** `~/sites/wordpress/wp-content/plugins/your-plugin/`
    -   **Reason:** It's a deployment target. Changes are temporary and will be overwritten by your sync script.
    -   **Correct Approach:** Make changes in your plugin source directory, then use the sync script to deploy.


## Troubleshooting Common Issues

### Git Submodule Conflicts

If you encounter conflicts during the submodule update, you may need to resolve them manually:

```bash
cd tests/gl-phpunit-test-framework
git status  # Check for conflicts
# Resolve conflicts if needed
git add .   # Stage resolved files
git commit -m "Resolve submodule conflicts"
cd ../..  
```

### Class Not Found Errors

If you encounter "Class not found" errors after updating:

1. **Check Autoloader Configuration:** Ensure your composer.json correctly maps the WP_PHPUnit_Framework namespace to the right directory.

2. **Regenerate Autoloader:** Run `composer dump-autoload` in your plugin's root directory.

3. **Clear Caches:** Remove any cached autoloader files in the vendor directory.

### Version Compatibility Issues

If you encounter compatibility issues between the framework and your plugin:

1. **Check PHP Version:** Ensure your PHP version meets the requirements of both projects.

2. **Check Framework Version:** If using a submodule, make sure you're on a compatible branch or commit.

3. **Review Dependency Conflicts:** Run `composer why-not package/name` to identify conflicting requirements.

### Duplicate Class Definitions

If you encounter "Cannot redeclare class" errors:

1. **Check for Multiple Autoloaders:** Ensure you're not loading the framework classes from multiple locations.

2. **Inspect exclude-from-classmap:** Make sure your composer.json excludes duplicate classes using the `exclude-from-classmap` setting.
