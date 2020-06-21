<?php
/**
 * @package WAF
 */
namespace WAF;

use WAF\Cache\MemcacheInterface;

/**
 * Prepend Security Class
 *
 * Warning: For security purposes this class must always be auto-prepended before any HTTP request
 * For example:
 * php_value auto_prepend_file 'path/to/waf/Prepend.php'
 *
 * PHP version 7.1
 *
 * @package Prepend
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * Prepend Class
 *
 */
class Prepend
{
    /**
     * Run method
     *
     * @return void
     */
    public static function init()
    {
        // Do not sanitize admin area request
        $is_admin = (isset($_SESSION['isLoggedInAdmin']) && $_SESSION['isLoggedInAdmin'] === true) ? true : false;
        if (!$is_admin) {
            // Initiate request sanitizing
            Sanitize::globals();
        }

        /**
         * Starting firewall.
         * We are using try-catch block in order not to break site functionality even if
         * firewall code breaks.
         */
        try {
            // Initiate firewall
            return self::_startFirewall();

        } catch (Exception $e) {
            // TODO
            // Log firewall error
        }
    }

    /**
     * Initiate Firewall method
     *
     */
    private static function _startFirewall()
    {
        // Prepare in-memory cache
        $memcached = new \Memcache();
        $memcached->addServer(MEMCACHE_HOST, MEMCACHE_PORT);

        // Load cache
        $cache = new MemcacheInterface($memcached);

        // Initiate firewall
        $firewall = new Firewall();
        $firewall->setCache($cache);

        return $firewall;
    }
}