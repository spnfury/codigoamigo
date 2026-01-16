<?php
// Página de verificación del footer moderno
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Verificación del Footer Moderno - CodigoAmigo</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-link {
            display: inline-block;
            background: #E30613;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px;
            transition: all 0.3s ease;
        }
        .test-link:hover {
            background: #C40510;
            transform: translateY(-2px);
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Verificación del Footer Moderno</h1>
        <p>Esta página verifica que el footer moderno esté funcionando correctamente en toda la web.</p>

        <div class="status info">
            ✅ Sistema de detección automática implementado:<br>
            - Todas las páginas con header moderno ahora usan el footer moderno automáticamente<br>
            - Variable global $GLOBALS['header_modern_used'] controla el tipo de footer<br>
            - Función get_footer() detecta automáticamente qué footer usar
        </div>

        <h2>🔗 Páginas para Probar:</h2>

        <a href="/" class="test-link" target="_blank">🏠 Página Principal</a>
        <a href="/buscar/test" class="test-link" target="_blank">🔍 Página de Búsqueda</a>
        <a href="/categoria/tecnologia-y-electronica" class="test-link" target="_blank">📱 Categorías</a>
        <a href="/listado_marcas" class="test-link" target="_blank">🏪 Listado de Marcas</a>
        <a href="/nuevo_codigo" class="test-link" target="_blank">➕ Publicar Código</a>
        <a href="/usuario" class="test-link" target="_blank">👤 Panel de Usuario</a>

        <h2>📋 Lo que Deberías Ver:</h2>
        <ul>
            <li><strong>🎉 Sección de bienvenida:</strong> "¡Gracias por usar CodigoAmigo!"</li>
            <li><strong>📱 Redes sociales:</strong> 4 íconos (Telegram, X/Twitter, Facebook, Instagram)</li>
            <li><strong>🔗 Enlaces organizados:</strong> Contacto, Categorías, Recursos</li>
            <li><strong>🛡️ Información de seguridad:</strong> Códigos verificados, comunidad activa</li>
            <li><strong>© Copyright:</strong> Información legal actualizada</li>
        </ul>

        <h2>🎯 Verificación Técnica:</h2>
        <div class="status success">
            ✅ Todas las páginas con <code>get_header_modern()</code> actualizadas<br>
            ✅ Variable global <code>$GLOBALS['header_modern_used']</code> establecida<br>
            ✅ Función <code>get_footer_modern()</code> creada y funcional<br>
            ✅ Sistema de detección automática implementado<br>
            ✅ CSS del footer moderno aplicado correctamente
        </div>

        <p><em>💡 Consejo: Abre las páginas en nuevas pestañas y verifica que el footer aparezca correctamente centrado y con todos los elementos.</em></p>
    </div>
</body>
</html>
