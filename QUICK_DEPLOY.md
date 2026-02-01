# Quick Deployment Guide

## Fastest Way to Deploy

### 1. Upload Files to Server

**Option A: Using Git (Recommended)**
```bash
ssh user@your-server.com
cd /var/www/html
git clone git@github.com:aviadmols/ZygAPi.git shopify-tags
cd shopify-tags
```

**Option B: Using FTP/SFTP**
- Upload all files to your server
- Make sure `.env` file is NOT uploaded (security)

### 2. Run Deployment Script

```bash
# Make script executable
chmod +x deploy.sh

# Run deployment
./deploy.sh
```

**OR manually:**

```bash
# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with your settings (database, API keys, etc.)
nano .env

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Run migrations
php artisan migrate --force

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Configure Web Server

**Apache:** Point DocumentRoot to `/path/to/shopify-tags/public`

**Nginx:** Point root to `/path/to/shopify-tags/public`

### 4. Set Up Queue Worker

```bash
# Create systemd service (see DEPLOYMENT.md for full instructions)
# OR run manually:
php artisan queue:work --queue=order-processing
```

### 5. Set Up Cron Job

```bash
crontab -e
# Add this line:
* * * * * cd /path/to/shopify-tags && php artisan schedule:run >> /dev/null 2>&1
```

## Required .env Settings

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=database

OPENROUTER_API_KEY=your_key_here
SHOPIFY_WEBHOOK_SECRET=your_secret_here
```

## Test Deployment

1. Visit: `https://your-domain.com`
2. Register/Login
3. Add a store
4. Test order processing
5. Check queue worker is processing jobs

## Common Issues

**500 Error:** Check file permissions and .env configuration
**Queue not working:** Make sure queue worker is running
**Database error:** Verify database credentials in .env

For detailed instructions, see DEPLOYMENT.md
