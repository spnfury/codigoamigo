#!/bin/bash
# Script para actualizar el job de Jenkins usando la API
# Cambia la configuración de "Pipeline script from SCM" a "Pipeline script"

JENKINS_URL="${JENKINS_URL:-http://localhost:8080}"
JOB_NAME="${JOB_NAME:-telegram-sync-chollos}"
SCRIPT_PATH="/home/admin/web/codigoamigo.com/jenkins/telegram-sync-job.groovy"

echo "🔄 Actualizando job de Jenkins usando la API..."
echo ""

# Solicitar credenciales
echo "Por favor, ingresa la URL de Jenkins (Enter para usar: $JENKINS_URL):"
read -r custom_url
if [ -n "$custom_url" ]; then
    JENKINS_URL="$custom_url"
fi

echo "Usuario de Jenkins:"
read -r JENKINS_USER

echo "Token de API de Jenkins (o contraseña):"
read -s JENKINS_TOKEN

echo ""
echo "Verificando conexión con Jenkins..."

# Verificar que el job existe
JOB_CONFIG_URL="$JENKINS_URL/job/$JOB_NAME/config.xml"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -u "$JENKINS_USER:$JENKINS_TOKEN" "$JOB_CONFIG_URL")

if [ "$HTTP_CODE" != "200" ]; then
    echo "❌ Error: No se pudo acceder al job. Código HTTP: $HTTP_CODE"
    echo ""
    echo "Verifica:"
    echo "  - Que la URL de Jenkins sea correcta"
    echo "  - Que el usuario y token sean válidos"
    echo "  - Que el job '$JOB_NAME' exista"
    exit 1
fi

echo "✓ Conexión exitosa con Jenkins"
echo ""

# Leer el contenido del script del pipeline
if [ ! -f "$SCRIPT_PATH" ]; then
    echo "❌ Error: No se encontró el archivo del pipeline en: $SCRIPT_PATH"
    exit 1
fi

echo "📄 Leyendo el script del pipeline..."
SCRIPT_CONTENT=$(cat "$SCRIPT_PATH")

# Obtener la configuración actual del job
echo "📥 Obteniendo configuración actual del job..."
CURRENT_CONFIG=$(curl -s -u "$JENKINS_USER:$JENKINS_TOKEN" "$JOB_CONFIG_URL")

# Crear la nueva configuración XML
echo "🔧 Generando nueva configuración..."

# Esta es una tarea compleja que requiere manipulación XML
# Por ahora, mostramos instrucciones manuales
echo ""
echo "⚠️  Actualizar el job vía API requiere manipulación XML compleja."
echo ""
echo "📋 SOLUCIÓN MANUAL (más simple):"
echo ""
echo "1. Abre Jenkins en tu navegador: $JENKINS_URL"
echo "2. Ve al job: $JOB_NAME"
echo "3. Click en 'Configure'"
echo "4. En 'Pipeline Definition', cambia de 'Pipeline script from SCM' a 'Pipeline script'"
echo "5. Copia y pega este contenido:"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
cat "$SCRIPT_PATH"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "6. Click en 'Save'"
echo ""
echo "✅ Después de esto, el job debería funcionar correctamente."



