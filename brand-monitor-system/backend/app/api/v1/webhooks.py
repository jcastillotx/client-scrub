from datetime import datetime

from fastapi import APIRouter, HTTPException, Request

from ...core.database import SessionLocal
from ...models.scrape_job import ScrapeJob
from ...scrapers.data_processor import process_apify_dataset

router = APIRouter()


@router.post("/apify/{client_id}")
async def apify_webhook(client_id: str, request: Request):
    """
    Handle Apify webhook notifications for all source types.

    Processes completed scrape jobs from:
    - Social media (Twitter, Instagram, Facebook, LinkedIn, TikTok, YouTube, etc.)
    - Search engines (Google, Bing)
    - News sources
    - Review sites
    - Forums and communities
    - E-commerce platforms
    - Generic web scrapers
    """
    payload = await request.json()

    # Extract run information
    resource = payload.get("resource", {})
    run_id = resource.get("id")
    default_dataset_id = resource.get("defaultDatasetId")
    event_type = payload.get("eventType", "")
    status = resource.get("status", "")

    if not run_id:
        raise HTTPException(status_code=400, detail="Invalid webhook payload: missing run_id")

    db = SessionLocal()
    try:
        scrape_job = (
            db.query(ScrapeJob)
            .filter(ScrapeJob.apify_run_id == run_id)
            .first()
        )

        if not scrape_job:
            return {"status": "ignored", "reason": "scrape_job not found"}

        # Handle failed runs
        if event_type == "ACTOR.RUN.FAILED" or status == "FAILED":
            scrape_job.status = "failed"
            scrape_job.completed_at = datetime.utcnow()
            scrape_job.error_message = resource.get("exitCode", "Unknown error")
            db.commit()
            return {"status": "failed", "scrape_job_id": str(scrape_job.id)}

        # Handle successful runs
        if not default_dataset_id:
            scrape_job.status = "completed"
            scrape_job.completed_at = datetime.utcnow()
            scrape_job.mentions_found = 0
            db.commit()
            return {"status": "completed", "mentions": 0}

        scrape_job.status = "processing"
        db.commit()

        # Process dataset with source-aware field mapping
        mentions = process_apify_dataset(
            db=db,
            scrape_job_id=scrape_job.id,
            dataset_id=default_dataset_id,
            source_type=scrape_job.source_type,
        )

        # Update job status
        scrape_job.status = "completed"
        scrape_job.completed_at = datetime.utcnow()
        scrape_job.mentions_found = len(mentions)

        # Extract Apify credits used if available
        usage = resource.get("usage", {})
        if usage:
            credits_used = usage.get("ACTOR_COMPUTE_UNITS", 0)
            scrape_job.apify_credits_used = credits_used

        db.commit()

        return {
            "status": "processed",
            "scrape_job_id": str(scrape_job.id),
            "source_type": scrape_job.source_type,
            "mentions_processed": len(mentions),
        }

    except Exception as e:
        # Log error and mark job as failed
        if scrape_job:
            scrape_job.status = "failed"
            scrape_job.error_message = str(e)
            scrape_job.completed_at = datetime.utcnow()
            db.commit()
        raise HTTPException(status_code=500, detail=f"Processing error: {str(e)}")

    finally:
        db.close()
