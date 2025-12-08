from __future__ import annotations

from typing import List, Optional

from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session

from ...core.database import get_db
from ...models.mention import Mention
from ...models.client import Client
from .auth import verify_api_key

router = APIRouter()


def _serialize_mention(mention: Mention) -> dict:
    return {
        "id": str(mention.id),
        "source_type": mention.source_type,
        "source_url": mention.source_url,
        "title": mention.title,
        "sentiment": mention.sentiment,
        "sentiment_score": float(mention.sentiment_score or 0),
        "discovered_at": mention.discovered_at.isoformat() if mention.discovered_at else None,
    }


@router.get("/")
def list_mentions(
    limit: int = Query(50, ge=1, le=200),
    offset: int = Query(0, ge=0),
    sentiment: Optional[str] = None,
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    query = db.query(Mention).filter(Mention.client_id == client.id)

    if sentiment:
        query = query.filter(Mention.sentiment == sentiment)

    total = query.count()
    mentions: List[Mention] = (
        query.order_by(Mention.discovered_at.desc())
        .offset(offset)
        .limit(limit)
        .all()
    )

    return {
        "total": total,
        "data": [_serialize_mention(m) for m in mentions],
    }
