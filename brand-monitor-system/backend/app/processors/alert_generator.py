from __future__ import annotations

from sqlalchemy.orm import Session

from ..models.alert import Alert


class AlertGenerator:
    """Create alert records based on processed mentions."""

    def create_negative_sentiment_alert(self, db: Session, mention, severity: str = "medium") -> Alert:
        alert = Alert(
            client_id=mention.client_id,
            mention_id=mention.id,
            alert_type="negative_sentiment",
            severity=severity,
            title=f"Negative mention detected for {mention.source_type}",
            description=mention.title or mention.content[:140],
        )
        db.add(alert)
        db.commit()
        db.refresh(alert)
        return alert


default_alert_generator = AlertGenerator()
