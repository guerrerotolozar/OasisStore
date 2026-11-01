# Technical Reproduction & Remediation Details — OasisStore

## 1. `oasis-unauthenticated-redis-session-queue`
* **Severidad:** **CRITICAL** (Likelihood: Medium | Impact: Critical)
* **Archivos:**
  - `docker-compose.yml` (Líneas 22–35, 70–73, 103–105)

### Traza de Ejecución y Causa Raíz
Redis 7 corre sin contraseña (`--requirepass`).
Paymenter usa Redis para:
- `SESSION_DRIVER: redis` (almacenamiento de sesiones en texto claro/serializadas).
- `QUEUE_CONNECTION: redis` (almacenamiento de tareas asíncronas como objetos PHP serializados).
Un contenedor en `oasis_net` o en la red puente externa `arturo-net` puede conectarse al puerto 6379, extraer identificadores de sesión de administradores o insertar objetos serializados en `queues:default` que al ser deserializados por `php artisan queue:work` ejecutan código remoto (RCE).

### PoC / Prueba de Reproducción
```bash
redis-cli -h 127.0.0.1 -p 6379 ping
```
*Comportamiento vulnerable:* Responde `PONG` sin autenticación.
*Comportamiento esperado:* `(error) NOAUTH Authentication required.`

### Código de Remediación
En `docker-compose.yml`:
```yaml
  cache:
    image: redis:7-alpine
    container_name: oasis-redis
    restart: unless-stopped
    command: redis-server --requirepass "${REDIS_PASSWORD}" --save 60 1 --loglevel warning
    volumes:
      - "./data/redis:/data"
    networks:
      - oasis_net
```
Y en `paymenter`:
```yaml
    environment:
      REDIS_PASSWORD: "${REDIS_PASSWORD}"
```

---

## 2. `oasis-world-writable-executable-code`
* **Severidad:** **HIGH** (Likelihood: Medium | Impact: Critical)
* **Archivos:**
  - `docker-compose.yml` (Línea 43)
  - `docker/paymenter/Dockerfile` (Líneas 63–65)

### Traza de Ejecución y Causa Raíz
En `docker-compose.yml`:
`chmod -R 777 /app/themes /app/extensions`
Otorga permisos universales de lectura, escritura y ejecución a cualquier usuario del sistema dentro del contenedor. Si un proceso de bajos privilegios es vulnerado, puede inyectar código en `OasisP2P.php` o en las plantillas Blade, que es ejecutado directamente por PHP-FPM con cada petición web.

### PoC / Prueba de Reproducción
```bash
docker compose exec paymenter ls -ld /app/themes/oasis/theme.php
```
*Comportamiento vulnerable:* Permisos `-rwxrwxrwx` (0777).
*Comportamiento esperado:* Permisos `-rw-r--r--` (0644).

### Código de Remediación
En `docker-compose.yml`:
```yaml
    command: ["/bin/ash", "-c", "chown -R nginx:nginx /app/themes /app/extensions && chmod -R 755 /app/themes /app/extensions && composer dump-autoload -o && php artisan optimize:clear && exec supervisord -n -c /etc/supervisord.conf"]
```

---

## 3. `oasis-wildcard-trusted-proxies`
* **Severidad:** **MEDIUM** (Likelihood: Medium | Impact: Medium)
* **Archivos:**
  - `docker-compose.yml` (Línea 63)
  - `DOMINIO_CLOUDFLARE_TECH.md` (Línea 11)

### Traza de Ejecución y Causa Raíz
`TRUSTED_PROXIES: "*"` le indica a Laravel que confíe ciegamente en cabeceras `X-Forwarded-*` recibidas de cualquier IP. Permite falsear la IP de origen para eludir throttles o inyectar `X-Forwarded-Host` para envenenar rutas generadas dinámicamente.

### PoC / Prueba de Reproducción
```bash
curl -I -H "X-Forwarded-Host: evil-attacker.com" http://localhost:8085/
```

### Código de Remediación
En `docker-compose.yml`:
```yaml
      TRUSTED_PROXIES: "172.18.0.0/16,127.0.0.1"
```
