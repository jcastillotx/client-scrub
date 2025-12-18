/**
 * Brand Monitor - Reddit Scraper
 *
 * Custom Apify actor for scraping Reddit using the JSON API.
 * Much faster and cheaper than browser-based scraping.
 *
 * Cost: ~60-70% cheaper than public actors
 */

import { Actor } from 'apify';
import { CheerioCrawler, log } from 'crawlee';

await Actor.init();

const input = await Actor.getInput() ?? {};
const {
    searchTerms = [],
    subreddits = [],
    maxPosts = 100,
    includeComments = true,
    maxCommentsPerPost = 10,
    sortBy = 'new',
    timeFilter = 'week',
} = input;

if (!searchTerms.length) {
    throw new Error('searchTerms is required');
}

log.info('Starting Reddit scraper', { searchTerms, maxPosts, sortBy });

// Use Reddit's JSON API for efficiency
const crawler = new CheerioCrawler({
    maxRequestsPerCrawl: searchTerms.length * 20,

    async requestHandler({ request, json, log }) {
        const { searchTerm, requestType } = request.userData;

        if (requestType === 'search') {
            // Process search results
            const posts = json?.data?.children || [];
            log.info(`Found ${posts.length} posts for "${searchTerm}"`);

            const results = [];

            for (const post of posts) {
                const data = post.data;

                const result = {
                    url: `https://reddit.com${data.permalink}`,
                    title: data.title || '',
                    text: data.selftext || '',
                    author: data.author || '[deleted]',
                    subreddit: data.subreddit || '',
                    score: data.score || 0,
                    numComments: data.num_comments || 0,
                    createdAt: new Date(data.created_utc * 1000).toISOString(),
                    searchTerm,
                    postType: 'post',
                    source_type: 'reddit',
                    isNsfw: data.over_18 || false,
                    thumbnail: data.thumbnail || '',
                };

                results.push(result);

                // Queue comment fetch if enabled
                if (includeComments && data.num_comments > 0) {
                    await crawler.addRequests([{
                        url: `https://reddit.com${data.permalink}.json?limit=${maxCommentsPerPost}`,
                        userData: {
                            searchTerm,
                            requestType: 'comments',
                            postUrl: result.url,
                            postTitle: result.title,
                        },
                    }]);
                }
            }

            await Actor.pushData(results);
            log.info(`Saved ${results.length} posts`);

            // Handle pagination
            const after = json?.data?.after;
            if (after && results.length < maxPosts) {
                const nextUrl = new URL(request.url);
                nextUrl.searchParams.set('after', after);

                await crawler.addRequests([{
                    url: nextUrl.toString(),
                    userData: { searchTerm, requestType: 'search' },
                }]);
            }
        }

        if (requestType === 'comments') {
            // Process comments
            const commentsData = json?.[1]?.data?.children || [];
            const { postUrl, postTitle, searchTerm: term } = request.userData;

            const comments = [];

            const extractComments = (children, depth = 0) => {
                if (depth > 2) return; // Limit nesting

                for (const child of children) {
                    if (child.kind !== 't1') continue;
                    const data = child.data;

                    comments.push({
                        url: postUrl,
                        title: postTitle,
                        text: data.body || '',
                        author: data.author || '[deleted]',
                        subreddit: data.subreddit || '',
                        score: data.score || 0,
                        numComments: 0,
                        createdAt: new Date(data.created_utc * 1000).toISOString(),
                        searchTerm: term,
                        postType: 'comment',
                        source_type: 'reddit',
                    });

                    // Get nested replies
                    if (data.replies?.data?.children) {
                        extractComments(data.replies.data.children, depth + 1);
                    }
                }
            };

            extractComments(commentsData);

            if (comments.length > 0) {
                await Actor.pushData(comments.slice(0, maxCommentsPerPost));
                log.info(`Saved ${Math.min(comments.length, maxCommentsPerPost)} comments`);
            }
        }
    },

    failedRequestHandler({ request, log }) {
        log.error(`Request failed: ${request.url}`);
    },
});

// Build search URLs
const requests = [];

for (const term of searchTerms) {
    if (subreddits.length > 0) {
        // Search in specific subreddits
        for (const sub of subreddits) {
            const url = `https://www.reddit.com/r/${sub}/search.json?q=${encodeURIComponent(term)}&sort=${sortBy}&t=${timeFilter}&restrict_sr=1&limit=100`;
            requests.push({
                url,
                userData: { searchTerm: term, requestType: 'search' },
            });
        }
    } else {
        // Search all of Reddit
        const url = `https://www.reddit.com/search.json?q=${encodeURIComponent(term)}&sort=${sortBy}&t=${timeFilter}&limit=100`;
        requests.push({
            url,
            userData: { searchTerm: term, requestType: 'search' },
        });
    }
}

await crawler.run(requests);

log.info('Reddit scraping complete');

await Actor.exit();
