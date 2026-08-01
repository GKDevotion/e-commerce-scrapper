#!/usr/bin/env node
/**
 * Multi-Platform Scraper — Amazon / Flipkart / Meesho
 * Uses playwright-extra + stealth plugin to bypass Cloudflare (Meesho).
 * Falls back to plain playwright if stealth not installed yet.
 *
 * Usage:  node scrape.js "<url>"
 * Output: single JSON line on stdout
 *
 * Install stealth:  npm install  (package.json includes it)
 */

let chromiumLauncher;
try {
    const { chromium: extra } = require('playwright-extra');
    const Stealth = require('puppeteer-extra-plugin-stealth');
    extra.use(Stealth());
    chromiumLauncher = extra;
    console.error('[scraper] playwright-extra stealth loaded');
} catch (_) {
    const { chromium } = require('playwright');
    chromiumLauncher = chromium;
    console.error('[scraper] stealth not available — using plain playwright');
}

const TIMEOUT_MS  = parseInt(process.env.SCRAPER_TIMEOUT_MS || '90000', 10);
const HEADLESS    = process.env.SCRAPER_HEADLESS !== 'false';

// ── Shared ────────────────────────────────────────────────────────────────────

function detectPlatform(url) {
    const host = new URL(url).hostname.toLowerCase();
    if (host.includes('flipkart.com')) return 'flipkart';
    if (host.includes('meesho.com'))   return 'meesho';
    return 'amazon';
}

async function launchBrowser() {
    return chromiumLauncher.launch({
        headless: HEADLESS,
        args: [
            '--disable-blink-features=AutomationControlled',
            '--disable-dev-shm-usage',
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-infobars',
            '--window-size=1366,900',
            '--disable-features=IsolateOrigins,site-per-process',
        ],
    });
}

async function newPage(browser, locale = 'en-IN') {
    const ctx = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        viewport:   { width: 1366, height: 900 },
        locale,
        timezoneId: 'Asia/Kolkata',
        extraHTTPHeaders: {
            'Accept-Language': 'en-IN,en-GB;q=0.9,en;q=0.8',
        },
    });
    await ctx.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver',  { get: () => undefined });
        Object.defineProperty(navigator, 'plugins',    { get: () => [1,2,3,4,5] });
        Object.defineProperty(navigator, 'languages',  { get: () => ['en-IN','en'] });
        window.chrome = { runtime: {} };
    });
    return ctx.newPage();
}

// ── Amazon ────────────────────────────────────────────────────────────────────

function extractAsin(url) {
    for (const p of [/\/dp\/([A-Z0-9]{10})/i, /\/product\/([A-Z0-9]{10})/i, /\/gp\/product\/([A-Z0-9]{10})/i]) {
        const m = url.match(p); if (m) return m[1].toUpperCase();
    }
    return null;
}

