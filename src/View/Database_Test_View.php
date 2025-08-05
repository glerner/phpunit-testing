<?php
/**
 * Database Test View Class
 *
 * Handles all output related to database testing, providing a clean separation
 * between database logic and output presentation.
 *
 * @package WP_PHPUnit_Framework
 */

namespace WP_PHPUnit_Framework\View;

use function WP_PHPUnit_Framework\colored_message;

/**
 * View class for displaying database test results.
 */
class Database_Test_View {
    // Constants for styling
    const STYLE_SUCCESS = 'green';
    const STYLE_ERROR = 'red';
    const STYLE_WARNING = 'yellow';
    const STYLE_INFO = 'blue';
    const STYLE_HEADER = 'cyan';

    /**
     * Display a test section header
     *
     * @param string $title The header title
     * @return void
     */
    public function display_section_header(string $title): void {
        colored_message("\n=== $title ===\n", self::STYLE_HEADER);
    }

    /**
     * Display a test result
     *
     * @param bool $success Whether the test passed
     * @param string $message The test message
     * @param array $details Optional details about the test
     * @return void
     */
    public function display_test_result(bool $success, string $message, array $details = []): void {
        $style = $success ? self::STYLE_SUCCESS : self::STYLE_ERROR;
        $status = $success ? 'PASS' : 'FAIL';

        colored_message("[$status] $message", $style);

        if (!empty($details)) {
            foreach ($details as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                colored_message("  $key: $value", 'normal');
            }
        }

        colored_message("\n");
    }

    /**
     * Display SQL query result data
     *
     * @param array $result The query result data
     * @param int|null $query_id Optional query identifier
     * @return void
     */
    public function display_sql_result(array $result, ?int $query_id = null): void {
        $id_text = $query_id ? " #$query_id" : '';

        if (!isset($result['success']) || !$result['success']) {
            colored_message("Query$id_text failed: " . ($result['error'] ?? 'Unknown error'), self::STYLE_ERROR);
            return;
        }

        colored_message("Query$id_text successful", self::STYLE_SUCCESS);

        if (isset($result['data']) && is_array($result['data'])) {
            colored_message("Results:", self::STYLE_INFO);

            // Display column headers if available
            if (!empty($result['data']) && is_array($result['data'][0])) {
                $headers = array_keys($result['data'][0]);
                colored_message("  " . implode("\t", $headers), 'normal');
            }

            // Display rows
            foreach ($result['data'] as $row) {
                if (is_array($row)) {
                    colored_message("  " . implode("\t", $row), 'normal');
                } else {
                    colored_message("  $row", 'normal');
                }
            }
        }

        // Display metadata if available
        if (isset($result['meta']) && is_array($result['meta'])) {
            colored_message("Metadata:", self::STYLE_INFO);
            foreach ($result['meta'] as $key => $value) {
                colored_message("  $key: $value", 'normal');
            }
        }

        colored_message("\n");
    }

    /**
     * Display environment information
     *
     * @param array $db_settings Database settings
     * @return void
     */
    public function display_environment_info(array $db_settings): void {
        $this->display_section_header("Environment Information");

        $safe_settings = $db_settings;
        if (isset($safe_settings['password'])) {
            $safe_settings['password'] = '********';
        }

        foreach ($safe_settings as $key => $value) {
            colored_message("$key: $value", self::STYLE_INFO);
        }

        colored_message("\n");
    }

    /**
     * Display test summary
     *
     * @param bool $all_tests_passed Whether all tests passed
     * @param array $test_results Test results array
     * @return void
     */
    public function display_test_summary(bool $all_tests_passed, array $test_results): void {
        $this->display_section_header("Test Summary");

        $total = count($test_results);
        $passed = count(array_filter($test_results, function($test) {
            return $test['success'] ?? false;
        }));

        colored_message("Tests run: $total", self::STYLE_INFO);
        colored_message("Tests passed: $passed", self::STYLE_SUCCESS);
        colored_message("Tests failed: " . ($total - $passed), $passed === $total ? self::STYLE_SUCCESS : self::STYLE_ERROR);

        if ($all_tests_passed) {
            colored_message("\nALL TESTS PASSED!", self::STYLE_SUCCESS);
        } else {
            colored_message("\nSOME TESTS FAILED!", self::STYLE_ERROR);

            // List failed tests
            colored_message("\nFailed tests:", self::STYLE_ERROR);
            foreach ($test_results as $test) {
                if (!($test['success'] ?? true)) {
                    colored_message("- " . ($test['name'] ?? 'Unnamed test') . ": " . ($test['message'] ?? 'No message'), self::STYLE_ERROR);
                }
            }
        }

        colored_message("\n");
    }

