/**
 * Brand Monitor - News Scraper
 *
 * Custom Apify actor for scraping Google News and RSS feeds.
 * Uses Cheerio for fast, efficient scraping.
 *
 * Cost: ~80% cheaper than public actors
 */

import { Actor } from 'apify';
import { CheerioCrawler, log } from 'crawlee';
import Parser from 'rss-parser';

await Actor.init();

const input = await Actor.getInput() ?? {};
const {
    searchTerms = [],
    rssFeedUrls = [],
    maxArticles = 50,
    language = 'en',
    country = 'US',
} = input;

if (!searchTerms.length && !rssFeedUrls.length) {
    throw new Error('Either searchTerms or rssFeedUrls is required');
}

log.info('Starting News scraper', { searchTerms, rssFeedUrls, maxArticles });

const rssParser = new Parser();
const collectedArticles = new Set();

const crawler = new CheerioCrawler({
    maxRequestsPerCrawl: (searchTerms.length + rssFeedUrls.length) * 5,

    async requestHandler({ request, $, log }) {
        const { searchTerm, requestType, feedUrl } = request.userData;

        if (requestType === 'google_news') {
            log.info(`Processing Google News for: ${searchTerm}`);

            const articles = [];

            // Parse Google News results
            $('article').each((i, el) => {
                if (articles.length >= maxArticles) return false;

                try {
                    const $article = $(el);

                    // Extract link
                    const linkEl = $article.find('a[href*="./articles/"]').first();
                    let url = linkEl.attr('href') || '';
                    if (url.startsWith('./')) {
                        url = `https://news.google.com${url.substring(1)}`;
                    }

                    // Extract title
                    const title = linkEl.text().trim() || $article.find('h3, h4').first().text().trim();

                    // Extract source
                    const source = $article.find('time').parent().text().split('·')[0]?.trim() || '';

                    // Extract time
                    const timeEl = $article.find('time');
                    const publishedAt = timeEl.attr('datetime') || new Date().toISOString();

                    if (title && url && !collectedArticles.has(url)) {
                        collectedArticles.add(url);
                        articles.push({
                            url,
                            title,
                            description: '',
                            source,
                            publishedAt,
                            searchTerm,
                            source_type: 'news',
                        });
                    }
                } catch (e) {
                    // Skip malformed articles
                }
            });

            // Alternative parsing for different Google News layout
            if (articles.length === 0) {
                $('c-wiz article, div[data-n-tid]').each((i, el) => {
                    if (articles.length >= maxArticles) return false;

                    try {
                        const $el = $(el);
                        const linkEl = $el.find('a').first();
                        const url = linkEl.attr('href') || '';
                        const title = $el.find('h3, h4, [role="heading"]').first().text().trim();
                        const source = $el.find('[data-n-tid]').first().text().trim();

                        if (title && url && !collectedArticles.has(url)) {
                            collectedArticles.add(url);
                            articles.push({
                                url: url.startsWith('http') ? url : `https://news.google.com${url}`,
                                title,
                                description: '',
                                source,
                                publishedAt: new Date().toISOString(),
                                searchTerm,
                                source_type: 'news',
                            });
                        }
                    } catch (e) {
                        // Skip
                    }
                });
            }

            if (articles.length > 0) {
                await Actor.pushData(articles);
                log.info(`Saved ${articles.length} articles for "${searchTerm}"`);
            }
        }

        if (requestType === 'rss') {
            log.info(`Processing RSS feed: ${feedUrl}`);

            try {
                // Get raw XML/content
                const content = $.html();

                // Parse RSS
                const feed = await rssParser.parseString(content);
                const articles = [];

                for (const item of feed.items || []) {
                    if (articles.length >= maxArticles) break;

                    const url = item.link || item.guid || '';
                    if (collectedArticles.has(url)) continue;

                    collectedArticles.add(url);
                    articles.push({
                        url,
                        title: item.title || '',
                        description: item.contentSnippet || item.content || '',
                        source: feed.title || feedUrl,
                        publishedAt: item.isoDate || item.pubDate || new Date().toISOString(),
                        searchTerm: feedUrl,
                        source_type: 'rss_feed',
                    });
                }

                if (articles.length > 0) {
                    await Actor.pushData(articles);
                    log.info(`Saved ${articles.length} articles from RSS`);
                }
            } catch (e) {
                log.error(`Failed to parse RSS: ${e.message}`);
            }
        }
    },

    failedRequestHandler({ request, log }) {
        log.error(`Request failed: ${request.url}`);
    },
});

// Build requests
const requests = [];

// Google News searches
for (const term of searchTerms) {
    const encodedTerm = encodeURIComponent(term);
    const url = `https://news.google.com/search?q=${encodedTerm}&hl=${language}&gl=${country}&ceid=${country}:${language}`;
    requests.push({
        url,
        userData: { searchTerm: term, requestType: 'google_news' },
    });
}

// RSS feeds
for (const feedUrl of rssFeedUrls) {
    requests.push({
        url: feedUrl,
        userData: { feedUrl, requestType: 'rss' },
    });
}

await crawler.run(requests);

log.info('News scraping complete', { totalArticles: collectedArticles.size });

await Actor.exit();
