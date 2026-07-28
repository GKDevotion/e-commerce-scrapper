#!/usr/bin/env node
/**
 * Multi-Platform Product Scraper — Playwright Edition
 * ----------------------------------------------------
 * Supports Amazon, Flipkart, and Meesho.
 * Platform is auto-detected from URL hostname.
 *
 * Usage:
 *   node scrape.js "<product_url>"
 *
 * Output:
 *   Prints a single JSON object to stdout on success.
 *   On failure, prints {"error": "..."} and exits with code 1.
 */

const { chromium } = require('playwright');

const TIMEOUT_MS = parseInt(process.env.SCRAPER_TIMEOUT_MS || '60000', 10);
const HEADLESS    = process.env.SCRAPER_HEADLESS !== 'false';

// ── Platform detection ────────────────────────────────────────────────────────

function detectPlatform(url) {
    const host = new URL(url).hostname.toLowerCase();
    if (host.includes('flipkart.com')) return 'flipkart';
    if (host.includes('meesho.com'))   return 'meesho';
    return 'amazon';
}

// ── Browser setup (shared) ───────────────────────────────────────────────────

async function launchBrowser() {
    return chromium.launch({
        headless: HEADLESS,
        args: [
            '--disable-blink-features=AutomationControlled',
            '--disable-dev-shm-usage',
            '--no-sandbox',
            '--disable-setuid-sandbox',
        ],
    });
}

async function newContext(browser, locale = 'en-IN') {
    const ctx = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        viewport:   { width: 1366, height: 900 },
        locale,
        timezoneId: 'Asia/Kolkata',
        extraHTTPHeaders: {
            'Accept-Language': 'en-IN,en-GB;q=0.9,en;q=0.8',
        },
    });

    // Mask automation fingerprints
    await ctx.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver',  { get: () => undefined });
        Object.defineProperty(navigator, 'plugins',    { get: () => [1, 2, 3, 4, 5] });
        Object.defineProperty(navigator, 'languages',  { get: () => ['en-IN', 'en'] });
        window.chrome = { runtime: {} };
    });

    return ctx;
}

// ── Amazon scraper ────────────────────────────────────────────────────────────

function extractAsin(url) {
    for (const p of [/\/dp\/([A-Z0-9]{10})/i, /\/product\/([A-Z0-9]{10})/i, /\/gp\/product\/([A-Z0-9]{10})/i]) {
        const m = url.match(p);
        if (m) return m[1].toUpperCase();
    }
    return null;
}

const AMAZON_BLOCK_SIGNALS = [
    'Type the characters you see',
    'Robot Check',
    "make sure you're not a robot",
    'api-services-support@amazon.com',
];

