<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/inc/funciones.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

if (!in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado - Panel de Marcas</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f8f9fa;
                margin: 0;
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .error-container {
                background: white;
                padding: 2rem;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
            }
            .error-icon {
                font-size: 3rem;
                color: #dc3545;
                margin-bottom: 1rem;
            }
            h1 {
                color: #333;
                margin-bottom: 1rem;
            }
            p {
                color: #666;
                margin-bottom: 2rem;
            }
            .btn {
                background: #FF6B35;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
                display: inline-block;
            }
            .btn:hover {
                background: #E55A2B;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">🔒</div>
            <h1>Acceso Denegado</h1>
            <p>Necesitas permisos de administrador para acceder a esta página.</p>
            <a href="https://www.codigoamigo.com" class="btn">Volver al Inicio</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Incluir el panel de marcas
include_once __DIR__ . '/public/admin_marcas.php';
?>
