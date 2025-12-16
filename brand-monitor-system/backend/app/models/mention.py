from __future__ import annotations

import uuid

from sqlalchemy import Boolean, Column, DateTime, ForeignKey, Numeric, String, Text
from sqlalchemy.dialects.postgresql import JSONB, UUID
from sqlalchemy.orm import relationship

from ..core.database import Base


class Mention(Base):
    __tablename__ = "mentions"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    client_id = Column(UUID(as_uuid=True), ForeignKey("clients.id", ondelete="CASCADE"))
    scrape_job_id = Column(UUID(as_uuid=True), ForeignKey("scrape_jobs.id"))
    source_type = Column(String(50), nullable=False)
    source_url = Column(Text, nullable=False)
    title = Column(Text)
    content = Column(Text, nullable=False)
    author = Column(String(255))
    published_at = Column(DateTime)
    discovered_at = Column(DateTime)
    sentiment = Column(String(20))
    sentiment_score = Column(Numeric(3, 2))
    confidence_score = Column(Numeric(3, 2))
    entities = Column(JSONB)
    is_duplicate = Column(Boolean, default=False)
    duplicate_of = Column(UUID(as_uuid=True), ForeignKey("mentions.id"))
    screenshot_url = Column(Text)
    raw_data = Column(JSONB)
    created_at = Column(DateTime)

    client = relationship("Client", back_populates="mentions")
    scrape_job = relationship("ScrapeJob", back_populates="mentions")
