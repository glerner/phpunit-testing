# Lando Command Execution Best Practices

This document explains how to properly execute commands in Lando environments based on systematic testing of different approaches. It covers three main types of Lando commands:

1. **Lando PHP Commands**: For executing PHP scripts within the Lando container
2. **Lando Exec Commands**: For running arbitrary commands in the Lando container
3. **Lando MySQL Commands**: For executing MySQL queries directly

## Key Requirements for All Lando Commands

1. **Working Directory**: Commands must be run from the WordPress root directory or a subfolder (e.g., the folder of the plugin being tested)
2. **Shell Error Redirection**: Any redirection (with `2>&1`) should be added outside the command string when using `exec()`
3. **Path References**: Use container paths (e.g., `/app/...`) when referencing files within the Lando container
4. **Command Syntax**: Use the correct syntax for `lando php`, `lando exec`, or `lando mysql` commands
5. **Command Quoting**: Quote arguments properly, especially those that might contain spaces

### Working Directory Requirement

Lando commands must be run from the WordPress root directory or a subfolder within the WordPress installation (such as a plugin directory). If executed from directories outside the WordPress installation, Lando will show its help menu instead of executing the command.

In PHP code, you can ensure this by:

```php
// Store original directory
$original_dir = getcwd();

// Change to WordPress root directory
$wp_root = '/path/to/wordpress';
chdir($wp_root);

// Execute Lando command
$command = format_php_command('lando php', array('/app/path/to/script.php', 'arg1'));
exec("$command 2>&1", $output, $return_var);

// Change back to original directory
chdir($original_dir);
```

### Container Paths

When referencing files within the Lando container, use container paths (e.g., `/app/...`) rather than host paths. The `/app` directory in the container maps to the WordPress root directory on the host.

### Output Redirection

You can redirect command output to files using standard shell redirection operators. This is useful for capturing logs or test results:

```bash
# Redirect stdout to a file
lando php "/app/path/to/script.php" > output.log

# Redirect both stdout and stderr to a file
lando php "/app/path/to/script.php" > output.log 2>&1

# Append to an existing file
lando php "/app/path/to/script.php" >> output.log 2>&1
```

When executing commands from PHP, you need to include the redirection in the command string:

```php
$command = format_php_command('lando php', array('/app/path/to/script.php'));

// Redirect output to a file
exec("$command > output.log 2>&1", $output, $return_var);
```

## 1. Lando PHP Commands

Lando provides two main approaches for executing PHP scripts:

### Option 1: lando php (Simplest)

```bash
lando php "/app/path/to/script.php" "arg1" "arg2"
```

This approach directly executes PHP within the Lando container.

### Option 2: lando exec with php

```bash
lando exec appserver -- php "/app/path/to/script.php" "arg1" "arg2"
```

**Important**: The `--` separator between the service name and the command is critical. Without it, the command will fail. The error message from Lando will show the correct syntax if you get this wrong.

You can also use an alternative syntax with single quotes around the command:

```bash
lando exec appserver -- 'php "/app/path/to/script.php" "arg1" "arg2"'
```

### PHP Command Execution Methods

**Note**: A complete implementation of `format_php_command()` is provided in Section 4 below. This function handles all Lando command types automatically.

All three PHP command execution methods can work successfully with Lando when implemented correctly:

### 1. Using exec() (Recommended)

```php
// Format the command without shell error redirection
function format_php_command($php_command, $arguments) {
    $args_string = '';
    foreach ($arguments as $arg) {
        $args_string .= ' "' . $arg . '"';
    }
    return $php_command . $args_string;
}

// Execute with shell error redirection added separately
$command = format_php_command('lando php', array('/app/path/to/script.php', 'arg1'));
exec("$command 2>&1", $output, $return_var);
```

**Key Points:**
- The command is constructed without the redirection
- The redirection is added outside the command string when executing
- Arguments are properly quoted to handle spaces in paths

### 2. Using proc_open()

```php
$command = format_php_command('lando php', array('/app/path/to/script.php', 'arg1'));
$descriptors = [
    0 => ["pipe", "r"],  // stdin
    1 => ["pipe", "w"],  // stdout
    2 => ["pipe", "w"]   // stderr
];

$process = proc_open($command, $descriptors, $pipes);

if (is_resource($process)) {
    // Close stdin
    fclose($pipes[0]);

    // Read stdout
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Read stderr
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // Close the process
    $return_code = proc_close($process);

    // Process stdout and stderr separately
    echo "Standard output:\n$stdout\n";
    echo "Error output:\n$stderr\n";
    echo "Return code: $return_code\n";
}
```

