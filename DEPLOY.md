# Deploying to a VPS

Target: a single Ubuntu/Debian VPS running Nginx + PHP-FPM 8.4, MySQL 8 and
Redis, serving one JSON API over HTTPS. Low traffic, one box, no orchestrator.

---

## 1. Server packages

```bash
sudo apt update
sudo apt install -y nginx mysql-server redis-server \
  php8.4-fpm php8.4-mysql php8.4-redis php8.4-mbstring \
  php8.4-bcmath php8.4-xml php8.4-curl php8.4-zip unzip git
```

Composer:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 2. PHP configuration

Edit `/etc/php/8.4/fpm/php.ini`:

```ini
display_errors = Off
display_startup_errors = Off
expose_php = Off
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 12M
```

`expose_php = Off` removes the `X-Powered-By` header at the source; the
application strips it too, but only for responses it actually renders.

```bash
sudo systemctl restart php8.4-fpm
```

## 3. Database and cache

```bash
sudo mysql -e "CREATE DATABASE candidacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'candidacy'@'127.0.0.1' IDENTIFIED BY '<strong-password>';"
sudo mysql -e "GRANT ALL PRIVILEGES ON candidacy.* TO 'candidacy'@'127.0.0.1'; FLUSH PRIVILEGES;"
```

Bind MySQL and Redis to localhost only (`bind-address = 127.0.0.1` in
`/etc/mysql/mysql.conf.d/mysqld.cnf`, `bind 127.0.0.1` in `/etc/redis/redis.conf`),
and set `requirepass` in the Redis config.

## 4. Application

```bash
sudo mkdir -p /var/www/candidacy && sudo chown -R $USER:www-data /var/www/candidacy
git clone <repo-url> /var/www/candidacy
cd /var/www/candidacy

composer install --no-dev --optimize-autoloader --no-interaction

cp .env.production.example .env
# fill in every <...> placeholder
php artisan key:generate

php artisan migrate --force
php artisan storage:link
```

Generate the health probe token with `openssl rand -hex 32`.

**Do not run `php artisan db:seed` in production** — the seeder creates
`test@example.com` / `password` with the `admin` role.

Create the first admin instead:

```bash
php artisan tinker --execute="\App\Models\User::create([
  'name' => 'Admin',
  'email' => 'you@example.com',
  'password' => bcrypt('<strong-password>'),
  'role' => 'admin',
]);"
```

Permissions:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

## 5. Cache the framework

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan l5-swagger:generate
```

`config:cache` stops `.env` from being read at runtime. Every setting this app
needs is read through `config()`, so this is safe — but it also means **any new
`env()` call outside `config/` will silently return its default in production.**

## 6. Nginx

`/etc/nginx/sites-available/candidacy`:

```nginx
limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;

