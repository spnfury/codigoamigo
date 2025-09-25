#!/bin/bash
echo "Realizando instalación limpia de dependencias..."
cd /home/admin/web/codigoamigo.com/public_html

# Eliminar vendor y composer.lock
rm -rf vendor composer.lock

# Instalar dependencias
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

echo "Instalación completada"
