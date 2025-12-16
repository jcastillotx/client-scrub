<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Cleanup plugin options
$option_keys = array(
    'brand_monitor_api_url',
    'brand_monitor_api_key',
);

foreach ($option_keys as $key) {
    delete_option($key);
}
