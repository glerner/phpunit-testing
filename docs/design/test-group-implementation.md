# Test Group Implementation Alternatives

This document outlines an approach for implementing test groups in the PHPUnit testing framework. Should run faster than loading PHPUnit in a batch, once for each test file in a group.

## Dynamic PHPUnit XML Configuration Approach

### Overview

Instead of running multiple test files individually, this approach generates a custom PHPUnit XML configuration file for each test group. This leverages PHPUnit's native capabilities for organizing tests while maintaining separation between different test types.

### Implementation Steps

1. **Create a generator script** (`generate-group-config.php`) that:
   - Reads group definitions from `.env.testing` in the INI-style format
   - Creates a custom PHPUnit XML configuration file for the requested group
   - Organizes files by test type within the configuration

2. **Modify the test script** to:
   - Call the generator when `--group` is specified
   - Run PHPUnit with the generated configuration file

### Example Generated Configuration

```xml
<?xml version="1.0"?>
<phpunit bootstrap="../tests/bootstrap/bootstrap.php" colors="true" verbose="true">
    <testsuites>
        <testsuite name="user-interface-unit">
            <file>../tests/unit/ui/class-admin-page-test.php</file>
            <file>../tests/unit/ui/class-settings-page-test.php</file>
        </testsuite>
        <testsuite name="user-interface-wp-mock">
            <file>../tests/wp-mock/ui/class-palette-display-test.php</file>
        </testsuite>
        <testsuite name="user-interface-integration">
            <file>../tests/integration/admin/class-admin-integration-test.php</file>
        </testsuite>
    </testsuites>
    <!-- Other configuration options copied from main config -->
</phpunit>
```

### Advantages

1. **Uses PHPUnit's native capabilities** for organizing tests
2. **Preserves test type separation** by creating separate testsuites within the configuration
3. **Maintains bootstrap compatibility** by using the appropriate bootstrap for each test type
4. **Simplifies execution** by running a single PHPUnit command instead of multiple commands
5. **Consolidated test results** making it easier to see overall status ("all passed" or a complete list of errors)
6. **Respects database configuration principles** by maintaining separation between test databases and WordPress databases

### Considerations

1. **File generation**: Requires writing a temporary file, which may need cleanup
2. **Complexity**: More complex implementation than the simple loop approach
3. **Flexibility**: May require additional logic to handle environment variables and other settings

### Example Generator Script

```php
<?php
/**
 * Generates a custom PHPUnit XML configuration file for a specific test group
 */

// Get group name from command line
$group_name = $argv[1] ?? null;
if (!$group_name) {
    echo "Usage: php generate-group-config.php GROUP_NAME\n";
    exit(1);
}

// Load .env.testing file
$env_file = __DIR__ . '/../.env.testing';
if (!file_exists($env_file)) {
    echo "Error: .env.testing file not found\n";
    exit(1);
}

// Parse INI sections to find the requested group
$in_group_section = false;
$group_files = [];
$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    // Check if we're entering the requested group section
    if (preg_match('/^\[' . preg_quote($group_name, '/') . '\]$/', $line)) {
        $in_group_section = true;
        continue;
    }

    // Check if we're leaving the group section
    if ($in_group_section && preg_match('/^\[/', $line)) {
        $in_group_section = false;
        continue;
    }

    // Collect file patterns from the group section
    if ($in_group_section && !preg_match('/^#/', $line) && trim($line) !== '') {
        $group_files[] = trim($line);
    }
}

if (empty($group_files)) {
    echo "Error: No files found for group '$group_name'\n";
    exit(1);
}

// Expand file patterns to get actual files
$unit_files = [];
$wp_mock_files = [];
$integration_files = [];

foreach ($group_files as $pattern) {
    $files = glob($pattern);
    foreach ($files as $file) {
        if (strpos($file, 'tests/unit/') !== false) {
            $unit_files[] = $file;
        } elseif (strpos($file, 'tests/wp-mock/') !== false) {
            $wp_mock_files[] = $file;
        } elseif (strpos($file, 'tests/integration/') !== false) {
            $integration_files[] = $file;
        }
    }
}

// Generate the PHPUnit XML configuration
$xml = new SimpleXMLElement('<?xml version="1.0"?><phpunit></phpunit>');
$xml->addAttribute('bootstrap', '../tests/bootstrap/bootstrap.php');
$xml->addAttribute('colors', 'true');
$xml->addAttribute('verbose', 'true');

$testsuites = $xml->addChild('testsuites');

// Add unit test files
if (!empty($unit_files)) {
    $testsuite = $testsuites->addChild('testsuite');
    $testsuite->addAttribute('name', $group_name . '-unit');
    foreach ($unit_files as $file) {
        $testsuite->addChild('file', $file);
    }
}

// Add WP Mock test files
if (!empty($wp_mock_files)) {
    $testsuite = $testsuites->addChild('testsuite');
    $testsuite->addAttribute('name', $group_name . '-wp-mock');
    foreach ($wp_mock_files as $file) {
        $testsuite->addChild('file', $file);
    }
}

// Add integration test files
if (!empty($integration_files)) {
    $testsuite = $testsuites->addChild('testsuite');
    $testsuite->addAttribute('name', $group_name . '-integration');
    foreach ($integration_files as $file) {
        $testsuite->addChild('file', $file);
    }
}

// Add other configuration options (copied from main config)
// ... (add coverage settings, etc.)

// Save the generated configuration
$output_file = __DIR__ . "/../config/phpunit-group-{$group_name}.xml";
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
$dom->save($output_file);

echo "Generated configuration file: $output_file\n";
```

### Test Script Integration

```bash
# Add to bin/test.sh
if [[ "$1" == "--group" ]]; then
    GROUP_NAME="$2"
    shift 2

    # Generate custom configuration for the group
    php bin/generate-group-config.php "$GROUP_NAME"

    # Run PHPUnit with the generated configuration
    CONFIG_FILE="config/phpunit-group-${GROUP_NAME}.xml"
    if [ -f "$CONFIG_FILE" ]; then
        echo "Running tests for group: $GROUP_NAME"
        $PHPUNIT -c "$CONFIG_FILE"
    else
        echo "Error: Configuration file not generated for group: $GROUP_NAME"
        exit 1
    fi
fi
```

## Comparison with Loop Approach

The loop approach (running each file individually) is simpler to implement but may be less efficient for large test groups. The XML configuration approach leverages PHPUnit's native capabilities and provides a more integrated solution.

Both approaches align with the design principles of the PHPUnit testing framework, particularly the separation of configuration from implementation and the support for modular architectural organization.
