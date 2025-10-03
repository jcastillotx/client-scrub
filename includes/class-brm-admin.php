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
        add_action('wp_ajax_brm_start_web_scraping', array($this, 'ajax_start_web_scraping'));
        add_action('wp_ajax_brm_delete_result', array($this, 'ajax_delete_result'));
        add_action('wp_ajax_brm_validate_results', array($this, 'ajax_validate_results'));
        add_action('wp_ajax_brm_purge_deleted_results', array($this, 'ajax_purge_deleted_results'));
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
                
                <!-- Workflow Steps -->
                <div class="brm-workflow">
                    <div class="brm-workflow-step active" id="step-1">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Add Clients</h3>
                            <p>Add your clients with their monitoring keywords</p>
                            <button class="button button-primary" onclick="brmShowAddClientForm()">Add New Client</button>
                        </div>
                    </div>
                    
                    <div class="brm-workflow-step" id="step-2">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Review Clients</h3>
                            <p>Review your client list and prepare for monitoring</p>
                        </div>
                    </div>
                    
                    <div class="brm-workflow-step" id="step-3">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Start Web Scraping</h3>
                            <p>Begin monitoring all clients for brand mentions</p>
                            <button class="button button-primary brm-start-scraping" onclick="brmStartWebScraping()" id="start-scraping-btn">
                                <span class="btn-text">Start Web Scraping</span>
                                <span class="btn-loading" style="display: none;">Scraping...</span>
                            </button>
                        </div>
                    </div>
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
        // Keep only web scraping handler here; other admin functions are provided in assets/admin.js
        function brmStartWebScraping() {
            if (confirm('Start web scraping for all clients? This may take several minutes.')) {
                var $btn = document.getElementById('start-scraping-btn');
                var $btnText = $btn.querySelector('.btn-text');
                var $btnLoading = $btn.querySelector('.btn-loading');
                
                // Show loading state
                $btn.disabled = true;
                $btnText.style.display = 'none';
                $btnLoading.style.display = 'inline';

                // Show persistent progress notice and periodically refresh stats
                var progressNotice = typeof showNoticePersistent === 'function'
                    ? showNoticePersistent('Starting web scraping... Progress will update automatically.', 'info')
                    : null;
                var refreshInterval = setInterval(function() {
                    if (typeof refreshStats === 'function') {
                        refreshStats();
                    }
                }, 5000);
                
                jQuery.post(brm_ajax.ajax_url, {
                    action: 'brm_start_web_scraping',
                    nonce: brm_ajax.nonce
                }, function(response) {
                    // Clear progress UI
                    if (refreshInterval) { clearInterval(refreshInterval); }
                    if (progressNotice && progressNotice.remove) { progressNotice.remove(); }

                    if (response.success) {
                        // Update workflow step
                        document.getElementById('step-2').classList.add('completed');
                        document.getElementById('step-3').classList.add('completed');
                        
                        // Show success toast notice
                        var summary = 'Web scraping completed successfully. Total results: ' +
                                      response.data.total_results + '. Clients scanned: ' +
                                      response.data.clients_count + '.';
                        if (typeof showNotice === 'function') {
                            showNotice(summary, 'success');
                        }

                        // Reload page to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        if (typeof showNotice === 'function') {
                            showNotice('Error: ' + response.data, 'error');
                        }
                    }
                }).fail(function() {
                    if (refreshInterval) { clearInterval(refreshInterval); }
                    if (progressNotice && progressNotice.remove) { progressNotice.remove(); }
                    if (typeof showNotice === 'function') {
                        showNotice('Network error. Please try again.', 'error');
                    }
                }).always(function() {
                    // Reset button state
                    $btn.disabled = false;
                    $btnText.style.display = 'inline';
                    $btnLoading.style.display = 'none';
                });
            }
        }
        </script>
        <?php
    }
    
    public function results_page() {
        $client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : null;
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'active'; // active | deleted
        $clients = BRM_Database::get_all_clients();
        
        ?>
        <div class="wrap">
            <h1>Monitoring Results</h1>
            
            <?php if ($client_id): ?>
                <?php $client = BRM_Database::get_client($client_id); ?>
                <h2>Results for: <?php echo esc_html($client->name); ?></h2>
                <button class="button button-primary" id="brm-manual-scan-btn" onclick="brmManualScan(<?php echo $client_id; ?>)">Run Manual Scan</button>
            <?php endif; ?>
            
            <div class="brm-filters">
                <form method="get">
                    <input type="hidden" name="page" value="brm-results">
                    
                    <select name="client_id">
                        <option value="">All Clients</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client->id; ?>" <?php selected($client_id, $client->id); ?>>
                                <?php echo esc_html($client->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="article" <?php selected($type_filter, 'article'); ?>>Articles</option>
                        <option value="news" <?php selected($type_filter, 'news'); ?>>News</option>
                        <option value="social" <?php selected($type_filter, 'social'); ?>>Social Media</option>
                        <option value="blog" <?php selected($type_filter, 'blog'); ?>>Blog Posts</option>
                        <option value="forum" <?php selected($type_filter, 'forum'); ?>>Forum Posts</option>
                    </select>

                    <select name="status">
                        <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                        <option value="deleted" <?php selected($status_filter, 'deleted'); ?>>Deleted</option>
                    </select>
                    
                    <input type="submit" class="button" value="Filter">
                </form>
            </div>
            
            <div class="brm-validation-actions" style="margin: 10px 0 20px;">
                <button class="button button-secondary" onclick="brmValidateResults(<?php echo $client_id ? $client_id : 0; ?>)">
                    Validate Results<?php echo $client_id ? ' (This Client)' : ''; ?>
                </button>
                <small class="description">Checks saved URLs and deletes invalid or fake ones.</small>
                <button class="button" style="margin-left:10px" onclick="brmPurgeDeleted(<?php echo $client_id ? $client_id : 0; ?>)">
                    Purge Deleted<?php echo $client_id ? ' (This Client)' : ' (All Clients)'; ?>
                </button>
                <small class="description">Permanently removes deleted results from the database.</small>
            </div>

            <div class="brm-results-list">
                <?php $this->display_results($client_id, $type_filter, $status_filter); ?>
            </div>
        </div>
        
        <script>
        function brmPurgeDeleted(clientId) {
            if (!confirm('This will permanently remove deleted results. Continue?')) {
                return;
            }
            var notice = typeof showNoticePersistent === 'function'
                ? showNoticePersistent('Purging deleted results...', 'info')
                : null;

            jQuery.post(brm_ajax.ajax_url, {
                action: 'brm_purge_deleted_results',
                nonce: brm_ajax.nonce,
                client_id: clientId || 0
            }, function(response) {
                if (notice && notice.remove) { notice.remove(); }
                if (response.success) {
                    if (typeof showNotice === 'function') {
                        showNotice('Purged ' + response.data.purged + ' deleted result(s).', 'success');
                    }
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    if (typeof showNotice === 'function') {
                        showNotice('Error: ' + response.data, 'error');
                    }
                }
            }).fail(function() {
                if (notice && notice.remove) { notice.remove(); }
                if (typeof showNotice === 'function') {
                    showNotice('Network error while purging.', 'error');
                }
            });
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
                'notification_email' => sanitize_email($_POST['notification_email']),
                // New: Perplexity model selector
                'perplexity_model' => sanitize_text_field($_POST['perplexity_model'] ?? 'sonar-pro')
            );
            
            update_option('brm_settings', $settings);

            // Reschedule cron based on monitoring frequency
            $schedule = in_array($settings['monitoring_frequency'], array('hourly','twicedaily','daily')) ? $settings['monitoring_frequency'] : 'daily';
            wp_clear_scheduled_hook('brm_daily_monitoring');
            wp_schedule_event(time(), $schedule, 'brm_daily_monitoring');

            echo '<div class="notice notice-success"><p>Settings saved successfully! Monitoring schedule updated.</p></div>';
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
                        <th scope="row">Perplexity Model</th>
                        <td>
                            <select name="perplexity_model">
                                <?php $pm = $settings['perplexity_model'] ?? 'sonar-pro'; ?>
                                <option value="sonar" <?php selected($pm, 'sonar'); ?>>sonar (lightweight search)</option>
                                <option value="sonar-pro" <?php selected($pm, 'sonar-pro'); ?>>sonar-pro (advanced search)</option>
                                <option value="sonar-deep-research" <?php selected($pm, 'sonar-deep-research'); ?>>sonar-deep-research (exhaustive research)</option>
                                <option value="sonar-reasoning" <?php selected($pm, 'sonar-reasoning'); ?>>sonar-reasoning (fast reasoning)</option>
                                <option value="sonar-reasoning-pro" <?php selected($pm, 'sonar-reasoning-pro'); ?>>sonar-reasoning-pro (premier reasoning)</option>
                            </select>
                            <p class="description">Applies when AI Provider is set to Perplexity. See official docs for model details.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Monitoring Frequency</th>
                        <td>
                            <select name="monitoring_frequency">
                                <option value="daily" <?php selected($settings['monitoring_frequency'] ?? 'daily', 'daily'); ?>>Daily</option>
                                <option value="twicedaily" <?php selected($settings['monitoring_frequency'] ?? '', 'twicedaily'); ?>>Twice Daily</option>
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
            
            <div class="brm-cost-estimate" id="brm-cost-estimate" data-num-clients="<?php echo intval($num_clients); ?>">
                <h3>Cost Estimation</h3>
                <?php
                $ai_service = new BRM_AI_Service();
                $clients = BRM_Database::get_all_clients();
                $num_clients = count($clients);
                $daily_requests = $num_clients;
                $monthly_requests = $daily_requests * 30;
                
                $cost_estimate = $ai_service->get_cost_estimate($monthly_requests);
                ?>
                <p><strong>Estimated Monthly Cost:</strong> $<span id="brm-monthly-cost"><?php echo number_format($cost_estimate['total_estimated_cost'], 4); ?></span> USD</p>
                <p><strong>Provider:</strong> <span id="brm-provider"><?php echo ucfirst($cost_estimate['provider']); ?></span></p>
                <p><strong>Model:</strong> <span id="brm-model"><?php echo $cost_estimate['model']; ?></span></p>
                <p><strong>Cost per request:</strong> $<span id="brm-cost-per-request"><?php echo number_format($cost_estimate['cost_per_request'], 4); ?></span> USD</p>
                <p><strong>Assumed tokens per request:</strong> <span id="brm-tokens"><?php echo number_format($cost_estimate['tokens_per_request_assumed']); ?></span></p>
                <p><strong>Cost per 1K tokens:</strong> $<span id="brm-cost-per-1k"><?php echo number_format($cost_estimate['cost_per_1k_tokens'], 5); ?></span> USD</p>
                <p><em>Estimation is approximate.</em></p>
            </div>
            <script>
            (function() {
                var numClients = parseInt(document.getElementById('brm-cost-estimate').getAttribute('data-num-clients'), 10) || 0;
                var providerSelect = document.querySelector('select[name="ai_provider"]');
                var modelSelect = document.querySelector('select[name="perplexity_model"]');
                var monthlySpan = document.getElementById('brm-monthly-cost');
                var providerSpan = document.getElementById('brm-provider');
                var modelSpan = document.getElementById('brm-model');
                var cprSpan = document.getElementById('brm-cost-per-request');
                var tokensSpan = document.getElementById('brm-tokens');
                var cpkSpan = document.getElementById('brm-cost-per-1k');

                function estimate() {
                    var provider = providerSelect ? providerSelect.value : 'openrouter';
                    var model = 'gpt-4o-mini';
                    if (provider === 'perplexity') {
                        model = modelSelect ? modelSelect.value : 'sonar-pro';
                    }

                    var costs = {
                        openrouter: {
                            'gpt-4o-mini': 0.00015,
                            'gpt-3.5-turbo': 0.00050
                        },
                        perplexity: {
                            'sonar': 0.00020,
                            'sonar-pro': 0.00035,
                            'sonar-deep-research': 0.00150,
                            'sonar-reasoning': 0.00030,
                            'sonar-reasoning-pro': 0.00075
                        }
                    };

                    var tokensPerRequest = 2000;
                    if (provider === 'perplexity' && model === 'sonar-deep-research') {
                        tokensPerRequest = 10000;
                    }

                    var costPer1k = (costs[provider] && costs[provider][model]) ? costs[provider][model] : (provider === 'perplexity' ? 0.00035 : 0.00015);
                    var costPerRequest = (tokensPerRequest / 1000.0) * costPer1k;
                    var dailyRequests = numClients;
                    var monthlyRequests = dailyRequests * 30;
                    var monthlyCost = monthlyRequests * costPerRequest;

                    providerSpan.textContent = provider.charAt(0).toUpperCase() + provider.slice(1);
                    modelSpan.textContent = model;
                    cprSpan.textContent = costPerRequest.toFixed(4);
                    tokensSpan.textContent = tokensPerRequest.toLocaleString();
                    cpkSpan.textContent = costPer1k.toFixed(5);
                    monthlySpan.textContent = monthlyCost.toFixed(4);
                }

                if (providerSelect) providerSelect.addEventListener('change', estimate);
                if (modelSelect) modelSelect.addEventListener('change', estimate);
                document.addEventListener('DOMContentLoaded', estimate);
            })();
            </script>
        </div>
        <?php
    }
    
    private function display_stats() {
        $stats = BRM_Monitor::get_monitoring_stats();
        ?>
        <div class="brm-stat-card">
            <h3>Total Clients</h3>
            <div class="brm-stat-number" data-key="total_clients"><?php echo $stats['total_clients']; ?></div>
        </div>
        
        <div class="brm-stat-card">
            <h3>Total Results</h3>
            <div class="brm-stat-number" data-key="total_results"><?php echo $stats['total_results']; ?></div>
        </div>
        
        <div class="brm-stat-card">
            <h3>Recent Results (7 days)</h3>
            <div class="brm-stat-number" data-key="recent_results"><?php echo $stats['recent_results']; ?></div>
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
    
    private function display_results($client_id = null, $type_filter = null, $status_filter = 'active') {
        $results = BRM_Monitor::get_client_results($client_id, $type_filter, 100, $status_filter);
        
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
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                    <tr data-result-id="<?php echo intval($result->id); ?>">
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
                        <td><?php echo isset($result->status) ? esc_html($result->status) : 'active'; ?></td>
                        <td>
                            <a href="<?php echo esc_url($result->url); ?>" target="_blank" rel="noopener" class="button button-small">View</a>
                            <?php if (($result->status ?? 'active') !== 'deleted'): ?>
                                <button class="button button-small button-link-delete" onclick="brmDeleteResult(<?php echo intval($result->id); ?>)">Delete</button>
                            <?php else: ?>
                                <span class="description">Deleted</span>
                            <?php endif; ?>
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
    
    public function ajax_start_web_scraping() {
        check_ajax_referer('brm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Get all active clients
        $clients = BRM_Database::get_all_clients();
        
        if (empty($clients)) {
            wp_send_json_error('No clients found. Please add clients first.');
        }
        
        $monitor = new BRM_Monitor();
        $total_results = 0;
        $errors = array();
        $scanned_clients = array();
        
        // Log the start of bulk scraping
        BRM_Database::log_monitoring_action(null, 'bulk_scraping_started', 'Starting bulk web scraping for ' . count($clients) . ' clients');
        
        foreach ($clients as $client) {
            try {
                $results = $monitor->scan_client($client);
                $total_results += count($results);
                $scanned_clients[] = array(
                    'id' => $client->id,
                    'name' => $client->name,
                    'new_results' => count($results)
                );
                
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
        
        // Update last scan time
        update_option('brm_last_scan_time', current_time('mysql'));
        
        $log_message = "Bulk scraping completed. Found {$total_results} total results.";
        if (!empty($errors)) {
            $log_message .= " Errors: " . implode('; ', $errors);
        }
        
        BRM_Database::log_monitoring_action(null, 'bulk_scraping_completed', $log_message);
        
        wp_send_json_success(array(
            'message' => 'Web scraping completed successfully!',
            'total_results' => $total_results,
            'scanned_clients' => $scanned_clients,
            'errors' => $errors,
            'clients_count' => count($clients)
        ));
    }

    public function ajax_delete_result() {
        check_ajax_referer('brm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $result_id = intval($_POST['result_id'] ?? 0);
        if (!$result_id) {
            wp_send_json_error('Invalid result ID');
        }

        $res = BRM_Database::delete_monitoring_result($result_id);
        if ($res !== false) {
            wp_send_json_success('Result deleted successfully');
        } else {
            wp_send_json_error('Failed to delete result');
        }
    }

    public function ajax_validate_results() {
        check_ajax_referer('brm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        global $wpdb;
        $table = $wpdb->prefix . 'brm_monitoring_results';

        $where = "status != 'deleted'";
        $params = array();
        if ($client_id > 0) {
            $where .= " AND client_id = %d";
            $params[] = $client_id;
        }

        BRM_Database::log_monitoring_action(
            $client_id > 0 ? $client_id : null,
            'validation_started',
            $client_id > 0 ? 'Validation started for client ' . $client_id : 'Validation started for all clients',
            'info'
        );

        $sql = "SELECT id, url FROM $table WHERE $where";
        $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        $ai = new BRM_AI_Service();
        $checked = 0;
        $deleted = 0;
        $valid = 0;

        foreach ($rows as $row) {
            $checked++;
            $url = $ai->normalize_url($row->url);
            if (!empty($url) && $ai->validate_url($url)) {
                $valid++;
                continue;
            }
            $res = BRM_Database::delete_monitoring_result($row->id);
            if ($res !== false) {
                $deleted++;
            }
        }

        BRM_Database::log_monitoring_action(
            $client_id > 0 ? $client_id : null,
            'validation_completed',
            "Validation completed. Checked: $checked, Deleted: $deleted, Valid: $valid",
            'success'
        );

        wp_send_json_success(array(
            'message' => 'Validation completed',
            'checked' => $checked,
            'deleted' => $deleted,
            'valid' => $valid,
            'client_id' => $client_id
        ));
    }

    public function ajax_purge_deleted_results() {
        check_ajax_referer('brm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;

        BRM_Database::log_monitoring_action(
            $client_id > 0 ? $client_id : null,
            'purge_deleted_started',
            $client_id > 0 ? 'Purging deleted results for client ' . $client_id : 'Purging deleted results for all clients',
            'info'
        );

        $purged = BRM_Database::purge_deleted_results($client_id > 0 ? $client_id : null);

        BRM_Database::log_monitoring_action(
            $client_id > 0 ? $client_id : null,
            'purge_deleted_completed',
            "Purged $purged deleted result(s)",
            'success'
        );

        wp_send_json_success(array(
            'message' => 'Purge completed',
            'purged' => intval($purged),
            'client_id' => $client_id
        ));
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

            var progressNotice = typeof showNoticePersistent === 'function'
                ? showNoticePersistent('Testing API connection...', 'info')
                : null;
            
            jQuery.post(brm_ajax.ajax_url, {
                action: 'brm_test_api',
                nonce: brm_ajax.nonce
            }, function(response) {
                if (progressNotice && progressNotice.remove) { progressNotice.remove(); }
                button.textContent = originalText;
                button.disabled = false;
                
                if (response.success) {
                    if (response.data.success) {
                        if (typeof showNotice === 'function') {
                            showNotice('API test successful! ' + response.data.message, 'success');
                        }
                    } else {
                        if (typeof showNotice === 'function') {
                            showNotice('API test failed: ' + response.data.message, 'error');
                        }
                    }
                } else {
                    if (typeof showNotice === 'function') {
                        showNotice('Error testing API: ' + response.data, 'error');
                    }
                }
                
                // Refresh the page to update status
                setTimeout(function() { location.reload(); }, 1200);
            }).fail(function() {
                if (progressNotice && progressNotice.remove) { progressNotice.remove(); }
                button.textContent = originalText;
                button.disabled = false;
                if (typeof showNotice === 'function') {
                    showNotice('Network error while testing API.', 'error');
                }
            });
        }
        
        function brmRefreshStatus() {
            location.reload();
        }
        </script>
        <?php
    }
}