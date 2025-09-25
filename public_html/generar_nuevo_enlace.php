<?php
// Generar nuevo enlace de recuperación de contraseña
session_start();
require_once 'vendor/autoload.php';
include_once 'inc/includes.php';
include_once 'inc/funciones.php';

echo "<h1>Generar Nuevo Enlace de Recuperación</h1>";

// Habilitar logging de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$email = isset($_GET['email']) ? $_GET['email'] : 'thevega82@gmail.com';

echo "<h2>Generando enlace para: <strong>$email</strong></h2>";

try {
    // Encriptar el email con las nuevas funciones
    $email_encriptado = encriptar($email);
    $email_encode = urlencode($email_encriptado);
    $url_recuperacion = "https://www.codigoamigo.com/nuevo_password?codigo=" . $email_encode;
    
    echo "<h3>Nuevo enlace de recuperación:</h3>";
    echo "<p><a href='$url_recuperacion' target='_blank' style='word-break: break-all;'>$url_recuperacion</a></p>";
    
    echo "<h3>Test de desencriptación:</h3>";
    $email_desencriptado = desencriptar($email_encriptado);
    echo "<p>Email desencriptado: <strong>$email_desencriptado</strong></p>";
    
    if ($email === $email_desencriptado) {
        echo "<p style='color: green;'>✅ Enlace generado correctamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error en la generación del enlace</p>";
    }
    
    echo "<h3>Formulario para probar:</h3>";
    echo "<form method='GET'>";
    echo "<p>Email: <input type='email' name='email' value='$email' required></p>";
    echo "<p><button type='submit'>Generar nuevo enlace</button></p>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Nota:</strong> Usa este enlace para probar la recuperación de contraseña.</p>";
?>


