/**
 * Pipeline de Jenkins para sincronización de canales de Telegram
 * 
 * Este pipeline ejecuta el script Python que monitorea canales de Telegram
 * y sincroniza los mensajes con la base de datos de chollos.
 */

pipeline {
    agent any
    
    environment {
        SCRIPTS_DIR = '/home/admin/web/codigoamigo.com/public_html/scripts'
        PYTHON_VENV = "${SCRIPTS_DIR}/venv"
        PYTHON_BIN = "${PYTHON_VENV}/bin/python3"
        LOG_FILE = "${SCRIPTS_DIR}/telegram_monitor.log"
    }
    
    // Ejecutar periódicamente cada 30 minutos
    triggers {
        cron('H/30 * * * *')  // Cada 30 minutos (H distribuye la carga)
    }
    
    stages {
        stage('Validar entorno') {
            steps {
                script {
                    echo "🔍 Validando entorno..."
                    sh """
                        if [ ! -f "${SCRIPTS_DIR}/telegram_monitor.py" ]; then
                            echo "❌ Error: Script telegram_monitor.py no encontrado"
                            exit 1
                        fi
                        if [ ! -f "${SCRIPTS_DIR}/config.py" ]; then
                            echo "❌ Error: Archivo config.py no encontrado"
                            exit 1
                        fi
                        echo "✓ Archivos encontrados"
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
                        
                        # Determinar qué Python usar
                        if [ -f "${PYTHON_BIN}" ]; then
                            PYTHON_CMD="${PYTHON_BIN}"
                            echo "Usando Python del venv: \${PYTHON_CMD}"
                        else
                            PYTHON_CMD="python3"
                            echo "Usando Python del sistema: \${PYTHON_CMD}"
                        fi
                        
                        # Verificar que telethon está instalado
                        \${PYTHON_CMD} -c "import telethon; print('Telethon version:', telethon.__version__)" || {
                            echo "❌ Error: Telethon no está instalado"
                            exit 1
                        }
                        echo "✓ Python y dependencias verificadas"
                    """
                }
            }
        }
        
        stage('Ejecutar sincronización') {
            steps {
                script {
                    echo "🚀 Iniciando sincronización de chollos..."
                    sh """
                        cd ${SCRIPTS_DIR}
                        
                        # Determinar qué Python usar
                        if [ -f "${PYTHON_BIN}" ]; then
                            PYTHON_CMD="${PYTHON_BIN}"
                        else
                            PYTHON_CMD="python3"
                        fi
                        
                        # Ejecutar el script
                        echo "Ejecutando: \${PYTHON_CMD} telegram_monitor.py"
                        \${PYTHON_CMD} telegram_monitor.py 2>&1 | tee -a ${LOG_FILE}
                        
                        EXIT_CODE=\${PIPESTATUS[0]}
                        
                        if [ \${EXIT_CODE} -ne 0 ]; then
                            echo "❌ Error en sincronización (código: \${EXIT_CODE})"
                            exit \${EXIT_CODE}
                        fi
                    """
                }
            }
        }
    }
    
    post {
        success {
            echo '✓ Sincronización completada exitosamente'
        }
        failure {
            echo '✗ Error en la sincronización. Revisa los logs para más detalles.'
        }
    }
}
