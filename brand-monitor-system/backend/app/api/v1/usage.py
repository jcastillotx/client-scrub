from __future__ import annotations

from datetime import date

from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from ...core.database import get_db
from ...models.client import Client
from ...models.usage import UsageTracking
from .auth import verify_api_key

router = APIRouter()


@router.get("/")
def current_month_usage(
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    today = date.today().replace(day=1)
    usage = (
        db.query(UsageTracking)
        .filter(
            UsageTracking.client_id == client.id,
            UsageTracking.month == today,
        )
        .first()
    )

    if not usage:
        return {
            "mentions_processed": 0,
            "apify_credits_used": 0,
            "claude_tokens_used": 0,
        }

    return {
        "mentions_processed": usage.mentions_processed,
        "apify_credits_used": float(usage.apify_credits_used or 0),
        "claude_tokens_used": usage.claude_tokens_used,
    }
