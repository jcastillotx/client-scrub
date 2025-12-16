from __future__ import annotations

from datetime import datetime
from typing import Any

from sqlalchemy.orm import Session

from ..core.apify_client import apify_service
from ..models.mention import Mention
from ..models.scrape_job import ScrapeJob


def process_apify_dataset(db: Session, scrape_job_id, dataset_id: str) -> list[Mention]:
    """Persist mentions from an Apify dataset."""

    dataset_items = apify_service.get_dataset_items(dataset_id)
    scrape_job: ScrapeJob | None = (
        db.query(ScrapeJob).filter(ScrapeJob.id == scrape_job_id).first()
    )
    client_id = scrape_job.client_id if scrape_job else None

    mentions: list[Mention] = []

    for raw in dataset_items:
        mention = Mention(
            scrape_job_id=scrape_job_id,
            client_id=client_id,
            source_type=raw.get("source_type", "web"),
            source_url=raw.get("url") or raw.get("link", ""),
            title=raw.get("title"),
            content=raw.get("text") or raw.get("content", ""),
            author=raw.get("author"),
            published_at=_parse_datetime(raw.get("publishedAt")),
            discovered_at=datetime.utcnow(),
            raw_data=raw,
        )
        db.add(mention)
        mentions.append(mention)

    db.commit()

    return mentions


def _parse_datetime(value: Any):
    if not value:
        return None
    if isinstance(value, datetime):
        return value
    try:
        return datetime.fromisoformat(value)
    except ValueError:
        return None
