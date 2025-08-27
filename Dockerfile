# Official PHP image with Apache
FROM php:8.2-apache

# Copy all project files into the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Enable Apache mod_rewrite (optional)
RUN a2enmod rewrite

# Expose port for Render
EXPOSE 10000

# Start Apache in foreground
CMD ["apache2-foreground"]
