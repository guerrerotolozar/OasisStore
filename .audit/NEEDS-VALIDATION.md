# Environmental & Deployment Validation Leads — OasisStore

## 1. Seguridad en la Red `arturo-net`
* **Archivo:** `docker-compose.yml` (`arturo-net: external: true`)
* **Acción:**
  - `oasis-paymenter`, `oasis-webhook` y `oasis-catalog-scraper` comparten la red `arturo-net` con el orquestador principal Arturo.
  - Asegurar que el contenedor `cache` (Redis) esté conectado **únicamente** a `oasis_net` y nunca a `arturo-net`, garantizando aislamiento de la base de datos de sesiones y colas.

## 2. Validación de Secretos HMAC en Producción
* **Archivo:** `webhook-service/main.py`
* **Acción:**
  - El servicio de webhooks valida que `WEBHOOK_SECRET` tenga al menos 32 caracteres y no contenga cadenas por defecto.
  - Verificar que el script `./deploy.sh` haya generado una clave aleatoria segura en `.env` tanto para `OASIS_WEBHOOK_SECRET` como para `WEBHOOK_SECRET`.

## 3. Ingress de Cloudflare Tunnel
* **Archivo:** `docker-compose.yml` (`oasis-cloudflared`)
* **Acción:**
  - En Cloudflare Zero Trust, validar que la regla de Ingress apunte a `http://oasis-paymenter:80`.
  - Asegurar que los puertos de MariaDB (3306) y Redis (6379) permanezcan completamente cerrados al exterior.
