from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from .api.v1 import alerts, analytics, auth, mentions, scraping, usage, webhooks


app = FastAPI(title="Brand Monitor API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(auth.router, prefix="/api/v1/auth", tags=["auth"])
app.include_router(scraping.router, prefix="/api/v1/scrape", tags=["scraping"])
app.include_router(webhooks.router, prefix="/api/v1/webhooks", tags=["webhooks"])
app.include_router(alerts.router, prefix="/api/v1/alerts", tags=["alerts"])
app.include_router(mentions.router, prefix="/api/v1/mentions", tags=["mentions"])
app.include_router(analytics.router, prefix="/api/v1/analytics", tags=["analytics"])
app.include_router(usage.router, prefix="/api/v1/usage", tags=["usage"])


@app.get("/")
def root():
    return {"message": "Brand Monitor API", "version": "1.0.0"}
