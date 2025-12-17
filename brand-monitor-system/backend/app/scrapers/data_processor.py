from __future__ import annotations

from datetime import datetime
from typing import Any, Dict, List, Optional
import hashlib

from sqlalchemy.orm import Session

from ..core.apify_client import apify_service
from ..models.mention import Mention
from ..models.scrape_job import ScrapeJob


# Field mappings for different source types
# Maps source_type -> field names for extraction
SOURCE_FIELD_MAPPINGS: Dict[str, Dict[str, List[str]]] = {
    # Search engines
    "google_search": {
        "url": ["url", "link"],
        "title": ["title", "displayedUrl"],
        "content": ["description", "snippet", "text"],
        "author": ["author", "source"],
        "published_at": ["publishedAt", "date"],
    },
    "bing_search": {
        "url": ["url", "link"],
        "title": ["title", "name"],
        "content": ["description", "snippet"],
        "author": ["source"],
        "published_at": ["datePublished", "date"],
    },

    # Social media - Twitter/X
    "twitter": {
        "url": ["url", "tweetUrl", "link"],
        "title": ["title"],
        "content": ["text", "fullText", "content", "tweet"],
        "author": ["author", "username", "user", "screenName", "handle"],
        "published_at": ["createdAt", "timestamp", "date", "publishedAt"],
        "engagement": ["retweetCount", "likeCount", "replyCount", "viewCount"],
    },

    # Instagram
    "instagram": {
        "url": ["url", "postUrl", "link"],
        "title": ["title", "caption"],
        "content": ["caption", "text", "description"],
        "author": ["ownerUsername", "username", "author", "owner"],
        "published_at": ["timestamp", "takenAt", "createdAt", "publishedAt"],
        "engagement": ["likesCount", "commentsCount", "viewsCount"],
    },
    "instagram_posts": {
        "url": ["url", "postUrl"],
        "title": ["caption"],
        "content": ["caption", "text"],
        "author": ["ownerUsername", "username"],
        "published_at": ["timestamp", "takenAt"],
        "engagement": ["likesCount", "commentsCount"],
    },
    "instagram_comments": {
        "url": ["postUrl", "url"],
        "title": [],
        "content": ["text", "comment"],
        "author": ["ownerUsername", "username", "author"],
        "published_at": ["timestamp", "createdAt"],
    },

    # Facebook
    "facebook": {
        "url": ["url", "postUrl", "link"],
        "title": ["title"],
        "content": ["text", "message", "content", "description"],
        "author": ["pageName", "authorName", "author", "username"],
        "published_at": ["time", "timestamp", "createdAt", "publishedAt"],
        "engagement": ["likes", "comments", "shares", "reactions"],
    },
    "facebook_posts": {
        "url": ["url", "postUrl"],
        "title": ["title"],
        "content": ["text", "message"],
        "author": ["pageName", "authorName"],
        "published_at": ["time", "timestamp"],
    },
    "facebook_comments": {
        "url": ["postUrl", "url"],
        "title": [],
        "content": ["text", "comment", "message"],
        "author": ["authorName", "author"],
        "published_at": ["timestamp", "createdAt"],
    },

    # LinkedIn
    "linkedin": {
        "url": ["url", "postUrl", "link"],
        "title": ["title"],
        "content": ["text", "content", "description"],
        "author": ["authorName", "author", "companyName"],
        "published_at": ["postedDate", "timestamp", "publishedAt"],
        "engagement": ["numLikes", "numComments", "numShares"],
    },
    "linkedin_posts": {
        "url": ["url", "postUrl"],
        "title": ["title"],
        "content": ["text", "content"],
        "author": ["authorName", "author"],
        "published_at": ["postedDate", "timestamp"],
    },
    "linkedin_company": {
        "url": ["url", "companyUrl"],
        "title": ["name", "companyName"],
        "content": ["description", "about"],
        "author": [],
        "published_at": [],
    },

    # TikTok
    "tiktok": {
        "url": ["webVideoUrl", "url", "videoUrl"],
        "title": ["title", "desc"],
        "content": ["text", "desc", "description"],
        "author": ["authorMeta.name", "authorName", "author", "uniqueId"],
        "published_at": ["createTime", "timestamp", "createdAt"],
        "engagement": ["diggCount", "shareCount", "commentCount", "playCount"],
    },
    "tiktok_hashtag": {
        "url": ["webVideoUrl", "url"],
        "title": ["title", "desc"],
        "content": ["desc", "text"],
        "author": ["authorMeta.name", "author"],
        "published_at": ["createTime", "timestamp"],
    },
    "tiktok_comments": {
        "url": ["videoUrl", "url"],
        "title": [],
        "content": ["text", "comment"],
        "author": ["uniqueId", "author", "username"],
        "published_at": ["createTime", "timestamp"],
    },

    # YouTube
    "youtube": {
        "url": ["url", "videoUrl", "link"],
        "title": ["title", "name"],
        "content": ["description", "text"],
        "author": ["channelName", "channelTitle", "author"],
        "published_at": ["publishedAt", "uploadDate", "date"],
        "engagement": ["viewCount", "likeCount", "commentCount"],
    },
    "youtube_comments": {
        "url": ["videoUrl", "url"],
        "title": [],
        "content": ["text", "comment", "textDisplay"],
        "author": ["authorDisplayName", "author", "channelName"],
        "published_at": ["publishedAt", "timestamp"],
    },
    "youtube_channel": {
        "url": ["url", "channelUrl"],
        "title": ["title", "channelName"],
        "content": ["description", "about"],
        "author": ["channelName"],
        "published_at": ["publishedAt"],
    },

    # Pinterest
    "pinterest": {
        "url": ["url", "pinUrl", "link"],
        "title": ["title", "name"],
        "content": ["description", "note", "text"],
        "author": ["pinner", "creator", "author"],
        "published_at": ["createdAt", "timestamp"],
        "engagement": ["saveCount", "commentCount"],
    },

    # Threads, Bluesky, Mastodon
    "threads": {
        "url": ["url", "postUrl"],
        "title": [],
        "content": ["text", "content"],
        "author": ["username", "author"],
        "published_at": ["timestamp", "createdAt"],
    },
    "bluesky": {
        "url": ["url", "uri"],
        "title": [],
        "content": ["text", "content"],
        "author": ["author", "handle", "displayName"],
        "published_at": ["createdAt", "indexedAt"],
    },
    "mastodon": {
        "url": ["url", "uri"],
        "title": [],
        "content": ["content", "text"],
        "author": ["account.username", "author"],
        "published_at": ["createdAt", "publishedAt"],
    },

    # Forums
    "reddit": {
        "url": ["url", "permalink", "link"],
        "title": ["title"],
        "content": ["text", "selftext", "body"],
        "author": ["author", "username"],
        "published_at": ["createdAt", "created_utc", "timestamp"],
        "engagement": ["score", "upvotes", "numComments"],
    },
    "reddit_comments": {
        "url": ["url", "permalink"],
        "title": [],
        "content": ["body", "text", "comment"],
        "author": ["author"],
        "published_at": ["createdAt", "created_utc"],
    },
    "hacker_news": {
        "url": ["url", "link"],
        "title": ["title"],
        "content": ["text", "content"],
        "author": ["author", "by", "username"],
        "published_at": ["time", "timestamp", "createdAt"],
        "engagement": ["points", "score", "numComments"],
    },
    "quora": {
        "url": ["url", "questionUrl", "link"],
        "title": ["question", "title"],
        "content": ["answer", "text", "content"],
        "author": ["author", "authorName"],
        "published_at": ["timestamp", "createdAt"],
    },
    "product_hunt": {
        "url": ["url", "link"],
        "title": ["name", "title"],
        "content": ["tagline", "description", "text"],
        "author": ["maker", "author", "hunterName"],
        "published_at": ["featuredAt", "createdAt", "timestamp"],
        "engagement": ["votesCount", "commentsCount"],
    },
    "discord": {
        "url": ["url", "messageUrl"],
        "title": ["channelName"],
        "content": ["content", "text", "message"],
        "author": ["author", "username"],
        "published_at": ["timestamp", "createdAt"],
    },
    "telegram": {
        "url": ["url", "messageUrl"],
        "title": ["channelName", "chatTitle"],
        "content": ["text", "message", "content"],
        "author": ["author", "senderName"],
        "published_at": ["date", "timestamp"],
    },
    "stackoverflow": {
        "url": ["url", "link"],
        "title": ["title", "question"],
        "content": ["body", "text", "answer"],
        "author": ["owner.display_name", "author", "username"],
        "published_at": ["creation_date", "createdAt", "timestamp"],
        "engagement": ["score", "view_count", "answer_count"],
    },

    # Review sites
    "yelp": {
        "url": ["url", "reviewUrl", "businessUrl"],
        "title": ["businessName", "name"],
        "content": ["text", "reviewText", "content"],
        "author": ["userName", "author", "reviewer"],
        "published_at": ["date", "timestamp", "publishedAt"],
        "rating": ["rating", "stars"],
    },
    "trustpilot": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "companyName"],
        "content": ["text", "reviewText", "content"],
        "author": ["consumerName", "author", "reviewer"],
        "published_at": ["createdAt", "date", "publishedAt"],
        "rating": ["rating", "stars"],
    },
    "g2": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "productName"],
        "content": ["text", "reviewText", "pros", "cons"],
        "author": ["reviewerName", "author"],
        "published_at": ["submittedAt", "date", "timestamp"],
        "rating": ["rating", "overallRating"],
    },
    "capterra": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "productName"],
        "content": ["text", "reviewText", "pros", "cons"],
        "author": ["reviewerName", "author"],
        "published_at": ["date", "timestamp"],
        "rating": ["rating", "overallRating"],
    },
    "glassdoor": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "headline", "companyName"],
        "content": ["text", "pros", "cons", "reviewText"],
        "author": ["author", "jobTitle"],
        "published_at": ["date", "timestamp", "reviewDateTime"],
        "rating": ["rating", "overallRating"],
    },
    "indeed_reviews": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "companyName"],
        "content": ["text", "reviewText", "pros", "cons"],
        "author": ["author", "jobTitle"],
        "published_at": ["date", "timestamp"],
        "rating": ["rating", "overallRating"],
    },
    "amazon_reviews": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "reviewTitle"],
        "content": ["text", "reviewText", "body"],
        "author": ["authorName", "author", "reviewer"],
        "published_at": ["date", "timestamp", "reviewDate"],
        "rating": ["rating", "stars"],
    },
    "amazon_search": {
        "url": ["url", "productUrl"],
        "title": ["title", "name"],
        "content": ["description"],
        "author": ["brand", "seller"],
        "published_at": [],
        "rating": ["rating", "stars"],
    },
    "google_maps": {
        "url": ["url", "placeUrl"],
        "title": ["title", "name", "placeName"],
        "content": ["text", "reviewText", "snippet"],
        "author": ["authorName", "author", "reviewer"],
        "published_at": ["publishedAtDate", "timestamp"],
        "rating": ["rating", "stars"],
    },
    "google_reviews": {
        "url": ["url", "reviewUrl"],
        "title": ["placeName", "businessName"],
        "content": ["text", "reviewText"],
        "author": ["authorName", "author"],
        "published_at": ["publishedAtDate", "timestamp"],
        "rating": ["rating", "stars"],
    },
    "tripadvisor": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "locationName"],
        "content": ["text", "reviewText"],
        "author": ["username", "author"],
        "published_at": ["publishedDate", "timestamp"],
        "rating": ["rating", "bubbleRating"],
    },
    "app_store": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "appName"],
        "content": ["text", "review", "content"],
        "author": ["userName", "author"],
        "published_at": ["updated", "date", "timestamp"],
        "rating": ["rating", "score"],
    },
    "google_play": {
        "url": ["url", "reviewUrl"],
        "title": ["title", "appName"],
        "content": ["text", "content", "review"],
        "author": ["userName", "author"],
        "published_at": ["date", "timestamp"],
        "rating": ["score", "rating"],
    },

    # News & Media
    "news": {
        "url": ["url", "link"],
        "title": ["title", "headline"],
        "content": ["text", "description", "snippet", "content"],
        "author": ["source", "author", "publisher"],
        "published_at": ["publishedAt", "date", "timestamp"],
    },
    "bing_news": {
        "url": ["url", "link"],
        "title": ["title", "name"],
        "content": ["description", "snippet"],
        "author": ["provider", "source"],
        "published_at": ["datePublished", "date"],
    },
    "rss_feed": {
        "url": ["url", "link", "guid"],
        "title": ["title"],
        "content": ["content", "description", "summary"],
        "author": ["author", "creator", "dc:creator"],
        "published_at": ["pubDate", "published", "date"],
    },
    "news_api": {
        "url": ["url", "link"],
        "title": ["title"],
        "content": ["content", "description"],
        "author": ["author", "source.name"],
        "published_at": ["publishedAt", "date"],
    },
    "medium": {
        "url": ["url", "link"],
        "title": ["title"],
        "content": ["content", "subtitle", "text"],
        "author": ["author", "creator", "username"],
        "published_at": ["publishedAt", "createdAt", "firstPublishedAt"],
        "engagement": ["clapCount", "responseCount"],
    },
    "substack": {
        "url": ["url", "link"],
        "title": ["title"],
        "content": ["subtitle", "description", "text"],
        "author": ["author", "publication"],
        "published_at": ["publishedAt", "date"],
    },

    # E-commerce
    "ebay": {
        "url": ["url", "itemUrl", "link"],
        "title": ["title", "name"],
        "content": ["description"],
        "author": ["seller", "sellerName"],
        "published_at": ["listingDate"],
        "rating": ["rating", "feedback"],
    },
    "walmart": {
        "url": ["url", "productUrl"],
        "title": ["title", "name"],
        "content": ["description", "shortDescription"],
        "author": ["brand", "seller"],
        "published_at": [],
        "rating": ["rating", "averageRating"],
    },
    "etsy": {
        "url": ["url", "listingUrl"],
        "title": ["title", "name"],
        "content": ["description"],
        "author": ["shopName", "seller"],
        "published_at": ["originalCreation"],
        "rating": ["rating", "reviewAverage"],
    },
    "shopify": {
        "url": ["url", "productUrl"],
        "title": ["title", "name"],
        "content": ["description", "bodyHtml"],
        "author": ["vendor", "storeName"],
        "published_at": ["createdAt", "publishedAt"],
    },

    # Generic web scrapers
    "web_scraper": {
        "url": ["url", "pageUrl", "link"],
        "title": ["title", "pageTitle"],
        "content": ["text", "content", "body"],
        "author": ["author"],
        "published_at": ["date", "timestamp"],
    },
    "cheerio_scraper": {
        "url": ["url", "pageUrl"],
        "title": ["title"],
        "content": ["text", "content", "body"],
        "author": ["author"],
        "published_at": ["date"],
    },
    "playwright_scraper": {
        "url": ["url", "pageUrl"],
        "title": ["title"],
        "content": ["text", "content"],
        "author": ["author"],
        "published_at": ["date"],
    },
    "puppeteer_scraper": {
        "url": ["url", "pageUrl"],
        "title": ["title"],
        "content": ["text", "content"],
        "author": ["author"],
        "published_at": ["date"],
    },
    "website_content_crawler": {
        "url": ["url", "pageUrl"],
        "title": ["title"],
        "content": ["text", "markdown", "content"],
        "author": [],
        "published_at": [],
    },
}

