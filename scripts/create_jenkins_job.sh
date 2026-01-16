#!/bin/bash
# Script para crear el job de Jenkins "telegram-sync-chollos" usando la Script Console

JENKINS_URL="${JENKINS_URL:-http://localhost:8085/jenkins}"
JOB_NAME="telegram-sync-chollos"
SCRIPT_PATH="/home/admin/web/codigoamigo.com/jenkins/create-telegram-sync-job.groovy"

echo "🔧 Creando job de Jenkins: $JOB_NAME"
echo ""

# Verificar que el script existe
if [ ! -f "$SCRIPT_PATH" ]; then
    echo "❌ Error: No se encontró el script en: $SCRIPT_PATH"
    exit 1
fi

echo "📄 Leyendo script Groovy..."
GROOVY_SCRIPT=$(cat "$SCRIPT_PATH")

echo ""
echo "Para crear el job, tienes dos opciones:"
echo ""
echo "OPCIÓN 1: Usar Script Console de Jenkins (Recomendado)"
echo "  1. Abre Jenkins en tu navegador: $JENKINS_URL"
echo "  2. Ve a: Manage Jenkins → Script Console"
echo "  3. Copia y pega el siguiente script:"
echo ""
echo "---"
cat "$SCRIPT_PATH"
echo "---"
echo ""
echo "  4. Haz clic en 'Run'"
echo "  5. Deberías ver: ✅ Job 'telegram-sync-chollos' creado exitosamente"
echo ""
echo "OPCIÓN 2: Usar API de Jenkins (requiere autenticación)"
echo ""
read -p "¿Quieres intentar crear el job vía API? (s/n): " respuesta

if [ "$respuesta" = "s" ] || [ "$respuesta" = "S" ]; then
    echo ""
    echo "Por favor, ingresa las credenciales de Jenkins:"
    read -p "Usuario: " JENKINS_USER
    read -s -p "Token/Contraseña: " JENKINS_TOKEN
    echo ""
    
    # Codificar el script para URL
    ENCODED_SCRIPT=$(python3 -c "import urllib.parse; print(urllib.parse.quote(open('$SCRIPT_PATH').read()))" 2>/dev/null || echo "")
    
    if [ -z "$ENCODED_SCRIPT" ]; then
        echo "❌ Error: No se pudo codificar el script. Usa la Opción 1."
        exit 1
    fi
    
    echo "Enviando script a Jenkins..."
    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
        -u "$JENKINS_USER:$JENKINS_TOKEN" \
        --data-urlencode "script=$(cat $SCRIPT_PATH)" \
        "$JENKINS_URL/scriptText")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
    BODY=$(echo "$RESPONSE" | head -n -1)
    
    if [ "$HTTP_CODE" = "200" ]; then
        echo "✅ Respuesta de Jenkins:"
        echo "$BODY"
    else
        echo "❌ Error HTTP $HTTP_CODE"
        echo "Respuesta: $BODY"
        echo ""
        echo "Por favor, usa la Opción 1 (Script Console) en su lugar."
    fi
else
    echo ""
    echo "Usa la Opción 1 para crear el job manualmente."
fi

