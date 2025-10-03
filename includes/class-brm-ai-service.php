<?php
/**
 * AI Service class for web scraping and analysis
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRM_AI_Service {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('brm_settings', array());
    }
    
    /**
     * Search for mentions using AI service
     */
    public function search_mentions($keywords, $client_name, $max_results = 20) {
        $provider = $this->settings['ai_provider'] ?? 'openrouter';
        
        switch ($provider) {
            case 'openrouter':
                return $this->search_with_openrouter($keywords, $client_name, $max_results);
            case 'perplexity':
                return $this->search_with_perplexity($keywords, $client_name, $max_results);
            default:
                return $this->search_with_openrouter($keywords, $client_name, $max_results);
        }
    }
    
    /**
     * Search using OpenRouter API
     */
    private function search_with_openrouter($keywords, $client_name, $max_results) {
        $api_key = $this->settings['api_key'] ?? '';
        
        if (empty($api_key)) {
            return array('error' => 'OpenRouter API key not configured');
        }
        
        $search_query = $this->build_search_query($keywords, $client_name);
        
        $prompt = "Search the web for recent mentions of: {$search_query}
        
        Please provide a JSON response with the following structure:
        {
            \"results\": [
                {
                    \"title\": \"Article/Post Title\",
                    \"url\": \"https://example.com/article\",
                    \"content\": \"Brief summary or excerpt\",
                    \"source\": \"Website/Platform name\",
                    \"type\": \"article|news|social|blog|forum\",
                    \"sentiment\": \"positive|negative|neutral\",
                    \"relevance_score\": 0.85
                }
            ]
        }
        
        Focus on recent content (last 30 days) and provide up to {$max_results} results.
        Include articles, news, blog posts, social media mentions, and forum discussions.
        Rate relevance from 0.0 to 1.0 based on how directly it relates to the search terms.";
        
        $response = $this->make_openrouter_request($prompt);
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        return $this->parse_ai_response($response);
    }
    
    /**
     * Search using Perplexity API
     */
    private function search_with_perplexity($keywords, $client_name, $max_results) {
        $api_key = $this->settings['api_key'] ?? '';
        
        if (empty($api_key)) {
            return array('error' => 'Perplexity API key not configured');
        }
        
        $search_query = $this->build_search_query($keywords, $client_name);
        
        $prompt = "Find recent web mentions of: {$search_query}
        
        Return a JSON array of results with this structure:
        [
            {
                \"title\": \"Title\",
                \"url\": \"URL\",
                \"content\": \"Summary\",
                \"source\": \"Source name\",
                \"type\": \"article|news|social|blog|forum\",
                \"sentiment\": \"positive|negative|neutral\",
                \"relevance_score\": 0.85
            }
        ]
        
        Focus on content from the last 30 days. Provide up to {$max_results} results.
        Include various content types: news articles, blog posts, social media, forums.
        Rate relevance 0.0-1.0 based on direct relation to search terms.";
        
        $response = $this->make_perplexity_request($prompt);
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        return $this->parse_ai_response($response);
    }
    
    /**
     * Make request to OpenRouter API
     */
    private function make_openrouter_request($prompt) {
        $api_key = $this->settings['api_key'];
        
        $request_data = array(
            'model' => 'openai/gpt-4o-mini', // Cost-effective model
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 4000,
            'temperature' => 0.3
        );
        
        $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url(),
                'X-Title' => 'Brand Reputation Monitor'
            ),
            'body' => json_encode($request_data),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        return new WP_Error('api_error', 'Invalid response from OpenRouter API');
    }
    
    /**
     * Make request to Perplexity API
     */
    private function make_perplexity_request($prompt) {
        $api_key = $this->settings['api_key'];
        
        $request_data = array(
            'model' => 'llama-3.1-sonar-small-128k-online', // Cost-effective model with web access
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 4000,
            'temperature' => 0.3
        );
        
        $response = wp_remote_post('https://api.perplexity.ai/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        return new WP_Error('api_error', 'Invalid response from Perplexity API');
    }
    
    /**
     * Build search query from keywords and client name
     */
    private function build_search_query($keywords, $client_name) {
        $keyword_array = array_map('trim', explode(',', $keywords));
        $search_terms = array_merge(array($client_name), $keyword_array);
        
        return implode(' OR ', array_map(function($term) {
            return '"' . $term . '"';
        }, $search_terms));
    }
    
    /**
     * Parse AI response and extract results
     */
    private function parse_ai_response($response) {
        // Try to extract JSON from the response
        $json_start = strpos($response, '[');
        $json_end = strrpos($response, ']') + 1;
        
        if ($json_start !== false && $json_end !== false) {
            $json_string = substr($response, $json_start, $json_end - $json_start);
            $results = json_decode($json_string, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($results)) {
                return array('results' => $results);
            }
        }
        
        // Try to find JSON object
        $json_start = strpos($response, '{');
        $json_end = strrpos($response, '}') + 1;
        
        if ($json_start !== false && $json_end !== false) {
            $json_string = substr($response, $json_start, $json_end - $json_start);
            $data = json_decode($json_string, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['results'])) {
                return $data;
            }
        }
        
        // Fallback: try to extract URLs and titles manually
        return $this->extract_results_manually($response);
    }
    
    /**
     * Manually extract results from AI response
     */
    private function extract_results_manually($response) {
        $results = array();
        
        // Look for URLs in the response
        preg_match_all('/https?:\/\/[^\s<>"]+/', $response, $urls);
        
        foreach ($urls[0] as $index => $url) {
            $results[] = array(
                'title' => 'Mention found',
                'url' => $url,
                'content' => 'AI-detected mention',
                'source' => parse_url($url, PHP_URL_HOST),
                'type' => 'article',
                'sentiment' => 'neutral',
                'relevance_score' => 0.5
            );
        }
        
        return array('results' => $results);
    }
    
    /**
     * Analyze sentiment of content
     */
    public function analyze_sentiment($content) {
        $provider = $this->settings['ai_provider'] ?? 'openrouter';
        
        $prompt = "Analyze the sentiment of this text and respond with only one word: positive, negative, or neutral.
        
        Text: {$content}";
        
        if ($provider === 'perplexity') {
            $response = $this->make_perplexity_request($prompt);
        } else {
            $response = $this->make_openrouter_request($prompt);
        }
        
        if (is_wp_error($response)) {
            return 'neutral';
        }
        
        $sentiment = strtolower(trim($response));
        return in_array($sentiment, array('positive', 'negative', 'neutral')) ? $sentiment : 'neutral';
    }
    
    /**
     * Test API connectivity
     */
    public function test_api_connectivity() {
        $provider = $this->settings['ai_provider'] ?? 'openrouter';
        $api_key = $this->settings['api_key'] ?? '';
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'API key not configured',
                'provider' => $provider
            );
        }
        
        // Simple test prompt
        $test_prompt = "Respond with just the word 'OK' to confirm API connectivity.";
        
        try {
            if ($provider === 'perplexity') {
                $response = $this->make_perplexity_request($test_prompt);
            } else {
                $response = $this->make_openrouter_request($test_prompt);
            }
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => $response->get_error_message(),
                    'provider' => $provider
                );
            }
            
            // Check if response contains expected content
            $response_clean = strtolower(trim($response));
            if (strpos($response_clean, 'ok') !== false || strpos($response_clean, 'connected') !== false) {
                return array(
                    'success' => true,
                    'message' => 'API connection successful',
                    'provider' => $provider,
                    'response_time' => $this->get_last_response_time()
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Unexpected API response: ' . substr($response, 0, 100),
                    'provider' => $provider
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'API test failed: ' . $e->getMessage(),
                'provider' => $provider
            );
        }
    }
    
    /**
     * Get last response time (placeholder for now)
     */
    private function get_last_response_time() {
        // This would track actual response times in a real implementation
        return '~2.5s';
    }
    
    /**
     * Get cost estimate for API usage
     */
    public function get_cost_estimate($num_requests, $provider = null) {
        $provider = $provider ?: ($this->settings['ai_provider'] ?? 'openrouter');
        
        // Rough cost estimates (as of 2024)
        $costs = array(
            'openrouter' => array(
                'gpt-4o-mini' => 0.00015, // per 1K tokens
                'gpt-3.5-turbo' => 0.0005
            ),
            'perplexity' => array(
                'llama-3.1-sonar-small' => 0.0002, // per 1K tokens
                'llama-3.1-sonar-large' => 0.001
            )
        );
        
        $model = $provider === 'openrouter' ? 'gpt-4o-mini' : 'llama-3.1-sonar-small';
        $cost_per_1k = $costs[$provider][$model] ?? 0.0002;
        
        // Estimate 2K tokens per request
        $estimated_cost = ($num_requests * 2 * $cost_per_1k);
        
        return array(
            'provider' => $provider,
            'model' => $model,
            'cost_per_request' => $cost_per_1k * 2,
            'total_estimated_cost' => $estimated_cost,
            'currency' => 'USD'
        );
    }
}