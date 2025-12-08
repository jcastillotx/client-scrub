from __future__ import annotations

from fastapi import APIRouter, Depends
from sqlalchemy import func
from sqlalchemy.orm import Session

from ...core.database import get_db
from ...models.client import Client
from ...models.mention import Mention
from .auth import verify_api_key

router = APIRouter()


@router.get("/sentiment")
def sentiment_overview(
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    sentiment_counts = (
        db.query(Mention.sentiment, func.count(Mention.id))
        .filter(Mention.client_id == client.id)
        .group_by(Mention.sentiment)
        .all()
    )

    total_mentions = sum(count for _, count in sentiment_counts) or 1
    aggregated = {sentiment or "unknown": count for sentiment, count in sentiment_counts}
    positive = aggregated.get("positive", 0)
    negative = aggregated.get("negative", 0)
    neutral = aggregated.get("neutral", 0)

    average_score = (
        db.query(func.avg(Mention.sentiment_score))
        .filter(Mention.client_id == client.id)
        .scalar()
    )

    return {
        "average_score": float(average_score or 0),
        "distribution": {
            "positive": positive,
            "negative": negative,
            "neutral": neutral,
        },
        "total_mentions": total_mentions,
    }


@router.get("/sources")
def source_breakdown(
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    breakdown = (
        db.query(Mention.source_type, func.count(Mention.id))
        .filter(Mention.client_id == client.id)
        .group_by(Mention.source_type)
        .all()
    )

    return {source or "unknown": count for source, count in breakdown}
