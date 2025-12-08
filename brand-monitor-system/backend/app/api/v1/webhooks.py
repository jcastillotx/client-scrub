from fastapi import APIRouter, HTTPException, Request

from ...core.database import SessionLocal
from ...models.scrape_job import ScrapeJob
from ...scrapers.data_processor import process_apify_dataset

router = APIRouter()


@router.post("/apify/{client_id}")
async def apify_webhook(client_id: str, request: Request):
    """Handle Apify webhook notifications"""

    payload = await request.json()

    # Extract run information
    run_id = payload.get("resource", {}).get("id")
    default_dataset_id = payload.get("resource", {}).get("defaultDatasetId")

    if not run_id or not default_dataset_id:
        raise HTTPException(status_code=400, detail="Invalid webhook payload")

    db = SessionLocal()
    try:
        scrape_job = (
            db.query(ScrapeJob)
            .filter(
                ScrapeJob.apify_run_id == run_id,
            )
            .first()
        )

        if scrape_job:
            scrape_job.status = "processing"
            db.commit()

            mentions = process_apify_dataset(
                db=db,
                scrape_job_id=scrape_job.id,
                dataset_id=default_dataset_id,
            )

            scrape_job.status = "completed"
            scrape_job.mentions_found = len(mentions)
            db.commit()

    finally:
        db.close()

    return {"status": "processed"}
