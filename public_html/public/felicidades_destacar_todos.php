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
$session_id = $_GET['session_id'] ?? '';
$tipo = $_GET['tipo'] ?? 'normal'; // Por defecto normal, puede ser 'normal' o 'super'

// Verificar que tenemos session_id
if (empty($session_id)) {
    header("Location: /mis-anuncios?error=session_id_faltante");
    exit;
}

// Verificar pago con Stripe
try {
    require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

    $stripe_secret_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
    $stripe = new \Stripe\StripeClient($stripe_secret_key);

    $session = $stripe->checkout->sessions->retrieve($session_id);

    if ($session->payment_status != 'paid') {
        header("Location: /mis-anuncios?error=pago_no_completado");
        exit;
    }

} catch (Exception $e) {
    error_log("Error verificando pago de destacar todos: " . $e->getMessage());
    header("Location: /mis-anuncios?error=error_verificacion_pago");
    exit;
}

// Obtener todos los códigos del usuario
$collection_codigos = getCollectionCodigos();
$codigos_usuario = $collection_codigos->find(['id_usuario' => $_SESSION["user_id"]]);

$codigos_destacados = [];
$errores = [];

// Destacar todos los códigos del usuario
foreach ($codigos_usuario as $codigo) {
    try {
        // Obtener la marca para determinar la posición
        $marca = getObjectMarca('nombre_clave', $codigo['marca']);
        if (!$marca) continue;

        // Calcular nueva posición (posición 1 para todos)
        $nueva_posicion = 1;

        // Preparar datos de actualización
        $update_data = [
            'posicion' => $nueva_posicion,
            'destacado' => time(), // sin fecha de fin (modelo puja)
            'fecha_destacado' => date('Y-m-d H:i:s'),
            'tipo_destacado' => $tipo
        ];
        
        // Para destacado "super", establecer también destacado_social (aparece en home y tiene prioridad)
        if ($tipo === 'super') {
            $update_data['destacado_social'] = time();
        }

        // Actualizar el código
        $updateResult = $collection_codigos->updateOne(
            ['_id' => $codigo['_id']],
            ['$set' => $update_data]
        );

        if ($updateResult->getModifiedCount() > 0) {
            // Si es destacado super, notificar a usuarios del home (solo una vez, no por cada código)
            if ($tipo === 'super' && !isset($notificacion_enviada)) {
                if (function_exists('notificar_competencia_home_destacado_super')) {
                    $codigo_actualizado = $collection_codigos->findOne(['_id' => $codigo['_id']]);
                    $emails_enviados = notificar_competencia_home_destacado_super(
                        (string)$codigo['_id'],
                        $_SESSION["user_id"],
                        $codigo_actualizado
                    );
                    error_log("Notificaciones de competencia home enviadas desde destacar todos: $emails_enviados");
                    $notificacion_enviada = true; // Marcar para no enviar múltiples veces
                }
            }
            $codigos_destacados[] = [
                'id' => $codigo['_id'],
                'marca' => $marca['nombre'],
                'codigo' => $codigo['codigo'],
                'posicion' => $nueva_posicion
            ];
        }

    } catch (Exception $e) {
        $errores[] = "Error destacando código " . $codigo['codigo'] . ": " . $e->getMessage();
        error_log("Error destacando código " . $codigo['_id'] . ": " . $e->getMessage());
    }
}

// Guardar en el historial
if (!empty($codigos_destacados)) {
    try {
        $collection_historial = getCollectionHistorial();

        $historial_entry = [
            'user_id' => $_SESSION["user_id"],
            'tipo' => 'destacar_todos',
            'fecha' => date('Y-m-d H:i:s'),
            'detalles' => 'Destacados ' . count($codigos_destacados) . ' códigos (prioridad sin fecha límite)',
            'monto' => 9.99,
            'codigos_destacados' => count($codigos_destacados),
            'session_id' => $session_id
        ];

        $collection_historial->insertOne($historial_entry);

    } catch (Exception $e) {
        error_log("Error guardando historial de destacar todos: " . $e->getMessage());
    }
}

