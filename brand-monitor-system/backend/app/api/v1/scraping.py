from typing import Dict, List, Optional

from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel
from sqlalchemy.orm import Session

from ...core.database import get_db
from ...models.client import Client
from ...scrapers.apify_orchestrator import ApifyOrchestrator, orchestrator
from .auth import verify_api_key


router = APIRouter()


# ==================== Request/Response Models ====================

class TriggerScrapeRequest(BaseModel):
    source_type: str
    keywords: List[str]
    custom_config: dict | None = None


class MultiSourceScrapeRequest(BaseModel):
    keywords: List[str]
    source_types: List[str] | None = None
    category: str | None = None
    custom_configs: Dict[str, dict] | None = None


class ComprehensiveScanRequest(BaseModel):
    brand_keywords: List[str]
    include_categories: List[str] | None = None
    exclude_sources: List[str] | None = None


class SourceTypeInfo(BaseModel):
    source_type: str
    actor_id: str
    category: str
    keyword_field: str


# ==================== Source Discovery Endpoints ====================

@router.get("/sources")
def list_available_sources():
    """
    List all available scraping sources.

    Returns comprehensive list of all supported platforms including:
    - Social media (Twitter, Instagram, Facebook, LinkedIn, TikTok, YouTube, etc.)
    - Search engines (Google, Bing)
    - News sources (Google News, Bing News, RSS feeds)
    - Review sites (Yelp, Trustpilot, G2, Glassdoor, etc.)
    - Forums (Reddit, Hacker News, Quora, Product Hunt, etc.)
    - E-commerce (Amazon, eBay, Walmart, Etsy)
    - Generic web scrapers
    """
    sources = []
    for source_type, config in ApifyOrchestrator.ACTOR_CONFIGS.items():
        # Find which category this source belongs to
        category = "other"
        for cat_name, cat_sources in ApifyOrchestrator.SOURCE_CATEGORIES.items():
            if source_type in cat_sources:
                category = cat_name
                break

        sources.append({
            "source_type": source_type,
            "actor_id": config["actor_id"],
            "category": category,
            "keyword_field": config.get("keyword_field", "searchTerms"),
            "default_config": config["default_input"],
        })

    return {
        "total_sources": len(sources),
        "sources": sources,
    }


@router.get("/sources/categories")
def list_source_categories():
    """
    List all source categories with their associated sources.

    Categories include:
    - search_engines: Google, Bing
    - social_media: Twitter, Instagram, Facebook, LinkedIn, TikTok, YouTube, Pinterest, Threads, Bluesky, Mastodon
    - forums: Reddit, Hacker News, Quora, Product Hunt, Discord, Telegram, Stack Overflow
    - review_sites: Yelp, Trustpilot, G2, Capterra, Glassdoor, Amazon Reviews, Google Maps, TripAdvisor, App Store, Google Play
    - news: Google News, Bing News, RSS feeds, Medium, Substack
    - ecommerce: Amazon, eBay, Walmart, Etsy, Shopify
    - generic_web: Web Scraper, Cheerio, Playwright, Puppeteer, Website Content Crawler
    """
    categories = {}
    for category, sources in ApifyOrchestrator.SOURCE_CATEGORIES.items():
        categories[category] = {
            "sources": sources,
            "count": len(sources),
        }

    return {
        "total_categories": len(categories),
        "categories": categories,
    }


@router.get("/sources/{category}")
def list_sources_by_category(category: str):
    """Get all sources for a specific category"""
    sources = ApifyOrchestrator.get_sources_by_category(category)
    if not sources:
        raise HTTPException(
            status_code=404,
            detail=f"Category '{category}' not found. Available categories: {ApifyOrchestrator.get_all_categories()}"
        )

    source_details = []
    for source_type in sources:
        config = ApifyOrchestrator.ACTOR_CONFIGS.get(source_type, {})
        source_details.append({
            "source_type": source_type,
            "actor_id": config.get("actor_id"),
            "keyword_field": config.get("keyword_field", "searchTerms"),
        })

    return {
        "category": category,
        "sources": source_details,
        "count": len(source_details),
    }


# ==================== Single Source Scraping ====================

