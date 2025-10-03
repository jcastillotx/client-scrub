<?php
/**
 * Plugin Name: Brand Reputation Monitor
 * Plugin URI: https://yourwebsite.com/brand-reputation-monitor
 * Description: Monitor client brand reputation by tracking mentions, articles, news, and backlinks across the web using AI-powered analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: brand-reputation-monitor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BRM_VERSION', '1.0.0');

// Include required files
require_once BRM_PLUGIN_PATH . 'includes/class-brm-database.php';
require_once BRM_PLUGIN_PATH . 'includes/class-brm-client-manager.php';
require_once BRM_PLUGIN_PATH . 'includes/class-brm-monitor.php';
require_once BRM_PLUGIN_PATH . 'includes/class-brm-ai-service.php';
require_once BRM_PLUGIN_PATH . 'includes/class-brm-admin.php';

/**
 * Main plugin class
 */
class BrandReputationMonitor {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize plugin components
        new BRM_Database();
        new BRM_Client_Manager();
        new BRM_Monitor();
        new BRM_AI_Service();
        new BRM_Admin();
        
        // Load text domain for translations
        load_plugin_textdomain('brand-reputation-monitor', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // Create database tables
        BRM_Database::create_tables();
        
        // Schedule daily monitoring
        if (!wp_next_scheduled('brm_daily_monitoring')) {
            wp_schedule_event(time(), 'daily', 'brm_daily_monitoring');
        }
        
        // Set default options
        add_option('brm_settings', array(
            'ai_provider' => 'openrouter',
            'api_key' => '',
            'monitoring_frequency' => 'daily',
            'max_results_per_client' => 50
        ));
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('brm_daily_monitoring');
    }
}

// Initialize the plugin
new BrandReputationMonitor();