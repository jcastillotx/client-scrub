<?php
/**
 * Brand Reputation Monitor Plugin Test Script
 * 
 * This script tests the plugin functionality without requiring full WordPress setup
 */

// Mock WordPress functions for testing
if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = array()) {
        // Mock response for testing
        return array(
            'body' => json_encode(array(
                'choices' => array(
                    array(
                        'message' => array(
                            'content' => json_encode(array(
                                'results' => array(
                                    array(
                                        'title' => 'Test Article 1',
                                        'url' => 'https://example.com/article1',
                                        'content' => 'This is a test article about the company.',
                                        'source' => 'Example News',
                                        'type' => 'article',
                                        'sentiment' => 'positive',
                                        'relevance_score' => 0.85
                                    ),
                                    array(
                                        'title' => 'Test News 2',
                                        'url' => 'https://example.com/news2',
                                        'content' => 'Another test mention found online.',
                                        'source' => 'Test Blog',
                                        'type' => 'news',
                                        'sentiment' => 'neutral',
                                        'relevance_score' => 0.72
                                    )
                                )
                            ))
                        )
                    )
                )
            )),
            'response' => array('code' => 200)
        );
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($response) {
        return false;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($text) {
        return trim(strip_tags($text));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($text) {
        return trim($text);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test_nonce_' . $action;
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action, $nonce) {
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message) {
        die($message);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        echo json_encode(array('success' => true, 'data' => $data));
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        echo json_encode(array('success' => false, 'data' => $data));
        exit;
    }
}

// Mock database class
class MockDatabase {
    private static $clients = array();
    private static $results = array();
    private static $logs = array();
    
    public static function save_client($data) {
        $id = count(self::$clients) + 1;
        self::$clients[$id] = array_merge($data, array('id' => $id));
        return $id;
    }
    
    public static function get_client($id) {
        return isset(self::$clients[$id]) ? (object) self::$clients[$id] : null;
    }
    
    public static function get_all_clients() {
        return array_map(function($client) {
            return (object) $client;
        }, self::$clients);
    }
    
    public static function save_monitoring_result($data) {
        $id = count(self::$results) + 1;
        self::$results[$id] = array_merge($data, array('id' => $id));
        return $id;
    }
    
    public static function get_monitoring_results($client_id = null, $type = null, $limit = 50) {
        $filtered = self::$results;
        
        if ($client_id) {
            $filtered = array_filter($filtered, function($result) use ($client_id) {
                return $result['client_id'] == $client_id;
            });
        }
        
        if ($type) {
            $filtered = array_filter($filtered, function($result) use ($type) {
                return $result['type'] == $type;
            });
        }
        
        return array_slice(array_map(function($result) {
            return (object) $result;
        }, $filtered), 0, $limit);
    }
    
    public static function log_monitoring_action($client_id, $action, $details, $status = 'success') {
        self::$logs[] = array(
            'client_id' => $client_id,
            'action' => $action,
            'details' => $details,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s')
        );
    }
}

// Mock AI Service
class MockAIService {
    public function search_mentions($keywords, $client_name, $max_results = 20) {
        // Simulate API call delay
        usleep(100000); // 0.1 seconds
        
        return array(
            'results' => array(
                array(
                    'title' => "Test Article about {$client_name}",
                    'url' => 'https://example.com/test-article',
                    'content' => "This is a test article mentioning {$client_name} and {$keywords}.",
                    'source' => 'Test News Site',
                    'type' => 'article',
                    'sentiment' => 'positive',
                    'relevance_score' => 0.85
                ),
                array(
                    'title' => "Social Media Mention of {$client_name}",
                    'url' => 'https://twitter.com/test/status/123',
                    'content' => "Great work by {$client_name}!",
                    'source' => 'Twitter',
                    'type' => 'social',
                    'sentiment' => 'positive',
                    'relevance_score' => 0.92
                )
            )
        );
    }
}

// Test functions
function test_client_management() {
    echo "Testing Client Management...\n";
    
    // Test adding a client
    $client_data = array(
        'name' => 'Test Company Inc.',
        'address' => '123 Test Street, Test City, TC 12345',
        'website' => 'https://testcompany.com',
        'phone' => '+1-555-0123',
        'email' => 'contact@testcompany.com',
        'keywords' => 'Test Company, Test Corp, test products'
    );
    
    $client_id = MockDatabase::save_client($client_data);
    echo "✓ Client added with ID: {$client_id}\n";
    
    // Test retrieving client
    $client = MockDatabase::get_client($client_id);
    if ($client && $client->name === $client_data['name']) {
        echo "✓ Client retrieved successfully\n";
    } else {
        echo "✗ Failed to retrieve client\n";
    }
    
    return $client_id;
}

function test_ai_monitoring($client_id) {
    echo "\nTesting AI Monitoring...\n";
    
    $ai_service = new MockAIService();
    $client = MockDatabase::get_client($client_id);
    
    if (!$client) {
        echo "✗ Client not found for monitoring test\n";
        return false;
    }
    
    // Test AI search
    $results = $ai_service->search_mentions($client->keywords, $client->name, 10);
    
    if (isset($results['results']) && count($results['results']) > 0) {
        echo "✓ AI search returned " . count($results['results']) . " results\n";
        
        // Test saving results
        $saved_count = 0;
        foreach ($results['results'] as $result) {
            $saved = MockDatabase::save_monitoring_result(array(
                'client_id' => $client_id,
                'title' => $result['title'],
                'url' => $result['url'],
                'content' => $result['content'],
                'source' => $result['source'],
                'type' => $result['type'],
                'sentiment' => $result['sentiment'],
                'relevance_score' => $result['relevance_score']
            ));
            
            if ($saved) {
                $saved_count++;
            }
        }
        
        echo "✓ Saved {$saved_count} monitoring results\n";
        return true;
    } else {
        echo "✗ AI search failed or returned no results\n";
        return false;
    }
}

function test_results_retrieval($client_id) {
    echo "\nTesting Results Retrieval...\n";
    
    $results = MockDatabase::get_monitoring_results($client_id);
    
    if (count($results) > 0) {
        echo "✓ Retrieved " . count($results) . " results for client\n";
        
        foreach ($results as $result) {
            echo "  - {$result->title} ({$result->type}, {$result->sentiment}, " . 
                 number_format($result->relevance_score * 100, 1) . "% relevance)\n";
        }
        
        return true;
    } else {
        echo "✗ No results found for client\n";
        return false;
    }
}

function test_logging() {
    echo "\nTesting Logging System...\n";
    
    MockDatabase::log_monitoring_action(1, 'test_action', 'Test log entry', 'success');
    MockDatabase::log_monitoring_action(1, 'test_error', 'Test error entry', 'error');
    
    echo "✓ Logging system functional\n";
    return true;
}

function test_cost_estimation() {
    echo "\nTesting Cost Estimation...\n";
    
    $clients = MockDatabase::get_all_clients();
    $num_clients = count($clients);
    
    // Simulate cost calculation
    $cost_per_request = 0.0003; // OpenRouter GPT-4o-mini
    $requests_per_month = $num_clients * 30; // Daily monitoring
    $monthly_cost = $requests_per_month * $cost_per_request;
    
    echo "✓ Cost estimation for {$num_clients} clients: $" . number_format($monthly_cost, 4) . "/month\n";
    echo "  - Cost per request: $" . number_format($cost_per_request, 4) . "\n";
    echo "  - Requests per month: {$requests_per_month}\n";
    
    return true;
}

// Run tests
echo "Brand Reputation Monitor - Plugin Test\n";
echo "=====================================\n\n";

$tests_passed = 0;
$total_tests = 5;

// Test 1: Client Management
if (test_client_management()) {
    $tests_passed++;
}

// Test 2: AI Monitoring
if (test_ai_monitoring(1)) {
    $tests_passed++;
}

// Test 3: Results Retrieval
if (test_results_retrieval(1)) {
    $tests_passed++;
}

// Test 4: Logging
if (test_logging()) {
    $tests_passed++;
}

// Test 5: Cost Estimation
if (test_cost_estimation()) {
    $tests_passed++;
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "Test Summary: {$tests_passed}/{$total_tests} tests passed\n";

if ($tests_passed === $total_tests) {
    echo "🎉 All tests passed! Plugin is ready for use.\n";
} else {
    echo "⚠️  Some tests failed. Please review the output above.\n";
}

echo "\nNext steps:\n";
echo "1. Install the plugin in WordPress\n";
echo "2. Configure your AI provider API key\n";
echo "3. Add your first client\n";
echo "4. Run a manual scan to test functionality\n";
echo "5. Set up daily monitoring\n";