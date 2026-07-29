#!/usr/bin/env bash

# ==============================================================================
# ALFATH - Sensus Ekonomi 2026 Production Deployment Script
# ==============================================================================
# Smart Auto-Detector for Git Stash, Native PHP, & Docker FrankenPHP Containers
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
echo -e "${YELLOW}🧹 Stashing local unstaged changes on server to prevent git pull conflicts...${NC}"
git stash --include-untracked || true

echo -e "${GREEN}📥 Fetching & Syncing latest code from GitHub (main)...${NC}"
git fetch origin main
git reset --hard origin/main

# 2. Detect PHP & Container Execution Environment
PHP_CMD=""
COMPOSER_CMD=""

if command -v php &> /dev/null; then
    PHP_CMD="php"
elif command -v docker &> /dev/null && docker ps | grep -q "alfath-franken"; then
    PHP_CMD="docker exec -i alfath-franken php"
elif command -v docker-compose &> /dev/null; then
    PHP_CMD="docker-compose exec -T alfath-franken php"
elif command -v docker &> /dev/null && docker compose version &> /dev/null; then
    PHP_CMD="docker compose exec -T alfath-franken php"
fi

if command -v composer &> /dev/null; then
    COMPOSER_CMD="composer"
elif command -v docker &> /dev/null && docker ps | grep -q "alfath-franken"; then
    COMPOSER_CMD="docker exec -i alfath-franken composer"
fi

# 3. Enter Maintenance Mode (If PHP available before build)
if [ -n "$PHP_CMD" ]; then
    echo -e "${YELLOW}🔒 Entering Maintenance Mode...${NC}"
    $PHP_CMD artisan down --retry=60 || true
fi

# 4. Check Docker Environment vs Native Environment
if command -v docker-compose &> /dev/null || (command -v docker &> /dev/null && docker compose version &> /dev/null); then
    echo -e "${GREEN}🐳 Rebuilding & restarting Docker containers (FrankenPHP)...${NC}"
    if command -v docker-compose &> /dev/null; then
        docker-compose up -d --build
    else
        docker compose up -d --build
    fi
    
    # Update PHP_CMD for the running container after build
    PHP_CMD="docker exec -i alfath-franken php"
    COMPOSER_CMD="docker exec -i alfath-franken composer"
else
    # Native Server Environment
    if [ -n "$COMPOSER_CMD" ]; then
        echo -e "${GREEN}📦 Installing Composer Dependencies (Production)...${NC}"
        $COMPOSER_CMD install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    fi

    echo -e "${GREEN}🎨 Building Frontend Assets...${NC}"
    if command -v pnpm &> /dev/null; then
        pnpm install --frozen-lockfile
        pnpm run build
    elif command -v npm &> /dev/null; then
        npm install
        npm run build
    fi
fi

# 5. Execute Laravel Artisan Tasks (Migrations & Caches)
if [ -n "$PHP_CMD" ]; then
    echo -e "${GREEN}🗄️ Running Safe Database Migrations...${NC}"
    $PHP_CMD artisan migrate --force

    echo -e "${GREEN}🔗 Verifying Storage Link...${NC}"
    $PHP_CMD artisan storage:link || true

    echo -e "${GREEN}⚡ Optimizing Application Caches...${NC}"
    $PHP_CMD artisan config:clear || true
    $PHP_CMD artisan route:clear || true
    $PHP_CMD artisan view:clear || true
    $PHP_CMD artisan cache:clear || true

    $PHP_CMD artisan config:cache || true
    $PHP_CMD artisan route:cache || true
    $PHP_CMD artisan view:cache || true
    $PHP_CMD artisan event:cache || true

    echo -e "${GREEN}🔄 Restarting Queue Workers...${NC}"
    $PHP_CMD artisan queue:restart || true

    echo -e "${GREEN}🔓 Exiting Maintenance Mode...${NC}"
    $PHP_CMD artisan up || true
else
    echo -e "${RED}⚠️ Warning: PHP command could not be executed directly on host or container.${NC}"
fi

# 6. Set Directory Permissions for Linux Hosts
if [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
    echo -e "${GREEN}🔑 Setting Directory Permissions...${NC}"
    chmod -R 775 storage bootstrap/cache database || true
fi

echo -e "${BLUE}=====================================================${NC}"
echo -e "${GREEN}✅ ALFATH SE2026 Deployment Completed Successfully!${NC}"
echo -e "${BLUE}=====================================================${NC}"
