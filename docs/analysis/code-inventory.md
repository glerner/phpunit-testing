# Code Inventory - PHPUnit Testing Framework

This document provides an inventory of key functions, classes, and variables in the PHPUnit Testing Framework.

## Namespace

```php
namespace WP_PHPUnit_Framework;
```

PHP core classes within the namespace must be prefixed with a backslash to indicate they're from the global namespace:

```php
\Throwable    // Correct reference to global Throwable class
\Exception    // Correct reference to global Exception class
```

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

**Error Handling**:
- Throws exception if wp-config.php doesn't exist
- Throws exception if any required database settings are missing after all sources are checked

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

**Purpose**: Validates that the WordPress root directory contains expected WordPress files and directories.

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

**Purpose**: Retrieves Lando environment configuration by executing the 'lando info' command. Works when running from outside a Lando container.

**Parameters**: None

**Return Value**:
- Type: array
- Lando configuration information or empty array if Lando is not running

**Error Handling**:
- Returns empty array if Lando command is not found
- Returns empty array if Lando is not running
- Returns empty array if Lando configuration cannot be parsed

**Logic Flow**:
1. Check if the lando command exists using 'which lando'
2. If not found, return empty array
3. Execute 'lando info --format=json' command
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
  - Source: Result from get_database_settings() function

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
- Provides extensive debugging information for troubleshooting


### `get_setting()`

**Location**: `/bin/setup-plugin-tests.php`

**Signature**:
```php
namespace WP_PHPUnit_Framework;

function get_setting(string $name, $default = null)
```

**Purpose**: Retrieves a setting from environment variables or loaded settings array.

**Parameters**:
- `$name`: Name of the setting to retrieve
  - Type: string
  - Required: Yes
  - Example: 'WP_TESTS_DB_HOST'

- `$default`: Default value if setting is not found
  - Type: mixed
  - Required: No
  - Default: null
  - Example: 'localhost'

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

- None specifically defined in the setup script beyond those loaded from wp-config.php

## Dependencies

- The script relies on WordPress core files being present at the specified root path
- Requires a valid wp-config.php file or alternative configuration sources
