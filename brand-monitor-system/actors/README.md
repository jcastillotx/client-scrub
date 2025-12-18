# Brand Monitor - Custom Apify Actors

Custom Apify actors for brand monitoring. These provide **70-80% cost savings** compared to public actors.

## Available Actors

| Actor | Description | Estimated Savings |
|-------|-------------|-------------------|
| `twitter-scraper` | Twitter/X search and mentions | 70-80% |
| `instagram-scraper` | Instagram hashtag scraping | 70-80% |
| `reddit-scraper` | Reddit posts and comments | 60-70% |
| `news-scraper` | Google News and RSS feeds | 80% |
| `reviews-scraper` | Multi-site reviews (Trustpilot, Yelp, G2) | 70% |

## Quick Start

### Prerequisites

1. Install Node.js 18+
2. Install Apify CLI:
   ```bash
   npm install -g apify-cli
   ```
3. Login to Apify:
   ```bash
   apify login
   ```

### Deploy All Actors

```bash
./deploy-all.sh
```

Or deploy individually:

```bash
cd twitter-scraper
npm install
apify push
```

## Usage After Deployment

### Update Backend Configuration

After deploying, update your backend's `apify_orchestrator.py` to use custom actors:

```python
ACTOR_CONFIGS = {
    "twitter": {
        "actor_id": "YOUR_USERNAME/brand-monitor-twitter-scraper",  # Your custom actor
        ...
    },
}
```

### Test via API

```bash
curl -X POST https://api.yourdomain.com/api/v1/scrape/trigger \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "source_type": "twitter",
    "keywords": ["@yourbrand", "your brand"]
  }'
```

## Actor Details

### Twitter Scraper

**Input:**
```json
{
  "searchTerms": ["@brand", "#brand", "brand name"],
  "maxTweets": 100,
  "includeReplies": true,
  "language": "en"
}
```

**Output:**
```json
{
  "url": "https://twitter.com/user/status/123",
  "text": "Tweet content...",
  "author": "Display Name",
  "authorHandle": "username",
  "createdAt": "2024-12-17T10:00:00Z",
  "likeCount": 42,
  "retweetCount": 5,
  "source_type": "twitter"
}
```

### Instagram Scraper

**Input:**
```json
{
  "hashtags": ["yourbrand", "yourproduct"],
  "maxPosts": 100,
  "includeComments": false
}
```

### Reddit Scraper

**Input:**
```json
{
  "searchTerms": ["your brand"],
  "subreddits": ["technology", "gadgets"],
  "maxPosts": 100,
  "includeComments": true,
  "sortBy": "new"
}
```

### News Scraper

**Input:**
```json
{
  "searchTerms": ["Your Company announcement"],
  "rssFeedUrls": ["https://techcrunch.com/feed/"],
  "maxArticles": 50
}
```

### Reviews Scraper

**Input:**
```json
{
  "urls": ["https://www.trustpilot.com/review/yourcompany.com"],
  "searchTerms": ["Your Company"],
  "sites": ["trustpilot", "yelp", "g2"],
  "maxReviews": 50
}
```

## Cost Comparison

| Operation | Public Actor | Custom Actor | Savings |
|-----------|-------------|--------------|---------|
| 100 tweets | ~$1.00 | ~$0.25 | 75% |
| 100 Instagram posts | ~$0.80 | ~$0.20 | 75% |
| 100 Reddit posts | ~$0.30 | ~$0.10 | 67% |
| 50 news articles | ~$0.30 | ~$0.05 | 83% |
| 50 reviews | ~$0.50 | ~$0.15 | 70% |

**Monthly savings for medium brand (1000 mentions/day):**
- Public actors: ~$150-200/month
- Custom actors: ~$40-60/month
- **Savings: $100-140/month ($1200-1680/year)**

## Development

### Local Testing

```bash
cd twitter-scraper
npm install

# Create input.json
echo '{"searchTerms": ["test"]}' > storage/key_value_stores/default/INPUT.json

# Run locally
npm start
```

### Modifying Actors

1. Edit `src/main.js`
2. Test locally with `npm start`
3. Deploy with `apify push`

## Troubleshooting

### Rate Limits

If you hit rate limits:
- Reduce `maxResults` per scrape
- Add delays between requests
- Use proxy rotation (Apify handles this)

### Login Walls

Some sites show login prompts:
- The scrapers try to dismiss these automatically
- If issues persist, reduce scraping frequency

### Missing Data

If fields are empty:
- Check if the site layout changed
- Update selectors in `src/main.js`
- Test with browser DevTools

## License

MIT - Use freely for your brand monitoring needs.
