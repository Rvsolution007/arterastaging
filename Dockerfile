FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libonig-dev libxml2-dev libzip-dev mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite headers

# Set DocumentRoot to project root (this project uses index.php in root, not public/)
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -i 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . /var/www/html/

WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create required storage directories
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    public/uploads

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Build .env from build args
ARG APP_NAME=Artera
ARG APP_ENV=production
ARG APP_KEY=
ARG APP_DEBUG=false
ARG APP_URL=https://localhost
ARG API_KEY=123456
ARG LOG_CHANNEL=stack
ARG LOG_LEVEL=error
ARG DB_CONNECTION=mysql
ARG DB_HOST=db
ARG DB_PORT=3306
ARG DB_DATABASE=arterastaging
ARG DB_USERNAME=root
ARG DB_PASSWORD=
ARG BROADCAST_DRIVER=log
ARG CACHE_DRIVER=file
ARG FILESYSTEM_DISK=local
ARG QUEUE_CONNECTION=sync
ARG SESSION_DRIVER=file
ARG SESSION_LIFETIME=120
ARG BG_REMOVER_URL=

RUN echo 'APP_NAME="'"${APP_NAME}"'"' > .env \
    && echo 'APP_ENV="'"${APP_ENV}"'"' >> .env \
    && echo 'APP_KEY="'"${APP_KEY}"'"' >> .env \
    && echo 'APP_DEBUG="'"${APP_DEBUG}"'"' >> .env \
    && echo 'APP_URL="'"${APP_URL}"'"' >> .env \
    && echo 'API_KEY="'"${API_KEY}"'"' >> .env \
    && echo 'LOG_CHANNEL="'"${LOG_CHANNEL}"'"' >> .env \
    && echo 'LOG_LEVEL="'"${LOG_LEVEL}"'"' >> .env \
    && echo 'DB_CONNECTION="'"${DB_CONNECTION}"'"' >> .env \
    && echo 'DB_HOST="'"${DB_HOST}"'"' >> .env \
    && echo 'DB_PORT="'"${DB_PORT}"'"' >> .env \
    && echo 'DB_DATABASE="'"${DB_DATABASE}"'"' >> .env \
    && echo 'DB_USERNAME="'"${DB_USERNAME}"'"' >> .env \
    && echo 'DB_PASSWORD="'"${DB_PASSWORD}"'"' >> .env \
    && echo 'BROADCAST_DRIVER="'"${BROADCAST_DRIVER}"'"' >> .env \
    && echo 'CACHE_DRIVER="'"${CACHE_DRIVER}"'"' >> .env \
    && echo 'FILESYSTEM_DISK="'"${FILESYSTEM_DISK}"'"' >> .env \
    && echo 'QUEUE_CONNECTION="'"${QUEUE_CONNECTION}"'"' >> .env \
    && echo 'SESSION_DRIVER="'"${SESSION_DRIVER}"'"' >> .env \
    && echo 'SESSION_LIFETIME="'"${SESSION_LIFETIME}"'"' >> .env \
    && echo 'BG_REMOVER_URL="'"${BG_REMOVER_URL}"'"' >> .env

# Copy entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
