<?php
/**
 * WAF Init file
 *
 * Warning: For security purposes this file must always be auto-prepended before any HTTP request
 * For example:
 * php_value auto_prepend_file 'path/to/waf/Prepend.php'
 *
 * PHP version 7.1
 *
 * @package Prepend
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

// Start session
session_start();

// Load WAF configuration
require_once dirname(__FILE__) . '/config/Config.php';
require_once dirname(__FILE__) . '/config/Includes.php';

// Initiates prepend logic and runs firewall
$waf = WAF\Prepend::init();
$waf->run();