# TinyShop

Multi-vendor e-commerce platform. Sellers sign up, build a storefront, and start selling — no technical knowledge required.

Built with PHP 8.1, Slim 4, Smarty 5, and vanilla JS. No frameworks on the frontend. Mobile-first, SPA-capable, theme-aware.

---

## Architecture

```
index.php                  Front controller (checks install lock, boots Slim)
install.php                Self-contained installer (OOP, runs once)
config/                    PHP config arrays (no .env, no dotenv)
src/
  App.php                  Bootstrap: DI container, routes, middleware, error handling
  Controllers/
    Api/                   JSON endpoints (auth, products, orders, checkout, admin)
    Admin/                 Admin panel pages
    DashboardController    Seller dashboard
    ShopController         Public storefront rendering
    PageController         Landing, pricing, help, auth pages
  Models/                  Active Record models (PDO, prepared statements)
  Services/                Auth, DB, Config, Upload, Mailer, Hooks, Theme, PlanGuard
    Gateways/              Stripe, PayPal, M-Pesa, Pesapal, COD
    OAuth/                 Google, Instagram, TikTok
    Importers/             Shopify, WooCommerce product import
  Middleware/              PSR-15 (AuthGuard, AdminGuard, CSRF, RateLimit, SecureHeaders)
  Enums/                   UserRole, OrderStatus, FieldType
templates/                 Smarty .tpl files (layouts, partials, pages)
themes/                    Storefront themes (classic)
addons/                    WP-style hook-based extensions
assets/                    Source CSS (SCSS) and JS
public/                    Compiled output (css, js, img, uploads)
var/                       Runtime (compiled templates, cache) — gitignored
```

96 PHP classes. Strict types everywhere. All classes are `final` unless abstract. Constructor injection via PHP-DI. No loose functions.

## Tech Stack

| Layer | Choice |
|---|---|
| Runtime | PHP 8.1+ on Apache (vhost, mod_rewrite) |
| Framework | Slim 4 + PHP-DI |
| Templates | Smarty 5 |
| Database | MySQL 8 via PDO |
| Frontend | jQuery, vanilla CSS (mobile-first, no frameworks) |
| Payments | Stripe, PayPal, M-Pesa STK Push, Pesapal, COD |
| Storage | Local filesystem or AWS S3 |
| Email | PHPMailer 7 (SMTP configured in admin) |
| Auth | Session-based + OAuth (Google, Instagram, TikTok) |
| Static analysis | PHPStan level 6, PHP CS Fixer (PER-CS2) |

## Setup

**Requirements:** PHP 8.1+, MySQL 8, Apache with mod_rewrite, Composer, Node 18+

```bash
git clone git@github.com:Davisonpro/tinyshop.git
cd tinyshop

composer install
npm install
npm run build
```

Create the config files:

```bash
cp config/env.example.php config/env.php    # edit with your DB creds, app URL, etc.
```

Point an Apache vhost at the project root (not `public/`):

```apache
<VirtualHost *:80>
    ServerName tinyshop.local
    DocumentRoot /path/to/tinyshop

    <Directory /path/to/tinyshop>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Visit the URL in your browser. If `config/.installed` doesn't exist, you'll be redirected to the installer which handles database setup and admin account creation.

## Configuration

All config is plain PHP arrays — no `.env` files, no `vlucas/phpdotenv`.

| File | Purpose |
|---|---|
| `config/env.php` | Environment variables (DB creds, app URL, OAuth keys) — gitignored |
| `config/app.php` | App name, debug mode, upload limits, template paths |
| `config/database.php` | MySQL connection (host, port, dbname, charset) |
| `config/oauth.php` | OAuth provider credentials (Google, Instagram, TikTok) |
| `config/routes.php` | All route definitions |
| `config/container.php` | DI container bindings |
| `config/middleware.php` | Global middleware stack |

## Routes

**Public** — Landing, login, register, forgot-password, pricing, help center, custom pages.

**Storefronts** — Each seller gets a subdomain-routed shop with product catalogue, collections, search, checkout, order tracking, and customer accounts.

**Seller Dashboard** (`/dashboard/*`) — Product CRUD, order management, shop settings, design customization, categories, coupons, analytics, billing, product import.

**Admin Panel** (`/admin/*`) — Seller management with impersonation, platform settings (SMTP, S3, branding), subscription plans, help articles, CMS pages, analytics.

**API** (`/api/*`) — JSON endpoints for auth, products, categories, orders, customers, coupons, checkout, billing, uploads, import, theme options, admin operations. Rate-limited and CSRF-protected.

**Webhooks** — Stripe, PayPal, M-Pesa, and Pesapal callbacks for both checkout and subscription billing.

## Build

Source files live in `assets/`. The build step compiles them to `public/`.

```bash
npm run build     # one-shot compile
npm run watch     # rebuild on change
```

**JS bundles** (esbuild, concatenation + minification):
`app`, `dashboard`, `cart`, `auth`, `help`, `landing`, `pricing`

**CSS** (Sass): `assets/css/*.scss` compiles to `public/css/*.css` + `.min.css`

Never edit files in `public/css/` or `public/js/` — they're overwritten on every build.

## Quality

```bash
composer analyse     # PHPStan level 6
composer cs-check    # PER-CS2 style check
composer cs-fix      # auto-fix style violations
```

PHPStan config: `phpstan.neon` — targets `src/`, level 6.
CS Fixer config: `.php-cs-fixer.dist.php` — PER-CS2 ruleset, strict types enforced, sorted imports.

## Security

- `declare(strict_types=1)` in every PHP file
- Prepared statements only — no SQL interpolation
- CSRF tokens on all state-changing API calls
- Session hardening: `httponly`, `samesite=Lax`, strict mode, 8-hour expiry
- Session regeneration on login, IP fingerprinting
- bcrypt password hashing
- Rate limiting on auth and checkout endpoints
- Security headers on every response (CSP, HSTS, X-Frame-Options, etc.)
- Role-based access: `AuthGuard` (sellers), `AdminGuard` (admin only), `GuestOnly` (redirects if logged in)
- Input validation at all API boundaries via `Validation` service

## Extensibility

WordPress-style hooks via `TinyShop\Services\Hooks`:

```php
// Register a hook
Hooks::addAction('user.registered', function (int $userId) {
    // send welcome email, track analytics, etc.
}, priority: 10);

// Fire a hook
Hooks::doAction('user.registered', $userId);

// Filter data
$price = Hooks::applyFilter('product.price', $price, $product);
```

Addons live in `addons/{name}/init.php` and are auto-loaded on boot. Available hooks: `tinyshop.boot`, `tinyshop.routes.registered`, `user.registered`, `user.logged_in`.

Storefront themes live in `themes/{name}/` with their own CSS, JS, and Smarty template overrides.

## Deployment

```bash
./deploy.sh
```

The script handles asset compilation, rsync to production, config file syncing (safe subset only), theme asset checksums, Smarty cache clearing, and asset version bumping.

## Project Structure Counts

```
src/Controllers/     25 files    (API, admin, dashboard, shop, pages)
src/Services/        37 files    (core services, gateways, OAuth, importers)
src/Models/          19 files    (Active Record, base + 18 domain models)
src/Middleware/      10 files    (PSR-15 middleware)
src/Enums/            3 files    (UserRole, OrderStatus, FieldType)
assets/js/           25 files    (7 bundles)
assets/css/          11 files    (SCSS sources)
templates/           40+ files   (Smarty .tpl)
```

## License

Proprietary. All rights reserved.
