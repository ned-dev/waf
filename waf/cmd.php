<?php
/**
 * WAF Include file
 *
 * Allows access to public WAF methods.
 * It can be included into any project.
 *
 * Sample usage:
 * require_once 'path/to/waf/waf.php'
 * WAF::block($ip, $reason);
 *
 * PHP version 7.1
 *
 * @package WAF
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

// Load WAF configuration
require_once dirname(__FILE__) . '/config/Config.php';
require_once dirname(__FILE__) . '/config/Includes.php';