# Code Inventory - PHPUnit Testing Framework

This document provides an inventory of key functions, classes, and variables in the PHPUnit Testing Framework.

## Table of Contents

- [Test Class Naming Conventions](#test-class-naming-conventions)
- [Namespace](#namespace)
- [Exception Handling](#exception-handling)
- [Important Global Variables](#important-global-variables)
- [Functions](#functions)
  - [load_settings_file()](#load_settings_file)
  - [get_setting()](#get_setting)
  - [get_phpunit_database_settings()](#get_phpunit_database_settings)
  - [install_wp_test_suite()](#install_wp_test_suite)
  - [format_php_command()](#format_php_command)
  - [format_mysql_command()](#format_mysql_command)
  - [format_mysql_execution()](#format_mysql_execution)
  - [drop_test_database_and_files()](#drop_test_database_and_files)
  - [display_help()](#display_help)
  - [esc_cli()](#esc_cli)
  - [find_wordpress_root()](#find_wordpress_root)
  - [get_wp_config_value()](#get_wp_config_value)
  - [download_wp_tests()](#download_wp_tests)
  - [generate_wp_tests_config()](#generate_wp_tests_config)
  - [colored_message()](#colored_message)
  - [print_usage()](#print_usage)
  - [build_phpunit_command()](#build_phpunit_command)
- [Variables](#variables)
  - [Global Variables](#global-variables)
  - [Key Configuration Variables](#key-configuration-variables)
- [Constants](#constants)
  - [Script Constants](#script-constants)
  - [Terminal Color Constants](#terminal-color-constants)
- [Dependencies](#dependencies)
- [Bootstrap Files](#bootstrap-files)
  - [Bootstrap File Relationships](#bootstrap-file-relationships)
  - [bootstrap.php](#bootstrapphp)
  - [bootstrap-unit.php](#bootstrap-unitphp)
  - [bootstrap-wp-mock.php](#bootstrap-wp-mockphp)
  - [bootstrap-integration.php](#bootstrap-integrationphp)
- [Test Execution Scripts](#test-execution-scripts)
  - [sync-and-test.php](#sync-and-testphp)
- [Configuration Files](#configuration-files)
  - [phpunit-integration.xml.dist](#phpunit-integrationxmldist)
  - [phpunit-unit.xml.dist](#phpunit-unitxmldist)
  - [phpunit-wp-mock.xml.dist](#phpunit-wp-mockxmldist)

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

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function get_database_settings(
    string $wp_config_path,
    array $lando_info = [],
    string $config_file_name = '.env.testing'
): array
```

**Purpose**: Retrieves WordPress database connection settings from multiple sources in a specific priority order. Its purpose is to determine the database settings (host, user, password, name, and table prefix) that should be used for WordPress plugin testing.

**Parameters**:
- `$wp_config_path`: Path to WordPress configuration file
  - Type: string
  - Required: Yes
  - Example: '/path/to/wordpress/wp-config.php'

- `$lando_info`: Lando environment configuration
  - Type: array
  - Required: No
  - Default: []
  - Source: Must be obtained by executing `lando info` command and parsing its JSON output
  - Detection: Lando is considered present when this array is not empty
  - Important: Cannot use LANDO_INFO environment variable, as it only exists inside Lando containers

- `$config_file_name`: Name of the configuration file
  - Type: string
  - Required: No
  - Default: '.env.testing'
  - Example: '.env.custom'

**Return Value**: Array containing database settings with keys:
- `db_host`: Database host
- `db_user`: Database username
- `db_pass`: Database password
- `db_name`: Database name
- `table_prefix`: WordPress table prefix (from wp-config.php)

**Exceptions Thrown**:
- `\Exception`: If wp-config.php doesn't exist at the specified path
- `\Exception`: If any required database settings (db_host, db_user, db_pass, db_name) are missing after checking all sources

**Logic Flow**:
1. Initialize settings array with empty values
2. Include wp-config.php
3. Extract database constants from wp-config.php
4. Override with settings from config file (.env.testing by default)
5. Override with environment variables if present
6. Override with Lando configuration if Lando info is provided
7. Validate that all required settings are present
8. Return the final settings array

**Priority Order**:
1. wp-config.php (lowest priority)
2. Config file (.env.testing)
3. Environment variables
4. Lando configuration (highest priority)


### `validate_wordpress_root()`

**Location**: `/bin/setup-plugin-tests.php` (main execution section)

**Purpose**: Validates that the WordPress root directory contains (a few expected) WordPress files and directories.

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


### `get_lando_info()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function get_lando_info(): array
```

**Purpose**: Retrieves Lando environment configuration by executing the `lando info` command. Works when running from outside a Lando container.

**Parameters**: None

**Return Value**:
- Type: array
- Lando configuration information or empty array if Lando is not running

**Error Handling**:
- Returns empty array if Lando command is not found
- Returns empty array if Lando is not running
- Returns empty array if Lando configuration cannot be parsed

**Logic Flow**:
1. Check if the lando command exists using `which lando`
2. If not found, return empty array
3. Execute `lando info --format=json` command
4. If command returns empty result, return empty array
5. Parse JSON output using json_decode
6. If parsing fails, return empty array
7. Return the parsed Lando configuration


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

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function format_ssh_command(string $ssh_command, string $command): string
```

**Purpose**: Formats SSH commands properly based on the SSH_COMMAND setting. Handles different SSH command formats, particularly for Lando environments.

**Parameters**:
- `$ssh_command`: The SSH command to use
  - Type: string
  - Required: Yes
  - Example: 'lando ssh' or 'ssh user@host'

- `$command`: The command to execute via SSH
  - Type: string
  - Required: Yes
  - Example: 'mysql -e "SELECT 1" '

**Return Value**:
- Type: string
- The properly formatted SSH command ready for execution

**Logic Flow**:
1. Check if the SSH command contains 'lando ssh'
2. For Lando SSH, format as: `lando ssh -c '  command  ' 2>&1`
3. For regular SSH, format as: `ssh_command '  command  ' 2>&1`
4. Return the formatted command

**Special Handling**:
- Adds proper quoting to ensure command executes correctly in the SSH environment

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

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
function format_php_command( string $php_script_path, array $arguments = [], string $command_type = 'auto' ): string
```

**Purpose**: Formats PHP commands with proper escaping for file paths, ensuring they work correctly with spaces and special characters.

**Parameters**:
- `$php_script_path`: The path to the PHP script to execute
  - Type: string
  - Required: Yes
  - Example: '/app/path/to/script.php'

- `$arguments`: Array of arguments to pass to the PHP script (not including the script itself)
  - Type: array
  - Required: No (default: empty array)
  - Example: ['arg1', 'arg2']

- `$command_type`: The type of command to use
  - Type: string
  - Required: No (default: 'auto')
  - Values: 'auto', 'standard', 'lando_php', 'lando_exec', or a custom command
  - If 'auto', will detect environment and use 'lando_php' or 'standard' accordingly

**Return Value**: String containing the properly formatted command with escaped arguments

**Logic Flow**:
1. Initialize an empty array for escaped arguments
2. For each argument, escape any double quotes
3. Wrap each argument in double quotes to handle spaces
4. Combine the command with escaped arguments
5. Return the final formatted command

**Special Handling**:
- Properly escapes double quotes in path arguments
- Wraps arguments in double quotes to handle paths with spaces
- Adds standard error redirection (2>&1) to capture all output
- Redirects stderr to stdout with `2>&1` for comprehensive output capture

### `format_mysql_parameters_and_query()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function format_mysql_parameters_and_query(string $host, string $user, string $pass, string $sql, ?string $db = null, string $command_type = 'direct'): string
```

**Purpose**: Formats MySQL parameters and SQL query (without the mysql executable). This function only handles the parameters and SQL escaping. The actual MySQL executable is added by the `format_mysql_execution()` function.

**Important Distinction**:
- This function returns: `-h host -u user -ppassword -e 'SELECT * FROM wp_users;'`
- It does NOT return: `mysql -h host -u user -ppassword -e 'SELECT * FROM wp_users;'`
- The `format_mysql_execution()` function is responsible for adding the appropriate MySQL executable command

**Parameters**:
- `$host`: Database host
  - Type: string
  - Required: Yes
  - Example: 'localhost' or 'database'

- `$user`: Database user
  - Type: string
  - Required: Yes
  - Example: 'root' or 'wordpress'

- `$pass`: Database password
  - Type: string
  - Required: Yes
  - Example: 'password'

- `$sql`: SQL command to execute
  - Type: string
  - Required: Yes
  - Example: 'SELECT 1;' or 'CREATE DATABASE test;'

- `$db`: Optional database name to use
  - Type: string|null
  - Required: No
  - Default: null
  - Example: 'wordpress_test'

- `$command_type`: The type of command
  - Type: string
  - Required: No
  - Default: 'ssh'
  - Allowed values: 'lando_direct', 'ssh', 'direct'

**Return Value**:
- Type: string
- Formatted MySQL command with proper connection parameters and escaped SQL

**Logic Flow**:
1. Build connection parameters (-h host -u user -ppassword)
2. Add database name if provided
3. Normalize SQL line endings and ensure it ends with semicolon
4. Replace newlines with spaces for multiline SQL
5. Apply appropriate escaping based on command type:
   - For 'lando_direct': Only escape single quotes with "'\'"
   - For 'ssh' or 'direct': Escape both single and double quotes
6. Format the final command as "connection_params -e 'escaped_sql'"
7. Return the formatted command

**Special Handling**:
- Different escaping rules for different command types ensure SQL executes correctly in various environments
- Handles multiline SQL statements by converting them to single-line commands

### `format_mysql_execution()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function format_mysql_execution(string $ssh_command, string $host, string $user, string $pass, string $sql, ?string $db = null): string
```

**Purpose**: Formats a MySQL command using the appropriate method (direct, SSH, or Lando). Determines the best execution method based on the environment and SSH command.

**Relationship with Other Functions**:
- Works with `format_mysql_command()` which handles parameter and SQL formatting
- Works with `format_ssh_command()` when SSH execution is needed
- Call flow: `format_mysql_execution()` → `format_mysql_command()` → (if SSH needed) `format_ssh_command()`
- Returns a command string that must still be executed by the caller using `exec()` or `system()`

**Parameters**:
- `$ssh_command`: The SSH command to use (or 'none' for direct)
  - Type: string
  - Required: Yes
  - Example: 'lando ssh', 'ssh user@host', or 'none'

- `$host`: Database host
  - Type: string
  - Required: Yes
  - Example: 'localhost' or 'database'

- `$user`: Database user
  - Type: string
  - Required: Yes
  - Example: 'root' or 'wordpress'

- `$pass`: Database password
  - Type: string
  - Required: Yes
  - Example: 'password'

- `$sql`: SQL command to execute
  - Type: string
  - Required: Yes
  - Example: 'SELECT 1;' or 'CREATE DATABASE test;'

- `$db`: Optional database name to use
  - Type: string|null
  - Required: No
  - Default: null
  - Example: 'wordpress_test'

**Return Value**:
- Type: string
- The fully formatted command ready to execute

**Logic Flow**:
1. Determine the command type based on the SSH command:
   - If SSH command contains 'lando ssh': Use 'lando_direct'
   - If SSH command is empty or 'none': Use 'direct'
   - Otherwise: Use 'ssh'
2. Format the MySQL parameters using format_mysql_command()
3. Build the final command based on the command type:
   - For 'lando_direct': `lando mysql $mysql_params 2>&1`
   - For 'ssh': Use format_ssh_command() to wrap the MySQL command
   - For 'direct': `mysql $mysql_params 2>&1`
4. Return the formatted command

**Special Handling**:
- Automatically detects Lando environments and uses the appropriate command format
- Redirects stderr to stdout with `2>&1` for comprehensive output capture
- Uses format_mysql_command() to handle SQL escaping based on command type

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

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

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

### `remove_test_suite()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function remove_test_suite(
    string $wp_tests_dir,
    string $db_name,
    string $db_host,
    string $ssh_command = ''
): bool
```

**Purpose**: Removes the WordPress test suite by dropping the test database and deleting test files. Used when the `--remove-all` or `--remove` command line option is specified.

**Parameters**:
- `$wp_tests_dir`: Directory where tests are installed
  - Type: string
  - Required: Yes
  - Example: '/path/to/wordpress/wp-content/plugins/wordpress-develop/tests/phpunit'

- `$db_name`: Database name
  - Type: string
  - Required: Yes
  - Example: 'wordpress_test'

- `$db_host`: Database host
  - Type: string
  - Required: Yes
  - Example: 'localhost' or 'database'

- `$ssh_command`: SSH command if using remote connection
  - Type: string
  - Required: No
  - Default: ''
  - Example: 'lando ssh' or 'ssh user@host'

**Return Value**:
- Type: bool
- True if successful, false otherwise

**Logic Flow**:
1. Check if SSH command is provided to determine connection method
2. Build SQL command to drop the test database
3. Format and execute the MySQL command using format_mysql_execution()
4. Check if the command was successful
5. Remove test files from the specified directory using rm -rf
6. Return true if the process completes

**Error Handling**:
- Continues with file removal even if database drop fails
- Displays error messages if database drop fails
- Checks if test directory exists before attempting to remove it

**Security Considerations**:
- Does not verify that the database contains expected WordPress test tables before dropping
- Potential improvement: Could verify presence of test tables (with any prefix + 'users' and 'posts') before dropping
- Potential improvement: Could check for non-test tables and prompt for confirmation

### `display_help()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function display_help(): void
```

**Purpose**: Displays help information for the setup script, including usage instructions, available options, and configuration details.

**Parameters**: None

**Return Value**: None (void)

**Logic Flow**:
1. Display script title and formatting
2. Show usage syntax
3. List available command line options
4. Provide a description of what the script does
5. Explain the --remove-all option
6. Provide information about configuration settings

**Command Line Options Documented**:
- `--help, -h`: Display the help message
- `--remove-all, --remove`: Remove test database and files

### `get_setting()`

**Locations**:
- `/bin/setup-plugin-tests.php`
- `/tests/bootstrap/bootstrap.php`

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

**Location**: `/bin/setup-plugin-tests.php`, `/bin/sync-and-test.php`, `/bin/sync-to-wp.php`

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

**Location**: `/bin/setup-plugin-tests.php`

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

**Logic Flow**:
1. Initialize depth counter
2. While depth is less than max_depth:
   - Check if wp-config.php exists in the current directory
   - If found, return the real path of the current directory
   - Otherwise, move up one directory level and increment depth
3. Return null if wp-config.php is not found

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
function generate_wp_tests_config(
    string $wp_tests_dir,
    string $db_name,
    string $db_user,
    string $db_pass,
    string $db_host,
    string $table_prefix = 'wptests_'
): string
```

**Purpose**: Generates the wp-tests-config.php file required by the WordPress test suite with the specified database settings.

**Parameters**:
- `$wp_tests_dir`: Directory where tests are installed
  - Type: string
  - Required: Yes
  - Example: '/path/to/wordpress/wp-content/plugins/my-plugin/tests/phpunit'

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
  - Example: 'password'

- `$db_host`: Database host
  - Type: string
  - Required: Yes
  - Example: 'localhost'

- `$table_prefix`: Database table prefix
  - Type: string
  - Required: No (default: 'wptests_')
  - Example: 'wp_test_'

**Return Value**:
- Type: string
- Path to the generated config file

**Logic Flow**:
1. Create the config file content with database settings
2. Write the content to wp-tests-config.php in the tests directory
3. Return the path to the created file

**Error Handling**:
- Throws exception if file cannot be written

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

### `colored_message()`

**Location**: `/bin/sync-and-test.php`

**Signature**:
```php
function colored_message(string $message, string $color = 'normal'): void
```

**Purpose**: Prints a colored message to the console for better visual feedback during test execution.

**Parameters**:
- `$message`: The message to print
  - Type: string
  - Required: Yes
  - Example: 'Tests completed successfully'

- `$color`: The color to use
  - Type: string
  - Required: No (default: 'normal')
  - Allowed values: 'green', 'yellow', 'red', 'blue', 'normal'
  - Example: 'green' for success messages, 'red' for errors

**Return Value**: None (void)

**Logic Flow**:
1. Define color codes for different message types
2. Select the appropriate color code based on the specified color
3. Wrap the message with color codes
4. Output the colored message to the console

### `print_usage()`

**Location**: `/bin/sync-and-test.php`

**Signature**:
```php
function print_usage(): void
```

**Purpose**: Displays usage information for the sync-and-test.php script, showing available command-line options and examples.

**Parameters**: None

**Return Value**: None (void)

**Logic Flow**:
1. Print script name and purpose
2. List available command-line options with descriptions
3. Show usage examples

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
  - Example: 'unit'

- `$options`: Additional PHPUnit options
  - Type: array
  - Required: Yes
  - Example: ['--filter=test_format_php_command', '--verbose']

- `$test_run_path`: The path where tests will be executed from
  - Type: string
  - Required: Yes
  - Example: '/app/wp-content/plugins/yourplugin/tests'

**Return Value**:
- Type: string
- The complete PHPUnit command ready for execution

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
