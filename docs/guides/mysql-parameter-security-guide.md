# MySQL Parameter Security Guide for WordPress Plugin Developers

## Overview

This guide explains how to securely handle MySQL connection parameters and SQL queries in WordPress plugin development. It introduces the `WP_MySQL_Parameter_Validator` class, which provides robust validation for MySQL parameters and queries, helping you prevent security vulnerabilities such as command injection and improper database access.

Following these practices will help ensure your plugin code is safe, maintainable, and compatible with WordPress Coding Standards.

---

## Why Validate MySQL Parameters?

- **Prevent command injection** when building shell commands.
- **Avoid SQL injection** and ensure only valid database operations are performed.
- **Increase code reliability** by catching errors early.
- **Follow WordPress security best practices**.

---

## Using `WP_MySQL_Parameter_Validator`

The `WP_MySQL_Parameter_Validator` class is located at:

```
src/class-mysql-parameter-validator.php
```

### Basic Usage Example

```php
require_once __DIR__ . '/../src/class-mysql-parameter-validator.php';

$params = [
    'host'     => 'localhost',
    'user'     => 'wordpress',
    'password' => 'strong_password',
    'database' => 'wp_myplugin',
    'sql'      => 'SELECT * FROM wp_users;',
];

$result = WP_MySQL_Parameter_Validator::validate_all( $params );

if ( is_wp_error( $result ) ) {
    // Handle error (show admin notice, log, etc)
    error_log( $result->get_error_message() );
    return;
}
// Safe to proceed with DB/API call
```

### Individual Parameter Validation

```php
$host_check = WP_MySQL_Parameter_Validator::validate_host( $host );
if ( is_wp_error( $host_check ) ) {
    // Handle error
}
```

### Shell Command Mode

If you are building a shell command (e.g., for `exec()`), use:

```php
$sql_check = WP_MySQL_Parameter_Validator::validate_sql( $sql, 'shell' );
```

---

## How to Code Properly So Validation Rarely Fails

- **Never accept raw user input** for MySQL parameters. Always use trusted sources (WordPress options, environment variables, or admin-configured settings).
- **Sanitize and validate** any data before passing to the validator. Use WordPress functions like `sanitize_text_field()`, `sanitize_user()`, and `esc_sql()` where appropriate.
- **Restrict allowed characters** for host, user, and database to alphanumerics, underscores, dashes, and dots. Avoid spaces or special shell characters.
- **Passwords:** Use strong, randomly generated passwords. Avoid shell metacharacters (;, &, |, `, $, >, <, \\, ', ", ?, *, [, ], (, ), {, }, comma, and whitespace) when passing via shell commands. If you must use such characters, ensure they are properly quoted/escaped or use parameterized APIs.
- **SQL queries:**
    - Prefer parameterized queries (e.g., `$wpdb->prepare()` or PDO) for user data.
    - If building SQL strings, never concatenate untrusted input.
    - Always end SQL statements with a semicolon, and avoid newlines in shell commands.
- **Error Handling:**
    - Always check for `WP_Error` after validation.
    - Log or display errors securely; do not expose sensitive details to users.
- **Internationalization:**
    - All error messages from the validator are translatable. Use `__()`/`_e()` in your own code as well.

---

## Example: Secure Workflow in a Plugin

```php
// Load validator
require_once __DIR__ . '/../src/class-mysql-parameter-validator.php';

// Get DB params from options or config
$host = get_option( 'myplugin_db_host', 'localhost' );
$user = get_option( 'myplugin_db_user', 'wordpress' );
$pass = getenv( 'MYPLUGIN_DB_PASS' );
$db   = get_option( 'myplugin_db_name', 'wp_myplugin' );
$sql  = 'SELECT * FROM wp_users;';

$params = compact( 'host', 'user', 'pass', 'db', 'sql' );

$result = WP_MySQL_Parameter_Validator::validate_all( [
    'host'     => $host,
    'user'     => $user,
    'password' => $pass,
    'database' => $db,
    'sql'      => $sql,
] );

if ( is_wp_error( $result ) ) {
    // Handle error securely
    error_log( $result->get_error_message() );
    wp_die( esc_html( $result->get_error_message() ) );
}
// Proceed with DB/API call
```

---

## Troubleshooting

- **Validator triggers errors:**
    - Check for unexpected characters in your parameters.
    - Review how parameters are sourced and sanitized.
    - Use the error message to identify which parameter failed and why.
- **Need to allow more characters?**
    - Fork or extend the validator class, or add a filter in your own code.
- **Unsure about SQL safety?**
    - Use `$wpdb->prepare()` or parameterized queries whenever possible.

---

## Summary Table: Allowed Characters

| Parameter | Allowed Characters                      |
|-----------|-----------------------------------------|
| Host      | a-z, A-Z, 0-9, _, -, ., :, [ ]          |
| User      | a-z, A-Z, 0-9, _, -, .                  |
| Password  | Most printable ASCII except shell chars |
| Database  | a-z, A-Z, 0-9, _, -, $                  |
| SQL       | Any valid SQL, but no shell metachars in shell mode |

---

## Further Reading
- [WordPress Plugin Handbook: Data Validation](https://developer.wordpress.org/plugins/security/securing-input/#validating-and-sanitizing)
- [WordPress Database API](https://developer.wordpress.org/reference/classes/wpdb/)
- [PHP Manual: SQL Injection Prevention](https://www.php.net/manual/en/security.database.sql-injection.php)

---

By following this guide and using the provided validator, you can write secure, robust, and maintainable WordPress plugins that interact with MySQL safely.