    /**
     * Display prepared statement documentation
     *
     * @return void
     */
    public function display_prepared_statement_docs(): void {
        $this->display_section_header("Prepared Statement Documentation");

        colored_message("Prepared statements help prevent SQL injection by separating SQL code from data.", self::STYLE_INFO);
        colored_message("Parameters are bound to placeholders (?) in the query.", self::STYLE_INFO);
        colored_message("\nParameter Types:", self::STYLE_INFO);
        colored_message("  i - integer", 'normal');
        colored_message("  d - double/float", 'normal');
        colored_message("  s - string", 'normal');
        colored_message("  b - blob (binary data)", 'normal');

        colored_message("\nExample:", self::STYLE_INFO);
        colored_message("  \$query = \"SELECT * FROM users WHERE id = ? AND status = ?\"", 'normal');
        colored_message("  \$params = [1, 'active']", 'normal');
        colored_message("  \$types = ['i', 's']", 'normal');
        colored_message("  \$result = execute_mysqli_prepared_statement(\$query, \$params, \$types)", 'normal');

        colored_message("\n");
    }

    /**
     * Display a generic message with optional styling
     *
     * @param string $message The message to display
     * @param string $style The style to use (default: normal)
     * @return void
     */
    public function display_message(string $message, string $style = 'normal'): void {
        colored_message($message, $style);
    }

    /**
     * Display debug information with key-value pairs
     *
     * @param string $title The debug section title
     * @param array $debug_info Key-value pairs of debug information
     * @return void
     */
    public function display_debug(string $title, array $debug_info): void {
        colored_message("\n🔍 DEBUG: {$title}", self::STYLE_HEADER);
        foreach ($debug_info as $key => $value) {
            colored_message(sprintf("  %-15s: %s", ucfirst($key), $value), self::STYLE_INFO);
        }
    }

    /**
     * Display an SQL query with formatting
     *
     * @param string $sql The SQL query to display
     * @return void
     */
    public function display_sql_query(string $sql): void {
        colored_message("\n" . $sql . "\n", 'white');
    }

    /**
     * Display detailed SQL debug information
     *
     * @param string $sql The SQL query
     * @param array $connection Connection information
     * @param array $result Query result
     * @return void
     */
    public function display_sql_debug(string $sql, array $connection, array $result): void {
        $this->display_debug('SQL Debug Information', [
            'query' => strlen($sql) > 100 ? substr($sql, 0, 97) . '...' : $sql,
            'host' => $connection['host'] ?? 'unknown',
            'user' => $connection['user'] ?? 'unknown',
            'database' => $connection['db'] ?? 'unknown',
            'success' => isset($result['success']) && $result['success'] ? 'Yes' : 'No',
            'error' => $result['error'] ?? 'None',
            'rows' => isset($result['meta']['num_rows']) ? $result['meta']['num_rows'] : 'N/A',
            'affected' => isset($result['meta']['affected_rows']) ? $result['meta']['affected_rows'] : 'N/A',
        ]);
    }

    /**
     * Display an error message
     *
     * @param string $message The error message to display
     * @return void
     */
    public function error_message(string $message): void {
        $this->display_message("\n❌ ERROR: {$message}", self::STYLE_ERROR);
    }

    /**
     * Display a debug message
     *
     * @param string $message The debug message to display
     * @return void
     */
    public function debug_message(string $message): void {
        $this->display_message("\n🔍 DEBUG: {$message}", self::STYLE_INFO);
    }

    /**
     * Display a warning message
     *
     * @param string $message The warning message to display
     * @return void
     */
    public function warning_message(string $message): void {
        $this->display_message("\n⚠️ WARNING: {$message}", self::STYLE_WARNING);
    }

    /**
     * Display a success message
     *
     * @param string $message The success message to display
     * @return void
     */
    public function success_message(string $message): void {
        $this->display_message("\n✅ SUCCESS: {$message}", self::STYLE_SUCCESS);
    }
}