**Advantages:**
- Separates stdout and stderr
- Provides return code
- More control over input/output

### 3. Using shell_exec()

```php
$command = format_php_command('lando php', array('/app/path/to/script.php', 'arg1'));
$output = shell_exec("$command 2>&1");
```

**Advantages:**
- Simple syntax
- Returns all output as a single string

**Disadvantages:**
- Doesn't provide the return code
- Less control over the output processing

## 4. Implementation Example

Here's a comprehensive implementation of the `format_php_command()` function that handles all Lando command types as well as non-Lando commands:

```php
/**
 * Format PHP command with proper escaping for file paths
 *
 * @param string $php_command The PHP command to use (e.g. 'php', 'lando php', 'lando exec appserver')
 * @param array  $arguments   Array of arguments to pass to the PHP command. Can be individual arguments
 *                           as separate array elements or a single command string as one element.
 * @return string Formatted PHP command (without shell error redirection)
 */
function format_php_command(string $php_command, array $arguments): string {
    // Determine if this is a Lando command
    // This automatic detection eliminates the need for a separate $is_lando parameter
    $is_lando = strpos($php_command, 'lando') === 0;

    // Check if this is a lando exec command
    $is_lando_exec = (strpos($php_command, 'lando exec') === 0);

    // Format the command based on the type
    if ($is_lando) {
        $args_string = '';
        foreach ($arguments as $arg) {
            // Escape any quotes in the argument
            $escaped_arg = str_replace('"', '\\"', $arg);
            // Add quotes around each argument
            $args_string .= ' "' . $escaped_arg . '"';
        }

        // For lando exec, ensure the -- separator is present
        if ($is_lando_exec && strpos($php_command, '--') === false) {
            // Add the -- separator if not already present
            if (substr($php_command, -1) !== ' ') {
                $php_command .= ' ';
            }
            $php_command .= '-- php';
        }

        return $php_command . $args_string;
    } else {
        // Standard handling for non-Lando commands
        $escaped_args = array();

        foreach ($arguments as $arg) {
            // Escape any double quotes in the argument
            $escaped_arg = str_replace('"', '\\"', $arg);
            // Add quotes around each argument
            $escaped_args[] = '"' . $escaped_arg . '"';
        }

        return $php_command . ' ' . implode(' ', $escaped_args);
    }
}
```

## Testing Your Command Execution

To verify your command execution is working correctly, create a simple diagnostic script that outputs information about how arguments are being received:

```php
<?php
// diagnostic.php
echo "=== PHP DIAGNOSTIC SCRIPT ===\n";
echo "Script path: " . __FILE__ . "\n";
echo "Current directory: " . getcwd() . "\n";
echo "PHP version: " . PHP_VERSION . "\n\n";

echo "Command line arguments:\n";
for ($i = 1; $i < count($argv); $i++) {
    echo "Arg $i: {$argv[$i]}\n";
}

echo "\nEnvironment variables:\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'LANDO_') === 0) {
        echo "$key: $value\n";
    }
}

echo "\n=== END DIAGNOSTIC OUTPUT ===\n";
```

## Summary of Best Practices

1. **Run Lando commands from the WordPress root directory or a subfolder within the WordPress installation**
2. **Keep any shell error redirection (with `2>&1`) outside the command string**
3. **Use container paths (`/app/...`) for file references**
4. **For `lando exec`, always include the `--` separator**
5. **Quote arguments properly to handle spaces in paths**
6. **Use `exec()` for most cases, as it provides a good balance of simplicity and functionality**
7. **Consider `proc_open()` for complex scenarios requiring separate stdout/stderr handling**

## 2. Lando Exec Commands

Beyond PHP and MySQL, Lando provides a general-purpose `exec` command for running arbitrary commands in the container. This is useful for executing any command that isn't covered by the specific Lando commands.

### Lando Exec Syntax

```bash
lando exec [service] -- [command]
```

The `--` separator is critical and must be included between the service name and the command.

### Examples of Lando Exec Commands

```bash
# Run a bash command
lando exec appserver -- ls -la /app

# Run a composer command
lando exec appserver -- composer install

# Run a WP-CLI command
lando exec appserver -- wp plugin list
```

### Executing Lando Exec Commands from PHP

