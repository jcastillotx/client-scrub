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

        // Load Font Awesome 6 for modern flat icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            '6.5.1'
        );

        wp_enqueue_script('brm-admin', BRM_PLUGIN_URL . 'assets/admin.js', array('jquery'), BRM_VERSION, true);
        wp_enqueue_style('brm-admin', BRM_PLUGIN_URL . 'assets/admin.css', array('font-awesome'), BRM_VERSION);

        wp_localize_script('brm-admin', 'brm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('brm_nonce')
        ));
    }
    
    public function admin_page() {
        ?>
        <div class="wrap brm-wrap">
            <div class="brm-header">
                <h1><i class="fa-solid fa-shield-halved"></i> Brand Reputation Monitor</h1>
                <p class="brm-subtitle">Monitor and analyze your clients' online presence</p>
            </div>

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
                        <div class="step-icon"><i class="fa-solid fa-user-plus"></i></div>
                        <div class="step-content">
                            <h3>Add Clients</h3>
                            <p>Add your clients with their monitoring keywords</p>
                            <button class="button button-primary brm-btn" onclick="brmShowAddClientForm()">
                                <i class="fa-solid fa-plus"></i> Add New Client
                            </button>
                        </div>
                    </div>

                    <div class="brm-workflow-step" id="step-2">
                        <div class="step-icon"><i class="fa-solid fa-list-check"></i></div>
                        <div class="step-content">
                            <h3>Review Clients</h3>
                            <p>Review your client list and prepare for monitoring</p>
                        </div>
                    </div>

                    <div class="brm-workflow-step" id="step-3">
                        <div class="step-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
                        <div class="step-content">
                            <h3>Start Web Scraping</h3>
                            <p>Begin monitoring all clients for brand mentions</p>
                            <button class="button button-primary brm-btn brm-start-scraping" onclick="brmStartWebScraping()" id="start-scraping-btn">
                                <span class="btn-text"><i class="fa-solid fa-play"></i> Start Web Scraping</span>
                                <span class="btn-loading" style="display: none;"><i class="fa-solid fa-spinner fa-spin"></i> Scraping...</span>
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
        <div class="wrap brm-wrap">
            <div class="brm-header">
                <h1><i class="fa-solid fa-chart-line"></i> Monitoring Results</h1>
            </div>

            <?php if ($client_id): ?>
                <?php $client = BRM_Database::get_client($client_id); ?>
                <h2><i class="fa-solid fa-user"></i> Results for: <?php echo esc_html($client->name); ?></h2>
                <button class="button button-primary brm-btn" id="brm-manual-scan-btn" onclick="brmManualScan(<?php echo $client_id; ?>)">
                    <i class="fa-solid fa-magnifying-glass"></i> Run Manual Scan
                </button>
            <?php endif; ?>

            <div class="brm-filters">
                <form method="get">
                    <input type="hidden" name="page" value="brm-results">

                    <div class="brm-filter-group">
                        <label><i class="fa-solid fa-filter"></i> Filters</label>
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

                        <button type="submit" class="button brm-btn-secondary">
                            <i class="fa-solid fa-magnifying-glass"></i> Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="brm-validation-actions">
                <button class="button button-secondary brm-btn-secondary" onclick="brmValidateResults(<?php echo $client_id ? $client_id : 0; ?>)">
                    <i class="fa-solid fa-check-double"></i> Validate Results<?php echo $client_id ? ' (This Client)' : ''; ?>
                </button>
                <small class="description">Checks saved URLs and deletes invalid or fake ones.</small>
                <button class="button brm-btn-secondary" style="margin-left:10px" onclick="brmPurgeDeleted(<?php echo $client_id ? $client_id : 0; ?>)">
                    <i class="fa-solid fa-trash-can"></i> Purge Deleted<?php echo $client_id ? ' (This Client)' : ' (All Clients)'; ?>
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
        if (isset($_POST['submit']) && isset($_POST['brm_settings_nonce'])) {
            // Verify nonce for CSRF protection
            if (!wp_verify_nonce($_POST['brm_settings_nonce'], 'brm_save_settings')) {
                wp_die('Security check failed. Please try again.');
            }

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized access');
            }

            $settings = array(
                'ai_provider' => sanitize_text_field($_POST['ai_provider']),
                'api_key' => sanitize_text_field($_POST['api_key']),
                'google_api_key' => sanitize_text_field($_POST['google_api_key'] ?? ''),
                'google_search_engine_id' => sanitize_text_field($_POST['google_search_engine_id'] ?? ''),
                'newsapi_key' => sanitize_text_field($_POST['newsapi_key'] ?? ''),
                'monitoring_frequency' => sanitize_text_field($_POST['monitoring_frequency']),
                'max_results_per_client' => intval($_POST['max_results_per_client']),
                'notification_email' => sanitize_email($_POST['notification_email'])
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
        <div class="wrap brm-wrap">
            <div class="brm-header">
                <h1><i class="fa-solid fa-gear"></i> Brand Reputation Monitor Settings</h1>
            </div>

            <form method="post">
                <?php wp_nonce_field('brm_save_settings', 'brm_settings_nonce'); ?>

                <h2><i class="fa-solid fa-magnifying-glass"></i> Primary Search APIs (Recommended)</h2>
                <p class="description">Configure real web search APIs for accurate brand monitoring. At least one is strongly recommended.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><i class="fa-brands fa-google"></i> Google Custom Search API Key</th>
                        <td>
                            <input type="password" name="google_api_key" value="<?php echo esc_attr($settings['google_api_key'] ?? ''); ?>" class="regular-text" autocomplete="new-password">
                            <p class="description">Get your API key from <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a> (100 free queries/day)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><i class="fa-solid fa-fingerprint"></i> Google Search Engine ID (CX)</th>
                        <td>
                            <input type="text" name="google_search_engine_id" value="<?php echo esc_attr($settings['google_search_engine_id'] ?? ''); ?>" class="regular-text">
                            <p class="description">Create a custom search engine at <a href="https://programmablesearchengine.google.com/" target="_blank">Programmable Search Engine</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><i class="fa-solid fa-newspaper"></i> NewsAPI Key</th>
                        <td>
                            <input type="password" name="newsapi_key" value="<?php echo esc_attr($settings['newsapi_key'] ?? ''); ?>" class="regular-text" autocomplete="new-password">
                            <p class="description">Get your free API key from <a href="https://newsapi.org/register" target="_blank">NewsAPI.org</a> (500 requests/day free)</p>
                        </td>
                    </tr>
                </table>

                <h2><i class="fa-solid fa-robot"></i> AI Provider (Fallback & Sentiment Analysis)</h2>
                <p class="description">AI provider for additional search results and sentiment analysis.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row">AI Provider</th>
                        <td>
                            <select name="ai_provider">
                                <option value="perplexity" <?php selected($settings['ai_provider'] ?? 'perplexity', 'perplexity'); ?>>Perplexity AI (Recommended - has web search)</option>
                                <option value="openrouter" <?php selected($settings['ai_provider'] ?? '', 'openrouter'); ?>>OpenRouter (No web search - analysis only)</option>
                            </select>
                            <p class="description"><strong>Note:</strong> Only Perplexity has real-time web search. OpenRouter/GPT models cannot search the web.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">AI Provider API Key</th>
                        <td>
                            <input type="password" name="api_key" value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" class="regular-text" autocomplete="new-password">
                            <p class="description">
                                Get your API key from
                                <a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity AI</a> or
                                <a href="https://openrouter.ai/keys" target="_blank">OpenRouter</a>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><i class="fa-solid fa-sliders"></i> Monitoring Settings</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><i class="fa-solid fa-clock"></i> Monitoring Frequency</th>
                        <td>
                            <select name="monitoring_frequency">
                                <option value="daily" <?php selected($settings['monitoring_frequency'] ?? 'daily', 'daily'); ?>>Daily (Recommended)</option>
                                <option value="twicedaily" <?php selected($settings['monitoring_frequency'] ?? '', 'twicedaily'); ?>>Twice Daily</option>
                                <option value="hourly" <?php selected($settings['monitoring_frequency'] ?? '', 'hourly'); ?>>Hourly (High API usage)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><i class="fa-solid fa-list-ol"></i> Max Results Per Client</th>
                        <td>
                            <input type="number" name="max_results_per_client" value="<?php echo esc_attr($settings['max_results_per_client'] ?? 20); ?>" min="5" max="100">
                            <p class="description">Number of results to fetch per scan (5-100)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><i class="fa-solid fa-envelope"></i> Notification Email</th>
                        <td>
                            <input type="email" name="notification_email" value="<?php echo esc_attr($settings['notification_email'] ?? get_option('admin_email')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>

            <div class="brm-cost-estimate">
                <h3><i class="fa-solid fa-calculator"></i> API Configuration & Cost Estimation</h3>
                <?php
                $ai_service = new BRM_AI_Service();
                $clients = BRM_Database::get_all_clients();
                $num_clients = count($clients);
                $daily_requests = $num_clients;
                $monthly_requests = $daily_requests * 30;

                $cost_estimate = $ai_service->get_cost_estimate($monthly_requests);

                // Check which APIs are configured
                $google_configured = !empty($settings['google_api_key']) && !empty($settings['google_search_engine_id']);
                $newsapi_configured = !empty($settings['newsapi_key']);
                $ai_configured = !empty($settings['api_key']);
                ?>

                <h4><i class="fa-solid fa-plug"></i> API Configuration Status</h4>
                <ul style="list-style: none; padding-left: 0;">
                    <li>
                        <?php if ($google_configured): ?>
                            <span class="brm-status-badge status-active"><i class="fa-solid fa-circle-check"></i> Google Custom Search</span>
                            <small>100 free queries/day, then ~$5/1000 queries</small>
                        <?php else: ?>
                            <span class="brm-status-badge status-pending"><i class="fa-solid fa-circle-xmark"></i> Google Custom Search</span>
                            <small>Not configured - <strong>Recommended for accurate results</strong></small>
                        <?php endif; ?>
                    </li>
                    <li style="margin-top: 8px;">
                        <?php if ($newsapi_configured): ?>
                            <span class="brm-status-badge status-active"><i class="fa-solid fa-circle-check"></i> NewsAPI</span>
                            <small>Free tier: 500 req/day</small>
                        <?php else: ?>
                            <span class="brm-status-badge status-pending"><i class="fa-solid fa-circle-xmark"></i> NewsAPI</span>
                            <small>Not configured - <strong>Great for news monitoring</strong></small>
                        <?php endif; ?>
                    </li>
                    <li style="margin-top: 8px;">
                        <?php if ($ai_configured): ?>
                            <span class="brm-status-badge status-active"><i class="fa-solid fa-circle-check"></i> AI Provider (<?php echo esc_html(ucfirst($settings['ai_provider'] ?? 'Not Set')); ?>)</span>
                        <?php else: ?>
                            <span class="brm-status-badge status-pending"><i class="fa-solid fa-circle-xmark"></i> AI Provider</span>
                            <small>Not configured</small>
                        <?php endif; ?>
                    </li>
                </ul>

                <?php if (!$google_configured && !$newsapi_configured): ?>
                    <div class="notice notice-warning inline" style="margin: 15px 0;">
                        <p><strong><i class="fa-solid fa-triangle-exclamation"></i> Warning:</strong> No real search APIs configured. Results will rely on AI-only search which may not return current web results. <strong>Configure Google Custom Search or NewsAPI for accurate brand monitoring.</strong></p>
                    </div>
                <?php endif; ?>

                <h4><i class="fa-solid fa-dollar-sign"></i> AI Provider Cost Estimate</h4>
                <p><strong>Estimated Monthly Cost:</strong> $<?php echo number_format($cost_estimate['total_estimated_cost'], 4); ?> USD</p>
                <p><strong>Provider:</strong> <?php echo esc_html($cost_estimate['provider']); ?></p>
                <p><strong>Model:</strong> <?php echo esc_html($cost_estimate['model']); ?></p>
                <p><strong>Cost per request:</strong> $<?php echo number_format($cost_estimate['cost_per_request'], 6); ?> USD</p>
                <?php if (!empty($cost_estimate['note'])): ?>
                    <p><strong>Note:</strong> <?php echo esc_html($cost_estimate['note']); ?></p>
                <?php endif; ?>
                <p class="description" style="margin-top: 12px;">
                    <i class="fa-solid fa-info-circle"></i>
                    Based on <?php echo $num_clients; ?> client(s), <?php echo $daily_requests; ?> daily request(s), and <?php echo $monthly_requests; ?> monthly request(s).
                </p>
            </div>
        </div>
        <?php
    }
    
    private function display_stats() {
        $stats = BRM_Monitor::get_monitoring_stats();
        ?>
        <div class="brm-stat-card">
            <div class="brm-stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="brm-stat-content">
                <h3>Total Clients</h3>
                <div class="brm-stat-number" data-key="total_clients"><?php echo $stats['total_clients']; ?></div>
            </div>
        </div>

        <div class="brm-stat-card">
            <div class="brm-stat-icon"><i class="fa-solid fa-chart-bar"></i></div>
            <div class="brm-stat-content">
                <h3>Total Results</h3>
                <div class="brm-stat-number" data-key="total_results"><?php echo $stats['total_results']; ?></div>
            </div>
        </div>

        <div class="brm-stat-card">
            <div class="brm-stat-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="brm-stat-content">
                <h3>Recent Results (7 days)</h3>
                <div class="brm-stat-number" data-key="recent_results"><?php echo $stats['recent_results']; ?></div>
            </div>
        </div>

        <div class="brm-stat-card brm-sentiment-card">
            <div class="brm-stat-icon"><i class="fa-solid fa-face-smile"></i></div>
            <div class="brm-stat-content">
                <h3>Sentiment Breakdown</h3>
                <div class="brm-sentiment-stats">
                    <?php foreach ($stats['by_sentiment'] as $sentiment => $count): ?>
                        <div class="brm-sentiment-item">
                            <span class="sentiment-<?php echo $sentiment; ?>">
                                <?php if ($sentiment === 'positive'): ?>
                                    <i class="fa-solid fa-circle-check"></i>
                                <?php elseif ($sentiment === 'negative'): ?>
                                    <i class="fa-solid fa-circle-xmark"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-minus"></i>
                                <?php endif; ?>
                                <?php echo ucfirst($sentiment); ?>
                            </span>
                            <span class="count"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
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
                        <td><i class="fa-solid fa-globe"></i> <?php echo esc_html($result->source); ?></td>
                        <td>
                            <span class="brm-type-badge type-<?php echo esc_attr($result->type); ?>">
                                <?php
                                $type_icon = 'fa-file-lines';
                                switch ($result->type) {
                                    case 'news': $type_icon = 'fa-newspaper'; break;
                                    case 'social': $type_icon = 'fa-share-nodes'; break;
                                    case 'blog': $type_icon = 'fa-blog'; break;
                                    case 'forum': $type_icon = 'fa-comments'; break;
                                }
                                ?>
                                <i class="fa-solid <?php echo $type_icon; ?>"></i> <?php echo esc_html(ucfirst($result->type)); ?>
                            </span>
                        </td>
                        <td>
                            <span class="brm-sentiment-badge sentiment-<?php echo esc_attr($result->sentiment); ?>">
                                <?php if ($result->sentiment === 'positive'): ?>
                                    <i class="fa-solid fa-circle-check"></i>
                                <?php elseif ($result->sentiment === 'negative'): ?>
                                    <i class="fa-solid fa-circle-xmark"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-minus"></i>
                                <?php endif; ?>
                                <?php echo esc_html(ucfirst($result->sentiment)); ?>
                            </span>
                        </td>
                        <td><i class="fa-solid fa-gauge"></i> <?php echo number_format($result->relevance_score * 100, 1); ?>%</td>
                        <td><i class="fa-regular fa-calendar"></i> <?php echo date('M j, Y', strtotime($result->found_at)); ?></td>
                        <td>
                            <?php if (($result->status ?? 'active') === 'deleted'): ?>
                                <span class="brm-status-badge status-deleted"><i class="fa-solid fa-trash"></i> Deleted</span>
                            <?php else: ?>
                                <span class="brm-status-badge status-active"><i class="fa-solid fa-circle-check"></i> Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($result->url); ?>" target="_blank" rel="noopener" class="button button-small brm-btn-small">
                                <i class="fa-solid fa-external-link"></i> View
                            </a>
                            <?php if (($result->status ?? 'active') !== 'deleted'): ?>
                                <button class="button button-small button-link-delete brm-btn-delete" onclick="brmDeleteResult(<?php echo intval($result->id); ?>)">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
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
            <h3><i class="fa-solid fa-heart-pulse"></i> System Status</h3>
            <div class="brm-health-indicators">
                <div class="brm-health-item">
                    <span class="brm-health-label"><i class="fa-solid fa-key"></i> API Configuration:</span>
                    <span class="brm-health-status status-<?php echo $api_configured ? 'good' : 'warning'; ?>">
                        <?php if ($api_configured): ?>
                            <i class="fa-solid fa-circle-check"></i> Configured
                        <?php else: ?>
                            <i class="fa-solid fa-triangle-exclamation"></i> Not Configured
                        <?php endif; ?>
                    </span>
                </div>

                <div class="brm-health-item">
                    <span class="brm-health-label"><i class="fa-solid fa-wifi"></i> API Connectivity:</span>
                    <span class="brm-health-status status-<?php echo $api_test['success'] ? 'good' : 'error'; ?>">
                        <?php if ($api_test['success']): ?>
                            <i class="fa-solid fa-circle-check"></i> Connected
                        <?php else: ?>
                            <i class="fa-solid fa-circle-xmark"></i> Failed
                        <?php endif; ?>
                    </span>
                    <?php if (!$api_test['success']): ?>
                        <small class="brm-error-message"><?php echo esc_html($api_test['message']); ?></small>
                    <?php endif; ?>
                </div>

                <div class="brm-health-item">
                    <span class="brm-health-label"><i class="fa-solid fa-robot"></i> AI Provider:</span>
                    <span class="brm-health-status status-info">
                        <i class="fa-solid fa-microchip"></i> <?php echo esc_html(ucfirst($settings['ai_provider'] ?? 'Not Set')); ?>
                    </span>
                </div>

                <div class="brm-health-item">
                    <span class="brm-health-label"><i class="fa-solid fa-calendar-check"></i> Last Scan:</span>
                    <span class="brm-health-status status-info">
                        <i class="fa-regular fa-clock"></i> <?php echo $last_scan === 'never' ? 'Never' : date('M j, Y H:i', strtotime($last_scan)); ?>
                    </span>
                </div>

                <div class="brm-health-actions">
                    <button type="button" class="button button-secondary brm-btn-secondary" onclick="brmTestAPI()">
                        <i class="fa-solid fa-plug-circle-check"></i> Test API Connection
                    </button>
                    <button type="button" class="button button-secondary brm-btn-secondary" onclick="brmRefreshStatus()">
                        <i class="fa-solid fa-rotate"></i> Refresh Status
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