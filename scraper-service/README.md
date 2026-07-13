# Amazon Scraper Sidecar (Playwright)

This is a small Node.js process that does the actual Amazon scraping using a
real headless Chromium browser via [Playwright](https://playwright.dev).

## Why this exists

Plain HTTP requests (no JS execution, no real browser fingerprint) get
blocked or served bot-check/CAPTCHA pages by Amazon almost immediately —
especially from server/VPS IP ranges. A real browser:

- Executes JavaScript (Amazon's product page partially renders via JS)
- Has a genuine TLS/Chrome fingerprint
- Can simulate basic human behavior (mouse movement, scrolling)

This gets past Amazon's bot detection far more reliably than the plain-HTTP
fallback in `app/Services/Scraper/AmazonScraperService.php`.

## Setup (one-time, on the server)

```bash
cd scraper-service
npm install
```

`npm install` automatically runs `playwright install --with-deps chromium`
via the `postinstall` script, which downloads a real Chromium binary
(~150–300MB) and any required OS-level libraries.

> **Note:** On some minimal Linux servers you may need to install system
> dependencies manually first:
> ```bash
> npx playwright install-deps chromium
> ```

## Test it standalone

```bash
node scrape.js "https://www.amazon.in/dp/B0FHXY7VZ5"
```

On success, prints a JSON object with `title`, `bullet_points`,
`description`, `images`, etc. On failure, prints `{"error": "..."}` and
exits with code 1.

## How Laravel uses this

`app/Services/Scraper/PlaywrightScraperService.php` shells out to this
script via Symfony Process whenever a product import is queued
(`app/Jobs/ScrapeAmazonProduct.php`). If this sidecar isn't installed yet,
the job automatically falls back to the plain HTTP scraper — which works
sometimes, but is **not reliable** against Amazon specifically.

## Configuration

Set in your Laravel `.env`:

```env
SCRAPER_DRIVER=playwright        # or 'http' to force the unreliable HTTP-only scraper
SCRAPER_NODE_BINARY=node         # path to node binary if not on PATH
SCRAPER_PLAYWRIGHT_TIMEOUT=60    # seconds before the browser process is killed
```

## Troubleshooting

**"Playwright scraper is not installed"**
→ Run `cd scraper-service && npm install` on the server, not just locally.

**Still getting blocked / CAPTCHA even with Playwright**
→ Amazon may have flagged your server's IP specifically. Options:
  - Add delays between scrape requests (avoid bursts)
  - Route scraper traffic through a residential/rotating proxy
  - Reduce scrape frequency per IP

**Works locally but fails in production**
→ Production servers are usually missing Chromium's system dependencies.
Run `npx playwright install-deps chromium` as root/sudo on the server.

**Process timeout errors**
→ Increase `SCRAPER_PLAYWRIGHT_TIMEOUT` in `.env`, and make sure the queue
job's `$timeout` property in `ScrapeAmazonProduct.php` is also high enough
(currently 150 seconds) to allow the full browser scrape to complete.
