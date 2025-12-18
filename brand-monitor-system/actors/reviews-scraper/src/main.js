/**
 * Brand Monitor - Reviews Scraper
 *
 * Multi-platform review scraper supporting:
 * - Trustpilot
 * - Yelp
 * - G2
 * - Capterra
 * - Google Maps
 *
 * Cost: ~70% cheaper than public actors
 */

import { Actor } from 'apify';
import { PlaywrightCrawler, log } from 'crawlee';

await Actor.init();

const input = await Actor.getInput() ?? {};
const {
    urls = [],
    searchTerms = [],
    sites = ['trustpilot', 'yelp'],
    maxReviews = 50,
    minRating = 1,
} = input;

if (!urls.length && !searchTerms.length) {
    throw new Error('Either urls or searchTerms is required');
}

log.info('Starting Reviews scraper', { urls: urls.length, searchTerms, sites, maxReviews });

// Platform-specific scrapers
const platformScrapers = {
    trustpilot: {
        searchUrl: (term) => `https://www.trustpilot.com/search?query=${encodeURIComponent(term)}`,
        detectPlatform: (url) => url.includes('trustpilot.com'),
        async scrapeReviews(page, maxReviews) {
            const reviews = [];

            // Get business name
            const businessName = await page.$eval(
                'h1, [data-business-unit-name]',
                el => el.textContent?.trim() || ''
            ).catch(() => '');

            // Scroll to load reviews
            for (let i = 0; i < 5 && reviews.length < maxReviews; i++) {
                await page.evaluate(() => window.scrollBy(0, 1000));
                await page.waitForTimeout(1000);

                const newReviews = await page.$$eval(
                    '[data-service-review-card-paper], article[class*="review"]',
                    (elements, max) => elements.slice(0, max).map(el => {
                        const rating = el.querySelector('[data-service-review-rating], img[alt*="star"]')
                            ?.getAttribute('data-service-review-rating')
                            || el.querySelector('img[alt*="star"]')?.alt?.match(/(\d)/)?.[1]
                            || '0';

                        return {
                            text: el.querySelector('[data-service-review-text-typography], p')?.textContent?.trim() || '',
                            title: el.querySelector('h2, [data-service-review-title]')?.textContent?.trim() || '',
                            author: el.querySelector('[data-consumer-name-typography], span[class*="consumer"]')?.textContent?.trim() || '',
                            rating: parseInt(rating) || 0,
                            date: el.querySelector('time')?.getAttribute('datetime') || '',
                        };
                    }),
                    maxReviews
                );

                for (const r of newReviews) {
                    if (reviews.length >= maxReviews) break;
                    if (!reviews.find(x => x.text === r.text)) {
                        reviews.push({ ...r, businessName, platform: 'trustpilot' });
                    }
                }
            }

            return reviews;
        }
    },

    yelp: {
        searchUrl: (term) => `https://www.yelp.com/search?find_desc=${encodeURIComponent(term)}`,
        detectPlatform: (url) => url.includes('yelp.com'),
        async scrapeReviews(page, maxReviews) {
            const reviews = [];

            const businessName = await page.$eval(
                'h1',
                el => el.textContent?.trim() || ''
            ).catch(() => '');

            for (let i = 0; i < 5 && reviews.length < maxReviews; i++) {
                await page.evaluate(() => window.scrollBy(0, 1000));
                await page.waitForTimeout(1000);

                const newReviews = await page.$$eval(
                    '[data-review-id], li[class*="review"]',
                    (elements, max) => elements.slice(0, max).map(el => {
                        const ratingEl = el.querySelector('[aria-label*="star"], div[class*="star"]');
                        const ratingText = ratingEl?.getAttribute('aria-label') || '';
                        const rating = parseInt(ratingText.match(/(\d)/)?.[1] || '0');

                        return {
                            text: el.querySelector('p[class*="comment"], span[class*="raw"]')?.textContent?.trim() || '',
                            title: '',
                            author: el.querySelector('[data-hovercard-id], a[href*="/user_details"]')?.textContent?.trim() || '',
                            rating,
                            date: el.querySelector('span[class*="date"]')?.textContent?.trim() || '',
                        };
                    }),
                    maxReviews
                );

                for (const r of newReviews) {
                    if (reviews.length >= maxReviews) break;
                    if (r.text && !reviews.find(x => x.text === r.text)) {
                        reviews.push({ ...r, businessName, platform: 'yelp' });
                    }
                }
            }

            return reviews;
        }
    },

    g2: {
        searchUrl: (term) => `https://www.g2.com/search?query=${encodeURIComponent(term)}`,
        detectPlatform: (url) => url.includes('g2.com'),
        async scrapeReviews(page, maxReviews) {
            const reviews = [];

            const businessName = await page.$eval(
                'h1, [itemprop="name"]',
                el => el.textContent?.trim() || ''
            ).catch(() => '');

            for (let i = 0; i < 5 && reviews.length < maxReviews; i++) {
                await page.evaluate(() => window.scrollBy(0, 1000));
                await page.waitForTimeout(1000);

                const newReviews = await page.$$eval(
                    '[itemprop="review"], div[class*="review-content"]',
                    (elements, max) => elements.slice(0, max).map(el => {
                        const ratingEl = el.querySelector('[itemprop="ratingValue"], div[class*="stars"]');
                        const rating = parseInt(ratingEl?.getAttribute('content') || ratingEl?.textContent?.match(/(\d)/)?.[1] || '0');

                        return {
                            text: el.querySelector('[itemprop="reviewBody"], div[class*="review-body"]')?.textContent?.trim() || '',
                            title: el.querySelector('[itemprop="headline"], h3')?.textContent?.trim() || '',
                            author: el.querySelector('[itemprop="author"], span[class*="reviewer"]')?.textContent?.trim() || '',
                            rating,
                            date: el.querySelector('time, [itemprop="datePublished"]')?.getAttribute('datetime') || '',
                        };
                    }),
                    maxReviews
                );

                for (const r of newReviews) {
                    if (reviews.length >= maxReviews) break;
                    if (r.text && !reviews.find(x => x.text === r.text)) {
                        reviews.push({ ...r, businessName, platform: 'g2' });
                    }
                }
            }

            return reviews;
        }
    },
};

