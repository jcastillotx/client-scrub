<?php
add_action('admin_menu', 'brand_monitor_add_admin_menu');
add_action('admin_enqueue_scripts', 'brand_monitor_admin_assets');
add_action('wp_ajax_brand_monitor_get_mentions', 'brand_monitor_get_mentions_ajax');

function brand_monitor_add_admin_menu() {
    add_menu_page(
        'Brand Monitor',
        'Brand Monitor',
        'manage_options',
        'brand-monitor',
        'brand_monitor_dashboard_page',
        'dashicons-visibility',
        30
    );
}

function brand_monitor_admin_assets($hook) {
    if ($hook !== 'toplevel_page_brand-monitor') {
        return;
    }

    wp_enqueue_style(
        'brand-monitor-admin',
        BRAND_MONITOR_PLUGIN_URL . 'assets/css/admin-dashboard.css',
        array(),
        BRAND_MONITOR_VERSION
    );

    wp_enqueue_script(
        'brand-monitor-dashboard',
        BRAND_MONITOR_PLUGIN_URL . 'assets/js/dashboard.js',
        array('jquery'),
        BRAND_MONITOR_VERSION,
        true
    );

    wp_localize_script('brand-monitor-dashboard', 'brandMonitorAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('brand_monitor_nonce'),
    ));
}

function brand_monitor_get_mentions_ajax() {
    check_ajax_referer('brand_monitor_nonce', 'nonce');

    $api_client = new Brand_Monitor_API_Client();
    $mentions = $api_client->get_mentions(array(
        'limit' => 10,
        'sort' => 'discovered_at_desc'
    ));

    wp_send_json_success($mentions['data'] ?? array());
}

function brand_monitor_dashboard_page() {
    $api_client = new Brand_Monitor_API_Client();

    $mentions = $api_client->get_mentions(array(
        'limit' => 10,
        'sort' => 'discovered_at_desc'
    ));

    $sentiment_data = $api_client->get_analytics('sentiment', array(
        'days' => 7
    ));

    ?>
    <div class="wrap brand-monitor-dashboard">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="brand-monitor-stats">
            <div class="stat-box">
                <h3><?php esc_html_e("Today's Mentions", 'brand-monitor'); ?></h3>
                <p class="stat-number"><?php echo isset($mentions['total']) ? esc_html($mentions['total']) : '0'; ?></p>
            </div>

            <div class="stat-box">
                <h3><?php esc_html_e('Sentiment Score', 'brand-monitor'); ?></h3>
                <p class="stat-number sentiment-positive">
                    <?php echo isset($sentiment_data['average_score']) ? esc_html(number_format((float) $sentiment_data['average_score'], 2)) : 'N/A'; ?>
                </p>
            </div>

            <div class="stat-box">
                <h3><?php esc_html_e('Active Alerts', 'brand-monitor'); ?></h3>
                <p class="stat-number alert-count">0</p>
            </div>
        </div>

        <div class="brand-monitor-mentions">
            <h2><?php esc_html_e('Recent Mentions', 'brand-monitor'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Source', 'brand-monitor'); ?></th>
                        <th><?php esc_html_e('Title', 'brand-monitor'); ?></th>
                        <th><?php esc_html_e('Sentiment', 'brand-monitor'); ?></th>
                        <th><?php esc_html_e('Date', 'brand-monitor'); ?></th>
                        <th><?php esc_html_e('Actions', 'brand-monitor'); ?></th>
                    </tr>
                </thead>
                <tbody id="mentions-list">
                    <?php if (isset($mentions['data']) && !empty($mentions['data'])): ?>
                        <?php foreach ($mentions['data'] as $mention): ?>
                            <tr>
                                <td><?php echo esc_html($mention['source_type']); ?></td>
                                <td><?php echo esc_html($mention['title']); ?></td>
                                <td>
                                    <span class="sentiment-badge sentiment-<?php echo esc_attr($mention['sentiment']); ?>">
                                        <?php echo esc_html(ucfirst($mention['sentiment'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($mention['discovered_at']))); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($mention['source_url']); ?>" target="_blank"><?php esc_html_e('View', 'brand-monitor'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No mentions found', 'brand-monitor'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
