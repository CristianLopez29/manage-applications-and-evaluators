# Deploying to a VPS

Target: one Ubuntu/Debian VPS hosting **several independent applications**, each
fully containerised, behind one shared Traefik proxy that owns :80 and :443 and
terminates TLS for all of them.

```
                    internet
                        |
                   :80  |  :443
                        v
              +------------------+
              |     traefik      |   /srv/traefik - deployed once
              +--------+---------+
                       |  network: edge
      +----------------+----------------+---------------+
      v                v                v               v
  candidacy        ticketing        eventhub        (next app)
   app:8080         app:8080         app:8080        app:8080
      |                |                |               |
  internal         internal         internal        internal
  mysql redis      mysql redis      mysql redis     mysql redis
  queue sched      ...              ...             ...
```

Each app keeps its own MySQL and Redis on a private `internal` network. Nothing
but Traefik binds a host port; an app's database is not reachable from another
app, from the host, or from the internet.

---

## Part 1 - Host, once

### 1.1 Packages

```bash
sudo apt update && sudo apt install -y ca-certificates curl git
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker "$USER"   # log out and back in
```

Nothing else is needed on the host: no PHP, no MySQL, no Nginx. Everything runs
in containers.

### 1.2 Firewall

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

⚠️ **`ufw` does not protect published container ports.** Docker writes its rules
straight into the `DOCKER` iptables chain, which is evaluated before ufw's, so a
container publishing `3306:3306` is reachable from the internet even while
`ufw status` claims that port is denied.

This is why no application service in `compose.prod.yaml` has a `ports:` key.
Verify it from **another machine** after every deploy:

```bash
nmap -Pn -p 22,80,443,3306,6379,8080 <your-server-ip>
```

Only 22, 80 and 443 may be `open`.

### 1.3 The shared network and proxy

```bash
docker network create edge

sudo mkdir -p /srv/traefik && cd /srv/traefik
# copy deploy/traefik/ from this repo here (compose.yaml + dynamic/)
echo "ACME_EMAIL=you@example.com" > .env
docker compose up -d
```

See [`deploy/traefik/README.md`](deploy/traefik/README.md). Traefik routes only
containers that carry `traefik.enable=true`, so a new container is never exposed
by accident.

---

## Part 2 - This application

### 2.1 Clone and configure

```bash
sudo mkdir -p /srv/candidacy && sudo chown "$USER" /srv/candidacy
git clone <repo-url> /srv/candidacy && cd /srv/candidacy

cp .env.production.example .env
```

Fill in every `<...>` placeholder. Generate the secrets:

```bash
openssl rand -hex 32        # HEALTHCHECK_TOKEN
openssl rand -base64 24     # DB_PASSWORD, DB_ROOT_PASSWORD, REDIS_PASSWORD
```

`APP_DOMAIN` is what Traefik matches on. **Point its A record at the VPS before
starting the container** - Traefik's TLS-ALPN challenge fails otherwise, and
repeated failures hit Let's Encrypt rate limits.

Generate the application key:

```bash
docker compose -f compose.prod.yaml build
docker compose -f compose.prod.yaml run --rm --no-deps app php artisan key:generate --show
# paste the base64:... value into APP_KEY in .env
```

### 2.2 Start

```bash
docker compose -f compose.prod.yaml up -d
docker compose -f compose.prod.yaml ps
docker compose -f compose.prod.yaml logs -f app
```

The `app` container's entrypoint runs migrations and rebuilds the config, route
and view caches on every boot. Those caches embed environment values, so they
are built at start rather than baked into the image, which is built once and run
against whatever `.env` the server provides.

Only the `web` role migrates. `queue` and `scheduler` share the same image but
skip it, so three containers never race the same migrator.

### 2.3 First admin

**Do not run `php artisan db:seed` in production** - the seeder creates
`test@example.com` / `password` with the `admin` role.

```bash
docker compose -f compose.prod.yaml exec app php artisan tinker
```

Then, at the prompt:

```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'you@example.com',
    'password' => bcrypt('a-strong-password'),
    'role' => 'admin',
]);
```

---

## Part 3 - Verify

