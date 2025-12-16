from typing import List

from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel
from sqlalchemy.orm import Session

from ...core.database import get_db
from ...models.client import Client
from ...scrapers.apify_orchestrator import orchestrator
from .auth import verify_api_key


router = APIRouter()


class TriggerScrapeRequest(BaseModel):
    source_type: str
    keywords: List[str]
    custom_config: dict | None = None


@router.post("/trigger")
def trigger_scrape(
    request: TriggerScrapeRequest,
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    """Manually trigger a scrape job"""

    scrape_job = orchestrator.trigger_scrape(
        db=db,
        client_id=client.id,
        source_type=request.source_type,
        keywords=request.keywords,
        custom_config=request.custom_config,
    )

    return {
        "scrape_job_id": str(scrape_job.id),
        "status": scrape_job.status,
        "apify_run_id": scrape_job.apify_run_id,
    }


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
        "status": scrape_job.status,
        "mentions_found": scrape_job.mentions_found,
        "started_at": scrape_job.started_at,
        "completed_at": scrape_job.completed_at,
    }
