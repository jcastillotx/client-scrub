# Installation Guide

This guide covers installing both the WordPress plugin and the backend API server.

## Table of Contents

1. [WordPress Plugin Installation](#wordpress-plugin-installation)
2. [Backend Server Setup](#backend-server-setup)
3. [Database Setup](#database-setup)
4. [Verifying Installation](#verifying-installation)

---

## WordPress Plugin Installation

### Method 1: Upload via WordPress Admin

1. Download the `brand-monitor.zip` file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the zip file and click **Install Now**
6. Click **Activate Plugin**

### Method 2: Manual Upload via FTP

1. Extract `brand-monitor.zip` on your computer
2. Connect to your server via FTP/SFTP
3. Upload the `brand-monitor` folder to `/wp-content/plugins/`
4. Log in to WordPress admin
5. Navigate to **Plugins**
6. Find "Brand Monitor" and click **Activate**

### Method 3: WP-CLI

```bash
# Upload and activate
wp plugin install /path/to/brand-monitor.zip --activate

# Or from a URL
wp plugin install https://example.com/brand-monitor.zip --activate
```

---

## Backend Server Setup

### Prerequisites

- Python 3.11 or higher
- PostgreSQL 15 or higher
- Redis 7 or higher
- Apify account with API token
- Anthropic API key

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-repo/brand-monitor-system.git
cd brand-monitor-system/backend
```

### Step 2: Create Virtual Environment

```bash
python -m venv venv
source venv/bin/activate  # Linux/macOS
# or
venv\Scripts\activate     # Windows
```

### Step 3: Install Dependencies

```bash
pip install -r requirements.txt
```

### Step 4: Configure Environment Variables

Create a `.env` file in the `backend` directory:

```bash
# Copy the example file
cp .env.example .env

# Edit with your values
nano .env
```

**Required Environment Variables:**

```env
# Database
DATABASE_URL=postgresql://username:password@localhost:5432/brand_monitor

# Redis (for Celery task queue)
REDIS_URL=redis://localhost:6379/0

# Apify - All web scraping (get from apify.com)
APIFY_API_TOKEN=apify_api_xxxxxxxxxxxxxxxxxxxxx

# Anthropic - Sentiment analysis (get from console.anthropic.com)
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxxx

# Application Security
SECRET_KEY=your-secure-random-secret-key-here

# Environment
ENVIRONMENT=production

# Optional: Webhook URL (your public API URL)
WEBHOOK_BASE_URL=https://api.yourdomain.com
```

### Step 5: Initialize the Database

```bash
# Create database tables
python -c "from app.core.database import Base, engine; Base.metadata.create_all(bind=engine)"
```

### Step 6: Create Initial Client

```bash
# Run the API server first
uvicorn app.main:app --host 0.0.0.0 --port 8000

# In another terminal, create a client via API or database
python scripts/create_client.py --name "Your Company" --email "admin@example.com"
```

### Step 7: Start the Services

**Development:**

```bash
# Terminal 1: API Server
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000

# Terminal 2: Celery Worker (for background tasks)
celery -A app.tasks worker --loglevel=info
```

**Production (with systemd):**

Create `/etc/systemd/system/brand-monitor-api.service`:

```ini
[Unit]
Description=Brand Monitor API
After=network.target

[Service]
User=www-data
WorkingDirectory=/opt/brand-monitor/backend
Environment="PATH=/opt/brand-monitor/venv/bin"
ExecStart=/opt/brand-monitor/venv/bin/uvicorn app.main:app --host 0.0.0.0 --port 8000
Restart=always

[Install]
WantedBy=multi-user.target
```

Create `/etc/systemd/system/brand-monitor-worker.service`:

```ini
[Unit]
Description=Brand Monitor Celery Worker
After=network.target redis.service

[Service]
User=www-data
WorkingDirectory=/opt/brand-monitor/backend
Environment="PATH=/opt/brand-monitor/venv/bin"
ExecStart=/opt/brand-monitor/venv/bin/celery -A app.tasks worker --loglevel=info
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable brand-monitor-api brand-monitor-worker
sudo systemctl start brand-monitor-api brand-monitor-worker
```

---

## Database Setup

### PostgreSQL Installation

**Ubuntu/Debian:**

```bash
sudo apt update
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

**macOS (Homebrew):**

```bash
brew install postgresql@15
brew services start postgresql@15
```

### Create Database and User

```bash
sudo -u postgres psql

-- In PostgreSQL shell:
CREATE USER brand_monitor WITH PASSWORD 'your_secure_password';
CREATE DATABASE brand_monitor OWNER brand_monitor;
GRANT ALL PRIVILEGES ON DATABASE brand_monitor TO brand_monitor;
\q
```

### Redis Installation

**Ubuntu/Debian:**

```bash
sudo apt install redis-server
sudo systemctl start redis
sudo systemctl enable redis
```

**macOS:**

```bash
brew install redis
brew services start redis
```

**Verify Redis:**

```bash
redis-cli ping
# Should return: PONG
```

---

## Verifying Installation

### 1. Check Backend API

```bash
# Health check
curl http://localhost:8000/health

# Should return: {"status": "healthy"}
```

### 2. Check API Documentation

Open in browser: `http://localhost:8000/docs`

You should see the FastAPI Swagger documentation.

### 3. Verify WordPress Plugin

1. Log in to WordPress admin
2. Navigate to **Brand Monitor** in the left menu
3. Go to **Settings**
4. Enter your API URL: `http://your-api-server:8000`
5. Enter your API Key (from client creation)
6. Click **Save Changes**

### 4. Test Connection

After saving settings, the plugin will attempt to validate the API key. If successful, you'll see a confirmation message.

### 5. Check Dashboard

Navigate to **Brand Monitor > Dashboard**. You should see:

- Today's Mentions count
- Sentiment Score
- Active Alerts
- Recent Mentions table

---

## Docker Installation (Alternative)

### Using Docker Compose

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  api:
    build: ./backend
    ports:
      - "8000:8000"
    environment:
      - DATABASE_URL=postgresql://brand_monitor:password@db:5432/brand_monitor
      - REDIS_URL=redis://redis:6379/0
      - APIFY_API_TOKEN=${APIFY_API_TOKEN}
      - ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
      - SECRET_KEY=${SECRET_KEY}
    depends_on:
      - db
      - redis

  worker:
    build: ./backend
    command: celery -A app.tasks worker --loglevel=info
    environment:
      - DATABASE_URL=postgresql://brand_monitor:password@db:5432/brand_monitor
      - REDIS_URL=redis://redis:6379/0
      - APIFY_API_TOKEN=${APIFY_API_TOKEN}
      - ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
    depends_on:
      - db
      - redis

  db:
    image: postgres:15
    environment:
      - POSTGRES_USER=brand_monitor
      - POSTGRES_PASSWORD=password
      - POSTGRES_DB=brand_monitor
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7
    volumes:
      - redis_data:/data

volumes:
  postgres_data:
  redis_data:
```

Run:

```bash
docker-compose up -d
```

---

## Next Steps

After installation:

1. [Configure your API keys](Configuration.md)
2. [Set up monitoring sources](Scraping-Sources.md)
3. [Explore the dashboard](Dashboard-Guide.md)

---

## Troubleshooting Installation

See [Troubleshooting Guide](Troubleshooting.md) for common installation issues.

| Issue | Solution |
|-------|----------|
| Plugin activation fails | Check PHP version is 8.0+ |
| API connection refused | Verify backend is running and URL is correct |
| Database connection error | Check PostgreSQL credentials and service status |
| Redis connection error | Verify Redis is running: `redis-cli ping` |
