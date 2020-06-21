<?php
/**
 * WAF Check config file
 *
 * PHP version 7.1
 *
 * @package Prepend
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

// Load WAF configuration
require_once dirname(__FILE__) . '/config/Config.php';
require_once dirname(__FILE__) . '/config/Includes.php';

// Initiates prepend logic and runs firewall
$waf = WAF\CheckIntegrity::init();