# Default fallback mapping for unknown source types
DEFAULT_FIELD_MAPPING = {
    "url": ["url", "link", "href", "pageUrl"],
    "title": ["title", "name", "headline"],
    "content": ["text", "content", "description", "body", "message"],
    "author": ["author", "username", "user", "creator"],
    "published_at": ["publishedAt", "createdAt", "timestamp", "date", "time"],
}


def _get_nested_value(data: Dict, key: str) -> Any:
    """Get a value from nested dict using dot notation (e.g., 'author.name')"""
    keys = key.split(".")
    value = data
    for k in keys:
        if isinstance(value, dict):
            value = value.get(k)
        else:
            return None
    return value


def _extract_field(raw: Dict, field_names: List[str]) -> Optional[str]:
    """Extract a field value trying multiple possible field names"""
    for field_name in field_names:
        value = _get_nested_value(raw, field_name)
        if value is not None and value != "":
            if isinstance(value, list):
                # Join list items (e.g., for pros/cons)
                return " ".join(str(v) for v in value if v)
            return str(value)
    return None


def _generate_content_hash(url: str, content: str) -> str:
    """Generate a hash for deduplication"""
    combined = f"{url}:{content[:500] if content else ''}"
    return hashlib.sha256(combined.encode()).hexdigest()


