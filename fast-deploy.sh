#!/bin/bash

# Fast Deploy Script for app.get.promo
# Usage: ./fast-deploy.sh

set -e  # Exit on error

echo "🚀 Starting deployment..."

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_DIR="/home/admin/domains/app.get.promo/private"
PHP="/usr/local/php83/bin/php"

cd $PROJECT_DIR

# 1. Pull latest code from Git
echo -e "${BLUE}📥 Pulling latest code from Git...${NC}"
git pull origin main

# 2. Install/Update Composer dependencies
echo -e "${BLUE}📦 Installing Composer dependencies...${NC}"
/usr/local/bin/composer83 install --no-dev --optimize-autoloader --no-interaction

# 3. Clear all caches
echo -e "${BLUE}🧹 Clearing caches...${NC}"
$PHP artisan config:clear
$PHP artisan cache:clear
$PHP artisan view:clear
$PHP artisan route:clear

# 4. Optimize for production
echo -e "${BLUE}⚡ Optimizing for production...${NC}"
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

# 5. Run migrations (if any)
echo -e "${BLUE}🗄️  Running database migrations...${NC}"
$PHP artisan migrate --force

# 6. Restart queue worker
echo -e "${BLUE}🔄 Restarting queue worker...${NC}"
$PHP artisan queue:restart

# If using supervisor, restart it
if command -v supervisorctl &> /dev/null; then
    supervisorctl restart laravel-worker:* 2>/dev/null || echo "Supervisor not configured or worker not running"
fi

# 7. Set permissions
echo -e "${BLUE}🔐 Setting permissions...${NC}"
chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${GREEN}🌐 Visit: http://app.get.promo${NC}"

