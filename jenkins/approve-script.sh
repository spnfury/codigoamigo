#!/bin/bash
# Script para aprobar automáticamente el script del pipeline en Jenkins
# Uso: ./approve-script.sh [JENKINS_URL] [JENKINS_USER] [JENKINS_TOKEN]

JENKINS_URL="${1:-http://localhost:8085/jenkins}"
JENKINS_USER="${2:-admin}"
JENKINS_TOKEN="${3}"

if [ -z "$JENKINS_TOKEN" ]; then
    echo "❌ Error: Se requiere token de API de Jenkins"
    echo ""
    echo "Uso: $0 [JENKINS_URL] [JENKINS_USER] [JENKINS_TOKEN]"
    echo ""
    echo "Ejemplo:"
    echo "  $0 https://casinovios.com/jenkins admin tu_token_aqui"
    echo ""
    echo "Para obtener un token de API:"
    echo "  1. Ve a Jenkins → Manage Jenkins → Manage Users"
    echo "  2. Click en tu usuario → Configure"
    echo "  3. En 'API Token', click en 'Add new Token'"
    echo ""
    exit 1
fi

echo "🔍 Aprobando scripts pendientes en Jenkins..."
echo "   URL: $JENKINS_URL"
echo "   Usuario: $JENKINS_USER"
echo ""

# Script Groovy para aprobar todos los scripts pendientes
GROOVY_SCRIPT='
import org.jenkinsci.plugins.scriptsecurity.scripts.ScriptApproval
import org.jenkinsci.plugins.scriptsecurity.scripts.languages.GroovyLanguage

def scriptApproval = ScriptApproval.get()
def pendingScripts = scriptApproval.getPendingScripts()

if (pendingScripts.isEmpty()) {
    println "✓ No hay scripts pendientes de aprobación"
} else {
    println "📋 Scripts pendientes encontrados: " + pendingScripts.size()
    
    pendingScripts.each { script ->
        println "   Aprobando: " + script.hash
        scriptApproval.approveScript(script.hash)
    }
    
    println "✅ Todos los scripts han sido aprobados"
}
'

# Ejecutar el script Groovy en Jenkins
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
    -u "${JENKINS_USER}:${JENKINS_TOKEN}" \
    --data-urlencode "script=${GROOVY_SCRIPT}" \
    "${JENKINS_URL}/scriptText")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" = "200" ]; then
    echo "$BODY"
    echo ""
    echo "✅ Scripts aprobados exitosamente"
    echo ""
    echo "Ahora puedes ejecutar el job 'telegram-sync-chollos' y debería funcionar."
else
    echo "❌ Error al aprobar scripts"
    echo "   Código HTTP: $HTTP_CODE"
    echo "   Respuesta: $BODY"
    exit 1
fi

