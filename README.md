# Brand Reputation Monitor WordPress Plugin

A comprehensive WordPress plugin for monitoring client brand reputation by tracking mentions, articles, news, and backlinks across the web using AI-powered analysis.

## Features

- **Client Profile Management**: Create and manage client profiles with contact information and monitoring keywords
- **AI-Powered Monitoring**: Uses OpenRouter or Perplexity AI for intelligent web scraping and analysis
- **Daily Automated Scans**: Automatic daily monitoring with configurable frequency
- **Sentiment Analysis**: Analyzes sentiment of found mentions (positive, negative, neutral)
- **Relevance Scoring**: AI-powered relevance scoring for each found mention
- **Multiple Content Types**: Tracks articles, news, social media, blog posts, and forum discussions
- **Cost-Effective**: Optimized for cost efficiency with configurable result limits
- **Admin Dashboard**: Comprehensive WordPress admin interface for managing everything

## Installation

1. Upload the plugin files to `/wp-content/plugins/brand-reputation-monitor/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your AI provider API key in the plugin settings

## Configuration

### AI Provider Setup

The plugin supports two AI providers:

#### OpenRouter (Recommended)
- More cost-effective for this use case
- Get API key from [OpenRouter.ai](https://openrouter.ai/)
- Uses GPT-4o-mini model for optimal cost/performance

#### Perplexity AI
- Alternative provider with powerful web search capabilities
- Get API key from [Perplexity.ai](https://www.perplexity.ai/)
- Uses Sonar models via the OpenAI-compatible Chat Completions API (e.g., `sonar`, `sonar-pro`, `sonar-deep-research`)
- See official docs: https://docs.perplexity.ai/api-reference/chat-completions-post

### Settings Configuration

1. Go to **Brand Monitor > Settings** in your WordPress admin
2. Select your preferred AI provider
3. Enter your API key
4. Configure monitoring frequency (daily, twice daily, or hourly)
5. Set maximum results per client per scan (5-100)
6. Set notification email for daily reports

## Usage

### Adding Clients

1. Go to **Brand Monitor > Clients**
2. Click "Add New Client"
3. Fill in client information:
   - **Name**: Client company/person name
   - **Address**: Physical address (optional)
   - **Website**: Company website (optional)
   - **Phone**: Contact phone number (optional)
   - **Email**: Contact email (optional)
   - **Keywords**: Comma-separated keywords to monitor (required)

### Monitoring Keywords

Enter relevant keywords that should be monitored for mentions:
- Company name
- Brand names
- Product names
- Key personnel names
- Industry-specific terms

Example: `"Acme Corp", "Acme Widgets", "John Smith CEO", "widget manufacturing"`

### Viewing Results

1. Go to **Brand Monitor > Results**
2. Filter by client, content type, or date
3. View sentiment analysis and relevance scores
4. Click "View" to open the original source

### Manual Scanning

- Use "Run Manual Scan" button for immediate scanning
- Useful for testing or urgent monitoring needs
- Results are saved to the database for future reference

## Cost Optimization

### Estimated Costs (2024)

**OpenRouter (GPT-4o-mini)**:
- ~$0.0003 per request
- 10 clients = ~$0.90/month
- 50 clients = ~$4.50/month

**Perplexity AI (Llama-3.1-sonar-small)**:
- ~$0.0004 per request
- 10 clients = ~$1.20/month
- 50 clients = ~$6.00/month

### Cost-Saving Tips

1. **Optimize Keywords**: Use specific, relevant keywords to reduce false positives
2. **Limit Results**: Set appropriate max results per client (20-50 is usually sufficient)
3. **Monitor Frequency**: Daily monitoring is usually sufficient for most use cases
4. **Review Results**: Regularly review and clean up irrelevant results

## Database Schema

The plugin creates three main tables:

### `wp_brm_clients`
- Client profile information
- Monitoring keywords
- Status and timestamps

### `wp_brm_monitoring_results`
- Found mentions and articles
- URLs, titles, content excerpts
- Sentiment and relevance scores
- Source and type classification

### `wp_brm_monitoring_logs`
- Monitoring activity logs
- Error tracking
- Performance metrics

## API Integration

### OpenRouter API
- Uses GPT-4o-mini model for cost efficiency
- Supports up to 4K tokens per request
- Includes web search capabilities

### Perplexity AI API
- Uses Llama-3.1-sonar-small model
- Built-in web search and real-time data access
- Optimized for current information

## Troubleshooting

### Common Issues

1. **No results found**
   - Check API key configuration
   - Verify keywords are relevant and specific
   - Ensure client has recent online presence

2. **High API costs**
   - Reduce max results per client
   - Use more specific keywords
   - Consider less frequent monitoring

3. **API errors**
   - Verify API key is valid and has sufficient credits
   - Check rate limits
   - Review error logs in monitoring logs

### Debug Information

- Check **Brand Monitor > Results** for monitoring logs
- Review WordPress error logs for API issues
- Use manual scan to test individual clients

## Security

- All user inputs are sanitized and validated
- API keys are stored securely in WordPress options
- AJAX requests are protected with nonces
- Database queries use prepared statements

## Performance

- Optimized database queries with proper indexing
- Caching of API responses where appropriate
- Background processing for daily scans
- Efficient result deduplication

## Support

For support and feature requests, please contact the plugin developer or submit issues through the appropriate channels.

## Changelog

### Version 1.3
- Fixed incorrect result links by normalizing and validating URLs before saving
- Filtered out fake/suspicious domains from AI results
- Added ability to delete individual monitoring results from the admin Results page (soft delete)
- Results page now includes a client filter to view results per client
- Bumped plugin version to 1.3

### Version 1.0.0
- Initial release
- Client profile management
- AI-powered monitoring with OpenRouter and Perplexity
- Daily automated scanning
- Sentiment analysis and relevance scoring
- Comprehensive admin interface
- Cost optimization features