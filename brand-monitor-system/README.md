# Brand Monitor System

This repository contains the FastAPI backend, data processors, and WordPress plugin required for the Brand Monitor platform. The goal is to orchestrate Apify actors, store mentions in PostgreSQL, analyze them with Claude via the Anthropic API, and surface the insights inside WordPress.

## Technology Stack

- FastAPI (Python 3.11+)
- PostgreSQL 15
- Redis 7
- Celery with Redis broker
- Apify API + Actors
- Anthropic Claude Sonnet 4.5
- WordPress plugin with PHP 8.0+ and React-ready admin assets

## Repository Structure

```
brand-monitor-system/
  backend/
    app/
      api/v1/
      core/
      processors/
      scrapers/
      models/
      main.py
    requirements.txt
    .env.example
    main.py
  wordpress-plugin/
    brand-monitor/
      admin/
      assets/
      includes/
      templates/
      brand-monitor.php
      uninstall.php
      readme.txt
```

## Database Schema Overview

Key tables include `clients`, `brand_keywords`, `monitoring_sources`, `scrape_jobs`, `mentions`, `alerts`, `alert_configs`, and `usage_tracking`. See `app/models` for SQLAlchemy models mirroring the PostgreSQL schema (UUID primary keys with `gen_random_uuid()` defaults, JSONB config fields, and denormalized indices for analytics).

## Backend Highlights

- `app/core` handles configuration, database sessions, security helpers, and the Apify client wrapper.
- `app/scrapers` contains the Apify orchestrator plus dataset processing utilities.
- `app/processors` integrates with Anthropic for sentiment analysis and leaves room for entity extraction, deduplication, and alerting logic.
- `app/api/v1` exposes routers for authentication, scraping, webhooks, mentions, analytics, and usage tracking.

## WordPress Plugin

Located in `wordpress-plugin/brand-monitor`. Key components:

- `brand-monitor.php` bootstraps the plugin, registers activation/deactivation hooks, and loads admin pages.
- `admin/` screens: dashboard, reports, settings, and source configuration.
- `includes/` contains the REST client, database helper, notifications, widgets, and scheduler utilities.
- `assets/` bundles CSS/JS for dashboards, charts, and notifications.
- `templates/` hosts email and dashboard widget markup.

## Development Workflow

1. `cd backend && python -m venv venv && source venv/bin/activate`
2. `pip install -r requirements.txt`
3. `cp .env.example .env` and update secrets.
4. Start PostgreSQL and Redis services locally or configure remote connection strings in `.env`.
5. `alembic upgrade head` (migrations TBD).
6. `uvicorn main:app --reload`
7. Start Celery worker: `celery -A app.tasks worker --loglevel=info`
8. Copy `wordpress-plugin/brand-monitor` into `wp-content/plugins/`, activate it, and configure API credentials.
9. Trigger Apify scrapes, verify webhooks, run sentiment analysis, and test WordPress data sync.
