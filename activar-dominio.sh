#!/usr/bin/env bash
# ==============================================================================
# OASIS STORE — ACTIVACIÓN DE DOMINIO PÚBLICO (+ CLOUDFLARE TUNNEL)
# ==============================================================================
# Uso: ./activar-dominio.sh <tu-dominio.tech>
# Ejemplo: ./activar-dominio.sh oasisstore.tech
# ==============================================================================
set -euo pipefail

cd "$(dirname "$0")"

GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

RAW_INPUT="${1:-}"

if [ -z "$RAW_INPUT" ]; then
    echo -e "${RED}✖ Error: Debes indicar el dominio como argumento.${NC}"
    echo -e "  Uso: ${CYAN}./activar-dominio.sh mi-tienda.tech${NC}"
    exit 1
fi

CLEAN_DOMAIN=$(echo "$RAW_INPUT" | sed -E 's|^https?://||I; s|/.*$||; s|[[:space:]]||g')

if [ -z "$CLEAN_DOMAIN" ] || [[ ! "$CLEAN_DOMAIN" =~ ^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
    echo -e "${RED}✖ Error: El dominio '${RAW_INPUT}' no tiene un formato válido (ej. oasisstore.tech).${NC}"
    exit 1
fi

NEW_URL="https://${CLEAN_DOMAIN}"

echo -e "${CYAN}==================================================================${NC}"
echo -e "${CYAN}   🌐 Activando Dominio Oficial en OasisStore: ${NEW_URL}${NC}"
echo -e "${CYAN}==================================================================${NC}"

if [ ! -f .env ]; then
    echo -e "${RED}✖ Error: No se encontró el archivo .env en $(pwd). Ejecuta primero ./deploy.sh${NC}"
    exit 1
fi

# 1. Actualizar APP_URL en .env preservando BIND_IP
echo -e "${YELLOW}[1/3] Actualizando APP_URL en .env a ${NEW_URL}...${NC}"
if grep -q '^APP_URL=' .env; then
    sed -i "s|^APP_URL=.*|APP_URL=\"${NEW_URL}\"|" .env
else
    echo "APP_URL=\"${NEW_URL}\"" >> .env
fi
chmod 600 .env
echo -e "${GREEN}✔ .env actualizado (APP_URL=\"${NEW_URL}\").${NC}"

# 2. Recrear contenedores paymenter y webhook-notifier para inyectar el nuevo APP_URL
echo -e "${YELLOW}[2/3] Aplicando nuevo APP_URL en contenedores paymenter y webhook-notifier...${NC}"
docker compose up -d paymenter webhook-notifier
echo -e "${GREEN}✔ Contenedores actualizados y en ejecución.${NC}"

# 3. Sincronizar setting interno de Paymenter y limpiar cachés de Laravel
echo -e "${YELLOW}[3/3] Sincronizando configuración interna de Paymenter y limpiando caché...${NC}"
if ! docker compose exec -T paymenter php artisan settings:change app_url "${NEW_URL}" >/dev/null 2>&1; then
    docker compose exec -T paymenter php -r "
        require '/app/vendor/autoload.php';
        \$app = require_once '/app/bootstrap/app.php';
        \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        if (class_exists(\App\Models\Setting::class)) {
            \App\Models\Setting::updateOrCreate(['key' => 'app_url'], ['value' => '${NEW_URL}']);
        }
    "
fi

docker compose exec -T paymenter php artisan config:clear
docker compose exec -T paymenter php artisan cache:clear
docker compose exec -T paymenter php artisan view:clear

echo -e "${CYAN}==================================================================${NC}"
echo -e "${GREEN}✅ Dominio activado exitosamente en OasisStore.${NC}"
echo -e "🌍 Tienda Pública (HTTPS):      ${NEW_URL}"
echo -e "🛡️  Admin Dashboard (Filament): ${NEW_URL}/admin"
echo -e "${CYAN}==================================================================${NC}"
