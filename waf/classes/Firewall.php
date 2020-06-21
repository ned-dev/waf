<?php
/**
 * @package WAF
 */
namespace WAF;

use WAF\Cache\CacheInterface;

/**
 * Main web application firewall class
 *
 * PHP version 7.1
 *
 * @package Firewall
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * Firewall Class
 *
 */
class Firewall
{
    /**
     * @var CacheInterface
     */
    protected $_cache;

    /**
     * Holds DB object
     *
     * @var object
     */
    protected $_db;

    /**
     * @var string Holds current session IP address
     */
    public $ip;

    /**
     * @var array Holds whitelisted IP addresses
     *
     */
    private $_whitelist_ips = array();

    /**
     * @var array Holds verified admin IP addresses
     *
     */
    private $_whitelist_ips_admin = array();

    /**
     * @var array Holds custom rate limiters settings
     *
     */
    private $_custom_rate_limiters = array();

    /**
     * @var string Rate Limiter keys prefix
     */
    public $rate_limiter_prefix = 'rate-limiter-';

    /**
     * Run method
     *
     * @return void
     */
    public function run()
    {
        // Get visitor real IP address
        $this->ip = self::getRealIp();

        // Load whitelisted IPs
        $cached_whitelist = $this->_cache->get('waf-whitelist');
        if (isset($cached_whitelist) && !empty($cached_whitelist)) {
            $this->_whitelist_ips = $cached_whitelist;
        } else {
            // Cache white list
            $this->cacheWhitelist();
        }

        // Load verified admin IPs
        $cached_whitelist_admin = $this->_cache->get('waf-whitelist-admin');
        if (isset($cached_whitelist_admin) && !empty($cached_whitelist_admin)) {
            $this->_whitelist_ips_admin = $cached_whitelist_admin;
        } else {
            // Cache white list
            $this->cacheAdminWhitelist();
        }

        // Check if IP is whitelisted
        if (in_array($this->ip, $this->_whitelist_ips)) {
            return true;
        }

        // Start rate limiter
        $this->rateLimiter($this->ip, RATE_REQUESTS, RATE_MINUTES);

        // Load custom rate limiters settings
        $custom_rate_limiters = $this->_cache->get('custom-rate-limiters');
        if (isset($custom_rate_limiters) && !empty($custom_rate_limiters)) {
            $this->_custom_rate_limiters = $custom_rate_limiters;
        } else {
            // Cache white list
            $this->cacheCustomRateLimiters();
        }

        // Run custom rate limits per URL
        if (!empty($this->_custom_rate_limiters)) {
            foreach ($this->_custom_rate_limiters as $key => $custom_rate) {
                $this->rateLimiter(
                    $this->ip,
                    $custom_rate['requests'],
                    $custom_rate['minutes'],
                    $custom_rate['id'],
                    $custom_rate['url'],
                    $custom_rate['match']
                );
            }
        }

        // Start server redirect status checks
        $this->checkRedirectStatus();
    }

    /**
     * Caches current IPs whitelist into memory
     *
     * @param int $minutes Minutes data stays in cache
     *
     * @return void
     */
    public function cacheWhitelist($minutes = 60)
    {
        // Load IPs whitelist XML file
        $ips_whitelist = simplexml_load_file(CONFIG_PATH . 'ips-whitelist.xml');

        // Convert IPs whitelist XML data to array
        $ips_json  = json_encode($ips_whitelist);
        $ips_array = json_decode($ips_json,true);

        // Cache whitelist into memory
        $ips_array = $ips_array['add'];
        $whitelist_array = array();
        foreach ($ips_array as $key => $val) {
            $whitelist_array[] = $val['@attributes']['ipAddress'];
        }

        // Set cache expiration
        $this->_cache->setTtl($minutes * 60 + 1);

        // Save data into memory
        $this->_cache->set('waf-whitelist', $whitelist_array);
        $this->_whitelist_ips = $whitelist_array;
    }

    /**
     * Caches current custom rate limiters settings into memory
     *
     * @param int $minutes Minutes data stays in cache
     *
     * @return void
     */
    public function cacheCustomRateLimiters($minutes = 60)
    {
        // Load custom rate limiters settings
        $rates_settings = simplexml_load_file(CONFIG_PATH . 'rate-limit-settings.xml');

        // Convert rate limiter settings XML data into array
        $rates_json  = json_encode($rates_settings);
        $rates_array = json_decode($rates_json,true);

        // Cache custom rate limiters into memory
        $rates_array = $rates_array['add'];
        $custom_rate_limiters_array = array();
        foreach ($rates_array as $key => $val) {
            $custom_rate_limiters_array[] = array(
                'id'          => $val['@attributes']['id'],
                'url'         => $val['@attributes']['url'],
                'match'       => $val['@attributes']['match'],
                'requests'    => $val['@attributes']['requests'],
                'minutes'     => $val['@attributes']['minutes'],
                'description' => $val['@attributes']['description'],
            );
        }

        // Set cache expiration
        $this->_cache->setTtl($minutes * 60 + 1);

        // Save data into memory
        $this->_cache->set('custom-rate-limiters', $custom_rate_limiters_array);
        $this->_custom_rate_limiters = $custom_rate_limiters_array;
    }

