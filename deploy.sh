#!/usr/bin/env bash

# ==============================================================================
# ALFATH - Sensus Ekonomi 2026 Production Deployment Script
# ==============================================================================
# Usage: ./deploy.sh
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

# 1. Enable Maintenance Mode
echo -e "${YELLOW}🔒 Entering Maintenance Mode...${NC}"
php artisan down --retry=60 || true

# 2. Fetch Latest Changes from Git
echo -e "${GREEN}📥 Pulling latest code from GitHub (main)...${NC}"
git pull origin main

# 3. Install/Update PHP Dependencies
echo -e "${GREEN}📦 Installing Composer Dependencies (Production)...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 4. Install & Build Frontend Assets
echo -e "${GREEN}🎨 Building Frontend Assets (Vite)...${NC}"
if command -v pnpm &> /dev/null; then
    pnpm install --frozen-lockfile
    pnpm run build
elif command -v npm &> /dev/null; then
    npm install
    npm run build
fi

# 5. Database Migrations (Safe Mode - No Drop/Truncate)
echo -e "${GREEN}🗄️ Running Database Migrations...${NC}"
php artisan migrate --force

# 6. Ensure Storage Link
echo -e "${GREEN}🔗 Verifying Storage Link...${NC}"
php artisan storage:link || true

# 7. Clear & Optimize Application Caches
echo -e "${GREEN}⚡ Optimizing Application Caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Restart Queue Workers
echo -e "${GREEN}🔄 Restarting Queue Workers...${NC}"
php artisan queue:restart || true

# 9. Set Permissions for Storage & Bootstrap Cache (If running on Linux)
if [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
    echo -e "${GREEN}🔑 Setting Directory Permissions...${NC}"
    chmod -R 775 storage bootstrap/cache database || true
fi

# 10. Exit Maintenance Mode
echo -e "${GREEN}🔓 Exiting Maintenance Mode...${NC}"
php artisan up

echo -e "${BLUE}=====================================================${NC}"
echo -e "${GREEN}✅ ALFATH SE2026 Deployment Completed Successfully!${NC}"
echo -e "${BLUE}=====================================================${NC}"
