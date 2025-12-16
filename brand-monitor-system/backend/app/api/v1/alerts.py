from __future__ import annotations

from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from ...core.database import get_db
from ...models.alert import Alert
from ...models.client import Client
from .auth import verify_api_key

router = APIRouter()


def _serialize_alert(alert: Alert) -> dict:
    return {
        "id": str(alert.id),
        "title": alert.title,
        "severity": alert.severity,
        "alert_type": alert.alert_type,
        "description": alert.description,
        "is_read": alert.is_read,
        "notified_at": alert.notified_at.isoformat() if alert.notified_at else None,
    }


@router.get("/")
def list_alerts(
    client: Client = Depends(verify_api_key),
    db: Session = Depends(get_db),
):
    alerts = (
        db.query(Alert)
        .filter(Alert.client_id == client.id)
        .order_by(Alert.created_at.desc())
        .limit(50)
        .all()
    )
    return [_serialize_alert(alert) for alert in alerts]
