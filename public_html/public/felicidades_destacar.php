<?php
// La sesión ya está iniciada en app_with_mongo.php

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: /login");
    exit;
}

// Obtener parámetros
$codigo_id = $_GET['codigo'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$metodo = $_GET['metodo'] ?? 'tarjeta';
$session_id = $_GET['session_id'] ?? '';

if (empty($codigo_id) || empty($tipo)) {
    header("Location: /mis-anuncios?error=parametros_faltantes");
    exit;
}

// Obtener información del código
$obj_id_codigo = new \MongoDB\BSON\ObjectId($codigo_id);
$codigo = getCodeByID($obj_id_codigo);

if (!$codigo || $codigo["id_usuario"] != $_SESSION["user_id"]) {
    header("Location: /mis-anuncios?error=codigo_no_encontrado");
    exit;
}

$marca = getObjectMarca('nombre_clave', $codigo["marca"]);
$marca_nombre = htmlspecialchars($marca['nombre'] ?? $codigo['marca']);

// Si es pago con tarjeta, verificar con Stripe
$pago_exitoso = false;
if ($metodo === 'tarjeta' && !empty($session_id)) {
    try {
        require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';
        
        $stripe_secret_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
        $stripe = new \Stripe\StripeClient($stripe_secret_key);
        
        $session = $stripe->checkout->sessions->retrieve($session_id);
        
        if ($session->payment_status == 'paid' && $session->client_reference_id == $codigo_id) {
            $pago_exitoso = true;
            
            // Actualizar el código para destacarlo
            $collection_codigos = getCollectionCodigos();
            $duracion_dias = $tipo === 'normal' ? 30 : 60;
            $fecha_fin = new DateTime();
            $fecha_fin->add(new DateInterval('P' . $duracion_dias . 'D'));
            
            $collection_codigos->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                [
                    '$set' => [
                        'destacado' => true,
                        'tipo_destacado' => $tipo,
                        'fecha_destacado' => new MongoDB\BSON\UTCDateTime(),
                        'fecha_fin_destacado' => new MongoDB\BSON\UTCDateTime($fecha_fin->getTimestamp() * 1000)
                    ]
                ]
            );
        }
    } catch (Exception $e) {
        error_log("Error verificando pago Stripe: " . $e->getMessage());
    }
} elseif ($metodo === 'saldo') {
    // Si es pago con saldo, verificar que el código esté realmente destacado
    if (isset($codigo['destacado']) && $codigo['destacado'] && 
        isset($codigo['tipo_destacado']) && $codigo['tipo_destacado'] === $tipo) {
        $pago_exitoso = true;
    } else {
        // Si no está destacado, intentar destacarlo usando la función moderna
        if (destacar_codigo_moderno($codigo_id, $tipo)) {
            $pago_exitoso = true;
            // Recargar los datos del código
            $codigo = getCodeByID($obj_id_codigo);
        } else {
            $pago_exitoso = false;
        }
    }
}

if (!$pago_exitoso) {
    header("Location: /mis-anuncios?error=pago_no_verificado");
    exit;
}

// Configurar información para la página
$titulo_destacado = $tipo === 'normal' ? 'Destacado Normal' : 'Destacado Super';
$precio_destacado = $tipo === 'normal' ? '0,99€' : '3,99€';
$duracion_destacado = 'Ilimitada (modelo puja)';
$descripcion_destacado = $tipo === 'normal' 
    ? 'Aparece en primera posición con badge "Destacado"'
    : 'Aparece en primera posición con badge dorado y en página principal';

$title = "¡Código destacado exitosamente! - Código Amigo";
$description = "Tu código de " . $marca_nombre . " ha sido destacado correctamente";

// No incluir header para evitar puntos de fuga
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<style>
/* Estilos para la página de felicidades destacar */
body {
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: white;
    font-family: 'Poppins', sans-serif;
    overflow-x: hidden;
}

.success-container {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    animation: slideInUp 0.8s ease-out;
    border: 1px solid rgba(255, 255, 255, 0.2);
    margin-top: 80px;
    margin-bottom: 40px;
}

.success-icon {
    font-size: 80px;
    color: #fff;
    margin-bottom: 20px;
    animation: bounceIn 1s ease-out;
}

h1 {
    font-size: 3em;
    margin-bottom: 15px;
    font-weight: 700;
    text-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

p {
    font-size: 1.2em;
    margin-bottom: 25px;
    line-height: 1.6;
}

.details-box {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
    text-align: left;
}

.details-box p {
    margin-bottom: 10px;
    font-size: 1em;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.details-box p strong {
    color: #f0f0f0;
}

.btn-group {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.btn-custom {
    background: #fff;
    color: #ff6b35;
    border: none;
    padding: 12px 25px;
    border-radius: 50px;
    font-size: 1.1em;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.btn-custom:hover {
    background: #e0e0e0;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    color: #ff6b35;
    text-decoration: none;
}

@keyframes slideInUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes bounceIn {
    0%, 20%, 40%, 60%, 80%, 100% {
        -webkit-transform: translateY(0);
        transform: translateY(0);
    }
    50% {
        -webkit-transform: translateY(-20px);
        transform: translateY(-20px);
    }
}

@media (max-width: 768px) {
    h1 {
        font-size: 2.2em;
    }
    p {
        font-size: 1em;
    }
    .success-container {
        padding: 25px;
    }
    .btn-group {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<div class="success-container">
    <i class="fas fa-star success-icon"></i>
    <h1>¡Código Destacado!</h1>
    <p>Tu código de descuento ha sido destacado exitosamente</p>
    
    <div class="details-box">
        <p>Marca: <strong><?php echo htmlspecialchars($marca_nombre); ?></strong></p>
        <p>Código: <strong><?php echo htmlspecialchars($codigo['codigo'] ?? 'N/A'); ?></strong></p>
        <p>Tipo de destacado: <strong><?php echo $titulo_destacado; ?></strong></p>
        <p>Precio pagado: <strong><?php echo $precio_destacado; ?></strong></p>
        <p>Método de pago: <strong><?php echo $metodo === 'tarjeta' ? 'Tarjeta de crédito' : 'Saldo de la cuenta'; ?></strong></p>
        <p>Duración: <strong><?php echo $duracion_destacado; ?></strong></p>
        <p>Beneficios: <strong><?php echo $descripcion_destacado; ?></strong></p>
        <p>Fecha: <strong><?php echo date('d/m/Y H:i'); ?></strong></p>
    </div>
    
    <div class="btn-group">
        <a href="/mis-anuncios" class="btn-custom">
            <i class="fas fa-home"></i> Ir a Mis Anuncios
        </a>
        <a href="/marca/<?php echo $codigo['marca']; ?>" class="btn-custom">
            <i class="fas fa-eye"></i> Ver Código Destacado
        </a>
    </div>
</div>

<?php get_footer(); ?>
</body>
</html>