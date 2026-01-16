<?php
/**
 * Página intermedia de redirección para shorteners que no se pueden expandir del lado del servidor
 * Expande el shortener del lado del cliente y reemplaza el tag de afiliado
 */

$shortener_url = $_GET['url'] ?? '';
$correct_tag = $_GET['tag'] ?? 'spnfuryy-21';

if (empty($shortener_url)) {
    header('Location: /chollos');
    exit;
}

// Validar que sea un enlace de Amazon conocido
$valid_shorteners = ['ganga.ad', 'chollo.biz', 'amzn.to', 'amz.tf', 'a.co'];
$is_valid = false;
foreach ($valid_shorteners as $domain) {
    if (stripos($shortener_url, $domain) !== false) {
        $is_valid = true;
        break;
    }
}

if (!$is_valid) {
    header('Location: ' . $shortener_url);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo al chollo...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .loader {
            text-align: center;
            color: white;
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <h2>Redirigiendo al chollo...</h2>
        <p>Por favor espera un momento</p>
    </div>

    <script>
    (function() {
        const shortenerUrl = <?php echo json_encode($shortener_url); ?>;
        const correctTag = <?php echo json_encode($correct_tag); ?>;
        
        // Crear un iframe oculto para seguir la redirección
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
        
        // Configurar listener ANTES de cargar el iframe
        let redirectAttempted = false;
        const checkInterval = setInterval(function() {
            try {
                // Intentar leer la URL del iframe (funcionará si está en mismo dominio o después de redirección)
                const iframeUrl = iframe.contentWindow.location.href;
                
                if (iframeUrl && iframeUrl !== 'about:blank' && iframeUrl.indexOf('amazon.') !== -1) {
                    // Conseguimos la URL de Amazon
                    clearInterval(checkInterval);
                    
                    // Parsear la URL y reemplazar el tag
                    const url = new URL(iframeUrl);
                    url.searchParams.set('tag', correctTag);
                    
                    // Redirigir al usuario
                    window.location.href = url.toString();
                    redirectAttempted = true;
                }
            } catch (e) {
                // Error de CORS esperado - el iframe está en otro dominio (shortener)
                // Continuamos intentando
            }
        }, 100);
        
        // Timeout: si después de 5 segundos no pudimos capturar la URL,
        // redirigir directamente al shortener (mejor que nada)
        setTimeout(function() {
            if (!redirectAttempted) {
                clearInterval(checkInterval);
                console.warn('No se pudo expandir el shortener del lado del cliente, redirigiendo directamente');
                window.location.href = shortenerUrl;
            }
        }, 5000);
        
        // Cargar el shortener en el iframe
        iframe.src = shortenerUrl;
        
    })();
    </script>
</body>
</html>
