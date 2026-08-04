# Jamuna Soft — Corporate Website & Admin Panel

A production-ready corporate website for **Jamuna Soft** (software development, web development, hosting, server management, digital marketing and business automation) with a full **Filament Admin Panel** so non-technical administrators can manage every part of the site.

## Features

**Public website** (server-rendered Blade + Tailwind CSS v4 + Alpine.js)

- Home page with admin-managed hero, trust statistics, featured services/portfolio/packages, industry solutions, work process, testimonials, FAQ, blog highlights and CTA sections
- Services with SEO-friendly detail pages (benefits, features, technologies, process, FAQs, related projects & packages)
- Industry solutions pages (challenges vs. offerings, recommended services)
- Filterable portfolio with full case-study pages and image galleries
- Hosting page with managed hosting plans (shared / managed / VPS / cloud / email)
- Packages & pricing with category filters
- About page (story, mission/vision, values, team)
- Blog with categories, tags, featured images, reading time, related posts and scheduled publishing
- Contact form and detailed quotation request form (honeypot + rate limiting + strict validation, optional file attachments stored privately)
- Newsletter signup with double opt-in and unsubscribe links
- English / বাংলা language switcher (per-field content overrides + UI translations)
- Technical SEO: meta/OG/Twitter tags, canonical URLs, JSON-LD (Organization, WebSite, Service, Article, FAQPage, BreadcrumbList, ProfessionalService), XML sitemap, robots.txt, redirect manager, noindex control, branded 404

**Admin panel** (Filament 5, at `/admin`)

- Dashboard: lead/message stats, monthly enquiries chart, lead status chart, most-requested services, recent leads with overdue follow-up highlighting
- Content: services & categories, industry solutions, portfolio & categories, packages, hosting plans, testimonials, team members, blog (posts/categories/tags), FAQs (attachable to services/solutions)
- Website: dynamic pages with a section builder (hero, rich text, image+text, feature grid, stats, logo grid, CTA, FAQ, testimonials, portfolio/service/pricing grids, contact form), navigation menus, redirects, social links, website settings, homepage & about content
- Sales: full lead management (statuses, priorities, assignment with email notification, follow-up dates, activity timeline, CSV export), contact messages, newsletter subscribers (CSV export)
- System: users, roles & permissions (Spatie), every resource gated per-permission
- Media uploads via Spatie Media Library with WebP conversions; Bengali translation fields on all content forms

## Requirements

- PHP **8.3+** (built on 8.4) with extensions: `pdo_mysql`, `mbstring`, `intl`, `gd`, `xml`, `curl`, `zip`
- Composer 2.2+
- MySQL 8 / MariaDB 10.6+
- Node.js 20+ and npm
- Nginx or Apache for production

## Local Installation

```bash
git clone <repository-url> jamunasoft && cd jamunasoft

composer install
cp .env.example .env
php artisan key:generate

# Create the database, then set DB_* in .env
mysql -u root -p -e "CREATE DATABASE jamunasoft_site CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate           # run migrations
php artisan db:seed           # roles/permissions + demo content (idempotent)
php artisan storage:link      # public storage symlink

npm install
npm run build                 # or: npm run dev (during development)

php artisan serve
```

The site is now at `http://localhost:8000` and the admin panel at `http://localhost:8000/admin`.

### Create the first Super Admin

No credentials are hardcoded anywhere. Create your admin interactively:

```bash
php artisan app:create-admin
# or non-interactively:
php artisan app:create-admin --name="Your Name" --email=you@example.com --password="strong-password"
```

Re-running the command with an existing email updates that user and (re)grants Super Admin.

### Queues (emails & notifications)

All emails are queued. Locally, either run a worker or leave `MAIL_MAILER=log` (emails are written to `storage/logs/laravel.log`; a broken mail config never blocks form submissions):

```bash
php artisan queue:work
```

### Scheduler

The scheduler sends daily follow-up reminder digests (08:00), regenerates the sitemap (02:00) and prunes failed jobs:

```bash
php artisan schedule:work   # local development
```

## Tests

Tests run against a separate MySQL database (`jamunasoft_site_test`, see `phpunit.xml`):

```bash
mysql -u root -p -e "CREATE DATABASE jamunasoft_site_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test
```

Covered: public page rendering (with and without content), SEO tag output, draft/scheduled/inactive content visibility, contact form validation/submission/honeypot/attachment rules, quotation form → lead creation with unique references and activity log, newsletter double opt-in/unsubscribe, redirect manager, locale switching, sitemap generation, admin authentication and role/permission restrictions across all resources.

## Production Deployment (Ubuntu VPS)

