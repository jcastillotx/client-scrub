from __future__ import annotations

import uuid

from sqlalchemy import Column, Date, ForeignKey, Integer, Numeric
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship

from ..core.database import Base


class UsageTracking(Base):
    __tablename__ = "usage_tracking"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    client_id = Column(UUID(as_uuid=True), ForeignKey("clients.id", ondelete="CASCADE"))
    month = Column(Date, nullable=False)
    mentions_processed = Column(Integer, default=0)
    apify_credits_used = Column(Numeric(10, 2), default=0)
    claude_tokens_used = Column(Integer, default=0)

    client = relationship("Client", back_populates="usage_records")