async function scrapeAmazon(url) {
    const asin = extractAsin(url);
    const targetUrl = asin ? (() => { try { const u = new URL(url); return `${u.protocol}//${u.host}/dp/${asin}`; } catch { return url; } })() : url;

    const browser = await launchBrowser();
    try {
        const context = await newContext(browser, 'en-US');
        const page    = await context.newPage();
        page.setDefaultTimeout(TIMEOUT_MS);

        await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: TIMEOUT_MS });
        await page.waitForTimeout(1200 + Math.random() * 800);
        await page.mouse.move(200, 300);
        await page.mouse.wheel(0, 600);
        await page.waitForTimeout(500);

        const html = await page.content();
        for (const sig of AMAZON_BLOCK_SIGNALS) {
            if (html.includes(sig)) throw new Error(`BLOCKED: Amazon served bot-check (matched: "${sig}")`);
        }

        const hasTitle = await page.locator('#productTitle, #title').count();
        if (hasTitle === 0) throw new Error('NO_PRODUCT_MARKUP: No #productTitle/#title found on Amazon page.');

        const data = await page.evaluate(() => {
            const text = sel => document.querySelector(sel)?.textContent.trim().replace(/\s+/g, ' ') ?? null;
            const title = text('#productTitle') || text('#title');
            let brand = text('#bylineInfo') || text('.author') || null;
            if (brand) brand = brand.replace(/^(Brand:|Visit the|Store)\s*/i,'').replace(/\s*Store$/i,'').trim();
            const bullets = Array.from(document.querySelectorAll('#feature-bullets li span.a-list-item'))
                .map(el => el.textContent.trim().replace(/\s+/g,' ')).filter(t => t.length > 10).slice(0,10);
            const description = text('#productDescription p') || text('#productDescription') || null;
            const specs = {};
            document.querySelectorAll('#productDetails_techSpec_section_1 tr, #prodDetails tr').forEach(row => {
                const cells = row.querySelectorAll('td,th');
                if (cells.length >= 2) specs[cells[0].textContent.trim()] = cells[1].textContent.trim();
            });
            let images = [];
            for (const s of Array.from(document.querySelectorAll('script')).map(s=>s.textContent||'')) {
                const hi = [...s.matchAll(/"hiRes":"(https:[^"]+)"/g)].map(m=>m[1]);
                if (hi.length) { images = hi; break; }
            }
            if (!images.length) {
                const img = document.querySelector('#landingImage,#imgBlkFront');
                if (img) images = [img.getAttribute('data-old-hires') || img.src].filter(Boolean);
            }
            const category = Array.from(document.querySelectorAll('#wayfinding-breadcrumbs_feature_div a'))
                .map(a=>a.textContent.trim()).filter(Boolean).join(' > ');
            let price = null;
            for (const sel of ['.a-price .a-offscreen','#priceblock_ourprice','#price']) {
                const el = document.querySelector(sel);
                if (el) { const n = parseFloat(el.textContent.replace(/[^0-9.]/g,'')); if (n) { price=n; break; } }
            }
            return { title, brand, bullets, description, specs, images, category, price };
        });

        await browser.close();
        return {
            platform: 'amazon', asin, url: targetUrl,
            title: data.title, brand: data.brand,
            manufacturer: data.specs['Manufacturer'] || null,
            bullets: data.bullets, description: data.description,
            specifications: data.specs, images: data.images,
            category: data.category, price: data.price,
            currency: targetUrl.includes('amazon.in') ? 'INR' : 'USD',
        };
    } catch (err) {
        await browser.close().catch(()=>{});
        throw err;
    }
}

// ── Flipkart scraper ──────────────────────────────────────────────────────────

