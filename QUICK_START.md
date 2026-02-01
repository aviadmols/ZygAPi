# Quick Start Guide - Railway Deployment

## Step 1: Install Railway CLI

### Windows (PowerShell):
```powershell
# Using npm (if you have Node.js installed)
npm install -g @railway/cli

# OR using Scoop
scoop install railway

# OR download directly
iwr https://railway.app/install.ps1 | iex
```

### macOS/Linux:
```bash
# Using npm
npm install -g @railway/cli

# OR using Homebrew
brew install railway

# OR using install script
curl -fsSL https://railway.app/install.sh | sh
```

### Verify Installation:
```bash
railway --version
```

## Step 2: Login to Railway

```bash
railway login
```

This will:
1. Open your browser
2. Ask you to login with GitHub, Google, or email
3. Authorize Railway CLI

## Step 3: Initialize Project

### Option A: Create New Project
```bash
cd shopify-tags-system
railway init
```

Follow the prompts:
- Project name: `shopify-tags-system` (or your preferred name)
- Select template: Choose "Empty Project" or "Laravel"

### Option B: Link to Existing Project
If you already have a Railway project:
```bash
railway link
```
Then select your project from the list.

## Step 4: Set Environment Variables

### Set Database Variables:
```bash
railway variables set DB_CONNECTION=mysql
railway variables set DB_HOST=your_database_host
railway variables set DB_DATABASE=your_database_name
railway variables set DB_USERNAME=your_database_user
railway variables set DB_PASSWORD=your_database_password
```

### Set Application Variables:
```bash
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set APP_URL=https://your-app.railway.app

# Generate app key
railway run php artisan key:generate
```

### Set API Keys:
```bash
railway variables set OPENROUTER_API_KEY=your_openrouter_api_key
railway variables set SHOPIFY_WEBHOOK_SECRET=your_webhook_secret
```

### Set Queue Configuration:
```bash
railway variables set QUEUE_CONNECTION=database
```

## Step 5: Add Database Service (if needed)

In Railway dashboard:
1. Click "New" → "Database" → "MySQL"
2. Railway will automatically create database
3. Copy connection details and set them as variables (see Step 4)

## Step 6: Deploy

```bash
railway up
```

This will:
- Build your application
- Deploy it to Railway
- Show you the deployment URL

## Step 7: Run Migrations

```bash
railway run php artisan migrate --force
```

## Step 8: Set Up Queue Worker

### In Railway Dashboard:
1. Click "New" → "Service"
2. Select your project
3. Choose "Empty Service"
4. Set Start Command: `php artisan queue:work --queue=order-processing --sleep=3 --tries=3`
5. Deploy

## Step 9: Access Your Application

After deployment, Railway will provide you with:
- **Web URL**: `https://your-app.railway.app`
- **Dashboard**: https://railway.app/dashboard

## Troubleshooting

### "railway: command not found"
- Make sure Railway CLI is installed
- Check your PATH environment variable
- Try reinstalling: `npm install -g @railway/cli`

### Login Issues
- Make sure you have a Railway account (sign up at https://railway.app)
- Try: `railway login --browserless` for token-based login

### Build Failures
- Check build logs in Railway dashboard
- Verify all environment variables are set
- Ensure PHP 8.2+ is available

### Database Connection Issues
- Verify database service is running
- Check database credentials in variables
- Ensure database service is in the same project

## View Logs

```bash
# View deployment logs
railway logs

# View logs for specific service
railway logs --service your-service-name
```

## Update Environment Variables

```bash
# View all variables
railway variables

# Set a variable
railway variables set KEY=value

# Delete a variable
railway variables delete KEY
```

## Redeploy

After making changes:
```bash
git add .
git commit -m "Your changes"
git push origin main
railway up
```

## Alternative: Deploy via GitHub

1. Go to Railway dashboard: https://railway.app/dashboard
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your repository: `aviadmols/ZygAPi`
5. Railway will automatically detect Laravel and deploy
6. Set environment variables in dashboard
7. Add worker service for queue processing

## Need Help?

- Railway Docs: https://docs.railway.app
- Railway Discord: https://discord.gg/railway
- Check `RAILWAY_DEPLOY.md` for detailed instructions