```php
// Format the command - each argument as a separate array element
$command = format_php_command('lando exec appserver', array('wp', 'plugin', 'list'));

// Alternative: you can also pass a single string as one argument
// $command = format_php_command('lando exec appserver', array('wp plugin list'));

// The format_php_command function will automatically add the -- separator
// Result: lando exec appserver -- php "wp" "plugin" "list"

// Execute with shell error redirection added separately
exec("$command 2>&1", $output, $return_var);

// Note: The command is constructed without the redirection
// The redirection is added when executing the command
```

### Key Points for Lando Exec Commands

1. **The `--` Separator**: Always include the `--` separator between the service name and the command
2. **Service Name**: Specify the service name (e.g., `appserver`, `database`)
3. **Command Quoting**: Quote arguments properly, especially those with spaces
4. **Shell Error Redirection**: Add `2>&1` outside the command string when using `exec()`
5. **Working Directory**: Run commands from the WordPress root directory or a subfolder

## 3. Lando MySQL Commands

Lando provides direct access to MySQL for executing database queries. Here's how to properly execute MySQL commands in Lando environments:

### Lando MySQL Syntax

```bash
lando mysql -h host -u user -ppassword database -e 'SQL QUERY;'
```

This approach directly executes MySQL within the Lando container. Note that there is no space between `-p` and the password.

### Formatting MySQL Commands in PHP

When executing MySQL commands from PHP, you need to properly escape the SQL query and handle the command formatting:

```php
/**
 * Format MySQL command with proper parameters and SQL command
 *
 * @param string      $host Database host
 * @param string      $user Database user
 * @param string      $pass Database password
 * @param string      $sql SQL command to execute
 * @param string|null $db Optional database name to use (the ?string syntax (nullable type notation) indicates an optional parameter with null default)
 * @param string      $command_type The type of command ('lando_direct', 'ssh', or 'direct'), defaults to 'ssh'
 * @return string Formatted MySQL command
 */
function format_mysql_command(string $host, string $user, string $pass, string $sql, ?string $db = null, string $command_type = 'ssh'): string {
    // Build the connection parameters
    $connection_params = "-h $host -u $user";

    // Add password if provided
    if (!empty($pass)) {
        $connection_params .= " -p$pass";
    }

    // Add database if provided
    if (!empty($db)) {
        $connection_params .= " $db";
    }

    // Process SQL command
    // 1. Normalize line endings to avoid issues with different environments
    $sql = str_replace("\r\n", "\n", $sql);

    // 2. Ensure SQL command ends with semicolon
    if (substr(trim($sql), -1) !== ';') {
        $sql .= ';';
    }

    // 3. For multiline SQL (like heredoc), replace newlines with spaces
    $sql = str_replace("\n", ' ', $sql);

    // 4. Escape quotes in SQL based on command type
    $escaped_sql = $sql;

    // Different escaping rules based on command type
    if ($command_type === 'lando_direct') {
        // For direct lando mysql command, we only need to escape single quotes
        $escaped_sql = str_replace("'", "'\\'", $sql);
    } else {
        // For SSH or direct MySQL, escape both single and double quotes
        $escaped_sql = str_replace("'", "\\'", $sql);
        $escaped_sql = str_replace('"', '\\"', $escaped_sql);
    }

    // Add the SQL command with proper quoting
    $formatted_command = "$connection_params -e '$escaped_sql'";

    return $formatted_command;
}
```

### Executing MySQL Commands with Lando

To execute the formatted MySQL command with Lando:

```php
// Format the MySQL command
$mysql_params = format_mysql_command($host, $user, $pass, $sql, $db, 'lando_direct');

// Create the full Lando MySQL command
$cmd = "lando mysql $mysql_params";

// Execute with shell error redirection added separately
// Note: The command is constructed without the redirection
exec("$cmd 2>&1", $output, $return_var);
```

### Key Points for Lando MySQL Commands

1. **Command Type**: Use 'lando_direct' when formatting MySQL commands for Lando
2. **SQL Escaping**: Different escaping rules apply for Lando MySQL vs. regular MySQL
3. **Password Format**: No space between `-p` and the password
4. **Shell Error Redirection**: As with PHP commands, add `2>&1` outside the command string
5. **Working Directory**: Commands must be run from the WordPress root directory or a subfolder
6. **Command Quoting**: Quote arguments properly, especially those that might contain spaces

## Summary of Best Practices

This guide has covered the best practices for executing commands in Lando environments:

1. **Lando PHP Commands**: Use either `lando php` or `lando exec appserver -- php` with proper quoting
2. **Lando Exec Commands**: Always include the `--` separator between service name and command
3. **Lando MySQL Commands**: Use the appropriate command type and escaping rules

By following these guidelines, you can ensure reliable execution of commands in Lando environments with proper error handling and output capture.
