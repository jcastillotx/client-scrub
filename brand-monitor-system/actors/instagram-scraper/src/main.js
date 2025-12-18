/**
 * Brand Monitor - Instagram Scraper
 *
 * Custom Apify actor for scraping Instagram hashtag pages.
 * Uses the public Instagram web interface (no login required).
 *
 * Cost: ~70-80% cheaper than public actors
 */

import { Actor } from 'apify';
import { PlaywrightCrawler, log } from 'crawlee';

await Actor.init();

const input = await Actor.getInput() ?? {};
const {
    hashtags = [],
    maxPosts = 100,
    includeComments = false,
    maxCommentsPerPost = 20,
} = input;

if (!hashtags.length) {
    throw new Error('hashtags is required');
}

log.info('Starting Instagram scraper', { hashtags, maxPosts });

const crawler = new PlaywrightCrawler({
    maxRequestsPerCrawl: hashtags.length * 20,
    headless: true,

    launchContext: {
        launchOptions: {
            args: ['--disable-blink-features=AutomationControlled'],
        },
    },

    async requestHandler({ page, request, log }) {
        const hashtag = request.userData.hashtag;
        log.info(`Processing hashtag: #${hashtag}`);

        // Navigate to hashtag page
        const url = `https://www.instagram.com/explore/tags/${hashtag}/`;
        await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });

        // Wait for content
        await page.waitForTimeout(3000);

        // Check for login wall and try to dismiss
        try {
            const loginButton = await page.$('text="Not Now"');
            if (loginButton) await loginButton.click();
        } catch (e) {
            // Continue if no login wall
        }

        const posts = [];
        let scrollAttempts = 0;
        const maxScrollAttempts = 15;

        while (posts.length < maxPosts && scrollAttempts < maxScrollAttempts) {
            // Extract post data from the page
            const newPosts = await page.evaluate(() => {
                const results = [];
                const articles = document.querySelectorAll('article a[href*="/p/"]');

                articles.forEach(link => {
                    try {
                        const postUrl = link.href;
                        const img = link.querySelector('img');
                        const caption = img?.alt || '';

                        if (postUrl && !results.find(p => p.url === postUrl)) {
                            results.push({
                                url: postUrl,
                                caption: caption.substring(0, 2000),
                                thumbnailUrl: img?.src || '',
                            });
                        }
                    } catch (e) {
                        // Skip malformed posts
                    }
                });

                return results;
            });

            // Add unique posts
            for (const post of newPosts) {
                if (posts.length >= maxPosts) break;
                if (posts.some(p => p.url === post.url)) continue;

                posts.push({
                    ...post,
                    hashtag,
                    source_type: 'instagram',
                    author: '', // Requires opening individual posts
                    timestamp: new Date().toISOString(),
                    likesCount: 0,
                    commentsCount: 0,
                    mediaType: 'image',
                });
            }

            // Scroll for more posts
            await page.evaluate(() => window.scrollBy(0, window.innerHeight * 2));
            await page.waitForTimeout(2000);
            scrollAttempts++;

            log.info(`Collected ${posts.length}/${maxPosts} posts for #${hashtag}`);
        }

        // Optionally get detailed info for each post
        if (posts.length > 0) {
            log.info(`Getting details for ${Math.min(posts.length, 20)} posts...`);

            // Get details for first 20 posts (to avoid rate limits)
            for (let i = 0; i < Math.min(posts.length, 20); i++) {
                try {
                    await page.goto(posts[i].url, { waitUntil: 'networkidle', timeout: 30000 });
                    await page.waitForTimeout(1500);

                    const details = await page.evaluate(() => {
                        // Get author
                        const authorEl = document.querySelector('header a[href*="/"]');
                        const author = authorEl?.innerText || '';

                        // Get caption from meta or page
                        const metaDesc = document.querySelector('meta[name="description"]');
                        const caption = metaDesc?.content || '';

                        // Get timestamp
                        const timeEl = document.querySelector('time');
                        const timestamp = timeEl?.getAttribute('datetime') || '';

                        // Try to get likes (may be hidden)
                        const likesEl = document.querySelector('section span');
                        const likesText = likesEl?.innerText || '0';
                        const likesCount = parseInt(likesText.replace(/[,likes\s]/gi, '')) || 0;

                        return { author, caption, timestamp, likesCount };
                    });

                    posts[i] = { ...posts[i], ...details };
                } catch (e) {
                    log.warning(`Failed to get details for post ${i}`);
                }
            }
        }

        // Save to dataset
        if (posts.length > 0) {
            await Actor.pushData(posts);
            log.info(`Saved ${posts.length} posts for #${hashtag}`);
        }
    },

    failedRequestHandler({ request, log }) {
        log.error(`Request failed: ${request.url}`);
    },
});

// Create requests for each hashtag
const requests = hashtags.map(tag => ({
    url: `https://www.instagram.com/explore/tags/${tag}/`,
    userData: { hashtag: tag.replace('#', '') },
}));

await crawler.run(requests);

log.info('Instagram scraping complete');

await Actor.exit();
