<?php
/**
 * Basic configuration file
 *
 * PHP version 7.1
 *
 * @author Ned Andonov <neoplovdiv@gmail.com>
 */

// Set base path
define('BASE_PATH', dirname(dirname(__FILE__)));
define('CONFIG_PATH', BASE_PATH . '/config/');
define('CLASSES_PATH', BASE_PATH . '/classes/');

// Debug
define('DEBUG', false); // TODO Change with dev cookie
if (defined('DEBUG') && DEBUG === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Rate Limiter default settings
define('RATE_REQUESTS', 300);
define('RATE_MINUTES', 1);

// 404 Requests limit default settings
define('REQUESTS_404', 50);
define('RATE_MINUTES_404', 1);

// Whitelist settings
define('WHITELIST_GOOGLE', true);

// Lockdown backoffice calls
define('LOCKDOWN_BACKOFFICE', 1);

// Memcache config
define('MEMCACHE_HOST', '127.0.0.1');
define('MEMCACHE_PORT', 11211);
// Htaccess block configuration
define('HTACCESS_BLOCK_ENABLED', true);
define('HTACCESS_PATH', '/public_html/.htaccess');


// Database config
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'waf');
define('DB_USER', 'test');
define('DB_PASS', '**********');