```bash
# 1. Code & dependencies
git clone <repository-url> /var/www/jamunasoft && cd /var/www/jamunasoft
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Environment
cp .env.example .env && php artisan key:generate
# Set: APP_ENV=production, APP_DEBUG=false, APP_URL=https://your-domain,
#      DB_*, MAIL_* (SMTP), QUEUE_CONNECTION=database

# 3. Database & storage
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
# Optional demo content: php artisan db:seed --class=DemoContentSeeder --force
php artisan storage:link
php artisan app:create-admin

# 4. Caches (run after every deploy)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize
php artisan sitemap:generate
```

### File permissions

```bash
sudo chown -R www-data:www-data /var/www/jamunasoft
sudo find /var/www/jamunasoft -type d -exec chmod 755 {} \;
sudo find /var/www/jamunasoft -type f -exec chmod 644 {} \;
sudo chmod -R ug+rwx /var/www/jamunasoft/storage /var/www/jamunasoft/bootstrap/cache
```

### Cron

```cron
* * * * * cd /var/www/jamunasoft && php artisan schedule:run >> /dev/null 2>&1
```

### Queue worker (systemd)

`/etc/systemd/system/jamunasoft-queue.service`:

```ini
[Unit]
Description=Jamuna Soft queue worker
After=network.target

[Service]
User=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/jamunasoft/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now jamunasoft-queue
```

### Nginx example

```nginx
server {
    listen 80;
    server_name jamunasoft.com www.jamunasoft.com;
    root /var/www/jamunasoft/public;
    index index.php;

    client_max_body_size 20M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~* \.(?:css|js|jpg|jpeg|png|gif|webp|svg|ico|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

Use Certbot (`sudo certbot --nginx`) for HTTPS. `URL::forceScheme('https')` is already applied in production.

### Apache notes

Point the vhost `DocumentRoot` at `/var/www/jamunasoft/public`, enable `mod_rewrite`, and allow `.htaccess` overrides (`AllowOverride All`). The default Laravel `public/.htaccess` handles routing.

## Backups

- **Database:** nightly `mysqldump jamunasoft_site | gzip` shipped off-site (object storage or another server). Keep at least 14 days.
- **Uploads:** back up `storage/app/public` (media library files) and `storage/app/private` (form attachments) with the same cadence.
- Test restores periodically — a backup that has never been restored is a hope, not a backup.

## Security Checklist (before go-live)

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Strong, unique DB and admin passwords; no shared credentials
- [ ] HTTPS enforced with a valid certificate
- [ ] `php artisan config:cache` run (env not readable via web)
- [ ] Queue worker + cron running
- [ ] Mail configured and tested (password reset, lead notifications)
- [ ] Replace all demo content (marked "demo") with real content
- [ ] Set real statistics on the homepage (do not publish demo numbers)
- [ ] Review roles: give staff the least role that fits their job
- [ ] Firewall: only 22/80/443 open; SSH key auth only
- [ ] Backups scheduled and restore-tested

## Architecture Notes

- **Settings** live in a cached key/value `settings` table, edited via *Website Settings* and *Homepage & About Content* in the admin panel; access them with the global `settings('key')` / `settings_t('key')` (locale-aware) helpers.
- **Translations:** content models carry a `translations` JSON column with per-locale overrides (`$model->t('field')`); UI strings use `lang/en.json` / `lang/bn.json`. The locale switcher stores the choice in the session.
- **Leads:** every quotation submission becomes a `Lead` with a unique reference (`JS-YYYY-XXXXXX`), an activity timeline, statuses, priorities, assignment (with email notification) and follow-up reminders (daily digest at 08:00 via scheduler).
- **Redirect manager** only runs when no real route matches (hooked into the 404 handler), so it can never shadow live pages.
- **Media** is handled by Spatie Media Library on the `public` disk with WebP conversions; form attachments are stored on the private `local` disk and only downloadable from the admin panel.
- **Permissions:** every Filament resource is gated by `<area>.view` / `<area>.manage` permissions; Super Admin bypasses via `Gate::before`. Seeded roles: Super Admin, Admin, Content Manager, Sales Manager, Support Manager.
- **View-layer caching** (menus, footer links, social links, settings) stores plain arrays with 1-hour TTL — never Eloquent models — so cached values survive deploys.
- **Phase 3 readiness:** hosting plans, leads and services are normalized so a client portal, invoicing, hosting provisioning API or payment integration can be added as new modules without schema rework. None of these are exposed in navigation until actually built (the optional Client Portal link appears only when a URL is configured in settings).

## Useful Commands

| Command | Purpose |
| --- | --- |
| `php artisan app:create-admin` | Create/promote a Super Admin |
| `php artisan db:seed --class=DemoContentSeeder` | (Re)seed demo content — idempotent |
| `php artisan sitemap:generate` | Regenerate `public/sitemap.xml` |
| `php artisan app:send-follow-up-reminders` | Email overdue follow-up digests now |
| `php artisan queue:work` | Process queued mail/notifications |
| `php artisan test` | Run the test suite |
| `vendor/bin/pint` | Format PHP code |