async function scrapeAmazon(url) {
    const asin      = extractAsin(url);
    const targetUrl = asin ? (() => { try { const u=new URL(url); return `${u.protocol}//${u.host}/dp/${asin}`; } catch{return url;} })() : url;
    const browser   = await launchBrowser();
    try {
        const page = await newPage(browser, 'en-US');
        page.setDefaultTimeout(TIMEOUT_MS);
        await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: TIMEOUT_MS });
        await page.waitForTimeout(1200 + Math.random()*800);
        await page.mouse.move(200, 300);
        await page.mouse.wheel(0, 600);
        await page.waitForTimeout(500);
        const html = await page.content();
        for (const sig of ['Type the characters you see','Robot Check',"make sure you're not a robot"]) {
            if (html.includes(sig)) throw new Error(`BLOCKED: Amazon bot-check "${sig}"`);
        }
        if (!await page.locator('#productTitle, #title').count()) {
            throw new Error('NO_PRODUCT_MARKUP: No title element on Amazon page.');
        }
        const data = await page.evaluate(() => {
            const txt = sel => document.querySelector(sel)?.textContent.trim().replace(/\s+/g,' ') ?? null;
            const title = txt('#productTitle') || txt('#title');
            let brand = txt('#bylineInfo') || null;
            if (brand) brand = brand.replace(/^(Brand:|Visit the|Store)\s*/i,'').replace(/\s*Store$/i,'').trim();
            const bullets = Array.from(document.querySelectorAll('#feature-bullets li span.a-list-item'))
                .map(el=>el.textContent.trim().replace(/\s+/g,' ')).filter(t=>t.length>10).slice(0,10);
            const description = txt('#productDescription p') || txt('#productDescription') || null;
            const specs = {};
            document.querySelectorAll('#productDetails_techSpec_section_1 tr, #prodDetails tr').forEach(row=>{
                const c = row.querySelectorAll('td,th'); if (c.length>=2) specs[c[0].textContent.trim()]=c[1].textContent.trim();
            });
            let images = [];
            for (const s of Array.from(document.querySelectorAll('script')).map(s=>s.textContent||'')) {
                const hi=[...(s.matchAll(/"hiRes":"(https:[^"]+)"/g))].map(m=>m[1]);
                if (hi.length){images=hi;break;}
            }
            if (!images.length) {
                const img=document.querySelector('#landingImage,#imgBlkFront');
                if (img) images=[img.getAttribute('data-old-hires')||img.src].filter(Boolean);
            }
            const category = Array.from(document.querySelectorAll('#wayfinding-breadcrumbs_feature_div a'))
                .map(a=>a.textContent.trim()).filter(Boolean).join(' > ');
            let price=null;
            for (const sel of ['.a-price .a-offscreen','#priceblock_ourprice','#price']) {
                const el=document.querySelector(sel); if(el){const n=parseFloat(el.textContent.replace(/[^0-9.]/g,''));if(n){price=n;break;}}
            }
            return {title,brand,bullets,description,specs,images,category,price};
        });
        await browser.close();
        return { platform:'amazon', asin, url:targetUrl, title:data.title, brand:data.brand,
            manufacturer:data.specs['Manufacturer']||null, bullets:data.bullets,
            description:data.description, specifications:data.specs, images:data.images,
            category:data.category, price:data.price,
            currency:targetUrl.includes('amazon.in')?'INR':'USD' };
    } catch(err){ await browser.close().catch(()=>{}); throw err; }
}

// ── Flipkart ──────────────────────────────────────────────────────────────────

