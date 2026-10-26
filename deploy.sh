#!/usr/bin/env bash
# ==============================================================================
# OASIS STORE — SCRIPT DE DESPLIEGUE AUTOMATIZADO Y BLINDADO
# ==============================================================================
set -euo pipefail

GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}==================================================================${NC}"
echo -e "${CYAN}   🚀 Desplegando OasisStore (Paymenter Blindado + OasisP2P)      ${NC}"
echo -e "${CYAN}==================================================================${NC}"

# 1. Crear archivo .env con secretos criptográficos y permisos 600 si no existe
if [ ! -f .env ] || grep -q "aqui_pon_tu_" .env; then
    echo -e "${YELLOW}[1/5] Generando archivo .env con claves aleatorias de alta entropía...${NC}"
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    if grep -q '^DB_PASSWORD="aqui_pon_tu_' .env; then
        DB_PASS=$(openssl rand -hex 20)
        sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=\"${DB_PASS}\"|" .env
    fi
    if grep -q '^DB_ROOT_PASSWORD="aqui_pon_tu_' .env; then
        ROOT_PASS=$(openssl rand -hex 20)
        sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=\"${ROOT_PASS}\"|" .env
    fi
    if grep -q '^WEBHOOK_SECRET="aqui_pon_tu_' .env; then
        WH_SECRET=$(openssl rand -hex 32)
        sed -i "s|^WEBHOOK_SECRET=.*|WEBHOOK_SECRET=\"${WH_SECRET}\"|" .env
    fi
    chmod 600 .env
    echo -e "${GREEN}✔ Archivo .env creado y protegido con permisos 600.${NC}"
else
    chmod 600 .env
    echo -e "${GREEN}[1/5] Archivo .env existente verificado (permisos 600).${NC}"
fi

# Garantizar que la red externa arturo-net exista antes de levantar el stack
if ! docker network inspect arturo-net >/dev/null 2>&1; then
    echo -e "${YELLOW}Creando red Docker 'arturo-net'...${NC}"
    docker network create arturo-net >/dev/null
fi

# 2. Preparar directorios persistentes en el host
echo -e "${YELLOW}[2/5] Preparando volúmenes persistentes (var, data, storage)...${NC}"
mkdir -p var data/database data/redis storage/logs storage/public/catalog-feed storage/tickets/uploads

# 3. Construir imágenes actualizadas desde fuente y levantar servicios
echo -e "${YELLOW}[3/5] Compilando imagen de Paymenter (PHP >= 8.3.36 + Vite assets) y FastAPI...${NC}"
docker compose build --pull
docker compose up -d

# 4. Auditar versión de PHP y directivas de seguridad dentro del contenedor
echo -e "${YELLOW}[4/5] Verificando versión de PHP y mitigación de seguridad...${NC}"
PHP_VER=$(docker compose exec -T paymenter php -r 'echo PHP_VERSION;')
if ! docker compose exec -T paymenter php -r 'exit(version_compare(PHP_VERSION, "8.3.36", "<") ? 1 : 0);'; then
    echo -e "${RED}✖ ERROR CRÍTICO: PHP (${PHP_VER}) es menor a 8.3.36. Abortando.${NC}"
    exit 1
fi
UPLOAD_PROGRESS=$(docker compose exec -T paymenter php -i | grep "session.upload_progress.enabled" || true)
echo -e "${GREEN}✔ PHP ${PHP_VER} verificado | ${UPLOAD_PROGRESS}${NC}"

# 5. Inicializar Paymenter y crear usuario Admin en el primer arranque
echo -e "${YELLOW}[5/5] ¿Deseas inicializar la tienda y crear tu usuario Admin ('app:init' + 'app:user:create')? [s/N]${NC}"
read -r INIT_CHOICE
if [[ "$INIT_CHOICE" =~ ^([sS][iI]|[sS])$ ]]; then
    docker compose exec paymenter php artisan app:init
    echo -e "${CYAN}Creando cuenta de Administrador...${NC}"
    docker compose exec paymenter php artisan app:user:create
fi

CURRENT_URL=$(grep -E '^APP_URL=' .env | cut -d'=' -f2- | tr -d '"\r' || echo "http://localhost:8085")

echo -e "${CYAN}==================================================================${NC}"
echo -e "${GREEN}✅ OasisStore desplegado y auditado correctamente.${NC}"
echo -e "🌐 Tienda / Portal Cliente:     ${CURRENT_URL}"
echo -e "🛡️  Admin Dashboard (Filament): ${CURRENT_URL}/admin"
echo -e "${CYAN}==================================================================${NC}"
