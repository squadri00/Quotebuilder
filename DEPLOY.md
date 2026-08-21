# Deploying Quotaire

Plain-English steps for putting this app on a real web server — first in Stripe **test mode** for a shakedown, then flipped to **live mode**.

## 1. What the server needs

- PHP 8.2+ with the extensions Laravel needs (most hosts have these by default: mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, bcmath, fileinfo, gd/imagick for images).
- MySQL/MariaDB database.
- Composer (PHP package manager).
- Node.js + npm (only needed once, to build the CSS/JS — you can build locally and upload the result instead if the server doesn't have Node).
- A domain name pointed at the server, with an SSL/TLS certificate (e.g. via Let's Encrypt/Certbot) — the app assumes HTTPS in production.
- The web server's document root must point at the app's `public/` folder, not the project root.

## 2. First deploy

Run these from the project folder on the server, in order:

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Now edit `.env` and fill in the real values for this server. The most important ones (see the comments already in `.env.example` for why):

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://yourdomain.com` (must match exactly how visitors reach the site)
- `DB_*` — your real database credentials
- `SESSION_SECURE_COOKIE=true` (uncomment this line — only once you've confirmed HTTPS is actually working)
- `MAIL_*` — your real outgoing email settings (or leave `MAIL_MAILER=log` temporarily if you're not ready to send real emails yet)
- `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` — your Stripe **test-mode** keys for phase 1 (see §4 below for how these get set from the Super Admin panel too)

Then finish the setup:

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 3. Two background processes the server must keep running

**Queue worker** — sends emails and processes background jobs. Without this running continuously, emails will queue up and never actually send.

```bash
php artisan queue:work --daemon
```

Don't just run this in a terminal and close it — set it up with **Supervisor** (or your host's equivalent) so it restarts automatically if it crashes or the server reboots. Most hosting control panels (cPanel, Plesk, Forge, etc.) have a "keep this process running" option for exactly this.

**Scheduler** — runs Quotaire's own hourly cleanup job (expiring abandoned implementation orders). Add this one line to the server's crontab:

```
* * * * * cd /path/to/quotebuilder && php artisan schedule:run >> /dev/null 2>&1
```

## 4. Stripe: test mode first, then go live

Everything Stripe-related is controlled from **Super Admin → Settings** in the app itself — you never need a developer to touch code to switch modes.

1. **Phase 1 (testing):** In Stripe's dashboard, make sure you're viewing **Test mode**, copy the test Publishable key, Secret key, and set up a webhook endpoint pointing at `https://yourdomain.com/stripe/webhook` to get a test-mode webhook signing secret. Paste all three into Super Admin → Settings.
2. Test the full flow yourself: sign up for a paid plan using [Stripe's test card numbers](https://stripe.com/docs/testing), confirm the subscription activates, confirm a real webhook fires (Stripe's dashboard shows delivery attempts).
3. **Phase 2 (going live):** Switch Stripe's dashboard to **Live mode**, repeat the same steps to get live Publishable/Secret keys and a live webhook signing secret, and paste those into Super Admin → Settings in place of the test values. That's the entire switch — no redeploy needed.

⚠️ The app will refuse to process any Stripe webhook at all if the webhook secret field is ever left blank (this fails loudly on purpose, rather than silently accepting unverified requests) — so if webhooks stop working after a settings change, check that field first.

## 5. Create your Super Admin login

There's no signup form for this — it's created directly on the server once:

```bash
php artisan tinker
```
```php
\App\Models\Admin::create([
    'name' => 'Your Name',
    'email' => 'you@yourdomain.com',
    'password' => bcrypt('a-strong-password-here'),
]);
```

Then log in at `https://yourdomain.com/superadmin/login`.

## 6. Every deploy after the first

```bash
git pull
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 7. Quick pre-launch checklist

- [ ] `APP_DEBUG=false` in production `.env`
- [ ] HTTPS certificate installed and working
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Database backups scheduled (ask your host how — this isn't set up in the app itself)
- [ ] Queue worker running under Supervisor (or equivalent)
- [ ] Cron line added for the scheduler
- [ ] Stripe test-mode keys entered in Super Admin → Settings, full signup-to-payment flow tested with a Stripe test card
- [ ] Only after everything above checks out: swap in Stripe live-mode keys
