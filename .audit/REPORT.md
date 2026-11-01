# Informe de Auditoría de Seguridad — OasisStore

## 1. Configuración de la Auditoría
* **Perfil:** `full` (Full Audit Mode)
* **Objetivo:** `W:\OasisStore`
* **Referencia de Código:** `0894172`
* **Alcance:** `extensions/`, `themes/`, `webhook-service/`, `catalog-scraper/`, `docker/`, `docker-compose.yml`
* **Política de Ejecución:** `sandboxed-source-and-local-only`
* **Verificación Independiente:** Verificado por subagente independiente (`af6946ec-81e2-445b-bc42-ad5408258f12`).
* **Validación de Esquemas:**
  - `validate-coverage-ledger.cjs`: **PASS (3 coverage units valid)**
  - `validate-findings.cjs`: **PASS (3 findings valid)**

---

## 2. Resumen de Postura de Seguridad

OasisStore cuenta con una sólida arquitectura de comercio electrónico basada en Paymenter (Laravel 11), Nginx endurecido (`oasis-security.conf`), PHP 8.3-FPM (`99-oasis-hardening.ini`), almacenamiento privado de comprobantes bancarios (`tickets/uploads`) y firma criptográfica HMAC en webhooks (`oasis-webhook`). No obstante, la auditoría identificó **dos vulnerabilidades de severidad Crítica/Alta** y **una de severidad Media**:
1. **Redis sin Autenticación para Sesiones y Colas:** Redis corre sin `--requirepass`, exponiendo sesiones de administradores/clientes y permitiendo potenciales ataques de RCE mediante deserialización de colas Laravel.
2. **Permisos World-Writable (`chmod -R 777`) en Código Ejecutable:** El comando de arranque aplica permisos `0777` sobre `/app/themes` y `/app/extensions`, permitiendo que procesos no privilegiados alteren scripts de la pasarela de pagos (`OasisP2P.php`) o inyecten webshells.
3. **`TRUSTED_PROXIES: "*"` Desbocado:** Confianza ciega en cualquier cabecera `X-Forwarded-*`, facilitando spoofing de IP y envenenamiento de Host headers.

---

## 3. Tabla de Hallazgos Confirmados

| Severidad | Identificador | Límite / Superficie Afectada | Resultado Observable |
|---|---|---|---|
| **CRITICAL** | `oasis-unauthenticated-redis-session-queue` | Redis 7 (`oasis-redis`) | Acceso sin contraseña a sesiones Laravel (secuestro de admin/clientes) y potencial RCE por deserialización de colas. |
| **HIGH** | `oasis-world-writable-executable-code` | Permisos en `/app/themes` y `/app/extensions` | Comando `chmod -R 777` sobre código ejecutable PHP (`OasisP2P.php`) y vistas Blade en tiempo de ejecución. |
| **MEDIUM** | `oasis-wildcard-trusted-proxies` | `TRUSTED_PROXIES: "*"` | Confianza ciega en cabeceras `X-Forwarded-*` (spoofing de IP para eludir throttles y alteración de Host headers). |

---

## 4. Detalle de Hallazgos y Remediación Inmediata

### [CRITICAL] `oasis-unauthenticated-redis-session-queue`
* **Ubicación:** `docker-compose.yml` (Líneas 22–35, 70–73)
* **Impacto:** Conexión sin contraseña a Redis. Volcado de sesiones de usuario y potencial inyección de gadgets en colas de Laravel (`QUEUE_CONNECTION=redis`).
* **Solución:** Agregar `--requirepass "${REDIS_PASSWORD}"` a Redis y pasar `REDIS_PASSWORD` al entorno de `paymenter`.

### [HIGH] `oasis-world-writable-executable-code`
* **Ubicación:** `docker-compose.yml` (Línea 43), `docker/paymenter/Dockerfile` (Líneas 63–65)
* **Impacto:** Permisos `777` sobre archivos `.php` y plantillas `.blade.php` ejecutados por PHP-FPM.
* **Solución:** Reemplazar `chmod -R 777` por `chown -R nginx:nginx /app/themes /app/extensions && chmod -R 755 /app/themes /app/extensions`.

### [MEDIUM] `oasis-wildcard-trusted-proxies`
* **Ubicación:** `docker-compose.yml` (Línea 63)
* **Impacto:** Permite a clientes falsificar `X-Forwarded-For` y `X-Forwarded-Host`.
* **Solución:** Reemplazar `*` por subredes concretas del proxy (ej. `172.18.0.0/16,127.0.0.1`).