def process_apify_dataset(
    db: Session,
    scrape_job_id,
    dataset_id: str,
    source_type: str | None = None,
) -> list[Mention]:
    """
    Persist mentions from an Apify dataset with source-aware field mapping.

    Args:
        db: Database session
        scrape_job_id: ID of the scrape job
        dataset_id: Apify dataset ID to fetch
        source_type: Type of source (e.g., 'twitter', 'instagram') for proper field mapping

    Returns:
        List of created Mention objects
    """
    dataset_items = apify_service.get_dataset_items(dataset_id)
    scrape_job: ScrapeJob | None = (
        db.query(ScrapeJob).filter(ScrapeJob.id == scrape_job_id).first()
    )
    client_id = scrape_job.client_id if scrape_job else None

    # Get source type from scrape job if not provided
    if source_type is None and scrape_job:
        source_type = getattr(scrape_job, "source_type", None)

    # Get field mapping for this source type
    field_mapping = SOURCE_FIELD_MAPPINGS.get(source_type or "", DEFAULT_FIELD_MAPPING)

    mentions: list[Mention] = []
    seen_hashes: set[str] = set()

    for raw in dataset_items:
        # Extract fields using source-specific mapping
        source_url = _extract_field(raw, field_mapping.get("url", DEFAULT_FIELD_MAPPING["url"])) or ""
        title = _extract_field(raw, field_mapping.get("title", DEFAULT_FIELD_MAPPING["title"]))
        content = _extract_field(raw, field_mapping.get("content", DEFAULT_FIELD_MAPPING["content"])) or ""
        author = _extract_field(raw, field_mapping.get("author", DEFAULT_FIELD_MAPPING["author"]))
        published_at_str = _extract_field(raw, field_mapping.get("published_at", DEFAULT_FIELD_MAPPING["published_at"]))

        # Extract additional metadata if available
        rating = None
        if "rating" in field_mapping:
            rating_str = _extract_field(raw, field_mapping["rating"])
            if rating_str:
                try:
                    rating = float(rating_str)
                except (ValueError, TypeError):
                    pass

        # Skip empty mentions
        if not source_url and not content:
            continue

        # Deduplication within this batch
        content_hash = _generate_content_hash(source_url, content)
        if content_hash in seen_hashes:
            continue
        seen_hashes.add(content_hash)

        mention = Mention(
            scrape_job_id=scrape_job_id,
            client_id=client_id,
            source_type=source_type or raw.get("source_type", "web"),
            source_url=source_url,
            title=title,
            content=content,
            author=author,
            published_at=_parse_datetime(published_at_str),
            discovered_at=datetime.utcnow(),
            raw_data=raw,
        )
        db.add(mention)
        mentions.append(mention)

    db.commit()

    return mentions


