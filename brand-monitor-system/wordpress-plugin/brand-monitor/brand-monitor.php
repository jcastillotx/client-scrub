<?php
/**
 * Plugin Name: Brand Monitor
 * Description: AI-powered brand monitoring and web intelligence
 * Version: 1.0.0
 * Author: Your Company
 * Text Domain: brand-monitor
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BRAND_MONITOR_VERSION', '1.0.0');
define('BRAND_MONITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BRAND_MONITOR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once BRAND_MONITOR_PLUGIN_DIR . 'includes/api-client.php';
require_once BRAND_MONITOR_PLUGIN_DIR . 'includes/database.php';
require_once BRAND_MONITOR_PLUGIN_DIR . 'includes/notifications.php';
require_once BRAND_MONITOR_PLUGIN_DIR . 'includes/widgets.php';
require_once BRAND_MONITOR_PLUGIN_DIR . 'includes/scheduler.php';

register_activation_hook(__FILE__, 'brand_monitor_activate');
function brand_monitor_activate() {
    Brand_Monitor_Database::create_tables();
    Brand_Monitor_Scheduler::schedule_events();
}

register_deactivation_hook(__FILE__, 'brand_monitor_deactivate');
function brand_monitor_deactivate() {
    Brand_Monitor_Scheduler::clear_events();
}

add_action('plugins_loaded', 'brand_monitor_init');
function brand_monitor_init() {
    load_plugin_textdomain('brand-monitor', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (is_admin()) {
        require_once BRAND_MONITOR_PLUGIN_DIR . 'admin/dashboard.php';
        require_once BRAND_MONITOR_PLUGIN_DIR . 'admin/settings.php';
        require_once BRAND_MONITOR_PLUGIN_DIR . 'admin/reports.php';
        require_once BRAND_MONITOR_PLUGIN_DIR . 'admin/sources.php';
    }
}
