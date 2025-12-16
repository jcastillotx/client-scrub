from __future__ import annotations

import os

from celery import Celery


celery_app = Celery(
    "brand_monitor",
    broker=os.getenv("REDIS_URL", "redis://localhost:6379/0"),
    backend=os.getenv("REDIS_URL", "redis://localhost:6379/0"),
)


@celery_app.task
def process_dataset_task(scrape_job_id: str, dataset_id: str) -> None:
    """Placeholder Celery task to process Apify datasets asynchronously."""
    # The FastAPI webhook currently processes synchronously.
    # This task is defined so Celery workers can be wired up later.
    return None
