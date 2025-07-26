<?php
/**
 * Lando Database Connection Wrapper
 *
 * A wrapper class that delegates database operations to execute_mysqli_lando
 * when running in a Lando environment.
 *
 * IMPORTANT: This class is specifically for the "dipping into Lando" scenario,
 * where PHP is running on the host but needs to access a database in Lando.
 * It does NOT maintain persistent connections and creates a new temporary PHP
 * script for each query via execute_mysqli_lando().
 *
 * For PHP code running INSIDE a Lando container, use Database_Connection_Manager
 * instead, which provides direct mysqli connections with connection pooling.
 *
 * @package GL_Reinvent\Service
 */

namespace GL_Reinvent\Service;

/**
 * Class Lando_Database_Connection
 * 
 * Handles database connections in a Lando environment by delegating to execute_mysqli_lando.
 * Use this class when PHP is running on the host but needs to access a database in Lando.
 * For PHP running inside Lando, use Database_Connection_Manager instead.
 */
class Lando_Database_Connection {
    /**
     * Database connection settings
     *
     * @var array
     */
    private $db_settings;

    /**
     * The database name
     *
     * @var string|null
     */
    private $db_name;

    /**
     * Constructor
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $pass Database password
     * @param string|null $db Optional database name
     */
    public function __construct($host, $user, $pass, $db = null) {
        $this->db_settings = array(
            'db_host' => $host,
            'db_user' => $user,
            'db_pass' => $pass,
            'db_name' => $db ?? ''
        );
        $this->db_name = $db;
    }

    /**
     * Execute a query
     *
     * @param string $sql The SQL query to execute
     * @return mixed The query result
     * @throws \RuntimeException If the query fails
     */
    public function query($sql) {
        $result = \WP_PHPUnit_Framework\execute_mysqli_lando($sql, $this->db_settings, $this->db_name);
        
        if (!$result['success']) {
            throw new \RuntimeException(
                'Database query failed: ' . ($result['error'] ?? 'Unknown error'),
                $result['error_code'] ?? 0
            );
        }

        return $result['data'];
    }

    /**
     * Get the last insert ID
     * 
     * @return int
     */
    public function insert_id() {
        return 0; // Can be enhanced if execute_mysqli_lando returns the last insert ID
    }

    /**
     * Get the number of affected rows
     * 
     * @return int
     */
    public function affected_rows() {
        return 0; // Can be enhanced if execute_mysqli_lando returns the number of affected rows
    }

    /**
     * Escape a string for use in a query
     * 
     * @param string $str The string to escape
     * @return string The escaped string
     */
    public function escape($str) {
        return str_replace(
            array('\\', '\0', '\n', '\r', "\'", '\"', '\Z'),
            array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'),
            $str
        );
    }

    /**
     * Close the connection
     * 
     * @return void
     */
    public function close() {
        // No-op since we don't maintain a persistent connection
    }

    /**
     * Check if the connection is still alive
     * 
     * @return bool
     */
    public function ping() {
        try {
            $result = \WP_PHPUnit_Framework\execute_mysqli_lando('SELECT 1', $this->db_settings, $this->db_name);
            return $result['success'];
        } catch (\Exception $e) {
            return false;
        }
    }
}
