# Railway Deployment Guide

## Installing Railway CLI

### Option 1: Using npm (Recommended)
```bash
npm install -g @railway/cli
```

### Option 2: Using Homebrew (macOS/Linux)
```bash
brew install railway
```

### Option 3: Using Scoop (Windows)
```bash
scoop install railway
```

### Option 4: Direct Download
Visit: https://docs.railway.app/develop/cli#installation

## Railway Deployment Steps

### 1. Login to Railway
```bash
railway login
```

### 2. Initialize Railway Project
```bash
railway init
```

### 3. Link to Existing Project (if you have one)
```bash
railway link
```

### 4. Set Environment Variables
```bash
# Set database connection
railway variables set DB_CONNECTION=mysql
railway variables set DB_HOST=your_db_host
railway variables set DB_DATABASE=your_database
railway variables set DB_USERNAME=your_username
railway variables set DB_PASSWORD=your_password

# Set application variables
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set APP_URL=https://your-app.railway.app

# Set API keys
railway variables set OPENROUTER_API_KEY=your_key
railway variables set SHOPIFY_WEBHOOK_SECRET=your_secret

# Generate app key
railway run php artisan key:generate
```

### 5. Deploy
```bash
railway up
```

### 6. Run Migrations
```bash
railway run php artisan migrate --force
```

### 7. Set Up Queue Worker

Create a new service in Railway dashboard:
- Service Type: Worker
- Start Command: `php artisan queue:work --queue=order-processing --sleep=3 --tries=3`

## Alternative: Deploy via GitHub

1. Connect your GitHub repository to Railway
2. Railway will automatically detect Laravel and deploy
3. Set environment variables in Railway dashboard
4. Add a worker service for queue processing

## Railway Configuration

The project includes `railway.json` with the following configuration:
- Build command: Installs dependencies and builds assets
- Start command: Runs Laravel's built-in server
- Restart policy: Restarts on failure

## Troubleshooting

### Railway CLI Not Found
If you get `railway: command not found`:
1. Install Railway CLI using one of the methods above
2. Verify installation: `railway --version`
3. Add Railway to your PATH if needed

### Build Failures
- Check PHP version compatibility (PHP 8.2+)
- Verify all environment variables are set
- Check build logs in Railway dashboard

### Queue Not Processing
- Make sure you've added a Worker service
- Check worker logs in Railway dashboard
- Verify QUEUE_CONNECTION is set correctly

## Railway vs Other Platforms

Railway is a modern alternative to Heroku. If you prefer other platforms:
- **Heroku**: See `Procfile` in the project
- **DigitalOcean App Platform**: See `DEPLOYMENT.md`
- **VPS**: See `DEPLOYMENT.md` for manual deployment
