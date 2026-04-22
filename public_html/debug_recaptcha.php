<?php
/**
 * Script de diagnóstico completo para reCAPTCHA
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug reCAPTCHA - CodigoAmigo.com</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .test-btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Debug reCAPTCHA - CodigoAmigo.com</h1>

        <div class="section info">
            <h3>📋 Información de Configuración</h3>
            <p><strong>Site Key:</strong> <code>6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS</code></p>
            <p><strong>Secret Key:</strong> <code>6LfyTegrAAAAAOnDI2_LSnJWf-knMy92ntWngpTQ</code></p>
            <p><strong>IP del servidor:</strong> <code><?php echo $_SERVER['SERVER_ADDR'] ?? 'Desconocida'; ?></code></p>
        </div>

        <div class="section">
            <h3>🌐 Test 1: Conectividad con Google</h3>
            <button class="test-btn" onclick="testGoogleConnectivity()">Probar Conectividad</button>
            <div id="connectivity-result"></div>
        </div>

        <div class="section">
            <h3>🔑 Test 2: Validación de Claves</h3>
            <button class="test-btn" onclick="testRecaptchaValidation()">Probar Validación</button>
            <div id="validation-result"></div>
        </div>

        <div class="section">
            <h3>📝 Test 3: Formulario Completo</h3>
            <form id="testForm">
                <label>Nombre: <input type="text" name="nombre" required></label><br><br>
                <label>Email: <input type="email" name="email" required></label><br><br>
                <label>Mensaje: <textarea name="mensaje" required></textarea></label><br><br>
                <button type="submit" class="test-btn">Enviar Test</button>
            </form>
            <div id="form-result"></div>
        </div>

        <div class="section">
            <h3>🔍 Test 4: Información del Navegador</h3>
            <button class="test-btn" onclick="showBrowserInfo()">Mostrar Información</button>
            <div id="browser-info"></div>
        </div>
    </div>

    <!-- Script de reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS" async defer></script>

    <script>
        function showBrowserInfo() {
            const info = {
                userAgent: navigator.userAgent,
                platform: navigator.platform,
                cookieEnabled: navigator.cookieEnabled,
                onLine: navigator.onLine,
                language: navigator.language,
                timestamp: new Date().toISOString()
            };

            document.getElementById('browser-info').innerHTML =
                '<pre>' + JSON.stringify(info, null, 2) + '</pre>';
        }

        function testGoogleConnectivity() {
            const resultDiv = document.getElementById('connectivity-result');
            resultDiv.innerHTML = '<p>Probando conectividad...</p>';

            fetch('https://www.google.com/recaptcha/api.js?render=6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS')
                .then(response => {
                    if (response.ok) {
                        resultDiv.innerHTML = '<div class="success"><strong>✅ Conectividad OK</strong><br>Script de reCAPTCHA cargó correctamente</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="error"><strong>❌ Error de conectividad</strong><br>Status: ' + response.status + '</div>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error"><strong>❌ Error de red</strong><br>' + error.message + '</div>';
                });
        }

        function testRecaptchaValidation() {
            const resultDiv = document.getElementById('validation-result');
            resultDiv.innerHTML = '<p>Probando validación de reCAPTCHA...</p>';

            // Ejecutar reCAPTCHA v3
            grecaptcha.execute('6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS', {action: 'debug_test'})
                .then(function(token) {
                    console.log('Token obtenido:', token.substring(0, 50) + '...');

                    // Enviar token para validación
                    return fetch('debug_recaptcha_validation.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'token=' + encodeURIComponent(token)
                    });
                })
                .then(response => response.text())
                .then(result => {
                    console.log('Respuesta completa:', result);
                    resultDiv.innerHTML = '<div class="info"><strong>📊 Resultado de validación:</strong><br><pre>' + result + '</pre></div>';
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = '<div class="error"><strong>❌ Error en validación</strong><br>' + error.message + '</div>';
                });
        }

        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const resultDiv = document.getElementById('form-result');
            resultDiv.innerHTML = '<p style="color: blue;">Procesando formulario...</p>';

            // Ejecutar reCAPTCHA v3
            grecaptcha.execute('6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS', {action: 'debug_form'})
                .then(function(token) {
                    const formData = new FormData(e.target);
                    formData.append('g-recaptcha-response', token);
                    formData.append('debug', '1');

                    return fetch('procesar_contacto.php', {
                        method: 'POST',
                        body: formData
                    });
                })
                .then(response => response.text())
                .then(result => {
                    console.log('Respuesta del formulario:', result);
                    if (result.includes('SUCCESS')) {
                        resultDiv.innerHTML = '<div class="success"><strong>✅ Formulario enviado correctamente</strong><br><pre>' + result + '</pre></div>';
                    } else {
                        resultDiv.innerHTML = '<div class="error"><strong>❌ Error en formulario</strong><br><pre>' + result + '</pre></div>';
                    }
                })
                .catch(error => {
                    console.error('Error en formulario:', error);
                    resultDiv.innerHTML = '<div class="error"><strong>❌ Error de conexión</strong><br>' + error.message + '</div>';
                });
        });

        // Verificar si reCAPTCHA está disponible
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof grecaptcha === 'undefined') {
                    document.getElementById('connectivity-result').innerHTML =
                        '<div class="error"><strong>❌ reCAPTCHA no cargó</strong><br>El script de Google no se cargó correctamente</div>';
                } else {
                    console.log('✅ reCAPTCHA cargado correctamente');
                }
            }, 3000);
        });
    </script>
</body>
</html>
