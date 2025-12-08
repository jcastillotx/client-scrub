from __future__ import annotations

import uuid

from sqlalchemy import Column, DateTime, Integer, Numeric, String
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship

from ..core.database import Base


class Client(Base):
    __tablename__ = "clients"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    api_key = Column(String(64), unique=True, nullable=False)
    company_name = Column(String(255), nullable=False)
    email = Column(String(255), nullable=False)
    subscription_tier = Column(String(50), nullable=False)
    monthly_mention_limit = Column(Integer, nullable=False)
    apify_budget_limit = Column(Numeric(10, 2))
    status = Column(String(20), default="active")
    created_at = Column(DateTime)
    updated_at = Column(DateTime)

    scrape_jobs = relationship("ScrapeJob", back_populates="client")
    mentions = relationship("Mention", back_populates="client")
    alerts = relationship("Alert", back_populates="client")
    usage_records = relationship("UsageTracking", back_populates="client")
