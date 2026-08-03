#!/usr/bin/env node
/**
 * Amazon Product Scraper — Playwright Edition
 * ---------------------------------------------
 * Real headless-browser scraper used as a sidecar process by the Laravel app.
 * This exists because Amazon's bot detection (PerimeterX-style fingerprinting,
 * JS-rendered content, CAPTCHA walls) reliably blocks plain HTTP requests —
 * see AmazonScraperService.php's PHP fallback for the (much less reliable)
 * HTTP-only approach.
 *
 * Usage:
 *   node scrape.js "<amazon_product_url>"
 *
 * Output:
 *   Prints a single JSON object to stdout on success.
 *   On failure, prints {"error": "..."} to stdout and exits with code 1.
 *
 * Called from Laravel via Symfony Process — see
 * app/Services/Scraper/PlaywrightScraperService.php
 */

const { chromium } = require('playwright');

const TIMEOUT_MS = parseInt(process.env.SCRAPER_TIMEOUT_MS || '45000', 10);
const HEADLESS = process.env.SCRAPER_HEADLESS !== 'false';

const BLOCK_SIGNALS = [
    'Type the characters you see in this image',
    'Robot Check',
    'Enter the characters you see below',
    "Sorry, we just need to make sure you're not a robot",
    'To discuss automated access to Amazon data',
    'api-services-support@amazon.com',
];

function extractAsin(url) {
    const patterns = [
        /\/dp\/([A-Z0-9]{10})/i,
        /\/product\/([A-Z0-9]{10})/i,
        /\/gp\/product\/([A-Z0-9]{10})/i,
        /[?&]ASIN=([A-Z0-9]{10})/i,
    ];
    for (const p of patterns) {
        const m = url.match(p);
        if (m) return m[1].toUpperCase();
    }
    return null;
}