def process_multi_source_results(
    db: Session,
    results: Dict[str, Any],
) -> Dict[str, List[Mention]]:
    """
    Process results from multiple scrape jobs.

    Args:
        db: Database session
        results: Dict mapping scrape_job_id to (dataset_id, source_type) tuples

    Returns:
        Dict mapping source_type to list of Mentions
    """
    all_mentions = {}

    for scrape_job_id, (dataset_id, source_type) in results.items():
        mentions = process_apify_dataset(
            db=db,
            scrape_job_id=scrape_job_id,
            dataset_id=dataset_id,
            source_type=source_type,
        )
        if source_type not in all_mentions:
            all_mentions[source_type] = []
        all_mentions[source_type].extend(mentions)

    return all_mentions


def _parse_datetime(value: Any) -> Optional[datetime]:
    """Parse datetime from various formats"""
    if not value:
        return None
    if isinstance(value, datetime):
        return value
    if isinstance(value, (int, float)):
        # Unix timestamp
        try:
            return datetime.fromtimestamp(value)
        except (ValueError, OSError):
            return None
    try:
        # ISO format
        return datetime.fromisoformat(str(value).replace("Z", "+00:00"))
    except ValueError:
        pass
    # Try common date formats
    date_formats = [
        "%Y-%m-%d %H:%M:%S",
        "%Y-%m-%dT%H:%M:%S",
        "%Y-%m-%d",
        "%B %d, %Y",
        "%b %d, %Y",
        "%d/%m/%Y",
        "%m/%d/%Y",
    ]
    for fmt in date_formats:
        try:
            return datetime.strptime(str(value), fmt)
        except ValueError:
            continue
    return None
