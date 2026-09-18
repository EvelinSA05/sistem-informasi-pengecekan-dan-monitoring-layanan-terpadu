FROM php:8.2-cli

# Install dependencies sistem yang dibutuhkan Laravel & PostgreSQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip

# Set working directory
WORKDIR /app

# Copy seluruh file project
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Jalankan Composer Install
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Jalankan NPM Build untuk memproses Tailwind/Vue/Vite
RUN npm install && npm run build

# Render akan menggunakan variabel $PORT
EXPOSE 10000

# Perintah utama untuk menjalankan server Laravel
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