    /**
     * Set in-memory cache
     *
     * @param CacheInterface $cache
     *
     * @return Firewall
     */
    public function setCache(CacheInterface $cache)
    {
        $this->_cache = $cache;
        return $this;
    }

    /**
     * Caches verified admin IPs whitelist into memory
     *
     * @param int $minutes Minutes data stays in cache
     *
     * @return void
     */
    public function cacheAdminWhitelist($minutes = 1)
    {
        // Prepare database
        $this->_db = new DB();
        $this->_db->setServerDetails(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Get the highest migration
        $sql = "SELECT ip
                FROM login_ips
                WHERE `status` = 'VERIFIED'";

        // Get DB Version
        $ips_array = $this->_db->getAll($sql);

        // Cache whitelist into memory
        $whitelist_array_admin = array();
        foreach ($ips_array as $key => $val) {
            $whitelist_array_admin[] = $val['ip'];
        }

        // Set cache expiration
        $this->_cache->setTtl($minutes * 60 + 1);

        // Save data into memory
        $this->_cache->set('waf-whitelist-admin', $whitelist_array_admin);
        $this->_whitelist_ips_admin = $whitelist_array_admin;
    }

    /**
     * Gets real IP
     *
     * @return string
     */
    public static function getRealIp()
    {
        // Order of environmental variables is very important
        foreach ( array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED',
                        'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key) {

            // Check if environmental variable exists
            if (array_key_exists($key, $_SERVER) === true) {

                foreach (explode(',', $_SERVER[$key]) as $ip) {

                    // If validation is successful, return IP address
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }

        // In case IP is fake and not validated
        return '0.0.0.0';
    }

    /**
     * High performance and accurate rate limiter implementation.
     * It allows only a certain number of requests per certain amount of minutes.
     *
     * In order to protect multiple resources we can use:
     * rateLimiter($ip, $allowed_requests, $minutes, 'script1.php');
     * rateLimiter($ip, $allowed_requests, $minutes, 'script2.php');
     * rateLimiter($ip, $allowed_requests, $minutes, 'url-prefix');
     *
     * @param string $ip               IP address
     * @param int    $allowed_requests Number of allowed requests
     * @param int    $minutes          Minutes
     * @param string $prefix           Optional - Custom rate limiter namespace
     * @param string $url              Optional - Set rate limiter for specific URL only
     * @param string $url_match        Optional - URL match setting(exact or match)
     */
    public function rateLimiter($ip, $allowed_requests, $minutes, $prefix = '', $url = '', $url_match = 'like')
    {
        // Check if rate limit is for specific URL only
        if (!empty($url)) {
            $current_request = $_SERVER['REQUEST_URI'];
            // Exact match check
            if ($url_match == 'exact' && $current_request != $url) {
                return;
            }
            // Like match check
            if ($url_match == 'like' && !strstr($current_request, $url)) {
                return;
            }
        }
        
        // Set custom prefix
        if (!empty($prefix)) {
            $this->rate_limiter_prefix = 'rate-limiter-' . $prefix;
        }

        // Initialize IP session prefix
        $this->rate_limiter_prefix = $this->rate_limiter_prefix . $ip;

        // Initialize number of requests
        $requests = 0;

        // Get current rates for the past minutes
        foreach ($this->getRateKeys($minutes) as $key) {

            // Get requests per minute
            $requests_per_minute = $this->_cache->get($key);

            if (false !== $requests_per_minute) {
                // Increment current requests
                $requests += $requests_per_minute;
            }
        }

        // Start requests per minute logging
        if (false === $requests_per_minute) {
            $this->_cache->setTtl($minutes * 60 + 1);
            $this->_cache->set($key, 1);
        } else {
            $this->_cache->increment($key, 1);
        }

        // Check if requests per minute limit is reached
        if ($requests > $allowed_requests) {
            // Requests limit reached
            // header("HTTP/1.0 529 Too Many Requests");
            // Block IP address
            $this->blockIp($ip, "Rate limiter: $this->rate_limiter_prefix, Reason: Too Many Requests");
        }
    }

    /**
     * Check server redirect status method
     *
     * '400 Bad Request', 'The request cannot be fulfilled due to bad syntax.'
     * '403 Forbidden', 'The server has refused to fulfil your request.'
     * '404 Not Found', 'The page you requested was not found on this server.'
     * '405 Method Not Allowed', 'The method specified in the request is not allowed for the specified resource.'
     * '408 Request Timeout', 'Your browser failed to send a request in the time allowed by the server.'
     * '500 Internal Server Error', 'The request was unsuccessful due to an unexpected condition encountered by the server.'
     * '502 Bad Gateway', 'The server received an invalid response while trying to carry out the request.'
     * '504 Gateway Timeout', 'The upstream server failed to send a request in the time allowed by the server.'
     *
     * @param string $custom_status Ability to set custom status
     *
     * @return void
     */
    public function checkRedirectStatus($custom_status = '') {

        // Get current redirect status
        if (empty($_SERVER['REDIRECT_STATUS']) && empty($custom_status)) {
            return;
        }
        $status = !empty($custom_status) ? $custom_status : $_SERVER['REDIRECT_STATUS'];

        // Check for 404 errors
        if ($status == 404) {

            // Set 404 prefix
            $this->rate_limiter_prefix = 'rate-limiter-404-';

            // Start 404 rate limiter
            $this->rateLimiter($this->ip, REQUESTS_404, RATE_MINUTES_404);
        }

        // Check for 302 errors
        if ($status == 302) {

            // Set 302 prefix
            $this->rate_limiter_prefix = 'rate-limiter-302-';

            // Start 302 rate limiter
            $this->rateLimiter($this->ip, REQUESTS_302, RATE_MINUTES_302);
        }

    }

    /**
     * Monitor 404 pages
     *
     */
    public function monitor404()
    {
       $this->checkRedirectStatus('404');
    }

    /**
     * Monitor 302 pages
     *
     */
    public function monitor302()
    {
        $this->checkRedirectStatus('302');
    }

    /**
     * Reverse DNS lookup to verify Google IP address
     * For more information: https://support.google.com/webmasters/answer/80553?hl=en
     *
     * @param string $ip IP address
     *
     * @return bool True / false
     */
    public static function isGoogleIp($ip)
    {
        // For example: crawl-66-249-66-1.googlebot.com
        $hostname = gethostbyaddr($ip);

        return preg_match('/\.googlebot|google\.com$/i', $hostname);
    }

    /**
     * Check if request comes from Google
     *
     * @param string $ip    IP Address
     * @param string $agent User agent
     *
     * @return bool True / false
     */
    public static function isGoogleRequest($ip = null, $agent = null)
    {
        // Get IP address
        if (is_null($ip)) {
            $ip = self::getRealIp();
        }

        // Get user agent
        if (is_null($agent)) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        }

        // Initialize check
        $is_valid_request = false;

        // Check user agent string
        if (strpos($agent, 'Google') !== false && self::isGoogleIp($ip)) {
            $is_valid_request = true;
        }

        return $is_valid_request;
    }

    /**
     * Block IP address method
     *
     * @param string $ip     IP Address to block
     * @param string $reason Reason for blocking
     *
     * @return bool
     */
    public static function blockIp($ip, $reason = 'Default WAF block') {

        // Check if we should whitelist Google requests
        if (defined('WHITELIST_GOOGLE') && self::isGoogleRequest($ip) === true) {
            // Do not block IP
            return true;
        }

        // Check if htaccess block is enabled
        if (defined('HTACCESS_BLOCK_ENABLED') && HTACCESS_BLOCK_ENABLED === true) {
            self::htaccessBlock($ip, $reason);
        }

        // Kill further scripts execution
        die('IP Blocked' . PHP_EOL);
    }

    /**
     * Htaccess block IP address
     *
     * @param string $ip     IP Address
     * @param string $reason Reason for blocking
     *
     * @return bool
     */
    public static function htaccessBlock($ip, $reason) {

        if (defined('HTACCESS_PATH')) {

            // Open the file to get existing content
            $current = file_get_contents(HTACCESS_PATH);

            // Check if IP exists
            if (!strstr($current, $ip) && filter_var($ip, FILTER_VALIDATE_IP)) {

                // Get current date and time
                $timestamp = date('Y-m-d H:i:s');
                // Append new IP to the file
                $current .= "\n# $timestamp IP Block reason: $reason\nDeny from $ip\n" . 'SetEnvIf X-Forwarded-For "' . $ip . '" DenyAccess';

                // Write the contents back to the file
                file_put_contents(HTACCESS_PATH, $current);
            }

            // IP was successfully added in deny list
            return true;

        } else {

            // Htaccess file not found
            return false;
        }
    }
    
    /**
     * Get current rate limiter key
     *
     * @param int $minutes Minutes
     *
     * @return array
     */
    private function getRateKeys($minutes) {

        // Initialize keys
        $keys = array();

        // Get current time
        $now  = time();

        // Load current keys for the past minutes
        for ($time = $now - $minutes * 60; $time <= $now; $time += 60) {
            // Add hours and minutes in order to accurately calculate requests for the period
            $keys[] = $this->rate_limiter_prefix . date("dHi", $time);
        }

        // Return current rate limiter keys
        return $keys;
    }
}