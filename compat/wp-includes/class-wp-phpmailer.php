<?php
/**
 * Compatibility shim for WordPress PHPUnit tests
 *
 * This file provides backward compatibility for the WordPress test suite
 * which expects the WP_PHPMailer class to be in this file.
 *
 * This is needed because WordPress 6.x uses PHPMailer 6.x with namespaces,
 * while older test suites might expect the pre-namespace structure.
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
    class WP_PHPMailer extends PHPMailer\PHPMailer\PHPMailer {
        /**
         * Constructor
         */
        public function __construct($exceptions = null) {
            // Call parent constructor with exceptions enabled
            parent::__construct($exceptions !== null ? $exceptions : true);
            
            // Set default WordPress mailer settings
            $this->Encoding = 'base64';
            $this->CharSet  = 'UTF-8';
        }
    }
}
