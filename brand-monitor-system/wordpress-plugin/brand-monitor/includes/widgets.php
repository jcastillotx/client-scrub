<?php
add_action('wp_dashboard_setup', 'brand_monitor_register_dashboard_widget');

function brand_monitor_register_dashboard_widget() {
    wp_add_dashboard_widget(
        'brand_monitor_dashboard',
        __('Brand Monitor', 'brand-monitor'),
        'brand_monitor_render_dashboard_widget'
    );
}

function brand_monitor_render_dashboard_widget() {
    $api_client = new Brand_Monitor_API_Client();
    $mentions = $api_client->get_mentions(array('limit' => 5));
    include BRAND_MONITOR_PLUGIN_DIR . 'templates/dashboard-widget.php';
}
