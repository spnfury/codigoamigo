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
    // Mostrar página de acceso
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso al Panel de Marcas - CodigoAmigo</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .access-container {
                background: white;
                padding: 3rem;
                border-radius: 15px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
                width: 90%;
            }
            .access-icon {
                font-size: 4rem;
                color: #E30613;
                margin-bottom: 1rem;
            }
            h1 {
                color: #333;
                margin-bottom: 1rem;
                font-size: 2rem;
            }
            p {
                color: #666;
                margin-bottom: 2rem;
                line-height: 1.6;
            }
            .btn-access {
                background: #E30613;
                color: white;
                padding: 15px 30px;
                border: none;
                border-radius: 8px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s ease;
                margin: 10px;
            }
            .btn-access:hover {
                background: #C40510;
                transform: translateY(-2px);
            }
            .btn-secondary {
                background: #6c757d;
            }
            .btn-secondary:hover {
                background: #5a6268;
            }
            .admin-info {
                background: #f8f9fa;
                padding: 1.5rem;
                border-radius: 8px;
                margin-top: 2rem;
                text-align: left;
            }
            .admin-info h3 {
                color: #333;
                margin-bottom: 1rem;
                font-size: 1.2rem;
            }
            .admin-info ul {
                list-style: none;
                padding: 0;
            }
            .admin-info li {
                padding: 0.5rem 0;
                color: #666;
            }
            .admin-info li i {
                color: #E30613;
                margin-right: 0.5rem;
            }
        </style>
    </head>
    <body>
        <div class="access-container">
            <div class="access-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>Acceso Restringido</h1>
            <p>Necesitas permisos de administrador para acceder al panel de gestión de marcas.</p>
            
            <a href="https://www.codigoamigo.com" class="btn-access">
                <i class="fas fa-home"></i> Ir al Inicio
            </a>
            
            <a href="https://www.codigoamigo.com/login" class="btn-access btn-secondary">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
            
            <div class="admin-info">
                <h3><i class="fas fa-info-circle"></i> Información para Administradores</h3>
                <ul>
                    <li><i class="fas fa-user"></i> Debes estar logueado con una cuenta autorizada</li>
                    <li><i class="fas fa-key"></i> Solo usuarios con permisos especiales pueden acceder</li>
                    <li><i class="fas fa-cog"></i> Contacta al administrador principal si necesitas acceso</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Si tiene permisos, redirigir al panel
header('Location: /public/admin_marcas.php');
exit;
?>
