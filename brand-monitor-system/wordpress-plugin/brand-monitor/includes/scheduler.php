<?php
class Brand_Monitor_Scheduler {
    public static function schedule_events() {
        if (!wp_next_scheduled('brand_monitor_cron_fetch_mentions')) {
            wp_schedule_event(time(), 'hourly', 'brand_monitor_cron_fetch_mentions');
        }
    }

    public static function clear_events() {
        $timestamp = wp_next_scheduled('brand_monitor_cron_fetch_mentions');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'brand_monitor_cron_fetch_mentions');
        }
    }
}

add_action('brand_monitor_cron_fetch_mentions', 'brand_monitor_sync_mentions');

function brand_monitor_sync_mentions() {
    $api_client = new Brand_Monitor_API_Client();
    $api_client->get_mentions(array('limit' => 100));
}