async function scrape(url) {
    const asin = extractAsin(url);
    const targetUrl = asin ? buildCanonicalUrl(url, asin) : url;

    const browser = await chromium.launch({
        headless: HEADLESS,
        args: [
            '--disable-blink-features=AutomationControlled',
            '--disable-dev-shm-usage',
            '--no-sandbox',
        ],
    });

    try {
        const context = await browser.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            viewport: { width: 1366, height: 900 },
            locale: 'en-US',
            timezoneId: 'Asia/Kolkata',
        });

        // Mask common automation fingerprints
        await context.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
            Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
            Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
            window.chrome = { runtime: {} };
        });

        const page = await context.newPage();
        page.setDefaultTimeout(TIMEOUT_MS);

        await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: TIMEOUT_MS });

        // Give Amazon's lazy-loaded content a moment, mimic human behavior
        await page.waitForTimeout(1200 + Math.random() * 800);
        await page.mouse.move(200, 300);
        await page.mouse.wheel(0, 600);
        await page.waitForTimeout(500);

        const html = await page.content();

        // Detect bot-check / CAPTCHA pages
        for (const signal of BLOCK_SIGNALS) {
            if (html.includes(signal)) {
                throw new Error(`BLOCKED: Amazon served a bot-check page (matched: "${signal}")`);
            }
        }

        // Confirm a real product page rendered
        const hasTitle = await page.locator('#productTitle, #title').count();
        if (hasTitle === 0) {
            throw new Error('NO_PRODUCT_MARKUP: Page loaded but no #productTitle/#title found — likely blocked, redirected, or invalid ASIN.');
        }

        const data = await page.evaluate(() => {
            const text = (sel) => {
                const el = document.querySelector(sel);
                return el ? el.textContent.trim().replace(/\s+/g, ' ') : null;
            };

            const title = text('#productTitle') || text('#title');

            // Brand
            let brand = text('#bylineInfo') || text('.author');
            if (brand) {
                brand = brand.replace(/^(Brand:|Visit the|Store)\s*/i, '').replace(/\s*Store$/i, '').trim();
            }

            // Bullet points
            const bullets = Array.from(
                document.querySelectorAll('#feature-bullets li span.a-list-item, #feature-bullets li:not(.aok-hidden) span')
            )
                .map((el) => el.textContent.trim().replace(/\s+/g, ' '))
                .filter((t) => t.length > 10)
                .slice(0, 10);

            // Description
            const description =
                text('#productDescription p') ||
                text('#productDescription') ||
                text('#feature-bullets');

            // Specifications table
            const specs = {};
            document.querySelectorAll('#productDetails_techSpec_section_1 tr, #prodDetails tr').forEach((row) => {
                const cells = row.querySelectorAll('td, th');
                if (cells.length >= 2) {
                    const key = cells[0].textContent.trim();
                    const val = cells[1].textContent.trim();
                    if (key && val) specs[key] = val;
                }
            });

            // Detail bullets (alternate spec format)
            document.querySelectorAll('[id*="detail-bullets"] li').forEach((li) => {
                const t = li.textContent.trim();
                if (t.includes(':')) {
                    const [k, ...rest] = t.split(':');
                    if (k && rest.length) specs[k.trim()] = rest.join(':').trim();
                }
            });

            // Images — pull from the embedded JS data blob Amazon uses for the gallery
            let images = [];
            const scripts = Array.from(document.querySelectorAll('script')).map((s) => s.textContent || '');
            for (const s of scripts) {
                const hiResMatches = [...s.matchAll(/"hiRes":"(https:[^"]+)"/g)].map((m) => m[1]);
                if (hiResMatches.length) {
                    images = hiResMatches;
                    break;
                }
            }
            if (!images.length) {
                const mainImg = document.querySelector('#landingImage, #imgBlkFront');
                if (mainImg) {
                    const src = mainImg.getAttribute('data-old-hires') || mainImg.src;
                    if (src) images = [src];
                }
            }

            // Category breadcrumb
            const category = Array.from(
                document.querySelectorAll('#wayfinding-breadcrumbs_feature_div a')
            )
                .map((a) => a.textContent.trim())
                .filter(Boolean)
                .join(' > ');

            // Price
            let price = null;
            const priceSelectors = ['.a-price .a-offscreen', '#priceblock_ourprice', '#priceblock_dealprice', '#price'];
            for (const sel of priceSelectors) {
                const el = document.querySelector(sel);
                if (el) {
                    const clean = el.textContent.replace(/[^0-9.]/g, '');
                    if (clean) {
                        price = parseFloat(clean);
                        break;
                    }
                }
            }

            // Weight / dimensions (best-effort from spec table)
            let weight = null;
            let dimensions = null;
            for (const [k, v] of Object.entries(specs)) {
                if (/weight/i.test(k) && !weight) weight = v;
                if (/dimensions/i.test(k) && !dimensions) dimensions = v;
            }

            return { title, brand, bullets, description, specs, images, category, price, weight, dimensions };
        });

        await browser.close();

        return {
            asin,
            url: targetUrl,
            title: data.title,
            brand: data.brand,
            manufacturer: data.specs['Manufacturer'] || data.specs['manufacturer'] || null,
            bullet_points: data.bullets,
            description: data.description,
            specifications: data.specs,
            images: data.images,
            category: data.category,
            attributes: data.specs,
            weight: data.weight,
            dimensions: data.dimensions,
            price: data.price,
            currency: targetUrl.includes('amazon.in') ? 'INR' : 'USD',
            scraped_at: new Date().toISOString(),
            scraper: 'playwright',
        };
    } catch (err) {
        await browser.close().catch(() => {});
        throw err;
    }
}

function buildCanonicalUrl(originalUrl, asin) {
    try {
        const u = new URL(originalUrl);
        return `${u.protocol}//${u.host}/dp/${asin}`;
    } catch {
        return originalUrl;
    }
}

(async () => {
    const url = process.argv[2];
    if (!url) {
        console.log(JSON.stringify({ error: 'No URL provided. Usage: node scrape.js <amazon_url>' }));
        process.exit(1);
    }

    try {
        const result = await scrape(url);
        console.log(JSON.stringify(result));
        process.exit(0);
    } catch (err) {
        console.log(JSON.stringify({ error: err.message || String(err) }));
        process.exit(1);
    }
})();
