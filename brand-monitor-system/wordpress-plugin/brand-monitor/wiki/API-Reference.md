# API Reference

Complete reference for the Brand Monitor REST API.

## Table of Contents

1. [Authentication](#authentication)
2. [Scraping Endpoints](#scraping-endpoints)
3. [Mentions Endpoints](#mentions-endpoints)
4. [Analytics Endpoints](#analytics-endpoints)
5. [Alerts Endpoints](#alerts-endpoints)
6. [Usage Endpoints](#usage-endpoints)
7. [Webhook Events](#webhook-events)
8. [Error Handling](#error-handling)

---

## Base URL

```
https://api.yourdomain.com/api/v1
```

## Authentication

All API requests require a Bearer token in the Authorization header:

```
Authorization: Bearer YOUR_API_KEY
```

### Validate API Key

```http
POST /auth/validate
```

**Response:**

```json
{
  "valid": true,
  "client": {
    "id": "uuid",
    "name": "Your Company",
    "subscription_tier": "professional"
  }
}
```

---

## Scraping Endpoints

### List Available Sources

```http
GET /scrape/sources
```

**Response:**

```json
{
  "total_sources": 52,
  "sources": [
    {
      "source_type": "twitter",
      "actor_id": "apify/twitter-scraper",
      "category": "social_media",
      "keyword_field": "searchTerms",
      "default_config": {
        "maxTweets": 100,
        "sort": "Latest"
      }
    }
  ]
}
```

### List Source Categories

```http
GET /scrape/sources/categories
```

**Response:**

```json
{
  "total_categories": 7,
  "categories": {
    "social_media": {
      "sources": ["twitter", "instagram", "facebook", ...],
      "count": 21
    },
    "review_sites": {
      "sources": ["yelp", "trustpilot", ...],
      "count": 12
    }
  }
}
```

### Get Sources by Category

```http
GET /scrape/sources/{category}
```

**Parameters:**

| Name | Type | Description |
|------|------|-------------|
| `category` | string | Category name (e.g., `social_media`) |

**Response:**

```json
{
  "category": "social_media",
  "sources": [
    {
      "source_type": "twitter",
      "actor_id": "apify/twitter-scraper",
      "keyword_field": "searchTerms"
    }
  ],
  "count": 21
}
```

### Trigger Single Source Scrape

```http
POST /scrape/trigger
```

**Request Body:**

```json
{
  "source_type": "twitter",
  "keywords": ["@yourbrand", "your brand"],
  "custom_config": {
    "maxTweets": 200
  }
}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `source_type` | string | Yes | Source identifier |
| `keywords` | array | Yes | Search terms or URLs |
| `custom_config` | object | No | Override default settings |

**Response:**

```json
{
  "scrape_job_id": "uuid",
  "source_type": "twitter",
  "status": "running",
  "apify_run_id": "apify-run-id"
}
```

### Trigger Multi-Source Scrape

```http
POST /scrape/trigger/multi
```

**Request Body (by category):**

```json
{
  "keywords": ["your brand"],
  "category": "social_media"
}
```

**Request Body (by sources):**

```json
{
  "keywords": ["your brand"],
  "source_types": ["twitter", "instagram", "facebook"],
  "custom_configs": {
    "twitter": {"maxTweets": 200}
  }
}
```

**Response:**

```json
{
  "jobs_created": 21,
  "jobs": [
    {
      "scrape_job_id": "uuid",
      "source_type": "twitter",
      "status": "running",
      "apify_run_id": "apify-run-id"
    }
  ]
}
```

### Trigger Comprehensive Brand Scan

```http
POST /scrape/trigger/comprehensive
```

**Request Body:**

```json
{
  "brand_keywords": ["Your Brand", "Your Product"],
  "include_categories": ["social_media", "news", "review_sites"],
  "exclude_sources": ["discord", "telegram"]
}
```

**Response:**

```json
{
  "total_jobs": 35,
  "by_category": {
    "social_media": {
      "jobs_created": 19,
      "jobs": [...]
    },
    "news": {
      "jobs_created": 6,
      "jobs": [...]
    }
  }
}
```

### Get Scrape Job Status

```http
GET /scrape/status/{scrape_job_id}
```

**Response:**

```json
{
  "id": "uuid",
  "source_type": "twitter",
  "status": "completed",
  "mentions_found": 47,
  "started_at": "2024-12-17T10:00:00Z",
  "completed_at": "2024-12-17T10:02:30Z",
  "error_message": null
}
```

**Status Values:**

| Status | Description |
|--------|-------------|
| `pending` | Job created, not started |
| `running` | Apify actor running |
| `processing` | Processing results |
| `completed` | Successfully finished |
| `failed` | Error occurred |

### List Scrape Jobs

```http
GET /scrape/jobs
```

**Query Parameters:**

| Name | Type | Default | Description |
|------|------|---------|-------------|
| `status` | string | - | Filter by status |
| `source_type` | string | - | Filter by source |
| `limit` | int | 50 | Results per page |
| `offset` | int | 0 | Pagination offset |

**Response:**

```json
{
  "total": 150,
  "limit": 50,
  "offset": 0,
  "jobs": [
    {
      "id": "uuid",
      "source_type": "twitter",
      "status": "completed",
      "mentions_found": 47,
      "started_at": "2024-12-17T10:00:00Z",
      "completed_at": "2024-12-17T10:02:30Z"
    }
  ]
}
```

---

## Mentions Endpoints

### List Mentions

```http
GET /mentions
```

**Query Parameters:**

| Name | Type | Default | Description |
|------|------|---------|-------------|
| `sentiment` | string | - | Filter: `positive`, `negative`, `neutral` |
| `source_type` | string | - | Filter by source |
| `limit` | int | 50 | Results per page |
| `offset` | int | 0 | Pagination offset |
| `start_date` | string | - | ISO date filter |
| `end_date` | string | - | ISO date filter |

**Response:**

```json
{
  "total": 1247,
  "limit": 50,
  "offset": 0,
  "data": [
    {
      "id": "uuid",
      "source_type": "twitter",
      "source_url": "https://twitter.com/...",
      "title": null,
      "content": "Just tried @YourBrand and it's amazing!",
      "author": "user123",
      "sentiment": "positive",
      "sentiment_score": 0.85,
      "published_at": "2024-12-17T09:30:00Z",
      "discovered_at": "2024-12-17T10:02:30Z"
    }
  ]
}
```

### Get Mention Details

```http
GET /mentions/{mention_id}
```

**Response:**

```json
{
  "id": "uuid",
  "source_type": "twitter",
  "source_url": "https://twitter.com/...",
  "title": null,
  "content": "Just tried @YourBrand and it's amazing!",
  "author": "user123",
  "sentiment": "positive",
  "sentiment_score": 0.85,
  "confidence_score": 0.92,
  "entities": ["Your Brand", "Product X"],
  "crisis_indicator": false,
  "published_at": "2024-12-17T09:30:00Z",
  "discovered_at": "2024-12-17T10:02:30Z",
  "raw_data": {...}
}
```

---

## Analytics Endpoints

### Get Sentiment Analytics

```http
GET /analytics/sentiment
```

**Query Parameters:**

| Name | Type | Default | Description |
|------|------|---------|-------------|
| `days` | int | 7 | Time range in days |

**Response:**

```json
{
  "average_score": 0.42,
  "total_mentions": 1247,
  "positive_count": 687,
  "negative_count": 198,
  "neutral_count": 362,
  "trend": "improving",
  "change_percent": 5.2
}
```

### Get Source Analytics

```http
GET /analytics/sources
```

**Response:**

```json
{
  "total_mentions": 1247,
  "by_source": [
    {
      "source_type": "twitter",
      "count": 456,
      "percentage": 36.6
    },
    {
      "source_type": "instagram",
      "count": 234,
      "percentage": 18.8
    }
  ]
}
```

### Get Timeline Analytics

```http
GET /analytics/timeline
```

**Query Parameters:**

| Name | Type | Default | Description |
|------|------|---------|-------------|
| `days` | int | 30 | Time range |
| `interval` | string | `day` | `hour`, `day`, `week` |

**Response:**

```json
{
  "interval": "day",
  "data": [
    {
      "date": "2024-12-17",
      "mentions": 47,
      "sentiment_avg": 0.45
    }
  ]
}
```

---

## Alerts Endpoints

### List Alerts

```http
GET /alerts
```

**Query Parameters:**

| Name | Type | Default | Description |
|------|------|---------|-------------|
| `is_read` | boolean | - | Filter by read status |
| `severity` | string | - | `low`, `medium`, `high`, `critical` |
| `limit` | int | 50 | Results per page |

**Response:**

```json
{
  "total": 23,
  "data": [
    {
      "id": "uuid",
      "type": "negative_sentiment",
      "severity": "high",
      "message": "Negative mention spike detected",
      "mention_id": "uuid",
      "is_read": false,
      "created_at": "2024-12-17T10:00:00Z"
    }
  ]
}
```

### Mark Alert as Read

```http
PUT /alerts/{alert_id}/read
```

**Response:**

```json
{
  "id": "uuid",
  "is_read": true
}
```

---

## Usage Endpoints

### Get Current Usage

```http
GET /usage
```

**Response:**

```json
{
  "month": "2024-12",
  "mentions_processed": 4567,
  "mentions_limit": 10000,
  "apify_credits_used": 45.67,
  "apify_credits_limit": 100.00,
  "claude_tokens_used": 125000,
  "claude_tokens_limit": 500000
}
```

---

## Webhook Events

### Apify Webhook

```http
POST /webhooks/apify/{client_id}
```

Automatically called by Apify when scrape jobs complete.

**Payload (from Apify):**

```json
{
  "eventType": "ACTOR.RUN.SUCCEEDED",
  "resource": {
    "id": "apify-run-id",
    "defaultDatasetId": "dataset-id",
    "status": "SUCCEEDED",
    "usage": {
      "ACTOR_COMPUTE_UNITS": 0.5
    }
  }
}
```

**Response:**

```json
{
  "status": "processed",
  "scrape_job_id": "uuid",
  "source_type": "twitter",
  "mentions_processed": 47
}
```

---

## Error Handling

### Error Response Format

```json
{
  "detail": "Error message here"
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| `200` | Success |
| `201` | Created |
| `400` | Bad Request - Invalid parameters |
| `401` | Unauthorized - Invalid API key |
| `403` | Forbidden - Insufficient permissions |
| `404` | Not Found |
| `429` | Too Many Requests - Rate limited |
| `500` | Internal Server Error |

### Common Errors

**Invalid Source Type:**

```json
{
  "detail": "Unknown source type: invalid. Available types: twitter, instagram, ..."
}
```

**Missing API Key:**

```json
{
  "detail": "Missing API key."
}
```

**Rate Limited:**

```json
{
  "detail": "Rate limit exceeded. Try again in 60 seconds."
}
```

---

## Rate Limits

| Endpoint | Limit |
|----------|-------|
| All endpoints | 60 requests/minute |
| `/scrape/trigger` | 10 requests/minute |
| `/scrape/trigger/comprehensive` | 1 request/minute |

---

## SDK Examples

### Python

```python
import requests

class BrandMonitorClient:
    def __init__(self, api_url, api_key):
        self.api_url = api_url
        self.headers = {
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json"
        }

    def trigger_scrape(self, source_type, keywords):
        response = requests.post(
            f"{self.api_url}/api/v1/scrape/trigger",
            headers=self.headers,
            json={
                "source_type": source_type,
                "keywords": keywords
            }
        )
        return response.json()

    def get_mentions(self, limit=50, sentiment=None):
        params = {"limit": limit}
        if sentiment:
            params["sentiment"] = sentiment
        response = requests.get(
            f"{self.api_url}/api/v1/mentions",
            headers=self.headers,
            params=params
        )
        return response.json()

# Usage
client = BrandMonitorClient(
    "https://api.yourdomain.com",
    "your-api-key"
)
job = client.trigger_scrape("twitter", ["@yourbrand"])
mentions = client.get_mentions(sentiment="negative")
```

### JavaScript

```javascript
class BrandMonitorClient {
  constructor(apiUrl, apiKey) {
    this.apiUrl = apiUrl;
    this.headers = {
      'Authorization': `Bearer ${apiKey}`,
      'Content-Type': 'application/json'
    };
  }

  async triggerScrape(sourceType, keywords) {
    const response = await fetch(`${this.apiUrl}/api/v1/scrape/trigger`, {
      method: 'POST',
      headers: this.headers,
      body: JSON.stringify({
        source_type: sourceType,
        keywords: keywords
      })
    });
    return response.json();
  }

  async getMentions(params = {}) {
    const query = new URLSearchParams(params);
    const response = await fetch(
      `${this.apiUrl}/api/v1/mentions?${query}`,
      { headers: this.headers }
    );
    return response.json();
  }
}

// Usage
const client = new BrandMonitorClient(
  'https://api.yourdomain.com',
  'your-api-key'
);
const job = await client.triggerScrape('twitter', ['@yourbrand']);
const mentions = await client.getMentions({ sentiment: 'negative' });
```

---

## Next Steps

- [Explore Scraping Sources](Scraping-Sources.md)
- [View Dashboard Guide](Dashboard-Guide.md)
- [Troubleshooting](Troubleshooting.md)