async function scrapeFlipkart(url) {
    const browser = await launchBrowser();
    try {
        const page = await newPage(browser, 'en-IN');
        page.setDefaultTimeout(TIMEOUT_MS);
        await page.route('**/*.{png,jpg,jpeg,gif,webp,svg,woff,woff2,ttf,mp4,mp3}', r=>r.abort());
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: TIMEOUT_MS });
        try { await page.waitForSelector('span.B_NuCI,h1.yhB1nd,span._35KyD6,h1', { timeout:15000 }); } catch(_){}
        await page.waitForTimeout(1500);
        await page.mouse.wheel(0, 400);
        await page.waitForTimeout(500);
        if ((await page.content()).length < 10000) throw new Error('Flipkart page too short — likely blocked');

        const data = await page.evaluate(() => {
            try {
                const nd=JSON.parse(document.getElementById('__NEXT_DATA__')?.textContent||'{}');
                const pp=nd?.props?.pageProps;
                const prod=pp?.product||pp?.pdpData?.product||pp?.initialData?.product||{};
                if (prod.name||prod.title) return {
                    title:prod.name||prod.title, brand:prod.brand||prod.brandName||null,
                    price:prod.price||prod.mrp||null, description:prod.description||prod.shortDescription||null,
                    bullets:prod.highlights||prod.keyFeatures||[],
                    images:(prod.images||prod.imageUrls||[]).map(i=>typeof i==='string'?i:i?.url).filter(Boolean),
                    category:prod.category||null, specifications:prod.specifications||{}
                };
            } catch(_){}
            // DOM fallback
            const txt=sel=>document.querySelector(sel)?.textContent?.trim()?.replace(/\s+/g,' ')??null;
            let title=null;
            for (const s of ['span.B_NuCI','h1.yhB1nd','span._35KyD6','h1']) { title=txt(s); if(title&&title.length>5)break; }
            let bullets=[];
            for (const s of ['div._3eNLam li','ul.ehgCNo li','div[class*="highlights"] li']) {
                const items=Array.from(document.querySelectorAll(s)).map(el=>el.textContent.trim()).filter(t=>t.length>3);
                if(items.length){bullets=items;break;}
            }
            const imgUrls=new Set();
            Array.from(document.querySelectorAll('script:not([src])')).forEach(s=>{
                [...(s.textContent||'').matchAll(/"(https:\/\/rukminim\d*\.flixcart\.com\/image\/[^"]+)"/g)]
                    .forEach(m=>imgUrls.add(m[1].replace(/\/\d+\/\d+\//,'/832/832/')));
            });
            const price=txt('div._30jeq3')||txt('span._30jeq3');
            return { title, brand:txt('span._2b9gBs')||null,
                price:price?'₹'+price.replace(/[^0-9]/g,''):null,
                description:txt('div._1mXcCf')||null,
                bullets, images:[...imgUrls], specifications:{} };
        });

        await browser.close();
        if (!data.title) throw new Error('FLIPKART_NO_TITLE: Could not extract title.');
        return { platform:'flipkart', url, title:data.title, brand:data.brand, manufacturer:data.brand,
            bullets:Array.isArray(data.bullets)?data.bullets:[], description:data.description,
            specifications:data.specifications||{}, images:(data.images||[]).filter(u=>u&&u.startsWith('http')),
            category:data.category||null, price:data.price, currency:'INR' };
    } catch(err){ await browser.close().catch(()=>{}); throw err; }
}

// ── Meesho ────────────────────────────────────────────────────────────────────

async function scrapeMeesho(url) {
    const browser = await launchBrowser();
    try {
        const page = await newPage(browser, 'en-IN');
        page.setDefaultTimeout(TIMEOUT_MS);

        // Allow all requests — Cloudflare needs to run its JS
        // Only block truly unnecessary heavy media
        await page.route('**/*.{mp4,mp3,pdf}', r=>r.abort());

        // Set headers that make us look like a real Chrome browser
        await page.setExtraHTTPHeaders({
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-IN,en-GB;q=0.9,en;q=0.8',
            'sec-fetch-dest': 'document',
            'sec-fetch-mode': 'navigate',
            'sec-fetch-site': 'none',
            'sec-fetch-user': '?1',
            'Upgrade-Insecure-Requests': '1',
        });

        console.error('[meesho] navigating...');

        // Use 'load' so Cloudflare JS executes fully before we check content
        try {
            await page.goto(url, { waitUntil: 'load', timeout: TIMEOUT_MS });
        } catch(_) {
            // load timeout is common with CF — continue anyway
            console.error('[meesho] load timeout — continuing');
        }

        // Give Cloudflare challenge time to execute and redirect (5-10s typical)
        await page.waitForTimeout(7000);

        // If CF challenge still visible, wait longer
        const cfActive = await page.locator(
            '#cf-wrapper, .cf-browser-verification, #challenge-form, #challenge-running'
        ).count();
        if (cfActive > 0) {
            console.error('[meesho] CF challenge still active, waiting 12s...');
            await page.waitForTimeout(12000);
        }

        // Human-like interaction after CF passes
        await page.mouse.move(300 + Math.random()*300, 200 + Math.random()*200);
        await page.waitForTimeout(800);
        await page.mouse.wheel(0, 200);
        await page.waitForTimeout(800);

        // Wait for product content
        try {
            await page.waitForSelector(
                'h1, [class*="pdp"], [class*="product-name"], [class*="ProductTitle"]',
                { timeout: 20000 }
            );
        } catch(_) { console.error('[meesho] product selector timeout — extracting anyway'); }

        await page.waitForTimeout(2000);

        const bodyLen = (await page.content()).length;
        console.error(`[meesho] page size: ${bodyLen} bytes`);

        if (bodyLen < 5000) {
            throw new Error(
                `MEESHO_CF_BLOCKED: Page only ${bodyLen} bytes after ${7000+cfActive*12000}ms wait. ` +
                'Cloudflare detected headless browser. Fix: npm install (adds stealth plugin), then restart queue.'
            );
        }

        const data = await page.evaluate(() => {
            // Try __NEXT_DATA__ first (Meesho = Next.js)
            try {
                const nd = JSON.parse(document.getElementById('__NEXT_DATA__')?.textContent || '{}');
                const pp = nd?.props?.pageProps;
                const prod = pp?.product || pp?.productDetail || pp?.initialData?.product || {};
                if (prod.name || prod.title) {
                    const images = (prod.image_urls || prod.images || [])
                        .map(i=>typeof i==='string'?i.replace(/\/\d+\/\d+\//,'/600/600/'):null).filter(Boolean);
                    const bullets = [];
                    (prod.description_sections||[]).forEach(s=>{
                        (s.data||[]).forEach(item=>{const t=typeof item==='string'?item:item?.text; if(t)bullets.push(t.trim());});
                    });
                    if (!bullets.length && prod.highlights) bullets.push(...(Array.isArray(prod.highlights)?prod.highlights:[]));
                    const specs = {};
                    (prod.product_attributes||prod.attributes||[]).forEach(a=>{ if(a.name&&a.value)specs[a.name]=a.value; });
                    return { title:prod.name||prod.title, brand:prod.brand||prod.supplier_name||null,
                        price:prod.price?'₹'+prod.price:(prod.mrp?'₹'+prod.mrp:null),
                        description:prod.description||prod.short_description||null,
                        bullets, images, specifications:specs, category:prod.category_name||prod.category||null };
                }
            } catch(_){}
            // OG meta fallback
            const og = key => document.querySelector(`meta[property="og:${key}"]`)?.content || null;
            const images = [...new Set(Array.from(document.querySelectorAll('img[src*="meesho"]'))
                .map(i=>i.src.replace(/\/\d+\/\d+\//,'/600/600/')).filter(u=>u.startsWith('http')))];
            return { title:document.querySelector('h1')?.textContent?.trim()||og('title'),
                brand:null, price:null, description:og('description'), bullets:[], images, specifications:{} };
        });

        await browser.close();
        if (!data.title) throw new Error('MEESHO_NO_TITLE: No product title found.');

        return { platform:'meesho', url, title:data.title, brand:data.brand, manufacturer:data.brand,
            bullets:Array.isArray(data.bullets)?data.bullets:[], description:data.description,
            specifications:data.specifications||{}, images:(data.images||[]).filter(u=>u&&u.startsWith('http')),
            category:data.category||null, price:data.price, currency:'INR' };
    } catch(err){ await browser.close().catch(()=>{}); throw err; }
}

// ── Main ──────────────────────────────────────────────────────────────────────

(async () => {
    const url = process.argv[2];
    if (!url) {
        process.stdout.write(JSON.stringify({ error: 'No URL. Usage: node scrape.js <url>' }) + '\n');
        process.exit(1);
    }
    let platform;
    try { platform = detectPlatform(url); } catch(e) {
        process.stdout.write(JSON.stringify({ error: 'Invalid URL: ' + e.message }) + '\n');
        process.exit(1);
    }
    try {
        let result;
        if (platform === 'flipkart')    result = await scrapeFlipkart(url);
        else if (platform === 'meesho') result = await scrapeMeesho(url);
        else                            result = await scrapeAmazon(url);
        process.stdout.write(JSON.stringify(result) + '\n');
        process.exit(0);
    } catch(err) {
        process.stdout.write(JSON.stringify({ error: err.message || String(err), platform }) + '\n');
        process.exit(1);
    }
})();
