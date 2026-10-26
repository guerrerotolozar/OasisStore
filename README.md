# OasisStore — Plataforma E-Commerce P2P & Sincronización de Catálogos Digitales

**OasisStore** es una arquitectura modular de comercio electrónico para bienes digitales, recargas y microtransacciones con liquidación **Peer-to-Peer (P2P)** sin comisiones de intermediación. Combina un motor de facturación en **Laravel 11 / Livewire 3** (basado en Paymenter MIT), una pasarela P2P propia con custodia privada de comprobantes, un microservicio asíncrono de webhooks en **FastAPI** y un motor genérico de extracción de catálogos web con **Playwright**.

---

## Arquitectura Modular (4 Subsistemas)

```text
OasisStore/
├── catalog-scraper/                # [Módulo 1] Plantilla de extracción y sincronización de catálogos (Playwright)
│   ├── scraper.py                  # Parser DOM -> JSON y descarga local de assets WebP
│   ├── data/catalogo.example.json  # Esquema estándar de catálogo (bundles, pase de temporada y créditos)
│   └── Dockerfile                  # Contenedor aislado ejecutado bajo demanda (--profile scraper)
├── extensions/Gateways/OasisP2P/   # [Módulo 2] Extensión de pasarela P2P (SPEI / USDT Crypto)
│   ├── OasisP2P.php                # Custodia privada de comprobantes, Rate Limiting y firma HMAC
│   └── resources/views/pay.blade.php # Terminal interactiva con cápsulas Click-to-Copy
├── themes/oasis/                   # [Módulo 3] Tema Frontend "Oasis Digital Vault" (Claro/Oscuro)
│   ├── sync-catalog.php            # Ingesta idempotente de catalogo.json hacia MariaDB
│   └── views/                      # Vistas Blade adaptadas: Home, Checkout directo, Factura y Sala P2P
└── webhook-service/                # [Módulo 4] Microservicio FastAPI + Orquestación de Notificaciones
    ├── main.py                     # Validación HMAC-SHA256, anti-replay y despacho multicanal (SSE/Discord/Telegram)
    └── Dockerfile                  # Contenedor Python sin exposición de puertos al host
```

---

## Características Técnicas

### 1. Motor de Sincronización de Catálogos (`catalog-scraper/` + `sync-catalog.php`)
- **Agnóstico de Fuente:** Diseñado para que cualquier desarrollador conecte su propia página web, tienda SPA o catálogo de juegos configurando `TARGET_STORE_URL` o generando un archivo compatible con `catalog-scraper/data/catalogo.example.json`.
- **Ingesta Idempotente en Base de Datos:** El script `themes/oasis/sync-catalog.php` detecta automáticamente actualizaciones en `storage/app/public/catalog-feed/catalogo.json` mediante huella de versión (`.last_synced_stamp`) y sincroniza categorías, productos, planes y precios en MariaDB sin duplicar registros.

### 2. Pasarela `OasisP2P` y Sala de Entrega Privada
- **Liquidación Directa (0% Comisión):** Soporte configurable para transferencias bancarias (SPEI) y criptomonedas (`USDT TRC20 / BEP20 / Binance Pay`) con cápsulas interactivas **Click-to-Copy** compatibles con contextos HTTP y HTTPS.
- **Almacenamiento Privado de Comprobantes:** Las capturas de pago se guardan fuera del directorio web público (`storage/app/tickets/uploads`) con nombres `ULID` y se sirven únicamente bajo autorización de sesión (`TicketMessageAttachmentPolicy`).
- **Retención `Pending Setup`:** Al marcar una factura como pagada, el evento `Invoice\Paid` mantiene el servicio en estado pendiente hasta que el operador confirma la entrega manual en la sala P2P.

### 3. Microservicio de Webhooks y Orquestador (`webhook-service/`)
- **Verificación Criptográfica:** Cada evento emitido por `OasisP2P` (`p2p.receipt_uploaded`, `invoice.paid`) se firma con `HMAC-SHA256` (`X-Oasis-Signature`) y ventana anti-replay de 300 segundos.
- **Despacho Multicanal:** Conecta por red interna Docker hacia un orquestador o API interna (`POST /api/notifications` para alertas SSE en tiempo real y `POST /api/notas` para bitácora Markdown), además de soporte opcional para Discord y Telegram.

---

## Despliegue Rápido con Docker Compose

### 1. Configurar Variables de Entorno
Copia la plantilla `.env.example` y reemplaza los marcadores (`aqui_pon_tu_*`) con tus credenciales, o deja que `./deploy.sh` genere las claves criptográficas automáticamente:

```bash
cp .env.example .env
chmod +x deploy.sh
./deploy.sh
```

### 2. Sincronizar un Catálogo Personalizado (Opcional)
Para poblar la tienda usando el esquema de ejemplo o ejecutando el scraper sobre tu propio origen:

```bash
# Opción A: Copiar el esquema de ejemplo al volumen público
mkdir -p storage/public/catalog-feed
cp catalog-scraper/data/catalogo.example.json storage/public/catalog-feed/catalogo.json

# Opción B: Ejecutar el contenedor Playwright bajo demanda
docker compose --profile scraper run --rm catalog-scraper
```

---

## Seguridad y Hardening Aplicado
- **PHP `>= 8.3.36` Verificado en Build:** El `Dockerfile` de la tienda valida en tiempo de compilación la versión de PHP e inyecta `session.upload_progress.enabled = 0`.
- **Aislamiento de Red:** Los contenedores de base de datos (`mariadb:11.4`), caché (`redis:7-alpine`) y `webhook-notifier` operan en una red bridge interna sin exponer puertos al host.
- **Higiene de Repositorio:** `.gitignore` bloquea cualquier archivo `.env`, sesiones de navegador (`auth.json`), volúmenes de base de datos y directorios de scrapers privados.

---

## Licencia
El código de las extensiones (`OasisP2P`), el tema (`themes/oasis`), el microservicio (`webhook-service`) y la plantilla de sincronización (`catalog-scraper`) se distribuye con fines educativos y de portafolio bajo licencia **MIT**. El núcleo base de facturación utiliza [Paymenter](https://github.com/paymenter/paymenter) (MIT License).
