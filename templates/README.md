# Test Templates

This directory contains example test files that demonstrate how to use the GL WordPress Testing Framework. These templates provide a starting point for creating your own tests.

## Directory Structure

- **unit/** - Example unit tests for isolated testing without WordPress
- **wp-mock/** - Example WP_Mock tests for testing code that interacts with WordPress functions
- **integration/** - Example integration tests for testing with a real WordPress environment

## How to Use These Templates

1. Copy the relevant template to your plugin's test directory
2. Rename the file and class to match your specific test needs
3. Modify the test methods to test your actual plugin functionality
4. Update the namespace and `@covers` annotation to match your plugin's structure

> **Note:** These template files will show IDE errors since they reference classes that don't exist in the template context. This is expected behavior. The errors will disappear once you copy the templates to your plugin and modify them to match your plugin's structure.

## Template Features

Each template demonstrates:

- Proper test class structure
- Setup and teardown methods
- Various assertion techniques
- Mocking dependencies (where applicable)
- Data providers for testing multiple scenarios
- WordPress-specific testing techniques (for WP_Mock and integration tests)

## Example Usage

```bash
# Copy a unit test template to your plugin
cp /path/to/framework/templates/unit/Example_Unit_Test.php /path/to/your-plugin/tests/Unit/Your_Feature_Test.php

# Edit the file to match your plugin's needs

# Then run the test
cd /path/to/your-plugin
vendor/bin/phpunit -c phpunit-unit.xml.dist tests/Unit/Your_Feature_Test.php
```

For more detailed information on writing tests, refer to the [PHPUnit Testing Tutorial](../docs/guides/phpunit-testing-tutorial.md).
