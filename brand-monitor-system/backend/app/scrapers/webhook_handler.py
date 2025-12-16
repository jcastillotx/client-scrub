from __future__ import annotations

from sqlalchemy.orm import Session

from ..models.scrape_job import ScrapeJob


def update_scrape_status(db: Session, run_id: str, status: str) -> None:
    """Helper used by webhook endpoints to set scrape run status."""
    scrape_job = db.query(ScrapeJob).filter(ScrapeJob.apify_run_id == run_id).first()
    if not scrape_job:
        return

    scrape_job.status = status
    db.commit()
