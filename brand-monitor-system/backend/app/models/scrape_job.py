from __future__ import annotations

import uuid

from sqlalchemy import Column, DateTime, ForeignKey, Integer, Numeric, String, Text
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship

from ..core.database import Base


class ScrapeJob(Base):
    __tablename__ = "scrape_jobs"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    client_id = Column(UUID(as_uuid=True), ForeignKey("clients.id", ondelete="CASCADE"))
    source_id = Column(UUID(as_uuid=True), nullable=True)
    source_type = Column(String(50), nullable=True, index=True)
    apify_run_id = Column(String(100))
    status = Column(String(50), nullable=False)
    started_at = Column(DateTime)
    completed_at = Column(DateTime)
    mentions_found = Column(Integer, default=0)
    apify_credits_used = Column(Numeric(10, 4))
    error_message = Column(Text)
    created_at = Column(DateTime)

    client = relationship("Client", back_populates="scrape_jobs")
    mentions = relationship("Mention", back_populates="scrape_job")
