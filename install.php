<?php
/**
 * Brand Reputation Monitor Installation Script
 * 
 * This script helps with the initial setup and testing of the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress, try to load it
    $wp_load_paths = array(
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    );
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress not found. Please run this script from within a WordPress installation.');
    }
}

// Check if user has permission
if (!current_user_can('manage_options')) {
    die('You do not have permission to run this script.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Brand Reputation Monitor - Installation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: #00a32a; }
        .error { color: #d63638; }
        .warning { color: #dba617; }
        .info { color: #2271b1; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px; }
        .step h3 { margin-top: 0; }
        .code { background: #f0f0f1; padding: 10px; border-radius: 4px; font-family: monospace; }
        .button { background: #2271b1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .button:hover { background: #135e96; }
    </style>
</head>
<body>
    <h1>Brand Reputation Monitor - Installation & Setup</h1>
    
    <?php
    $errors = array();
    $warnings = array();
    $success = array();
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '5.0', '<')) {
        $errors[] = "WordPress version {$wp_version} is too old. Please upgrade to WordPress 5.0 or higher.";
    } else {
        $success[] = "WordPress version {$wp_version} is compatible.";
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = "PHP version " . PHP_VERSION . " is too old. Please upgrade to PHP 7.4 or higher.";
    } else {
        $success[] = "PHP version " . PHP_VERSION . " is compatible.";
    }
    
    // Check required functions
    $required_functions = array('curl_init', 'json_encode', 'wp_remote_get', 'wp_remote_post');
    foreach ($required_functions as $func) {
        if (!function_exists($func)) {
            $errors[] = "Required function {$func} is not available.";
        }
    }
    
    if (empty($errors)) {
        $success[] = "All required functions are available.";
    }
    
    // Check database permissions
    global $wpdb;
    $test_table = $wpdb->prefix . 'brm_test';
    $create_result = $wpdb->query("CREATE TABLE IF NOT EXISTS {$test_table} (id INT PRIMARY KEY)");
    if ($create_result === false) {
        $errors[] = "Cannot create database tables. Please check database permissions.";
    } else {
        $wpdb->query("DROP TABLE {$test_table}");
        $success[] = "Database permissions are sufficient.";
    }
    
    // Check if plugin is active
    if (!class_exists('BrandReputationMonitor')) {
        $warnings[] = "Plugin is not active. Please activate the Brand Reputation Monitor plugin.";
    } else {
        $success[] = "Plugin is active and loaded.";
    }
    
    // Check if tables exist
    if (class_exists('BRM_Database')) {
        $clients_table = $wpdb->prefix . 'brm_clients';
        $results_table = $wpdb->prefix . 'brm_monitoring_results';
        $logs_table = $wpdb->prefix . 'brm_monitoring_logs';
        
        $tables_exist = true;
        foreach (array($clients_table, $results_table, $logs_table) as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
                $tables_exist = false;
                break;
            }
        }
        
        if ($tables_exist) {
            $success[] = "Database tables are created.";
        } else {
            $warnings[] = "Database tables are not created. Please activate the plugin to create them.";
        }
    }
    
    // Check cron functionality
    if (wp_next_scheduled('brm_daily_monitoring')) {
        $success[] = "Daily monitoring cron job is scheduled.";
    } else {
        $warnings[] = "Daily monitoring cron job is not scheduled. This will be set up when you activate the plugin.";
    }
    
    // Display results
    if (!empty($success)) {
        echo '<div class="step">';
        echo '<h3 class="success">✓ System Checks Passed</h3>';
        echo '<ul>';
        foreach ($success as $msg) {
            echo '<li class="success">' . esc_html($msg) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    if (!empty($warnings)) {
        echo '<div class="step">';
        echo '<h3 class="warning">⚠ Warnings</h3>';
        echo '<ul>';
        foreach ($warnings as $msg) {
            echo '<li class="warning">' . esc_html($msg) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    if (!empty($errors)) {
        echo '<div class="step">';
        echo '<h3 class="error">✗ Errors Found</h3>';
        echo '<ul>';
        foreach ($errors as $msg) {
            echo '<li class="error">' . esc_html($msg) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    if (empty($errors)) {
        echo '<div class="step">';
        echo '<h3 class="success">🎉 Installation Complete!</h3>';
        echo '<p>Your Brand Reputation Monitor plugin is ready to use.</p>';
        echo '<p><a href="' . admin_url('admin.php?page=brand-reputation-monitor') . '" class="button">Go to Plugin Dashboard</a></p>';
        echo '</div>';
    }
    ?>
    
    <div class="step">
        <h3>Next Steps</h3>
        <ol>
            <li><strong>Configure API Settings</strong>: Go to <a href="<?php echo admin_url('admin.php?page=brm-settings'); ?>">Brand Monitor > Settings</a> and enter your AI provider API key.</li>
            <li><strong>Add Your First Client</strong>: Go to <a href="<?php echo admin_url('admin.php?page=brand-reputation-monitor'); ?>">Brand Monitor > Clients</a> and add a client profile.</li>
            <li><strong>Test Manual Scan</strong>: Use the "Run Manual Scan" button to test the monitoring functionality.</li>
            <li><strong>Review Results</strong>: Check <a href="<?php echo admin_url('admin.php?page=brm-results'); ?>">Brand Monitor > Results</a> to see found mentions.</li>
        </ol>
    </div>
    
    <div class="step">
        <h3>API Provider Setup</h3>
        <h4>OpenRouter (Recommended)</h4>
        <ol>
            <li>Visit <a href="https://openrouter.ai/" target="_blank">OpenRouter.ai</a></li>
            <li>Create an account and get your API key</li>
            <li>Add funds to your account (minimum $5 recommended)</li>
            <li>Enter the API key in plugin settings</li>
        </ol>
        
        <h4>Perplexity AI (Alternative)</h4>
        <ol>
            <li>Visit <a href="https://www.perplexity.ai/" target="_blank">Perplexity.ai</a></li>
            <li>Sign up for API access</li>
            <li>Get your API key from the dashboard</li>
            <li>Enter the API key in plugin settings</li>
        </ol>
    </div>
    
    <div class="step">
        <h3>Cost Estimation</h3>
        <p>Based on your current setup, here are estimated monthly costs:</p>
        <div class="code">
            <strong>OpenRouter (GPT-4o-mini):</strong><br>
            • 10 clients: ~$0.90/month<br>
            • 25 clients: ~$2.25/month<br>
            • 50 clients: ~$4.50/month<br><br>
            
            <strong>Perplexity AI (Llama-3.1-sonar-small):</strong><br>
            • 10 clients: ~$1.20/month<br>
            • 25 clients: ~$3.00/month<br>
            • 50 clients: ~$6.00/month
        </div>
        <p><em>Costs may vary based on actual usage and API pricing changes.</em></p>
    </div>
    
    <div class="step">
        <h3>Support & Documentation</h3>
        <p>For detailed documentation and support:</p>
        <ul>
            <li>Read the <code>README.md</code> file for complete documentation</li>
            <li>Check the plugin settings for configuration options</li>
            <li>Review monitoring logs for troubleshooting</li>
            <li>Test with a small number of clients first</li>
        </ul>
    </div>
    
    <p><a href="<?php echo admin_url(); ?>" class="button">Return to WordPress Admin</a></p>
</body>
</html>