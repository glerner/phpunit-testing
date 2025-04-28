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

The testing framework is designed to be included as a submodule or Composer package within your plugin project:

```
phpunit-testing/                # The framework repository
├── src/                        # Source code for the testing framework
│   ├── Unit/                   # Base classes for unit tests
│   │   └── Unit_Test_Case.php
│   ├── WP_Mock/                # Base classes for WP-Mock tests
│   │   └── WP_Mock_Test_Case.php
│   └── Integration/            # Base classes for integration tests
│       └── Integration_Test_Case.php
├── config/                     # Configuration templates
│   ├── phpunit/
│   │   ├── phpunit.xml.dist
│   │   ├── phpunit-integration.xml.dist
│   │   └── phpunit-wp-mock.xml.dist
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
namespace GL\Testing\Framework\Unit;

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
namespace GL\Testing\Framework\WP_Mock;

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
namespace GL\Testing\Framework\Integration;

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
namespace GL\Testing\Framework\Unit;
namespace GL\Testing\Framework\WP_Mock;
namespace GL\Testing\Framework\Integration;
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

### Using Test Shell Script

The framework includes a shell script (`bin/test.sh`) that simplifies running tests with various options. The typical workflow involves:

1. WP_ROOT is set in the environment
2. `php bin/sync-to-wp.php` - Syncs your plugin code to the WordPress installation
3. `./bin/test.sh` - Runs tests with specified options. Use either the Lando version or else directly from your  plugin directory

#### Mock Tests (using WP_Mock)

```bash
# Run all WP-Mock tests
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && WP_ROOT=/app ./bin/test.sh --mock"
# Or directly from plugin directory:
./bin/test.sh --mock

# Run a specific WP-Mock test file
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && WP_ROOT=/app ./bin/test.sh --mock --file tests/wp-mock/specific-test.php"
# Or directly from plugin directory:
./bin/test.sh --mock --file tests/wp-mock/specific-test.php
```

#### Unit Tests

```bash
# Run all unit tests
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && WP_ROOT=/app ./bin/test.sh --unit"
# Or directly from plugin directory:
./bin/test.sh --unit

# Run unit tests with code coverage
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && WP_ROOT=/app ./bin/test.sh --unit --coverage"
# Or directly from plugin directory:
./bin/test.sh --unit --coverage

# Run unit tests with debug output
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && PHP_ERROR_REPORTING=E_ALL WP_ROOT=/app ./bin/test.sh --unit"
# Or directly from plugin directory:
PHP_ERROR_REPORTING=E_ALL ./bin/test.sh --unit

# Run a specific unit test file
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
lando ssh -c "cd /app/wp-content/plugins/your-plugin && PHP_ERROR_REPORTING=E_ALL WP_ROOT=/app ./bin/test.sh --unit --file tests/unit/specific-test.php"
# Or directly from plugin directory:
PHP_ERROR_REPORTING=E_ALL ./bin/test.sh --unit --file tests/unit/specific-test.php
```

#### Integration Tests

```bash
# Run all integration tests
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && WP_ROOT=/app ./bin/test.sh --integration"
# Or directly from plugin directory:
./bin/test.sh --integration

# Run integration tests with API keys
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && OPENAI_API_KEY=your_key WP_ROOT=/app ./bin/test.sh --integration"
# Or directly from plugin directory:
OPENAI_API_KEY=your_key ./bin/test.sh --integration
```

#### All Tests

```bash
# Run all test types
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && WP_ROOT=/app ./bin/test.sh"
# Or directly from plugin directory:
./bin/test.sh

# Run all tests with verbose output
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress
lando ssh -c "cd /app/wp-content/plugins/your-plugin && WP_ROOT=/app ./bin/test.sh" --verbose
# Or directly from plugin directory:
./bin/test.sh --verbose
```

#### Running Specific Test Directories

```bash
# Run tests in a specific directory
cd ~/sites/your-plugin/
php bin/sync-to-wp.php
cd ~/sites/wordpress/wp-content/plugins/your-plugin
lando ssh -c "vendor/bin/phpunit tests/specific-directory"
# Or directly from plugin directory:
vendor/bin/phpunit tests/specific-directory
```

> **Tip**: For efficiency, you can combine the commands with ` && ` to run them as a single line:
> ```bash
> cd ~/sites/your-plugin/ && php bin/sync-to-wp.php && cd ~/sites/wordpress && lando ssh -c "cd /app/wp-content/plugins/your-plugin && WP_ROOT=/app ./bin/test.sh --unit"
> ```

### Test Script Options

The `bin/test.sh` script supports several options to control which tests to run and how to run them:

```
Usage: ./bin/test.sh [options] [--file FILE]

Options:
  --help          Show help message
  --unit          Run unit tests (tests that don't require WordPress functions)
  --mock          Run WP Mock tests (tests that mock WordPress functions)
  --integration   Run integration tests (tests that require a WordPress database)
  --coverage      Generate code coverage report in build/coverage directory
  --file FILE     Run a specific test file instead of the entire test suite
```

Each option corresponds to a specific test type and automatically selects the appropriate bootstrap file and test directory. For example, `--unit` will use the unit test bootstrap file and run tests in the `tests/unit` directory.

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

### Coding Standards

This project follows PSR-12 coding standards and WordPress coding standards where appropriate. When contributing code, please ensure your contributions adhere to these standards.

#### Fixing Coding Standards Issues

We use PHP_CodeSniffer to enforce coding standards. You can check and fix coding standards issues with:

```bash
# Check coding standards
composer run-script phpcs

# Fix coding standards issues automatically where possible
composer run-script phpcbf
```

> **Note:** Using Composer's phpcbf command works more reliably than trying to install and configure Visual Studio Code extensions for PHP code formatting. The command-line approach ensures consistent formatting across all development environments.

Common issues to watch for:
- Indentation (4 spaces, not tabs)
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

---

This tutorial is part of the GL WordPress PHPUnit Testing Framework by George Lerner.
