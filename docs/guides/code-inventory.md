# Code Inventory - PHPUnit Testing Framework

This document provides an inventory of key functions, classes, and variables in the PHPUnit Testing Framework.

## Table of Contents

- [Test Class Naming Conventions](#test-class-naming-conventions)
- [Namespace](#namespace)
- [Exception Handling](#exception-handling)
- [Important Global Variables](#important-global-variables)
- [Functions](#functions)
  - [build_phpunit_command()](#build_phpunit_command)
  - [check_phpunit_exists()](#check_phpunit_exists)
  - [colored_message()](#colored_message)
  - [display_help() (in setup-plugin-tests.php)](#display_help-in-setup-plugin-tests)
  - [display_help() (in sync-and-test.php)](#display_help-in-sync-and-test)
  - [download_wp_tests()](#download_wp_tests)
  - [drop_test_database_and_files()](#drop_test_database_and_files)
  - [esc_cli()](#esc_cli)
  - [find_project_root()](#find_project_root)
  - [find_wordpress_root()](#find_wordpress_root)
  - [format_mysql_execution()](#format_mysql_execution)
  - [format_mysql_parameters_and_query()](#format_mysql_parameters_and_query)
  - [format_php_command()](#format_php_command)
  - [format_ssh_command()](#format_ssh_command)
  - [generate_wp_tests_config()](#generate_wp_tests_config)
  - [get_cli_value()](#get_cli_value)
  - [get_lando_info()](#get_lando_info)
  - [get_phpunit_database_settings()](#get_phpunit_database_settings)
  - [get_setting()](#get_setting)
  - [get_wp_config_value()](#get_wp_config_value)
  - [has_cli_flag()](#has_cli_flag)
  - [install_wp_test_suite()](#install_wp_test_suite)
  - [load_settings_file()](#load_settings_file)
  - [log_message()](#log_message)
  - [make_path()](#make_path)
  - [parse_lando_info()](#parse_lando_info)
  - [trim_folder_settings()](#trim_folder_settings)
- [Variables](#variables)
  - [Global Variables](#global-variables)
  - [Key Configuration Variables](#key-configuration-variables)
- [Constants](#constants)
  - [Script Constants](#script-constants)
  - [Terminal Color Constants](#terminal-color-constants)
- [Dependencies](#dependencies)
- [Bootstrap Files](#bootstrap-files)
- [Command Line in Plugins Using WP_PHPUnit_Framework](#command-line-in-plugins-using-wp_phpunit_framework)
  - [Bootstrap File Relationships](#bootstrap-file-relationships)
  - [bootstrap.php](#bootstrapphp)
  - [bootstrap-unit.php](#bootstrap-unitphp)
  - [bootstrap-wp-mock.php](#bootstrap-wp-mockphp)
  - [bootstrap-integration.php](#bootstrap-integrationphp)
- [Test Execution Scripts](#test-execution-scripts)
  - [sync-and-test.php](#sync-and-testphp)
- [Configuration Files](#configuration-files)
  - [phpunit-integration.xml](#phpunit-integrationxml)
  - [phpunit-unit.xml](#phpunit-unitxml)
  - [phpunit-wp-mock.xml](#phpunit-wp-mockxml)

>Note: several functions have been moved to `framework-functions.php` and this document still lists them in their old locations.

## WordPress + PSR Standards

### Modern Approach (Adopted)

1. **Directory Structure**:
   ```
   plugin-name/
   ├── src/                    # All PHP classes (PSR-4 autoloaded)
   │   ├── Model/            # Domain models
   │   ├── Service/          # Business logic
   │   └── Controller/       # Request handlers
   ├── tests/                # Test files (WordPress style)
   │   ├── Unit/           # Unit tests
   │   ├── Integration/     # Integration tests
   │   └── WP-Mock/        # WP-Mock tests
   ├── assets/              # CSS, JS, images (kebab-case)
   ├── templates/          # Template files (kebab-case)
   ├── composer.json       # PSR-4 autoloading config
   └── plugin-name.php      # Main plugin file (kebab-case)
   ```

2. **Naming Conventions**:

   | Type | Location | Naming Convention | Example |
   |------|----------|-------------------|---------|
   | **Class Files** | `src/` | Match class name (PascalCase) | `Journey_Questions_Model.php` |
   | **Test Files** | `tests/` | `test-{feature}.php` (kebab-case) | `test-journey-questions.php` |
   | **Main Plugin File** | Root | `plugin-name.php` (kebab-case) | `reinvent-coaching-process.php` |
   | **Assets** | `assets/` | kebab-case | `main.js`, `admin-styles.css` |
   | **Templates** | `templates/` | kebab-case | `single-journey.php` |

3. **AI Prompting Guidelines**:
   When requesting code generation, specify:
   ```
   Follow these naming conventions:
   - Class files: PSR-4 PascalCase matching class name (e.g., `Journey_Questions_Model.php`)
   - Test files: WordPress kebab-case (e.g., `test-journey-questions.php`)
   - Non-PHP files: kebab-case (e.g., `admin-styles.css`)
   ```

4. **Autoloading**:
   - PSR-4 autoloading via Composer
   - Namespaces match directory structure
   - Example: `GL_Reinvent\Model\Journey_Questions_Model` in `src/Model/Journey_Questions_Model.php`
   - **For Projects Using This Framework**:
     - Map the namespace in your project's `autoload-dev.psr-4`: `"WP_PHPUnit_Framework\\": "tests/gl-phpunit-test-framework/src/"`
     - Exclude the framework's vendor directory to prevent conflicts:
       ```json
       "autoload-dev": {
           "exclude-from-classmap": [
               "**/tests/gl-phpunit-test-framework/vendor/"
           ]
       }
       ```

### Traditional WordPress Style (Legacy)

1. **Directory Structure**:
   ```
   plugin-name/
   ├── includes/
   │   ├── class-plugin-name.php
   │   └── class-plugin-name-core.php
   └── plugin-name.php
   ```

2. **Loading**:
   - Manual file includes/requires
   - No namespacing
   - Global functions and classes

### Test Framework Structure

```
framework-root/
├── src/                    # Production code (autoload)
│   ├── Unit/              # Base test cases for users
│   ├── WP_Mock/           # WP_Mock test utilities
│   └── ...                # Other framework components
└── tests/                 # Tests for the framework itself (autoload-dev)
    └── Framework/        # Framework's own test suite
        ├── Unit/         # Unit tests for framework components
        ├── WP_Mock/      # Tests for WP_Mock integration
        └── Integration/  # Integration tests
```

- `autoload`: Classes that users extend/use in their tests
- `autoload-dev`: Tests of the framework itself

## Test Class Naming Conventions

Test classes must follow these naming conventions:

1. **Test Class Prefix**: All test classes must be prefixed with `Test_`
   ```php
   // Correct:
   class Test_Journey_Questions_Model extends Unit_Test_Case

   // Incorrect:
   class Journey_Questions_Model_Test extends Unit_Test_Case
   ```

## Test Framework File and Directory Conventions

The test framework follows PHPUnit's standard file and directory naming conventions rather than WordPress conventions for the following components:

### Directory Structure
```
src/
├── Integration/      # Integration test base classes
├── Stubs/           # Mock and stub classes
├── Unit/            # Unit test base classes
└── WP_Mock/         # WP_Mock test base classes
```

### File Naming
- Base test classes use `PascalCase` (e.g., `Unit_Test_Case.php`)
- Stub classes use `PascalCase` (e.g., `WP_UnitTestCase.php`)
- Test files use `kebab-case` (e.g., `test-journey-questions-model.php`)

### Rationale
This structure was chosen because:
1. It aligns with PHPUnit's conventions that most PHP developers are familiar with
2. The test framework is a standalone component that may be used outside WordPress
3. It maintains consistency with PHP ecosystem standards

Note: This is an exception to WordPress naming conventions and only applies to the test framework's internal structure. Your actual test files should still follow WordPress naming conventions.


2. **File Naming**: Test files should be named to match their class name in lowercase with hyphens:
   ```
   test-journey-questions-model.php  // Contains Test_Journey_Questions_Model
   ```

3. **Test Method Naming**: Test methods should be descriptive and use snake_case:
   ```php
   public function test_get_phase_description_returns_expected_structure()
   ```

4. **Namespaces**: Tests should use the appropriate namespace based on their test type:
   ```php
   // For Unit tests (isolated tests without WordPress dependencies)
   namespace WP_PHPUnit_Framework\Unit;

   // For Integration tests (tests that interact with WordPress core)
   namespace WP_PHPUnit_Framework\Integration;

   // For WP_Mock tests (tests that mock WordPress functions)
   namespace WP_PHPUnit_Framework\WP_Mock;
   ```

   The base namespace `WP_PHPUnit_Framework` should be used for all test types, with the appropriate sub-namespace indicating the test type.

## Namespace

The framework uses the following namespace:

```php
namespace WP_PHPUnit_Framework;
```

### Namespace Conventions

1. **Global Classes**: WordPress and PHP core classes within the namespace must be prefixed with a backslash to indicate they're from the global namespace:

   ```php
   \Throwable    // Correct reference to global Throwable class
   \Exception    // Correct reference to global Exception class
   \WP_Mock      // Correct reference to global WP_Mock class
   ```

2. **Namespace References**: Use the "fully qualified from current namespace" approach for clarity:

   ```php
   // If you're in this namespace:
   namespace WP_PHPUnit_Framework\Tests;

   // You can reference a child namespace like this:
   use Unit\Test_Case; // Refers to WP_PHPUnit_Framework\Tests\Unit\Test_Case
   ```

3. **PSR-4 Compliance**: Namespaces should match directory structure and follow PSR-4 conventions.

## Exception Handling

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
set_exception_handler(function(\Throwable $e) {...});
```

**Parameters**:
- `$e`: The uncaught exception or error
  - Type: \Throwable
  - Required: Yes
  - Automatically provided by PHP when an uncaught exception occurs

**Purpose**: Provides a global exception handler to catch and display any uncaught exceptions in a user-friendly format with color coding.

**Dependencies**:
- Color constants: COLOR_RED, COLOR_RESET
- PHP's set_exception_handler() function

**Available Variables Within Handler**:
- `$e->getMessage()`: The exception message
- `$e->getFile()`: File where the exception occurred
- `$e->getLine()`: Line number where the exception occurred
- `$e->getTraceAsString()`: Full stack trace as a string

**Behavior**:
1. Catches any uncaught exceptions (\Throwable)
2. Displays the exception class name, message, file, and line number in red
3. Shows the full stack trace
4. Exits with error code 1

## Important Global Variables

### `$loaded_settings`

**Location**: `/bin/setup-plugin-tests.php`

**Purpose**: Stores all settings loaded from the .env.testing file as key-value pairs.

**Usage**: Used by the get_setting() function to retrieve configuration values.

### `$plugin_dir` and `$plugin_slug`

**Location**: `/bin/setup-plugin-tests.php`

**Purpose**:
- `$plugin_dir`: Contains the absolute path to the plugin directory
- `$plugin_slug`: Contains the basename of the plugin directory

**Usage**: Used throughout the script to reference the plugin being tested and to set proper permissions.

### `$wp_root` and `$filesystem_wp_root`

**Location**: `/bin/setup-plugin-tests.php`

**Purpose**:
- `$wp_root`: Path to WordPress root in the container (e.g., '/app')
- `$filesystem_wp_root`: Path to WordPress root on the host filesystem (e.g., '/home/user/wordpress')

**Usage**: Used to properly reference WordPress files in different contexts (container vs. host).

### `$ssh_command`

**Location**: `/bin/setup-plugin-tests.php`

**Purpose**: Stores the SSH command configuration for database operations.

**Common Values**:
- `'lando ssh'`: Used in Lando environments
- `'none'`: Used for direct local connections
- `'ssh user@host'`: Used for remote SSH connections

**Usage**: Used by format_mysql_execution() to determine how to execute MySQL commands.

## Command Line Arguments Handling

**Location**: `/bin/setup-plugin-tests.php`

**Implementation**:
```php
// Parse command line arguments
$remove_all = false;
$show_help = false;

foreach ($argv as $arg) {
    if ($arg === '--remove-all' || $arg === '--remove') {
        $remove_all = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        $show_help = true;
    }
}
```

**Supported Arguments**:
- `--help`, `-h`: Display help information
- `--remove-all`, `--remove`: Remove test database and files

**Behavior**:
1. Iterates through command line arguments in $argv
2. Sets appropriate flags based on the arguments
3. Takes different actions based on the flags:
   - If $show_help is true, displays help and exits
   - If $remove_all is true, removes the test suite instead of installing it

## Functions

### `get_database_settings()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function get_database_settings(
    string $wp_config_path,
    array $lando_info = array(),
    string $config_file_name = '.env.testing'
): array
```

**Purpose**: Retrieves the primary WordPress database settings. Its output is passed to `get_phpunit_database_settings()`.

### `is_lando_environment()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function is_lando_environment(): bool
```

**Purpose**: Checks if the script is running inside a Lando container or if Lando is active on the host.

### `get_lando_info()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework\Bin;

function get_lando_info(): array
```

**Purpose**: Retrieves Lando service information (e.g., database credentials) by executing the `lando info` command on the host machine and parsing its JSON output. This function is essential for connecting to the database in a Lando environment from an external script.

**Related**: `parse_lando_info()`

**Parameters**: None

**Return Value**:
- Type: `array`
- An associative array of the Lando configuration, or an empty array if Lando is not running or the configuration cannot be parsed.

**Logic Flow**:
1.  Checks if a Lando environment is running by calling `is_lando_environment()`. If not, it displays a message and returns an empty array.
2.  Executes `lando info --format=json` to get the configuration details.

### `check_phpunit_exists()`

**Location**: `/bin/sync-and-test.php`

**Signature**:
```php
function check_phpunit_exists(string $test_run_path, string $your_plugin_dest, bool $targeting_lando): void
```

**Purpose**: Verifies that the PHPUnit executable exists in the testing environment's `vendor/bin` directory before attempting to run tests. If the executable is missing, it prints a detailed, environment-aware error message with instructions on how to run `composer install` and then exits the script. This function is crucial for providing clear user guidance in complex environments like Lando.

**Parameters**:
- `$test_run_path`: The path to the plugin's test directory *inside the container* (e.g., `/app/wp-content/plugins/YOURPLUGIN/tests`). Used for generating the user-facing error message.
- `$your_plugin_dest`: The path to the plugin's directory *on the host filesystem* (e.g., `/home/YOURNAME/sites/wordpress/wp-content/plugins/gl-reinvent`). Used for the `file_exists()` check.
- `$targeting_lando`: A boolean indicating if the script is running in a Lando context. Used to tailor the error message.

**Dependencies**:
- `colored_message()`
- `get_setting()`

**Behavior**:
1. Constructs the host-specific path to the PHPUnit executable.
2. Uses `file_exists()` to check for the executable on the host filesystem.
3. If the file is not found, it prints a multi-line, colored error message.
4. The error message provides the exact `cd` command and path needed to run `composer install` inside the Lando container, or provides the host path for non-Lando environments.
5. Exits the script with status code 1.
3.  If the command fails or returns an empty result, it displays an error and returns an empty array.
4.  Parses the JSON output. If parsing fails, it displays an error and returns an empty array.
5.  On success, returns the parsed Lando configuration as an associative array.

### `get_phpunit_database_settings()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function get_phpunit_database_settings( array $wp_db_settings, ?string $db_name = null, ?string $table_prefix = null ): array
```

**Purpose**: Adapts the main WordPress database settings for the PHPUnit test environment, allowing for a separate test database and table prefix to ensure test isolation.

**Parameters**:
- Uses global variables:
  - `$filesystem_wp_root`: Path to WordPress root on the host filesystem
  - `$wp_root`: Path to WordPress root in the container

**Return Value**: None (exits script on failure)

**Error Handling**:
- Displays clear error message showing both container and filesystem paths
- Exits with non-zero status code (1) on validation failure

**Logic Flow**:
1. Use `$filesystem_wp_root` for validation path
2. Check for existence of wp-includes, wp-admin, and wp-content directories
3. If any are missing, display error message with both container and filesystem paths
4. Exit with error code if validation fails
5. Display success message with both container and filesystem paths if validation passes


### `get_phpunit_database_settings()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function get_phpunit_database_settings(
    array $wp_db_settings,
    ?string $test_db_name = null,
    ?string $test_table_prefix = null
): array
```

**Purpose**: Configures PHPUnit database settings based on WordPress database settings. Allows specifying a custom database name and table prefix for tests.

**Important Design Considerations**:
- Uses the same database credentials (host, user, password) as the WordPress installation
- Always uses a separate database from WordPress to enable clean deletion of test data
- Supports custom table prefixes to allow multiple plugins to be tested within the same WordPress installation

**Parameters**:
- `$wp_db_settings`: WordPress database settings
  - Type: array
  - Required: Yes
  - Source: Must be the result from get_database_settings() function
  - Contains: db_host, db_user, db_pass, db_name, table_prefix

- `$test_db_name`: Complete database name for tests
  - Type: string|null
  - Required: No
  - Default: null (will use WordPress database name + '_test')
  - Example: 'wordpress_phpunit'

- `$test_table_prefix`: Table prefix for tests
  - Type: string|null
  - Required: No
  - Default: null (will use WordPress table prefix)
  - Example: 'wptests_'

**Return Value**:
- Type: array
- PHPUnit database settings with keys:
  - `db_host`: Database host (same as WordPress)
  - `db_user`: Database username (same as WordPress)
  - `db_pass`: Database password (same as WordPress)
  - `db_name`: Test database name
  - `table_prefix`: Table prefix for tests

**Logic Flow**:
1. Start with WordPress database settings
2. Set database name (use provided name or append '_test' to WordPress database name)
3. Set table prefix (use provided prefix or keep WordPress table prefix)
4. Display the configured settings
5. Return the PHPUnit database settings


### `install_test_suite()`

**Location**: `/bin/setup-plugin-tests.php`

**Git Repository Used**:
- Uses `https://github.com/WordPress/wordpress-develop.git` for downloading test suite
- Custom approach rather than using the default WP-CLI installation script
- Allows for better control over the installation process and compatibility with different environments

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function install_test_suite(
    string $wp_tests_dir,
    string $db_name,
    string $db_user,
    string $db_pass,
    string $db_host
): bool
```

**Purpose**: Installs the WordPress test suite by creating the test database, configuring the test environment, and running the WordPress test installer script.

**Requirements**:
- Requires `get_setting('WP_ROOT')` to retrieve the container path when running in Lando
- Requires `get_setting('FILESYSTEM_WP_ROOT')` for filesystem paths when not in Lando
- Requires `$targeting_lando` variable to determine if running in a Lando environment
- MySQL command-line client must be available in the environment

**Parameters**:
- `$wp_tests_dir`: Directory where tests are installed
  - Type: string
  - Required: Yes
  - Example: '/path/to/wordpress/wp-content/plugins/wordpress-develop/tests/phpunit'

- `$db_name`: Database name for tests
  - Type: string
  - Required: Yes
  - Example: 'wordpress_test'

- `$db_user`: Database username
  - Type: string
  - Required: Yes
  - Example: 'wordpress'

- `$db_pass`: Database password
  - Type: string
  - Required: Yes

- `$db_host`: Database host
  - Type: string
  - Required: Yes
  - Example: 'localhost'

**Return Value**:
- Type: bool
- True if installation was successful, false otherwise

**Error Handling**:
- Checks if MySQL command is available
- Validates database connection before proceeding
- Verifies that test files exist after installation
- Provides detailed debug output for troubleshooting

**Logic Flow**:
1. Verify database connection
2. Create test database if it doesn't exist
3. Generate WordPress test installation script
4. Execute installation script using appropriate PHP command (local or Lando)
5. Clean up temporary files
6. Return success/failure status

**Important Design Considerations**:
- Path handling strategy:
  - For filesystem operations (creating directories, writing files), use filesystem paths
  - For operations inside Lando (database access, PHP execution), use container paths
- Handles both local and Lando environments appropriately
  - When using a Lando WordPress setup, must run the PHPUnit test installation script with `lando php` (not `php`), because that script accesses databases that are defined in the Lando container
- Database operations:
  - Creates a separate test database if it doesn't exist
  - Validates database connection before attempting installation
  - Uses the same database credentials as WordPress but with a different database name
- Permissions handling:
  - Detects Lando environment using `exec('which lando')`
  - Sets appropriate permissions for plugin files in Lando using `lando ssh` command
  - Uses direct container path for permissions: `/app/wp-content/plugins/$plugin_slug`
- Provides extensive debugging information for troubleshooting


### `display_help()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
function display_help(): void
```

**Purpose**: Displays a detailed help message for the `setup-plugin-tests.php` script. It outlines the script's purpose, usage, and available command-line options.

**Logic Flow**:
1.  Prints a header for the setup script.
2.  Shows the basic usage format: `php setup-plugin-tests.php [options]`.
3.  Lists and describes the available options:
    -   `--help display this help message.
    -   `--remove-all`, `--remove`: Remove test database and files.
4.  Provides a general description of what the script does (sets up the WP testing environment).
5.  Explains that configuration is loaded from `.env.testing`.

**Context**: This function is called when the user passes the `--help` or `-h` argument to the setup script. It provides all the necessary information for a user to understand and operate the initial test environment setup.


### `print_usage()`

**Location**: `/bin/sync-and-test.php` (in consuming projects like `reinvent`)

**Signature**:
```php
function print_usage(): void
```

**Purpose**: Displays a detailed usage message for the `sync-and-test.php` script, which is responsible for running the various PHPUnit test suites.

**Logic Flow**:
1.  Prints the basic usage format: `php sync-and-test.php [options] [--file=<file>]`.
2.  Lists and describes the different test types that can be run:
    -   `--unit`: Runs unit tests.
    -   `--wp-mock`: Runs tests using WP Mock.
    -   `--integration`: Runs integration tests with a full WordPress environment.
    -   `--all`: Runs all test types.
3.  Lists and describes other available options:
    -   `--file=<file>`: Run a specific test file.
    -   `--coverage`: Generate an HTML code coverage report.
    -   `--verbose`: Show detailed output.
    -   `--help`: Show this help message.
4.  Lists relevant environment variables that can be configured (`WP_TESTS_DIR`, `WP_ROOT`, `TEST_FRAMEWORK_DIR`).

**Context**: This function is typically called when the user passes the `--help` argument to the test runner script. While not part of the core framework files in `phpunit-testing`, it is a critical function in the scripts that *use* the framework, and is documented here as a canonical example.


### `drop_test_database_and_files()`

**Location**: `/bin/setup-plugin-tests.php` (implemented as `remove_test_suite`)

**Signature**:
```php
function remove_test_suite(
    string $wp_tests_dir,
    string $db_name,
    string $db_host,
    string $ssh_command = ''
): bool
```

**Purpose**: Cleans up the test environment by dropping the test database and deleting the WordPress test suite files. It is invoked via the `--remove-all` or `--remove` command-line arguments.

**Dependencies**:
- `format_mysql_execution()`

### `format_mysql_parameters_and_query()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework\Bin;

function format_mysql_parameters_and_query(
    string $host,
    string $user,
    string $pass,
    string $sql,
    ?string $db = null,
    string $command_type = 'direct'
): string
```

**Purpose**: Assembles the core `mysql` client command arguments, including connection parameters and the SQL query. It handles environment-specific escaping of the SQL string but does **not** add the `mysql` or `lando mysql` executable prefix.

**Important Distinction**:
- This is a helper function for `format_mysql_execution()`.
- It returns only the arguments, like: `-h host -u user -ppassword -e 'SELECT 1;'`
- It does **not** return the full command, like: `mysql -h host ...`

**Parameters**:
- `$host`, `$user`, `$pass`, `$sql`, `$db`: Standard MySQL connection and query parameters.
- `$command_type`: The target execution environment (`lando_direct`, `ssh`, or `direct`). This dictates how the SQL string is escaped.

**Return Value**:
- Type: `string`
- A string of `mysql` client arguments ready to be passed to an executable.

**Logic Flow**:
1.  Builds the connection parameter string (`-h`, `-u`, `-p`, and optional database name).
2.  Normalizes the SQL string by trimming it and ensuring it ends with a semicolon.
3.  Escapes quotes within the SQL string based on the `$command_type`:
    -   `lando_direct`: Escapes only single quotes (`'`).
    -   `ssh` or `direct`: Escapes both single (`'`) and double (`"`) quotes.
4.  Combines the connection parameters and the escaped SQL into the final argument string, using the `-e` flag for execution.

**Special Handling**:
- Handles different SQL quote escaping rules required for `lando`, `ssh`, and direct `mysql` commands.
- Does not add a space after the `-p` password parameter, as required by `mysql`.

### `format_mysql_execution()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework\Bin;
function format_mysql_execution(
    string $ssh_command,
    string $host,
    string $user,
    string $pass,
    string $sql,
    ?string $db = null
): string
```

**Purpose**: A high-level wrapper that constructs the complete, executable shell command for running a MySQL query. It correctly formats the command for different target environments (Lando, remote SSH, or direct local execution).

**Parameters**:
- `$ssh_command`: The SSH command string (e.g., 'lando ssh', 'ssh user@host', or an empty string for local execution).
- `$host`: Database host.
- `$user`: Database user.
- `$pass`: Database password.
- `$sql`: The SQL query to execute.
- `$db`: (Optional) The specific database to use.

**Return Value**:
- Type: `string`
- The full shell command ready for execution (e.g., `lando mysql -h...` or `ssh user@host 'mysql -h...'`).

**Dependencies & Call Flow**:
- This function determines the execution environment (`direct`, `ssh`, `lando`) and then calls other functions to build the command.
- **`format_mysql_parameters_and_query()`**: Called to format the core `mysql ...` arguments and handle SQL escaping appropriate for the environment.
- **`format_ssh_command()`**: If an SSH connection is needed, this function is used to wrap the `mysql` command for remote execution.





### `load_settings_file()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function load_settings_file(): array
```

**Purpose**: Loads settings from the `.env.testing` file in the project root directory.

**Parameters**: None (currently hardcoded to use `.env.testing`)

**Return Value**:
- Type: array
- Contains all settings loaded from the .env.testing file as key-value pairs

**Dependencies**:
- PROJECT_DIR constant
- Color constants: COLOR_CYAN, COLOR_RESET for output formatting

**Logic Flow**:
1. Determine the path to the .env.testing file in the project root
2. Check if the file exists
3. Read the file line by line, skipping comments (lines starting with #)
5. Parse lines containing '=' into key-value pairs
6. Remove quotes from values if present
7. Store settings in an array
8. Return the settings array

**Limitations**:
- Currently hardcodes the path to `.env.testing` in the project root
- Cannot load settings from a different file or location

**Suggested Improvement**:
```php
function load_settings_file(?string $env_file = null): array {
    $env_file = $env_file ?? PROJECT_DIR . '/.env.testing';
    // Rest of the function remains the same
}
```

### `format_ssh_command()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function format_ssh_command(string $ssh_command, string $command): string
```

**Purpose**: Wraps a command in the appropriate format for execution over SSH, based on the SSH_COMMAND setting, handling special cases like Lando.

**Parameters**:
- `$ssh_command`: The base SSH connection command (e.g., `lando ssh` or `ssh user@host`).
- `$command`: The command to be executed on the remote server.

**Return Value**:
- A single string ready for execution, with the remote command properly quoted and error output redirected to stdout (`2>&1`).

**Essential Note**:
- It specifically checks for `lando ssh` and uses the required `-c` flag for command execution in that environment.
2. For Lando SSH, format as: `lando ssh -c '  command  ' 2>&1`
3. For regular SSH, format as: `ssh_command '  command  ' 2>&1`

### `is_lando_environment()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
function is_lando_environment(?string $command = null): bool
```

**Purpose**: Determines if the current environment is a Lando environment or if a specified command is a Lando command.

**Parameters**:
- `$command`: Optional command to check if it's a Lando command
  - Type: string|null
  - Required: No (default: null)
  - Example: 'lando php', 'lando exec appserver'

**Return Value**: Boolean indicating whether the environment is a Lando environment or the command is a Lando command.


### `format_php_command()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework\Bin;

function format_php_command( string $php_script_path, array $arguments = [], string $command_type = 'auto' ): string
```

**Purpose**: Formats a full PHP command string, correctly wrapping it for different execution environments like Lando, Docker, or direct local execution.

**Parameters**:
- `$php_script_path`: The absolute path to the PHP script to execute.
- `$arguments`: An associative or indexed array of arguments to pass to the script.
  - *Positional*: `['value1', 'value2']` becomes `"value1" "value2"`
  - *Named*: `['name' => 'value']` becomes `--name="value"`
- `$command_type`: The target environment. Can be `auto`, `direct`, `docker`, `lando_php`, or `lando_exec`.

**Return Value**:
- Type: `string`
- The fully formatted and escaped command string ready for execution.

**Logic Flow**:
1.  **Environment Detection**: If `$command_type` is `auto`, it checks for a `/.dockerenv` file to determine if it's in a Docker container. It defaults to `docker` if found, otherwise `direct`.
2.  **Command Wrapping**: It prepends the correct executable based on `$command_type`:
    -   `lando_php`: `lando php "..."`
    -   `lando_exec`: `lando exec appserver -- php "..."`
    -   `docker` or `direct`: `php "..."`
3.  **Argument Formatting**: It iterates through the `$arguments` array, formatting them as either positional (`"value"`) or named (`--key="value"`) arguments.


### `check_system_requirements()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function check_system_requirements(): bool
```

**Purpose**: Checks if the system meets the requirements for running the WordPress plugin tests setup script. Verifies that necessary command-line tools are available.

**Parameters**: None

**Return Value**:
- Type: bool
- True if all requirements are met, false otherwise

**Logic Flow**:
1. Check if git is available using `exec('which git')`
2. If git is not found, display error and return false
3. Check if mysql client is available using `exec('which mysql')`
4. If mysql client is not found, display error and return false
5. If all checks pass, display success message and return true

**Error Handling**:
- Returns false if any required tool is missing
- Displays specific error messages for each missing requirement

### `parse_lando_info()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework\Bin;

function parse_lando_info(): ?array
```

**Purpose**: Parses the LANDO_INFO environment variable to retrieve Lando configuration. This function is only used when running inside a Lando container.

**Related**: get_lando_info()

**Parameters**: None

**Return Value**:
- Type: array|null
- Lando configuration as an array if successful, null if not in a Lando environment or if parsing fails

**Logic Flow**:
1. Attempts to read the LANDO_INFO environment variable (does not execute 'lando info' command)
2. If empty, return null (not in a Lando container)
3. Parse the JSON data from the environment variable
4. If JSON parsing fails, display warning and return null
5. Return the parsed Lando configuration

**Important Note**:
- This function relies on the LANDO_INFO environment variable which is only available inside Lando containers
- It's different from get_lando_info() which executes the 'lando info' command and works from outside containers
- LANDO_INFO environment variable only exists inside Lando containers or when using 'lando ssh'


**Command Line Options Documented**:
- `--help, -h`: Display the help message
- `--remove-all, --remove`: Remove test database and files

### `get_setting()`

**Locations**:
- `/bin/framework-functions.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function get_setting(string $name, mixed $default = null): mixed
```

**Purpose**: Retrieves a setting from environment variables or loaded settings array, with environment variables taking precedence.

**Parameters**:
- `$name`: Name of the setting to retrieve
  - Type: string
  - Required: Yes
  - Example: 'WP_TESTS_DIR'

- `$default`: Default value if setting is not found
  - Type: mixed
  - Required: No

**Behavior**:
1. First checks environment variables (highest priority)
2. Then checks settings loaded from .env.testing
3. Finally falls back to the provided default value

The function returns the first value it finds, following this priority order.

**Usage in bootstrap-integration.php**:
The bootstrap-integration.php file uses this function to get the WP_TESTS_DIR setting:
```php
$wp_tests_dir = get_setting('WP_TESTS_DIR');
```
This ensures that WP_TESTS_DIR is properly loaded from .env.testing or from environment variables if explicitly set.
  - Default: null
  - Example: 'localhost' or '[none]'

**Return Value**:
- Type: mixed
- The setting value from environment or loaded settings, or the specified default if not found

**Dependencies**:
- Uses global `$loaded_settings` array

**Logic Flow**:
1. Check if setting exists as environment variable
2. If found, return environment variable value
3. If not found in environment, check loaded settings array
4. If found in loaded settings, return that value
5. Otherwise return default value

## Variables

### Global Variables

- `$loaded_settings`: Array containing settings loaded from configuration file

### Key Configuration Variables

- `WP_ROOT`: Path to WordPress root directory
- `FILESYSTEM_WP_ROOT`: Path to WordPress root on the host filesystem
- `WP_TESTS_DB_HOST`: Database host for tests
- `WP_TESTS_DB_USER`: Database username for tests
- `WP_TESTS_DB_PASSWORD`: Database password for tests
- `WP_TESTS_DB_NAME`: Database name for tests
- `SSH_COMMAND`: SSH command for remote operations

## Constants

### Script Constants
- `SCRIPT_DIR`: Directory containing the setup script
- `PROJECT_DIR`: Root directory of the plugin project

### Terminal Color Constants
- `COLOR_RESET`: Reset terminal color ("\033[0m")
- `COLOR_RED`: Red text ("\033[31m")
- `COLOR_GREEN`: Green text ("\033[32m")
- `COLOR_YELLOW`: Yellow text ("\033[33m")
- `COLOR_BLUE`: Blue text ("\033[34m")
- `COLOR_MAGENTA`: Magenta text ("\033[35m")
- `COLOR_CYAN`: Cyan text ("\033[36m")
- `COLOR_WHITE`: White text ("\033[37m")
- `COLOR_BOLD`: Bold text ("\033[1m")

## Dependencies

- The script relies on WordPress core files being present at the specified root path
- Requires a valid wp-config.php file or alternative configuration sources

### `esc_cli()`

**Location**: `/bin/framework-functions.php`

Meant as a helper function to colored_message, not to be used directly often.

**Signature**:
```php
function esc_cli(string $text): string
```

**Purpose**: Escapes a string for CLI output. Currently returns the text unchanged, but provides a consistent API for future CLI output escaping if needed.

**Parameters**:
- `$text`: Text to escape
  - Type: string
  - Required: Yes
  - Example: 'Command output text'

**Return Value**: The escaped string (currently unchanged from input)

### `find_wordpress_root()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function find_wordpress_root(string $current_dir, int $max_depth = 5): ?string
```

**Purpose**: Finds the WordPress root directory by looking for wp-config.php file, starting from the current directory and traversing up to a specified maximum depth.

**Parameters**:
- `$current_dir`: Starting directory to search from
  - Type: string
  - Required: Yes
  - Example: '/home/user/sites/wordpress-site/wp-content/plugins/my-plugin'

- `$max_depth`: Maximum directory depth to search upward
  - Type: int
  - Required: No (default: 5)
  - Example: 3

**Return Value**:
- Type: string|null
- WordPress root path if found, null if not found within the specified depth

### `find_project_root()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function find_project_root(string $start_dir, string $marker = 'composer.json'): ?string
```

**Purpose**: Finds the project's root directory by searching from a starting directory towards the filesystem root, for a specific marker file or directory.

**Relationship with Other Functions**:
- This is a general-purpose utility function, similar to `find_wordpress_root()`, but it looks for a generic marker (`composer.json` by default) instead of a specific file like `wp-config.php`.

**Parameters**:
- `$start_dir`: The directory to begin the search from.
  - Type: string
  - Required: Yes
- `$marker`: The file or directory name to search for.
  - Type: string
  - Required: No (default: 'composer.json')

**Return Value**:
- Type: string|null
- The path to the root directory if the marker is found, otherwise `null`.



### `get_wp_config_value()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
function get_wp_config_value(string $search_value, string $wp_config_path): ?string
```

**Purpose**: Extracts a specific configuration value from the WordPress wp-config.php file using regex pattern matching.

**Parameters**:
- `$search_value`: Config constant name to search for
  - Type: string
  - Required: Yes
  - Example: 'DB_NAME', 'DB_USER', 'DB_PASSWORD'

- `$wp_config_path`: Path to wp-config.php file
  - Type: string
  - Required: Yes
  - Example: '/var/www/html/wp-config.php'

**Return Value**:
- Type: string|null
- The extracted configuration value if found, null otherwise

**Logic Flow**:
1. Check if wp-config.php exists at the specified path
2. Read the file contents
3. Use regex to find the define statement for the requested constant
4. Extract and return the value if found
5. Return null if not found

### `has_cli_flag()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function has_cli_flag(string|array $flags, ?array $source_argv = null): bool
```

**Purpose**: Checks if a specific flag (or one of a list of flags) exists in the command-line arguments.

**Parameters**:
- `$flags`: A single flag (e.g., `--help`) or an array of possible flags (e.g., `['--help', '-h']`).
- `$source_argv`: (Optional) An array of arguments to check. Defaults to the global `$argv`.

**Return Value**:
- `true` if any of the specified flags are found, `false` otherwise.

### `download_wp_tests()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
function download_wp_tests(string $wp_tests_dir): bool
```

**Purpose**: Downloads and sets up the WordPress test suite from the WordPress develop GitHub repository.

**Parameters**:
- `$wp_tests_dir`: Directory where tests will be installed
  - Type: string
  - Required: Yes
  - Example: '/path/to/wordpress/wp-content/plugins/my-plugin/tests/phpunit'

**Return Value**:
- Type: bool
- True if installation was successful, false otherwise

**Logic Flow**:
1. Create tests directory if it doesn't exist
2. Check if test suite is already installed
3. Create temporary directory for downloading
4. Clone WordPress develop repository
5. Copy test files to the target directory
6. Clean up temporary files
7. Verify installation was successful

**Error Handling**:
- Returns false if directory creation fails
- Returns false if Git clone fails
- Returns false if file copying fails
- Returns false if required test files are missing after installation

### `generate_wp_tests_config()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function generate_wp_tests_config(
    string $wp_tests_dir,
    string $wp_root,
    string $db_name,
    string $db_user,
    string $db_pass,
    string $db_host,
    string $plugin_dir
): bool
```

**Purpose**: Generates the `wp-tests-config.php` file required by the WordPress test suite. It uses a heredoc to create the file content with the specified database and path settings, then writes it to the appropriate directory.

**Parameters**:
- `$wp_tests_dir`: The directory where the WordPress tests are installed.
- `$wp_root`: The path to the WordPress codebase being tested.
- `$db_name`, `$db_user`, `$db_pass`, `$db_host`: Standard database connection details.
- `$plugin_dir`: The directory of the plugin being tested.

**Return Value**:
- Type: `bool`
- Returns `true` on success, `false` on failure.


### `get_cli_value()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function get_cli_value(string|array $flags, ?array $source_argv = null): ?string
```

**Purpose**: Gets the value of a command-line argument. Supports both `--option=value` and `--option value` formats.

**Parameters**:
- `$flags`: A single flag or an array of aliases (e.g., `['--user', '-u']`).
- `$source_argv`: (Optional) An array of arguments to check. Defaults to the global `$argv`.

**Return Value**:
- The value of the argument if found. Returns an empty string if the flag is present but has no value, and `null` if the flag is not found.

## Command Line in Plugins Using WP_PHPUnit_Framework

Some scripts in the framework's `bin` directory are intended to be run directly from the framework's path within your project, not copied into your project's root `bin` directory.

### `setup-plugin-tests.php`

This script should be executed from its location within the `tests/gl-phpunit-test-framework/bin/` directory. 

**Example Usage:**
```bash
php tests/gl-phpunit-test-framework/bin/setup-plugin-tests.php
```

> **Note:** Do *not* copy this file to your plugin's `bin` directory. The `update-framework.php` script is responsible for copying all other necessary `bin` files.

## Bootstrap Files

### Bootstrap File Relationships

**Overview**: The bootstrap files work together to create isolated test environments for each test type (unit, wp-mock, integration). Each test type has its own bootstrap file with specific requirements and dependencies.

**Note**: PHPUnit will use `*.xml` files if they exist, falling back to `*.xml.dist` files. This allows for local configuration overrides.

**Execution Flow**:
1. PHPUnit configuration files (phpunit-unit.xml.dist, phpunit-wp-mock.xml.dist, phpunit-integration.xml.dist) set the PHPUNIT_BOOTSTRAP_TYPE environment variable. Note: User can override with *.xml instead of *.xml.dist.
2. All test types load bootstrap.php as their entry point
3. bootstrap.php loads settings from .env.testing and provides the get_setting function
4. bootstrap.php then loads the specific bootstrap file based on the test type
5. Each specific bootstrap file sets up the environment for its test type

**Key Design Principles**:
- Each test type runs in its own isolated environment
- Settings from .env.testing are loaded in bootstrap.php and made available to all test types
- Explicit environment variables take precedence over .env.testing settings
- Test types should be run sequentially, not simultaneously

### `bootstrap.php`

**Location**: `/tests/bootstrap/bootstrap.php`

**Purpose**: Main bootstrap file for the GL WordPress Testing Framework. Serves as the entry point for all test types and handles common initialization tasks.

**Functionality**:
- Loads Composer autoloader
- Registers framework PSR-4 prefixes
- Sets up error reporting
- Loads settings from .env.testing
- Provides the get_setting function for accessing settings
- Loads specific bootstrap file based on test type (unit, wp-mock, or integration)

**Test Type Selection**:
- Uses `get_setting('PHPUNIT_BOOTSTRAP_TYPE', 'unit')` to determine which specific bootstrap file to load
- Defaults to 'unit' if not specified

**Dependencies**:
- Requires a valid Composer autoloader
- Expects .env.testing file to exist (but will work without it)

### `bootstrap-unit.php`

**Location**: `/tests/bootstrap/bootstrap-unit.php`

**Purpose**: Handles initialization of testing environment for unit tests. Sets up Mockery and other dependencies for isolated unit testing.

**Functionality**:
- Initializes Mockery
- Sets up Brain\Monkey if available
- Registers shutdown functions for proper teardown

**Dependencies**:
- Requires bootstrap.php to be loaded first
- Requires Mockery to be installed
- Optionally uses Brain\Monkey if available

### `bootstrap-wp-mock.php`

**Location**: `/tests/bootstrap/bootstrap-wp-mock.php`

**Purpose**: Handles initialization of testing environment for WP_Mock tests. Sets up WP_Mock and defines common WordPress constants and functions.

**Functionality**:
- Uses `get_setting()` for WordPress-related settings
- Defines WordPress constants (ABSPATH, WP_DEBUG, etc.)
- Initializes WP_Mock
- Registers shutdown function to verify expectations

**Dependencies**:
- Requires bootstrap.php to be loaded first
- Requires WP_CONTENT_DIR and other WordPress paths to be properly set
- Requires WP_Mock to be installed
- Uses settings from .env.testing

### `bootstrap-integration.php`

**Location**: `/tests/bootstrap/bootstrap-integration.php`

**Purpose**: Handles initialization of testing environment for integration tests. Sets up WordPress test environment and database.

**Functionality**:
- Uses `get_setting('WP_TESTS_DIR')` to locate WordPress test library
- Falls back to searching common locations if WP_TESTS_DIR is not set
- Loads WordPress test bootstrap
- Sets up Mockery for integration tests

**Dependencies**:
- Requires bootstrap.php to be loaded first
- Requires WP_TESTS_DIR to be set in .env.testing or as an environment variable
- Requires WordPress test library to be installed
- Requires a properly configured test database

## Setup and Execution

### Setup Commands

**Proper Execution Sequence**:

1. **Sync plugin files to WordPress**:
   ```bash
   cd /path/to/phpunit-testing
   composer sync:wp
   ```
   This copies the plugin files to the WordPress plugins directory at `FILESYSTEM_WP_ROOT/wp-content/plugins/FRAMEWORK_DEST_NAME/`.

2. **Run the setup script**:
   ```bash
   cd /path/to/phpunit-testing
   php bin/setup-plugin-tests.php
   ```
   This sets up the WordPress test environment, including the test database.

**Important Notes**:
- Tests are always run from within the WordPress environment (FILESYSTEM_WP_ROOT/wp-content/plugins/FRAMEWORK_DEST_NAME), never from the plugin source directory
- The plugin files must be synced to WordPress before running the setup script
- The setup script changes directory to FILESYSTEM_WP_ROOT before running Lando commands
- Lando (or other local development container) must be running before executing the setup script

### Lando PHP Command Execution

When executing PHP commands in Lando environments, the following considerations are important:

1. **Directory**: Lando commands must be run from the WordPress root directory (FILESYSTEM_WP_ROOT)
2. **Shell Error Redirection**: The `2>&1` redirection must be added outside the command string when calling exec()
3. **Path References**: Container paths (e.g., `/app/...`) must be used when referencing files within the Lando container

## PHPUnit Configuration Files

Note: User can override with *.xml instead of *.xml.dist.

### `phpunit.xml.dist`

**Location**: `/config/phpunit.xml.dist`

**Purpose**: Main PHPUnit configuration file that includes all test types (unit, wp-mock, and integration).

**Configuration**:
- Bootstrap file: `../tests/bootstrap/bootstrap.php`
- Test directories: `../tests/unit`, `../tests/wp-mock`, `../tests/integration`

### `phpunit-unit.xml.dist`

**Location**: `/config/phpunit-unit.xml.dist`

**Purpose**: PHPUnit configuration file specifically for unit tests.

**Configuration**:
- Bootstrap file: `../tests/bootstrap/bootstrap.php`
- Test directory: `../tests/unit`

### `phpunit-wp-mock.xml.dist`

**Location**: `/config/phpunit-wp-mock.xml.dist`

**Purpose**: PHPUnit configuration file specifically for WP_Mock tests.

**Configuration**:
- Bootstrap file: `../tests/bootstrap/bootstrap.php`
- Test directory: `../tests/wp-mock`

### `phpunit-integration.xml.dist`

**Location**: `/config/phpunit-integration.xml.dist`

**Purpose**: PHPUnit configuration file specifically for integration tests.

**Configuration**:
- Bootstrap file: `../tests/bootstrap/bootstrap.php`
- Test directory: `../tests/integration`

### `.env.testing` - Environment Configuration

**Location**: `PROJECT_DIR/tests/.env.testing`

**Purpose**: This file stores all environment-specific configurations required for the testing framework to operate correctly within a consuming project. It is loaded by `tests/bootstrap/bootstrap.php` (via `framework-functions.php`) and its settings are accessible via the `get_setting()` function.

The `bin/test-env-requirements.php` script (when copied to `PROJECT_DIR/bin/`) is used to validate the settings in this file.

**Key Settings Validated by `test-env-requirements.php`**:

This script validates various settings crucial for the testing environment, such as WordPress paths (`WP_CORE_DIR`, `WP_CONTENT_DIR`), database credentials (if applicable for the test type), and plugin-specific paths (`PLUGIN_SLUG`, `PLUGIN_FOLDER`).

Constants like `PROJECT_DIR` (the root of the project being tested) and `FRAMEWORK_DIR` (the path to the `gl-phpunit-test-framework`) are determined contextually by the scripts that use them (e.g., `tests/bootstrap/bootstrap.php` or `bin/test-env-requirements.php` itself via `getcwd()`) and are **not** settings that `test-env-requirements.php` expects to find or validate *within* the `.env.testing` file.

Refer to `.env.sample.testing` in the framework root for a comprehensive list of settings that *can* be configured in your project's `PROJECT_DIR/tests/.env.testing` file.

**Important**: This file should be created by copying `.env.sample.testing` from the framework root to `PROJECT_DIR/tests/.env.testing` and then customizing it for the specific project environment. It should typically be added to the project's `.gitignore` file.

### `colored_message()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function colored_message(string $message, string $color = 'normal'): void
```

**Purpose**: Prints a colored message to the console for better visual feedback during test execution. Does esc_cli the text.

**Parameters**:
- `$message`: The message to print
  - Type: string
  - Required: Yes
  - Example: 'Tests completed successfully'

- `$color`: The color to use
  - Type: string
  - Required: No (default: 'normal')
  - Allowed values: 'green', 'yellow', 'red', 'blue', 'purple', 'cyan', 'light_gray', 'white', 'normal'
  - Example: 'green' for success messages, 'red' for errors

**Return Value**: None (void)


### `log_message()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function log(string $message, string $type = 'info', bool $display_on_console = true): void
```

**Purpose**:
This function serves as a centralized logger. It displays a colored, icon-prefixed message to the console and simultaneously writes a timestamped, uncolored version to the log file specified by the `TEST_ERROR_LOG` environment setting.

**Parameters**:
-   `$message`: The message to log.
-   `$type`: The type of message, which determines the icon and color. Accepts `info`, `success`, `warning`, `error`, or `debug`.
-   `$display_on_console`: A boolean to control whether the message is echoed to the console. Defaults to `true`.

**Behavior**:
-   **Console Output**: Uses `colored_message()` to display a formatted message with an icon (e.g., ✅ for success).
-   **File Output**: Appends a line to the file specified in `TEST_ERROR_LOG` (defaults to `/tmp/phpunit-testing.log`). The log entry is prefixed with a timestamp and the message type (e.g., `[2023-10-27 10:30:00] [SUCCESS]: Operation completed.`).

### `make_path()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function make_path(...$segments): string
```

**Purpose**: Joins multiple path segments into a single, clean, cross-platform-compatible path string. Intelligently handles leading and trailing slashes in each segment to produce a valid path. Preserves leading slash if first argument is absolute

**Parameters**:
- `...$segments`: A variable number of string arguments representing parts of a path.
  - Type: string

**Return Value**:
- Type: string
- A normalized path, e.g., `make_path('/usr', 'local', 'bin')` returns `/usr/local/bin`.

### `trim_folder_settings()`

**Location**: `/bin/framework-functions.php`

**Signature**:
```php
function trim_folder_settings(array $settings): array
```

**Purpose**: Trims leading and trailing slashes and spaces from specific folder path settings within a settings array.

**Parameters**:
- `$settings`: An associative array of settings containing folder paths.
  - Type: array
  - Required: Yes

**Return Value**:
- Type: array
- The modified settings array with specified folder path values trimmed.


### `display_help()` (in sync-and-test.php)

**Location**: `/bin/sync-and-test.php`

**Signature**:
```php
function display_help(): void
```

**Purpose**: Displays usage information for the script, showing available command-line options and examples.

### `display_help()` (in setup-plugin-tests.php)

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
function display_help(): void
```

**Purpose**: Displays usage information for the script, showing available command-line options and examples.

**Parameters**: None

**Return Value**: None (void)

### `build_phpunit_command()`

**Location**: `/bin/sync-and-test.php`

**Signature**:
```php
function build_phpunit_command($test_type, $options, $test_run_path)
```

**Purpose**: Constructs the PHPUnit command with appropriate configuration file and options based on the test type.

**Parameters**:
- `$test_type`: Type of tests to run
  - Type: string
  - Required: Yes
  - Allowed values: 'unit', 'integration', 'wp-mock'
  - Purpose: Determines which `phpunit-{$test_type}.xml` configuration file to use.

- `$options`: An associative array of additional PHPUnit options.
  - Type: array
  - Required: Yes
  - Supported Keys:
    - `verbose` (bool): If true, adds the `--verbose` flag.
    - `filter` (string): If set, adds the `--filter` flag with the given value.

- `$test_run_path`: The path where tests will be executed from
  - Type: string
  - Required: Yes
  - Example: '/app/wp-content/plugins/yourplugin/tests'
  - Note: **This parameter is accepted by the function but is not used in its logic.**

**Return Value**:
- Type: string
- The complete, escaped PHPUnit command ready for execution via `passthru()` or a similar function.

**Logic Flow**:
1. Start with the base PHPUnit command
2. Add the configuration file path
3. Add any specified filter options
4. Add any additional options
5. Return the complete command string

## Test Execution Scripts

### `sync-and-test.php`

**Location**: `/bin/sync-and-test.php`

**Purpose**: Syncs the plugin to WordPress and runs PHPUnit tests. This script provides a simple way to run tests without requiring Composer or Lando.

**Key Features**:
- Syncs the plugin files to WordPress before running tests
- Runs tests in the WordPress environment, not in the plugin source directory
- Supports running specific test types (unit, wp-mock, integration) or all test types sequentially

**Important Notes**:
- Each test type (unit, wp-mock, integration) runs in its own isolated environment
- When using the `--all` option, tests run sequentially and completely separately
- Never attempt to run all test types simultaneously in a single PHPUnit process, as this causes conflicts between different test environments
- The script loads environment variables from .env.testing, with explicit environment variables taking precedence

**Usage**:
```bash
php bin/sync-and-test.php [options]
```

**Options**:
- `--unit` - Run unit tests only
- `--wp-mock` - Run WP Mock tests only
- `--integration` - Run integration tests only
- `--all` - Run all test types sequentially
- `--file=<path>` - Run tests in a specific file
- `--coverage` - Generate code coverage report
- `--verbose` - Show verbose output

## Code Quality Scripts

### `phpcbf.sh`

**Location**: `/bin/phpcbf.sh`

**Purpose**: Runs PHPCBF with practical exclusions that focus on functional issues rather than minor formatting concerns.

**Usage**:
```bash
./bin/phpcbf.sh [options] [<file>...]
```

**Examples**:
- `./bin/phpcbf.sh` - Run on all files
- `./bin/phpcbf.sh src/Integration/` - Run on specific directory
- `./bin/phpcbf.sh --report-file=report.txt` - Save report to file

**Excluded Rules**:
- `Squiz.Commenting.InlineComment` - Comment formatting rules
- `PEAR.Functions.FunctionCallSignature` - Spacing in function calls
- `Generic.Formatting.MultipleStatementAlignment` - Alignment of assignments
- `WordPress.Arrays.ArrayIndentation` - Array indentation rules
- `WordPress.WhiteSpace.OperatorSpacing` - Spacing around operators
- `WordPress.WhiteSpace.ControlStructureSpacing` - Spacing in control structures
- `WordPress.PHP.YodaConditions` - Requiring Yoda conditions

**Behavior**:
1. First runs the `spaces_to_tabs` Composer script to convert spaces to tabs
2. Then runs PHPCBF with the excluded rules
3. Passes through any additional arguments to PHPCBF

### `phpunit-framework-tests.xml.dist`

**Location**: `/config/phpunit-framework-tests.xml.dist`

**Purpose**: PHPUnit configuration file for testing the framework itself.

**Configuration**:
- Bootstrap file: `../tests/bootstrap/bootstrap.php`
- Test directories: `../tests/framework/unit`, `../tests/framework/wp-mock`, `../tests/framework/integration`
