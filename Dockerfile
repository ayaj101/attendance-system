# Attendance & Overtime Management System - production image
# Works on any Docker host (Render, Railway, Fly.io, a VPS, etc.).
FROM php:8.2-apache

# PHP extensions required by the app.
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers

# Apache: allow .htaccess overrides.
RUN sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    || true

# Copy application source.
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Many PaaS providers inject $PORT; make Apache listen on it (default 80).
ENV PORT=80
RUN sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -ri 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
CMD ["apache2-foreground"]
