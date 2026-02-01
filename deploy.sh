#!/bin/bash

# Shopify Tags System - Deployment Script
# This script automates the deployment process

set -e

echo "🚀 Starting deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  .env file not found. Copying from .env.example...${NC}"
    cp .env.example .env
    echo -e "${RED}❌ Please configure .env file before continuing!${NC}"
    exit 1
fi

# Install/Update dependencies
echo -e "${GREEN}📦 Installing PHP dependencies...${NC}"
composer install --optimize-autoloader --no-dev --no-interaction

echo -e "${GREEN}📦 Installing Node dependencies...${NC}"
npm install

echo -e "${GREEN}🏗️  Building assets...${NC}"
npm run build

# Generate key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${GREEN}🔑 Generating application key...${NC}"
    php artisan key:generate --force
fi

# Set permissions
echo -e "${GREEN}🔐 Setting permissions...${NC}"
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || echo "Note: Run 'chown -R www-data:www-data storage bootstrap/cache' manually if needed"

# Run migrations
echo -e "${GREEN}🗄️  Running database migrations...${NC}"
php artisan migrate --force

# Clear and cache configuration
echo -e "${GREEN}⚡ Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize autoloader
composer dump-autoload --optimize

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo ""
echo "📋 Next steps:"
echo "1. Make sure queue worker is running: php artisan queue:work --queue=order-processing"
echo "2. Set up cron job: * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo "3. Configure web server to point to: $(pwd)/public"
echo "4. Test the application: https://your-domain.com"
