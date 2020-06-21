<?php
// Get Real IP Address example
if (isset($waf) && is_object($waf)) {
    $ip = $waf::getRealIp();
    echo $ip;
}

// Block IP address
// WAF::blockIp('10.10.55.5', 'Reason to block IP');

echo '<h1>WAF v0.1 protected area</h1>' . PHP_EOL;

// Sample custom 404 page monitoring
// $waf->monitor404();