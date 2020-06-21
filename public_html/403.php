<?php
/**
 * Google Analytics Pixel tracking setup
 *
 * Full documentation: https://developers.google.com/analytics/resources/concepts/gaConceptsTrackingOverview
 *
 * @package Firewall
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

// Set Google Analytics account id
$google_ua = 'UA-XXXXXXX-X';

// Set Google GIF tracking URL
$gif_url = 'http://www.google-analytics.com/__utm.gif';

// Try to get referer
$referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 0;

// Prepare cookie unique id
$unique_id     = time() . rand(1000000000, 9999999999);
$unique_prefix = rand(10000000, 99999999) . '.' . rand(1000000000, 9999999999);

// Set mandary cookie value
$google_cookie_value = '__utma=' . $unique_prefix . '.1207701397.1207701397.1207701397.1;';

// Get Real IP Address example
$ip = 'WAF is Off';
if (isset($waf) && is_object($waf)) {
    $ip = $waf::getRealIp();
}

// Generate page and campaign
$page     = '403 Forbidden Page, IP address: ' . $ip;
$campaign = '403 Campaign, IP address: '       . $ip;

// Set Google Analytics parameters
$google_analytics_settings = array(

    'utmwv' => '4',                          // Tracking code version
    'utmn'  => $unique_id,                   // Unique ID generated for each GIF request to prevent caching of the GIF image
    'utmhn' => $_SERVER['SERVER_NAME'],      // Host Name
    'utmcs' => 'ISO-8859-1',                 // Language encoding for the browser. Some browsers don't set this, in which case it is set to "-"
    'utmul' => 'en-us',                      // Browser language
    'utmje' => '0',                          // Indicates if browser is Java-enabled. 1 is true.
    'utmcn' => '1',                          // Starts a new campaign session
    'utmdt' => $page ,                       // Page title, which is a URL-encoded string.
    'utmhid'=> time() . rand(1000, 100000),  // Hit ID, random number
    'utmr'  => $referer,                     // Referral, complete URL
    'utmp'  => $_SERVER['REQUEST_URI'],      // Page request of the current page
    'utm_campaign'  => $campaign,            // Campaign name
    'utmac' => $google_ua,                   // Account String. Appears on all requests
    'utmcc' => $google_cookie_value,         // Cookie values. If no cookie data is sent, and the request is ignored
);

// Generate analytics query
$analytics_get_query = http_build_query($google_analytics_settings);

// Generate tracking src
$google_tracking_gif = $gif_url . '?' . $analytics_get_query;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 Forbidden</title>
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

        ga('create', '<?=$google_ua?>', 'auto');
        ga('send', 'pageview');
    </script>
</head>
<body>

<h1>403 Forbidden page</h1>

<img src="<?=$google_tracking_gif?>" width="1" height="1" />

</body>
</html>