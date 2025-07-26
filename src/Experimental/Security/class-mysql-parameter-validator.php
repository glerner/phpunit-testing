<?php
/**
 * MySQL Parameter and SQL Validator for WordPress Plugins
 *
 * Validates MySQL connection parameters and SQL queries for safety and correctness.
 * Returns WP_Error objects in a WordPress environment, or throws exceptions otherwise.
 *
 * @package    WP_PHPUnit_Framework
 * @author     Your Name
 * @since      1.0.0
 */

namespace WP_PHPUnit_Framework\Experimental\Security;

if ( ! class_exists( 'MySQL_Parameter_Validator' ) ) {
	/**
	 * Class MySQL_Parameter_Validator
	 */
	class MySQL_Parameter_Validator {
		/**
		 * Validate MySQL host parameter.
		 *
		 * @param string $host
		 * @return true|WP_Error
		 */
		public static function validate_host( $host ) {
			if ( ! is_string( $host ) || $host === '' ) {
				return self::error_or_throw( __( 'MySQL host must be a non-empty string.', 'phpunit-testing' ) );
			}
			// Allow: alphanumerics, dash, dot, underscore, colon (for port), and IPv6 brackets
			if ( ! preg_match( '/^[a-zA-Z0-9_\-\.\[\]:]+$/', $host ) ) {
				return self::error_or_throw( sprintf( __( 'MySQL host contains invalid characters: %s', 'phpunit-testing' ), $host ) );
			}
			return true;
		}

		/**
		 * Validate MySQL user parameter.
		 *
		 * @param string $user
		 * @return true|WP_Error
		 */
		public static function validate_user( $user ) {
			if ( ! is_string( $user ) || $user === '' ) {
				return self::error_or_throw( __( 'MySQL user must be a non-empty string.', 'phpunit-testing' ) );
			}
			// WordPress uses sanitize_user for usernames, but MySQL allows more, so use a safe subset
			if ( ! preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $user ) ) {
				return self::error_or_throw( sprintf( __( 'MySQL user contains invalid characters: %s', 'phpunit-testing' ), $user ) );
			}
			return true;
		}

		/**
		 * Validate MySQL password parameter.
		 *
		 * @param string $password
		 * @return true|WP_Error
		 */
		public static function validate_password( $password ) {
			if ( ! is_string( $password ) ) {
				return self::error_or_throw( __( 'MySQL password must be a string.', 'phpunit-testing' ) );
			}
			// For passwords, allow most printable ASCII except shell metacharacters
			if ( preg_match( '/[;|&`$><\\\n\r]/', $password ) ) {
				return self::error_or_throw( __( 'MySQL password contains potentially dangerous shell characters.', 'phpunit-testing' ) );
			}
			return true;
		}

		/**
		 * Validate MySQL database parameter.
		 *
		 * @param string $database
		 * @return true|WP_Error
		 */
		public static function validate_database( $database ) {
			if ( ! is_string( $database ) || $database === '' ) {
				return self::error_or_throw( __( 'MySQL database name must be a non-empty string.', 'phpunit-testing' ) );
			}
			// MySQL database names: alphanumerics, underscore, dollar, dash
			if ( ! preg_match( '/^[a-zA-Z0-9_\-$]+$/', $database ) ) {
				return self::error_or_throw( sprintf( __( 'MySQL database name contains invalid characters: %s', 'phpunit-testing' ), $database ) );
			}
			return true;
		}

		/**
		 * Validate SQL query/command for safe use.
		 *
		 * @param string $sql
		 * @param string $mode 'api' (default) for DB API, 'shell' for shell commands
		 * @return true|WP_Error
		 */
		public static function validate_sql( $sql, $mode = 'api' ) {
			if ( ! is_string( $sql ) || trim( $sql ) === '' ) {
				return self::error_or_throw( __( 'SQL query must be a non-empty string.', 'phpunit-testing' ) );
			}
			// For shell mode, block shell metacharacters and newlines
			if ( 'shell' === $mode && preg_match( '/[;|&`$><\\\n\r]/', $sql ) ) {
				return self::error_or_throw( __( 'SQL query contains potentially dangerous shell characters.', 'phpunit-testing' ) );
			}
			// Optionally, restrict to a whitelist of allowed SQL commands
			// e.g., only allow SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, etc.
			// Uncomment below to restrict:
			// if ( ! preg_match( '/^(SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|SHOW|GRANT|FLUSH) /i', ltrim( $sql ) ) ) {
			// 	return self::error_or_throw( __( 'SQL command type is not allowed.', 'phpunit-testing' ) );
			// }
			return true;
		}

		/**
		 * Validate all parameters at once.
		 *
		 * @param array $params ['host' => ..., 'user' => ..., 'password' => ..., 'database' => ..., 'sql' => ...]
		 * @param string $mode 'api' or 'shell'
		 * @return true|WP_Error
		 */
		public static function validate_all( $params, $mode = 'api' ) {
			foreach ( [ 'host', 'user', 'password', 'database', 'sql' ] as $key ) {
				if ( isset( $params[ $key ] ) ) {
					$method = 'validate_' . $key;
					$result = self::$method( $params[ $key ], ( 'sql' === $key ? $mode : null ) );
					if ( true !== $result ) {
						return $result;
					}
				}
			}
			return true;
		}

		/**
		 * Return WP_Error if available, else throw Exception.
		 *
		 * @param string $message
		 * @return WP_Error|void
		 * @throws Exception
		 */
		protected static function error_or_throw( $message ) {
			if ( function_exists( 'is_wp_error' ) && class_exists( 'WP_Error' ) ) {
				return new \WP_Error( 'mysql_param_invalid', $message );
			}
			throw new \Exception( $message );
		}
	}
}
