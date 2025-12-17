# Scraping Sources Guide

Brand Monitor supports **50+ data sources** across multiple categories. This guide explains each source and how to use them effectively.

## Table of Contents

1. [Source Categories](#source-categories)
2. [Social Media Sources](#social-media-sources)
3. [Review Sites](#review-sites)
4. [Forums & Communities](#forums--communities)
5. [News & Media](#news--media)
6. [E-commerce](#e-commerce)
7. [Search Engines](#search-engines)
8. [Generic Web Scrapers](#generic-web-scrapers)
9. [API Usage Examples](#api-usage-examples)

---

## Source Categories

| Category | Sources | Best For |
|----------|---------|----------|
| `social_media` | 21 sources | Brand mentions, sentiment, trends |
| `review_sites` | 12 sources | Product/company reviews, ratings |
| `forums` | 8 sources | Community discussions, feedback |
| `news` | 6 sources | Press coverage, media mentions |
| `ecommerce` | 5 sources | Product listings, competitors |
| `search_engines` | 2 sources | General web presence |
| `generic_web` | 5 sources | Custom website monitoring |

---

## Social Media Sources

### Twitter/X

| Source Type | Description | Keyword Field |
|------------|-------------|---------------|
| `twitter` | Search tweets | `searchTerms` |

**Example:**
```json
{
  "source_type": "twitter",
  "keywords": ["@yourbrand", "#yourbrand", "your brand name"]
}
```

**Best practices:**
- Use @mentions to find direct references
- Use hashtags for campaign tracking
- Monitor competitor mentions

### Instagram

| Source Type | Description | Keyword Field |
|------------|-------------|---------------|
| `instagram` | Search hashtags/accounts | `search` |
| `instagram_posts` | Get posts by hashtag | `hashtags` |
| `instagram_comments` | Get post comments | `directUrls` |

**Example:**
```json
{
  "source_type": "instagram",
  "keywords": ["yourbrand", "yourproduct"]
}
```

### Facebook

| Source Type | Description | Keyword Field |
|------------|-------------|---------------|
| `facebook` | Search public content | `searchTerms` |
| `facebook_posts` | Scrape page posts | `startUrls` |
| `facebook_comments` | Get post comments | `startUrls` |

**Note:** Limited to public pages and groups.

### LinkedIn

| Source Type | Description | Keyword Field |
|------------|-------------|---------------|
| `linkedin` | Search content | `searchTerms` |
| `linkedin_posts` | Get posts | `searchTerms` |
| `linkedin_company` | Company profiles | `companyUrls` |

**Example:**
```json
{
  "source_type": "linkedin_company",
  "keywords": ["https://linkedin.com/company/your-company"]
}
```

### TikTok

| Source Type | Description | Keyword Field |
|------------|-------------|---------------|
| `tiktok` | Search videos | `searchQueries` |
| `tiktok_hashtag` | Videos by hashtag | `hashtags` |
| `tiktok_comments` | Video comments | `postURLs` |

**Example:**
```json
{
  "source_type": "tiktok_hashtag",
  "keywords": ["yourbrand", "yourproduct"]
}
```

### YouTube

| Source Type | Description | Keyword Field |
|------------|-------------|---------------|
| `youtube` | Search videos | `searchKeywords` |
| `youtube_comments` | Video comments | `startUrls` |
| `youtube_channel` | Channel content | `channelUrls` |

**Example:**
```json
{
  "source_type": "youtube",
  "keywords": ["your brand review", "your product unboxing"]
}
```

### Pinterest

| Source Type | Description | Keyword Field |
|------------|-------------|---------------|
| `pinterest` | Search pins | `searchTerms` |

### Other Social Platforms

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `threads` | Threads (Meta) | `searchTerms` |
| `bluesky` | Bluesky | `searchTerms` |
| `mastodon` | Mastodon | `searchTerms` |

---

## Review Sites

### Business Reviews

| Source Type | Platform | Keyword Field | Returns |
|------------|----------|---------------|---------|
| `yelp` | Yelp | `searchTerms` | Businesses + reviews |
| `google_maps` | Google Maps | `searchTerms` | Places + reviews |
| `google_reviews` | Google Reviews | `placeUrls` | Reviews only |
| `tripadvisor` | TripAdvisor | `searchTerms` | Locations + reviews |

**Example - Monitor your business reviews:**
```json
{
  "source_type": "google_reviews",
  "keywords": ["https://maps.google.com/your-business-url"]
}
```

### Software Reviews

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `trustpilot` | Trustpilot | `searchTerms` |
| `g2` | G2 Crowd | `productUrls` |
| `capterra` | Capterra | `productUrls` |

**Example - Monitor software reviews:**
```json
{
  "source_type": "g2",
  "keywords": ["https://www.g2.com/products/your-product"]
}
```

### Employment Reviews

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `glassdoor` | Glassdoor | `companyUrls` |
| `indeed_reviews` | Indeed | `companyUrls` |

**Example - Monitor employer brand:**
```json
{
  "source_type": "glassdoor",
  "keywords": ["https://www.glassdoor.com/your-company"]
}
```

### Product Reviews

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `amazon_reviews` | Amazon | `productUrls` |
| `app_store` | Apple App Store | `appUrls` |
| `google_play` | Google Play Store | `appUrls` |

**Example - Monitor app reviews:**
```json
{
  "source_type": "app_store",
  "keywords": ["https://apps.apple.com/app/your-app"]
}
```

---

## Forums & Communities

### General Forums

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `reddit` | Reddit | `searchTerms` |
| `reddit_comments` | Reddit Comments | `postUrls` |
| `hacker_news` | Hacker News | `searchTerms` |
| `quora` | Quora | `searchTerms` |
| `stackoverflow` | Stack Overflow | `searchTerms` |

**Example - Monitor Reddit discussions:**
```json
{
  "source_type": "reddit",
  "keywords": ["your brand", "your product"],
  "custom_config": {
    "sort": "new",
    "includeComments": true
  }
}
```

### Product Communities

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `product_hunt` | Product Hunt | `searchTerms` |

### Chat Platforms

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `discord` | Discord | `channelUrls` |
| `telegram` | Telegram | `channelUrls` |

**Note:** Requires channel URLs you have access to.

---

## News & Media

### News Aggregators

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `news` | Google News | `searchTerms` |
| `bing_news` | Bing News | `searchTerms` |
| `news_api` | News API | `searchTerms` |

**Example - Monitor press coverage:**
```json
{
  "source_type": "news",
  "keywords": ["\"Your Company\" announcement", "Your Company funding"]
}
```

### Blog Platforms

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `medium` | Medium | `searchTerms` |
| `substack` | Substack | `searchTerms` |

### RSS Feeds

| Source Type | Description | Keyword Field |
|------------|-------------|---------------|
| `rss_feed` | Custom RSS feeds | `feedUrls` |

**Example - Monitor industry blogs:**
```json
{
  "source_type": "rss_feed",
  "keywords": [
    "https://techcrunch.com/feed/",
    "https://blog.yourindustry.com/rss"
  ]
}
```

---

## E-commerce

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `amazon_search` | Amazon | `searchTerms` |
| `ebay` | eBay | `searchTerms` |
| `walmart` | Walmart | `searchTerms` |
| `etsy` | Etsy | `searchTerms` |
| `shopify` | Shopify stores | `storeUrls` |

**Example - Monitor competitor products:**
```json
{
  "source_type": "amazon_search",
  "keywords": ["competitor product name"]
}
```

---

## Search Engines

| Source Type | Platform | Keyword Field |
|------------|----------|---------------|
| `google_search` | Google | `queries` |
| `bing_search` | Bing | `queries` |

**Example - Monitor web presence:**
```json
{
  "source_type": "google_search",
  "keywords": ["\"Your Brand\" review", "Your Brand complaints"]
}
```

---

## Generic Web Scrapers

For custom websites not covered by specific scrapers:

| Source Type | Best For | Keyword Field |
|------------|----------|---------------|
| `web_scraper` | General websites | `startUrls` |
| `cheerio_scraper` | Fast, static pages | `startUrls` |
| `playwright_scraper` | JavaScript-heavy sites | `startUrls` |
| `puppeteer_scraper` | Complex interactions | `startUrls` |
| `website_content_crawler` | Full site crawls | `startUrls` |

**Example - Scrape competitor website:**
```json
{
  "source_type": "website_content_crawler",
  "keywords": ["https://competitor.com/blog"]
}
```

---

## API Usage Examples

### Single Source Scrape

```bash
curl -X POST https://api.yourdomain.com/api/v1/scrape/trigger \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "source_type": "twitter",
    "keywords": ["@yourbrand", "your brand"]
  }'
```

### Multi-Source Scrape by Category

```bash
curl -X POST https://api.yourdomain.com/api/v1/scrape/trigger/multi \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "keywords": ["your brand"],
    "category": "social_media"
  }'
```

### Comprehensive Brand Scan

```bash
curl -X POST https://api.yourdomain.com/api/v1/scrape/trigger/comprehensive \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "brand_keywords": ["Your Brand", "Your Product"],
    "include_categories": ["social_media", "news", "review_sites"],
    "exclude_sources": ["discord", "telegram"]
  }'
```

### List Available Sources

```bash
curl https://api.yourdomain.com/api/v1/scrape/sources \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### List Sources by Category

```bash
curl https://api.yourdomain.com/api/v1/scrape/sources/social_media \
  -H "Authorization: Bearer YOUR_API_KEY"
```

---

## Recommended Monitoring Strategy

### For B2C Brands

```json
{
  "brand_keywords": ["Your Brand", "@yourbrand"],
  "include_categories": [
    "social_media",
    "review_sites",
    "news",
    "ecommerce"
  ]
}
```

### For B2B/SaaS Companies

```json
{
  "brand_keywords": ["Your Product", "your-product.com"],
  "include_categories": [
    "social_media",
    "review_sites",
    "forums",
    "news"
  ],
  "priority_sources": [
    "twitter",
    "linkedin",
    "g2",
    "trustpilot",
    "hacker_news",
    "product_hunt"
  ]
}
```

### For Local Businesses

```json
{
  "brand_keywords": ["Your Business Name"],
  "include_categories": [
    "review_sites"
  ],
  "priority_sources": [
    "google_maps",
    "google_reviews",
    "yelp",
    "tripadvisor",
    "facebook"
  ]
}
```

### For Mobile Apps

```json
{
  "keywords": ["Your App Name"],
  "source_types": [
    "app_store",
    "google_play",
    "twitter",
    "reddit",
    "product_hunt"
  ]
}
```

---

## Cost Optimization Tips

1. **Start with priority sources** - Focus on where your audience is
2. **Use category-based scraping** - More efficient than individual calls
3. **Set reasonable limits** - Adjust `maxResults` based on needs
4. **Schedule strategically** - Social media: hourly, Reviews: daily, News: every 4 hours
5. **Exclude unnecessary sources** - Use `exclude_sources` to skip irrelevant platforms

---

## Next Steps

- [View API Reference](API-Reference.md)
- [Explore the Dashboard](Dashboard-Guide.md)
- [Estimate Costs](Cost-Estimation.md)
