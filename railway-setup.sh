#!/bin/bash

# Railway Setup Script
# Run this after initial deployment to set up the application

echo "🚀 Setting up Zyg on Railway..."

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Cache configuration
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage links
echo "🔗 Creating storage links..."
php artisan storage:link

echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "1. Create admin user: php artisan make:filament-user"
echo "2. Seed demo data (optional): php artisan db:seed --class=DemoSeeder"
echo "3. Visit your Railway app URL to access the admin panel"
