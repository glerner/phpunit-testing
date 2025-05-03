<?php
/**
 * Compatibility shim for WordPress PHPUnit tests
 *
 * This file provides backward compatibility for the WordPress test suite
 * which expects the WP_PHPMailer class to be in this file.
 *
 * This is needed because WordPress 6.x uses PHPMailer 6.x with namespaces,
 * while older test suites might expect the pre-namespace structure.
 *
 * @package WP_PHPUnit_Framework
 */

// Make sure we have the PHPMailer classes loaded
require_once ABSPATH . 'wp-includes/PHPMailer/PHPMailer.php';
require_once ABSPATH . 'wp-includes/PHPMailer/Exception.php';



// Only define the class if it doesn't already exist
if (!class_exists('WP_PHPMailer')) {
    /**
     * WordPress PHPMailer class for handling emails.
     *
     * This is a compatibility wrapper for PHPMailer\PHPMailer\PHPMailer
     * to support PHPUnit tests that expect the older class structure.
     */
    // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
    // phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
    /**
     * @property string $Encoding
     * @property string $CharSet
     */
    class WP_PHPMailer extends PHPMailer\PHPMailer\PHPMailer {
        /**
         * Constructor
         *
         * @param bool|null $exceptions Whether to throw external exceptions.
         */
        public function __construct( ?bool $exceptions = null ) {
            // Call parent constructor with exceptions enabled
            parent::__construct($exceptions !== null ? $exceptions : true);

            // Set default WordPress mailer settings
            // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
            $this->Encoding = 'base64';
            $this->CharSet  = 'UTF-8';
            // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
        }
    }
    // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
    // phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace
}
