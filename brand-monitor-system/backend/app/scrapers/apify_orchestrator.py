from __future__ import annotations

from typing import Dict, List
from uuid import UUID

from sqlalchemy.orm import Session

from ..core.apify_client import apify_service
from ..models.scrape_job import ScrapeJob


class ApifyOrchestrator:
    ACTOR_CONFIGS: dict[str, dict] = {
        "google_search": {
            "actor_id": "apify/google-search-scraper",
            "default_input": {
                "maxPagesPerQuery": 3,
                "resultsPerPage": 100,
                "languageCode": "en",
                "countryCode": "us",
            },
        },
        "twitter": {
            "actor_id": "apify/twitter-scraper",
            "default_input": {
                "maxTweets": 100,
                "sort": "Latest",
            },
        },
        "reddit": {
            "actor_id": "apify/reddit-scraper",
            "default_input": {
                "sort": "new",
                "maxResults": 50,
            },
        },
        "web_scraper": {
            "actor_id": "apify/web-scraper",
            "default_input": {
                "maxConcurrency": 5,
            },
        },
        "news": {
            "actor_id": "apify/google-news-scraper",
            "default_input": {
                "maxArticles": 50,
            },
        },
    }

    def trigger_scrape(
        self,
        db: Session,
        client_id: UUID,
        source_type: str,
        keywords: List[str],
        custom_config: Dict | None = None,
    ) -> ScrapeJob:
        """Trigger an Apify actor run for a specific source type"""

        actor_config = self.ACTOR_CONFIGS.get(source_type)
        if not actor_config:
            raise ValueError(f"Unknown source type: {source_type}")

        run_input = {**actor_config["default_input"]}
        if custom_config:
            run_input.update(custom_config)

        if source_type == "google_search":
            run_input["queries"] = keywords
        elif source_type in ["twitter", "reddit"]:
            run_input["searchTerms"] = keywords

        webhook_url = f"https://your-api.com/api/v1/webhooks/apify/{client_id}"
        webhooks = [
            {
                "eventTypes": ["ACTOR.RUN.SUCCEEDED"],
                "requestUrl": webhook_url,
            }
        ]

        scrape_job = ScrapeJob(
            client_id=client_id,
            status="pending",
        )
        db.add(scrape_job)
        db.commit()
        db.refresh(scrape_job)

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


orchestrator = ApifyOrchestrator()