server {
    listen 80;
    server_name <your-domain>;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name <your-domain>;
    root /var/www/candidacy/public;

    ssl_certificate     /etc/letsencrypt/live/<your-domain>/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/<your-domain>/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    index index.php;
    charset utf-8;
    client_max_body_size 12M;

    access_log /var/log/nginx/candidacy.access.log;
    error_log  /var/log/nginx/candidacy.error.log;

    location / {
        # Burst absorbs normal client retries; the application throttle
        # (60/min authenticated, 5/min on login) is the real limit. This
        # only stops a flood from reaching PHP at all.
        limit_req zone=api burst=60 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/candidacy /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

TLS with Certbot:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d <your-domain>
```

The application trusts `X-Forwarded-*` only from loopback and private ranges,
which is what Nginx on the same host sends. Without that, every request would
look like it came from the proxy and the per-IP rate limits would collapse into
one shared bucket.

⚠️ **Behind Cloudflare or any external proxy**, the forwarded request arrives from a *public* address
that is not in those ranges, so the app falls back to treating the CDN edge as the client: rate limits
collapse again and the access log records the edge IP. Add the provider's published ranges to
`trustProxies(at: [...])` in `bootstrap/app.php`. Never use `at: '*'` on a host that is also reachable
directly — that lets anyone spoof their IP with a forged `X-Forwarded-For`.

⚠️ **HSTS is sent with `includeSubDomains`** and a two-year max-age. If you have (or later add) a
subdomain that is not served over HTTPS, browsers that have seen this header will refuse to reach it.
Drop `includeSubDomains` in `SecurityHeaders` if that is a problem, and only consider `preload` once
you are certain — preload lists are slow to leave.

Add a catch-all server block so requests with an unknown `Host` header (scanners, or someone pointing
their own domain at your IP) are dropped rather than served your app:

```nginx
server {
    listen 80 default_server;
    listen 443 ssl default_server;
    server_name _;
    ssl_certificate     /etc/letsencrypt/live/<your-domain>/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/<your-domain>/privkey.pem;
    return 444;
}
```

## 7. Network exposure

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status verbose
```

Verify from **outside** the box that only 22/80/443 answer:

```bash
# from another machine
nmap -Pn -p 22,80,443,3306,6379,8025 <your-server-ip>
```

`3306`, `6379` and `8025` must all be `closed` or `filtered`.

### If you deploy with Docker Compose instead of the native install above

⚠️ **`ufw` does not protect published container ports.** Docker writes its rules straight into the
`DOCKER` iptables chain, which is evaluated before ufw's, so a container publishing `3306:3306` is
reachable from the internet even while `ufw status` claims the port is denied. `compose.yaml` in this
repo publishes MySQL (3306), Redis (6379) and Mailpit (8025) for local development.

Bind those to loopback before starting the stack:

```yaml
# compose.override.yaml on the server
services:
  mysql:
    ports: ['127.0.0.1:3306:3306']
  redis:
    ports: ['127.0.0.1:6379:6379']
  mailpit:
    ports: []          # Mailpit captures mail and never sends it — do not run it in production
```

Then re-run the `nmap` check above. An exposed Redis with no `requirepass` is compromised within
minutes of being reachable.

## 8. Queue worker and scheduler

Queue-backed endpoints (`POST /api/v1/candidates/{id}/analyze`,
`POST /api/v1/evaluators/report`) return `202` and never complete without a
worker running.

`/etc/systemd/system/candidacy-queue.service`:

```ini
[Unit]
Description=Candidacy queue worker
After=network.target

[Service]
User=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/candidacy/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now candidacy-queue
```

Scheduler (runs `ProcessOverdueAssignmentsJob` every 15 minutes) via cron:

```bash
sudo crontab -u www-data -e
# * * * * * cd /var/www/candidacy && php artisan schedule:run >> /dev/null 2>&1
```

## 9. Verify the deploy

```bash
TOKEN=<HEALTHCHECK_TOKEN>

curl -s -H "X-Health-Check-Token: $TOKEN" https://<your-domain>/api/health
curl -s -H "X-Health-Check-Token: $TOKEN" https://<your-domain>/api/readiness
```

`/api/readiness` returns `200` when the database and cache are both reachable
and `503` when either is down, so an uptime monitor can alert on it. Point
UptimeRobot (or equivalent) at it with the token as a custom header.

Log in and call an endpoint:

```bash
curl -s -X POST https://<your-domain>/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"you@example.com","password":"<password>"}'

curl -s https://<your-domain>/api/v1/candidates \
  -H "Authorization: Bearer <token>"
```

Swagger UI is at `https://<your-domain>/api/documentation` and requires an
admin bearer token whenever `APP_ENV` is not `local`.

## 10. Redeploying

```bash
cd /var/www/candidacy
git pull
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan l5-swagger:generate
sudo systemctl restart candidacy-queue
sudo systemctl reload php8.4-fpm
```

Restarting the queue worker matters: `queue:work` holds the old code in memory
until it is restarted.

## Logs

| File | Contents |
|------|----------|
| `storage/logs/app.json.log-<date>` | Application log, one JSON object per line, rotated 14 days |
| `storage/logs/access.json.log-<date>` | One line per request: method, path, status, duration, `request_id`, rotated 7 days |
| `/var/log/nginx/candidacy.access.log` | Nginx access log |

Every response carries an `X-Request-Id` header; the same id appears in both
application and access lines, so a report from a user is traceable end to end.
