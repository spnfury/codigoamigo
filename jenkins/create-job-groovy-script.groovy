/**
 * Script Groovy para crear el job "limpiar-chollos-publicidad" en Jenkins
 * 
 * Ejecutar este script en: Jenkins → Manage Jenkins → Script Console
 * O usar: curl -X POST -u USER:TOKEN http://localhost:8085/jenkins/scriptText --data-urlencode "script=$(cat create-job-groovy-script.groovy)"
 */

import jenkins.model.*
import org.jenkinsci.plugins.workflow.job.WorkflowJob
import org.jenkinsci.plugins.workflow.cps.CpsFlowDefinition
import hudson.model.*
import hudson.triggers.TimerTrigger

def jobName = 'limpiar-chollos-publicidad'
def scriptContent = '''
/**
 * Pipeline de Jenkins para limpieza de chollos con #Publicidad
 * 
 * Este pipeline ejecuta el script PHP que limpia chollos antiguos que contienen
 * etiquetas de publicidad (#Publicidad) en el título o descripción.
 */

pipeline {
    agent any
    
    environment {
        SCRIPTS_DIR = '/home/admin/web/codigoamigo.com/public_html/scripts'
    }
    
    triggers {
        cron('H * * * *') // Ejecutar cada hora
    }
    
    stages {
        stage('Limpiar chollos con #Publicidad') {
            steps {
                script {
                    sh """
                        cd ${SCRIPTS_DIR}
                        php limpiar_chollos_publicidad.php
                    """
                }
            }
        }
    }
    
    post {
        success {
            echo '✓ Limpieza de chollos completada exitosamente'
        }
        failure {
            echo '✗ Error en la limpieza de chollos. Revisa los logs para más detalles.'
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
job.description = 'Pipeline para limpiar chollos con #Publicidad cada hora'

// Configurar el pipeline script
def flowDefinition = new CpsFlowDefinition(scriptContent, true)
job.definition = flowDefinition

// Agregar trigger de cron (cada hora)
def triggers = new org.jenkinsci.plugins.workflow.job.properties.PipelineTriggersJobProperty([
    new TimerTrigger('H * * * *')
])
job.addProperty(triggers)

// Guardar el job
jenkins.add(job, jobName)
job.save()

println "✅ Job '${jobName}' creado exitosamente!"
println "   - Se ejecutará automáticamente cada hora"
println "   - Puedes verlo en: ${jenkins.rootUrl}job/${jobName}/"





