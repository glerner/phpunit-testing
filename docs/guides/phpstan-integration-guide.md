# PHPStan Integration Guide

This document provides guidance on integrating PHPStan into WordPress plugin development workflows, covering both clean slate projects and existing codebases.

## Table of Contents

1. [Introduction to PHPStan](#introduction-to-phpstan)
2. [Clean Slate Approach](#clean-slate-approach)
3. [Working with Existing Code](#working-with-existing-code)
4. [PHPStan Configuration](#phpstan-configuration)
5. [WordPress-Specific Rules](#wordpress-specific-rules)
6. [CI Integration](#ci-integration)
7. [Best Practices](#best-practices)

## Introduction to PHPStan

PHPStan is a static analysis tool that finds errors in your code without actually running it. It catches whole classes of bugs even before you write tests for your code.

### Key Benefits

- Detects type-related errors
- Finds dead code and impossible conditions
- Identifies incorrect method calls
- Validates property access
- Ensures consistent return types

### Rule Levels

PHPStan has 9 levels of strictness (0-8):

- Level 0: Basic checks
- Level 3: Good starting point for new projects
- Level 5: Recommended for mature projects
- Level 8: Maximum strictness

## Clean Slate Approach

For new projects or complete rewrites (like our GL Color Palette Generator), we recommend a "zero tolerance" approach to PHPStan errors.

### Installation

This is already done for you in the GL PHPUnit Testing framework, if you followed the standard installation instructions.

1. **Install PHPStan via Composer**:
   ```bash
   # Basic installation
   composer require --dev phpstan/phpstan

   # With WordPress extension
   composer require --dev phpstan/phpstan szepeviktor/phpstan-wordpress
   ```

2. **Verify Installation**:
   ```bash
   ./vendor/bin/phpstan --version
   ```

### WordPress-Specific Rules

Install the WordPress extension for PHPStan:

```bash
composer require --dev szepeviktor/phpstan-wordpress
```

Add to your configuration:

```neon
includes:
    - vendor/szepeviktor/phpstan-wordpress/extension.neon
```

### Implementation Steps

1. **Start with Level 3**:
   ```bash
   ./vendor/bin/phpstan analyse --level=3 src/
   ```

2. **Include PHPStan in Development Workflow**:
   - Run before pushing to GitHub (not necessarily before every local commit)
   - Local commits for backup or work-in-progress are fine without PHPStan checks
   - Make passing PHPStan a requirement for Pull Request approval and merging to main branch

3. **Gradually Increase Level**:
   - Move to level 5 once the codebase is stable
   - Target level 8 for production-ready code

4. **Document Special Level Requirements** (only when needed):
   ```php
   /**
    * Class responsible for color manipulation.
    * This class requires special handling for PHPStan due to complex color calculations.
    *
    * @phpstan-level 5
    */
   class Color_Manipulator {
       // ...
   }
   ```

   Note: You don't need to annotate every file with PHPStan levels. This is only needed for specific classes or methods that require exceptions to your project's default level.

### Benefits of Clean Slate

- No technical debt from the beginning
- Consistent code quality across the project
- Easier maintenance long-term
- Better developer experience

## Working with Existing Code

For existing projects with legacy code, a different approach is needed to gradually improve code quality without blocking development.

### Creating a Baseline

A baseline file tells PHPStan to ignore existing errors while catching new ones:

1. **Generate a baseline**:
   ```bash
   ./vendor/bin/phpstan analyse --level=3 --generate-baseline=phpstan-baseline.neon src/
   ```

2. **Include the baseline in your configuration**:
   ```neon
   # phpstan.neon
   includes:
       - phpstan-baseline.neon
   ```

3. **Run PHPStan with the baseline**:
   ```bash
   ./vendor/bin/phpstan analyse --level=3 src/
   ```

### Gradually Improving

1. **Fix errors in small batches**:
   - Choose a specific error type or file
   - Fix those errors
   - Update the baseline

2. **Track progress**:
   - Count remaining errors: `grep -c "error:" phpstan-baseline.neon`
   - Set targets for error reduction

3. **Increase level incrementally**:
   - Move to higher levels only after clearing lower-level errors

## PHPStan Configuration

Create a `phpstan.neon` file in your project root:

```neon
parameters:
    level: 3
    paths:
        - src/
    excludePaths:
        - vendor/
    checkMissingIterableValueType: false
    treatPhpDocTypesAsCertain: false
```

### WordPress-Specific Configuration

```neon
parameters:
    scanDirectories:
        - /path/to/wordpress/core
    dynamicConstantNames:
        - WP_DEBUG
        - ABSPATH
        - WPINC
```

### Custom WordPress Rules

Create custom rules for WordPress-specific patterns:

```php
/**
 * Validates proper sanitization of user input.
 */
class SanitizationRule extends PHPStan\Rules\Rule
{
    // Implementation
}
```

## CI Integration

### GitHub Actions

Create `.github/workflows/phpstan.yml`:

```yaml
name: PHPStan

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          tools: composer:v2

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --no-progress
```

### GitLab CI

Create `.gitlab-ci.yml`:

```yaml
phpstan:
  image: php:7.4
  before_script:
    - apt-get update && apt-get install -y git zip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install
  script:
    - vendor/bin/phpstan analyse --no-progress
```

## Best Practices

1. **Document Exceptions**:
   ```php
   /** @phpstan-ignore-next-line */
   $variable = $complex_function();
   ```

2. **Use PHP Type Annotations**:
   ```php
   /**
    * @param array<string, mixed> $options
    * @return array<int, string>
    */
   function process_options(array $options): array {
       // ...
   }
   ```

3. **Create Extension Methods**:
   ```php
   /**
    * @method string get_title()
    * @method void set_title(string $title)
    */
   class Post {
       // ...
   }
   ```

4. **Regular PHPStan Updates**:
   - Update PHPStan regularly
   - Review new rules and features
   - Adjust configuration as needed

5. **Team Training**:
   - Ensure all developers understand PHPStan
   - Share common error patterns and solutions
   - Celebrate error reduction milestones

## Conclusion

Whether starting fresh or working with existing code, PHPStan is a powerful tool for improving code quality. By integrating it into your development workflow, you can catch errors early and maintain a high standard of code quality throughout your project's lifecycle.
