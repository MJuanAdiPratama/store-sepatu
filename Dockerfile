FROM php:8.5-apache

# Install ekstensi mysqli & pdo untuk koneksi ke MySQL/Aiven
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy semua file project ke dalam server
COPY . /var/www/html/

# Buka port 80
EXPOSE 80