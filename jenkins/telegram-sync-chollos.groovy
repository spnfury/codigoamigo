/**
 * Pipeline de Jenkins para sincronización de canales de Telegram y publicación de chollos
 * 
 * Este pipeline ejecuta el script Python que monitorea canales de Telegram
 * y sincroniza los mensajes con la base de datos de chollos.
 * Los chollos nuevos se publican automáticamente en Telegram.
 * 
 * Configuración:
 * - Ejecuta cada 30 minutos automáticamente
 * - Obtiene hasta 500 mensajes por canal
 * - Procesa en lotes de 10 para evitar timeouts
 */

pipeline {
    agent any
    
    options {
        timeout(time: 30, unit: 'MINUTES')  // Timeout de 30 minutos
        retry(2)  // Reintentar hasta 2 veces si falla
    }
    
    environment {
        SCRIPTS_DIR = '/home/admin/web/codigoamigo.com/public_html/scripts'
        PYTHON_VENV = "${SCRIPTS_DIR}/venv"
        PYTHON_BIN = "${PYTHON_VENV}/bin/python3"
        LOG_FILE = "${SCRIPTS_DIR}/telegram_monitor.log"
    }
    
    // Ejecutar periódicamente cada 30 minutos
    triggers {
        cron('H/30 * * * *')  // Cada 30 minutos (H distribuye la carga aleatoriamente)
    }
    
    stages {
        stage('Validar entorno') {
            steps {
                script {
                    echo "🔍 Validando entorno..."
                    
                    // Verificar que el directorio existe
                    sh """
                        if [ ! -d "${SCRIPTS_DIR}" ]; then
                            echo "❌ Error: Directorio ${SCRIPTS_DIR} no existe"
                            exit 1
                        fi
                        echo "✓ Directorio de scripts encontrado"
                    """
                    
                    // Verificar que el script existe
                    sh """
                        if [ ! -f "${SCRIPTS_DIR}/telegram_monitor.py" ]; then
                            echo "❌ Error: Script telegram_monitor.py no encontrado"
                            exit 1
                        fi
                        echo "✓ Script telegram_monitor.py encontrado"
                    """
                    
                    // Verificar que config.py existe
                    sh """
                        if [ ! -f "${SCRIPTS_DIR}/config.py" ]; then
                            echo "❌ Error: Archivo config.py no encontrado"
                            echo "   Crea config.py basándote en config.example.py"
                            exit 1
                        fi
                        echo "✓ Archivo config.py encontrado"
                    """
                    
                    // Verificar que el venv existe y tiene Python
                    sh """
                        if [ ! -d "${PYTHON_VENV}" ]; then
                            echo "⚠️  Virtual environment no encontrado, usando Python del sistema"
                        elif [ ! -f "${PYTHON_BIN}" ]; then
                            echo "⚠️  Python en venv no encontrado, usando Python del sistema"
                        else
                            echo "✓ Virtual environment encontrado"
                        fi
                    """
                }
            }
        }
        
        stage('Preparar entorno Python') {
            steps {
                script {
                    echo "🐍 Preparando entorno Python..."
                    
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
                        
                        # Verificar que Python funciona
                        \${PYTHON_CMD} --version || exit 1
                        
                        # Verificar que telethon está instalado
                        \${PYTHON_CMD} -c "import telethon; print('Telethon version:', telethon.__version__)" || {
                            echo "❌ Error: Telethon no está instalado"
                            echo "   Ejecuta: pip3 install telethon requests"
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
                        
                        # Ejecutar el script y capturar código de salida
                        echo "Ejecutando: \${PYTHON_CMD} telegram_monitor.py"
                        
                        # Ejecutar el script y guardar salida en log
                        \${PYTHON_CMD} telegram_monitor.py >> ${LOG_FILE} 2>&1
                        EXIT_CODE=\$?
                        
                        # Mostrar últimas líneas del log
                        echo "=== Últimas líneas del log ==="
                        tail -n 20 ${LOG_FILE} || true
                        
                        if [ "\${EXIT_CODE}" -eq 0 ]; then
                            echo "✓ Sincronización completada exitosamente"
                        else
                            echo "❌ Error en sincronización (código: \${EXIT_CODE})"
                            exit \${EXIT_CODE}
                        fi
                    """
                }
            }
        }
        
        stage('Verificar resultados') {
            steps {
                script {
                    echo "📊 Verificando resultados..."
                    
                    sh """
                        # Mostrar últimas líneas del log
                        echo "=== Últimas 20 líneas del log ==="
                        tail -n 20 ${LOG_FILE} || echo "No se pudo leer el log"
                        
                        # Verificar si hay errores recientes
                        if tail -n 50 ${LOG_FILE} | grep -i "error\|failed\|exception" > /dev/null; then
                            echo "⚠️  Se detectaron errores en el log. Revisa ${LOG_FILE}"
                        else
                            echo "✓ No se detectaron errores críticos"
                        fi
                    """
                }
            }
        }
    }
    
    post {
        always {
            script {
                echo "📝 Limpiando y finalizando..."
                
                // Mostrar resumen del log
                sh """
                    echo "=== Resumen de la ejecución ==="
                    if [ -f ${LOG_FILE} ]; then
                        echo "Últimas líneas del log:"
                        tail -n 10 ${LOG_FILE} || true
                    fi
                """
            }
        }
        
        success {
            echo '✅ Pipeline completado exitosamente'
            echo 'Los chollos nuevos se han sincronizado y publicado en Telegram'
        }
        
        failure {
            echo '❌ Pipeline falló'
            echo 'Revisa los logs para más detalles:'
            echo "  - Log del script: ${LOG_FILE}"
            echo "  - Console Output de Jenkins"
        }
        
        unstable {
            echo '⚠️  Pipeline completado con advertencias'
        }
    }
}


