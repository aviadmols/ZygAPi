# Zyg - Multi-Tenant Automation Platform

A production-ready Laravel 11 multi-tenant automation platform for e-commerce stores with Shopify and Recharge integrations.

## Features

- Multi-tenant architecture scoped by Shop
- Shopify and Recharge integrations
- Automation engine with workflow execution
- Dry-run simulations
- Chat-driven iteration with OpenRouter AI
- Webhook handling
- Structured logging and domain logs
- Version history for automations

## Local Setup

### Requirements

- PHP 8.2+
- PostgreSQL
- Redis
- Composer
- Node.js & NPM

### Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=zyg
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379

   QUEUE_CONNECTION=redis

   OPENROUTER_API_KEY=your_openrouter_api_key
   OPENROUTER_DEFAULT_MODEL=anthropic/claude-3.5-sonnet
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Seed demo data:
   ```bash
   php artisan db:seed --class=DemoSeeder
   ```

7. Install Filament admin panel:
   ```bash
   php artisan filament:install --panels
   ```

8. Create admin user:
   ```bash
   php artisan make:filament-user
   ```

## Running the Application

### Development Server

```bash
php artisan serve
```

### Queue Worker

```bash
php artisan queue:work
```

### Scheduler

Add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use Laravel's built-in scheduler command in production.

## Webhook Setup

### Shopify Webhooks

Configure webhooks in Shopify admin pointing to:
- `POST /webhooks/shopify/{shop}/orders/create`
- `POST /webhooks/shopify/{shop}/orders/update`

### Recharge Webhooks

Configure webhooks in Recharge pointing to:
- `POST /webhooks/recharge/{shop}/subscription/created`
- `POST /webhooks/recharge/{shop}/subscription/updated`

## Railway Deployment

See the comprehensive [Railway Deployment Guide](docs/RAILWAY_DEPLOYMENT.md) for detailed instructions.

### Quick Start

1. **Create Railway Project**
   - Go to [Railway Dashboard](https://railway.app/dashboard)
   - Click "New Project" → "Deploy from GitHub repo"

2. **Add Services** (3 services needed):
   - **Web**: `php artisan serve --host=0.0.0.0 --port=$PORT`
   - **Worker**: `php artisan queue:work redis --tries=3 --timeout=300`
   - **Scheduler**: `php artisan schedule:work`

3. **Add Databases**:
   - PostgreSQL (for application data)
   - Redis (for queue and cache)

4. **Set Environment Variables**:
   - `APP_KEY` (generate with `php artisan key:generate --show`)
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL` (your Railway domain)
   - `QUEUE_CONNECTION=redis`
   - `OPENROUTER_API_KEY`
   - Database and Redis variables (auto-injected by Railway)

5. **Run Migrations**:
   ```bash
   railway run php artisan migrate --force
   ```

6. **Create Admin User**:
   ```bash
   railway run php artisan make:filament-user
   ```

For detailed step-by-step instructions, see [RAILWAY_DEPLOYMENT.md](docs/RAILWAY_DEPLOYMENT.md).

## API Endpoints

### Playground

- `POST /api/internal/playground/analyze` - Analyze payload and generate patch suggestions
- `POST /api/internal/playground/run` - Run automation in playground mode

### Automations

- `POST /api/automations/{automation_id}/run` - Run automation manually (supports `order_ids` array for bulk execution)
- `POST /api/automations/{automation_id}/webhook-url` - Get webhook URL for webhook-triggered automations

### Runs

- `POST /api/runs/{run_id}/retry` - Retry a failed run

### Webhooks

- `POST /webhooks/shopify/{shop_id}/orders/create` - Shopify order created webhook
- `POST /webhooks/shopify/{shop_id}/orders/update` - Shopify order updated webhook
- `POST /webhooks/recharge/{shop_id}/subscription/created` - Recharge subscription created webhook
- `POST /webhooks/recharge/{shop_id}/subscription/updated` - Recharge subscription updated webhook

## Documentation

- [Engineering Guidelines](docs/ENGINEERING_GUIDELINES.md)
- [Automation DSL](docs/AUTOMATION_DSL.md)
- [Concurrency and Error Handling](docs/CONCURRENCY_AND_ERROR_HANDLING.md)
- [Concurrency and Error Handling](docs/CONCURRENCY_AND_ERROR_HANDLING.md)

## License

MIT
