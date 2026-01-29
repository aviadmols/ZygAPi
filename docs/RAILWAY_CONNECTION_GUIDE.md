# Railway Connection Guide - PostgreSQL

## How to Connect ZygAPi to PostgreSQL on Railway

Based on your Railway dashboard, you have:
- ✅ **Postgres** service - Online (green)
- 🔄 **ZygAPi** service - Building

### Step 1: Get PostgreSQL Connection Variables

1. In Railway dashboard, click on your **Postgres** service
2. Go to the **"Variables"** tab
3. You'll see these variables (Railway auto-generates them):
   - `PGHOST` - Database host
   - `PGPORT` - Database port (usually 5432)
   - `PGDATABASE` - Database name
   - `PGUSER` - Database username
   - `PGPASSWORD` - Database password

### Step 2: Link PostgreSQL Variables to ZygAPi

**Option A: Use DATABASE_URL (Easiest - Recommended)**

Railway automatically provides `DATABASE_URL` in your Postgres service. The easiest way is to reference it:

1. Click on your **ZygAPi** service
2. Go to **"Variables"** tab
3. Click **"New Variable"**
4. Add:
   - **Name:** `DATABASE_URL`
   - **Value:** Click on **"Reference Variable"** or **"Add from Service"** → Select **Postgres** service → Select `DATABASE_URL`
   - OR manually copy the `DATABASE_URL` value from Postgres service Variables tab

The application will automatically use `DATABASE_URL` for connection.

**Option B: Use Individual DB Variables**

If you prefer individual variables, add these to **ZygAPi** service:

1. Click on **ZygAPi** service → **"Variables"** tab
2. Click **"New Variable"** and add each:

```env
DB_CONNECTION=pgsql
DB_HOST=<copy PGHOST value from Postgres service>
DB_PORT=<copy PGPORT value from Postgres service>
DB_DATABASE=<copy PGDATABASE value from Postgres service>
DB_USERNAME=<copy PGUSER value from Postgres service>
DB_PASSWORD=<copy PGPASSWORD value from Postgres service>
```

**Option C: Reference PG* Variables (Also Works)**

The application also supports Railway's `PG*` variables directly:

1. Click on **ZygAPi** service → **"Variables"** tab
2. Click **"New Variable"** → **"Reference Variable"** → Select **Postgres** service
3. Reference these variables: `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`
4. Add: `DB_CONNECTION=pgsql`

### Step 3: Add Other Required Variables

Add these to your **ZygAPi** service variables:

```env
APP_NAME=Zyg
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=https://your-app-name.up.railway.app
APP_TIMEZONE=UTC

QUEUE_CONNECTION=redis

OPENROUTER_API_KEY=your_openrouter_api_key
OPENROUTER_DEFAULT_MODEL=anthropic/claude-3.5-sonnet

WEBHOOK_BYPASS_SIGNATURE=false
```

**Note:** If you have a Redis service, Railway will also auto-inject Redis variables.

### Step 4: Generate APP_KEY

Run locally to generate APP_KEY:

```bash
php artisan key:generate --show
```

Copy the output (starts with `base64:`) and set it as `APP_KEY` in Railway.

### Step 5: Wait for Build to Complete

1. Wait for **ZygAPi** to finish building (currently at 00:52)
2. Once it shows "Online" (green), proceed to next step

### Step 6: Run Migrations

After ZygAPi is online:

1. Click on **ZygAPi** service
2. Go to **"Deployments"** → Latest deployment
3. Click **"View Logs"** → **"Open Shell"** (or use Railway CLI)

Run migrations:
```bash
php artisan migrate --force
```

### Step 7: Create Admin User

```bash
php artisan make:filament-user
```

Follow the prompts to create your admin user.

### Step 8: Verify Connection

Test the database connection:

1. In Railway shell:
   ```bash
   php artisan tinker
   ```
2. In tinker:
   ```php
   DB::connection()->getPdo();
   ```
   If it returns a PDO object, connection is working!

### Troubleshooting

#### Connection Refused
- Verify PostgreSQL service is **Online** (green status)
- Check `DB_HOST` matches the PostgreSQL service host
- Ensure `DB_PORT` is correct (usually 5432)

#### Authentication Failed
- Double-check `DB_USERNAME` and `DB_PASSWORD`
- Make sure you copied exact values (no extra spaces)

#### Variables Not Found
- Ensure variables are set in **ZygAPi** service (not just Postgres)
- After adding variables, Railway will redeploy automatically
- Wait for new deployment to complete

#### Build Still Failing
- Check build logs for errors
- Verify Dockerfile includes `libpq-dev` (already fixed)
- Ensure all required PHP extensions are installed

### Quick Checklist

- [ ] PostgreSQL service is Online (green)
- [ ] ZygAPi service variables include (choose one option):
  - **Option A:** `DATABASE_URL` (referenced from Postgres service)
  - **Option B:** `DB_CONNECTION=pgsql` + `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
  - **Option C:** `DB_CONNECTION=pgsql` + `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD` (referenced from Postgres)
- [ ] `APP_KEY` (generated - see Step 4)
- [ ] `APP_URL` (your Railway domain)
- [ ] ZygAPi build completed successfully
- [ ] Migrations run successfully (auto-runs on deploy via Dockerfile CMD)
- [ ] Admin user created (via DemoSeeder or manually)

### Visual Guide

In Railway dashboard:
1. **Postgres** service → **Variables** tab → Copy `PG*` values
2. **ZygAPi** service → **Variables** tab → Add `DB_*` variables
3. **ZygAPi** service → **Deployments** → Wait for "Online"
4. **ZygAPi** service → **Shell** → Run migrations
