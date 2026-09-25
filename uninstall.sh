#!/bin/bash
# ==============================================================================
# Amnezia VPN Control Panel - Complete Uninstall Script
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

echo -e "${RED}"
echo "=================================================================="
echo "          Amnezia VPN Control Panel — Удаление панели             "
echo "=================================================================="
echo -e "${NC}"

# Check root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Ошибка: Скрипт должен быть запущен с правами root (sudo bash)${NC}"
    exit 1
fi

INSTALL_DIR="/opt/amnezia-panel"
PANEL_PORT="8082"

echo -e "${YELLOW}ВНИМАНИЕ:${NC} Это действие полностью остановит и удалит веб-панель управления и её базу данных."
echo -e "Сами VPN-контейнеры с пирами на сервере затронуты не будут."
echo ""
read -p "Вы уверены, что хотите удалить Amnezia VPN Control Panel? (y/N): " -r CONFIRM
echo ""

if [[ ! "$CONFIRM" =~ ^[YyДд]$ ]]; then
    echo -e "${BLUE}Удаление отменено пользователем.${NC}"
    exit 0
fi

echo -e "${CYAN}Шаг 1: Остановка и удаление контейнеров панели...${NC}"
if [ -d "$INSTALL_DIR" ]; then
    cd "$INSTALL_DIR"
    docker compose down -v --remove-orphans 2>/dev/null || docker-compose down -v 2>/dev/null || true
fi

# Stop and remove named panel containers if still running
docker rm -fv amnezia-panel-web amnezia-panel-db amnezia-panel-dind 2>/dev/null || true
docker volume rm amnezia-panel_db_data amnezia-panel_dind_data 2>/dev/null || true
docker network rm amnezia-panel_default 2>/dev/null || true

echo -e "${CYAN}Шаг 2: Закрытие порта панели в файрволе...${NC}"
if command -v ufw >/dev/null 2>&1; then
    ufw delete allow ${PANEL_PORT}/tcp >/dev/null 2>&1 || true
fi

echo -e "${CYAN}Шаг 3: Удаление файлов панели...${NC}"
if [ -d "$INSTALL_DIR" ]; then
    rm -rf "$INSTALL_DIR"
fi

echo ""
echo -e "${GREEN}==================================================================${NC}"
echo -e "${GREEN}       Amnezia VPN Control Panel успешно удалена с сервера!       ${NC}"
echo -e "${GREEN}==================================================================${NC}"
