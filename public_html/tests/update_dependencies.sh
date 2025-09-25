#!/bin/bash
echo "Actualizando dependencias de MongoDB..."
cd /home/admin/web/codigoamigo.com/public_html
composer update mongodb/mongodb --no-dev --optimize-autoloader
echo "Dependencias actualizadas correctamente"