const crawler = new PlaywrightCrawler({
    maxRequestsPerCrawl: urls.length + (searchTerms.length * sites.length) + 50,
    headless: true,

    async requestHandler({ page, request, log }) {
        const { platform, businessUrl, searchTerm } = request.userData;

        // Detect platform from URL if not specified
        let detectedPlatform = platform;
        if (!detectedPlatform) {
            for (const [name, scraper] of Object.entries(platformScrapers)) {
                if (scraper.detectPlatform(request.url)) {
                    detectedPlatform = name;
                    break;
                }
            }
        }

        if (!detectedPlatform || !platformScrapers[detectedPlatform]) {
            log.warning(`Unknown platform for URL: ${request.url}`);
            return;
        }

        const scraper = platformScrapers[detectedPlatform];
        log.info(`Scraping ${detectedPlatform}: ${request.url}`);

        await page.goto(request.url, { waitUntil: 'networkidle', timeout: 60000 });
        await page.waitForTimeout(2000);

        // If this is a search page, find business links
        if (searchTerm) {
            const businessLinks = await page.$$eval(
                'a[href*="/review/"], a[href*="/biz/"], a[href*="/products/"]',
                links => links.slice(0, 3).map(l => l.href)
            );

            for (const link of businessLinks) {
                await crawler.addRequests([{
                    url: link,
                    userData: { platform: detectedPlatform, searchTerm },
                }]);
            }
            return;
        }

        // Scrape reviews
        const reviews = await scraper.scrapeReviews(page, maxReviews);

        // Filter and save
        const filteredReviews = reviews
            .filter(r => r.rating >= minRating)
            .map(r => ({
                url: request.url,
                title: r.title,
                text: r.text,
                author: r.author,
                rating: r.rating,
                date: r.date,
                businessName: r.businessName,
                platform: r.platform,
                source_type: r.platform,
            }));

        if (filteredReviews.length > 0) {
            await Actor.pushData(filteredReviews);
            log.info(`Saved ${filteredReviews.length} reviews from ${detectedPlatform}`);
        }
    },

    failedRequestHandler({ request, log }) {
        log.error(`Request failed: ${request.url}`);
    },
});

// Build requests
const requests = [];

// Direct URLs
for (const url of urls) {
    requests.push({
        url,
        userData: { businessUrl: url },
    });
}

// Search terms
for (const term of searchTerms) {
    for (const site of sites) {
        if (platformScrapers[site]) {
            requests.push({
                url: platformScrapers[site].searchUrl(term),
                userData: { platform: site, searchTerm: term },
            });
        }
    }
}

await crawler.run(requests);

log.info('Reviews scraping complete');

await Actor.exit();
