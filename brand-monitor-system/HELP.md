# Help & Troubleshooting

## Common Issues
### Webhooks Not Updating Mentions
- Confirm Apify webhook URL matches the public endpoint (`/api/v1/webhooks/apify/{client_id}`).
- Check backend logs for JSON parsing errors.
- Ensure the Celery worker is running if you move dataset processing off the main thread.

### WordPress Dashboard Shows "Missing API Key"
- Settings → Brand Monitor must contain both the API URL and API key.
- Verify the API key is active in the backend `clients` table.

### Redis or PostgreSQL Connection Errors
- Validate `DATABASE_URL` and `REDIS_URL` inside `backend/.env`.
- Restart the backend, PostgreSQL, and Redis services.

### Sentiment Scores Always Zero
- Confirm `ANTHROPIC_API_KEY` is set and valid.
- Inspect Celery/worker logs to make sure the sentiment analyzer runs.

## Support Workflow
1. Reproduce the issue locally by starting the backend services.
2. Check FastAPI logs and Celery worker logs for errors.
3. Use FastAPI's interactive docs at `http://localhost:8000/docs` to test endpoints manually.
4. For WordPress-specific issues, enable `WP_DEBUG` and inspect `wp-content/debug.log`.

## Useful Commands
- `uvicorn main:app --reload` – start FastAPI server.
- `celery -A app.tasks worker --loglevel=info` – start Celery worker.
- `pytest` – run backend tests (once added).
- `wp cron event list` – verify scheduled events from the plugin.

## Contact
If internal troubleshooting fails, escalate with:
- API logs
- WordPress debug logs
- Steps to reproduce
- Environment details (WP/PHP versions, PostgreSQL/Redis versions)
