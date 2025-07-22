<?php
/**
 * Database Connection Manager
 *
 * Manages database connections with connection pooling and validation.
 *
 * @package GL_Reinvent\Service
 */

namespace GL_Reinvent\Service;

class Database_Connection_Manager {
    /**
     * Singleton instance
     *
     * @var Database_Connection_Manager|null
     */
    private static $instance = null;

    /**
     * Active database connections
     *
     * @var array<string, array{
     *     connection: \mysqli,
     *     last_used: float,
     *     created_at: float,
     *     params: array{host: string, user: string, db: ?string}
     * }>
     */
    private array $connections = [];

    /**
     * Maximum idle time in seconds before a connection is considered stale (5 minutes)
     */
    private const MAX_IDLE_TIME = 300;

    /**
     * Maximum lifetime of a connection in seconds (30 minutes)
     */
    private const MAX_CONNECTION_LIFETIME = 1800;

    /**
     * Class constructor.
     */
    private function __construct() {}

    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get a database connection
     *
     * @param string $host Database host.
     * @param string $user Database user.
     * @param string $pass Database password.
     * @param string|null $db Optional database name.
     * @return \mysqli
     * @throws \RuntimeException If connection fails.
     */
    public function get_connection($host, $user, $pass, $db = null) {
        $this->cleanup_stale_connections();
        $connection_key = $this->get_connection_key($host, $user, $db);

        // Return existing valid connection if available
        if (isset($this->connections[$connection_key])) {
            $connection = $this->connections[$connection_key];
            if ($this->validate_connection($connection['connection'])) {
                $connection['last_used'] = microtime(true);
                return $connection['connection'];
            }
            // Remove invalid connection
            $this->close_connection($connection_key);
        }

        // Create new connection
        $mysqli = new \mysqli($host, $user, $pass, $db ?? '');
        if ($mysqli->connect_error) {
            throw new \RuntimeException(
                "Failed to connect to database: " . $mysqli->connect_error,
                $mysqli->connect_errno
            );
        }

        $this->connections[$connection_key] = [
            'connection' => $mysqli,
            'last_used' => microtime(true),
            'created_at' => microtime(true),
            'params' => [
                'host' => $host,
                'user' => $user,
                'db' => $db
            ]
        ];

        return $mysqli;
    }

    /**
     * Validate if a connection is still active
     *
     * @param \mysqli $connection The connection to validate.
     * @return bool
     */
    private function validate_connection(\mysqli $connection) {
        try {
            return $connection->ping();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Close a specific connection
     *
     * @param string $connection_key The connection key.
     * @return void
     */
    public function close_connection(string $connection_key): void {
        if (isset($this->connections[$connection_key])) {
            $connection = $this->connections[$connection_key];
            @$connection['connection']->close();
            unset($this->connections[$connection_key]);
        }
    }

    /**
     * Close all active connections
     *
     * @return void
     */
    public function close_all_connections(): void {
        foreach (array_keys($this->connections) as $key) {
            $this->close_connection($key);
        }
    }

    /**
     * Clean up stale connections
     *
     * @return void
     */
    private function cleanup_stale_connections() {
        $now = microtime(true);

        foreach ($this->connections as $key => $connection) {
            $idleTime = $now - $connection['last_used'];
            $age = $now - $connection['created_at'];

            if ($idleTime > self::MAX_IDLE_TIME || $age > self::MAX_CONNECTION_LIFETIME) {
                $this->close_connection($key);
            }
        }
    }

    /**
     * Generate a connection key
     *
     * @param string $host Database host.
     * @param string $user Database user.
     * @param string|null $db Optional database name.
     * @return string
     */
    private function get_connection_key(string $host, string $user, ?string $db = null): string {
        return md5(implode('|', [$host, $user, $db ?? '']));
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance.
     *
     * @throws \RuntimeException Always throws an exception.
     */
    public function __wakeup() {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    /**
     * Clean up on destruction.
     */
    public function __destruct() {
        $this->close_all_connections();
    }
}
