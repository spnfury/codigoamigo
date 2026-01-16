<?php
// Página de prueba para códigos que son enlaces
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Test Código Enlace - CodigoAmigo</title>
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
        .test-btn {
            background: linear-gradient(135deg, #E30613, #C40510);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
            text-decoration: none;
            display: inline-block;
        }
        .test-btn:hover {
            background: linear-gradient(135deg, #C40510, #d14920);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4);
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
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Código Enlace</h1>
        <p>Esta página prueba cómo se muestran los códigos que son enlaces en lugar de códigos tradicionales.</p>

        <div class="status info">
            ✅ Problema solucionado:<br>
            - Cuando un código es un enlace, ya no se muestra duplicado<br>
            - Solo se muestra la sección de "Enlace directo" con el botón para abrirlo<br>
            - El botón de copiar funciona correctamente para enlaces<br>
            - Se muestra notificación apropiada al copiar
        </div>

        <h2>🔗 Pruebas Disponibles:</h2>

        <a href="/de-indexacapital?codigo=5e2f827a708f7c1ca878b782" class="test-btn" target="_blank">
            🎯 Código Indexa Capital (Enlace)
        </a>

        <a href="/buscar/codigo" class="test-btn" target="_blank">
            🔍 Página de Búsqueda
        </a>

        <a href="/" class="test-btn" target="_blank">
            🏠 Página Principal
        </a>

        <h2>📋 Lo que Deberías Ver:</h2>
        <ul>
            <li><strong>🎯 Código Enlace:</strong> Solo muestra el enlace directo, no código duplicado</li>
            <li><strong>🔗 Enlace directo:</strong> Se puede hacer clic para abrir en nueva pestaña</li>
            <li><strong>📋 Copiar enlace:</strong> Botón funciona correctamente copiando el enlace</li>
            <li><strong>✨ Notificación:</strong> Muestra "Enlace copiado" apropiadamente</li>
        </ul>

        <h2>🎨 Características Implementadas:</h2>
        <div class="status success">
            ✅ Detección automática de URLs vs códigos<br>
            ✅ Interfaz diferenciada para enlaces<br>
            ✅ Función de copiar mejorada<br>
            ✅ Notificaciones contextuales<br>
            ✅ Estilos específicos para enlaces
        </div>

        <p><em>💡 Consejo: Prueba hacer clic en "Copiar enlace" en un código que sea un enlace para ver la nueva funcionalidad.</em></p>
    </div>
</body>
</html>
