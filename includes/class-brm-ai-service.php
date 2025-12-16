<?php
/**
 * AI Service class for web scraping and analysis
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRM_AI_Service {

    private $settings;
    private $last_request_time = 0;
    private $min_request_interval = 1; // Minimum seconds between requests (rate limiting)

    public function __construct() {
        $this->settings = get_option('brm_settings', array());
        $this->last_request_time = get_transient('brm_last_api_request') ?: 0;
    }

    /**
     * Apply rate limiting to prevent API abuse
     */
    private function apply_rate_limit() {
        $current_time = time();
        $time_since_last = $current_time - $this->last_request_time;

        if ($time_since_last < $this->min_request_interval) {
            $wait_time = $this->min_request_interval - $time_since_last;
            sleep($wait_time);
        }

        $this->last_request_time = time();
        set_transient('brm_last_api_request', $this->last_request_time, 3600);
    }

    /**
     * Search using Google Custom Search API (Real web results)
     */
    public function search_with_google($keywords, $client_name, $max_results = 10) {
        $api_key = $this->settings['google_api_key'] ?? '';
        $search_engine_id = $this->settings['google_search_engine_id'] ?? '';

        if (empty($api_key) || empty($search_engine_id)) {
            return array('error' => 'Google Custom Search API not configured');
        }

        $search_query = $this->build_search_query($keywords, $client_name);
        $results = array();

        // Google CSE returns max 10 results per request
        $num_requests = ceil($max_results / 10);
        $total_fetched = 0;

        for ($i = 0; $i < $num_requests && $total_fetched < $max_results; $i++) {
            $start_index = ($i * 10) + 1;

            $url = add_query_arg(array(
                'key' => $api_key,
                'cx' => $search_engine_id,
                'q' => $search_query,
                'num' => min(10, $max_results - $total_fetched),
                'start' => $start_index,
                'dateRestrict' => 'm1', // Last month
                'safe' => 'active'
            ), 'https://www.googleapis.com/customsearch/v1');

            $this->apply_rate_limit();

            $response = wp_remote_get($url, array('timeout' => 30));

            if (is_wp_error($response)) {
                BRM_Database::log_monitoring_action(null, 'google_search_error', $response->get_error_message(), 'error');
                continue;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if ($status_code !== 200) {
                $error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
                BRM_Database::log_monitoring_action(null, 'google_search_error', "HTTP $status_code: $error_msg", 'error');
                continue;
            }

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $url = $this->normalize_url($item['link'] ?? '');
                    if (empty($url)) continue;

                    $results[] = array(
                        'title' => $item['title'] ?? 'Untitled',
                        'url' => $url,
                        'content' => $item['snippet'] ?? '',
                        'source' => parse_url($url, PHP_URL_HOST) ?: '',
                        'type' => $this->classify_content_type($url, $item['snippet'] ?? ''),
                        'sentiment' => 'neutral', // Will be analyzed separately
                        'relevance_score' => 0.8 // Google results are highly relevant
                    );
                    $total_fetched++;

                    if ($total_fetched >= $max_results) break;
                }
            }
        }

        BRM_Database::log_monitoring_action(null, 'google_search_completed', "Found $total_fetched results from Google", 'success');

        return array('results' => $results);
    }

    /**
     * Search using NewsAPI (Real news articles)
     */
    public function search_with_newsapi($keywords, $client_name, $max_results = 10) {
        $api_key = $this->settings['newsapi_key'] ?? '';

        if (empty($api_key)) {
            return array('error' => 'NewsAPI key not configured');
        }

        $search_query = $client_name . ' ' . str_replace(',', ' OR ', $keywords);

        $url = add_query_arg(array(
            'apiKey' => $api_key,
            'q' => $search_query,
            'language' => 'en',
            'sortBy' => 'relevancy',
            'pageSize' => min($max_results, 100),
            'from' => date('Y-m-d', strtotime('-30 days'))
        ), 'https://newsapi.org/v2/everything');

        $this->apply_rate_limit();

        $response = wp_remote_get($url, array('timeout' => 30));

        if (is_wp_error($response)) {
            BRM_Database::log_monitoring_action(null, 'newsapi_error', $response->get_error_message(), 'error');
            return array('error' => $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code !== 200 || ($data['status'] ?? '') !== 'ok') {
            $error_msg = $data['message'] ?? 'Unknown NewsAPI error';
            BRM_Database::log_monitoring_action(null, 'newsapi_error', $error_msg, 'error');
            return array('error' => $error_msg);
        }

        $results = array();
        if (isset($data['articles']) && is_array($data['articles'])) {
            foreach ($data['articles'] as $article) {
                $url = $this->normalize_url($article['url'] ?? '');
                if (empty($url)) continue;

                $results[] = array(
                    'title' => $article['title'] ?? 'Untitled',
                    'url' => $url,
                    'content' => $article['description'] ?? '',
                    'source' => $article['source']['name'] ?? parse_url($url, PHP_URL_HOST),
                    'type' => 'news',
                    'sentiment' => 'neutral', // Will be analyzed separately
                    'relevance_score' => 0.85 // News articles are highly relevant
                );

                if (count($results) >= $max_results) break;
            }
        }

        BRM_Database::log_monitoring_action(null, 'newsapi_completed', 'Found ' . count($results) . ' news articles', 'success');

        return array('results' => $results);
    }

    /**
     * Classify content type based on URL and content
     */
    private function classify_content_type($url, $content) {
        $url_lower = strtolower($url);
        $content_lower = strtolower($content);

        // Check URL patterns
        if (preg_match('/(news|press|article|story)/', $url_lower)) {
            return 'news';
        }
        if (preg_match('/(blog|post|journal)/', $url_lower)) {
            return 'blog';
        }
        if (preg_match('/(forum|discuss|thread|community)/', $url_lower)) {
            return 'forum';
        }
        if (preg_match('/(twitter|facebook|linkedin|instagram|tiktok)/', $url_lower)) {
            return 'social';
        }

        // Check domain patterns
        $host = parse_url($url, PHP_URL_HOST);
        if ($host) {
            if (preg_match('/(news|press|times|post|journal|herald|tribune)/', $host)) {
                return 'news';
            }
            if (preg_match('/(blog|medium|wordpress|blogger|substack)/', $host)) {
                return 'blog';
            }
            if (preg_match('/(reddit|quora|stackoverflow|forum)/', $host)) {
                return 'forum';
            }
        }

        return 'article'; // Default type
    }

    /**
     * Perform hybrid search using multiple sources
     */
    public function search_mentions_hybrid($keywords, $client_name, $max_results = 20) {
        $all_results = array();
        $errors = array();

        // Calculate results per source
        $results_per_source = ceil($max_results / 3);

        // 1. Try Google Custom Search (if configured)
        if (!empty($this->settings['google_api_key']) && !empty($this->settings['google_search_engine_id'])) {
            BRM_Database::log_monitoring_action(null, 'hybrid_search', 'Querying Google Custom Search', 'info');
            $google_results = $this->search_with_google($keywords, $client_name, $results_per_source);
            if (isset($google_results['results'])) {
                $all_results = array_merge($all_results, $google_results['results']);
            } else {
                $errors[] = 'Google: ' . ($google_results['error'] ?? 'Unknown error');
            }
        }

        // 2. Try NewsAPI (if configured)
        if (!empty($this->settings['newsapi_key'])) {
            BRM_Database::log_monitoring_action(null, 'hybrid_search', 'Querying NewsAPI', 'info');
            $news_results = $this->search_with_newsapi($keywords, $client_name, $results_per_source);
            if (isset($news_results['results'])) {
                $all_results = array_merge($all_results, $news_results['results']);
            } else {
                $errors[] = 'NewsAPI: ' . ($news_results['error'] ?? 'Unknown error');
            }
        }

        // 3. Use AI-based search as fallback or supplement
        $ai_provider = $this->settings['ai_provider'] ?? 'openrouter';
        $remaining_slots = $max_results - count($all_results);

        if ($remaining_slots > 0 && !empty($this->settings['api_key'])) {
            BRM_Database::log_monitoring_action(null, 'hybrid_search', "Querying $ai_provider for additional results", 'info');

            // Only use Perplexity for AI search since it has web access
            if ($ai_provider === 'perplexity') {
                $ai_results = $this->search_with_perplexity($keywords, $client_name, $remaining_slots);
                if (isset($ai_results['results'])) {
                    $all_results = array_merge($all_results, $ai_results['results']);
                } else {
                    $errors[] = 'Perplexity: ' . ($ai_results['error'] ?? 'Unknown error');
                }
            }
        }

        // Deduplicate results by canonical URL
        $unique_results = array();
        $seen_urls = array();

        foreach ($all_results as $result) {
            $canonical = $this->canonicalize_url($result['url']);
            if (!in_array($canonical, $seen_urls)) {
                $seen_urls[] = $canonical;
                $unique_results[] = $result;
            }
        }

        // Analyze sentiment for results that need it
        $unique_results = $this->batch_analyze_sentiment($unique_results);

        // Trim to max_results
        $unique_results = array_slice($unique_results, 0, $max_results);

        $log_message = 'Hybrid search completed. Found ' . count($unique_results) . ' unique results.';
        if (!empty($errors)) {
            $log_message .= ' Errors: ' . implode('; ', $errors);
        }
        BRM_Database::log_monitoring_action(null, 'hybrid_search_completed', $log_message, 'success');

        return array('results' => $unique_results, 'errors' => $errors);
    }

    /**
     * Batch analyze sentiment for results
     */
    private function batch_analyze_sentiment($results) {
        // Only analyze if AI provider is configured
        if (empty($this->settings['api_key'])) {
            return $results;
        }

        foreach ($results as &$result) {
            if ($result['sentiment'] === 'neutral' && !empty($result['content'])) {
                // Quick sentiment classification based on content
                $content = strtolower($result['content']);

                // Simple keyword-based sentiment (fast, no API call needed)
                $positive_words = array('great', 'excellent', 'amazing', 'love', 'best', 'wonderful', 'fantastic', 'positive', 'success', 'award', 'win', 'innovative', 'growth');
                $negative_words = array('bad', 'terrible', 'worst', 'hate', 'fail', 'problem', 'issue', 'lawsuit', 'scandal', 'fraud', 'crisis', 'negative', 'loss', 'decline');

                $positive_score = 0;
                $negative_score = 0;

                foreach ($positive_words as $word) {
                    if (strpos($content, $word) !== false) {
                        $positive_score++;
                    }
                }

                foreach ($negative_words as $word) {
                    if (strpos($content, $word) !== false) {
                        $negative_score++;
                    }
                }

                if ($positive_score > $negative_score) {
                    $result['sentiment'] = 'positive';
                } elseif ($negative_score > $positive_score) {
                    $result['sentiment'] = 'negative';
                }
                // Otherwise stays neutral
            }
        }

        return $results;
    }

    /**
     * Helper to canonicalize URL for deduplication
     */
    private function canonicalize_url($url) {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return $url;
        }
        $host = strtolower($parts['host']);
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        if ($path === '') {
            $path = '/';
        }
        return $host . $path;
    }
    
    /**
     * Normalize URL (ensure scheme and trim)
     */
    public function normalize_url($url) {
        $url = trim($url ?? '');
        if (empty($url)) {
            return '';
        }
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            // If it's missing scheme but looks like a domain
            if (preg_match('/^[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $url)) {
                $url = 'https://' . $url;
            } else {
                return '';
            }
        } elseif (empty($parts['scheme'])) {
            $url = 'https://' . ltrim($url, '/');
        }
        return $url;
    }

    /**
     * Validate URL by basic checks and HTTP response
     */
    public function validate_url($url) {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return false;
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        if (!in_array($scheme, array('http', 'https'))) {
            return false;
        }
        $host = strtolower($parts['host']);

        // Reject local/suspicious hosts
        $suspicious = array('localhost', 'example.com', 'test.com', 'domain.com', 'yourdomain.com', 'loremipsum.com');
        foreach ($suspicious as $bad) {
            if ($host === $bad || strpos($host, $bad) !== false) {
                return false;
            }
        }

        // Reject private IPs
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ip = $host;
            if (preg_match('/^(10\.|127\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $ip)) {
                return false;
            }
        }

        // Attempt HEAD request first
        $resp = wp_remote_head($url, array('timeout' => 10, 'redirection' => 5));
        if (is_wp_error($resp)) {
            // Fallback to GET for servers that disallow HEAD
            $resp = wp_remote_get($url, array('timeout' => 10, 'redirection' => 5));
            if (is_wp_error($resp)) {
                return false;
            }
        }
        $code = wp_remote_retrieve_response_code($resp);
        return is_numeric($code) && $code >= 200 && $code < 400;
    }
    
    /**
     * Search for mentions using AI service
     */
    public function search_mentions($keywords, $client_name, $max_results = 20) {
        $provider = $this->settings['ai_provider'] ?? 'openrouter';
        $api_key = $this->settings['api_key'] ?? '';
        
        // Check if API key is configured
        if (empty($api_key)) {
            BRM_Database::log_monitoring_action(null, 'ai_search_failed', 'No API key configured', 'error');
            return array('error' => 'API key not configured. Please configure your AI provider settings.');
        }
        
        BRM_Database::log_monitoring_action(null, 'ai_search_started', "Starting AI search for {$client_name} with {$provider}", 'info');
        
        switch ($provider) {
            case 'openrouter':
                $result = $this->search_with_openrouter($keywords, $client_name, $max_results);
                break;
            case 'perplexity':
                $result = $this->search_with_perplexity($keywords, $client_name, $max_results);
                break;
            default:
                $result = $this->search_with_openrouter($keywords, $client_name, $max_results);
        }
        
        // Log the result
        if (isset($result['error'])) {
            BRM_Database::log_monitoring_action(null, 'ai_search_failed', $result['error'], 'error');
        } else {
            $result_count = isset($result['results']) ? count($result['results']) : 0;
            BRM_Database::log_monitoring_action(null, 'ai_search_completed', "Found {$result_count} results", 'success');
        }
        
        return $result;
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
        
        $parsed = $this->parse_ai_response($response);
        $clean = isset($parsed['results']) && is_array($parsed['results'])
            ? $this->filter_and_normalize_results($parsed['results'], $max_results)
            : array();
        return array('results' => $clean);
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
        
        $parsed = $this->parse_ai_response($response);
        $clean = isset($parsed['results']) && is_array($parsed['results'])
            ? $this->filter_and_normalize_results($parsed['results'], $max_results)
            : array();
        return array('results' => $clean);
    }
    
    /**
     * Make request to OpenRouter API
     */
    private function make_openrouter_request($prompt) {
        $api_key = $this->settings['api_key'];

        // Apply rate limiting
        $this->apply_rate_limit();

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

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Handle API errors
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
            return new WP_Error('api_error', 'OpenRouter API error (HTTP ' . $status_code . '): ' . $error_message);
        }

        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        return new WP_Error('api_error', 'Invalid response from OpenRouter API');
    }
    
    /**
     * Make request to Perplexity API
     *
     * Perplexity Sonar models follow an OpenAI-compatible Chat Completions API.
     * See: https://docs.perplexity.ai/api-reference/chat-completions-post
     */
    private function make_perplexity_request($prompt) {
        $api_key = $this->settings['api_key'];

        // Apply rate limiting
        $this->apply_rate_limit();

        // Use a supported Sonar model per current docs. 'sonar-pro' provides advanced search.
        $model = 'sonar-pro';

        // Encourage concise, structured outputs and limit to recent content.
        $request_data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a precise, concise assistant. When asked to return structured data, output strict JSON only.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            // Keep responses bounded and deterministic for parsing
            'max_tokens' => 1500,
            'temperature' => 0.2,
            // Prefer recent sources (roughly last month)
            'search_recency_filter' => 'month',
            // Optionally increase search context size for better citations
            'web_search_options' => array(
                'search_context_size' => 'high'
            )
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

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Handle HTTP errors
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
            return new WP_Error('api_error', 'Perplexity API error (HTTP ' . $status_code . '): ' . $error_message);
        }

        // Return model message content on success
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        // Bubble up API error details if present
        if (isset($data['error'])) {
            $msg = is_array($data['error']) && isset($data['error']['message']) ? $data['error']['message'] : (string) $data['error'];
            return new WP_Error('api_error', 'Perplexity API error: ' . $msg);
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
        // Look for URLs in the response
        preg_match_all('/https?:\\/\\/[^\\s<>\"]+/', $response, $urls);
        $results = array();
        foreach ($urls[0] as $url) {
            $results[] = array(
                'title' => 'Mention found',
                'url' => $url,
                'content' => '',
                'source' => parse_url($url, PHP_URL_HOST),
                'type' => 'article',
                'sentiment' => 'neutral',
                'relevance_score' => 0.5
            );
        }
        return array('results' => $results);
    }

    /**
     * Filter and normalize results, enforce max_results and URL validation
     */
    private function filter_and_normalize_results($results, $max_results) {
        $clean = array();
        foreach ($results as $r) {
            $url = $this->normalize_url($r['url'] ?? '');
            if (empty($url) || !$this->validate_url($url)) {
                continue;
            }
            $clean[] = array(
                'title' => $r['title'] ?? 'Untitled',
                'url' => $url,
                'content' => $r['content'] ?? '',
                'source' => parse_url($url, PHP_URL_HOST) ?: ($r['source'] ?? ''),
                'type' => $r['type'] ?? 'article',
                'sentiment' => $r['sentiment'] ?? 'neutral',
                'relevance_score' => $r['relevance_score'] ?? 0.5
            );
            if (count($clean) >= $max_results) {
                break;
            }
        }
        return $clean;
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

        // Cost estimates (as of 2025) - input + output tokens combined
        // OpenRouter GPT-4o-mini: $0.15/1M input + $0.60/1M output
        // Perplexity Sonar Pro: $3/1M input + $15/1M output (includes search)
        $costs = array(
            'openrouter' => array(
                'model' => 'openai/gpt-4o-mini',
                'input_per_1k' => 0.00015,  // $0.15 per 1M = $0.00015 per 1K
                'output_per_1k' => 0.0006,   // $0.60 per 1M = $0.0006 per 1K
                'display_name' => 'GPT-4o-mini (via OpenRouter)'
            ),
            'perplexity' => array(
                'model' => 'sonar-pro',
                'input_per_1k' => 0.003,     // $3 per 1M = $0.003 per 1K
                'output_per_1k' => 0.015,    // $15 per 1M = $0.015 per 1K
                'display_name' => 'Sonar Pro (Perplexity)'
            )
        );

        $provider_costs = $costs[$provider] ?? $costs['openrouter'];

        // Estimate tokens per request: ~500 input (prompt) + ~1500 output (results)
        $input_tokens_per_request = 0.5;  // in thousands
        $output_tokens_per_request = 1.5; // in thousands

        $cost_per_request = ($input_tokens_per_request * $provider_costs['input_per_1k']) +
                            ($output_tokens_per_request * $provider_costs['output_per_1k']);

        $estimated_cost = $num_requests * $cost_per_request;

        return array(
            'provider' => ucfirst($provider),
            'model' => $provider_costs['display_name'],
            'cost_per_request' => $cost_per_request,
            'total_estimated_cost' => $estimated_cost,
            'currency' => 'USD',
            'note' => $provider === 'perplexity' ? 'Includes built-in web search' : 'Requires external search integration'
        );
    }
}