// Usar header moderno
get_header_modern("Códigos Destacados - Código Amigo", "Todos tus códigos han sido destacados exitosamente.");
?>

<div class="container-fluid" style="background: linear-gradient(180deg, #1a1a1a 0%, #2a2a2a 100%); min-height: 100vh; padding: 0;">
    <!-- Banner de éxito -->
    <div style="background: linear-gradient(135deg, #FF9800, #F57C00); padding: 50px 0; text-align: center;">
        <div class="container">
            <div style="display: inline-block; background: rgba(255,255,255,0.15); padding: 20px; border-radius: 50%; margin-bottom: 25px;">
                <i class="fas fa-star" style="font-size: 3rem; color: white;"></i>
            </div>
            <h1 style="color: white; font-size: 3rem; font-weight: bold; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                ¡Códigos Destacados! ⭐
            </h1>
            <p style="color: white; font-size: 1.3rem; margin: 20px 0 0 0; opacity: 0.95;">
                Todos tus códigos han sido destacados exitosamente
            </p>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container" style="padding: 60px 0;">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <!-- Resumen de la operación -->
                <div class="success-summary" style="background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 40px; border-radius: 20px; margin: 40px 0; text-align: center; box-shadow: 0 15px 35px rgba(76, 175, 80, 0.4);">
                    <div style="font-size: 80px; margin-bottom: 25px;">🎉</div>
                    <h2 style="margin: 0 0 20px 0; font-size: 2.5rem; font-weight: 600;">¡Operación Exitosa!</h2>
                    <p style="margin: 0 0 25px 0; font-size: 1.3rem; opacity: 0.95; line-height: 1.6;">
                        Has destacado <strong><?php echo count($codigos_destacados); ?> códigos</strong> con prioridad sin fecha límite
                        con un solo pago de <strong>9,99€</strong>
                    </p>

                    <!-- Información del pago -->
                    <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 15px; margin: 25px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <strong style="font-size: 1.2rem;">💳 Método de pago:</strong>
                                <span style="font-size: 1.1rem; margin-left: 10px;">Tarjeta de crédito</span>
                            </div>
                            <div>
                                <strong style="font-size: 1.2rem;">💰 Total pagado:</strong>
                                <span style="font-size: 1.5rem; margin-left: 10px; color: #FFE082;">9,99€</span>
                            </div>
                            <div>
                                <strong style="font-size: 1.2rem;">⏱️ Duración:</strong>
                                <span style="font-size: 1.1rem; margin-left: 10px;">Sin fecha límite (hasta que otro usuario te supere)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de códigos destacados -->
                <?php if (!empty($codigos_destacados)): ?>
                <div class="highlighted-codes" style="background: white; border-radius: 15px; padding: 30px; margin: 30px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                    <h3 style="color: #FF9800; margin-bottom: 25px; font-size: 1.8rem; font-weight: 600; text-align: center;">
                        📋 Códigos Destacados
                    </h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($codigos_destacados as $codigo): ?>
                        <div style="background: linear-gradient(135deg, #FFF3E0, #FFE0B2); border: 2px solid #FF9800; border-radius: 12px; padding: 20px; text-align: center; position: relative;">
                            <div style="position: absolute; top: -8px; right: -8px; background: #4CAF50; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: bold;">
                                ⭐ Destacado
                            </div>

                            <div style="margin-bottom: 15px;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #E65100; margin-bottom: 5px;">
                                    <?php echo htmlspecialchars($codigo['marca']); ?>
                                </div>
                                <div style="font-family: monospace; font-size: 1.2rem; font-weight: bold; color: #2E7D32; background: white; padding: 8px; border-radius: 6px; border: 2px dashed #4CAF50;">
                                    <?php echo htmlspecialchars($codigo['codigo']); ?>
                                </div>
                            </div>

                            <div style="color: #E65100; font-weight: bold; font-size: 1.1rem;">
                                Posición #<?php echo $codigo['posicion']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php elseif (empty($errores)): ?>
                <!-- Mensaje cuando no hay códigos para destacar -->
                <div class="no-codes" style="background: white; border-radius: 15px; padding: 40px 30px; margin: 30px 0; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                    <div style="font-size: 4rem; margin-bottom: 20px;">📭</div>
                    <h3 style="color: #666; margin-bottom: 15px; font-size: 1.5rem;">No tienes códigos para destacar</h3>
                    <p style="color: #888; margin-bottom: 25px;">Publica algunos códigos primero y luego podrás destacarlos todos de una vez.</p>
                    <a href="/nuevo_codigo" style="background: #4CAF50; color: white; padding: 15px 30px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">
                        <i class="fas fa-plus-circle"></i>
                        Publicar mi primer código
                    </a>
                </div>
                <?php endif; ?>

                <!-- Errores si los hubo -->
                <?php if (!empty($errores)): ?>
                <div class="error-summary" style="background: linear-gradient(135deg, #f44336, #d32f2f); color: white; padding: 25px; border-radius: 15px; margin: 30px 0;">
                    <h4 style="margin: 0 0 15px 0; font-size: 1.3rem;">⚠️ Algunos códigos no pudieron ser destacados:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Acciones -->
                <div style="text-align: center; margin: 40px 0;">
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                        <a href="/mis-anuncios" style="background: #4CAF50; color: white; padding: 18px 35px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 1.2rem; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3); display: inline-flex; align-items: center; gap: 10px;">
                            <i class="fas fa-list"></i>
                            Ver Mis Anuncios
                        </a>

                        <a href="/nuevo_codigo" style="background: #2196F3; color: white; padding: 18px 35px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 1.2rem; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(33, 150, 243, 0.3); display: inline-flex; align-items: center; gap: 10px;">
                            <i class="fas fa-plus-circle"></i>
                            Publicar Nuevo Código
                        </a>
                    </div>
                </div>

                <!-- Información adicional -->
                <div style="text-align: center; margin: 40px 0; padding: 30px; background: #2a2a2a; border-radius: 15px; border: 1px solid #333;">
                    <h3 style="color: #E30613; margin-bottom: 15px; font-size: 1.5rem;">🎯 ¿Qué significa esto?</h3>
                    <p style="margin: 0 0 20px 0; font-size: 1.1rem; color: #ccc;">
                        Todos tus códigos ahora aparecen en las <strong>primeras posiciones</strong> de cada marca sin fecha límite. Mantendrán la prioridad hasta que otro usuario destaque por encima.
                        Esto significa que tendrán mucha más visibilidad y recibirán más clicks de los usuarios.
                    </p>
                    <div style="background: rgba(227, 6, 19, 0.1); padding: 15px; border-radius: 10px; border-left: 4px solid #E30613;">
                        <p style="margin: 0; font-size: 1rem; color: #E30613;">
                            💡 <strong>Consejo:</strong> Los códigos destacados pueden generar hasta <strong>10 veces más ingresos</strong>
                            que los códigos normales. ¡Aprovecha al máximo esta inversión!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Animaciones suaves */
.success-summary, .highlighted-codes, .error-summary, .action-buttons, .additional-info {
    animation: fadeInUp 0.8s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .container-fluid h1 {
        font-size: 2.2rem !important;
    }

    .success-summary {
        padding: 25px !important;
        margin: 20px 0 !important;
    }

    .highlighted-codes {
        padding: 20px !important;
    }

    .action-buttons {
        flex-direction: column !important;
        gap: 15px !important;
    }

    .action-buttons a {
        width: 100% !important;
        justify-content: center !important;
    }
}
</style>

<?php get_footer(); ?>
