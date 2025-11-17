<?php
/**
 * Monitoring system class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRM_Monitor {
    
    private $ai_service;
    
    public function __construct() {
        $this->ai_service = new BRM_AI_Service();
        add_action('brm_daily_monitoring', array($this, 'run_daily_scan'));
        add_action('wp_ajax_brm_manual_scan', array($this, 'ajax_manual_scan'));
    }
    
    /**
     * Run daily monitoring scan for all active clients
     */
    private function canonicalize_url($url) {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return '';
        }
        $host = strtolower($parts['host']);
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        if ($path === '') {
            $path = '/';
        }
        return $host . $path;
    }

    public function run_daily_scan() {
        $clients = BRM_Database::get_all_clients();
        $total_results = 0;
        $errors = array();
        
        BRM_Database::log_monitoring_action(null, 'daily_scan_started', 'Starting daily monitoring scan for ' . count($clients) . ' clients');
        
        foreach ($clients as $client) {
            try {
                $results = $this->scan_client($client);
                $total_results += count($results);
                
                BRM_Database::log_monitoring_action(
                    $client->id, 
                    'client_scan_completed', 
                    'Found ' . count($results) . ' new mentions',
                    'success'
                );
                
            } catch (Exception $e) {
                $errors[] = "Client {$client->name}: " . $e->getMessage();
                
                BRM_Database::log_monitoring_action(
                    $client->id, 
                    'client_scan_failed', 
                    $e->getMessage(),
                    'error'
                );
            }
        }
        
        $log_message = "Daily scan completed. Found {$total_results} total results.";
        if (!empty($errors)) {
            $log_message .= " Errors: " . implode('; ', $errors);
        }
        
        BRM_Database::log_monitoring_action(null, 'daily_scan_completed', $log_message);
        
        // Update last scan time
        update_option('brm_last_scan_time', current_time('mysql'));
        
        // Send notification email if configured
        $this->send_notification_email($total_results, $errors);
    }
    
    /**
     * Scan a specific client for mentions
     */
    public function scan_client($client) {
        if (empty($client->keywords)) {
            throw new Exception('No keywords configured for client');
        }
        
        // Get AI search results
        $settings = get_option('brm_settings', array());
        $max_results = isset($settings['max_results_per_client']) ? (int) $settings['max_results_per_client'] : 20;
        if ($max_results < 1) {
            $max_results = 20; // sane minimum default
        } elseif ($max_results > 100) {
            $max_results = 100; // cap to prevent excessive API usage
        }
        $search_results = $this->ai_service->search_mentions(
            $client->keywords,
            $client->name,
            $max_results
        );
        
        if (isset($search_results['error'])) {
            throw new Exception($search_results['error']);
        }
        
        $new_results = array();
        
        if (isset($search_results['results']) && is_array($search_results['results'])) {
            foreach ($search_results['results'] as $result) {
                $raw_url = $result['url'] ?? '';
                $url = $this->ai_service->normalize_url($raw_url);
                if (empty($url) || !$this->ai_service->validate_url($url)) {
                    continue; // skip invalid or non-resolving URLs
                }

                $canonical = $this->canonicalize_url($url);
                // Check if this result already exists (by canonical_url or exact url)
                if (!$this->result_exists($client->id, $url)) {
                    // Save the result
                    $saved = BRM_Database::save_monitoring_result(array(
                        'client_id' => $client->id,
                        'title' => $result['title'] ?? 'Untitled',
                        'url' => $url,
                        'canonical_url' => $canonical,
                        'content' => $result['content'] ?? '',
                        'source' => parse_url($url, PHP_URL_HOST) ?: ($result['source'] ?? ''),
                        'type' => $result['type'] ?? 'article',
                        'sentiment' => $result['sentiment'] ?? 'neutral',
                        'relevance_score' => $result['relevance_score'] ?? 0.5
                    ));
                    
                    if ($saved) {
                        // Return normalized result structure
                        $new_results[] = array(
                            'title' => $result['title'] ?? 'Untitled',
                            'url' => $url,
                            'content' => $result['content'] ?? '',
                            'source' => parse_url($url, PHP_URL_HOST) ?: ($result['source'] ?? ''),
                            'type' => $result['type'] ?? 'article',
                            'sentiment' => $result['sentiment'] ?? 'neutral',
                            'relevance_score' => $result['relevance_score'] ?? 0.5
                        );
                    }
                }
            }
        }
        
        return $new_results;
    }
    
    /**
     * Check if a result already exists for a client
     */
    private function result_exists($client_id, $url) {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_monitoring_results';

        $canonical = $this->canonicalize_url($url);
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE client_id = %d AND (canonical_url = %s OR url = %s)",
            $client_id,
            $canonical,
            $url
        ));
        
        return intval($exists) > 0;
    }
    
    /**
     * Manual scan for a specific client (AJAX)
     */
    public function ajax_manual_scan() {
        check_ajax_referer('brm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $client_id = intval($_POST['client_id']);
        $client = BRM_Database::get_client($client_id);
        
        if (!$client) {
            wp_send_json_error('Client not found');
        }
        
        // Check if API is configured
        $settings = get_option('brm_settings', array());
        if (empty($settings['api_key'])) {
            wp_send_json_error('AI API key not configured. Please configure your AI provider settings first.');
        }
        
        try {
            BRM_Database::log_monitoring_action($client_id, 'manual_scan_started', "Manual scan initiated for {$client->name}", 'info');
            
            $results = $this->scan_client($client);
            
            BRM_Database::log_monitoring_action(
                $client_id, 
                'manual_scan_completed', 
                "Manual scan completed. Found " . count($results) . " new results",
                'success'
            );
            
            wp_send_json_success(array(
                'message' => 'Scan completed successfully',
                'new_results' => count($results),
                'results' => $results
            ));
            
        } catch (Exception $e) {
            BRM_Database::log_monitoring_action(
                $client_id, 
                'manual_scan_failed', 
                "Manual scan failed: " . $e->getMessage(),
                'error'
            );
            
            wp_send_json_error('Scan failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Send notification email about monitoring results
     */
    private function send_notification_email($total_results, $errors) {
        $settings = get_option('brm_settings', array());
        $notification_email = $settings['notification_email'] ?? get_option('admin_email');
        
        if (empty($notification_email)) {
            return;
        }
        
        $subject = 'Brand Reputation Monitor - Daily Report';
        $message = "Daily monitoring scan completed.\n\n";
        $message .= "Total new mentions found: {$total_results}\n";
        
        if (!empty($errors)) {
            $message .= "\nErrors encountered:\n";
            foreach ($errors as $error) {
                $message .= "- {$error}\n";
            }
        }
        
        $message .= "\nView detailed results in your WordPress admin panel.";
        
        wp_mail($notification_email, $subject, $message);
    }
    
    /**
     * Get monitoring statistics
     */
    public static function get_monitoring_stats() {
        global $wpdb;
        $results_table = $wpdb->prefix . 'brm_monitoring_results';
        $clients_table = $wpdb->prefix . 'brm_clients';
        
        $stats = array();
        
        // Total clients
        $stats['total_clients'] = $wpdb->get_var("SELECT COUNT(*) FROM $clients_table WHERE status = 'active'");
        
        // Total results (exclude deleted)
        $stats['total_results'] = $wpdb->get_var("SELECT COUNT(*) FROM $results_table WHERE status != 'deleted'");
        
        // Results by type (exclude deleted)
        $type_stats = $wpdb->get_results("
            SELECT type, COUNT(*) as count 
            FROM $results_table 
            WHERE status != 'deleted'
            GROUP BY type
        ");
        $stats['by_type'] = array();
        foreach ($type_stats as $type_stat) {
            $stats['by_type'][$type_stat->type] = $type_stat->count;
        }
        
        // Results by sentiment (exclude deleted)
        $sentiment_stats = $wpdb->get_results("
            SELECT sentiment, COUNT(*) as count 
            FROM $results_table 
            WHERE sentiment IS NOT NULL 
              AND status != 'deleted'
            GROUP BY sentiment
        ");
        $stats['by_sentiment'] = array();
        foreach ($sentiment_stats as $sentiment_stat) {
            $stats['by_sentiment'][$sentiment_stat->sentiment] = $sentiment_stat->count;
        }
        
        // Recent results (last 7 days, exclude deleted)
        $stats['recent_results'] = $wpdb->get_var("
            SELECT COUNT(*) FROM $results_table 
            WHERE found_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND status != 'deleted'
        ");
        
        return $stats;
    }
    
    /**
     * Get results for a specific client
     */
    public static function get_client_results($client_id, $type = null, $limit = 50, $status = null) {
        if ($status === 'deleted') {
            return BRM_Database::get_monitoring_results_deleted($client_id, $type, $limit);
        }
        // Default/active results exclude deleted
        return BRM_Database::get_monitoring_results($client_id, $type, $limit);
    }
    
    /**
     * Get recent monitoring logs
     */
    public static function get_recent_logs($limit = 20) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'brm_monitoring_logs';
        $clients_table = $wpdb->prefix . 'brm_clients';

        // Sanitize limit to prevent SQL injection
        $limit = absint($limit);
        if ($limit < 1) {
            $limit = 20;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT l.*, c.name as client_name
            FROM $logs_table l
            LEFT JOIN $clients_table c ON l.client_id = c.id
            ORDER BY l.created_at DESC
            LIMIT %d
        ", $limit));
    }
}