```bash
TOKEN=<HEALTHCHECK_TOKEN>
DOMAIN=<your-domain>

# TLS and redirect
curl -sI http://$DOMAIN | head -1                 # 301 to https
curl -sI https://$DOMAIN/up | head -1             # 200

# Probes: 403 without the token, 200 with it
curl -so /dev/null -w '%{http_code}\n' https://$DOMAIN/api/health
curl -s -H "X-Health-Check-Token: $TOKEN" https://$DOMAIN/api/readiness

# Headers: no X-Powered-By, HSTS present
curl -sI https://$DOMAIN/up | grep -iE 'x-powered-by|strict-transport|x-frame'

# Telescope must not exist in production
curl -so /dev/null -w '%{http_code}\n' https://$DOMAIN/telescope   # 404

# A real request
curl -s -X POST https://$DOMAIN/api/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"you@example.com","password":"a-strong-password"}'

curl -s https://$DOMAIN/api/v1/candidates \
  -H "Authorization: Bearer <token>" -H 'Accept: application/json'
```

`/api/readiness` answers `200` when the database and cache are both reachable
and `503` when either is down, so an uptime monitor can alert on it. Point
UptimeRobot at it with the token as a custom header.

Swagger UI is at `https://$DOMAIN/api/documentation` and requires an admin
bearer token outside `local`; a browser without one is redirected to `/login`,
which is a small page for obtaining a token.

---

## Part 4 - Operating

### Redeploy

```bash
cd /srv/candidacy
git pull
docker compose -f compose.prod.yaml build
docker compose -f compose.prod.yaml up -d
```

`up -d` recreates only what changed. The entrypoint re-runs migrations and
rebuilds caches; the queue and scheduler containers are replaced with the new
image, which matters because `queue:work` holds code in memory.

### Logs

| Where | What |
|-------|------|
| `docker compose -f compose.prod.yaml logs -f app` | nginx + php-fpm + the `stderr` half of `LOG_CHANNEL=production` |
| `... exec app tail -f storage/logs/app.json.log-<date>` | Structured application log, rotated 14 days |
| `... exec app tail -f storage/logs/access.json.log-<date>` | One JSON line per request: method, path, status, duration, `request_id` |
| `docker compose logs -f traefik` (in `/srv/traefik`) | Edge access log and ACME certificate events |

Every response carries `X-Request-Id`; the same id is in both application and
access lines, so a user's report is traceable end to end.

Cap Docker's own log growth in `/etc/docker/daemon.json`:

```json
{ "log-driver": "json-file", "log-opts": { "max-size": "10m", "max-file": "3" } }
```

### Backups

```bash
docker compose -f compose.prod.yaml exec -T mysql \
  mysqldump -u root -p"$DB_ROOT_PASSWORD" --single-transaction candidacy \
  | gzip > "/srv/backups/candidacy-$(date +%F).sql.gz"
```

The `storage` volume holds uploaded CVs and is not in the database - back it up
too, or the files are gone on volume loss.

### Resource use

Roughly 1.7 GB with all five containers up (MySQL ~700 MB, app ~250 MB, queue
~150 MB, scheduler ~100 MB, Redis ~50 MB). `compose.prod.yaml` sets memory
limits per service so one app cannot starve the others; `docker stats` shows
actual use.

---

## Notes for the other apps on this box

- **Router names must be unique.** `traefik.http.routers.<name>` collides
  silently across compose projects. This app uses `candidacy`.
- **Set `name:` in every compose file** (this one uses `name: candidacy`), or
  Compose derives the project name from the directory and two apps in
  similarly-named directories will fight over container names.
- **Each app joins `edge` plus its own private network.** Only the HTTP-serving
  container goes on `edge`; databases stay on `internal`.
- **Trusted proxies.** Traefik reaches containers over a Docker bridge network
  in `172.16.0.0/12`, which this app already trusts in `bootstrap/app.php`.
  Without that the app sees every request as coming from the proxy and its
  per-user rate limits collapse into one shared bucket. An app in front of
  Cloudflare needs Cloudflare's published ranges added as well - never a
  wildcard on a host that is also reachable directly, since that lets anyone
  spoof their IP with a forged `X-Forwarded-For`.
- **HSTS is sent with `includeSubDomains`** and a two-year max-age. If any
  subdomain on this box is not served over HTTPS, browsers that have seen the
  header will refuse to reach it.
