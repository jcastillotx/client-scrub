<?php
/**
 * Plugin Name: Brand Reputation Monitor
 * Plugin URI: https://www.kre8ivtech.com
 * Description: Monitor client brand reputation by tracking mentions, articles, news, and backlinks across the web using AI-powered analysis.
 * Version: 1.1.0
 * Author: Kre8ivTech
 * Author URI: https://www.kre8ivtech.com
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
define('BRM_VERSION', '1.1.0');

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
        
        // One-time schema migration/backfill for canonical_url and deduplication
        if (!get_option('brm_canonical_migrated')) {
            BRM_Database::create_tables();
            BRM_Database::backfill_canonical_urls();
            update_option('brm_canonical_migrated', 1);
        }

        // Add REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Load text domain for translations
        load_plugin_textdomain('brand-reputation-monitor', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('brm/v1', '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'health_check'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        register_rest_route('brm/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_status'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }
    
    /**
     * Health check endpoint
     */
    public function health_check($request) {
        $ai_service = new BRM_AI_Service();
        $settings = get_option('brm_settings', array());
        
        $health_data = array(
            'status' => 'healthy',
            'timestamp' => current_time('mysql'),
            'plugin_version' => BRM_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'api_configured' => !empty($settings['api_key']),
            'ai_provider' => $settings['ai_provider'] ?? 'not_configured',
            'clients_count' => count(BRM_Database::get_all_clients()),
            'last_scan' => get_option('brm_last_scan_time', 'never')
        );
        
        // Test API connectivity if configured
        if (!empty($settings['api_key'])) {
            $test_result = $ai_service->test_api_connectivity();
            $health_data['api_test'] = $test_result;
            
            if (!$test_result['success']) {
                $health_data['status'] = 'degraded';
            }
        } else {
            $health_data['api_test'] = array(
                'success' => false,
                'message' => 'API key not configured'
            );
            $health_data['status'] = 'not_configured';
        }
        
        return new WP_REST_Response($health_data, 200);
    }
    
    /**
     * API status endpoint for admin
     */
    public function api_status($request) {
        $ai_service = new BRM_AI_Service();
        $settings = get_option('brm_settings', array());
        
        $status_data = array(
            'api_configured' => !empty($settings['api_key']),
            'ai_provider' => $settings['ai_provider'] ?? 'not_configured',
            'connectivity_test' => $ai_service->test_api_connectivity(),
            'monitoring_stats' => BRM_Monitor::get_monitoring_stats(),
            'recent_logs' => BRM_Monitor::get_recent_logs(10)
        );
        
        return new WP_REST_Response($status_data, 200);
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