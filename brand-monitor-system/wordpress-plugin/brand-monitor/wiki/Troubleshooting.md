# Troubleshooting Guide

Solutions to common issues with Brand Monitor.

## Table of Contents

1. [Plugin Issues](#plugin-issues)
2. [Connection Issues](#connection-issues)
3. [Scraping Issues](#scraping-issues)
4. [Data Issues](#data-issues)
5. [Performance Issues](#performance-issues)
6. [Backend Issues](#backend-issues)

---

## Plugin Issues

### Plugin Won't Activate

**Symptoms:**
- Error message on activation
- White screen after activation
- "Plugin could not be activated" error

**Solutions:**

1. **Check PHP Version**
   ```bash
   php -v
   # Requires PHP 8.0+
   ```

2. **Check WordPress Version**
   - Requires WordPress 6.2+
   - Update WordPress if needed

3. **Check for Conflicts**
   - Deactivate other plugins
   - Switch to default theme
   - Try activating again

4. **Check Error Logs**
   ```bash
   # View WordPress debug log
   tail -f wp-content/debug.log
   ```

5. **Enable Debug Mode**
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

### Dashboard Not Loading

**Symptoms:**
- Blank page in admin
- JavaScript errors in console
- Infinite loading spinner

**Solutions:**

1. **Clear Browser Cache**
   - Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)

2. **Check JavaScript Console**
   - Open browser DevTools (F12)
   - Look for errors in Console tab

3. **Regenerate Assets**
   ```bash
   # In WordPress root
   wp cache flush
   ```

4. **Check File Permissions**
   ```bash
   chmod 755 wp-content/plugins/brand-monitor
   chmod 644 wp-content/plugins/brand-monitor/*.php
   ```

---

## Connection Issues

### "Connection Failed" Error

**Symptoms:**
- API validation fails
- "Could not connect to API" message
- Timeout errors

**Solutions:**

1. **Verify API URL**
   - Check for typos
   - Ensure https:// prefix
   - Try without trailing slash

2. **Check Backend Status**
   ```bash
   curl https://api.yourdomain.com/health
   # Should return: {"status": "healthy"}
   ```

3. **Verify API Key**
   - Confirm key is correct
   - Check for extra spaces
   - Regenerate if unsure

4. **Check Firewall**
   - Ensure port 443 (HTTPS) is open
   - Check if backend IP is whitelisted

5. **Test from Server**
   ```bash
   # SSH into WordPress server
   curl -X POST https://api.yourdomain.com/api/v1/auth/validate \
     -H "Authorization: Bearer YOUR_API_KEY"
   ```

### SSL Certificate Errors

**Symptoms:**
- "SSL certificate problem"
- "Certificate verify failed"

**Solutions:**

1. **Verify Certificate**
   ```bash
   openssl s_client -connect api.yourdomain.com:443
   ```

2. **Update CA Certificates**
   ```bash
   # Ubuntu/Debian
   sudo apt update && sudo apt install ca-certificates
   ```

3. **Temporary Workaround** (development only)
   ```php
   // In api-client.php - NOT for production
   'sslverify' => false
   ```

### Timeout Errors

**Symptoms:**
- "Request timed out"
- Slow API responses

**Solutions:**

1. **Increase Timeout**
   ```php
   // In api-client.php
   'timeout' => 60  // Increase from 30
   ```

2. **Check Backend Performance**
   ```bash
   # Time API response
   time curl https://api.yourdomain.com/api/v1/mentions
   ```

3. **Check Network Latency**
   ```bash
   ping api.yourdomain.com
   ```

---

## Scraping Issues

### Scrape Jobs Stuck in "Pending"

**Symptoms:**
- Jobs never start
- Status stays "pending" indefinitely

**Solutions:**

1. **Check Apify Token**
   ```bash
   curl https://api.apify.com/v2/users/me \
     -H "Authorization: Bearer YOUR_APIFY_TOKEN"
   ```

2. **Verify Apify Credits**
   - Log in to Apify console
   - Check remaining credits
   - Add payment method if needed

3. **Check Backend Logs**
   ```bash
   tail -f /var/log/brand-monitor/api.log
   ```

### No Results from Scrape

**Symptoms:**
- Scrape completes but 0 mentions
- Empty dataset

**Solutions:**

1. **Verify Keywords**
   - Check spelling
   - Try broader terms
   - Test in Apify console directly

2. **Check Source Availability**
   - Some sources may be rate-limited
   - Try different source type

3. **Review Actor Configuration**
   ```json
   {
     "maxResults": 100,  // Not 0
     "searchTerms": ["actual keywords"]
   }
   ```

### Webhook Not Receiving Data

**Symptoms:**
- Scrape runs but data never appears
- Webhook endpoint not called

**Solutions:**

1. **Verify Webhook URL**
   - Must be publicly accessible
   - Check WEBHOOK_BASE_URL in .env

2. **Test Webhook Endpoint**
   ```bash
   curl -X POST https://api.yourdomain.com/api/v1/webhooks/apify/test \
     -H "Content-Type: application/json" \
     -d '{"test": true}'
   ```

3. **Check Apify Webhook Logs**
   - Go to Apify Console
   - View run details
   - Check webhook delivery status

4. **Use ngrok for Development**
   ```bash
   ngrok http 8000
   # Update WEBHOOK_BASE_URL with ngrok URL
   ```

---

## Data Issues

### Duplicate Mentions

**Symptoms:**
- Same mention appears multiple times
- Duplicate content in table

**Solutions:**

1. **Run Deduplication**
   ```bash
   python scripts/deduplicate_mentions.py
   ```

2. **Check Scrape Schedules**
   - Avoid overlapping scrapes
   - Use appropriate intervals

3. **Enable Deduplication**
   - Already enabled by default
   - Uses URL + content hash

### Incorrect Sentiment

**Symptoms:**
- Positive content marked negative
- Sentiment seems random

**Solutions:**

1. **Check Anthropic API**
   ```bash
   # Verify API key works
   curl https://api.anthropic.com/v1/messages \
     -H "x-api-key: YOUR_KEY" \
     -H "Content-Type: application/json" \
     -d '{"model": "claude-sonnet-4-20250514", "max_tokens": 10, "messages": [{"role": "user", "content": "test"}]}'
   ```

2. **Review Batch Size**
   - Smaller batches may improve accuracy
   - Check sentiment_analyzer.py settings

3. **Check Content Language**
   - AI works best with English
   - Consider language-specific analysis

### Missing Fields

**Symptoms:**
- Author field empty
- No published date
- Missing content

**Solutions:**

1. **Check Field Mappings**
   - Review data_processor.py
   - Verify field names match source

2. **Check Raw Data**
   ```sql
   SELECT raw_data FROM mentions
   WHERE author IS NULL LIMIT 5;
   ```

3. **Update Field Mapping**
   - Some sources change their API
   - Update SOURCE_FIELD_MAPPINGS

---

## Performance Issues

### Slow Dashboard Load

**Symptoms:**
- Dashboard takes >5 seconds
- Browser feels sluggish

**Solutions:**

1. **Reduce Mentions Per Page**
   - Go to Settings
   - Lower "Mentions per Page" to 10

2. **Add Database Indexes**
   ```sql
   CREATE INDEX idx_mentions_discovered_at
   ON mentions(discovered_at DESC);

   CREATE INDEX idx_mentions_sentiment
   ON mentions(sentiment);
   ```

3. **Enable Caching**
   ```bash
   # Install Redis cache
   wp plugin install redis-cache --activate
   ```

### High Memory Usage

**Symptoms:**
- PHP memory errors
- Server running slow

**Solutions:**

1. **Increase PHP Memory**
   ```php
   // In wp-config.php
   define('WP_MEMORY_LIMIT', '256M');
   ```

2. **Optimize Queries**
   - Use pagination
   - Limit date ranges

3. **Clean Old Data**
   ```sql
   -- Delete mentions older than 90 days
   DELETE FROM mentions
   WHERE discovered_at < NOW() - INTERVAL 90 DAY;
   ```

---

## Backend Issues

### Database Connection Failed

**Symptoms:**
- "Connection refused" errors
- Backend won't start

**Solutions:**

1. **Check PostgreSQL Status**
   ```bash
   sudo systemctl status postgresql
   sudo systemctl start postgresql
   ```

2. **Verify Connection String**
   ```bash
   psql postgresql://user:pass@localhost:5432/brand_monitor
   ```

3. **Check Port**
   ```bash
   netstat -an | grep 5432
   ```

### Redis Connection Failed

**Symptoms:**
- Celery workers fail
- Task queue errors

**Solutions:**

1. **Check Redis Status**
   ```bash
   sudo systemctl status redis
   redis-cli ping
   ```

2. **Verify Redis URL**
   ```bash
   redis-cli -u redis://localhost:6379/0 ping
   ```

### Celery Workers Not Processing

**Symptoms:**
- Tasks queue but never execute
- Jobs stuck in "running"

**Solutions:**

1. **Check Worker Status**
   ```bash
   celery -A app.tasks inspect active
   ```

2. **Restart Workers**
   ```bash
   sudo systemctl restart brand-monitor-worker
   ```

3. **Check Logs**
   ```bash
   tail -f /var/log/celery/worker.log
   ```

---

## Error Messages Reference

| Error | Cause | Solution |
|-------|-------|----------|
| "Missing API key" | No key configured | Add key in Settings |
| "Invalid API key" | Wrong/expired key | Regenerate and update |
| "Rate limit exceeded" | Too many requests | Wait and retry |
| "Unknown source type" | Invalid source | Check available sources |
| "Connection refused" | Backend down | Start backend services |
| "Timeout" | Slow network/server | Increase timeout, check server |

---

## Getting Help

### Collect Debug Info

Before requesting help, gather:

1. **Plugin Version**
   ```
   Brand Monitor > Settings > Version
   ```

2. **WordPress Info**
   ```
   Tools > Site Health > Info
   ```

3. **Error Logs**
   ```bash
   tail -100 wp-content/debug.log
   ```

4. **Backend Logs**
   ```bash
   tail -100 /var/log/brand-monitor/api.log
   ```

### Support Channels

- **GitHub Issues**: [Report bugs](https://github.com/your-repo/issues)
- **Documentation**: You're reading it!
- **Email**: support@yourdomain.com

---

## Next Steps

- [Review Configuration](Configuration.md)
- [Check API Reference](API-Reference.md)
- [Developer Guide](Developer-Guide.md)
