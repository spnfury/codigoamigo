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





