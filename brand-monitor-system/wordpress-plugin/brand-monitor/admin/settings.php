<?php
add_action('admin_init', 'brand_monitor_register_settings');
add_action('admin_menu', 'brand_monitor_add_settings_submenu');

function brand_monitor_register_settings() {
    register_setting('brand_monitor_settings', 'brand_monitor_api_url');
    register_setting('brand_monitor_settings', 'brand_monitor_api_key');
}

function brand_monitor_add_settings_submenu() {
    add_submenu_page(
        'brand-monitor',
        __('Brand Monitor Settings', 'brand-monitor'),
        __('Settings', 'brand-monitor'),
        'manage_options',
        'brand-monitor-settings',
        'brand_monitor_settings_page'
    );
}

function brand_monitor_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Brand Monitor Settings', 'brand-monitor'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('brand_monitor_settings'); ?>
            <?php do_settings_sections('brand_monitor_settings'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="brand_monitor_api_url"><?php esc_html_e('API URL', 'brand-monitor'); ?></label></th>
                    <td>
                        <input type="text" name="brand_monitor_api_url" id="brand_monitor_api_url" value="<?php echo esc_attr(get_option('brand_monitor_api_url', 'https://api.yourdomain.com')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="brand_monitor_api_key"><?php esc_html_e('API Key', 'brand-monitor'); ?></label></th>
                    <td>
                        <input type="text" name="brand_monitor_api_key" id="brand_monitor_api_key" value="<?php echo esc_attr(get_option('brand_monitor_api_key')); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
