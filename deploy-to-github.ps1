# PowerShell script to deploy Zyg to GitHub
# Run this script from the project root directory

Write-Host "🚀 Deploying Zyg to GitHub..." -ForegroundColor Green

# Check if git is initialized
if (-not (Test-Path .git)) {
    Write-Host "Initializing git repository..." -ForegroundColor Yellow
    git init
}

# Add remote (will overwrite if exists)
Write-Host "Setting up remote repository..." -ForegroundColor Yellow
git remote remove origin 2>$null
git remote add origin git@github.com:aviadmols/ZygAPi.git

# Add all files
Write-Host "Adding files to git..." -ForegroundColor Yellow
git add .

# Commit
Write-Host "Creating commit..." -ForegroundColor Yellow
git commit -m "Initial commit: Zyg Multi-Tenant Automation Platform

- Laravel 12 multi-tenant automation platform
- Shopify and Recharge integrations
- Automation engine with dry-run support
- Chat-driven iteration with OpenRouter AI
- Webhook handling and queue processing
- Filament admin panel
- Railway deployment ready"

# Set main branch
Write-Host "Setting main branch..." -ForegroundColor Yellow
git branch -M main

# Push to GitHub
Write-Host "Pushing to GitHub..." -ForegroundColor Yellow
git push -u origin main

Write-Host "✅ Deployment complete!" -ForegroundColor Green
Write-Host "Repository: https://github.com/aviadmols/ZygAPi" -ForegroundColor Cyan
