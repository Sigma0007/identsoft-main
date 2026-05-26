FROM php:8.1-apache

# Force cache bust - 2026-05-26-v2

# Install system dependencies
RUN apt-get update && apt-get install -y \
    zip unzip git curl libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Expose port 80
EXPOSE 80

# Create startup script that generates .env and runs migrations
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Generate .env from environment variables\n\
cat > /var/www/html/.env << ENVEOF\n\
APP_NAME=iDentSoft\n\
APP_ENV=production\n\
APP_KEY=base64:ga8wZ9Ew0nJunxf/v7OHBpEEvWqCc7jcHD0f9UIzTJU=\n\
APP_DEBUG=true\n\
APP_URL=https://identsoft-main.onrender.com\n\
\n\
LOG_CHANNEL=stderr\n\
LOG_LEVEL=error\n\
\n\
DB_CONNECTION=pgsql\n\
DB_HOST=dpg-d8a156ek1jcs73fgq570-a.oregon-postgres.render.com\n\
DB_PORT=5432\n\
DB_DATABASE=identsoft_db\n\
DB_USERNAME=identsoft_db_user\n\
DB_PASSWORD=ePzNDb3ExRVOPY1BpfYEDniXFH0pB5cz\n\
\n\
BROADCAST_DRIVER=log\n\
CACHE_DRIVER=file\n\
QUEUE_CONNECTION=database\n\
SESSION_DRIVER=cookie\n\
SESSION_LIFETIME=120\n\
\n\
MAIL_MAILER=smtp\n\
MAIL_HOST=smtp.mailtrap.io\n\
MAIL_PORT=2525\n\
MAIL_USERNAME=b65cbc3576ba03\n\
MAIL_PASSWORD=cd3f723e8ebd74\n\
MAIL_ENCRYPTION=tls\n\
MAIL_FROM_ADDRESS=from@mail.com\n\
MAIL_FROM_NAME="From Name"\n\
ENVEOF\n\
\n\
# Clear caches\n\
php artisan config:clear\n\
php artisan cache:clear\n\
php artisan view:clear\n\
php artisan route:clear\n\
\n\
# Run migrations\n\
php artisan migrate --force || true\n\
\n\
# Fix permissions\n\
chown -R www-data:www-data /var/www/html/storage\n\
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache\n\
\n\
# Start Apache\n\
exec apache2-foreground\n\
' > /var/www/html/start.sh && chmod +x /var/www/html/start.sh

CMD ["/var/www/html/start.sh"]

