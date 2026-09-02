# 🛍️ Urban Thread — E-Commerce Web Application

A full-featured e-commerce platform for streetwear & fashion built with **vanilla PHP 8 + MySQL** (backend API) and **HTML/CSS/JavaScript + Bootstrap 5** (frontend). No frameworks, no build tools — just copy it into XAMPP and run.

## Quick Start (TL;DR)

1. Install [XAMPP](https://www.apachefriends.org/) (PHP 8.x + MySQL)
2. Copy this folder to `C:\xampp\htdocs\urban-thread`
3. Start **Apache** and **MySQL** in the XAMPP Control Panel
4. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin) → create database `urban_thread` → import `database.sql`
5. Visit [http://localhost:8080/urban-thread/](http://localhost:8080/urban-thread/) (or `http://localhost/urban-thread/` if you skipped the port change)

**Demo accounts** (see [Step 7](#step-7--demo-accounts)): `admin@urbanthread.com` / `admin123` (admin) · `john@example.com` / `user123` (customer)

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Prerequisites](#prerequisites)
- [Running Guide — XAMPP (detailed)](#running-guide--xampp-detailed)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [API Reference](#api-reference)
- [Virtual Card & Points System](#virtual-card--points-system)
- [Troubleshooting](#troubleshooting)
- [Known Issues & Security Notes](#known-issues--security-notes)
- [Production Readiness Checklist](#production-readiness-checklist)
- [Recommendations / Roadmap](#recommendations--roadmap)

## Features

### Storefront
- Product catalog with **category cards, search, size/color filters, price slider, sorting** (price, popularity, newest)
- Product detail pages with gallery, specifications and related items
- **Cart** with database persistence for logged-in users + localStorage cart for guests, **auto-merged on login**
- **Wishlist** (database-backed, one-click toggle from product cards)
- Checkout with shipping form, order summary, and order confirmation page
- Contact form with admin inbox + auto-reply email

### Accounts & Security
- Registration, login, logout with session management
- **"Remember me"** persistent login — auto-restores the session on return visits (token + session rotation on every use)
- **Google Sign-In** (OAuth 2.0 ID token flow, cryptographically verified against Google's JWKS)
- Role-based access (`admin` / `user`) enforced server-side on every admin API
- Login rate-limiting (5 attempts / 15 min per account or IP), registration & top-up rate limits
- CSRF protection (Origin/Referer validation on all POST endpoints), security headers, prepared statements everywhere

### Admin Panel (`admin.php`)
- Dashboard statistics (revenue, orders, users, products)
- Product management (add/edit, stock, categories)
- Order management (status updates)
- User management & contact-message inbox

### Virtual Card & Rewards
- **Urban Thread Virtual Card** — wallet with balance, points, and transaction history
- Earn **10 points per Rs 1** topped up; redeem **100 points = Rs 5**
- Pay for orders directly from the card balance

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x (vanilla, no framework), `mysqli` with prepared statements |
| Database | MySQL / MariaDB (phpMyAdmin for admin) |
| Frontend | HTML5, CSS3, vanilla JavaScript (ES6), Bootstrap 5.3 |
| Icons/Fonts | Bootstrap Icons, Google Fonts |
| Auth | PHP sessions, Google Identity (OAuth 2.0 ID tokens), OpenSSL for JWT verification |
| Server | Apache (XAMPP) — tested on Windows; works on any LAMP stack |

## Project Structure

```
urban-thread/
├── .htaccess                  # Apache hardening (blocks database.sql, security headers)
├── database.sql               # Full schema + demo data (import via phpMyAdmin)
├── README.md
│
├── index.php                  # Home page
├── shop.php  +  js/shop.js    # Product listing, filters, pagination
├── product.php + js/product.js# Product detail
├── cart.php  +  js/cart.js    # Cart page
├── checkout.php               # Checkout (COD or Urban Card)
├── order-confirmation.php     # Order success page
├── card.php                   # Virtual Card wallet page
├── account.php + js/account.js# User account dashboard
├── admin.php + js/admin.js    # Admin panel
├── reg.php  +  js/reg.js      # Login / Register (reg.PHP alias exists)
├── about.php, contact.php, faq.php, returns.php
├── includes/navbar.php        # Shared navbar include
│
├── api/
│   ├── config/db.php          # DB credentials, session/CSRF/security helpers
│   ├── auth/                  # login, register, logout, check, google
│   ├── products/              # list, single
│   ├── cart/                  # add, get, update, remove, sync
│   ├── wishlist/              # add, get, toggle, remove
│   ├── orders/                # create, list
│   ├── card/                  # topup, redeem, get
│   ├── admin/                 # stats + admin CRUD endpoints
│   ├── contact/submit.php     # Contact form
│   └── email/send.php         # Email templates + mail()
│
├── css/                       # Page-specific stylesheets
├── js/                        # Page-specific scripts
└── img/                       # Product/category images
```

## Prerequisites

- **XAMPP 8.x** (includes Apache, MySQL/MariaDB, phpMyAdmin) — [apachefriends.org](https://www.apachefriends.org/)
- Windows / macOS / Linux (guide below uses Windows)
- A modern browser
- *(Optional)* A Google Cloud OAuth Client ID if you want Google Sign-In
- *(Optional)* internet access for CDN assets (Bootstrap, icons, fonts) — the app loads them from jsDelivr

## Running Guide — XAMPP (detailed)

### Step 1 — Install XAMPP
1. Download XAMPP **8.2.x** (or any 8.x) from [apachefriends.org](https://www.apachefriends.org/)
2. Install to the default location `C:\xampp`
3. Launch the **XAMPP Control Panel** (`xampp-control.exe`)

### Step 2 — (Recommended) Configure Apache to run on port 8080
The project's default config uses port **8080** (avoids conflicts with IIS/ Skype/other apps):
1. In the XAMPP Control Panel click **Config** (next to Apache) → **Apache (httpd.conf)**
2. Change `Listen 80` → `Listen 8080` and `ServerName localhost:80` → `ServerName localhost:8080`
3. Save and (re)start Apache

> **Using port 80 instead?** No problem — see [Configuration](#configuration) to point `SITE_URL` at `http://localhost/urban-thread`.

### Step 3 — Copy the project into `htdocs`
Copy the whole `urban-thread` folder into the web root:

```
C:\xampp\htdocs\urban-thread
```

Final check: this file must exist → `C:\xampp\htdocs\urban-thread\index.php`

### Step 4 — Start Apache & MySQL
In the XAMPP Control Panel click **Start** on both **Apache** and **MySQL**. Both should turn green.
- Apache test: [http://localhost:8080/](http://localhost:8080/) → XAMPP dashboard
- MySQL runs on port **3306** (no dashboard, but phpMyAdmin connects to it)

### Step 5 — Create the database (import `database.sql`)
1. Open [http://localhost:8080/phpmyadmin](http://localhost:8080/phpmyadmin)
2. Click **New** in the left sidebar → database name `urban_thread` → **Create**
3. Select the `urban_thread` database → **Import** tab
4. Choose `C:\xampp\htdocs\urban-thread\database.sql` → **Import** (default settings)
5. You should see ~26 tables (users, products, cart, orders, virtual_cards, …) and a few seeded products

### Step 6 — Open the site
Visit: **[http://localhost:8080/urban-thread/](http://localhost:8080/urban-thread/)**

### Step 7 — Demo accounts
| Role | Email | Password | Landing page |
|---|---|---|---|
| Admin | `admin@urbanthread.com` | `admin123` | `admin.php` |
| Customer | `john@example.com` | `user123` | `account.php` |

> ⚠️ The seed passwords are stored in plaintext **only until first login** — the app transparently re-hashes them with bcrypt on the first successful login of each account. New registrations are always hashed.

### Step 8 — Optional: Google Sign-In & Email
- **Google Sign-In**: create an OAuth Client ID at [console.cloud.google.com](https://console.cloud.google.com/apis/credentials) (type *Web application*, authorized origin `http://localhost:8080`), then set your Client ID in `api/auth/google.php` and in the Google button markup on `reg.php`. Tokens are verified server-side against Google's public keys (JWKS).
- **Email**: PHP `mail()` is used for order confirmations and the contact form. On Windows it needs a mail server configured in `php.ini` (or a tool like **MailHog**/**Papercut**); the app works fine without it — email failures are logged to the `email_logs` table and never break the UI.

## Database Setup

- **File:** `database.sql` (project root)
- **Database name expected by the app:** `urban_thread`
- **Credentials expected:** MySQL user `root` with **empty password** (XAMPP default) — change in `api/config/db.php` if yours differ

### Key tables
| Tables | Purpose |
|---|---|
| `users`, `login_attempts`, `registration_logs`, `remember_tokens`, `google_login_logs` | accounts + auth security |
| `products`, `product_images`, `product_sizes`, `product_colors`, `product_specs`, `reviews` | catalog |
| `cart`, `wishlist` | per-user/guest shopping state |
| `orders`, `order_items` | purchase records |
| `virtual_cards`, `card_transactions`, `promo_codes` | wallet & rewards |
| `contact_messages`, `faq_categories`, `faq_items`, `benefits`, `site_content` | content & support |
| `email_templates`, `email_logs`, `chat_logs` | outbound mail & chatbot logs |

**Re-importing:** drop the `urban_thread` database in phpMyAdmin and import `database.sql` again to reset to a clean demo state (all users/orders/wallet data are wiped).

## Configuration

Everything lives in **`api/config/db.php`**:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'urban_thread');
define('DB_USER', 'root');
define('DB_PASS', '');                      // XAMPP default is empty

define('SITE_URL', 'http://localhost:8080/urban-thread');
```

| Setting | When to change |
|---|---|
| `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` | Different MySQL port, database name, or a password set for `root` |
| `SITE_URL` | Running on port **80** instead of 8080, a different folder name, or a real domain |

Other config you may touch:
- **Hardcoded base path** `/urban-thread/…` also appears in frontend `fetch()` calls (`js/*.js`). If you rename the folder, update those too (search for `/urban-thread/`).
- **API base** for products pagination is capped server-side (`api/products/list.php`: max `limit` 100, min `page` 1).
- **Google Client ID** in `api/auth/google.php` + the sign-in button on `reg.php`.

## API Reference

All endpoints return JSON (`{ success, message, ... }`). Session cookie auth; CORS is locked to the `SITE_URL` origin.

| Endpoint | Method | Auth | Purpose |
|---|---|---|---|
| `api/auth/register.php` | POST | — | Create account (rate-limited, hashed passwords, welcome bonus) |
| `api/auth/login.php` | POST | — | Login (rate-limited, `remember me` support) |
| `api/auth/logout.php` | POST | user | Destroy session + remember token |
| `api/auth/check.php` | GET | — | Session status; **auto-restores login from "remember me" cookie** |
| `api/auth/google.php` | POST | — | Google Sign-In (JWKS-verified ID token) |
| `api/products/list.php` | GET | — | Catalog: `category, search, min/max_price, sort, limit≤100, page` |
| `api/products/single.php` | GET | — | One product + images/specs |
| `api/cart/add.php` | POST | guest/user | Add item (stock-checked) |
| `api/cart/get.php` | GET | guest/user | Current cart |
| `api/cart/update.php` | POST | guest/user | Change quantity (clamped to stock) |
| `api/cart/remove.php` | POST | guest/user | Remove item |
| `api/cart/sync.php` | POST | user | Merge guest (localStorage) cart after login |
| `api/wishlist/toggle.php` | POST | user | Toggle wishlist item |
| `api/wishlist/get.php` | GET | user | List wishlist |
| `api/wishlist/add.php` / `remove.php` | POST | user | Add / remove |
| `api/orders/create.php` | POST | user | Place order (transactional, stock-locked, card payment verified) |
| `api/orders/list.php` | GET | user | User's order history |
| `api/card/get.php` | GET | user | Wallet: balance, points, transactions |
| `api/card/topup.php` | POST | user | Add funds (max 5/hour; **demo — no real payment gateway**) |
| `api/card/redeem.php` | POST | user | Points → balance (100 pts = Rs 5) |
| `api/contact/submit.php` | POST | — | Contact message (flood-limited) |
| `api/admin/stats.php` | GET | admin | Dashboard metrics |
| `api/admin/products/list.php` / `save.php` | GET / POST | admin | Product management (max 500 rows) |
| `api/admin/orders/list.php` / `update.php` | GET / POST | admin | Order management |
| `api/admin/users/list.php` | GET | admin | User list (max 500 rows, real total) |
| `api/admin/messages/list.php` / `update.php` | GET / POST | admin | Contact inbox (max 500 rows) |

## Virtual Card & Points System

The **Urban Thread Virtual Card** is a demo wallet (no real money):

- Every new account gets a card with **Rs 50 + 150 points** welcome balance
- **Add Funds** (top-up): Rs 10 – Rs 50,000 per top-up, max **5 top-ups/hour** — instantly credits the card and earns **10 points per Rs 1**
- **Redeem points**: 100 points = Rs 5, credited straight to the card balance
- **Pay at checkout**: choose *Urban Card* as payment method — the order total is deducted from the balance inside the same DB transaction that creates the order (no double-spend)
- Full transaction ledger on the **Virtual Card page** (`card.php`, login required)

> ⚠️ **This is a demo flow.** Top-ups are not real payments. For production, integrate a payment gateway (Stripe / JazzCash / Easypaisa) and make top-ups admin-approved or gateway-verified.

## Troubleshooting

| Problem | Cause & Fix |
|---|---|
| **"Connection failed" / DB errors** | MySQL not started, or wrong credentials in `api/config/db.php`. Start MySQL in XAMPP; XAMPP default is user `root`, empty password. |
| **phpMyAdmin 404** | Apache not running, or you forgot the port: use `http://localhost:8080/phpmyadmin`. |
| **Page loads but products don't appear** | Database not imported, or DB name isn't exactly `urban_thread`. Re-check Step 5. Open browser DevTools → Network to see the `api/products/list.php` response. |
| **Blank white page** | PHP fatal error. Check `C:\xampp\php\php.ini` → `display_errors=On` *temporarily* for debugging, or check `C:\xampp\php\logs\php_error_log`. |
| **Apache won't start (port busy)** | Another app holds port 80/8080. Change to 8080 in `httpd.conf` (Step 2), or stop the conflicting service (Skype, IIS, VMware). |
| **MySQL won't start** | Port 3306 in use (another MySQL/Skype). Change port in `my.ini` or stop the other service. Also delete `C:\xampp\mysql\data\aria_log.*` corruption locks after a crash. |
| **Site works at `/` but assets 404** | Folder must be named exactly `urban-thread` inside `htdocs`, and `SITE_URL` must match (`http://localhost:8080/urban-thread`). |
| **Login says "Too many attempts"** | Built-in throttle: 5 failed logins / 15 min per account or IP. Wait 15 minutes or restart MySQL (attempts are in the `login_attempts` table). |
| **Cart items disappear after login** | They don't — the guest cart is merged by `api/cart/sync.php` on first page load after login. Hard-refresh (Ctrl+F5). |
| **"View All Products" shows only some products** | The API caps `limit` at **100**. If you have more than 100 active products, use pagination/filtering instead. |
| **Emails never arrive** | Windows PHP has no mail server by default. Install MailHog/Papercut for local testing, or configure SMTP via `php.ini`. Failures are recorded in `email_logs` and never break checkout. |
| **Changes to CSS/JS not visible** | Browser cache. Hard-refresh with **Ctrl + F5**. |
| **Google Sign-In fails** | Client ID not set / wrong authorized origin in Google Cloud Console, or no internet (token verification needs to reach Google's JWKS endpoint). |
| **`reg.PHP` vs `reg.php`** | Windows/XAMPP is case-insensitive, but Linux hosting isn't. Always link to `reg.php`. |

**Windows tips**
- Run the XAMPP Control Panel **as Administrator** to install the Apache/MySQL services.
- Antivirus can slow `mail()` and file writes — add `C:\xampp` to exclusions if pages feel sluggish.

## Known Issues & Security Notes

**Already hardened in this codebase**
- ✅ All product/order/cart queries use **prepared statements** (no SQL injection)
- ✅ **Passwords hashed** with bcrypt (`password_hash`); legacy plaintext rows are transparently re-hashed on first successful login
- ✅ **Google Sign-In tokens cryptographically verified** against Google's public keys (JWKS) — forged tokens are rejected
- ✅ **CSRF protection** (Origin/Referer validation) on all POST endpoints
- ✅ **Session fixation protection** (session ID rotated on every login/restore)
- ✅ **Rate limits**: login (5/15 min), registration, wallet top-up (5/hour), contact form (3/10 min)
- ✅ **Transactional checkout** with row-locked stock checks (no oversell / double-spend)
- ✅ All endpoints send CORS restricted to the configured origin (no wildcards) + security headers
- ✅ `.htaccess` blocks `database.sql`, dotfiles and backup files from being served
- ✅ XSS: user-controlled output escaped server-side (`sanitize`, `htmlspecialchars`) and client-side (`escapeHtml`)

**Functional gaps (known, non-breaking)**
- ✅ "Remember me" auto-restores sessions on return visits (`api/auth/check.php`), rotating both token and session ID after each use
- No password **reset/change**, email **verification**, or account **deletion** flows yet (schema columns exist)
- `promo_codes` table exists but checkout does not consume codes yet
- The chat widget calls a free external Hugging Face API — fine for a demo, but it sends chat text to a third party
- Admin list endpoints return at most the **500 most recent rows** (hard cap); not fully paginated yet

**Before real money / real users**
- Top-ups are **not real payments** — wire in a payment gateway before going live
- Demo accounts (`admin123` / `user123`) must be removed or re-passworded
- Run behind **HTTPS**; set cookie `secure` flags accordingly (the code already sets `secure` when HTTPS is detected)
- `database.sql` contains demo data — never ship it to a production server (`.htaccess` blocks it, but removing the file is safer)

## Production Readiness Checklist

- [ ] Set a MySQL password and update `api/config/db.php`
- [ ] Point `SITE_URL` (and the `/urban-thread/` paths in `js/*.js`) at the real domain
- [ ] Enable HTTPS + HSTS; confirm cookies now carry the `Secure` flag
- [ ] Delete demo accounts and any real PII from the database
- [ ] Remove `database.sql` from the server (keep it in version control only)
- [ ] Integrate a real payment gateway for wallet top-ups
- [ ] Configure real SMTP (PHPMailer or similar) for transactional email
- [ ] Set `display_errors=Off` in `php.ini` (error logging on)
- [ ] Schedule cleanup of expired `remember_tokens` and old `login_attempts`
- [ ] Add server-side monitoring/backups (MySQL dump cron, error-log alerts)

## Recommendations / Roadmap

| Priority | Item |
|---|---|
| High | Password reset (email token) + change-password in account page |
| High | Real payment gateway for the virtual card |
| Medium | Full pagination for admin lists; promo-code support at checkout |
| Medium | Email verification on signup |
| Low | Product reviews UI (tables exist), admin dashboard charts, multi-image uploads |
| Low | Replace the external chatbot API with a self-hosted/proxied service |

---

*Urban Thread — built as a full-stack learning project; hardened during a security review pass. See the commit history for the detailed change list.*




