<?php
require_once __DIR__ . '/public_html/vendor/autoload.php';
require_once __DIR__ . '/public_html/inc/includes.php';
require_once __DIR__ . '/public_html/myphp/funciones.php';
require_once __DIR__ . '/public_html/myphp/funciones_destacados_email.php';

$usuario = ['mail' => 'test@codigoamigo.com', 'username' => 'Test User', '_id' => '12345'];
$codigo = ['tipo_destacado' => 'normal', '_id' => '67890'];

$dias_restantes = 2;
$marca_nombre = "Spliiitcom";
$nombre = "Test User";
$tipo = "Destacado Normal";
$precio = "0,99€";
$fecha_fin = "25/03/2026";

$contenido = '
        <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Tu código <strong>' . htmlspecialchars($tipo) . '</strong> en la marca <strong>' . htmlspecialchars(ucfirst($marca_nombre)) . '</strong> expira en <strong>' . $dias_restantes . ' días</strong> (el ' . $fecha_fin . ').</p>
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:15px;margin:20px 0;">
            <p style="margin:0;color:#856404;">⏰ Cuando expire, tu código dejará de aparecer en posición destacada.</p>
        </div>
        <p>Renueva ahora por solo <strong>' . $precio . '</strong> para mantener tu visibilidad.</p>';

$html = _templateBaseDestacadoEmail('⏰ Tu destacado expira pronto', $contenido, 'Renovar ahora', 'https://example.com');
echo bin2hex($html) . "\n";
echo "Done\n";
