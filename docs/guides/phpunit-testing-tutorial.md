# PHPUnit Testing Tutorial for Large Projects

This tutorial provides a comprehensive guide to setting up and organizing PHPUnit tests for large PHP projects, especially those with WordPress integration. It's based on lessons learned from the GL Color Palette Generator project `https://github.com/glerner/gl-color-palette-generator.git` .

## Table of Contents

- [Test Types and When to Use Each](#test-types-and-when-to-use-each)
- [Directory Structure](#directory-structure)
- [Base Test Classes](#base-test-classes)
- [Naming Conventions](#naming-conventions)
- [Namespace Organization](#namespace-organization)
- [Determining the Right Test Type](#determining-the-right-test-type)
- [Mocking Strategies](#mocking-strategies)
- [Test Isolation](#test-isolation)
- [Managing Test Dependencies](#managing-test-dependencies)
- [Continuous Integration Setup](#continuous-integration-setup)
- [Test Analysis and Maintenance](#test-analysis-and-maintenance)
- [Contributing](#contributing)

## Test Types and When to Use Each

### 1. Unit Tests

- **Purpose**: Test individual components in isolation
- **When to use**: For classes/functions with minimal external dependencies
- **Characteristics**:
  - Fast execution
  - No database or filesystem access
  - No WordPress core dependencies
  - Dependencies are mocked or stubbed
- **Example scenarios**:
  - Utility classes
  - Data transformation functions
  - Business logic that doesn't rely on WordPress

### 2. WP-Mock Tests

- **Purpose**: Test WordPress-dependent code without a full WordPress environment
- **When to use**: For code that uses WordPress functions but doesn't need database access
- **Characteristics**:
  - Medium execution speed
  - Mocks WordPress functions and hooks
  - No actual WordPress loading
  - Good for testing plugin hooks, filters, and actions
- **Example scenarios**:
  - Code that uses `add_action()` or `add_filter()`
  - Functions that call WordPress utility functions
  - Admin page rendering that uses WordPress functions

### 3. Integration Tests

- **Purpose**: Test code that interacts with WordPress core, database, or external services
- **When to use**: When you need to test actual WordPress behavior or database interactions
- **Characteristics**:
  - Slower execution
  - Requires a test WordPress database
  - Tests actual integration with WordPress
  - May include API calls to external services
- **Example scenarios**:
  - Database operations
  - WordPress option handling
  - REST API endpoints
  - Live API integrations

## Directory Structure

### Framework Structure

The GL WP PHPUnit Test Framework is designed to be included as a submodule or Composer package within your plugin project's test/ folder. It is not intended to be used as a standalone package.

```
gl-phpunit-test-framework/                # The framework repository
├── src/                        # Source code for the testing framework
│   ├── Unit/                   # Base classes for unit tests
│   │   └── Unit_Test_Case.php
│   ├── WP_Mock/                # Base classes for WP-Mock tests
│   │   └── WP_Mock_Test_Case.php
│   └── Integration/            # Base classes for integration tests
│       └── Integration_Test_Case.php
├── config/                     # Configuration templates
│   ├── phpunit/
│   │   ├── phpunit-unit.xml.dist
│   │   ├── phpunit-wp-mock.xml.dist
│   │   └── phpunit-integration.xml.dist
│   ├── phpstan/
│   └── phpcs/
├── templates/                  # Template files for test creation
│   ├── unit/
│   ├── wp-mock/
│   └── integration/
├── tests/                      # Tests for the framework itself
│   └── bootstrap/
│       ├── bootstrap.php
│       ├── bootstrap-integration.php
│       └── bootstrap-wp-mock.php
└── docs/
    └── guides/
        └── phpunit-testing-tutorial.md
```

### Plugin Project Structure

When using this framework in your plugin project, your directory structure would look like:

```
your-plugin-root/
├── src/                        # Your plugin source code
│   ├── core/
│   ├── admin/
│   └── ...
├── tests/                      # Your plugin tests
│   ├── framework/              # This framework (as a submodule or via Composer)
│   │   └── ...
│   ├── bootstrap/              # Your plugin-specific bootstrap files
│   │   ├── bootstrap.php
│   │   ├── bootstrap-integration.php
│   │   └── bootstrap-wp-mock.php
│   ├── fixtures/               # Test data files
│   │   ├── images/             # Sample images for testing
│   │   ├── data/               # General test data files (JSON, XML, CSV, etc.)
│   │   ├── api-responses/      # Mock responses from external APIs
│   │   └── ...
│   ├── unit/                   # Your unit tests, mirroring your src/ structure
│   │   ├── core/
│   │   └── ...
│   ├── wp-mock/                # Your WP-Mock tests, mirroring your src/ structure
│   │   ├── admin/
│   │   └── ...
│   ├── integration/            # Your integration tests, mirroring your src/ structure
│   │   ├── core/
│   │   └── ...
│   └── TEST-PLAN.md            # Documentation of your test plan
├── phpunit.xml                 # Main PHPUnit configuration
├── phpunit-integration.xml     # Integration tests configuration
└── phpunit-wp-mock.xml         # WP-Mock tests configuration
```

Key points:
- The framework is included in `tests/framework/`
- Your plugin-specific tests are organized in `tests/unit/`, `tests/wp-mock/`, and `tests/integration/`
- Test directories mirror your source code structure
- Separate configuration files for each test type
- Bootstrap files for different test environments
- Fixtures directory for test data and sample files

## Base Test Classes

The framework provides base test classes for each test type to ensure consistent setup:

### Unit Test Case

```php
namespace WP_PHPUnit_Framework\Unit;

class Unit_Test_Case extends \PHPUnit\Framework\TestCase {
    protected function setUp(): void {
        parent::setUp();
        // Common setup for unit tests
    }

    protected function tearDown(): void {
        // Common teardown for unit tests
        parent::tearDown();
    }
}
```

### WP-Mock Test Case

```php
namespace WP_PHPUnit_Framework\WP_Mock;

class WP_Mock_Test_Case extends \PHPUnit\Framework\TestCase {
    protected function setUp(): void {
        parent::setUp();
        \WP_Mock::setUp();
    }

    protected function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }
}
```

### Integration Test Case

```php
namespace WP_PHPUnit_Framework\Integration;

class Integration_Test_Case extends \PHPUnit\Framework\TestCase {
    protected function setUp(): void {
        parent::setUp();
        // Setup WordPress test environment
    }

    protected function tearDown(): void {
        // Cleanup WordPress test environment
        parent::tearDown();
    }
}
```

## Naming Conventions

Consistent naming helps maintain clarity:

### Test Files

- **Pattern**: `test-{class-being-tested}.php`
- **Examples**:
  - `test-settings-manager.php`
  - `test-api-client.php`

### Test Classes

- **Pattern**: `Test_{ClassBeingTested}`
- **Examples**:
  - `Test_Settings_Manager`
  - `Test_API_Client`

### Base Classes

- **Pattern**: `{Type}_Test_Case`
- **Examples**:
  - `Unit_Test_Case`
  - `WP_Mock_Test_Case`
  - `Integration_Test_Case`

### Test Methods

- **Pattern**: `test_{method_being_tested}_{scenario}`
- **Examples**:
  - `test_get_option_returns_default_when_not_set()`
  - `test_process_data_handles_empty_input()`

## Namespace Organization

Namespaces should reflect your directory structure:

### Framework Namespaces

```php
namespace WP_PHPUnit_Framework\Unit;
namespace WP_PHPUnit_Framework\WP_Mock;
namespace WP_PHPUnit_Framework\Integration;
```

### Plugin Test Namespaces

```php
namespace Your\Plugin\Tests\Unit\Core;
namespace Your\Plugin\Tests\WP_Mock\Admin;
namespace Your\Plugin\Tests\Integration\API;
```

## Determining the Right Test Type

Use this decision flowchart to determine which test type to use:

1. **Does the code interact with WordPress functions, hooks, or globals?**
   - **No**: Use Unit Test
   - **Yes**: Continue to next question

2. **Does the code need a real WordPress database or filesystem?**
   - **No**: Use WP-Mock Test
   - **Yes**: Use Integration Test

3. **Does the code make external API calls?**
   - If these can be mocked: Use WP-Mock Test
   - If these need to be tested live: Use Integration Test

## Mocking Strategies

Different test types require different mocking approaches:

### For Unit Tests

Even though unit tests focus on testing components in isolation, they often need mocks for several reasons:

- **Dependency Isolation**: To test a class without being affected by its dependencies
- **Controlled Testing Environment**: To create predictable test conditions
- **Verifying Interactions**: To ensure your class correctly interacts with dependencies
- **Testing Edge Cases**: To easily simulate error conditions or rare scenarios
- **Performance**: To avoid slow operations from real dependencies

Common mocking approaches for unit tests:

1. **No Mocks**: For simple classes with no dependencies or with simple value objects as dependencies
2. **PHPUnit's createMock**: For simple interface mocking when you just need basic method stubs
3. **Mockery**: For more complex mocking scenarios requiring sophisticated expectations

Example with Mockery:
```php
public function test_process_data_calls_validator() {
    $validator = \Mockery::mock('Project\Validator');
    $validator->shouldReceive('validate')
        ->once()
        ->with('test-data')
        ->andReturn(true);

    $processor = new Data_Processor($validator);
    $processor->process_data('test-data');
}
```

### For WP-Mock Tests

#### Using WP_Mock for WordPress Hooks

Use WP_Mock to mock WordPress hooks and actions:

```php
public function test_register_hooks_adds_actions() {
    \WP_Mock::expectActionAdded('init', [$this->instance, 'initialize']);
    \WP_Mock::expectFilterAdded('the_content', [$this->instance, 'filter_content']);

    $this->instance->register_hooks();
}
```

#### Using Brain Monkey for WordPress Functions

Brain Monkey complements WP_Mock by providing the ability to mock WordPress global functions. This is essential for testing code that interacts with WordPress core functions without needing a real WordPress environment.

**Setup and Teardown:**

Ensure proper setup and teardown in your test classes:

```php
public function setUp(): void {
    parent::setUp();
    \Brain\Monkey\setUp();
    // Your test setup
}

public function tearDown(): void {
    \Brain\Monkey\tearDown();
    parent::tearDown();
}
```

### Why Use Mocks?

Mocks are essential in unit testing for several reasons:
1. **Isolation**: Ensure tests only evaluate the specific code under test
2. **Determinism**: Create predictable test environments
3. **Verification**: Confirm interactions with dependencies
4. **Edge Cases**: Simulate hard-to-reproduce scenarios
5. **Speed**: Avoid slow external dependencies

### Using Mockery for PHP Classes

```php
public function test_api_client_handles_error() {
    // Create a mock of the HTTP client
    $http_client = \Mockery::mock('HTTP_Client');

    // Set expectations
    $http_client->shouldReceive('request')
                ->once()
                ->with('GET', 'https://api.example.com/data')
                ->andThrow(new \Exception('Connection error'));

    // Inject the mock
    $api_client = new API_Client($http_client);

    // Test the method with the mock
    $this->expectException(\Exception::class);
    $api_client->fetchData();
}
```

### Using Brain Monkey to Mock WordPress Functions

```php
use Brain\Monkey\Functions;

public function test_cache_operations() {
    // Functions\expect is the Brain Monkey command to mock a WordPress function
    // Use Brain Monkey to mock wp_cache_get and specify its return value
    Functions\expect('wp_cache_get')
        ->once()
        ->with('test_key', 'test_group')
        ->andReturn(false);

    // Use Brain Monkey to mock wp_cache_set and specify its return value
    Functions\expect('wp_cache_set')
        ->once()
        ->with('test_key', \Mockery::any(), 'test_group', 3600)
        ->andReturn(true);

    // Call the function that uses these WordPress functions
    $result = $this->cache_manager->get_or_set('test_key');

    // Assert the expected behavior
    $this->assertNotNull($result);
}
```

### Using WP_Mock for WordPress Hooks

```php
public function test_init_hooks() {
    // Expect the add_action function to be called with specific parameters
    \WP_Mock::expectActionAdded('init', [$this->plugin, 'initialize']);

    // Expect the add_filter function to be called with specific parameters
    \WP_Mock::expectFilterAdded('the_content', [$this->plugin, 'filter_content']);

    // Call the method that should add these hooks
    $this->plugin->setup_hooks();

    // WP_Mock will automatically verify expectations during tearDown
}
```

## Test Isolation

Ensure tests don't affect each other:

1. **Reset State**: Clean up after each test
   ```php
   protected function tearDown(): void {
       // Reset any static properties
       YourClass::$static_property = null;

       // Reset global state
       global $wp_actions;
       $wp_actions = [];

       parent::tearDown();
   }
   ```

2. **Use Data Providers**: Keep tests focused on single scenarios
   ```php
   /**
    * @dataProvider provide_validation_scenarios
    */
   public function test_validation($input, $expected_result) {
       $validator = new Validator();
       $this->assertSame($expected_result, $validator->is_valid($input));
   }

   public function provide_validation_scenarios() {
       return [
           'valid email' => ['test@example.com', true],
           'invalid email' => ['not-an-email', false],
           'empty string' => ['', false],
       ];
   }
   ```

3. **Avoid Shared Resources**: Don't rely on external files or databases unless necessary

## Managing Test Dependencies

### Composer Dependencies

```json
{
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "mockery/mockery": "^1.4",
        "brain/monkey": "^2.6",
        "10up/wp_mock": "^0.4"
    }
}
```

### Bootstrap Files

Create separate bootstrap files for different test types:

```php
// bootstrap.php (Unit tests)
require_once __DIR__ . '/../../vendor/autoload.php';
\Brain\Monkey\setUp();

// bootstrap-wp-mock.php
require_once __DIR__ . '/../../vendor/autoload.php';
WP_Mock::bootstrap();

// bootstrap-integration.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/wordpress/wordpress-develop/tests/phpunit/includes/bootstrap.php';
```

## Continuous Integration Setup

### GitHub Actions Example

```yaml
name: Tests

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [7.4, 8.0, 8.1]
        test-type: [unit, wp-mock, integration]

    steps:
    - uses: actions/checkout@v2

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php }}
        extensions: mbstring, intl
        coverage: xdebug

    - name: Install dependencies
      run: composer install --prefer-dist --no-progress

    - name: Run ${{ matrix.test-type }} tests
      run: vendor/bin/phpunit -c phpunit-${{ matrix.test-type }}.xml
```

## Test Analysis and Maintenance

Regularly review and maintain your test suite:

1. **Coverage Analysis**: Use PHPUnit's coverage reports to identify untested code
2. **Test Quality Review**: Periodically review tests for effectiveness
3. **Refactoring Tests**: Update tests when refactoring code
4. **Code Quality Metrics**: Use tools like PHPStan to analyze test code quality
5. **PHPDoc Completeness**: Check for complete PHPDoc annotations (@covers, etc.)
6. **Test Isolation**: Analyze potential test isolation issues

## Running Tests

This section provides comprehensive instructions for running different types of tests in various environments.

### Basic PHPUnit Command

After setting up the framework, you can run PHPUnit using the basic command:

```bash
# From your plugin directory
./vendor/bin/phpunit
```

### Using Environment Variables

You can control test behavior using environment variables:

```bash
# Run unit tests
PHPUNIT_BOOTSTRAP_TYPE=unit ./vendor/bin/phpunit

# Run WP-Mock tests
PHPUNIT_BOOTSTRAP_TYPE=wp-mock ./vendor/bin/phpunit

# Run integration tests
PHPUNIT_BOOTSTRAP_TYPE=integration ./vendor/bin/phpunit

# Specify WordPress root directory
WP_ROOT=/app ./vendor/bin/phpunit

# Combine multiple environment variables
WP_ROOT=/app PHPUNIT_BOOTSTRAP_TYPE=integration ./vendor/bin/phpunit
```

#### Setting Environment Variables in Lando

To avoid specifying `WP_ROOT` in every command, you can add it to your `.lando.yml` file:

```yaml
services:
  appserver:
    overrides:
      environment:
        WP_ROOT: /app
        # Other environment variables...
```

With this configuration, the `WP_ROOT` environment variable will be automatically available inside the Lando container, eliminating the need to specify it in each test command.

### Running Tests

> **IMPORTANT**: Tests must be run from within the WordPress environment (~/sites/wordpress/wp-content/plugins/your-plugin/). Only code quality tools like PHPCS or PHPStan can be run directly from your plugin development directory.

#### Using the Sync-and-Test Script

For users who prefer a simpler approach, the framework includes a `sync-and-test.php` script that combines syncing your plugin to WordPress and running tests in a single command. This is the recommended approach for most users.

```bash
# From your plugin development directory
cd ~/sites/your-plugin/

# Run the sync-and-test.php script with the desired test type
php bin/sync-and-test.php --unit
```

The script handles all three steps automatically:
1. Syncs your plugin to WordPress (using the existing sync-to-wp.php script)
2. Changes to the WordPress plugin directory
3. Runs the appropriate tests

**Note:** If only `--multisite` is given, it defaults to `--integration --multisite`.

##### Available Options

```
--help          Show help message
--unit          Run unit tests (tests that don't require WordPress functions)
--wp-mock       Run WP Mock tests (tests that mock WordPress functions)
--integration   Run integration tests (tests that require a WordPress database)
--all           Run all test types (unit, wp-mock, integration) in sequence
--multisite     Run integration tests in multisite mode (or with --unit/--wp-mock for advanced scenarios)
--coverage      Generate code coverage report in build/coverage directory
--verbose       Show verbose output
--file=<file>   Run a specific test file instead of the entire test suite
```

When running integration tests with `--multisite`, `sync-and-test.php` sets `WP_TESTS_MULTISITE=1` in the environment.

If there are no tests in a suite, PHPUnit will report "No tests executed!" — this is normal for new projects.

##### Examples

```bash
# Run unit tests
php bin/sync-and-test.php --unit

# Run WP Mock tests with a specific file
php bin/sync-and-test.php --wp-mock --file=tests/wp-mock/specific-test.php

# Run integration tests with code coverage
php bin/sync-and-test.php --integration --coverage

# Run all tests with verbose output
php bin/sync-and-test.php --all --verbose
```

#### Running Tests with Composer and Lando

For more advanced users, the framework also provides Composer scripts and Lando commands that simplify running tests. The typical workflow involves:

1. `php bin/sync-to-wp.php` - Syncs your plugin code to the WordPress installation
2. Navigate to your plugin directory **inside the WordPress installation**
3. Run tests using Composer or Lando commands

#### Mock Tests (using WP_Mock)

For all examples below, first sync your plugin and navigate to the WordPress plugin directory:

```bash
# Step 1: From your plugin development directory, sync to WordPress
cd ~/sites/your-plugin/
php bin/sync-to-wp.php

# Step 2: Navigate to your plugin directory INSIDE WordPress
cd ~/sites/wordpress/wp-content/plugins/your-plugin/
```

Then run the tests:

```bash
# Run all WP-Mock tests using Composer
composer test:wp-mock

# Or using Lando
lando test:wp-mock

# Run a specific WP-Mock test file using PHPUnit directly
vendor/bin/phpunit -c config/phpunit-wp-mock.xml.dist tests/wp-mock/specific-test.php

# Or using Lando
lando ssh -c "cd /app/wp-content/plugins/your-plugin && vendor/bin/phpunit -c config/phpunit-wp-mock.xml.dist tests/wp-mock/specific-test.php"
```

#### Unit Tests

For all examples below, first sync your plugin and navigate to the WordPress plugin directory:

```bash
# Step 1: From your plugin development directory, sync to WordPress
cd ~/sites/your-plugin/
php bin/sync-to-wp.php

# Step 2: Navigate to your plugin directory INSIDE WordPress
cd ~/sites/wordpress/wp-content/plugins/your-plugin/
```

Then run the tests:

```bash
# Run all unit tests using Composer
composer test:unit

# Or using Lando
lando test:unit

# Generate code coverage report
lando test:coverage

# Or using Composer directly with PHPUnit
# This creates HTML coverage reports in the build/coverage directory
# You can view these reports by opening build/coverage/index.html in a browser
vendor/bin/phpunit -c config/phpunit-unit.xml.dist --coverage-html build/coverage

# Run tests with debug output
PHP_ERROR_REPORTING=E_ALL composer test:unit

# Or using Lando with environment variables
lando ssh -c "cd /app/wp-content/plugins/your-plugin && PHP_ERROR_REPORTING=E_ALL composer test:unit"

# Run a specific unit test file
vendor/bin/phpunit -c config/phpunit-unit.xml.dist tests/unit/specific-test.php

# Or using Lando
lando ssh -c "cd /app/wp-content/plugins/your-plugin && vendor/bin/phpunit -c config/phpunit-unit.xml.dist tests/unit/specific-test.php"
```

#### Integration Tests

For all examples below, first sync your plugin and navigate to the WordPress plugin directory:

```bash
# Step 1: From your plugin development directory, sync to WordPress
cd ~/sites/your-plugin/
php bin/sync-to-wp.php

# Step 2: Navigate to your plugin directory INSIDE WordPress
cd ~/sites/wordpress/wp-content/plugins/your-plugin/
```

Then run the tests:

```bash
# Run all integration tests using Composer
composer test:integration

# Or using Lando
lando test:integration

# Run integration tests with API keys
OPENAI_API_KEY=your_key composer test:integration

# Or using Lando with environment variables
lando ssh -c "cd /app/wp-content/plugins/your-plugin && OPENAI_API_KEY=your_key composer test:integration"
```

#### All Tests

For all examples below, first sync your plugin and navigate to the WordPress plugin directory:

```bash
# Step 1: From your plugin development directory, sync to WordPress
cd ~/sites/your-plugin/
php bin/sync-to-wp.php

# Step 2: Navigate to your plugin directory INSIDE WordPress
cd ~/sites/wordpress/wp-content/plugins/your-plugin/
```

Then run the tests:

```bash
# Run all tests using Composer
composer test

# Or using Lando (run each test type separately)
lando test:unit && lando test:wp-mock && lando test:integration

# Run tests with verbose output (run each test type separately)
vendor/bin/phpunit -c config/phpunit-unit.xml.dist --verbose
vendor/bin/phpunit -c config/phpunit-wp-mock.xml.dist --verbose
vendor/bin/phpunit -c config/phpunit-integration.xml.dist --verbose

# Or using Lando with verbose flag (run each test type separately)
lando ssh -c "cd /app/wp-content/plugins/your-plugin && vendor/bin/phpunit -c config/phpunit-unit.xml.dist --verbose"
lando ssh -c "cd /app/wp-content/plugins/your-plugin && vendor/bin/phpunit -c config/phpunit-wp-mock.xml.dist --verbose"
lando ssh -c "cd /app/wp-content/plugins/your-plugin && vendor/bin/phpunit -c config/phpunit-integration.xml.dist --verbose"
```

#### Running Specific Test Directories

For all examples below, first sync your plugin and navigate to the WordPress plugin directory:

```bash
# Step 1: From your plugin development directory, sync to WordPress
cd ~/sites/your-plugin/
php bin/sync-to-wp.php

# Step 2: Navigate to your plugin directory INSIDE WordPress
cd ~/sites/wordpress/wp-content/plugins/your-plugin/
```

Then run the tests:

```bash
# Run tests in a specific directory using PHPUnit directly
vendor/bin/phpunit tests/specific-directory

# Or using Lando
lando ssh -c "cd /app/wp-content/plugins/your-plugin && vendor/bin/phpunit tests/specific-directory"
```

> **Tip**: For efficiency, you can combine the commands with ` && ` to run them as a single line:
> ```bash
> cd ~/sites/your-plugin/ && php bin/sync-to-wp.php && cd ~/sites/wordpress/wp-content/plugins/your-plugin/ && composer test:unit
> ```
> Or with Lando:
> ```bash
> cd ~/sites/your-plugin/ && php bin/sync-to-wp.php && cd ~/sites/wordpress/wp-content/plugins/your-plugin/ && lando test:unit
> ```

### Available Test Commands

The framework provides several Composer scripts and Lando commands to run different types of tests:

#### Composer Commands

```
composer test              # Run all tests
composer test:unit         # Run unit tests (tests that don't require WordPress functions)
composer test:wp-mock      # Run WP Mock tests (tests that mock WordPress functions)
composer test:integration  # Run integration tests (tests that require a WordPress database)
```

#### Lando Commands

```
lando test:unit         # Run unit tests
lando test:wp-mock      # Run WP_Mock tests
lando test:mock         # Run mock tests
lando test:integration  # Run integration tests
lando test:coverage     # Generate code coverage report
```

Each command corresponds to a specific test type and automatically selects the appropriate bootstrap file and test directory. For example, `test:unit` will use the unit test bootstrap file and run tests in the `tests/unit` directory.

#### PHPUnit Options

For more advanced usage, you can run PHPUnit directly with additional options:

```bash
vendor/bin/phpunit -c config/phpunit-unit.xml.dist --filter=testSpecificMethod
vendor/bin/phpunit -c config/phpunit-unit.xml.dist --group=feature
vendor/bin/phpunit -c config/phpunit-unit.xml.dist --coverage-html build/coverage
```

#### Future Enhancements

**Test Groups**: The `--group` option is planned as a future enhancement to allow running tests for specific architectural components (like "Palette Management" or "Color Manipulation" or "User Interface"). Groups will be defined in `.env.testing` with paths to relevant test files, providing more flexibility in organizing tests by functional area.

### Environment Variables

The testing framework respects several environment variables:

- `PHPUNIT_BOOTSTRAP_TYPE`: Determines which bootstrap file to use (unit, wp-mock, integration)
- `WP_ROOT`: Path to WordPress root directory
- `PHP_ERROR_REPORTING`: PHP error reporting level
- `WP_TESTS_DIR`: Directory containing WordPress test suite
- `WP_DEVELOP_DIR`: Directory containing WordPress develop repository

Additional environment variables can be used for specific tests that require API keys or other credentials.

## Contributing

We welcome contributions to improve this testing framework and documentation. For detailed guidelines on how to contribute, please refer to the [CONTRIBUTING.md](../../CONTRIBUTING.md) file in the root of this repository.


## Multisite Testing in WordPress Plugins

### How Multisite Affects PHPUnit Tests

- **Single-site tests** will run on the main site of a multisite WordPress install, but they do not test multisite-specific features (network options, site creation, switching blogs, etc.).
- If your plugin has code that behaves differently on multisite, you must run tests with multisite enabled to cover those code paths.
- Single-site tests will usually pass on a multisite install, but will not catch multisite-specific bugs.

### When to Use `--multisite` for Different Test Types

| Test Type   | Use --multisite?   | Why/Why Not?                         |
|-------------|-------------------|--------------------------------------|
| Unit        | No (almost never)  | Unit tests should be WP-agnostic     |
| WP-Mock     | No                | WP-Mock doesn’t load real WP         |
| Integration | Yes (sometimes)    | To test real multisite behavior      |

- **Unit tests:** Only use multisite if you are testing logic that directly handles multisite-specific functionality (rare, e.g., a backup plugin that needs to handle network-wide and single-site backups). For most unit tests, multisite is not relevant, but your test case can always define constants or call functions to simulate multisite if needed.
- **WP-Mock tests:** Multisite is not relevant; WP-Mock does not load a real WordPress environment. However, your test cases can manually define multisite-related constants or mock functions to simulate multisite logic if you wish.
- **Integration tests:** Use multisite mode to test real multisite behavior, such as network options, site/user management, and blog switching. This is where `--multisite` is most useful and fully supported by the test runner.

### Command-Line Options and Defaults

- If a developer specifies only `--multisite`, the test runner (`sync-and-test.php`) automatically defaults to `--integration --multisite`.
- When running integration tests with `--multisite`, `sync-and-test.php` sets the `WP_TESTS_MULTISITE=1` environment variable (via the PHPUnit XML config), ensuring the WordPress test suite runs in multisite mode.
- `--multisite` is not meaningful for unit or WP-Mock tests unless your code specifically requires it, but your test cases can still set up multisite simulation if needed.

### Do You Need `--url` for Multisite PHPUnit Tests?

- **Usually not.**
- The WordPress test suite sets up its own test sites and domains. You only need `--url` if your tests depend on a specific domain or subsite, or if using WP-CLI (not PHPUnit).
- For most plugin integration tests, switching blogs with `switch_to_blog()` is sufficient.

### Best Practices for Multisite PHPUnit Testing

- Use a separate PHPUnit config file (e.g., `phpunit-multisite.xml.dist`) or set the `WP_TESTS_MULTISITE=1` environment variable.
- Add a `--multisite` flag to your test runner to make running multisite tests easy and consistent.
- In your test bootstrap, check for the multisite flag or env var and define multisite-related constants as needed.
- Write integration tests that use multisite-specific functions and assertions (e.g., `is_multisite()`, `get_sites()`, `switch_to_blog()`).

### Example: Enabling Multisite for Integration Tests

1. **Copy your integration config:**
   ```sh
   cp config/phpunit-integration.xml.dist config/phpunit-multisite.xml.dist
   ```
2. **Edit `phpunit-multisite.xml.dist` to include:**
   ```xml
   <php>
     <env name="WP_TESTS_MULTISITE" value="1"/>
   </php>
   ```
3. **Update your test runner:**
   - The included `sync-and-test.php` already supports the `--multisite` option and will use `phpunit-multisite.xml.dist` for integration tests if it is set. No extra configuration is needed.
   - The test bootstrap (`tests/bootstrap/bootstrap.php`) also supports multisite via the `WP_TESTS_MULTISITE` environment variable.
4. **Update your bootstrap:**
   ```php
   if (getenv('WP_TESTS_MULTISITE')) {
       define('MULTISITE', true);
       define('SUBDOMAIN_INSTALL', true); // or false, as needed
       // ...other multisite constants
   }
   ```

### Coding Standards

This project follows PSR-12 coding standards and WordPress coding standards where appropriate. When contributing code, please ensure your contributions adhere to these standards.

#### Code Quality Tools

We use several tools to maintain code quality and enforce coding standards.

Before running these tools, it's recommended to commit your current changes to git. This allows you to easily see the changes made by the automated tools and revert them if necessary.

Note: `composer run-script <command>` is more explicit about what's happening, and `composer <command>` also works. The shorthand syntax (composer phpcs) works for any script defined in your composer.json file.

- **PHP Code Beautifier and Fixer (PHPCBF)**: Automatically fixes many of the issues detected by PHPCS. It can correct formatting, spacing, and other style issues.
  - For practical formatting (avoiding minor issues): `./bin/phpcbf.sh`
  - For specific files: `./bin/phpcbf.sh path/to/file.php`
  - direct command: `composer run-script phpcbf`

- **bin/phpcbf.sh**: A practical wrapper script that runs PHPCBF with sensible exclusions, focusing on functional issues rather than minor formatting concerns.
  - Run with: `./bin/phpcbf.sh`
  - For specific files: `./bin/phpcbf.sh path/to/file.php`
  - Automatically converts spaces to tabs first
  - Excludes purely cosmetic rules that don't affect functionality

- **PHP_CodeSniffer (PHPCS)**: Detects violations of coding standards in your PHP code. It helps maintain consistent code style across the project.
  - Run with: `composer run-script phpcs`
  - See detailed errors: `composer run-script phpcs -- -s` (shows sniff codes)
  - Summary report: `composer run-script phpcs -- --report=summary`
  - Check specific file: `composer run-script phpcs -- path/to/file.php`

For detailed information about PHPCS and PHPCBF, including troubleshooting tips and configuration details, see [PHPCS-PHPCBF-Guide.md](../tools/PHPCS-PHPCBF-Guide.md).

- **PHPStan**: Performs static analysis of your code to find bugs and errors without actually running the code. It can detect type-related issues, undefined methods, unused code, and other potential problems.
  - Configured with WordPress-specific rules via szepeviktor/phpstan-wordpress
  - Run with: `composer run-script analyze`

>
> **Troubleshooting:** If you encounter errors with `trim(): Passing null to parameter #1 ($string)` when running PHPCBF with PHP 8.1+, this is due to a compatibility issue in older versions of the WordPress Coding Standards package. The solution is to upgrade to version 3.1.0 or later:
> ```bash
> composer require --dev wp-coding-standards/wpcs:^3.1 --update-with-dependencies
> ```
>
> Also, make sure to properly configure your project's prefixes in the `phpcs.xml.dist` file:
> ```xml
> <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
>     <properties>
>         <property name="prefixes" type="array">
>             <element value="YourPrefix"/>
>             <element value="your_prefix"/>
>             <element value="Your\\Namespace"/>
>         </property>
>     </properties>
> </rule>
> ```

Common issues to watch for:
- Indentation (tabs, not spaces) as per WordPress Coding Standards
- Line length (generally 100 characters max)
- Proper spacing around operators
- Proper docblock formatting
- Naming conventions

### Testing Your Contributions

Before submitting a pull request, please ensure:

1. All tests pass
2. Your code follows the project's coding standards
3. You've added tests for any new functionality
4. Documentation is updated if necessary

## Troubleshooting

### Code Quality Tools

#### PHPCS and PHPCBF Issues

**Iterative Process**: PHPCBF and PHPCS often require multiple runs. After running PHPCBF, PHPCS might still report fixable issues. Run PHPCBF again until no more automatic fixes are possible.

**Persistent Issues**: If PHPCBF reports fixing the same number of errors across multiple runs while PHPCS still reports thousands of fixable issues, try these approaches:

1. Focus on one file at a time: `composer run-script phpcbf -- path/to/specific/file.php`
2. Temporarily exclude problematic rules in phpcs.xml.dist
3. Manually fix critical issues first

**Conflicting Indentation Standards**: If you see contradictory errors like both `Tabs must be used to indent lines` and `Spaces must be used to indent lines` in the same file, you have conflicting coding standards enabled. This happens because:

1. **WordPress Coding Standards** requires tabs for indentation
2. **PSR-12** requires spaces for indentation

When both standards are enabled in phpcs.xml.dist, they conflict with each other. To resolve this:

1. Decide which standard to prioritize (for WordPress plugins, typically WordPress Coding Standards)
2. Modify your phpcs.xml.dist to exclude the conflicting rule from one standard:
   ```xml
   <rule ref="PSR12">
       <!-- Exclude PSR-12 indentation rule to avoid conflict with WordPress -->
       <exclude name="Generic.WhiteSpace.DisallowTabIndent"/>
   </rule>
   ```

**Converting Spaces to Tabs**: To quickly convert leading spaces to tabs in your PHP files (to comply with WordPress Coding Standards), use the provided composer script:

```bash
composer run-script spaces_to_tabs
```

This script finds all PHP files in the `src`, `tests`, and `templates` directories and converts any leading 4-space indentation to tabs. This is particularly useful when working with code that was originally formatted according to PSR-12 standards (which uses spaces) and needs to be converted to WordPress Coding Standards (which uses tabs).

3. Configure your editor to use tabs for PHP files. For Windsurf and Visual Studio Code:
   - Ensure "Editor: Insert Spaces" is unchecked in settings
   - "Editor: Detect Indentation" can be checked to match existing files

**WordPress Coding Standards PHP 8.1+ Compatibility**: If you encounter errors with `trim(): Passing null to parameter #1 ($string)` when running PHPCBF with PHP 8.1+, update to WordPress Coding Standards 3.1.0 or later:

```bash
composer require --dev wp-coding-standards/wpcs:^3.1 --update-with-dependencies
```

---

This tutorial is part of the GL WordPress PHPUnit Testing Framework by George Lerner.
