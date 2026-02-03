#!/bin/bash
set -e

echo "=== Shopify Tags System - Auto Setup ==="

# Run migrations (creates tables)
echo "Running database migrations..."
php artisan migrate --force --no-interaction || echo "Migration failed - check DB connection. Starting anyway..."

# Ensure all existing AI conversations have type field set (if column exists)
echo "Updating existing AI conversations..."
php artisan db:seed --class=UpdateAiConversationsTypeSeeder --force 2>/dev/null || echo "Could not update existing conversations - continuing..."

# Seed default AI prompt if missing
php artisan db:seed --class=PromptTemplateSeeder --force 2>/dev/null || true

# Clear and cache config/views (avoid stale Blade cache)
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:clear 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Start queue worker in background (processes Order Processing jobs)
echo "Starting queue worker..."
php artisan queue:work database --queue=order-processing,default --sleep=3 --tries=3 --max-time=3600 &
WORKER_PID=$!

# Start the server (foreground)
echo "Starting Zyg Automations server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
