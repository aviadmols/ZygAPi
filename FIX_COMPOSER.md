# Fix Composer Lock File Issue

If you're getting errors about PHP 8.4 requirements when running `composer install` on PHP 8.2, follow these steps:

## Solution 1: Pull Latest Changes (Recommended)

The repository has been updated with PHP 8.2 compatible versions. Pull the latest changes:

```bash
git pull origin main
composer install --optimize-autoloader --no-dev --no-interaction
```

## Solution 2: Update Composer Lock File Manually

If Solution 1 doesn't work, update the lock file on the server:

```bash
# Remove old lock file
rm composer.lock

# Update dependencies
composer update --with-all-dependencies --no-interaction --no-dev

# This will downgrade Symfony packages to v7.4 which are compatible with PHP 8.2
```

## Solution 3: Force Update with Platform Config

Add platform config to force PHP 8.2:

```bash
# Edit composer.json and ensure this is in the config section:
# "platform": {
#     "php": "8.2.30"
# }

# Then run:
composer update --with-all-dependencies --no-interaction --no-dev
```

## Verify Fix

After updating, verify the versions:

```bash
composer show symfony/clock symfony/css-selector symfony/event-dispatcher symfony/string symfony/translation
```

You should see versions like:
- symfony/clock v7.4.0
- symfony/css-selector v7.4.0
- symfony/event-dispatcher v7.4.4
- symfony/string v7.4.4
- symfony/translation v7.4.4

## Quick Fix Command

Run this single command to fix everything:

```bash
git pull origin main && composer update --with-all-dependencies --no-interaction --no-dev && composer install --optimize-autoloader --no-dev --no-interaction
```
