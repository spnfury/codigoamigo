#!/bin/bash
# Script para habilitar checkouts locales en Jenkins
# Esto permite que Jenkins use rutas file:// para Git

echo "🔧 Habilitando checkouts locales en Jenkins..."
echo ""

# Buscar el archivo de configuración de Jenkins
JENKINS_HOME="${JENKINS_HOME:-/var/lib/jenkins}"
JENKINS_CONFIG="${JENKINS_HOME}/config.xml"

if [ ! -f "$JENKINS_CONFIG" ]; then
    echo "❌ No se encontró el archivo de configuración de Jenkins en: $JENKINS_CONFIG"
    echo ""
    echo "Buscando en ubicaciones comunes..."
    
    # Buscar en ubicaciones comunes
    for path in /var/lib/jenkins /opt/jenkins /usr/share/jenkins ~/.jenkins; do
        if [ -f "$path/config.xml" ]; then
            JENKINS_HOME="$path"
            JENKINS_CONFIG="$path/config.xml"
            echo "✓ Encontrado en: $JENKINS_CONFIG"
            break
        fi
    done
fi

if [ ! -f "$JENKINS_CONFIG" ]; then
    echo "❌ No se pudo encontrar la configuración de Jenkins."
    echo ""
    echo "Por favor, ejecuta este comando manualmente:"
    echo ""
    echo "  sudo java -jar jenkins-cli.jar -s http://localhost:8080/ -auth USER:TOKEN groovy = <<'EOF'"
    echo "  System.setProperty('hudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT', 'true')"
    echo "  EOF"
    echo ""
    exit 1
fi

echo "📝 Ubicación de Jenkins: $JENKINS_HOME"
echo ""

# Método 1: Usar jenkins-cli (si está disponible)
if command -v jenkins-cli &> /dev/null || [ -f "$JENKINS_HOME/jenkins-cli.jar" ]; then
    echo "Método 1: Usando jenkins-cli..."
    
    JENKINS_URL="${JENKINS_URL:-http://localhost:8080}"
    JENKINS_USER="${JENKINS_USER:-admin}"
    
    echo "¿Cuál es la URL de tu Jenkins? (Enter para usar: $JENKINS_URL)"
    read -r custom_url
    if [ -n "$custom_url" ]; then
        JENKINS_URL="$custom_url"
    fi
    
    echo "¿Cuál es tu usuario de Jenkins? (Enter para usar: $JENKINS_USER)"
    read -r custom_user
    if [ -n "$custom_user" ]; then
        JENKINS_USER="$custom_user"
    fi
    
    echo "Por favor, ingresa tu token de API de Jenkins (o contraseña):"
    read -s JENKINS_TOKEN
    
    echo ""
    echo "Ejecutando comando en Jenkins..."
    
    if [ -f "$JENKINS_HOME/jenkins-cli.jar" ]; then
        CLI_JAR="$JENKINS_HOME/jenkins-cli.jar"
    else
        CLI_JAR="jenkins-cli"
    fi
    
    java -jar "$CLI_JAR" -s "$JENKINS_URL" -auth "$JENKINS_USER:$JENKINS_TOKEN" groovy = <<'EOF'
System.setProperty('hudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT', 'true')
println "✓ Checkouts locales habilitados"
EOF
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ ¡Checkouts locales habilitados exitosamente!"
        echo ""
        echo "Ahora necesitas reiniciar Jenkins para que los cambios surtan efecto:"
        echo "  sudo systemctl restart jenkins"
        echo "  # o"
        echo "  sudo service jenkins restart"
        exit 0
    else
        echo "❌ Error al ejecutar el comando. Intentando método alternativo..."
    fi
fi

# Método 2: Modificar directamente el archivo de propiedades del sistema
echo ""
echo "Método 2: Configurando propiedad del sistema directamente..."

PROPERTIES_FILE="$JENKINS_HOME/jenkins.properties"

if [ ! -f "$PROPERTIES_FILE" ]; then
    echo "Creando archivo de propiedades..."
    touch "$PROPERTIES_FILE"
fi

# Agregar la propiedad si no existe
if ! grep -q "hudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT" "$PROPERTIES_FILE"; then
    echo "hudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT=true" >> "$PROPERTIES_FILE"
    echo "✓ Propiedad agregada al archivo de propiedades"
else
    # Actualizar si ya existe
    sed -i 's/^hudson\.plugins\.git\.GitSCM\.ALLOW_LOCAL_CHECKOUT=.*/hudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT=true/' "$PROPERTIES_FILE"
    echo "✓ Propiedad actualizada en el archivo de propiedades"
fi

echo ""
echo "✅ Configuración aplicada. Ahora necesitas reiniciar Jenkins:"
echo ""
echo "  sudo systemctl restart jenkins"
echo "  # o"
echo "  sudo service jenkins restart"
echo ""
echo "Después del reinicio, el job debería funcionar correctamente."



