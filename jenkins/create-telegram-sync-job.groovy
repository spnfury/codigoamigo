/**
 * Script Groovy para crear el job "telegram-sync-chollos" en Jenkins
 * 
 * Ejecutar este script en: Jenkins → Manage Jenkins → Script Console
 * O usar: curl -X POST -u USER:TOKEN http://localhost:8085/jenkins/scriptText --data-urlencode "script=$(cat create-telegram-sync-job.groovy)"
 */

import jenkins.model.*
import org.jenkinsci.plugins.workflow.job.WorkflowJob
import org.jenkinsci.plugins.workflow.cps.CpsFlowDefinition
import hudson.model.*
import hudson.triggers.TimerTrigger

def jobName = 'telegram-sync-chollos'
def scriptContent = '''
/**
 * Pipeline de Jenkins para sincronización de canales de Telegram y publicación de chollos
 * 
 * Este pipeline ejecuta el script Python que monitorea canales de Telegram
 * y sincroniza los mensajes con la base de datos de chollos.
 * Los chollos nuevos se publican automáticamente en Telegram.
 */

pipeline {
    agent any
    
    options {
        timeout(time: 30, unit: 'MINUTES')
        retry(2)
    }
    
    environment {
        SCRIPTS_DIR = '/home/admin/web/codigoamigo.com/public_html/scripts'
        PYTHON_VENV = "${SCRIPTS_DIR}/venv"
        PYTHON_BIN = "${PYTHON_VENV}/bin/python3"
        LOG_FILE = "${SCRIPTS_DIR}/telegram_monitor.log"
    }
    
    triggers {
        cron('H/30 * * * *')
    }
    
    stages {
        stage('Validar entorno') {
            steps {
                script {
                    echo "🔍 Validando entorno..."
                    sh """
                        if [ ! -d "${SCRIPTS_DIR}" ]; then
                            echo "❌ Error: Directorio no existe"
                            exit 1
                        fi
                        if [ ! -f "${SCRIPTS_DIR}/telegram_monitor.py" ]; then
                            echo "❌ Error: Script no encontrado"
                            exit 1
                        fi
                        if [ ! -f "${SCRIPTS_DIR}/config.py" ]; then
                            echo "❌ Error: config.py no encontrado"
                            exit 1
                        fi
                        echo "✓ Entorno validado"
                    """
                }
            }
        }
        
        stage('Preparar Python') {
            steps {
                script {
                    echo "🐍 Preparando Python..."
                    sh """
                        cd ${SCRIPTS_DIR}
                        if [ -f "${PYTHON_BIN}" ]; then
                            PYTHON_CMD="${PYTHON_BIN}"
                        else
                            PYTHON_CMD="python3"
                        fi
                        \${PYTHON_CMD} --version
                        \${PYTHON_CMD} -c "import telethon; print('Telethon OK')" || {
                            echo "❌ Telethon no instalado"
                            exit 1
                        }
                    """
                }
            }
        }
        
        stage('Ejecutar sincronización') {
            steps {
                script {
                    echo "🚀 Sincronizando chollos..."
                    sh """
                        cd ${SCRIPTS_DIR}
                        if [ -f "${PYTHON_BIN}" ]; then
                            PYTHON_CMD="${PYTHON_BIN}"
                        else
                            PYTHON_CMD="python3"
                        fi
                        \${PYTHON_CMD} telegram_monitor.py
                    """
                }
            }
        }
    }
    
    post {
        success {
            echo '✅ Sincronización completada'
        }
        failure {
            echo '❌ Error en sincronización'
        }
    }
}
'''

def jenkins = Jenkins.instance

// Verificar si el job ya existe
def existingJob = jenkins.getItem(jobName)
if (existingJob != null) {
    println "⚠️  El job '${jobName}' ya existe. Eliminándolo para recrearlo..."
    existingJob.delete()
}

// Crear el nuevo job
def job = new WorkflowJob(jenkins, jobName)
job.description = 'Pipeline para sincronizar chollos de Telegram cada 30 minutos y publicarlos automáticamente'

// Configurar el pipeline script
def flowDefinition = new CpsFlowDefinition(scriptContent, true)
job.definition = flowDefinition

// Agregar trigger de cron (cada 30 minutos)
def triggers = new org.jenkinsci.plugins.workflow.job.properties.PipelineTriggersJobProperty([
    new TimerTrigger('H/30 * * * *')
])
job.addProperty(triggers)

// Guardar el job
job.save()

println "✅ Job '${jobName}' creado exitosamente"
println "   - Ejecuta cada 30 minutos automáticamente"
println "   - Sincroniza hasta 500 mensajes por canal"
println "   - Publica automáticamente nuevos chollos en Telegram"


