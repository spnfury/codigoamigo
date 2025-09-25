#!/bin/bash
echo "Actualizando solo MongoDB..."
cd /home/admin/web/codigoamigo.com/public_html

# Instalar solo MongoDB con ignore-platform-reqs
composer require mongodb/mongodb:^1.15 --ignore-platform-reqs

echo "MongoDB actualizado correctamente"