async function scrapeFlipkart(url) {
    const browser = await launchBrowser();
    try {
        const context = await newContext(browser, 'en-IN');
        const page    = await context.newPage();
        page.setDefaultTimeout(TIMEOUT_MS);

        // Block heavy assets to speed up load
        await page.route('**/*.{png,jpg,jpeg,gif,webp,svg,woff,woff2,ttf,mp4,mp3}', r => r.abort());

        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: TIMEOUT_MS });

        // Wait for any of the known Flipkart title selectors
        try {
            await page.waitForSelector(
                'span.B_NuCI, h1.yhB1nd, span._35KyD6, [class*="title"], h1',
                { timeout: 15000 }
            );
        } catch (_) { /* continue even if selector times out */ }

        await page.waitForTimeout(1500);
        await page.mouse.wheel(0, 400);
        await page.waitForTimeout(500);

        const html = await page.content();

        // Check for Flipkart login wall or CAPTCHA
        if (html.includes('_2Pt6f3') || html.includes('login to continue') || html.length < 10000) {
            throw new Error('BLOCKED: Flipkart served a login wall or empty page');
        }

        const data = await page.evaluate(() => {
            // ── Try embedded __NEXT_DATA__ (Next.js pages) ────────────────────
            try {
                const nextEl = document.getElementById('__NEXT_DATA__');
                if (nextEl) {
                    const nd = JSON.parse(nextEl.textContent);
                    const pp = nd?.props?.pageProps;
                    const prod = pp?.product || pp?.pdpData?.product || pp?.initialData?.product || {};
                    if (prod.name || prod.title) {
                        return {
                            title:       prod.name || prod.title,
                            brand:       prod.brand || prod.brandName || null,
                            price:       prod.price || prod.mrp || null,
                            description: prod.description || prod.shortDescription || null,
                            bullets:     prod.highlights || prod.keyFeatures || [],
                            images:      (prod.images||prod.imageUrls||[]).map(i=>typeof i==='string'?i:i?.url).filter(Boolean),
                            category:    prod.category || null,
                            specifications: prod.specifications || prod.attributes || {},
                        };
                    }
                }
            } catch(_) {}

            // ── Try embedded window.__INITIAL_STATE__ ─────────────────────────
            try {
                const scripts = Array.from(document.querySelectorAll('script:not([src])'));
                for (const s of scripts) {
                    const text = s.textContent || '';
                    const match = text.match(/window\.__INITIAL_STATE__\s*=\s*(\{.+?\});\s*<\/script>/s)
                                || text.match(/window\.__INITIAL_STATE__\s*=\s*(\{[\s\S]+?\})\s*;?\s*(?:window|var|\n)/);
                    if (match) {
                        const state = JSON.parse(match[1]);
                        const prod = state?.pdpData?.product || state?.pageData?.product || state?.product || {};
                        if (prod.title || prod.name) {
                            return {
                                title:       prod.title || prod.name,
                                brand:       prod.brand || null,
                                price:       prod.price || null,
                                description: prod.description || null,
                                bullets:     prod.highlights || prod.keyFeatures || [],
                                images:      [],
                                category:    prod.category || null,
                                specifications: {},
                            };
                        }
                    }
                }
            } catch(_) {}

            // ── DOM extraction fallback ───────────────────────────────────────
            const text = sel => document.querySelector(sel)?.textContent?.trim()?.replace(/\s+/g,' ') ?? null;

            const titleSelectors = [
                'span.B_NuCI', 'h1.yhB1nd', 'span._35KyD6',
                'span[class*="B_NuCI"]', 'h1[class*="title"]',
                'div[class*="title"] h1', 'h1'
            ];
            let title = null;
            for (const sel of titleSelectors) {
                title = text(sel);
                if (title && title.length > 5) break;
            }

            // Bullets / highlights
            const bulletSelectors = [
                'div._3eNLam li', 'ul.ehgCNo li', 'div.Xop5oC li',
                'div[class*="highlights"] li', 'div[class*="keyFeatures"] li',
                'div._1mXcCf li', 'li[class*="_21Ahn0"]',
            ];
            let bullets = [];
            for (const sel of bulletSelectors) {
                const items = Array.from(document.querySelectorAll(sel))
                    .map(el => el.textContent.trim().replace(/\s+/g,' ')).filter(t => t.length > 3);
                if (items.length) { bullets = items; break; }
            }

            // Images
            const imgUrls = new Set();
            // From JSON-LD
            try {
                const ld = document.querySelector('script[type="application/ld+json"]');
                if (ld) {
                    const j = JSON.parse(ld.textContent);
                    const imgs = Array.isArray(j) ? j.flatMap(x=>x.image||[]) : (j.image||[]);
                    imgs.forEach(i => { if(typeof i==='string') imgUrls.add(i); });
                }
            } catch(_) {}
            // From scripts — Flipkart CDN
            Array.from(document.querySelectorAll('script:not([src])')).forEach(s => {
                const matches = [...(s.textContent||'').matchAll(/"(https:\/\/rukminim\d*\.flixcart\.com\/image\/[^"]+)"/g)];
                matches.forEach(m => imgUrls.add(m[1].replace(/\/\d+\/\d+\//,'/832/832/')));
            });
            // Fallback: img tags
            if (!imgUrls.size) {
                document.querySelectorAll('img[src*="rukminim"]').forEach(img => {
                    imgUrls.add(img.src.replace(/\/\d+\/\d+\//,'/832/832/'));
                });
            }

            // Price
            const priceEl = document.querySelector('div._30jeq3, div[class*="price"] .a-price-whole, span._30jeq3');
            const price   = priceEl ? priceEl.textContent.replace(/[^0-9]/g,'') : null;

            // Brand
            const brand = text('span._2b9gBs') || text('a.G6XhRU') || null;

            // Description
            const desc = text('div._1mXcCf') || text('div[class*="description"]') || null;

            return { title, brand, price: price ? '₹'+price : null, description: desc, bullets, images: [...imgUrls], specifications: {} };
        });

        await browser.close();

        if (!data.title) {
            throw new Error('FLIPKART_NO_TITLE: Could not extract product title. Page may have changed structure.');
        }

        return {
            platform: 'flipkart', url,
            title:          data.title,
            brand:          data.brand,
            manufacturer:   data.brand,
            bullets:        Array.isArray(data.bullets) ? data.bullets : [],
            description:    data.description,
            specifications: data.specifications || {},
            images:         (data.images || []).filter(u => u && u.startsWith('http')),
            category:       data.category || null,
            price:          data.price,
            currency:       'INR',
        };
    } catch (err) {
        await browser.close().catch(()=>{});
        throw err;
    }
}

// ── Meesho scraper ────────────────────────────────────────────────────────────

async function scrapeMeesho(url) {
    const browser = await launchBrowser();
    try {
        const context = await newContext(browser, 'en-IN');
        const page    = await context.newPage();
        page.setDefaultTimeout(TIMEOUT_MS);

        await page.route('**/*.{mp4,mp3,woff,woff2,ttf}', r => r.abort());
        await page.goto(url, { waitUntil: 'networkidle', timeout: TIMEOUT_MS });

        try {
            await page.waitForSelector('h1, [class*="pdp-title"], [class*="product-name"]', { timeout: 12000 });
        } catch (_) {}

        await page.waitForTimeout(1500);

        const data = await page.evaluate(() => {
            // Try __NEXT_DATA__ (Meesho is Next.js)
            try {
                const nd = JSON.parse(document.getElementById('__NEXT_DATA__')?.textContent || '{}');
                const pp = nd?.props?.pageProps;
                const prod = pp?.product || pp?.productDetail || pp?.initialData?.product || {};
                if (prod.name || prod.title) {
                    const images = (prod.image_urls || prod.images || []).map(i =>
                        typeof i === 'string' ? i.replace(/\/\d+\/\d+\//,'/600/600/') : null
                    ).filter(Boolean);
                    const bullets = [];
                    (prod.description_sections || []).forEach(sec => {
                        (sec.data || []).forEach(item => {
                            const t = typeof item === 'string' ? item : item?.text;
                            if (t) bullets.push(t.trim());
                        });
                    });
                    return {
                        title:       prod.name || prod.title,
                        brand:       prod.brand || prod.supplier_name || null,
                        price:       prod.price ? '₹' + prod.price : null,
                        description: prod.description || null,
                        bullets:     bullets.length ? bullets : (prod.highlights || []),
                        images,
                        category:    prod.category_name || null,
                        specifications: {},
                    };
                }
            } catch(_) {}

            // DOM fallback
            const text = sel => document.querySelector(sel)?.textContent?.trim()?.replace(/\s+/g,' ') ?? null;
            const title = text('h1') || text('[class*="pdp-title"]') || text('[class*="product-name"]');
            const price = text('[class*="price"]');
            const images = [...new Set(
                Array.from(document.querySelectorAll('img[src*="meesho"]'))
                    .map(i => i.src.replace(/\/\d+\/\d+\//,'/600/600/'))
            )];
            return { title, brand: null, price, description: null, bullets: [], images, specifications: {} };
        });

        await browser.close();

        if (!data.title) {
            throw new Error('MEESHO_NO_TITLE: Could not extract product title from Meesho page.');
        }

        return {
            platform: 'meesho', url,
            title:          data.title,
            brand:          data.brand,
            manufacturer:   data.brand,
            bullets:        data.bullets || [],
            description:    data.description,
            specifications: data.specifications || {},
            images:         (data.images || []).filter(u => u && u.startsWith('http')),
            category:       data.category || null,
            price:          data.price,
            currency:       'INR',
        };
    } catch (err) {
        await browser.close().catch(()=>{});
        throw err;
    }
}

// ── Main ──────────────────────────────────────────────────────────────────────

(async () => {
    const url = process.argv[2];
    if (!url) {
        console.log(JSON.stringify({ error: 'No URL provided. Usage: node scrape.js <url>' }));
        process.exit(1);
    }

    let platform;
    try {
        platform = detectPlatform(url);
    } catch (e) {
        console.log(JSON.stringify({ error: 'Invalid URL: ' + e.message }));
        process.exit(1);
    }

    try {
        let result;
        if (platform === 'flipkart') result = await scrapeFlipkart(url);
        else if (platform === 'meesho') result = await scrapeMeesho(url);
        else result = await scrapeAmazon(url);

        console.log(JSON.stringify(result));
        process.exit(0);
    } catch (err) {
        console.log(JSON.stringify({ error: err.message || String(err), platform }));
        process.exit(1);
    }
})();
