#!/bin/bash
# ==============================================================================
# Amnezia VPN Control Panel - Quick Installation Script
# https://github.com/pashkatara/amnezia-control-panel
# ==============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

if [ -t 0 ]; then
    clear 2>/dev/null || true
fi

echo -e "${PURPLE}"
echo "=================================================================="
echo "          Amnezia VPN Control Panel — Установка панели           "
echo "=================================================================="
echo -e "${NC}"

# Check root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Ошибка: Скрипт должен быть запущен с правами root (sudo bash)${NC}"
    exit 1
fi

INSTALL_DIR="/opt/amnezia-panel"
PANEL_PORT="8082"

echo -e "${CYAN}Шаг 1: Проверка и установка системных зависимостей...${NC}"
apt-get update -q
apt-get install -y -q curl git tar zip unzip ufw iptables >/dev/null 2>&1 || true

# Check Docker
if ! command -v docker >/dev/null 2>&1; then
    echo -e "${YELLOW}Установка Docker...${NC}"
    curl -fsSL https://get.docker.com | sh
    systemctl enable --now docker
else
    echo -e "${GREEN}✓ Docker уже установлен${NC}"
fi

# Check Docker Compose
if ! docker compose version >/dev/null 2>&1; then
    echo -e "${YELLOW}Установка плагина Docker Compose...${NC}"
    apt-get install -y -q docker-compose-plugin >/dev/null 2>&1 || true
fi

echo -e "${CYAN}Шаг 2: Подготовка рабочей директории: ${INSTALL_DIR}...${NC}"
if [ ! -f "$INSTALL_DIR/docker-compose.yml" ]; then
    echo -e "${CYAN}Клонирование репозитория с GitHub...${NC}"
    mkdir -p "$INSTALL_DIR"
    git clone https://github.com/pashkatara/amnezia-control-panel.git "$INSTALL_DIR"
fi

cd "$INSTALL_DIR"

# Generate random JWT secret
JWT_SECRET=$(openssl rand -hex 32 2>/dev/null || date +%s%N | sha256sum | head -c 64)
DB_PASS="amnezia"
DB_ROOT_PASS="rootpassword"

if [ ! -f .env ]; then
    echo -e "${CYAN}Создание файла конфигурации .env...${NC}"
    cat <<EOF > .env
APP_ENV=production
APP_NAME="Amnezia VPN Panel"
SESSION_NAME=amnezia_panel_session
DEFAULT_LOCALE=ru

# Database Configuration
DB_HOST=db
DB_PORT=3306
DB_DATABASE=amnezia_panel
DB_USERNAME=amnezia
DB_PASSWORD=${DB_PASS}
DB_ROOT_PASSWORD=${DB_ROOT_PASS}

# Admin Account
ADMIN_EMAIL=admin@amnez.ia
ADMIN_PASSWORD=admin123

# Settings
ALLOW_REGISTRATION=false
JWT_SECRET=${JWT_SECRET}
API_RATE_LIMIT=100

# VPN Defaults
DEFAULT_VPN_PORT_MIN=30000
DEFAULT_VPN_PORT_MAX=65000
DEFAULT_VPN_SUBNET=10.8.1.0/24
DEFAULT_DNS=1.1.1.1,1.0.0.1
EOF
fi

echo -e "${CYAN}Шаг 3: Сборка и запуск контейнеров...${NC}"
docker compose up -d --build

# Open firewall port if UFW is active
if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then
    echo -e "${CYAN}Открытие порта ${PANEL_PORT} в UFW...${NC}"
    ufw allow ${PANEL_PORT}/tcp >/dev/null 2>&1 || true
fi

# Wait for database to initialize
echo -e "${YELLOW}Ожидание готовности базы данных...${NC}"
for i in {1..30}; do
    if docker compose exec -T db mysqladmin ping -h localhost --silent >/dev/null 2>&1; then
        echo -e "${GREEN}✓ База данных готова${NC}"
        break
    fi
    sleep 2
done

# Install composer dependencies if needed
echo -e "${CYAN}Проверка зависимостей приложения...${NC}"
docker compose exec -T web composer install --no-dev --optimize-autoloader --no-security-blocking >/dev/null 2>&1 || true

# Get Server IP
SERVER_IP=$(curl -s -4 ifconfig.me || curl -s -4 icanhazip.com || hostname -I | awk '{print $1}')

echo ""
echo -e "${GREEN}==================================================================${NC}"
echo -e "${GREEN}       Установка Amnezia VPN Control Panel успешно завершена!     ${NC}"
echo -e "${GREEN}==================================================================${NC}"
echo ""
echo -e "  🌐 Адрес панели:    ${CYAN}http://${SERVER_IP}:${PANEL_PORT}${NC}"
echo -e "  👤 Email:           ${YELLOW}admin@amnez.ia${NC}"
echo -e "  🔑 Пароль:          ${YELLOW}admin123${NC}"
echo ""
echo -e "${YELLOW}Рекомендация: смените пароль администратора после первого входа!${NC}"
echo -e "Папка установки: ${INSTALL_DIR}"
echo "=================================================================="
