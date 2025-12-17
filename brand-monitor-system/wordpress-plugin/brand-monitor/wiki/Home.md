# Brand Monitor - WordPress Plugin Documentation

Welcome to the Brand Monitor WordPress Plugin documentation. This plugin provides AI-powered brand monitoring and web intelligence directly in your WordPress dashboard.

## Overview

Brand Monitor connects your WordPress site to a powerful backend that scrapes **50+ data sources** across the web and social media, analyzes sentiment using Claude AI, and delivers actionable brand intelligence.

### Key Features

- **Real-time Dashboard** - View mentions, sentiment scores, and alerts at a glance
- **50+ Data Sources** - Monitor social media, news, reviews, forums, and more
- **AI-Powered Sentiment Analysis** - Understand how people feel about your brand
- **Automated Alerts** - Get notified when negative mentions or crises are detected
- **Scheduled Scraping** - Set up automated monitoring schedules
- **Comprehensive Reports** - Generate detailed brand intelligence reports

## Quick Links

| Documentation | Description |
|--------------|-------------|
| [Installation Guide](Installation.md) | How to install and activate the plugin |
| [Configuration Guide](Configuration.md) | Setting up API keys and connections |
| [Dashboard Guide](Dashboard-Guide.md) | Using the monitoring dashboard |
| [Scraping Sources](Scraping-Sources.md) | Complete list of 50+ supported sources |
| [API Reference](API-Reference.md) | Backend API endpoints and usage |
| [Troubleshooting](Troubleshooting.md) | Common issues and solutions |
| [Developer Guide](Developer-Guide.md) | Extending and customizing the plugin |
| [Cost Estimation](Cost-Estimation.md) | Pricing guide for Apify and Claude AI |

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     WordPress Site                               │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              Brand Monitor Plugin                         │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐    │   │
│  │  │Dashboard│  │ Sources │  │ Reports │  │Settings │    │   │
│  │  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘    │   │
│  │       └────────────┴────────────┴────────────┘          │   │
│  │                         │                                │   │
│  │                   API Client                             │   │
│  └─────────────────────────┼───────────────────────────────┘   │
└────────────────────────────┼────────────────────────────────────┘
                             │ HTTPS/REST
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Brand Monitor Backend                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐     │
│  │  FastAPI    │  │  Celery     │  │  PostgreSQL         │     │
│  │  Server     │  │  Workers    │  │  + Redis            │     │
│  └──────┬──────┘  └──────┬──────┘  └─────────────────────┘     │
│         │                │                                       │
│         └────────┬───────┘                                       │
│                  │                                               │
│    ┌─────────────┼─────────────┐                                │
│    ▼             ▼             ▼                                │
│ ┌──────┐    ┌─────────┐   ┌──────────┐                         │
│ │Apify │    │Claude AI│   │Webhooks  │                         │
│ │(50+  │    │Sentiment│   │Processing│                         │
│ │actors)│   │Analysis │   │          │                         │
│ └──────┘    └─────────┘   └──────────┘                         │
└─────────────────────────────────────────────────────────────────┘
```

## Supported Platforms

### Social Media (21 sources)
Twitter/X, Instagram, Facebook, LinkedIn, TikTok, YouTube, Pinterest, Threads, Bluesky, Mastodon

### Review Sites (12 sources)
Yelp, Trustpilot, G2, Capterra, Glassdoor, Indeed, Amazon Reviews, Google Maps, Google Reviews, TripAdvisor, App Store, Google Play

### Forums & Communities (8 sources)
Reddit, Hacker News, Quora, Product Hunt, Discord, Telegram, Stack Overflow

### News & Media (6 sources)
Google News, Bing News, RSS Feeds, Medium, Substack, News API

### E-commerce (5 sources)
Amazon, eBay, Walmart, Etsy, Shopify

### Search Engines (2 sources)
Google Search, Bing Search

## System Requirements

### WordPress Requirements
- WordPress 6.2 or higher
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+

### Backend Requirements
- Python 3.11+
- PostgreSQL 15+
- Redis 7+
- Apify Account (for web scraping)
- Anthropic API Key (for sentiment analysis)

## Getting Started

1. **Install the Plugin** - Upload to WordPress or install via zip
2. **Configure API Connection** - Enter your backend API URL and key
3. **Set Up Monitoring Sources** - Choose which platforms to monitor
4. **Configure Keywords** - Add your brand name and related terms
5. **Start Monitoring** - View results in the dashboard

Continue to the [Installation Guide](Installation.md) to get started.

## Support

- **GitHub Issues**: [Report bugs and request features](https://github.com/your-repo/brand-monitor/issues)
- **Documentation**: You're reading it!
- **Email Support**: support@yourdomain.com

## License

This plugin is licensed under the GPLv2 or later.

---

**Version**: 1.0.0
**Last Updated**: December 2024
