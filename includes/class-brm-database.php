<?php
/**
 * Database management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRM_Database {
    
    public function __construct() {
        // Constructor intentionally left empty.
        // Monitoring is scheduled and handled by BRM_Monitor.
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Clients table
        $clients_table = $wpdb->prefix . 'brm_clients';
        $clients_sql = "CREATE TABLE $clients_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text,
            website varchar(255),
            phone varchar(50),
            email varchar(255),
            keywords text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Monitoring results table
        $results_table = $wpdb->prefix . 'brm_monitoring_results';
        $results_sql = "CREATE TABLE $results_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            title varchar(500) NOT NULL,
            url varchar(1000) NOT NULL,
            canonical_url varchar(1000),
            content text,
            source varchar(255),
            type varchar(50) NOT NULL,
            sentiment varchar(20),
            relevance_score decimal(3,2),
            found_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime,
            status varchar(20) DEFAULT 'new',
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY type (type),
            KEY found_at (found_at),
            KEY canonical_url (canonical_url)
        ) $charset_collate;";
        
        // Monitoring logs table
        $logs_table = $wpdb->prefix . 'brm_monitoring_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9),
            action varchar(100) NOT NULL,
            details text,
            status varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($clients_sql);
        dbDelta($results_sql);
        dbDelta($logs_sql);

        // Backfill canonical_url for existing records
        self::backfill_canonical_urls();
    }
    
    public static function get_client($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_clients';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function get_all_clients() {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_clients';
        return $wpdb->get_results("SELECT * FROM $table WHERE status = 'active' ORDER BY created_at DESC");
    }
    
    public static function save_client($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_clients';
        
        $result = $wpdb->insert(
            $table,
            array(
                'name' => sanitize_text_field($data['name']),
                'address' => sanitize_textarea_field($data['address']),
                'website' => esc_url_raw($data['website']),
                'phone' => sanitize_text_field($data['phone']),
                'email' => sanitize_email($data['email']),
                'keywords' => sanitize_text_field($data['keywords'])
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public static function update_client($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_clients';
        
        return $wpdb->update(
            $table,
            array(
                'name' => sanitize_text_field($data['name']),
                'address' => sanitize_textarea_field($data['address']),
                'website' => esc_url_raw($data['website']),
                'phone' => sanitize_text_field($data['phone']),
                'email' => sanitize_email($data['email']),
                'keywords' => sanitize_text_field($data['keywords'])
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    public static function delete_client($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_clients';
        
        return $wpdb->update(
            $table,
            array('status' => 'deleted'),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    public static function delete_monitoring_result($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_monitoring_results';

        return $wpdb->update(
            $table,
            array('status' => 'deleted'),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    public static function save_monitoring_result($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_monitoring_results';
        
        return $wpdb->insert(
            $table,
            array(
                'client_id' => intval($data['client_id']),
                'title' => sanitize_text_field($data['title']),
                'url' => esc_url_raw($data['url']),
                'canonical_url' => isset($data['canonical_url']) ? sanitize_text_field($data['canonical_url']) : null,
                'content' => sanitize_textarea_field($data['content']),
                'source' => sanitize_text_field($data['source']),
                'type' => sanitize_text_field($data['type']),
                'sentiment' => sanitize_text_field($data['sentiment']),
                'relevance_score' => floatval($data['relevance_score'])
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f')
        );
    }
    
    public static function get_monitoring_results($client_id = null, $type = null, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_monitoring_results';
        
        $where = array();
        $where_values = array();
        
        // Exclude deleted results by default
        $where[] = "status != 'deleted'";
        
        if ($client_id) {
            $where[] = "client_id = %d";
            $where_values[] = $client_id;
        }
        
        if ($type) {
            $where[] = "type = %s";
            $where_values[] = $type;
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT * FROM $table $where_clause ORDER BY found_at DESC LIMIT %d";
        $where_values[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare($sql, $where_values));
    }
    
    public static function log_monitoring_action($client_id, $action, $details, $status = 'success') {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_monitoring_logs';
        
        return $wpdb->insert(
            $table,
            array(
                'client_id' => $client_id,
                'action' => sanitize_text_field($action),
                'details' => sanitize_textarea_field($details),
                'status' => sanitize_text_field($status)
            ),
            array('%d', '%s', '%s', '%s')
        );
    }

    private static function canonicalize_url($url) {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return '';
        }
        $host = strtolower($parts['host']);
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        if ($path === '') {
            $path = '/';
        }
        return $host . $path;
    }

    public static function backfill_canonical_urls() {
        global $wpdb;
        $table = $wpdb->prefix . 'brm_monitoring_results';
        // Ensure column exists
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'canonical_url'");
        if (empty($columns)) {
            return;
        }
        // Backfill a reasonable batch size
        $rows = $wpdb->get_results("SELECT id, url FROM $table WHERE canonical_url IS NULL OR canonical_url = '' LIMIT 10000");
        if (empty($rows)) {
            return;
        }
        foreach ($rows as $row) {
            $canonical = self::canonicalize_url($row->url);
            if (!empty($canonical)) {
                $wpdb->update(
                    $table,
                    array('canonical_url' => $canonical),
                    array('id' => $row->id),
                    array('%s'),
                    array('%d')
                );
            }
        }
    }
}