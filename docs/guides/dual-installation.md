# Dual Installation: Git Submodule and Composer

## Overview

This document explains how to work with the framework using either Git submodule or Composer installation. It's particularly useful for:
- Framework developers maintaining the test framework
- Plugin developers using the framework
- Teams with mixed development environments

## Choosing an Installation Method

### Use Git Submodule If:
- You're actively developing the framework
- You need to test framework changes in the context of a plugin
- You want to track exact commits in your project

### Use Composer If:
- You just need to use the framework
- You want simpler dependency management
- You're not modifying the framework code

## Publishing to Packagist

To make the framework available via Composer for other projects, you can publish it to Packagist:

1. **Prerequisites**:
   - A GitHub repository for the framework
   - A Packagist.org account
   - A valid `composer.json` in your repository root

2. **Steps to Publish**:
   ```bash
   # 1. Ensure your composer.json is properly configured with name, description, and autoloading
   # 2. Commit and push your code to GitHub
   git tag 1.0.0
   git push origin 1.0.0
   
   # 3. Go to Packagist.org and submit your package URL
   #    (e.g., https://github.com/yourname/phpunit-testing)
   # 4. Enable GitHub service hook for automatic updates
   ```

3. **Updating**:
   ```bash
   # 1. Make your changes
   # 2. Update the version in composer.json
   # 3. Create a new tag
   git tag 1.0.1
   git push origin 1.0.1
   # 4. Packagist will automatically update
   ```

## Installation Methods

### 1. Git Submodule (Recommended for Development)
**Best for**: Framework developers and those contributing to the framework

```bash
# Add as a submodule
git submodule add https://github.com/glerner/phpunit-testing.git tests/gl-phpunit-test-framework

# Initialize and update
git submodule update --init --recursive
```

### 2. Composer (For Production Use)
**Best for**: Projects that just need to use the framework

#### From GitHub (Development)
Add to your `composer.json`:
```json
{
    "require-dev": {
        "glerner/phpunit-testing": "dev-main"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/glerner/phpunit-testing"
        }
    ]
}
```

#### From Packagist (Production)
After publishing to Packagist, users can simply run:
```bash
composer require --dev glerner/phpunit-testing
```

For both methods, then run:
```bash
composer install
```

## Development Workflow

### For Framework Developers

#### Local Development Workflow
1. **Make changes in source repository** (`~/sites/phpunit-testing`)
   ```bash
   # Make your changes
   git add .
   git commit -m "Your changes"
   ```

2. **Test in your plugin** (`~/sites/your-plugin`)
   ```bash
   # Option 1: Pull directly from local framework
   cd ~/sites/your-plugin/tests/gl-phpunit-test-framework
   git pull ~/sites/phpunit-testing main --allow-unrelated-histories

   # Option 2: Or set up a local remote (do this once)
   cd ~/sites/your-plugin/tests/gl-phpunit-test-framework
   git remote add local ~/sites/phpunit-testing

   # Then pull from local
   git pull local main

   # Update the submodule reference
   cd ..
   git add gl-phpunit-test-framework
   git commit -m "Update framework with local changes"
   ```

#### Quick Testing (Temporary Changes)
For quick tests without committing:
```bash
# In your plugin directory
cd ~/sites/your-plugin/tests/gl-phpunit-test-framework

# Override with local changes
cp -r ~/sites/phpunit-testing/* .
# reminder, never make changes to tests/gl-phpunit-test-framework, change the source

# When done testing, reset to committed state
git reset --hard
git clean -fd
```

### For Plugin Developers
1. **Update the framework** (if using submodule)
   ```bash
   git submodule update --remote --merge
   git add tests/gl-phpunit-test-framework
   git commit -m "Update phpunit-testing submodule to latest"
   ```

2. **Or update via Composer**
   ```bash
   composer update glerner/phpunit-testing
   ```

3. **Copy framework files**
   ```bash
   php bin/copy-wp-phpunit-test-framework-convenient-files.php
   ```

## How It Works

### Autoloading
Both methods use Composer's PSR-4 autoloader, so your test classes work the same way regardless of installation method.

### File Copying
The `copy-wp-phpunit-test-framework-convenient-files.php` script handles:
- Copying configuration files to `tests/config/`
- Setting up bootstrap files in `tests/bootstrap/`
- Installing helper scripts in `bin/`

## Best Practices

### Git Submodule
- **Do** commit the submodule reference in your main repository
- **Don't** make changes directly in the submodule directory
- Update with:
  ```bash
  git submodule update --remote --merge
  git add tests/gl-phpunit-test-framework
  git commit -m "Update framework submodule"
  ```

### Composer
- **Do** commit `composer.lock`
- **Do** specify version constraints in `composer.json`
- Update with:
  ```bash
  composer update glerner/phpunit-testing
  ```

## Publishing to Packagist

To make your framework available via Packagist:

1. Create a release on GitHub
   ```bash
   git tag -a v1.0.0 -m "First stable release"
   git push origin v1.0.0
   ```

2. Go to [Packagist](https://packagist.org/) and submit your repository

3. Enable GitHub webhook for automatic updates

## Troubleshooting

### Submodule Issues
```bash
# If submodule gets out of sync
cd tests/gl-phpunit-test-framework
git fetch
git reset --hard origin/main
```

### Composer Issues
```bash
# If autoloading breaks
composer dump-autoload

# If you need to reinstall
rm -rf vendor/
composer install
```

## File Locations

| File Type          | Git Submodule Location           | Composer Location                 |
|--------------------|----------------------------------|----------------------------------|
| Framework Source  | `tests/gl-phpunit-test-framework/` | `vendor/glerner/phpunit-testing/` |
| Config Files      | `tests/config/`                  | `tests/config/`                  |
| Bootstrap Files   | `tests/bootstrap/`               | `tests/bootstrap/`               |
| Bin Scripts       | `bin/*.dist`                     | `bin/*.dist`                     |
