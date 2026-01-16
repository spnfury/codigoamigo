<?php
// Test simple de reCAPTCHA
$title = "Test Simple reCAPTCHA - CodigoAmigo.com";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <script src="https://www.google.com/recaptcha/api.js?render=6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS" async defer></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
        .recaptcha-container { margin: 20px 0; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $title; ?></h1>

        <div id="message"></div>

        <form id="testForm">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="mensaje">Mensaje:</label>
                <textarea id="mensaje" name="mensaje" rows="4" required></textarea>
            </div>

            <!-- reCAPTCHA v3 funciona en segundo plano -->

            <button type="submit">Enviar Test</button>
        </form>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = '<p style="color: blue;">Procesando...</p>';

            // Ejecutar reCAPTCHA v3
            grecaptcha.execute('6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS', {action: 'contact'})
            .then(function(token) {
                // Enviar datos con token de reCAPTCHA v3
                const formData = new FormData(e.target);
                formData.append('g-recaptcha-response', token);

                return fetch('test_recaptcha_action.php', {
                    method: 'POST',
                    body: formData
                });
            })
            .then(response => response.text())
            .then(result => {
                console.log('Respuesta del servidor:', result);
                if (result.includes('SUCCESS')) {
                    messageDiv.innerHTML = '<p class="success">✅ reCAPTCHA válido! Formulario enviado correctamente.</p>';
                } else {
                    messageDiv.innerHTML = '<p class="error">❌ Error: ' + result + '</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.innerHTML = '<p class="error">❌ Error de conexión: ' + error.message + '</p>';
            });
        });
    </script>
</body>
</html>
