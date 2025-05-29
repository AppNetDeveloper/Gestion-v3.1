#!/bin/bash
# Script para actualizar la configuración de Supervisor

# Crear directorio de logs si no existe
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage/logs

# Copiar archivos de configuración a Supervisor
echo "Copiando archivos de configuración a Supervisor..."
sudo cp /var/www/html/laravel-scraping-*.conf /etc/supervisor/conf.d/

# Actualizar la configuración de Supervisor
echo "Actualizando configuración de Supervisor..."
sudo supervisorctl update

# Reiniciar los servicios
echo "Reiniciando servicios..."
sudo supervisorctl restart all

# Mostrar estado
echo "\nEstado actual de los servicios:"
sudo supervisorctl status

echo "\n¡Configuración actualizada correctamente!"
echo "Puedes ver los logs en /var/www/html/storage/logs/"
