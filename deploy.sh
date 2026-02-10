#!/bin/bash

# Deployment script for JICOS ERP (aaPanel/Linux)

echo "Starting deployment..."

# 1. Pull latest changes
echo "Pulling latest changes from Git..."
git pull origin main

# 2. Update dependencies (optional, uncomment if needed)
# composer install --no-interaction --prefer-dist --optimize-autoloader

# 3. Run migrations
echo "Running database migrations..."
php artisan migrate --force

# 4. Clear and optimize cache
echo "Optimizing application..."
php artisan optimize:clear
php artisan optimize

echo "Deployment finished successfully!"
