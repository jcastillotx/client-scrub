# Developer Guide

Extend and customize Brand Monitor for your specific needs.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Building Custom Apify Actors](#building-custom-apify-actors)
3. [Adding New Data Sources](#adding-new-data-sources)
4. [Extending the WordPress Plugin](#extending-the-wordpress-plugin)
5. [Custom Sentiment Analysis](#custom-sentiment-analysis)
6. [Webhooks & Integrations](#webhooks--integrations)
7. [Database Schema](#database-schema)

---

## Architecture Overview

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Plugin                          │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  PHP Classes                                         │   │
│  │  ├── Brand_Monitor_API_Client (API communication)   │   │
│  │  ├── Brand_Monitor_Database (local caching)         │   │
│  │  ├── Brand_Monitor_Scheduler (WP Cron)              │   │
│  │  └── Brand_Monitor_Notifications (alerts)           │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Backend API (FastAPI)                     │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Core Modules                                        │   │
│  │  ├── app/api/v1/ (REST endpoints)                   │   │
│  │  ├── app/scrapers/ (Apify orchestration)            │   │
│  │  ├── app/processors/ (sentiment analysis)           │   │
│  │  └── app/models/ (SQLAlchemy ORM)                   │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
        ┌──────────┐   ┌──────────┐   ┌──────────┐
        │  Apify   │   │ Claude   │   │PostgreSQL│
        │ (scrape) │   │  (AI)    │   │  (data)  │
        └──────────┘   └──────────┘   └──────────┘
```

---

## Building Custom Apify Actors

Building your own Apify actors provides **significant cost savings** (up to 70% reduction) and full control over scraping logic.

### Why Build Custom Actors?

| Benefit | Description |
|---------|-------------|
| **Lower Costs** | Pay only for compute, not actor fees |
| **Full Control** | Customize exactly what data is collected |
| **Better Performance** | Optimize for your specific use case |
| **No Dependencies** | Not affected by third-party actor changes |
| **Custom Integrations** | Direct webhook/API integration |

### Getting Started with Actor Templates

Apify provides templates at [apify.com/templates](https://apify.com/templates):

1. **Crawlee + Playwright** - For JavaScript-heavy sites
2. **Crawlee + Cheerio** - For static HTML sites
3. **Crawlee + Puppeteer** - Alternative browser automation
4. **Python Scrapy** - Python-based scraping

### Example: Custom Twitter Actor

```javascript
// src/main.js
import { Actor } from 'apify';
import { PlaywrightCrawler } from 'crawlee';

await Actor.init();

const input = await Actor.getInput();
const { searchTerms, maxTweets = 100 } = input;

const crawler = new PlaywrightCrawler({
    async requestHandler({ page, request, enqueueLinks }) {
        // Navigate to Twitter search
        const searchUrl = `https://twitter.com/search?q=${encodeURIComponent(request.userData.query)}&f=live`;
        await page.goto(searchUrl);

        // Wait for tweets to load
        await page.waitForSelector('[data-testid="tweet"]');

        // Extract tweets
        const tweets = await page.$$eval('[data-testid="tweet"]', (elements) => {
            return elements.map(el => ({
                text: el.querySelector('[data-testid="tweetText"]')?.innerText,
                author: el.querySelector('[data-testid="User-Name"]')?.innerText,
                url: el.querySelector('a[href*="/status/"]')?.href,
                timestamp: el.querySelector('time')?.dateTime,
            }));
        });

        // Save to dataset
        await Actor.pushData(tweets);
    },
});

// Run for each search term
for (const query of searchTerms) {
    await crawler.run([{ url: 'https://twitter.com', userData: { query } }]);
}

await Actor.exit();
```

### Actor Configuration

Create `actor.json`:

```json
{
    "actorSpecification": 1,
    "name": "custom-twitter-scraper",
    "title": "Custom Twitter Scraper",
    "version": "1.0.0",
    "input": {
        "type": "object",
        "properties": {
            "searchTerms": {
                "title": "Search Terms",
                "type": "array",
                "items": { "type": "string" }
            },
            "maxTweets": {
                "title": "Max Tweets",
                "type": "integer",
                "default": 100
            }
        },
        "required": ["searchTerms"]
    }
}
```

### Deploying Custom Actors

```bash
# Install Apify CLI
npm install -g apify-cli

# Login to Apify
apify login

# Create new actor
apify create my-custom-actor

# Deploy
apify push
```

### Integrating Custom Actors

Update `apify_orchestrator.py`:

```python
ACTOR_CONFIGS = {
    # ... existing actors ...

    # Your custom actor
    "custom_twitter": {
        "actor_id": "your-username/custom-twitter-scraper",
        "default_input": {
            "maxTweets": 100,
        },
        "keyword_field": "searchTerms",
    },
}
```

### Cost Comparison

| Actor Type | Cost per 1000 results | Savings |
|------------|----------------------|---------|
| Public Actor (apify/twitter-scraper) | ~$5-10 | Baseline |
| Custom Actor | ~$1-2 (compute only) | **70-80%** |

---

## Adding New Data Sources

### Step 1: Add Actor Configuration

In `backend/app/scrapers/apify_orchestrator.py`:

```python
ACTOR_CONFIGS = {
    # Add your new source
    "new_platform": {
        "actor_id": "apify/new-platform-scraper",  # or your custom actor
        "default_input": {
            "maxResults": 100,
        },
        "keyword_field": "searchTerms",
    },
}

# Add to appropriate category
SOURCE_CATEGORIES = {
    "social_media": [
        # ... existing sources
        "new_platform",  # Add here
    ],
}
```

### Step 2: Add Field Mappings

In `backend/app/scrapers/data_processor.py`:

```python
SOURCE_FIELD_MAPPINGS = {
    # Add mappings for the new source
    "new_platform": {
        "url": ["url", "postUrl", "link"],
        "title": ["title", "headline"],
        "content": ["text", "content", "body"],
        "author": ["author", "username", "user"],
        "published_at": ["createdAt", "timestamp", "date"],
        "engagement": ["likes", "shares", "comments"],
    },
}
```

### Step 3: Update WordPress Source Dropdown

In `wordpress-plugin/brand-monitor/admin/sources.php`:

```php
<select name="source_type">
    <!-- Existing options -->
    <option value="new_platform"><?php esc_html_e('New Platform', 'brand-monitor'); ?></option>
</select>
```

### Step 4: Test the Integration

```bash
# Test via API
curl -X POST https://api.yourdomain.com/api/v1/scrape/trigger \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"source_type": "new_platform", "keywords": ["test"]}'
```

---

## Extending the WordPress Plugin

### Adding Custom Admin Pages

```php
// In your-custom-extension.php
add_action('admin_menu', 'my_custom_brand_monitor_page');

function my_custom_brand_monitor_page() {
    add_submenu_page(
        'brand-monitor',
        __('Custom Analytics', 'brand-monitor'),
        __('Custom Analytics', 'brand-monitor'),
        'manage_options',
        'brand-monitor-custom',
        'render_custom_page'
    );
}

function render_custom_page() {
    $api_client = new Brand_Monitor_API_Client();
    $data = $api_client->get_mentions(['limit' => 100]);

    // Your custom rendering logic
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Custom Analytics', 'brand-monitor'); ?></h1>
        <!-- Your custom content -->
    </div>
    <?php
}
```

### Adding Custom Widgets

```php
// Dashboard widget
add_action('wp_dashboard_setup', 'my_custom_brand_widget');

function my_custom_brand_widget() {
    wp_add_dashboard_widget(
        'brand_monitor_custom_widget',
        __('Brand Monitor - Custom View', 'brand-monitor'),
        'render_custom_widget'
    );
}

function render_custom_widget() {
    $api_client = new Brand_Monitor_API_Client();
    $sentiment = $api_client->get_analytics('sentiment');

    echo '<p>' . esc_html__('Sentiment Score: ', 'brand-monitor');
    echo esc_html($sentiment['average_score']) . '</p>';
}
```

### Custom Hooks & Filters

```php
// Filter mentions before display
add_filter('brand_monitor_mentions', 'filter_mentions', 10, 1);

function filter_mentions($mentions) {
    // Add custom filtering logic
    return array_filter($mentions, function($m) {
        return $m['sentiment'] !== 'neutral';
    });
}

// Action after new mention
add_action('brand_monitor_new_mention', 'handle_new_mention', 10, 1);

function handle_new_mention($mention) {
    if ($mention['sentiment'] === 'negative') {
        // Send to Slack, create ticket, etc.
        wp_mail('team@example.com', 'Negative Mention Alert', $mention['content']);
    }
}
```

---

## Custom Sentiment Analysis

### Using Different AI Providers

Replace Claude with other providers in `backend/app/processors/sentiment_analyzer.py`:

```python
# OpenAI Example
import openai

class OpenAISentimentAnalyzer:
    def __init__(self):
        self.client = openai.OpenAI(api_key=settings.openai_api_key)

    def analyze(self, text: str) -> dict:
        response = self.client.chat.completions.create(
            model="gpt-4",
            messages=[
                {"role": "system", "content": "Analyze sentiment..."},
                {"role": "user", "content": text}
            ]
        )
        return self._parse_response(response)
```

### Local Sentiment Models

For offline analysis:

```python
from transformers import pipeline

class LocalSentimentAnalyzer:
    def __init__(self):
        self.classifier = pipeline(
            "sentiment-analysis",
            model="cardiffnlp/twitter-roberta-base-sentiment"
        )

    def analyze(self, text: str) -> dict:
        result = self.classifier(text[:512])[0]
        return {
            "sentiment": result["label"].lower(),
            "score": result["score"]
        }
```

---

## Webhooks & Integrations

### Outgoing Webhooks

Send data to external services:

```python
# backend/app/core/webhooks.py
import httpx

async def send_webhook(url: str, data: dict):
    async with httpx.AsyncClient() as client:
        await client.post(url, json=data)

# Usage in mention processor
async def on_new_mention(mention: Mention):
    if mention.sentiment == "negative":
        await send_webhook(
            "https://hooks.slack.com/services/xxx",
            {
                "text": f"Negative mention: {mention.content[:200]}",
                "source": mention.source_url
            }
        )
```

### Slack Integration

```python
# backend/app/integrations/slack.py
from slack_sdk import WebClient

class SlackNotifier:
    def __init__(self, token: str):
        self.client = WebClient(token=token)

    def send_alert(self, channel: str, mention: dict):
        self.client.chat_postMessage(
            channel=channel,
            blocks=[
                {
                    "type": "section",
                    "text": {
                        "type": "mrkdwn",
                        "text": f"*New {mention['sentiment']} mention*\n{mention['content'][:200]}"
                    }
                },
                {
                    "type": "actions",
                    "elements": [
                        {
                            "type": "button",
                            "text": {"type": "plain_text", "text": "View Source"},
                            "url": mention['source_url']
                        }
                    ]
                }
            ]
        )
```

---

## Database Schema

### Core Tables

```sql
-- Clients (multi-tenant)
CREATE TABLE clients (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    subscription_tier VARCHAR(50),
    monthly_mention_limit INTEGER,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Scrape Jobs
CREATE TABLE scrape_jobs (
    id UUID PRIMARY KEY,
    client_id UUID REFERENCES clients(id),
    source_type VARCHAR(50),
    apify_run_id VARCHAR(100),
    status VARCHAR(50) NOT NULL,
    mentions_found INTEGER DEFAULT 0,
    apify_credits_used NUMERIC(10, 4),
    error_message TEXT,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Mentions
CREATE TABLE mentions (
    id UUID PRIMARY KEY,
    client_id UUID REFERENCES clients(id),
    scrape_job_id UUID REFERENCES scrape_jobs(id),
    source_type VARCHAR(50),
    source_url TEXT,
    title TEXT,
    content TEXT,
    author VARCHAR(255),
    sentiment VARCHAR(50),
    sentiment_score NUMERIC(5, 4),
    confidence_score NUMERIC(5, 4),
    published_at TIMESTAMP,
    discovered_at TIMESTAMP DEFAULT NOW(),
    raw_data JSONB
);

-- Alerts
CREATE TABLE alerts (
    id UUID PRIMARY KEY,
    client_id UUID REFERENCES clients(id),
    mention_id UUID REFERENCES mentions(id),
    type VARCHAR(50),
    severity VARCHAR(50),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_mentions_client ON mentions(client_id);
CREATE INDEX idx_mentions_sentiment ON mentions(sentiment);
CREATE INDEX idx_mentions_source ON mentions(source_type);
CREATE INDEX idx_mentions_discovered ON mentions(discovered_at DESC);
CREATE INDEX idx_scrape_jobs_status ON scrape_jobs(status);
```

### Adding Custom Fields

```sql
-- Add rating field for review sources
ALTER TABLE mentions ADD COLUMN rating NUMERIC(3, 2);

-- Add engagement metrics
ALTER TABLE mentions ADD COLUMN engagement_count INTEGER;

-- Add custom tags
ALTER TABLE mentions ADD COLUMN tags TEXT[];
```

---

## Testing

### Backend Tests

```python
# tests/test_scraping.py
import pytest
from app.scrapers.apify_orchestrator import ApifyOrchestrator

def test_source_types():
    orch = ApifyOrchestrator()
    sources = orch.get_all_source_types()
    assert "twitter" in sources
    assert len(sources) >= 50

def test_categories():
    orch = ApifyOrchestrator()
    social = orch.get_sources_by_category("social_media")
    assert "twitter" in social
    assert "instagram" in social
```

### WordPress Tests

```php
// tests/test-api-client.php
class Test_API_Client extends WP_UnitTestCase {
    public function test_api_client_initialization() {
        $client = new Brand_Monitor_API_Client();
        $this->assertNotNull($client);
    }

    public function test_mentions_retrieval() {
        // Mock API response
        add_filter('pre_http_request', function() {
            return ['body' => json_encode(['data' => []])];
        });

        $client = new Brand_Monitor_API_Client();
        $mentions = $client->get_mentions();
        $this->assertIsArray($mentions);
    }
}
```

---

## Next Steps

- [Cost Estimation](Cost-Estimation.md) - Understand pricing and savings
- [API Reference](API-Reference.md) - Complete endpoint documentation
- [Troubleshooting](Troubleshooting.md) - Common development issues
