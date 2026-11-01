# Architecture & Attack Surface Analysis — OasisStore

## 1. System Overview

OasisStore is a digital gaming store, bundle catalog, and P2P billing platform built on Paymenter (Laravel 11 / PHP 8.3-FPM) and Nginx:

* **Web Application:** Nginx + PHP 8.3-FPM Paymenter container (`127.0.0.1:8085:80`).
* **Database:** MariaDB 11.4 LTS (`oasis-db`).
* **Cache & Async Queue:** Redis 7 Alpine (`oasis-redis`).
* **Custom Payment Gateway:** `OasisP2P` gateway (`extensions/Gateways/OasisP2P/`) with support for Colombia (Bancolombia, Nequi), Mexico (SPEI), and USDT Multi-network.
* **Webhook & Automation Services:**
  - `oasis-webhook`: FastAPI service (`webhook-service/main.py`) verifying HMAC signatures and notifying Arturo-Api, Discord, and Telegram.
  - `oasis-catalog-scraper`: Playwright + BS4 scraper for rotating game catalogs.
* **Public Ingress:** Cloudflare Tunnel via `oasis-cloudflared`.

```
                        [ Internet Traffic ]
                                 │
                     [ Cloudflare Tunnel Ingress ]
                                 │
                         [ Nginx / PHP: 8085 ]
                                 │
             ┌───────────────────┼───────────────────┐
             ▼                   ▼                   ▼
     [ MariaDB: 3306 ]    [ Redis: 6379 ]    [ FastAPI Webhook: 8000 ]
                                                     │
                                             [ Arturo-Api: 3000 ]
```

---

## 2. Boundaries & Trust Zones

* **Public Internet Boundary:**
  - Catalog browsing, multi-currency cart management, and user authentication.
  - Authenticated receipt upload (`/extensions/gateways/oasisp2p/invoice/{invoice}/receipt`) saving receipts to private ticket disk (`tickets/uploads`).
* **Internal Docker Networks:**
  - `oasis_net`: Internal bridge network for OasisStore services.
  - `arturo-net`: External shared bridge network connecting OasisStore with Arturo-Api.
* **File System & Volume Mounts:**
  - Bind mounts for themes (`./themes/oasis:/app/themes/oasis`) and extensions (`./extensions/Gateways/OasisP2P:/app/extensions/Gateways/OasisP2P`).
  - Private storage volumes for logs, tickets, and MariaDB/Redis data.

---

## 3. High-Priority Entrypoints & Attack Surfaces

1. **Redis Cache and Queue Broker (`docker-compose.yml`):**
   - Redis runs without `--requirepass`.
   - Stores active Laravel sessions (`SESSION_DRIVER: redis`), cache, and queued jobs across shared networks.
2. **Theme and Extension Code Permissions (`docker-compose.yml` & `Dockerfile`):**
   - Entrypoint forcefully executes `chmod -R 777 /app/themes /app/extensions`.
   - Leaves running PHP controllers, helpers, and Blade templates world-writable.
3. **Reverse Proxy Trust Configuration (`docker-compose.yml`):**
   - `TRUSTED_PROXIES: "*"` instructs Laravel to trust all `X-Forwarded-*` headers from any origin, exposing the app to IP spoofing and Host header poisoning.
