"""
Custom Apify Actor Configuration

This module defines custom actors that can be deployed to reduce costs by 70-80%.

To use custom actors:
1. Deploy actors from /actors directory: ./deploy-all.sh
2. Set USE_CUSTOM_ACTORS = True below
3. Update APIFY_USERNAME with your Apify username
"""

# Set to True after deploying custom actors
USE_CUSTOM_ACTORS = False

# Your Apify username (find with: apify info)
APIFY_USERNAME = "your-username"

# Custom actor mappings (source_type -> custom actor name)
CUSTOM_ACTOR_CONFIGS = {
    "twitter": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-twitter-scraper",
        "default_input": {
            "maxTweets": 100,
            "includeReplies": True,
            "language": "en",
        },
        "keyword_field": "searchTerms",
    },
    "instagram": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-instagram-scraper",
        "default_input": {
            "maxPosts": 100,
            "includeComments": False,
        },
        "keyword_field": "hashtags",
    },
    "instagram_posts": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-instagram-scraper",
        "default_input": {
            "maxPosts": 100,
        },
        "keyword_field": "hashtags",
    },
    "reddit": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-reddit-scraper",
        "default_input": {
            "maxPosts": 100,
            "includeComments": True,
            "sortBy": "new",
            "timeFilter": "week",
        },
        "keyword_field": "searchTerms",
    },
    "news": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-news-scraper",
        "default_input": {
            "maxArticles": 50,
            "language": "en",
            "country": "US",
        },
        "keyword_field": "searchTerms",
    },
    "rss_feed": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-news-scraper",
        "default_input": {
            "maxArticles": 100,
        },
        "keyword_field": "rssFeedUrls",
    },
    "trustpilot": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-reviews-scraper",
        "default_input": {
            "maxReviews": 50,
            "sites": ["trustpilot"],
        },
        "keyword_field": "searchTerms",
    },
    "yelp": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-reviews-scraper",
        "default_input": {
            "maxReviews": 50,
            "sites": ["yelp"],
        },
        "keyword_field": "searchTerms",
    },
    "g2": {
        "actor_id": f"{APIFY_USERNAME}/brand-monitor-reviews-scraper",
        "default_input": {
            "maxReviews": 50,
            "sites": ["g2"],
        },
        "keyword_field": "urls",
    },
}


def get_actor_config(source_type: str, public_config: dict) -> dict:
    """
    Get actor configuration, using custom actor if available and enabled.

    Args:
        source_type: The source type (e.g., 'twitter', 'reddit')
        public_config: The default public actor config

    Returns:
        Actor configuration dict
    """
    if USE_CUSTOM_ACTORS and source_type in CUSTOM_ACTOR_CONFIGS:
        return CUSTOM_ACTOR_CONFIGS[source_type]
    return public_config


def is_custom_actor_enabled(source_type: str) -> bool:
    """Check if custom actor is enabled for a source type."""
    return USE_CUSTOM_ACTORS and source_type in CUSTOM_ACTOR_CONFIGS


def get_all_custom_sources() -> list:
    """Get list of source types with custom actors available."""
    return list(CUSTOM_ACTOR_CONFIGS.keys())
