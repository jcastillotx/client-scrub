# Cost Estimation Guide

Understand the costs associated with Brand Monitor and how to optimize spending.

## Table of Contents

1. [Cost Overview](#cost-overview)
2. [Apify Costs](#apify-costs)
3. [Building Custom Actors (Big Savings!)](#building-custom-actors-big-savings)
4. [Anthropic Claude Costs](#anthropic-claude-costs)
5. [Infrastructure Costs](#infrastructure-costs)
6. [Cost Optimization Strategies](#cost-optimization-strategies)
7. [Pricing Scenarios](#pricing-scenarios)

---

## Cost Overview

Brand Monitor has three main cost components:

| Component | Purpose | Typical Cost |
|-----------|---------|--------------|
| **Apify** | Web scraping | $49-500+/month |
| **Anthropic Claude** | Sentiment analysis | $5-300/month |
| **Infrastructure** | Hosting, database | $15-100+/month |

### Total Monthly Cost Estimates

| Use Case | Mentions/Day | Monthly Cost |
|----------|-------------|--------------|
| Small Brand | 50-100 | $70-150 |
| Medium Brand | 500-1000 | $200-400 |
| Large Brand | 2000-5000 | $500-1000 |
| Enterprise | 10000+ | $1500+ |

---

## Apify Costs

### Pricing Plans

| Plan | Monthly Cost | Credits | Best For |
|------|-------------|---------|----------|
| **Free** | $0 | $5 | Testing only |
| **Starter** | $49 | $49 | Small brands |
| **Scale** | $499 | $499 | Medium businesses |
| **Enterprise** | Custom | Custom | High volume |

### Credit Usage by Source

Using **public Apify actors**:

| Source Category | Cost per 100 results | Notes |
|----------------|---------------------|-------|
| **Search Engines** | | |
| Google Search | $0.10-0.30 | Efficient |
| Bing Search | $0.10-0.20 | Efficient |
| **Social Media** | | |
| Twitter/X | $0.50-1.00 | Rate limits apply |
| Instagram | $0.30-0.80 | Post + comments |
| Facebook | $0.30-0.80 | Public pages only |
| LinkedIn | $0.50-1.50 | Most expensive |
| TikTok | $0.30-0.80 | Video metadata |
| YouTube | $0.20-0.50 | Including comments |
| **Review Sites** | | |
| Yelp | $0.20-0.50 | Reviews included |
| Trustpilot | $0.20-0.50 | Straightforward |
| G2/Capterra | $0.30-0.60 | Detailed reviews |
| Google Maps | $0.30-0.60 | With reviews |
| **Forums** | | |
| Reddit | $0.10-0.30 | Efficient |
| Hacker News | $0.10-0.20 | Lightweight |
| **News** | | |
| Google News | $0.10-0.30 | Headlines |
| RSS Feeds | $0.05-0.10 | Very efficient |

### Example Monthly Calculation

**Scenario: Medium brand, daily monitoring**

```
Daily scraping:
- Twitter (100 results): $0.75 × 30 days = $22.50
- Instagram (100 results): $0.50 × 30 days = $15.00
- Reddit (50 results): $0.15 × 30 days = $4.50
- News (50 results): $0.20 × 30 days = $6.00
- Reviews (100 results): $0.40 × 7 days = $2.80

Total Apify: ~$51/month (fits Starter plan)
```

---

## Building Custom Actors (Big Savings!)

### Why Build Custom Actors?

Building your own Apify actors can reduce costs by **70-80%** because:

1. **No actor fees** - Only pay for compute units
2. **Optimized for your needs** - No wasted data collection
3. **Full control** - Exactly what you need, nothing more

### Cost Comparison

| Approach | Cost per 1000 results | Monthly (10K results) |
|----------|----------------------|----------------------|
| Public actors | $5-10 | $50-100 |
| Custom actors | $1-2 | $10-20 |
| **Savings** | **70-80%** | **$40-80** |

### Apify Actor Templates

Get started quickly with templates at [apify.com/templates](https://apify.com/templates):

| Template | Best For | Setup Time |
|----------|----------|------------|
| **Crawlee + Playwright** | JavaScript-heavy sites (Twitter, Instagram) | 2-4 hours |
| **Crawlee + Cheerio** | Static HTML (news, blogs) | 1-2 hours |
| **Python Scrapy** | Large-scale crawling | 2-3 hours |
| **Crawlee + Puppeteer** | Browser automation | 2-4 hours |

### Custom Actor Development Costs

| Phase | Time | One-time Cost* |
|-------|------|---------------|
| Basic actor (1 source) | 4-8 hours | $200-400 |
| Advanced actor (anti-detection) | 8-16 hours | $400-800 |
| Full suite (all social media) | 40-80 hours | $2000-4000 |

*Based on $50/hour developer rate. DIY is free!

### ROI Analysis

**Building a custom Twitter actor:**

```
Development cost: $400 (one-time)
Monthly savings: $40-60 vs public actor

Break-even: 7-10 months
Year 1 savings: $80-320
Year 2+ savings: $480-720/year
```

### Getting Started

1. **Start with Apify templates**: [apify.com/templates](https://apify.com/templates)
2. **Choose Crawlee + Playwright** for most social media
3. **Use Cheerio** for news/blog sites (faster, cheaper)
4. **Test locally** before deploying
5. **Monitor usage** and optimize

```bash
# Quick start with Apify CLI
npm install -g apify-cli
apify create my-twitter-scraper --template playwright-crawlee
cd my-twitter-scraper
# Edit src/main.js
apify run  # Test locally
apify push # Deploy to Apify
```

---

## Anthropic Claude Costs

### Pricing (as of December 2024)

| Model | Input (per 1M tokens) | Output (per 1M tokens) |
|-------|----------------------|------------------------|
| Claude Sonnet 4 | $3.00 | $15.00 |
| Claude Haiku 3.5 | $0.80 | $4.00 |
| Claude Opus 4.5 | $15.00 | $75.00 |

### Recommended Model

**Claude Haiku 3.5** provides the best cost/performance for sentiment analysis:

- 80% cheaper than Sonnet
- Sufficient accuracy for sentiment classification
- Fast response times

### Token Usage Estimates

| Mention Length | Input Tokens | Output Tokens | Cost (Haiku) |
|---------------|-------------|--------------|--------------|
| Tweet (280 chars) | ~100 | ~50 | $0.0003 |
| Review (1000 chars) | ~300 | ~50 | $0.0005 |
| Article (5000 chars) | ~1500 | ~100 | $0.0016 |

### Monthly Claude Costs

| Mentions/Month | Average Cost |
|---------------|--------------|
| 1,000 | $0.50-1.00 |
| 10,000 | $5-10 |
| 50,000 | $25-50 |
| 100,000 | $50-100 |

### Optimization Tips

1. **Use Haiku** for simple sentiment classification
2. **Batch processing** reduces API overhead
3. **Cache results** for duplicate content
4. **Skip non-text content** (images, videos without captions)

---

## Infrastructure Costs

### Minimum Setup

| Component | Option | Monthly Cost |
|-----------|--------|-------------|
| **Server** | DigitalOcean Droplet (2GB) | $12 |
| **Database** | Managed PostgreSQL (basic) | $15 |
| **Redis** | Managed Redis (basic) | $10 |
| **Total** | | **$37** |

### Recommended Setup

| Component | Option | Monthly Cost |
|-----------|--------|-------------|
| **Server** | DigitalOcean Droplet (4GB) | $24 |
| **Database** | Managed PostgreSQL (2GB) | $25 |
| **Redis** | Managed Redis (1GB) | $15 |
| **CDN/SSL** | Cloudflare (free tier) | $0 |
| **Backups** | Automated | $5 |
| **Total** | | **$69** |

### Production Setup

| Component | Option | Monthly Cost |
|-----------|--------|-------------|
| **Server** | 2x DigitalOcean (4GB) | $48 |
| **Load Balancer** | DigitalOcean LB | $12 |
| **Database** | Managed PostgreSQL (4GB) | $50 |
| **Redis** | Managed Redis (2GB) | $25 |
| **Backups** | Daily | $10 |
| **Monitoring** | Datadog/New Relic | $25 |
| **Total** | | **$170** |

### Free/Low-Cost Alternatives

| Component | Free Option | Limitation |
|-----------|-------------|------------|
| Database | Supabase Free | 500MB, 50K rows |
| Redis | Upstash Free | 10K commands/day |
| Hosting | Railway Free | 500 hours/month |
| Server | Render Free | 750 hours/month |

---

## Cost Optimization Strategies

### 1. Smart Scheduling

Don't scrape everything hourly:

```
# Recommended schedules by source type
Social Media: Every 4 hours (6x/day)
News: Every 2 hours (12x/day)
Reviews: Once daily
Forums: Every 6 hours (4x/day)
```

**Savings: 50-70% vs hourly scraping**

### 2. Targeted Keywords

More specific = less noise = lower costs:

```
❌ Bad: ["brand"]
✅ Good: ["@brandname", "#brand", "brand review", "brand complaints"]
```

### 3. Result Limits

Set reasonable limits per source:

```json
{
  "social_media": {"maxResults": 100},
  "news": {"maxResults": 50},
  "reviews": {"maxResults": 100},
  "forums": {"maxResults": 50}
}
```

### 4. Exclude Low-Value Sources

Skip sources with minimal ROI:

```json
{
  "exclude_sources": [
    "pinterest",      // Low brand mentions
    "ebay",           // Unless you sell there
    "telegram",       // Requires access
    "discord"         // Requires access
  ]
}
```

### 5. Use Custom Actors

Build custom actors for your top 5 sources:

| Your Top Sources | Savings |
|-----------------|---------|
| Twitter | 70% |
| Instagram | 70% |
| Reddit | 60% |
| News sites | 80% |
| Reviews | 60% |

---

## Pricing Scenarios

### Scenario 1: Startup (MVP)

**Profile**: Testing product-market fit

| Component | Choice | Cost |
|-----------|--------|------|
| Apify | Free tier | $0 |
| Claude | Haiku, minimal | $5 |
| Infrastructure | Free tiers | $0 |
| **Total** | | **$5/month** |

**Limitations**:
- ~100 mentions/day max
- Limited sources

### Scenario 2: Small Business

**Profile**: Local business, 1-2 locations

| Component | Choice | Cost |
|-----------|--------|------|
| Apify | Starter ($49) | $49 |
| Claude | Haiku | $10 |
| Infrastructure | Basic | $37 |
| **Total** | | **$96/month** |

**Capacity**:
- ~500 mentions/day
- Core social + reviews

### Scenario 3: Growing Brand

**Profile**: Regional/national brand, active social presence

| Component | Choice | Cost |
|-----------|--------|------|
| Apify | Starter + custom actors | $49 |
| Claude | Haiku | $25 |
| Infrastructure | Recommended | $69 |
| **Total** | | **$143/month** |

**Capacity**:
- ~2000 mentions/day
- All major platforms

### Scenario 4: Enterprise (with Custom Actors)

**Profile**: Large brand, high volume, custom needs

| Component | Choice | Cost |
|-----------|--------|------|
| Apify | Scale + all custom actors | $200 |
| Claude | Haiku + Sonnet for complex | $100 |
| Infrastructure | Production | $170 |
| Custom development | Amortized | $100 |
| **Total** | | **$570/month** |

**Capacity**:
- ~10,000+ mentions/day
- Full platform coverage
- Custom integrations

### Scenario 5: Enterprise (Public Actors Only)

Same as above but without custom actors:

| Component | Choice | Cost |
|-----------|--------|------|
| Apify | Scale | $499 |
| Claude | Haiku + Sonnet | $100 |
| Infrastructure | Production | $170 |
| **Total** | | **$769/month** |

**Custom actors save: $199/month = $2,388/year**

---

## Summary: How to Minimize Costs

### Quick Wins

1. **Use Claude Haiku** instead of Sonnet (-80%)
2. **Reduce scraping frequency** (-50-70%)
3. **Set result limits** (-30-50%)
4. **Target specific keywords** (-20-30%)

### Long-term Savings

1. **Build custom Apify actors** (-70-80% on scraping)
2. **Use free infrastructure tiers** where possible
3. **Monitor and optimize** monthly

### Custom Actor Investment

| Investment | Monthly Savings | Annual Savings | ROI |
|------------|-----------------|----------------|-----|
| $400 (1 actor) | $40-60 | $480-720 | 120-180% |
| $2000 (full suite) | $200-300 | $2400-3600 | 120-180% |

**Recommendation**: Start with public actors, then build custom actors for your top 3-5 sources once you know which platforms matter most.

---

## Resources

- [Apify Templates](https://apify.com/templates) - Start building custom actors
- [Apify Pricing Calculator](https://apify.com/pricing) - Estimate your costs
- [Anthropic Pricing](https://anthropic.com/pricing) - Claude API costs
- [DigitalOcean Calculator](https://www.digitalocean.com/pricing/calculator) - Infrastructure costs

---

## Next Steps

- [Developer Guide](Developer-Guide.md) - Build custom actors
- [Configuration Guide](Configuration.md) - Set up API keys
- [Scraping Sources](Scraping-Sources.md) - Choose which sources to monitor
