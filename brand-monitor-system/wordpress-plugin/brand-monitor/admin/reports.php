<?php
add_action('admin_menu', 'brand_monitor_add_reports_submenu');

function brand_monitor_add_reports_submenu() {
    add_submenu_page(
        'brand-monitor',
        __('Brand Monitor Reports', 'brand-monitor'),
        __('Reports', 'brand-monitor'),
        'manage_options',
        'brand-monitor-reports',
        'brand_monitor_reports_page'
    );
}

function brand_monitor_reports_page() {
    $api_client = new Brand_Monitor_API_Client();
    $sentiment_data = $api_client->get_analytics('sentiment', array('days' => 30));
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Reports', 'brand-monitor'); ?></h1>
        <p><?php esc_html_e('Export aggregated analytics for stakeholders.', 'brand-monitor'); ?></p>

        <h2><?php esc_html_e('Sentiment Distribution (30 days)', 'brand-monitor'); ?></h2>
        <pre><?php echo esc_html(print_r($sentiment_data, true)); ?></pre>
    </div>
    <?php
}
