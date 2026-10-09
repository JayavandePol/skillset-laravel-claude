# Reverse proxies

## Laravel side (all proxies)

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');   // or the proxy network CIDR, e.g. '172.16.0.0/12'
    $middleware->trustHosts(at: ['example.com'], subdomains: true); // optional host header protection
})
```

```dotenv
APP_URL=https://app.example.com
SESSION_SECURE_COOKIE=true
ASSET_URL=           # only if assets are on a CDN
```

Symptoms of a missing `trustProxies`: mixed-content warnings, `http://` in generated URLs/redirects, signed URLs failing ("Invalid signature"), wrong client IPs in rate limits.

## Nginx Proxy Manager

1. Put NPM and the app on a shared Docker network (`proxy`), or forward to `host-ip:port`.
2. Proxy Host: domain → `http://app:8080`; **Block Common Exploits** on; **Websockets Support** on for Reverb hosts.
3. SSL tab: Let's Encrypt, **Force SSL**, **HTTP/2**, **HSTS** (after verifying HTTPS works).
4. Advanced tab:

   ```nginx
   client_max_body_size 50M;
   proxy_read_timeout 120s;
   proxy_set_header X-Forwarded-Proto $scheme;
   proxy_set_header X-Forwarded-Host $host;
   ```

5. Optional: Access List to IP-restrict `/admin`, `/horizon`, `/pulse` (or a separate admin domain).

## Traefik (Dokploy / standalone)

Labels on the `app` service (Dokploy generates these):

```yaml
labels:
  - traefik.enable=true
  - traefik.http.routers.app.rule=Host(`app.example.com`)
  - traefik.http.routers.app.entrypoints=websecure
  - traefik.http.routers.app.tls.certresolver=letsencrypt
  - traefik.http.services.app.loadbalancer.server.port=8080
```

Websockets work without extra config. Body size limits via a `buffering` middleware if needed.

## Plain Nginx (VPS without containers)

Root `/var/www/app/current/public`, `try_files $uri $uri/ /index.php?$query_string;`, deny dotfiles (`location ~ /\.(?!well-known) { deny all; }`), PHP-FPM socket, gzip/brotli, long cache for `/build/`.
