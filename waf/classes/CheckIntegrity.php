<?php
/**
 * @package WAF
 */
namespace WAF;

/**
 * CheckIntegrity Class
 *
 * PHP version 7.1
 *
 * @author Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * Prepend Class
 *
 */
class CheckIntegrity
{
    /**
     * Run method
     *
     * @return void
     */
    public static function init()
    {
        // Errors status
        $errors = false;

        // Check if firewall is enabled and running
        if (!self::checkIfRunning()) {
            $errors = true;
        }

        // Check .htaccess settings and read-write permissions
        if (!self::htaccessCheck()) {
            $errors = true;
        }

        // Check XML and rate limiter configuration
        if (!self::checkRateLimiterConfig()) {
            $errors = true;
        }

        // Check Memcached settings
        if (!self::checkMemcached()) {
            $errors = true;
        }

        // Check errors status
        if ($errors) {
            echo 'FAIL';
            // Send email alert
            self::sendEmailAlert();
        } else {
            echo 'PASS';
        }
    }

    public static function sendEmailAlert()
    {
        // Logs path
        $logs_path = BASE_PATH . '/logs/';

        // Check if we should block request
        if (is_file($logs_path . 'block-time')) {
            $block_time = file_get_contents($logs_path . 'block-time');
            $interval  = abs($block_time - time());
            $minutes   = round($interval / 60);
            // Block for 90 minutes
            if ($minutes >= 90) {
                file_put_contents($logs_path . 'counter', 0);
                unlink($logs_path . 'counter-time');
                unlink($logs_path . 'block-time');
            } else {
                // echo 'BLOCK Diff. in minutes is: ' . $minutes;
                return;
            }
        }

        // Save current time
        if (!is_file($logs_path . 'counter-time')) {
            file_put_contents($logs_path . 'counter-time', time());
        }
        $last_call_time = file_get_contents($logs_path . 'counter-time');

        // DB can be down and we use simple text file counter to throttle email sending
        $count = file_get_contents($logs_path . 'counter');
        $count++;
        file_put_contents($logs_path . 'counter', $count);

        // Check interval
        $interval  = abs($last_call_time - time());
        $minutes   = round($interval / 60);
        // echo 'Diff. in minutes: '.$minutes;

        if ($count >= 2 && $minutes < 2 || $count > 20) {
            // Block requests
            file_put_contents($logs_path . 'block-time', time());
        }

        // Send email alert
        mail(NOTIFICATIONS_EMAIL, 'WAF integrity check failed!', 'WAF disabled! Incorrect configuration settings.');
    }

    /**
     * Check if firewall is enabled and running
     *
     */
    public static function checkIfRunning()
    {
        $htaccess_contents = file_get_contents(HTACCESS_PATH);
        if (strstr($htaccess_contents, '#php_value auto_prepend_file')) {
            echo 'WAF disabled!';
            return false;
        }

        if (!strstr($htaccess_contents, 'php_value auto_prepend_file') || !strstr($htaccess_contents, '/waf/init.php')) {
            echo 'WAF disabled!';
            return false;
        }

        // Valid configuration
        return true;
    }

    /**
     * Check .htaccess settings and read-write permissions
     *
     */
    public static function htaccessCheck()
    {
        if (!file_exists(HTACCESS_PATH) || !is_readable(HTACCESS_PATH) || !is_writable(HTACCESS_PATH))
        {
            // Cannot access .htaccess file
            echo 'Cannot access .htaccess file! Permanent WAF block disabled!';
            return false;
        }

        // Valid configuration
        return true;
    }

    /**
     * Check rate limiter config
     *
     */
    public static function checkRateLimiterConfig()
    {
        // Load custom rate limiters settings
        $rates_settings = simplexml_load_file(CONFIG_PATH . 'rate-limit-settings.xml');

        // Convert rate limiter settings XML data into array
        $rates_json  = json_encode($rates_settings);
        $rates_array = json_decode($rates_json,true);

        if (empty($rates_array)) {
            echo 'Invalid rate-limit-settings.xml configuration!';
            return false;
        }

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

            if (empty($val['@attributes']['id']) || empty($val['@attributes']['match']) || empty($val['@attributes']['requests'])) {
                echo 'Invalid attribute found in rate-limit-settings.xml configuration!';
                return false;
            }
        }

        // Valid configuration
        return true;
    }

    /**
     * Check Memcached settings
     *
     */
    public static function checkMemcached()
    {
        if (!class_exists('Memcache')) {
            echo 'Memcache class not available!';
            return false;
        }

        // Simple memcache test
        $mem = new \Memcache();
        $mem->addServer(MEMCACHE_HOST, MEMCACHE_PORT);

        $result = $mem->get("test_key");

        if ($result) {
            // echo $result;
        } else {
            // echo "No matching key found.  I'll add that now!";
            $mem->set("test_key", "I am data held in memcached!") or die("Couldn't save anything to memcached...");
        }

        // Valid configuration
        return true;
    }

}