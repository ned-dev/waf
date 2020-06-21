<?php
/**
 * Basic includes
 *
 * PHP version 7.1
 *
 * @author Ned Andonov <neoplovdiv@gmail.com>
 */

// Holds Prepend class
require_once CLASSES_PATH . 'Prepend.php';

// Holds Firewall main class
require_once CLASSES_PATH . 'Firewall.php';

// Holds WAF public methods
require_once CLASSES_PATH . 'Waf.php';

// Holds sanitize logic
require_once CLASSES_PATH . 'Sanitize.php';

// Hold database class
require_once CLASSES_PATH . 'DB.php';

// Include cache interface
require_once CLASSES_PATH . '/cache/CacheInterface.php';

// Include default in-memory cache
require_once CLASSES_PATH . '/cache/Memcache.php';
require_once CLASSES_PATH . '/cache/Memcached.php';