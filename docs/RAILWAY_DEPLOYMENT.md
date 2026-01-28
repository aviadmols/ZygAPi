# Railway Deployment Guide

This guide will walk you through deploying Zyg to Railway.

## Prerequisites

- Railway account (sign up at https://railway.com/)
- GitHub repository with your code (or Railway CLI)

## Step 1: Create Railway Project

1. Go to [Railway Dashboard](https://railway.app/dashboard)
2. Click "New Project"
3. Select "Deploy from GitHub repo" (recommended) or "Empty Project"

## Step 2: Add Services

You need to create **3 services** for the application:

### Service 1: Web Application

1. Click "New Service" → "GitHub Repo" (or "Empty Service")
2. Connect your repository
3. Railway will auto-detect it's a PHP/Laravel project
4. Set the following:
   - **Name**: `zyg-web`
   - **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`
   - **Root Directory**: `/` (default)

### Service 2: Queue Worker

1. Click "New Service" → "GitHub Repo"
2. Select the same repository
3. Set the following:
   - **Name**: `zyg-worker`
   - **Start Command**: `php artisan queue:work redis --tries=3 --timeout=300`
   - **Root Directory**: `/` (default)

### Service 3: Scheduler

1. Click "New Service" → "GitHub Repo"
2. Select the same repository
3. Set the following:
   - **Name**: `zyg-scheduler`
   - **Start Command**: `php artisan schedule:work`
   - **Root Directory**: `/` (default)

## Step 3: Add Database (PostgreSQL)

1. Click "New" → "Database" → "Add PostgreSQL"
2. Railway will automatically create a PostgreSQL database
3. Note the connection variables (they'll be auto-injected)

## Step 4: Add Redis

1. Click "New" → "Database" → "Add Redis"
2. Railway will automatically create a Redis instance
3. Note the connection variables (they'll be auto-injected)

## Step 5: Configure Environment Variables

For **each service** (web, worker, scheduler), set these environment variables:

### Required Variables

```env
APP_NAME=Zyg
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=https://your-app-name.up.railway.app
APP_TIMEZONE=UTC

# Database (auto-injected by Railway PostgreSQL service)
DB_CONNECTION=pgsql
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD are auto-set

# Redis (auto-injected by Railway Redis service)
REDIS_CLIENT=phpredis
# REDIS_HOST, REDIS_PORT, REDIS_PASSWORD are auto-set

# Queue
QUEUE_CONNECTION=redis

# OpenRouter
OPENROUTER_API_KEY=your_openrouter_api_key
OPENROUTER_DEFAULT_MODEL=anthropic/claude-3.5-sonnet

# Webhooks
WEBHOOK_BYPASS_SIGNATURE=false

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
```

### Generate APP_KEY

Run locally:
```bash
php artisan key:generate --show
```

Copy the output and set it as `APP_KEY` in Railway.

## Step 6: Run Migrations

After the web service is deployed:

1. Go to your web service in Railway
2. Click "Deployments" → Latest deployment → "View Logs"
3. Click "Open Shell" or use Railway CLI:

```bash
railway run php artisan migrate --force
```

Or add a one-time command in Railway:
- Service → "Deploy" → "Run Command": `php artisan migrate --force`

## Step 7: Seed Demo Data (Optional)

```bash
railway run php artisan db:seed --class=DemoSeeder
```

## Step 8: Create Admin User

```bash
railway run php artisan make:filament-user
```

Follow the prompts to create your admin user.

## Step 9: Configure Public Domain

1. Go to your web service
2. Click "Settings" → "Networking"
3. Click "Generate Domain" or add a custom domain
4. Update `APP_URL` in environment variables to match your domain

## Step 10: Verify Deployment

1. Visit your Railway app URL
2. You should see the Filament login page
3. Log in with your admin credentials
4. Test creating a shop and automation

## Monitoring

### View Logs

- **Web Service**: Click service → "Deployments" → "View Logs"
- **Worker Service**: Click service → "Deployments" → "View Logs"
- **Scheduler Service**: Click service → "Deployments" → "View Logs"

### Check Queue Status

Access your app and check:
- Filament admin panel → Runs (to see queued/running jobs)
- Or use Railway shell: `php artisan queue:monitor`

## Troubleshooting

### Database Connection Issues

- Verify PostgreSQL service is running
- Check environment variables are set correctly
- Ensure `DB_CONNECTION=pgsql` is set

### Queue Not Processing

- Verify Redis service is running
- Check `QUEUE_CONNECTION=redis` is set
- Ensure worker service is running
- Check worker logs for errors

### Scheduler Not Running

- Verify scheduler service is running
- Check scheduler logs
- Ensure database connection is working (scheduler needs DB)

### Build Failures

- Check build logs for composer errors
- Ensure `composer.json` is correct
- Verify PHP version compatibility (8.4+)

### Memory Issues

- Increase service memory in Railway settings
- Default is usually 512MB, increase to 1GB if needed

## Scaling

### Scale Workers

To handle more concurrent requests:

1. Go to worker service
2. Click "Settings" → "Scaling"
3. Increase "Replicas" (e.g., 2-3 workers)

### Scale Web Service

1. Go to web service
2. Click "Settings" → "Scaling"
3. Increase "Replicas" for load balancing

## Cost Optimization

- Use Railway's spending limits to control costs
- Monitor usage in Railway dashboard
- Consider using Railway's startup program if eligible

## Continuous Deployment

Railway automatically deploys on every push to your main branch. To disable:

1. Go to service → "Settings" → "Source"
2. Toggle "Auto Deploy"

## Additional Resources

- [Railway Documentation](https://docs.railway.app/)
- [Railway Discord](https://discord.gg/railway)
- [Railway Status](https://status.railway.app/)
