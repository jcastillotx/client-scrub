from __future__ import annotations

from typing import Dict, List
from uuid import UUID

from sqlalchemy.orm import Session

from ..core.apify_client import apify_service
from ..models.scrape_job import ScrapeJob


class ApifyOrchestrator:
    """
    Orchestrates web scraping across multiple platforms via Apify actors.

    Supports comprehensive brand monitoring across:
    - Social Media: Twitter, Instagram, Facebook, LinkedIn, TikTok, YouTube, Pinterest
    - Search Engines: Google Search, Bing Search
    - News: Google News, Bing News, RSS Feeds
    - Review Sites: Yelp, Trustpilot, G2, Glassdoor, Amazon Reviews, Google Maps
    - Forums & Communities: Reddit, Hacker News, Quora, Product Hunt
    - Generic: Web Scraper for custom websites
    """

    ACTOR_CONFIGS: dict[str, dict] = {
        # ==================== SEARCH ENGINES ====================
        "google_search": {
            "actor_id": "apify/google-search-scraper",
            "default_input": {
                "maxPagesPerQuery": 3,
                "resultsPerPage": 100,
                "languageCode": "en",
                "countryCode": "us",
            },
            "keyword_field": "queries",
        },
        "bing_search": {
            "actor_id": "apify/bing-search-scraper",
            "default_input": {
                "maxResults": 100,
                "market": "en-US",
            },
            "keyword_field": "queries",
        },

        # ==================== SOCIAL MEDIA ====================
        "twitter": {
            "actor_id": "apify/twitter-scraper",
            "default_input": {
                "maxTweets": 100,
                "sort": "Latest",
                "includeReplies": True,
                "includeRetweets": False,
            },
            "keyword_field": "searchTerms",
        },
        "instagram": {
            "actor_id": "apify/instagram-scraper",
            "default_input": {
                "resultsLimit": 100,
                "searchType": "hashtag",
            },
            "keyword_field": "search",
        },
        "instagram_posts": {
            "actor_id": "apify/instagram-post-scraper",
            "default_input": {
                "resultsLimit": 100,
            },
            "keyword_field": "hashtags",
        },
        "instagram_comments": {
            "actor_id": "apify/instagram-comment-scraper",
            "default_input": {
                "resultsLimit": 200,
            },
            "keyword_field": "directUrls",
        },
        "facebook": {
            "actor_id": "apify/facebook-scraper",
            "default_input": {
                "resultsLimit": 100,
            },
            "keyword_field": "searchTerms",
        },
        "facebook_posts": {
            "actor_id": "apify/facebook-posts-scraper",
            "default_input": {
                "resultsLimit": 100,
            },
            "keyword_field": "startUrls",
        },
        "facebook_comments": {
            "actor_id": "apify/facebook-comments-scraper",
            "default_input": {
                "resultsLimit": 200,
            },
            "keyword_field": "startUrls",
        },
        "linkedin": {
            "actor_id": "apify/linkedin-scraper",
            "default_input": {
                "resultsLimit": 50,
            },
            "keyword_field": "searchTerms",
        },
        "linkedin_posts": {
            "actor_id": "apify/linkedin-posts-scraper",
            "default_input": {
                "resultsLimit": 100,
            },
            "keyword_field": "searchTerms",
        },
        "linkedin_company": {
            "actor_id": "apify/linkedin-company-scraper",
            "default_input": {
                "resultsLimit": 50,
            },
            "keyword_field": "companyUrls",
        },
        "tiktok": {
            "actor_id": "apify/tiktok-scraper",
            "default_input": {
                "resultsPerPage": 100,
                "searchSection": "video",
            },
            "keyword_field": "searchQueries",
        },
        "tiktok_hashtag": {
            "actor_id": "apify/tiktok-hashtag-scraper",
            "default_input": {
                "resultsLimit": 100,
            },
            "keyword_field": "hashtags",
        },
        "tiktok_comments": {
            "actor_id": "apify/tiktok-comments-scraper",
            "default_input": {
                "resultsLimit": 200,
            },
            "keyword_field": "postURLs",
        },
        "youtube": {
            "actor_id": "apify/youtube-scraper",
            "default_input": {
                "maxResults": 100,
                "searchType": "video",
            },
            "keyword_field": "searchKeywords",
        },
        "youtube_comments": {
            "actor_id": "apify/youtube-comment-scraper",
            "default_input": {
                "maxComments": 200,
            },
            "keyword_field": "startUrls",
        },
        "youtube_channel": {
            "actor_id": "apify/youtube-channel-scraper",
            "default_input": {
                "maxResults": 50,
            },
            "keyword_field": "channelUrls",
        },
        "pinterest": {
            "actor_id": "apify/pinterest-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "searchTerms",
        },
        "threads": {
            "actor_id": "apify/threads-scraper",
            "default_input": {
                "resultsLimit": 100,
            },
            "keyword_field": "searchTerms",
        },
        "bluesky": {
            "actor_id": "apify/bluesky-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "searchTerms",
        },
        "mastodon": {
            "actor_id": "apify/mastodon-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "searchTerms",
        },

        # ==================== FORUMS & COMMUNITIES ====================
        "reddit": {
            "actor_id": "apify/reddit-scraper",
            "default_input": {
                "sort": "new",
                "maxResults": 50,
                "includeComments": True,
            },
            "keyword_field": "searchTerms",
        },
        "reddit_comments": {
            "actor_id": "apify/reddit-comments-scraper",
            "default_input": {
                "maxComments": 200,
            },
            "keyword_field": "postUrls",
        },
        "hacker_news": {
            "actor_id": "apify/hacker-news-scraper",
            "default_input": {
                "maxResults": 100,
                "includeComments": True,
            },
            "keyword_field": "searchTerms",
        },
        "quora": {
            "actor_id": "apify/quora-scraper",
            "default_input": {
                "maxResults": 50,
            },
            "keyword_field": "searchTerms",
        },
        "product_hunt": {
            "actor_id": "apify/product-hunt-scraper",
            "default_input": {
                "maxResults": 50,
            },
            "keyword_field": "searchTerms",
        },
        "discord": {
            "actor_id": "apify/discord-scraper",
            "default_input": {
                "maxMessages": 200,
            },
            "keyword_field": "channelUrls",
        },
        "telegram": {
            "actor_id": "apify/telegram-scraper",
            "default_input": {
                "maxMessages": 200,
            },
            "keyword_field": "channelUrls",
        },
        "stackoverflow": {
            "actor_id": "apify/stackoverflow-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "searchTerms",
        },

        # ==================== REVIEW SITES ====================
        "yelp": {
            "actor_id": "apify/yelp-scraper",
            "default_input": {
                "maxResults": 100,
                "includeReviews": True,
            },
            "keyword_field": "searchTerms",
        },
        "trustpilot": {
            "actor_id": "apify/trustpilot-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "searchTerms",
        },
        "g2": {
            "actor_id": "apify/g2-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "productUrls",
        },
        "capterra": {
            "actor_id": "apify/capterra-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "productUrls",
        },
        "glassdoor": {
            "actor_id": "apify/glassdoor-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "companyUrls",
        },
        "indeed_reviews": {
            "actor_id": "apify/indeed-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "companyUrls",
        },
        "amazon_reviews": {
            "actor_id": "apify/amazon-reviews-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "productUrls",
        },
        "amazon_search": {
            "actor_id": "apify/amazon-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "searchTerms",
        },
        "google_maps": {
            "actor_id": "apify/google-maps-scraper",
            "default_input": {
                "maxReviews": 100,
                "includeReviews": True,
            },
            "keyword_field": "searchTerms",
        },
        "google_reviews": {
            "actor_id": "apify/google-reviews-scraper",
            "default_input": {
                "maxReviews": 200,
            },
            "keyword_field": "placeUrls",
        },
        "tripadvisor": {
            "actor_id": "apify/tripadvisor-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "searchTerms",
        },
        "app_store": {
            "actor_id": "apify/app-store-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "appUrls",
        },
        "google_play": {
            "actor_id": "apify/google-play-scraper",
            "default_input": {
                "maxReviews": 100,
            },
            "keyword_field": "appUrls",
        },

        # ==================== NEWS & MEDIA ====================
        "news": {
            "actor_id": "apify/google-news-scraper",
            "default_input": {
                "maxArticles": 50,
                "language": "en",
            },
            "keyword_field": "searchTerms",
        },
        "bing_news": {
            "actor_id": "apify/bing-news-scraper",
            "default_input": {
                "maxResults": 50,
            },
            "keyword_field": "searchTerms",
        },
        "rss_feed": {
            "actor_id": "apify/rss-feed-scraper",
            "default_input": {
                "maxItems": 100,
            },
            "keyword_field": "feedUrls",
        },
        "news_api": {
            "actor_id": "apify/news-api-scraper",
            "default_input": {
                "maxArticles": 100,
            },
            "keyword_field": "searchTerms",
        },
        "medium": {
            "actor_id": "apify/medium-scraper",
            "default_input": {
                "maxResults": 50,
            },
            "keyword_field": "searchTerms",
        },
        "substack": {
            "actor_id": "apify/substack-scraper",
            "default_input": {
                "maxResults": 50,
            },
            "keyword_field": "searchTerms",
        },

        # ==================== E-COMMERCE ====================
        "ebay": {
            "actor_id": "apify/ebay-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "searchTerms",
        },
        "walmart": {
            "actor_id": "apify/walmart-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "searchTerms",
        },
        "etsy": {
            "actor_id": "apify/etsy-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "searchTerms",
        },
        "shopify": {
            "actor_id": "apify/shopify-scraper",
            "default_input": {
                "maxResults": 100,
            },
            "keyword_field": "storeUrls",
        },

        # ==================== GENERIC WEB ====================
        "web_scraper": {
            "actor_id": "apify/web-scraper",
            "default_input": {
                "maxConcurrency": 5,
                "maxPagesPerCrawl": 100,
            },
            "keyword_field": "startUrls",
        },
        "cheerio_scraper": {
            "actor_id": "apify/cheerio-scraper",
            "default_input": {
                "maxConcurrency": 10,
                "maxPagesPerCrawl": 100,
            },
            "keyword_field": "startUrls",
        },
        "playwright_scraper": {
            "actor_id": "apify/playwright-scraper",
            "default_input": {
                "maxConcurrency": 5,
                "maxPagesPerCrawl": 50,
            },
            "keyword_field": "startUrls",
        },
        "puppeteer_scraper": {
            "actor_id": "apify/puppeteer-scraper",
            "default_input": {
                "maxConcurrency": 5,
                "maxPagesPerCrawl": 50,
            },
            "keyword_field": "startUrls",
        },
        "website_content_crawler": {
            "actor_id": "apify/website-content-crawler",
            "default_input": {
                "maxCrawlPages": 100,
            },
            "keyword_field": "startUrls",
        },
    }

    # Source type categories for easier management
    SOURCE_CATEGORIES = {
        "search_engines": ["google_search", "bing_search"],
        "social_media": [
            "twitter", "instagram", "instagram_posts", "instagram_comments",
            "facebook", "facebook_posts", "facebook_comments",
            "linkedin", "linkedin_posts", "linkedin_company",
            "tiktok", "tiktok_hashtag", "tiktok_comments",
            "youtube", "youtube_comments", "youtube_channel",
            "pinterest", "threads", "bluesky", "mastodon"
        ],
        "forums": [
            "reddit", "reddit_comments", "hacker_news", "quora",
            "product_hunt", "discord", "telegram", "stackoverflow"
        ],
        "review_sites": [
            "yelp", "trustpilot", "g2", "capterra", "glassdoor",
            "indeed_reviews", "amazon_reviews", "google_maps",
            "google_reviews", "tripadvisor", "app_store", "google_play"
        ],
        "news": [
            "news", "bing_news", "rss_feed", "news_api", "medium", "substack"
        ],
        "ecommerce": ["amazon_search", "ebay", "walmart", "etsy", "shopify"],
        "generic_web": [
            "web_scraper", "cheerio_scraper", "playwright_scraper",
            "puppeteer_scraper", "website_content_crawler"
        ],
    }

    @classmethod
    def get_all_source_types(cls) -> List[str]:
        """Return all supported source types"""
        return list(cls.ACTOR_CONFIGS.keys())

    @classmethod
    def get_sources_by_category(cls, category: str) -> List[str]:
        """Return source types for a specific category"""
        return cls.SOURCE_CATEGORIES.get(category, [])

    @classmethod
    def get_all_categories(cls) -> List[str]:
        """Return all available categories"""
        return list(cls.SOURCE_CATEGORIES.keys())

    def trigger_scrape(
        self,
        db: Session,
        client_id: UUID,
        source_type: str,
        keywords: List[str],
        custom_config: Dict | None = None,
        webhook_base_url: str | None = None,
    ) -> ScrapeJob:
        """
        Trigger an Apify actor run for a specific source type.

        Args:
            db: Database session
            client_id: Client UUID for tracking
            source_type: One of the supported source types (e.g., 'twitter', 'instagram')
            keywords: List of search terms, URLs, or hashtags depending on source type
            custom_config: Optional configuration to override defaults
            webhook_base_url: Base URL for webhooks (defaults to configured URL)

        Returns:
            ScrapeJob: The created scrape job with Apify run ID
        """
        actor_config = self.ACTOR_CONFIGS.get(source_type)
        if not actor_config:
            available = ", ".join(self.get_all_source_types())
            raise ValueError(
                f"Unknown source type: {source_type}. Available types: {available}"
            )

        run_input = {**actor_config["default_input"]}
        if custom_config:
            run_input.update(custom_config)

        # Use the keyword_field from config to set the search terms
        keyword_field = actor_config.get("keyword_field", "searchTerms")
        run_input[keyword_field] = keywords

        # Configure webhook for async result handling
        base_url = webhook_base_url or "https://your-api.com"
        webhook_url = f"{base_url}/api/v1/webhooks/apify/{client_id}"
        webhooks = [
            {
                "eventTypes": ["ACTOR.RUN.SUCCEEDED", "ACTOR.RUN.FAILED"],
                "requestUrl": webhook_url,
            }
        ]

        # Create the scrape job record
        scrape_job = ScrapeJob(
            client_id=client_id,
            status="pending",
            source_type=source_type,
        )
        db.add(scrape_job)
        db.commit()
        db.refresh(scrape_job)

        # Trigger the Apify actor
        run = apify_service.run_actor(
            actor_id=actor_config["actor_id"],
            run_input=run_input,
            webhooks=webhooks,
        )

        scrape_job.apify_run_id = run["id"]
        scrape_job.status = "running"
        db.commit()
        db.refresh(scrape_job)

        return scrape_job

    def trigger_multi_source_scrape(
        self,
        db: Session,
        client_id: UUID,
        keywords: List[str],
        source_types: List[str] | None = None,
        category: str | None = None,
        custom_configs: Dict[str, Dict] | None = None,
        webhook_base_url: str | None = None,
    ) -> List[ScrapeJob]:
        """
        Trigger scrapes across multiple sources simultaneously.

        Args:
            db: Database session
            client_id: Client UUID
            keywords: Search terms to use
            source_types: Specific source types to scrape (optional)
            category: Category of sources to scrape (e.g., 'social_media', 'review_sites')
            custom_configs: Dict mapping source_type to custom config
            webhook_base_url: Base URL for webhooks

        Returns:
            List of created ScrapeJob instances
        """
        if source_types is None and category is None:
            raise ValueError("Must specify either source_types or category")

        if category:
            source_types = self.get_sources_by_category(category)

        if not source_types:
            raise ValueError(f"No sources found for category: {category}")

        custom_configs = custom_configs or {}
        jobs = []

        for source_type in source_types:
            try:
                job = self.trigger_scrape(
                    db=db,
                    client_id=client_id,
                    source_type=source_type,
                    keywords=keywords,
                    custom_config=custom_configs.get(source_type),
                    webhook_base_url=webhook_base_url,
                )
                jobs.append(job)
            except Exception as e:
                # Log error but continue with other sources
                print(f"Failed to trigger {source_type}: {e}")

        return jobs

    def trigger_comprehensive_brand_scan(
        self,
        db: Session,
        client_id: UUID,
        brand_keywords: List[str],
        include_categories: List[str] | None = None,
        exclude_sources: List[str] | None = None,
        webhook_base_url: str | None = None,
    ) -> Dict[str, List[ScrapeJob]]:
        """
        Trigger a comprehensive brand monitoring scan across all relevant sources.

        Args:
            db: Database session
            client_id: Client UUID
            brand_keywords: Brand names and related keywords to monitor
            include_categories: Categories to include (defaults to all)
            exclude_sources: Specific sources to exclude
            webhook_base_url: Base URL for webhooks

        Returns:
            Dict mapping category names to lists of ScrapeJobs
        """
        include_categories = include_categories or list(self.SOURCE_CATEGORIES.keys())
        exclude_sources = exclude_sources or []

        results = {}

        for category in include_categories:
            sources = [
                s for s in self.get_sources_by_category(category)
                if s not in exclude_sources
            ]

            if sources:
                jobs = self.trigger_multi_source_scrape(
                    db=db,
                    client_id=client_id,
                    keywords=brand_keywords,
                    source_types=sources,
                    webhook_base_url=webhook_base_url,
                )
                results[category] = jobs

        return results


orchestrator = ApifyOrchestrator()
