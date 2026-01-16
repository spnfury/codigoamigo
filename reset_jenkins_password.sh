#!/bin/bash
# Script para resetear la contraseña de Jenkins
# Uso: ./reset_jenkins_password.sh

set -e

JENKINS_USER_CONFIG="/var/lib/jenkins/users/spnfury_0dfeac4c403a18eca4e4e65884093ae96e489d7f194f6215dd8b0815fd1c6295/config.xml"
BACKUP_FILE="${JENKINS_USER_CONFIG}.backup.$(date +%Y%m%d_%H%M%S)"

# Verificar que el archivo existe
if [ ! -f "$JENKINS_USER_CONFIG" ]; then
    echo "ERROR: No se encontró el archivo de configuración del usuario"
    exit 1
fi

# Verificar que Python3 está disponible
if ! command -v python3 &> /dev/null; then
    echo "ERROR: Python3 no está instalado"
    exit 1
fi

# Solicitar la nueva contraseña
echo "═══════════════════════════════════════════════════════"
echo "Resetear Contraseña de Jenkins"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "Usuario: spnfury"
echo ""

read -sp "Ingresa la nueva contraseña: " NEW_PASSWORD
echo ""

if [ -z "$NEW_PASSWORD" ]; then
    echo "ERROR: La contraseña no puede estar vacía"
    exit 1
fi

read -sp "Confirma la nueva contraseña: " CONFIRM_PASSWORD
echo ""

if [ "$NEW_PASSWORD" != "$CONFIRM_PASSWORD" ]; then
    echo "ERROR: Las contraseñas no coinciden"
    exit 1
fi

echo ""
echo "Generando hash bcrypt para la nueva contraseña..."

# Generar el hash bcrypt con el formato que usa Jenkins
BCRYPT_HASH=$(python3 << EOF
import bcrypt
import sys
password = sys.argv[1].encode('utf-8')
salt = bcrypt.gensalt(rounds=10)
hash_value = bcrypt.hashpw(password, salt).decode('utf-8')
print(hash_value)
EOF
"$NEW_PASSWORD")

if [ -z "$BCRYPT_HASH" ]; then
    echo "ERROR: No se pudo generar el hash"
    exit 1
fi

# Crear backup del archivo original
echo "Creando backup del archivo original..."
cp "$JENKINS_USER_CONFIG" "$BACKUP_FILE"
chown jenkins:jenkins "$BACKUP_FILE"

# Actualizar el hash en el archivo XML
echo "Actualizando el hash en el archivo de configuración..."

# Usar sed para reemplazar el hash existente
sed -i "s|<passwordHash>#jbcrypt:\$.*</passwordHash>|<passwordHash>#jbcrypt:$BCRYPT_HASH</passwordHash>|" "$JENKINS_USER_CONFIG"

# Verificar que el cambio se aplicó correctamente
if grep -q "#jbcrypt:$BCRYPT_HASH" "$JENKINS_USER_CONFIG"; then
    echo "✓ Hash actualizado correctamente"
else
    echo "ERROR: No se pudo verificar el cambio. Restaurando backup..."
    cp "$BACKUP_FILE" "$JENKINS_USER_CONFIG"
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════════"
echo "✓ Contraseña reseteada exitosamente"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "Usuario: spnfury"
echo "Nueva contraseña: [la que ingresaste]"
echo ""
echo "NOTA: Para aplicar los cambios, Jenkins necesita reiniciarse."
echo "Puedes reiniciar Jenkins con: sudo systemctl restart jenkins"
echo ""
echo "Backup guardado en: $BACKUP_FILE"
echo ""

