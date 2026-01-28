#!/bin/bash

# Bash script to deploy Zyg to GitHub
# Run this script from the project root directory

echo "🚀 Deploying Zyg to GitHub..."

# Check if git is initialized
if [ ! -d .git ]; then
    echo "Initializing git repository..."
    git init
fi

# Add remote (will overwrite if exists)
echo "Setting up remote repository..."
git remote remove origin 2>/dev/null || true
git remote add origin git@github.com:aviadmols/ZygAPi.git

# Add all files
echo "Adding files to git..."
git add .

# Commit
echo "Creating commit..."
git commit -m "Initial commit: Zyg Multi-Tenant Automation Platform

- Laravel 12 multi-tenant automation platform
- Shopify and Recharge integrations
- Automation engine with dry-run support
- Chat-driven iteration with OpenRouter AI
- Webhook handling and queue processing
- Filament admin panel
- Railway deployment ready"

# Set main branch
echo "Setting main branch..."
git branch -M main

# Push to GitHub
echo "Pushing to GitHub..."
git push -u origin main

echo "✅ Deployment complete!"
echo "Repository: https://github.com/aviadmols/ZygAPi"
