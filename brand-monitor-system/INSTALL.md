# Installation Guide

## Prerequisites
- Python 3.11+
- PostgreSQL 15
- Redis 7
- Node-compatible WordPress environment (PHP 8.0+, MySQL, etc.)

## Backend Setup
1. Clone this repository and enter the project directory:
   ```
   git clone <repo-url>
   cd brand-monitor-system
   ```
2. Copy the environment template and populate secrets:
   ```
   cd backend
   cp .env.example .env
   # edit .env with database credentials, Redis URL, API tokens
   ```
3. Install Python dependencies:
   ```
   python -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```
4. Start PostgreSQL and Redis services locally or configure remote connection strings in `.env`.
5. Run database migrations (Alembic scripts TBD):
   ```
   alembic upgrade head
   ```
6. Start the FastAPI server:
   ```
   uvicorn main:app --host 0.0.0.0 --port 8000
   ```
7. Start the Celery worker in a separate terminal:
   ```
   celery -A app.tasks worker --loglevel=info
   ```
8. Access the API at `http://localhost:8000`.

## WordPress Plugin Setup
1. Copy `wordpress-plugin/brand-monitor` into your WordPress `wp-content/plugins/` directory.
2. In wp-admin, activate **Brand Monitor** under Plugins.
3. Navigate to Settings → Brand Monitor and enter the backend API URL and API key.
4. Visit the Brand Monitor menu to view dashboards, reports, and sources.

## Post-Installation Checks
- Trigger `/api/v1/scrape/trigger` for a client and confirm Apify webhooks arrive.
- Ensure Redis, PostgreSQL, and Celery worker services are healthy.
- Verify the WordPress dashboard displays mentions and sentiment analytics.
