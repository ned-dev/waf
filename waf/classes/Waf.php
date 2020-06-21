<?php
/**
 * @package WAF
 */
namespace WAF;

/**
 * WAF Public Methods Class
 *
 * Contains publicly available WAF methods
 *
 * PHP version 7.1
 *
 * @author Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * WAF Run Class
 *
 */
class Cmd
{
    /**
     * Get Real IP method
     *
     * @return string IP Address
     */
    public static function getRealIp()
    {
        return Firewall::getRealIp();
    }

    /**
     * Block IP address method
     *
     * @param string $ip     IP Address to block
     * @param string $reason Reason for blocking
     *
     * @return void
     */
    public static function blockIp($ip, $reason)
    {
        return Firewall::blockIp($ip, $reason);
    }

}