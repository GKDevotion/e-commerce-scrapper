# Amazon AI Listing Builder

> AI-powered SaaS platform that allows Amazon sellers to import a product URL and automatically generate a unique, Amazon-compliant listing draft under their own brand.

---

## 🚀 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 + PHP 8.4 |
| Frontend | Bootstrap 5.3 (CDN) + Vanilla JS |
| Database | MySQL 8.0 |
| Queue | Redis + Laravel Horizon |
| AI | OpenAI GPT-4o |
| Scraping | PHP DOM + HTTP (Puppeteer optional) |
| Payments | Razorpay + Stripe |
| Marketplace | Amazon SP-API |

---

## 📁 Project Structure

```
amazon-listing-builder/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/          # Login, Register, ForgotPassword
│   │   │   ├── Admin/         # AdminController (dashboard, users, plans, AI settings)
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductImportController.php
│   │   │   ├── AiGenerationController.php
│   │   │   ├── ExportController.php
│   │   │   ├── BillingController.php
│   │   │   └── ProfileController.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Plan.php
│   │   ├── Subscription.php
│   │   ├── Payment.php
│   │   ├── ProductImport.php
│   │   ├── AiGeneration.php
│   │   ├── Export.php
│   │   ├── PromptTemplate.php
│   │   ├── ApiLog.php
│   │   └── AuditLog.php
│   ├── Services/
│   │   ├── Scraper/AmazonScraperService.php   # HTTP scraper + DOM parser
│   │   ├── AI/AiGenerationService.php          # OpenAI GPT-4o integration
│   │   ├── Amazon/AmazonSpApiService.php        # SP-API publish
│   │   └── Export/ExportService.php             # CSV, Excel, JSON, PDF, Flat File
│   └── Jobs/
│       └── ScrapeAmazonProduct.php             # Async queue job
├── database/
│   ├── migrations/                             # 3 migration files
│   └── seeders/DatabaseSeeder.php              # Plans + Admin + Demo users
├── resources/views/
│   ├── layouts/
│   │   ├── app.blade.php                       # Main sidebar layout (dark sidebar)
│   │   └── auth.blade.php                      # Split-panel auth layout
│   ├── welcome.blade.php                       # Public landing page
│   ├── auth/                                   # login, register, forgot-password
│   ├── dashboard/                              # index, profile
│   ├── listings/                               # create, show, imports, generation
│   │   └── partials/listing-column.blade.php   # Comparison column
│   ├── billing/                                # plans, subscription
│   └── admin/                                  # dashboard, users/, plans/, ai-settings, analytics
├── routes/web.php
└── config/services.php
```

---

## ⚡ Quick Setup

### 1. Clone & Install

```bash
git clone <your-repo>
cd amazon-listing-builder
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configure .env

```env
# Database
DB_DATABASE=amazon_listing_builder
DB_USERNAME=root
DB_PASSWORD=your_password

# Required for AI
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o

# Optional: Amazon SP-API
AMAZON_SP_API_CLIENT_ID=...
AMAZON_SP_API_CLIENT_SECRET=...
AMAZON_SP_API_REFRESH_TOKEN=...

# Optional: Payments
RAZORPAY_KEY_ID=...
RAZORPAY_KEY_SECRET=...
STRIPE_KEY=...
STRIPE_SECRET=...
```

### 3. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 4. Run the Application

```bash
# Terminal 1: Web server
php artisan serve

# Terminal 2: Queue worker (for async scraping)
php artisan queue:work --queue=default

# Optional: Horizon (queue dashboard)
php artisan horizon
```

### 5. Access

| URL | Description |
|-----|-------------|
| `http://localhost:8000` | Landing page |
| `http://localhost:8000/login` | User login |
| `http://localhost:8000/register` | User registration |
| `http://localhost:8000/dashboard` | User dashboard |
| `http://localhost:8000/admin/dashboard` | Admin panel |

**Default credentials (after seeding):**
- Admin: `admin@amazonlistingbuilder.com` / `Admin@1234`
- Demo:  `demo@amazonlistingbuilder.com` / `Demo@1234`

---

## 🔄 User Flow

```
1. Register / Login
   ↓
2. New Listing → Enter Amazon URL + Brand + Manufacturer + Keywords
   ↓
3. System scrapes Amazon (async job via Redis queue)
   ↓
4. GPT-4o generates: Title, 5 Bullets, Description, Search Terms, SEO Keywords, Highlights, A+ Content
   ↓
5. All original brand/manufacturer references replaced with user's brand
   ↓
6. Side-by-side comparison (Original vs Generated) with click-to-copy
   ↓
7. Export: CSV / Excel / Amazon Flat File / JSON / PDF
   ↓ (Pro+ users)
8. Publish directly to Amazon Seller Central via SP-API
```

---

## 📦 Database Tables

| Table | Purpose |
|-------|---------|
| `users` | User accounts with plan, usage tracking, role |
| `plans` | Subscription plans (Free/Starter/Pro/Enterprise) |
| `subscriptions` | Active subscriptions, billing cycles |
| `payments` | Payment records (Razorpay/Stripe) |
| `product_imports` | Raw scraped Amazon data |
| `ai_generations` | AI-generated listing content |
| `exports` | Export file records |
| `prompt_templates` | Admin-configurable AI prompts |
| `api_logs` | Service API call logs |
| `audit_logs` | User action audit trail |

---

## 🎨 Design System

| Token | Value |
|-------|-------|
| Primary Red | `#E31837` |
| Dark Red | `#b01028` |
| Black | `#0d0d0d` |
| Font Heading | Sora (800/900) |
| Font Body | Inter (400/600) |
| Border Radius | 10–16px |

---

## 🔮 Roadmap (Future Modules)

- [ ] Flipkart Listing Generator
- [ ] Meesho Listing Generator
- [ ] eBay Listing Generator
- [ ] WooCommerce / Shopify import
- [ ] Bulk URL import (CSV upload)
- [ ] Bulk AI generation queue
- [ ] Team / Agency dashboard
- [ ] Multi-marketplace SP-API (IN, UK, DE, CA)
- [ ] Playwright/Puppeteer scraper fallback
- [ ] A/B listing testing
- [ ] Listing performance tracking

---

## 🔐 Security

- Laravel CSRF protection on all forms
- Rate limiting on scraper and AI endpoints
- Encrypted API keys (use `php artisan encrypt`)
- Role-based access (admin/user)
- Soft deletes for audit trail
- Input validation on all controllers
- SQL injection prevention via Eloquent ORM

---

## 💳 Payment Integration

### Razorpay (India)
Set `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET` in `.env`. The billing controller creates a subscription record and redirects to Razorpay checkout.

### Stripe (International)
Set `STRIPE_KEY` and `STRIPE_SECRET`. Use `php artisan stripe:webhook` to handle webhook events.

---

## 🤖 AI Content Rules

The AI system:
- ✅ Generates completely unique product titles
- ✅ Writes 5 SEO-optimized bullet points
- ✅ Creates 250–400 word product descriptions
- ✅ Produces backend search terms (under 250 bytes)
- ✅ Suggests A+ content modules
- ❌ Never copies competitor text verbatim
- ❌ Never uses original brand/manufacturer names
- ❌ Never makes unverifiable claims
- ❌ Never reproduces A+ content or reviews
