<?php
// Página de prueba para verificar el menú de propietario del código
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Test Menú Propietario - CodigoAmigo</title>
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
        <h1>🧪 Test Menú Propietario</h1>
        <p>Esta página prueba que el menú de propietario aparece correctamente cuando un usuario ve su propio código.</p>

        <div class="status info">
            ✅ Funcionalidad implementada:<br>
            - Detección automática si el usuario es propietario del código<br>
            - Menú de gestión con opciones: Editar, Eliminar, Ver estadísticas<br>
            - API de eliminación de códigos con verificación de permisos<br>
            - Estilos específicos para el menú de propietario
        </div>

        <h2>🔗 Páginas para Probar:</h2>

        <a href="/de-indexacapital?codigo=5e2f827a708f7c1ca878b782" class="test-btn" target="_blank">
            🎯 Código Indexa Capital (ver menú propietario)
        </a>

        <a href="/buscar/codigo" class="test-btn" target="_blank">
            🔍 Página de Búsqueda
        </a>

        <a href="/" class="test-btn" target="_blank">
            🏠 Página Principal
        </a>

        <h2>📋 Lo que Deberías Ver (si estás logueado):</h2>
        <ul>
            <li><strong>👤 Información del usuario:</strong> Muestra quién publicó el código</li>
            <li><strong>⚙️ Menú de gestión:</strong> Solo aparece si eres el propietario</li>
            <li><strong>✏️ Editar código:</strong> Botón para editar el código</li>
            <li><strong>🗑️ Eliminar código:</strong> Botón para eliminar el código (con confirmación)</li>
            <li><strong>📊 Ver estadísticas:</strong> Botón para ver estadísticas del código</li>
        </ul>

        <h2>🎨 Características Técnicas:</h2>
        <div class="status success">
            ✅ Verificación de permisos automática<br>
            ✅ API RESTful para eliminación de códigos<br>
            ✅ Funciones JavaScript para gestión<br>
            ✅ Estilos diferenciados para propietario<br>
            ✅ Confirmación antes de eliminar
        </div>

        <h2>🔐 Seguridad Implementada:</h2>
        <ul>
            <li><strong>Autenticación requerida:</strong> Solo usuarios logueados pueden gestionar códigos</li>
            <li><strong>Verificación de propiedad:</strong> Solo el propietario puede editar/eliminar</li>
            <li><strong>Validación de sesión:</strong> Se verifica la sesión en cada operación</li>
            <li><strong>Confirmación obligatoria:</strong> Se pide confirmación antes de eliminar</li>
        </ul>

        <p><em>💡 Consejo: Para ver el menú de propietario, necesitas estar logueado como el usuario que publicó el código.</em></p>
    </div>
</body>
</html>
