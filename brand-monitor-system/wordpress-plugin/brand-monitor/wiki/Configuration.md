# Configuration Guide

This guide covers all configuration options for the Brand Monitor plugin and backend.

## Table of Contents

1. [WordPress Plugin Settings](#wordpress-plugin-settings)
2. [Backend Configuration](#backend-configuration)
3. [Apify Setup](#apify-setup)
4. [Anthropic API Setup](#anthropic-api-setup)
5. [Webhook Configuration](#webhook-configuration)
6. [Scheduling Options](#scheduling-options)

---

## WordPress Plugin Settings

### Accessing Settings

1. Log in to WordPress admin
2. Navigate to **Brand Monitor > Settings**

### Configuration Options

| Setting | Description | Example |
|---------|-------------|---------|
| **API URL** | Your backend API endpoint | `https://api.yourdomain.com` |
| **API Key** | Authentication key from backend | `bm_live_xxxxxxxxxxxx` |

### API URL

The API URL should point to your Brand Monitor backend server:

- **Local development**: `http://localhost:8000`
- **Production**: `https://api.yourdomain.com`

**Important**: Use HTTPS in production for security.

### API Key

Generate an API key from the backend:

```bash
# Using the API
curl -X POST https://api.yourdomain.com/api/v1/clients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Company",
    "email": "admin@example.com",
    "subscription_tier": "professional"
  }'
```

The response includes your API key:

```json
{
  "id": "uuid-here",
  "api_key": "bm_live_xxxxxxxxxxxxxxxxxxxx",
  "name": "My Company"
}
```

---

## Backend Configuration

### Environment Variables

All backend configuration is done via environment variables in `.env`:

```env
# ============================================
# DATABASE CONFIGURATION
# ============================================
DATABASE_URL=postgresql://user:password@localhost:5432/brand_monitor

# ============================================
# REDIS CONFIGURATION
# ============================================
REDIS_URL=redis://localhost:6379/0

# ============================================
# API KEYS (REQUIRED)
# ============================================
# Apify - Get from https://apify.com/settings/integrations
APIFY_API_TOKEN=apify_api_xxxxxxxxxxxxxxxxxxxxx

# Anthropic - Get from https://console.anthropic.com/settings/keys
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxxx

# ============================================
# APPLICATION SETTINGS
# ============================================
# Random secret for JWT signing (generate with: openssl rand -hex 32)
SECRET_KEY=your-64-character-secret-key-here

# Environment: development or production
ENVIRONMENT=production

# ============================================
# OPTIONAL SETTINGS
# ============================================
# Public URL for Apify webhooks (must be accessible from internet)
WEBHOOK_BASE_URL=https://api.yourdomain.com

# CORS settings (comma-separated origins)
CORS_ORIGINS=https://yourwordpress.com,https://admin.yourwordpress.com

# Rate limiting
RATE_LIMIT_PER_MINUTE=60

# Logging level
LOG_LEVEL=INFO
```

### Configuration Reference

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `DATABASE_URL` | Yes | - | PostgreSQL connection string |
| `REDIS_URL` | Yes | - | Redis connection string |
| `APIFY_API_TOKEN` | Yes | - | Apify API token for scraping |
| `ANTHROPIC_API_KEY` | Yes | - | Claude API key for sentiment |
| `SECRET_KEY` | Yes | - | JWT signing key |
| `ENVIRONMENT` | No | `development` | `development` or `production` |
| `WEBHOOK_BASE_URL` | No | - | Public URL for webhooks |
| `CORS_ORIGINS` | No | `*` | Allowed CORS origins |
| `RATE_LIMIT_PER_MINUTE` | No | `60` | API rate limit |
| `LOG_LEVEL` | No | `INFO` | Logging verbosity |

---

## Apify Setup

Apify powers all web scraping in Brand Monitor.

### Step 1: Create Apify Account

1. Go to [apify.com](https://apify.com)
2. Sign up for a free account
3. Verify your email

### Step 2: Get API Token

1. Log in to Apify Console
2. Go to **Settings > Integrations**
3. Copy your **Personal API Token**

### Step 3: Choose a Plan

| Plan | Monthly Cost | Credits | Best For |
|------|-------------|---------|----------|
| **Free** | $0 | $5 | Testing |
| **Starter** | $49 | $49 | Small brands |
| **Scale** | $499 | $499 | Medium businesses |
| **Enterprise** | Custom | Custom | Large enterprises |

### Step 4: Configure in Backend

Add to your `.env`:

```env
APIFY_API_TOKEN=apify_api_xxxxxxxxxxxxxxxxxxxxx
```

### Understanding Apify Credits

Credits are consumed based on:
- **Compute units**: CPU time used by actors
- **Data transfer**: Amount of data downloaded
- **Proxy usage**: If using Apify proxy

**Approximate costs per 100 results:**

| Source Type | Credits Used |
|------------|-------------|
| Google Search | ~$0.10-0.30 |
| Twitter/X | ~$0.50-1.00 |
| Instagram | ~$0.30-0.80 |
| LinkedIn | ~$0.50-1.50 |
| Reddit | ~$0.10-0.30 |
| News | ~$0.10-0.30 |
| Review Sites | ~$0.20-0.50 |

---

## Anthropic API Setup

Claude AI provides sentiment analysis for all mentions.

### Step 1: Create Anthropic Account

1. Go to [console.anthropic.com](https://console.anthropic.com)
2. Sign up or log in
3. Add a payment method

### Step 2: Generate API Key

1. Navigate to **API Keys**
2. Click **Create Key**
3. Copy the key (starts with `sk-ant-`)

### Step 3: Configure in Backend

Add to your `.env`:

```env
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxxx
```

### Pricing

| Model | Input (per 1M tokens) | Output (per 1M tokens) |
|-------|----------------------|------------------------|
| Claude Sonnet 4 | $3.00 | $15.00 |
| Claude Haiku 3.5 | $0.80 | $4.00 |

**Estimated cost per mention**: $0.001-0.005 depending on content length

The system uses batch processing to optimize costs.

---

## Webhook Configuration

Webhooks allow Apify to notify your backend when scraping jobs complete.

### Requirements

- Your backend must be publicly accessible (not localhost)
- HTTPS is recommended for production

### Configuration

Set your public URL in `.env`:

```env
WEBHOOK_BASE_URL=https://api.yourdomain.com
```

The webhook endpoint is automatically configured as:
```
POST https://api.yourdomain.com/api/v1/webhooks/apify/{client_id}
```

### Using ngrok for Development

If developing locally, use ngrok to expose your backend:

```bash
# Install ngrok
brew install ngrok  # macOS
# or download from ngrok.com

# Expose local port 8000
ngrok http 8000
```

Copy the HTTPS URL (e.g., `https://abc123.ngrok.io`) and set:

```env
WEBHOOK_BASE_URL=https://abc123.ngrok.io
```

### Webhook Payload

Apify sends this payload when a job completes:

```json
{
  "eventType": "ACTOR.RUN.SUCCEEDED",
  "resource": {
    "id": "run-id-here",
    "defaultDatasetId": "dataset-id-here",
    "status": "SUCCEEDED",
    "usage": {
      "ACTOR_COMPUTE_UNITS": 0.5
    }
  }
}
```

---

## Scheduling Options

### WordPress Cron (Plugin-side)

The plugin uses WordPress cron for hourly syncs:

```php
// Default: Sync mentions every hour
add_action('brand_monitor_hourly_sync', 'brand_monitor_sync_mentions');
```

**Customize interval** in `includes/scheduler.php`:

```php
// Change to every 30 minutes
wp_schedule_event(time(), 'thirty_minutes', 'brand_monitor_hourly_sync');
```

### Backend Scheduling

For automated scraping, configure cron jobs:

**Using crontab:**

```bash
crontab -e

# Add these lines:
# Run social media scrape every 4 hours
0 */4 * * * curl -X POST https://api.yourdomain.com/api/v1/scrape/trigger \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"source_type": "twitter", "keywords": ["your brand"]}'

# Run comprehensive scan daily at midnight
0 0 * * * curl -X POST https://api.yourdomain.com/api/v1/scrape/trigger/comprehensive \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"brand_keywords": ["your brand", "your product"]}'
```

### Celery Beat (Advanced)

For complex scheduling, use Celery Beat:

```python
# In app/tasks.py
from celery.schedules import crontab

app.conf.beat_schedule = {
    'social-media-every-4-hours': {
        'task': 'app.tasks.scrape_social_media',
        'schedule': crontab(hour='*/4'),
        'args': (['your brand', 'your product'],),
    },
    'news-every-hour': {
        'task': 'app.tasks.scrape_news',
        'schedule': crontab(minute=0),
        'args': (['your brand'],),
    },
}
```

Run Celery Beat:

```bash
celery -A app.tasks beat --loglevel=info
```

---

## Security Best Practices

### 1. Use Strong API Keys

Generate secure keys:

```bash
openssl rand -hex 32
```

### 2. Enable HTTPS

Always use HTTPS in production:

```nginx
server {
    listen 443 ssl;
    server_name api.yourdomain.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://localhost:8000;
    }
}
```

### 3. Restrict CORS

In production, limit CORS origins:

```env
CORS_ORIGINS=https://yourwordpress.com
```

### 4. Rate Limiting

Enable rate limiting to prevent abuse:

```env
RATE_LIMIT_PER_MINUTE=60
```

### 5. Rotate Keys Regularly

Update API keys periodically:

1. Generate new key in backend
2. Update WordPress plugin settings
3. Revoke old key

---

## Next Steps

- [Set up monitoring sources](Scraping-Sources.md)
- [Explore the dashboard](Dashboard-Guide.md)
- [View API reference](API-Reference.md)
