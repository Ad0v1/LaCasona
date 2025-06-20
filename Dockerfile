FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    libxml2-dev libzip-dev zip unzip curl git \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
     gd mysqli pdo pdo_mysql mbstring xml zip opcache

# Habilitar módulos de Apache necesarios
RUN a2enmod rewrite headers

# Configuración PHP personalizada
RUN echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "date.timezone=America/Lima" >> /usr/local/etc/php/conf.d/uploads.ini

# Copiar todos los archivos del proyecto al contenedor
COPY . /var/www/html/

# Establecer permisos apropiados
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Reemplazar archivo de configuración de Apache
COPY devops/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Habilitar el sitio
RUN a2ensite 000-default.conf

# Puerto por defecto
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