@router.post("/trigger")
def trigger_scrape(
    request: TriggerScrapeRequest,
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    """
    Trigger a scrape job for a single source type.

    Supported source_types include all platforms listed in /sources endpoint.
    Keywords should be appropriate for the source type:
    - Search/social: brand names, hashtags, search terms
    - Review sites with URLs: product/company URLs
    - RSS: feed URLs
    """
    try:
        scrape_job = orchestrator.trigger_scrape(
            db=db,
            client_id=client.id,
            source_type=request.source_type,
            keywords=request.keywords,
            custom_config=request.custom_config,
        )
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    return {
        "scrape_job_id": str(scrape_job.id),
        "source_type": request.source_type,
        "status": scrape_job.status,
        "apify_run_id": scrape_job.apify_run_id,
    }


# ==================== Multi-Source Scraping ====================

@router.post("/trigger/multi")
def trigger_multi_source_scrape(
    request: MultiSourceScrapeRequest,
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    """
    Trigger scrapes across multiple sources simultaneously.

    Either specify source_types directly or use a category name.
    Example categories: 'social_media', 'review_sites', 'forums', 'news'
    """
    try:
        jobs = orchestrator.trigger_multi_source_scrape(
            db=db,
            client_id=client.id,
            keywords=request.keywords,
            source_types=request.source_types,
            category=request.category,
            custom_configs=request.custom_configs,
        )
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    return {
        "jobs_created": len(jobs),
        "jobs": [
            {
                "scrape_job_id": str(job.id),
                "source_type": job.source_type,
                "status": job.status,
                "apify_run_id": job.apify_run_id,
            }
            for job in jobs
        ],
    }


@router.post("/trigger/comprehensive")
def trigger_comprehensive_brand_scan(
    request: ComprehensiveScanRequest,
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    """
    Trigger a comprehensive brand monitoring scan across all relevant sources.

    This will scrape:
    - All major social media platforms
    - Search engines
    - News sources
    - Review sites
    - Forums and communities
    - E-commerce platforms

    Use include_categories to limit to specific categories.
    Use exclude_sources to skip specific source types.
    """
    results = orchestrator.trigger_comprehensive_brand_scan(
        db=db,
        client_id=client.id,
        brand_keywords=request.brand_keywords,
        include_categories=request.include_categories,
        exclude_sources=request.exclude_sources,
    )

    response = {
        "total_jobs": sum(len(jobs) for jobs in results.values()),
        "by_category": {},
    }

    for category, jobs in results.items():
        response["by_category"][category] = {
            "jobs_created": len(jobs),
            "jobs": [
                {
                    "scrape_job_id": str(job.id),
                    "source_type": job.source_type,
                    "status": job.status,
                }
                for job in jobs
            ],
        }

    return response


# ==================== Job Status ====================

@router.get("/status/{scrape_job_id}")
def get_scrape_status(
    scrape_job_id: str,
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    """Get status of a scrape job"""
    from uuid import UUID

    from ...models.scrape_job import ScrapeJob

    scrape_job = (
        db.query(ScrapeJob)
        .filter(
            ScrapeJob.id == UUID(scrape_job_id),
            ScrapeJob.client_id == client.id,
        )
        .first()
    )

    if not scrape_job:
        raise HTTPException(status_code=404, detail="Scrape job not found")

    return {
        "id": str(scrape_job.id),
        "source_type": scrape_job.source_type,
        "status": scrape_job.status,
        "mentions_found": scrape_job.mentions_found,
        "started_at": scrape_job.started_at,
        "completed_at": scrape_job.completed_at,
        "error_message": scrape_job.error_message,
    }


@router.get("/jobs")
def list_scrape_jobs(
    status: Optional[str] = None,
    source_type: Optional[str] = None,
    limit: int = 50,
    offset: int = 0,
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    """List all scrape jobs for the client with optional filters"""
    from ...models.scrape_job import ScrapeJob

    query = db.query(ScrapeJob).filter(ScrapeJob.client_id == client.id)

    if status:
        query = query.filter(ScrapeJob.status == status)
    if source_type:
        query = query.filter(ScrapeJob.source_type == source_type)

    total = query.count()
    jobs = query.order_by(ScrapeJob.created_at.desc()).offset(offset).limit(limit).all()

    return {
        "total": total,
        "limit": limit,
        "offset": offset,
        "jobs": [
            {
                "id": str(job.id),
                "source_type": job.source_type,
                "status": job.status,
                "mentions_found": job.mentions_found,
                "started_at": job.started_at,
                "completed_at": job.completed_at,
            }
            for job in jobs
        ],
    }
