# Dashboard Guide

Learn how to use the Brand Monitor WordPress dashboard to track your brand across the web.

## Table of Contents

1. [Dashboard Overview](#dashboard-overview)
2. [Statistics Cards](#statistics-cards)
3. [Recent Mentions](#recent-mentions)
4. [Sources Page](#sources-page)
5. [Reports Page](#reports-page)
6. [Settings Page](#settings-page)

---

## Dashboard Overview

Access the dashboard by clicking **Brand Monitor** in the WordPress admin menu.

### Main Dashboard Layout

```
┌─────────────────────────────────────────────────────────────┐
│  Brand Monitor Dashboard                                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   Today's   │  │  Sentiment  │  │   Active    │         │
│  │  Mentions   │  │   Score     │  │   Alerts    │         │
│  │     47      │  │    0.42     │  │      3      │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Recent Mentions                                     │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │ Source    │ Title        │ Sentiment │ Date    │ Act │   │
│  │ Twitter   │ Great prod.. │ Positive  │ Dec 17  │ View│   │
│  │ Reddit    │ Any reviews? │ Neutral   │ Dec 17  │ View│   │
│  │ Yelp      │ Disappoint.. │ Negative  │ Dec 16  │ View│   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## Statistics Cards

### Today's Mentions

Displays the total number of brand mentions discovered today across all monitored sources.

**What it shows:**
- Real-time count from the backend API
- Updates automatically via AJAX
- Includes all source types

**Interpreting the data:**
- Higher numbers may indicate a campaign going viral
- Sudden spikes could indicate a PR crisis
- Compare to historical averages

### Sentiment Score

Shows the average sentiment score for recent mentions.

**Score Range:**
- `-1.0 to -0.3`: Negative sentiment
- `-0.3 to 0.3`: Neutral sentiment
- `0.3 to 1.0`: Positive sentiment

**Color Coding:**
- Green: Positive (score > 0.3)
- Gray: Neutral (-0.3 to 0.3)
- Red: Negative (score < -0.3)

### Active Alerts

Count of unread alerts requiring attention.

**Alert Types:**
- **Negative Sentiment Spike**: Unusual increase in negative mentions
- **Crisis Indicator**: AI detected potential PR crisis
- **Volume Spike**: Unusual increase in total mentions
- **Competitor Activity**: Mentions of competitors (if configured)

---

## Recent Mentions

The mentions table displays the latest brand mentions from all sources.

### Table Columns

| Column | Description |
|--------|-------------|
| **Source** | Platform where mention was found (Twitter, Reddit, etc.) |
| **Title** | Post title or first line of content |
| **Sentiment** | AI-analyzed sentiment (Positive/Neutral/Negative) |
| **Date** | When the mention was discovered |
| **Actions** | Link to view original source |

### Sentiment Badges

```
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│  Positive   │  │   Neutral   │  │  Negative   │
│   (green)   │  │   (gray)    │  │   (red)     │
└─────────────┘  └─────────────┘  └─────────────┘
```

### Filtering Mentions

Use the filter options above the table:

1. **By Sentiment**: Show only positive, negative, or neutral
2. **By Source**: Filter to specific platforms
3. **By Date Range**: Set custom date filters

### Viewing Original Content

Click the **View** link to open the original post/review in a new tab.

---

## Sources Page

Navigate to **Brand Monitor > Sources** to configure monitoring.

### Adding a New Source

1. Select **Source Type** from dropdown:
   - Google Search
   - Twitter
   - Reddit
   - Instagram
   - News
   - And 45+ more...

2. Enter **Keywords**:
   ```
   your brand name
   @yourbrand
   #yourbrand
   "your product"
   ```

3. Set **Schedule (cron)**:
   ```
   0 * * * *     # Every hour
   0 */4 * * *   # Every 4 hours
   0 0 * * *     # Daily at midnight
   0 0 * * 0     # Weekly on Sunday
   ```

4. Click **Save Source**

### Source Configuration Examples

**Twitter Monitoring:**
```
Source Type: Twitter
Keywords: @yourbrand, #yourbrand, "Your Brand review"
Schedule: 0 * * * * (hourly)
```

**Review Sites:**
```
Source Type: Trustpilot
Keywords: your-company (or company URL)
Schedule: 0 0 * * * (daily)
```

**News Monitoring:**
```
Source Type: News
Keywords: "Your Company" announcement, "Your Company" funding
Schedule: 0 */4 * * * (every 4 hours)
```

### Managing Sources

- **Edit**: Click on existing source to modify
- **Delete**: Remove sources no longer needed
- **Pause**: Temporarily disable without deleting
- **Test**: Run a one-time scrape to verify configuration

---

## Reports Page

Navigate to **Brand Monitor > Reports** for detailed analytics.

### Available Reports

#### 1. Sentiment Trend Report

Shows sentiment changes over time.

```
         Sentiment Over Last 30 Days
    1.0 │                     ╭────
        │               ╭────╯
    0.5 │         ╭────╯
        │    ╭───╯
    0.0 │───╯
        │
   -0.5 │
        └────────────────────────────────
         Week 1   Week 2   Week 3   Week 4
```

#### 2. Source Distribution Report

Pie chart showing mentions by source.

```
     ┌─────────────────────┐
     │  Twitter: 36%       │
     │  Instagram: 19%     │
     │  Reddit: 15%        │
     │  News: 12%          │
     │  Reviews: 10%       │
     │  Other: 8%          │
     └─────────────────────┘
```

#### 3. Volume Trend Report

Daily/weekly/monthly mention counts.

#### 4. Top Authors Report

Most active users mentioning your brand.

#### 5. Crisis Report

Timeline of negative sentiment spikes with context.

### Exporting Reports

- **CSV**: Download raw data
- **PDF**: Formatted report for sharing
- **Email**: Schedule automated report delivery

---

## Settings Page

Navigate to **Brand Monitor > Settings** to configure the plugin.

### API Configuration

| Setting | Description | Example |
|---------|-------------|---------|
| **API URL** | Backend server address | `https://api.yourdomain.com` |
| **API Key** | Authentication token | `bm_live_xxxxx` |

### Notification Settings

| Setting | Description |
|---------|-------------|
| **Email Alerts** | Receive alerts via email |
| **Alert Recipients** | Email addresses for notifications |
| **Alert Threshold** | Minimum severity for notifications |

### Display Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Mentions per Page** | Number in table | 10 |
| **Dashboard Refresh** | Auto-refresh interval | 5 minutes |
| **Date Format** | How dates display | WordPress default |

### Testing Connection

After entering API settings:

1. Click **Save Changes**
2. Look for "Connection Successful" message
3. If failed, verify:
   - API URL is correct
   - API Key is valid
   - Backend server is running

---

## Quick Actions

### Trigger Manual Scrape

From the Sources page:

1. Select source type
2. Enter keywords
3. Click **Run Now** for immediate scrape

### View All Alerts

Click the **Active Alerts** stat card to see all alerts:

- Unread alerts highlighted
- Click to view related mention
- Mark as read to dismiss

### Export Data

From the Mentions table:

1. Apply desired filters
2. Click **Export**
3. Choose format (CSV/JSON)

---

## Dashboard Widgets

### Adding to WordPress Dashboard

The plugin provides a dashboard widget for the main WordPress dashboard:

1. Go to **Dashboard**
2. Click **Screen Options** (top right)
3. Enable **Brand Monitor Summary**

### Widget Features

- Quick stats overview
- Last 5 mentions
- Link to full dashboard

---

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `r` | Refresh mentions |
| `n` | Next page |
| `p` | Previous page |
| `f` | Focus filter |
| `?` | Show shortcuts |

---

## Mobile Responsiveness

The dashboard is fully responsive:

- **Desktop**: Full table view
- **Tablet**: Condensed columns
- **Mobile**: Card-based layout

---

## Next Steps

- [Configure Scraping Sources](Scraping-Sources.md)
- [View API Reference](API-Reference.md)
- [Troubleshooting](Troubleshooting.md)
