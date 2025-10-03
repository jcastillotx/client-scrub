<?php
/**
 * Client management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRM_Client_Manager {
    
    public function __construct() {
        add_action('wp_ajax_brm_save_client', array($this, 'ajax_save_client'));
        add_action('wp_ajax_brm_update_client', array($this, 'ajax_update_client'));
        add_action('wp_ajax_brm_delete_client', array($this, 'ajax_delete_client'));
        add_action('wp_ajax_brm_get_client', array($this, 'ajax_get_client'));
    }
    
    public function ajax_save_client() {
        check_ajax_referer('brm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'address' => sanitize_textarea_field($_POST['address']),
            'website' => esc_url_raw($_POST['website']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'keywords' => sanitize_text_field($_POST['keywords'])
        );
        
        $client_id = BRM_Database::save_client($data);
        
        if ($client_id) {
            wp_send_json_success(array(
                'message' => 'Client saved successfully',
                'client_id' => $client_id
            ));
        } else {
            wp_send_json_error('Failed to save client');
        }
    }
    
    public function ajax_update_client() {
        check_ajax_referer('brm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $client_id = intval($_POST['client_id']);
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'address' => sanitize_textarea_field($_POST['address']),
            'website' => esc_url_raw($_POST['website']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'keywords' => sanitize_text_field($_POST['keywords'])
        );
        
        $result = BRM_Database::update_client($client_id, $data);
        
        if ($result !== false) {
            wp_send_json_success('Client updated successfully');
        } else {
            wp_send_json_error('Failed to update client');
        }
    }
    
    public function ajax_delete_client() {
        check_ajax_referer('brm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $client_id = intval($_POST['client_id']);
        $result = BRM_Database::delete_client($client_id);
        
        if ($result !== false) {
            wp_send_json_success('Client deleted successfully');
        } else {
            wp_send_json_error('Failed to delete client');
        }
    }
    
    public function ajax_get_client() {
        check_ajax_referer('brm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $client_id = intval($_POST['client_id']);
        $client = BRM_Database::get_client($client_id);
        
        if ($client) {
            wp_send_json_success($client);
        } else {
            wp_send_json_error('Client not found');
        }
    }
    
    public static function get_client_form_html($client = null) {
        $is_edit = !is_null($client);
        $client_id = $is_edit ? $client->id : '';
        $name = $is_edit ? $client->name : '';
        $address = $is_edit ? $client->address : '';
        $website = $is_edit ? $client->website : '';
        $phone = $is_edit ? $client->phone : '';
        $email = $is_edit ? $client->email : '';
        $keywords = $is_edit ? $client->keywords : '';
        
        ob_start();
        ?>
        <form id="brm-client-form" class="brm-form">
            <?php if ($is_edit): ?>
                <input type="hidden" name="client_id" value="<?php echo esc_attr($client_id); ?>">
            <?php endif; ?>
            
            <div class="brm-form-group">
                <label for="client_name">Client Name *</label>
                <input type="text" id="client_name" name="name" value="<?php echo esc_attr($name); ?>" required>
            </div>
            
            <div class="brm-form-group">
                <label for="client_address">Address</label>
                <textarea id="client_address" name="address" rows="3"><?php echo esc_textarea($address); ?></textarea>
            </div>
            
            <div class="brm-form-group">
                <label for="client_website">Website</label>
                <input type="url" id="client_website" name="website" value="<?php echo esc_attr($website); ?>" placeholder="https://example.com">
            </div>
            
            <div class="brm-form-group">
                <label for="client_phone">Phone Number</label>
                <input type="tel" id="client_phone" name="phone" value="<?php echo esc_attr($phone); ?>">
            </div>
            
            <div class="brm-form-group">
                <label for="client_email">Email</label>
                <input type="email" id="client_email" name="email" value="<?php echo esc_attr($email); ?>">
            </div>
            
            <div class="brm-form-group">
                <label for="client_keywords">Keywords for Monitoring *</label>
                <textarea id="client_keywords" name="keywords" rows="3" required placeholder="Enter keywords separated by commas (e.g., company name, brand, products)"><?php echo esc_textarea($keywords); ?></textarea>
                <small>Enter keywords that should be monitored for mentions, separated by commas.</small>
            </div>
            
            <div class="brm-form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo $is_edit ? 'Update Client' : 'Add Client'; ?>
                </button>
                <?php if ($is_edit): ?>
                    <button type="button" class="button" onclick="brmCancelEdit()">Cancel</button>
                <?php endif; ?>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
    
    public static function get_clients_list_html() {
        $clients = BRM_Database::get_all_clients();
        
        ob_start();
        ?>
        <div class="brm-clients-list">
            <div class="brm-list-header">
                <h3>Clients</h3>
                <button class="button button-primary" onclick="brmShowAddClientForm()">Add New Client</button>
            </div>
            
            <?php if (empty($clients)): ?>
                <p>No clients found. <a href="#" onclick="brmShowAddClientForm()">Add your first client</a>.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Website</th>
                            <th>Phone</th>
                            <th>Keywords</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><strong><?php echo esc_html($client->name); ?></strong></td>
                                <td>
                                    <?php if ($client->website): ?>
                                        <a href="<?php echo esc_url($client->website); ?>" target="_blank"><?php echo esc_html($client->website); ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($client->phone ?: '-'); ?></td>
                                <td><?php echo esc_html($client->keywords); ?></td>
                                <td><?php echo date('M j, Y', strtotime($client->created_at)); ?></td>
                                <td>
                                    <button class="button button-small" onclick="brmEditClient(<?php echo $client->id; ?>)">Edit</button>
                                    <button class="button button-small" onclick="brmViewResults(<?php echo $client->id; ?>)">View Results</button>
                                    <button class="button button-small button-link-delete" onclick="brmDeleteClient(<?php echo $client->id; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}