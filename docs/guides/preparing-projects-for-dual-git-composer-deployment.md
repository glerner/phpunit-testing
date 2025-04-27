# Preparing a Git Project for Dual Deployment (Git or Composer)

This guide explains how to structure and configure a Git repository so it can be used by other developers either directly via Git (submodules) or as a Composer package. This approach is particularly useful for WordPress development tools, testing frameworks, and libraries.

## Why Support Dual Deployment?

Different developers have different preferences and workflows:

- **Git-focused developers** may prefer direct access to files via Git submodules
  - Better for contributors who might submit changes or bug fixes
  - Provides full access to Git history and version control
  - Allows direct modification of the code within your project

- **Composer-focused developers** may prefer standardized dependency management
  - Cleaner installation with less Git clutter
  - Simpler dependency management
  - Treats your package as a "black box" dependency

- **WordPress developers** often span a spectrum from traditional Git users to modern PHP developers

By supporting both deployment methods, you maximize the usability and adoption of your project.

## Project Structure Requirements

For a project to work well with both Git submodules and Composer, it should have:

1. **Clear directory structure** that's intuitive when used directly
2. **Proper namespacing** for Composer's autoloading
3. **Self-contained functionality** with minimal external dependencies
4. **Comprehensive documentation** for both installation methods

## Step 1: Organize Your Directory Structure

Create a directory structure that works well for both direct usage and autoloading:

```
your-project/
├── src/                      # Main source code
│   ├── Core/                 # Core functionality
│   ├── Components/           # Additional components
│   └── Utilities/            # Helper classes
├── config/                   # Configuration templates
│   ├── phpunit.xml.dist
│   ├── phpstan.neon.dist
│   └── phpcs.xml.dist
├── examples/                 # Example implementations
│   ├── basic/
│   ├── advanced/
│   └── wordpress-integration/
├── docs/                     # Documentation
├── tests/                    # Tests for the framework itself
├── .gitignore
├── composer.json             # Package definition
├── README.md                 # Main documentation
└── LICENSE                   # License file
```

## Step 2: Configure composer.json

Create a `composer.json` file that properly defines your package:

```json
{
  "name": "yourusername/project-name",
  "description": "A clear description of your project",
  "type": "library",
  "license": "MIT",
  "authors": [
    {
      "name": "Your Name",
      "email": "your.email@example.com"
    }
  ],
  "require": {
    "php": ">=7.4"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.0"
  },
  "autoload": {
    "psr-4": {
      "YourNamespace\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "YourNamespace\\Tests\\": "tests/"
    }
  }
}
```

## Step 3: Create Comprehensive Documentation

### In README.md

Include clear instructions for both installation methods:

````markdown
## Installation

### Option A: Via Composer

```bash
# Add the repository to your composer.json
composer config repositories.project-name vcs https://github.com/yourusername/project-name.git

# Require the package
composer require yourusername/project-name:dev-main
```

### Option B: Via Git Submodule

```bash
# Add as a submodule to your project
git submodule add https://github.com/yourusername/project-name.git vendor/project-name

# Initialize and update the submodule
git submodule init
git submodule update
```

## Usage

### Compatible Usage for Both Installation Methods

```php
// This approach works for both Composer and Git installations
if (class_exists('YourNamespace\\Core\\MainClass') === false) {
    // Only include the file if the class isn't already loaded by Composer
    $file_path = __DIR__ . '/vendor/project-name/src/Core/MainClass.php';
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

use YourNamespace\Core\MainClass;

$instance = new MainClass();
```

This pattern allows your code to work seamlessly regardless of whether the package was installed via Composer (where autoloading handles class loading) or Git (where manual inclusion may be necessary).
````

## Step 4: Make Your Project Git-Friendly

Ensure your `.gitignore` file is properly configured:

```
# Composer
/vendor/
/composer.lock

# PHPUnit
/.phpunit.result.cache

# IDE files
/.idea/
/.vscode/

# OS files
.DS_Store
Thumbs.db
```

## Step 5: Create a Universal Bootstrap File

To support both Composer and Git users with a single codebase, create a bootstrap file that works for both installation methods:

```php
// bootstrap.php
<?php

// Check if Composer's autoloader is available
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
} else {
    // Simple autoloader for when not using Composer
    spl_autoload_register(function ($class) {
        // Convert namespace to file path
        $prefix = 'YourNamespace\\';
        $base_dir = __DIR__ . '/src/';

        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relative_class = substr($class, $len);

        // Convert namespace separators to directory separators
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    });
}
```

## Step 6: Version Tagging for Composer Users

Composer users benefit from semantic versioning:

```bash
# Tag a version
git tag -a v1.0.0 -m "Initial stable release"
git push origin v1.0.0
```

## Step 7: Publish Your Project

1. **Push to GitHub** or another Git hosting service
2. **Document both installation methods** in your README
3. **Share with both Git and Composer users**

## Real-World Example: WordPress Testing Framework

For a WordPress PHPUnit testing framework:

```json
{
  "name": "glerner/wp-phpunit-framework",
  "description": "WordPress PHPUnit Testing Framework with support for unit, integration, and WP-Mock tests",
  "type": "library",
  "license": "MIT",
  "require": {
    "php": ">=8.0",
    "phpunit/phpunit": "^9.0",
    "brain/monkey": "^2.6",
    "mockery/mockery": "^1.4",
    "yoast/phpunit-polyfills": "^1.0"
  },
  "autoload": {
    "psr-4": {
      "WP_PHPUnit_Framework\\TestFramework\\": "src/"
    }
  }
}
```

## Installation Instructions for Users

### For Composer Users

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/glerner/wp-phpunit-framework.git"
    }
  ],
  "require-dev": {
    "glerner/wp-phpunit-framework": "dev-main"
  }
}
```

### For Git Users

```bash
# Add the framework as a submodule
git submodule add https://github.com/glerner/wp-phpunit-framework.git tests/framework

# Initialize and update
git submodule init
git submodule update
```

### For End-User Plugins on Hosts Without Git/Composer

**Note:** For developer tools like the PHPUnit testing framework, users would need Git or Composer. This section applies primarily to end-user plugins like GL Color Palette Generator.

For users on hosting that doesn't support Git or Composer, provide a ready-to-use zip file:

```bash
# Navigate to your project directory
cd ~/sites/gl-color-palette-generator

# Create a .zip file excluding vendor/, .git/, and other unnecessary files
zip -r gl-color-palette-generator.zip . -x "vendor/*" ".git/*" ".gitignore" "composer.lock" "node_modules/*" ".DS_Store" "*.log"
```

When preparing a .zip distribution:

1. **Include dependencies**: If your plugin relies on libraries normally installed via Composer, include them directly in a `lib/` or `includes/` directory

2. **Ensure bootstrap works**: Your bootstrap.php should handle the absence of Composer's autoloader

3. **Installation instructions**: Include clear instructions for uploading via WordPress Admin → Plugins → Add New → Upload Plugin

4. **Version number**: Include the version number in the filename (e.g., `gl-color-palette-generator-1.0.0.zip`)

## Conclusion

By following these steps, you can create a project that's accessible to developers with different workflows and preferences. This dual-deployment approach is particularly valuable in the WordPress ecosystem, where development practices vary widely.

The key is to maintain a clean, well-organized codebase with clear documentation for both installation methods. This approach respects different developer workflows while providing the benefits of your project to the widest possible audience.
