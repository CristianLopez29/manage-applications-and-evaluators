# Shared edge proxy

Deployed once per VPS, independently of the applications. It owns :80 and :443;
no application container publishes a host port.

## First-time setup

```bash
sudo mkdir -p /srv/traefik && cd /srv/traefik
# copy this directory here (compose.yaml + dynamic/)

# The shared network every app joins. Create it before anything else.
docker network create edge

echo "ACME_EMAIL=you@example.com" > .env

docker compose up -d
docker compose logs -f traefik
```

## Adding an application

Nothing here changes. In the app's own compose file:

1. Join the `edge` network (plus its own private `internal` network).
2. Publish no host ports.
3. Add the labels:

```yaml
labels:
  traefik.enable: "true"
  traefik.docker.network: edge
  traefik.http.routers.<name>.rule: Host(`<domain>`)
  traefik.http.routers.<name>.entrypoints: websecure
  traefik.http.routers.<name>.tls.certresolver: letsencrypt
  traefik.http.services.<name>.loadbalancer.server.port: "8080"
```

`<name>` must be unique across every app on the box: two routers with the same
name silently collide. Point the domain's A record at the VPS *before* starting
the container — the TLS-ALPN challenge fails otherwise, and repeated failures
hit Let's Encrypt rate limits.

## What this does not do

TLS terminates here, so applications see plain HTTP and must trust the proxy's
`X-Forwarded-*` headers to recover the real client IP and scheme. Traefik reaches
containers over a Docker bridge network in `172.16.0.0/12`, which this app
already trusts (`bootstrap/app.php`). An app that does not trust those headers
will see every request as coming from the proxy, collapsing its per-IP rate
limits into one shared bucket.
