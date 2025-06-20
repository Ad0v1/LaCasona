FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configurar y compilar extensiones de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        opcache

# Habilitar módulos de Apache
RUN a2enmod rewrite headers expires deflate

# Configurar PHP para producción
RUN echo "opcache.memory_consumption=128" > /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_input_vars=3000" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "date.timezone=America/Lima" >> /usr/local/etc/php/conf.d/uploads.ini

# Copiar configuración personalizada de Apache y .htaccess
COPY devops/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY devops/htaccess/.htaccess /var/www/html/.htaccess

# Crear estructura de directorios si no existe
RUN mkdir -p /var/www/html/assets/css \
    && mkdir -p /var/www/html/assets/js \
    && mkdir -p /var/www/html/assets/imagenes \
    && mkdir -p /var/www/html/controllers \
    && mkdir -p /var/www/html/includes \
    && mkdir -p /var/www/html/admin

# Establecer permisos adecuados
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/assets

# Crear usuario no-root para seguridad (opcional)
RUN groupadd -r kawai && useradd -r -g kawai kawai

# Verificación de salud
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Puerto expuesto
EXPOSE 80

# Ejecutar Apache
CMD ["apache2-foreground"]
