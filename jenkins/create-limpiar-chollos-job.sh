#!/bin/bash
# Script para crear el job de Jenkins "limpiar-chollos-publicidad" vía API

JENKINS_URL="${JENKINS_URL:-https://casinovios.com/jenkins}"
JOB_NAME="limpiar-chollos-publicidad"
SCRIPT_PATH="/home/admin/web/codigoamigo.com/jenkins/limpiar-chollos-job.groovy"

echo "🔧 Creando job de Jenkins: $JOB_NAME"
echo ""

# Solicitar credenciales si no están en variables de entorno
if [ -z "$JENKINS_USER" ] || [ -z "$JENKINS_TOKEN" ]; then
    echo "Por favor, ingresa la URL de Jenkins (Enter para usar: $JENKINS_URL):"
    read -r custom_url
    if [ -n "$custom_url" ]; then
        JENKINS_URL="$custom_url"
    fi
    
    if [ -z "$JENKINS_USER" ]; then
        echo "Usuario de Jenkins:"
        read -r JENKINS_USER
    fi
    
    if [ -z "$JENKINS_TOKEN" ]; then
        echo "Token de API de Jenkins (o contraseña):"
        read -s JENKINS_TOKEN
        echo ""
    fi
fi

echo ""
echo "Verificando conexión con Jenkins..."

# Verificar conexión con Jenkins
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -u "$JENKINS_USER:$JENKINS_TOKEN" "$JENKINS_URL/api/json")

if [ "$HTTP_CODE" != "200" ]; then
    echo "❌ Error: No se pudo conectar a Jenkins. Código HTTP: $HTTP_CODE"
    echo ""
    echo "Verifica:"
    echo "  - Que la URL de Jenkins sea correcta: $JENKINS_URL"
    echo "  - Que el usuario y token sean válidos"
    echo "  - Que Jenkins esté corriendo"
    exit 1
fi

echo "✓ Conexión exitosa con Jenkins"
echo ""

# Verificar si el job ya existe
JOB_CHECK_URL="$JENKINS_URL/job/$JOB_NAME/api/json"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -u "$JENKINS_USER:$JENKINS_TOKEN" "$JOB_CHECK_URL")

if [ "$HTTP_CODE" == "200" ]; then
    echo "⚠️  El job '$JOB_NAME' ya existe."
    echo ""
    echo "¿Deseas actualizarlo? (s/n):"
    read -r respuesta
    if [ "$respuesta" != "s" ] && [ "$respuesta" != "S" ]; then
        echo "Operación cancelada."
        exit 0
    fi
    UPDATE_EXISTING=true
else
    UPDATE_EXISTING=false
fi

# Leer el contenido del script del pipeline
if [ ! -f "$SCRIPT_PATH" ]; then
    echo "❌ Error: No se encontró el archivo del pipeline en: $SCRIPT_PATH"
    exit 1
fi

echo "📄 Leyendo el script del pipeline..."
SCRIPT_CONTENT=$(cat "$SCRIPT_PATH")

# Crear el XML de configuración del job
echo "🔧 Generando configuración XML del job..."

# Guardar el XML en un archivo temporal usando CDATA para el script
TEMP_XML=$(mktemp)
cat > "$TEMP_XML" << 'XMLHEAD'
<?xml version='1.0' encoding='UTF-8'?>
<flow-definition plugin="workflow-job">
  <description>Pipeline para limpiar chollos con #Publicidad cada hora</description>
  <keepDependencies>false</keepDependencies>
  <properties>
    <org.jenkinsci.plugins.workflow.job.properties.PipelineTriggersJobProperty>
      <triggers>
        <hudson.triggers.TimerTrigger>
          <spec>H * * * *</spec>
        </hudson.triggers.TimerTrigger>
      </triggers>
    </org.jenkinsci.plugins.workflow.job.properties.PipelineTriggersJobProperty>
  </properties>
  <definition class="org.jenkinsci.plugins.workflow.cps.CpsFlowDefinition" plugin="workflow-cps">
    <script><![CDATA[
XMLHEAD

# Agregar el contenido del script
cat "$SCRIPT_PATH" >> "$TEMP_XML"

# Cerrar el CDATA y el XML
cat >> "$TEMP_XML" << 'XMLTAIL'
]]></script>
    <sandbox>true</sandbox>
  </definition>
</flow-definition>
XMLTAIL

if [ "$UPDATE_EXISTING" = true ]; then
    echo "📤 Actualizando job existente..."
    CREATE_URL="$JENKINS_URL/job/$JOB_NAME/config.xml"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
        -u "$JENKINS_USER:$JENKINS_TOKEN" \
        -H "Content-Type: application/xml" \
        --data-binary "@$TEMP_XML" \
        "$CREATE_URL")
    
    if [ "$HTTP_CODE" == "200" ]; then
        echo "✅ Job actualizado exitosamente"
    else
        echo "❌ Error al actualizar el job. Código HTTP: $HTTP_CODE"
        rm -f "$TEMP_XML"
        exit 1
    fi
else
    echo "📤 Creando nuevo job..."
    CREATE_URL="$JENKINS_URL/createItem?name=$JOB_NAME"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
        -u "$JENKINS_USER:$JENKINS_TOKEN" \
        -H "Content-Type: application/xml" \
        --data-binary "@$TEMP_XML" \
        "$CREATE_URL")
    
    if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "201" ]; then
        echo "✅ Job creado exitosamente"
    else
        echo "❌ Error al crear el job. Código HTTP: $HTTP_CODE"
        echo ""
        echo "Intenta verificar:"
        echo "  - Que tengas permisos para crear jobs"
        echo "  - Que el nombre del job no esté en uso"
        rm -f "$TEMP_XML"
        exit 1
    fi
fi

# Limpiar archivo temporal
rm -f "$TEMP_XML"

echo ""
echo "🎉 ¡Job '$JOB_NAME' configurado correctamente!"
echo ""
echo "📋 Próximos pasos:"
echo "  1. Ve a Jenkins: $JENKINS_URL/job/$JOB_NAME"
echo "  2. El job se ejecutará automáticamente cada hora"
echo "  3. Puedes ejecutarlo manualmente con 'Build Now'"
echo ""
echo "✅ Listo para usar"

