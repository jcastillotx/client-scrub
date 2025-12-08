<?php
add_action('admin_menu', 'brand_monitor_add_sources_submenu');

function brand_monitor_add_sources_submenu() {
    add_submenu_page(
        'brand-monitor',
        __('Monitoring Sources', 'brand-monitor'),
        __('Sources', 'brand-monitor'),
        'manage_options',
        'brand-monitor-sources',
        'brand_monitor_sources_page'
    );
}

function brand_monitor_sources_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Monitoring Sources', 'brand-monitor'); ?></h1>
        <p><?php esc_html_e('Configure Apify actors and schedules per source.', 'brand-monitor'); ?></p>

        <form method="post">
            <?php wp_nonce_field('brand_monitor_sources'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Source Type', 'brand-monitor'); ?></th>
                    <td>
                        <select name="source_type">
                            <option value="google_search"><?php esc_html_e('Google Search', 'brand-monitor'); ?></option>
                            <option value="twitter"><?php esc_html_e('Twitter', 'brand-monitor'); ?></option>
                            <option value="reddit"><?php esc_html_e('Reddit', 'brand-monitor'); ?></option>
                            <option value="news"><?php esc_html_e('News', 'brand-monitor'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Keywords', 'brand-monitor'); ?></th>
                    <td>
                        <textarea name="keywords" rows="4" class="large-text" placeholder="brand, brand reviews"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Schedule (cron)', 'brand-monitor'); ?></th>
                    <td>
                        <input type="text" name="schedule_cron" class="regular-text" placeholder="0 * * * *" />
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Source', 'brand-monitor')); ?>
        </form>
    </div>
    <?php
}
