#!/usr/bin/env bash

# ==============================================================================
# ALFATH - Sensus Ekonomi 2026 Production Deployment Script
# ==============================================================================
# Priority: Docker Compose V2 (docker compose) over Legacy Python v1 (docker-compose)
# ==============================================================================

set -e

# Color definitions for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}=====================================================${NC}"
echo -e "${BLUE}🚀 Starting ALFATH SE2026 Deployment Process...${NC}"
echo -e "${BLUE}=====================================================${NC}"

# 1. Resolve Unstaged Changes on Server & Fetch Latest Code
echo -e "${YELLOW}🧹 Stashing local unstaged changes on server to prevent git conflicts...${NC}"
git stash --include-untracked || true

echo -e "${GREEN}📥 Fetching & Syncing latest code from GitHub (main)...${NC}"
git fetch origin main
git reset --hard origin/main

# 2. Check if Docker container is running or needs a full rebuild
FORCE_BUILD=false
if [ "$1" == "--build" ]; then
    FORCE_BUILD=true
fi

# Detect running Docker container or native PHP
IS_DOCKER_RUNNING=false
PHP_EXEC=""
COMPOSER_EXEC=""

# Helper function to invoke docker compose (Prefer Docker CLI v2 plugin)
run_docker_compose() {
    if command -v docker &> /dev/null && docker compose version &> /dev/null; then
        docker compose "$@"
    elif command -v docker-compose &> /dev/null; then
        docker-compose "$@"
    fi
}

if command -v docker &> /dev/null && docker ps | grep -q "alfath-franken"; then
    IS_DOCKER_RUNNING=true
    PHP_EXEC="docker exec -i alfath-franken php"
    COMPOSER_EXEC="docker exec -i alfath-franken composer"
elif command -v php &> /dev/null; then
    PHP_EXEC="php"
    COMPOSER_EXEC="composer"
fi

# 3. Enter Maintenance Mode
if [ -n "$PHP_EXEC" ]; then
    echo -e "${YELLOW}🔒 Entering Maintenance Mode...${NC}"
    $PHP_EXEC artisan down --retry=60 || true
fi

# 4. Handle Execution (Fast In-Container vs Full Rebuild)
if [ "$FORCE_BUILD" = true ] || [ "$IS_DOCKER_RUNNING" = false ]; then
    echo -e "${YELLOW}🐳 Starting / Refreshing Docker Container (FrankenPHP)...${NC}"
    
    # Remove any stuck old container if KeyError/ContainerConfig occurred
    docker rm -f alfath-franken 2>/dev/null || true

    if [ "$FORCE_BUILD" = true ]; then
        run_docker_compose up -d --build
    else
        run_docker_compose up -d
    fi
    
    # Re-assign execution commands after container start
    PHP_EXEC="docker exec -i alfath-franken php"
    COMPOSER_EXEC="docker exec -i alfath-franken composer"
else
    echo -e "${GREEN}⚡ Container is active. Performing Fast In-Container Update (No slow rebuild)...${NC}"
fi

# 5. Run Composer & Build Assets inside container or host
if [ -n "$COMPOSER_EXEC" ]; then
    echo -e "${GREEN}📦 Installing Composer Dependencies...${NC}"
    $COMPOSER_EXEC install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

echo -e "${GREEN}🎨 Building Frontend Assets (Vite)...${NC}"
if [ "$IS_DOCKER_RUNNING" = true ] || [ -f "/.dockerenv" ] || docker ps | grep -q "alfath-franken"; then
    docker exec -i alfath-franken pnpm install --frozen-lockfile || true
    docker exec -i alfath-franken pnpm run build || true
elif command -v pnpm &> /dev/null; then
    pnpm install --frozen-lockfile
    pnpm run build
elif command -v npm &> /dev/null; then
    npm install
    npm run build
fi

# 6. Execute Laravel Artisan Tasks (Migrations & Caches)
if [ -n "$PHP_EXEC" ]; then
    echo -e "${GREEN}🗄️ Running Safe Database Migrations...${NC}"
    $PHP_EXEC artisan migrate --force

    echo -e "${GREEN}🔗 Verifying Storage Link...${NC}"
    $PHP_EXEC artisan storage:link || true

    echo -e "${GREEN}⚡ Optimizing Application Caches...${NC}"
    $PHP_EXEC artisan config:clear || true
    $PHP_EXEC artisan route:clear || true
    $PHP_EXEC artisan view:clear || true
    $PHP_EXEC artisan cache:clear || true

    $PHP_EXEC artisan config:cache || true
    $PHP_EXEC artisan route:cache || true
    $PHP_EXEC artisan view:cache || true
    $PHP_EXEC artisan event:cache || true

    echo -e "${GREEN}🔄 Restarting Queue Workers...${NC}"
    $PHP_EXEC artisan queue:restart || true

    echo -e "${GREEN}🔓 Exiting Maintenance Mode...${NC}"
    $PHP_EXEC artisan up || true
fi

# 7. Set Directory Permissions for Linux Hosts
if [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
    chmod -R 775 storage bootstrap/cache database || true
fi

echo -e "${BLUE}=====================================================${NC}"
echo -e "${GREEN}⚡ ALFATH SE2026 Deployment Completed Successfully!${NC}"
echo -e "${BLUE}=====================================================${NC}"
