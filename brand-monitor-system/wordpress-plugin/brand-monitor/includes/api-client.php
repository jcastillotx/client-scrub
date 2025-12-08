<?php
class Brand_Monitor_API_Client {

    private $api_url;
    private $api_key;

    public function __construct() {
        $this->api_url = rtrim(get_option('brand_monitor_api_url', 'https://api.yourdomain.com'), '/');
        $this->api_key = get_option('brand_monitor_api_key');
    }

    private function make_request($endpoint, $method = 'GET', $data = null) {
        if (empty($this->api_key)) {
            return array('error' => __('Missing API key.', 'brand-monitor'));
        }

        $url = $this->api_url . $endpoint;

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 30,
        );

        if ($data && $method !== 'GET') {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function validate_api_key() {
        return $this->make_request('/api/v1/auth/validate', 'POST');
    }

    public function get_mentions($params = array()) {
        $query_string = http_build_query($params);
        return $this->make_request('/api/v1/mentions?' . $query_string);
    }

    public function get_analytics($type = 'sentiment', $params = array()) {
        $query_string = http_build_query($params);
        return $this->make_request('/api/v1/analytics/' . $type . '?' . $query_string);
    }

    public function trigger_scrape($source_type, $keywords) {
        return $this->make_request('/api/v1/scrape/trigger', 'POST', array(
            'source_type' => $source_type,
            'keywords'    => $keywords,
        ));
    }

    public function get_alerts($params = array()) {
        $query_string = http_build_query($params);
        return $this->make_request('/api/v1/alerts?' . $query_string);
    }
}
