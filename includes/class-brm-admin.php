<?php
/**
 * Admin interface class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_brm_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_brm_test_api', array($this, 'ajax_test_api'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Brand Reputation Monitor',
            'Brand Monitor',
            'manage_options',
            'brand-reputation-monitor',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'brand-reputation-monitor',
            'Clients',
            'Clients',
            'manage_options',
            'brand-reputation-monitor',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'brand-reputation-monitor',
            'Monitoring Results',
            'Results',
            'manage_options',
            'brm-results',
            array($this, 'results_page')
        );
        
        add_submenu_page(
            'brand-reputation-monitor',
            'Settings',
            'Settings',
            'manage_options',
            'brm-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'brand-reputation-monitor') === false && strpos($hook, 'brm-') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('brm-admin', BRM_PLUGIN_URL . 'assets/admin.js', array('jquery'), BRM_VERSION, true);
        wp_enqueue_style('brm-admin', BRM_PLUGIN_URL . 'assets/admin.css', array(), BRM_VERSION);
        
        wp_localize_script('brm-admin', 'brm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('brm_nonce')
        ));
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Brand Reputation Monitor</h1>
            
            <div class="brm-dashboard">
                <div class="brm-health-status">
                    <?php $this->display_health_status(); ?>
                </div>
                
                <div class="brm-stats-grid">
                    <?php $this->display_stats(); ?>
                </div>
                
                <div class="brm-main-content">
                    <div class="brm-clients-section">
                        <?php echo BRM_Client_Manager::get_clients_list_html(); ?>
                    </div>
                    
                    <div class="brm-form-section" id="brm-form-section" style="display: none;">
                        <h3>Add/Edit Client</h3>
                        <div id="brm-client-form-container"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function brmShowAddClientForm() {
            document.getElementById('brm-form-section').style.display = 'block';
            document.getElementById('brm-client-form-container').innerHTML = '<?php echo addslashes(BRM_Client_Manager::get_client_form_html()); ?>';
        }
        
        function brmEditClient(clientId) {
            // AJAX call to get client data and show form
            jQuery.post(brm_ajax.ajax_url, {
                action: 'brm_get_client',
                client_id: clientId,
                nonce: brm_ajax.nonce
            }, function(response) {
                if (response.success) {
                    document.getElementById('brm-form-section').style.display = 'block';
                    document.getElementById('brm-client-form-container').innerHTML = '<?php echo addslashes(BRM_Client_Manager::get_client_form_html()); ?>';
                    // Populate form with client data
                    jQuery('#client_name').val(response.data.name);
                    jQuery('#client_address').val(response.data.address);
                    jQuery('#client_website').val(response.data.website);
                    jQuery('#client_phone').val(response.data.phone);
                    jQuery('#client_email').val(response.data.email);
                    jQuery('#client_keywords').val(response.data.keywords);
                }
            });
        }
        
        function brmCancelEdit() {
            document.getElementById('brm-form-section').style.display = 'none';
        }
        
        function brmDeleteClient(clientId) {
            if (confirm('Are you sure you want to delete this client?')) {
                jQuery.post(brm_ajax.ajax_url, {
                    action: 'brm_delete_client',
                    client_id: clientId,
                    nonce: brm_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
        }
        
        function brmViewResults(clientId) {
            window.location.href = '<?php echo admin_url('admin.php?page=brm-results'); ?>&client_id=' + clientId;
        }
        </script>
        <?php
    }
    
    public function results_page() {
        $client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : null;
        
        ?>
        <div class="wrap">
            <h1>Monitoring Results</h1>
            
            <?php if ($client_id): ?>
                <?php $client = BRM_Database::get_client($client_id); ?>
                <h2>Results for: <?php echo esc_html($client->name); ?></h2>
                <button class="button button-primary" onclick="brmManualScan(<?php echo $client_id; ?>)">Run Manual Scan</button>
            <?php endif; ?>
            
            <div class="brm-filters">
                <form method="get">
                    <input type="hidden" name="page" value="brm-results">
                    <?php if ($client_id): ?>
                        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <?php endif; ?>
                    
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="article" <?php selected($type_filter, 'article'); ?>>Articles</option>
                        <option value="news" <?php selected($type_filter, 'news'); ?>>News</option>
                        <option value="social" <?php selected($type_filter, 'social'); ?>>Social Media</option>
                        <option value="blog" <?php selected($type_filter, 'blog'); ?>>Blog Posts</option>
                        <option value="forum" <?php selected($type_filter, 'forum'); ?>>Forum Posts</option>
                    </select>
                    
                    <input type="submit" class="button" value="Filter">
                </form>
            </div>
            
            <div class="brm-results-list">
                <?php $this->display_results($client_id, $type_filter); ?>
            </div>
        </div>
        
        <script>
        function brmManualScan(clientId) {
            if (confirm('Run manual scan for this client?')) {
                jQuery.post(brm_ajax.ajax_url, {
                    action: 'brm_manual_scan',
                    client_id: clientId,
                    nonce: brm_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert('Scan completed! Found ' + response.data.new_results + ' new results.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $settings = array(
                'ai_provider' => sanitize_text_field($_POST['ai_provider']),
                'api_key' => sanitize_text_field($_POST['api_key']),
                'monitoring_frequency' => sanitize_text_field($_POST['monitoring_frequency']),
                'max_results_per_client' => intval($_POST['max_results_per_client']),
                'notification_email' => sanitize_email($_POST['notification_email'])
            );
            
            update_option('brm_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        $settings = get_option('brm_settings', array());
        ?>
        <div class="wrap">
            <h1>Brand Reputation Monitor Settings</h1>
            
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">AI Provider</th>
                        <td>
                            <select name="ai_provider">
                                <option value="openrouter" <?php selected($settings['ai_provider'] ?? 'openrouter', 'openrouter'); ?>>OpenRouter (Recommended)</option>
                                <option value="perplexity" <?php selected($settings['ai_provider'] ?? '', 'perplexity'); ?>>Perplexity AI</option>
                            </select>
                            <p class="description">OpenRouter is generally more cost-effective for this use case.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="api_key" value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" class="regular-text" required>
                            <p class="description">
                                Get your API key from 
                                <a href="https://openrouter.ai/" target="_blank">OpenRouter</a> or 
                                <a href="https://www.perplexity.ai/" target="_blank">Perplexity AI</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Monitoring Frequency</th>
                        <td>
                            <select name="monitoring_frequency">
                                <option value="daily" <?php selected($settings['monitoring_frequency'] ?? 'daily', 'daily'); ?>>Daily</option>
                                <option value="twice_daily" <?php selected($settings['monitoring_frequency'] ?? '', 'twice_daily'); ?>>Twice Daily</option>
                                <option value="hourly" <?php selected($settings['monitoring_frequency'] ?? '', 'hourly'); ?>>Hourly</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Max Results Per Client</th>
                        <td>
                            <input type="number" name="max_results_per_client" value="<?php echo esc_attr($settings['max_results_per_client'] ?? 20); ?>" min="5" max="100">
                            <p class="description">Maximum number of results to fetch per client per scan.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Notification Email</th>
                        <td>
                            <input type="email" name="notification_email" value="<?php echo esc_attr($settings['notification_email'] ?? get_option('admin_email')); ?>" class="regular-text">
                            <p class="description">Email address to receive daily monitoring reports.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="brm-cost-estimate">
                <h3>Cost Estimation</h3>
                <?php
                $ai_service = new BRM_AI_Service();
                $clients = BRM_Database::get_all_clients();
                $num_clients = count($clients);
                $daily_requests = $num_clients;
                $monthly_requests = $daily_requests * 30;
                
                $cost_estimate = $ai_service->get_cost_estimate($monthly_requests);
                ?>
                <p><strong>Estimated Monthly Cost:</strong> $<?php echo number_format($cost_estimate['total_estimated_cost'], 4); ?> USD</p>
                <p><strong>Provider:</strong> <?php echo ucfirst($cost_estimate['provider']); ?></p>
                <p><strong>Model:</strong> <?php echo $cost_estimate['model']; ?></p>
                <p><strong>Cost per request:</strong> $<?php echo number_format($cost_estimate['cost_per_request'], 4); ?> USD</p>
            </div>
        </div>
        <?php
    }
    
    private function display_stats() {
        $stats = BRM_Monitor::get_monitoring_stats();
        ?>
        <div class="brm-stat-card">
            <h3>Total Clients</h3>
            <div class="brm-stat-number"><?php echo $stats['total_clients']; ?></div>
        </div>
        
        <div class="brm-stat-card">
            <h3>Total Results</h3>
            <div class="brm-stat-number"><?php echo $stats['total_results']; ?></div>
        </div>
        
        <div class="brm-stat-card">
            <h3>Recent Results (7 days)</h3>
            <div class="brm-stat-number"><?php echo $stats['recent_results']; ?></div>
        </div>
        
        <div class="brm-stat-card">
            <h3>Sentiment Breakdown</h3>
            <div class="brm-sentiment-stats">
                <?php foreach ($stats['by_sentiment'] as $sentiment => $count): ?>
                    <div class="brm-sentiment-item">
                        <span class="sentiment-<?php echo $sentiment; ?>"><?php echo ucfirst($sentiment); ?></span>
                        <span class="count"><?php echo $count; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    private function display_results($client_id = null, $type_filter = null) {
        $results = BRM_Monitor::get_client_results($client_id, $type_filter, 100);
        
        if (empty($results)) {
            echo '<p>No results found.</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Source</th>
                    <th>Type</th>
                    <th>Sentiment</th>
                    <th>Relevance</th>
                    <th>Found</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($result->title); ?></strong>
                            <?php if ($result->content): ?>
                                <br><small><?php echo esc_html(wp_trim_words($result->content, 20)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($result->source); ?></td>
                        <td>
                            <span class="brm-type-badge type-<?php echo esc_attr($result->type); ?>">
                                <?php echo esc_html(ucfirst($result->type)); ?>
                            </span>
                        </td>
                        <td>
                            <span class="brm-sentiment-badge sentiment-<?php echo esc_attr($result->sentiment); ?>">
                                <?php echo esc_html(ucfirst($result->sentiment)); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($result->relevance_score * 100, 1); ?>%</td>
                        <td><?php echo date('M j, Y', strtotime($result->found_at)); ?></td>
                        <td>
                            <a href="<?php echo esc_url($result->url); ?>" target="_blank" class="button button-small">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    public function ajax_get_stats() {
        check_ajax_referer('brm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $stats = BRM_Monitor::get_monitoring_stats();
        wp_send_json_success($stats);
    }
    
    public function ajax_test_api() {
        check_ajax_referer('brm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $ai_service = new BRM_AI_Service();
        $test_result = $ai_service->test_api_connectivity();
        
        wp_send_json_success($test_result);
    }
    
    private function display_health_status() {
        $settings = get_option('brm_settings', array());
        $ai_service = new BRM_AI_Service();
        $api_configured = !empty($settings['api_key']);
        $last_scan = get_option('brm_last_scan_time', 'never');
        
        // Get API test result
        $api_test = $ai_service->test_api_connectivity();
        
        ?>
        <div class="brm-health-card">
            <h3>System Status</h3>
            <div class="brm-health-indicators">
                <div class="brm-health-item">
                    <span class="brm-health-label">API Configuration:</span>
                    <span class="brm-health-status status-<?php echo $api_configured ? 'good' : 'warning'; ?>">
                        <?php echo $api_configured ? 'Configured' : 'Not Configured'; ?>
                    </span>
                </div>
                
                <div class="brm-health-item">
                    <span class="brm-health-label">API Connectivity:</span>
                    <span class="brm-health-status status-<?php echo $api_test['success'] ? 'good' : 'error'; ?>">
                        <?php echo $api_test['success'] ? 'Connected' : 'Failed'; ?>
                    </span>
                    <?php if (!$api_test['success']): ?>
                        <small class="brm-error-message"><?php echo esc_html($api_test['message']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="brm-health-item">
                    <span class="brm-health-label">AI Provider:</span>
                    <span class="brm-health-status status-info">
                        <?php echo esc_html(ucfirst($settings['ai_provider'] ?? 'Not Set')); ?>
                    </span>
                </div>
                
                <div class="brm-health-item">
                    <span class="brm-health-label">Last Scan:</span>
                    <span class="brm-health-status status-info">
                        <?php echo $last_scan === 'never' ? 'Never' : date('M j, Y H:i', strtotime($last_scan)); ?>
                    </span>
                </div>
                
                <div class="brm-health-actions">
                    <button type="button" class="button button-secondary" onclick="brmTestAPI()">
                        Test API Connection
                    </button>
                    <button type="button" class="button button-secondary" onclick="brmRefreshStatus()">
                        Refresh Status
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        function brmTestAPI() {
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Testing...';
            button.disabled = true;
            
            jQuery.post(brm_ajax.ajax_url, {
                action: 'brm_test_api',
                nonce: brm_ajax.nonce
            }, function(response) {
                button.textContent = originalText;
                button.disabled = false;
                
                if (response.success) {
                    if (response.data.success) {
                        alert('API test successful! ' + response.data.message);
                    } else {
                        alert('API test failed: ' + response.data.message);
                    }
                } else {
                    alert('Error testing API: ' + response.data);
                }
                
                // Refresh the page to update status
                location.reload();
            });
        }
        
        function brmRefreshStatus() {
            location.reload();
        }
        </script>
        <?php
    }
}