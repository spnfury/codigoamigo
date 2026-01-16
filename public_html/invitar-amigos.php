<?php
// La sesión ya está iniciada en app_with_mongo.php

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: /");
    exit;
}

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/inc/funciones.php';

// Obtener datos del usuario
$usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
if (!$usuario) {
    header("Location: /");
    exit;
}

// Generar o obtener código de referido
$codigo_referido = generarCodigoReferido($_SESSION["user_id"], $data_usuario);

// Debug: mostrar información de sesión y usuario
error_log("Debug invitar-amigos.php: user_id=" . $_SESSION["user_id"] . ", username=" . ($data_usuario['username'] ?? 'NULL') . ", codigo_referido=" . $codigo_referido);

// Obtener lista de amigos referidos
$amigos_referidos = obtenerAmigosReferidos($_SESSION["user_id"]);

// Obtener estadísticas de referidos
$estadisticas = obtenerEstadisticasReferidos($_SESSION["user_id"]);


// Configurar variables para el header
$title = "Invita a tus amigos y gana dinero - Código Amigo";
$description = "Invita a tus amigos a Código Amigo y gana 5€ por cada amigo que se registre y verifique su perfil. ¡Comparte tu código de referido!";
$title_social = "Invita a tus amigos y gana dinero";
$description_social = "Gana 5€ por cada amigo que se registre en Código Amigo usando tu código de referido.";

// Incluir header
get_header_modern($title, $description, $title_social, $description_social);
?>

<div class="container-fluid" style="margin-top: 20px;">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <!-- Header de la página -->
            <div class="referral-header text-center" style="background: linear-gradient(135deg, #E30613, #FF4D4D); color: white; padding: 40px 20px; border-radius: 15px; margin-bottom: 30px;">
                <h1 style="margin: 0 0 15px 0; font-size: 2.5em; font-weight: 700;">
                    <i class="fas fa-gift"></i> Invita a tus amigos
                </h1>
                <p style="font-size: 1.2em; margin: 0; opacity: 0.9;">
                    Gana <strong>5€</strong> por cada amigo que se registre y verifique su perfil
                </p>
            </div>

            <!-- Estadísticas -->
            <div class="row" style="margin-bottom: 30px;">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card text-center" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="font-size: 2em; color: #E30613; font-weight: bold;"><?php echo $estadisticas['total_referidos']; ?></div>
                        <div style="color: #666; font-size: 0.9em;">Amigos invitados</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card text-center" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="font-size: 2em; color: #28a745; font-weight: bold;"><?php echo $estadisticas['referidos_verificados']; ?></div>
                        <div style="color: #666; font-size: 0.9em;">Perfiles verificados</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card text-center" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="font-size: 2em; color: #28a745; font-weight: bold;"><?php echo $estadisticas['dinero_ganado']; ?>€</div>
                        <div style="color: #666; font-size: 0.9em;">Dinero ganado</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card text-center" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="font-size: 2em; color: #ffc107; font-weight: bold;"><?php echo $estadisticas['dinero_pendiente']; ?>€</div>
                        <div style="color: #666; font-size: 0.9em;">Pendiente</div>
                    </div>
                </div>
            </div>

            <!-- Código de referido -->
            <div class="referral-code-section" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h2 style="color: #333; margin-bottom: 20px;">
                    <i class="fas fa-code"></i> Tu código de referido
                </h2>
                
                <div class="code-display" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="font-size: 1.5em; font-weight: bold; color: #E30613; text-align: center; margin-bottom: 10px;">
                        <?php echo $codigo_referido; ?>
                    </div>
                    <div style="text-align: center;">
                        <button class="btn btn-primary" onclick="copiarCodigo()" style="background: #E30613; border-color: #E30613;">
                            <i class="fas fa-copy"></i> Copiar código
                        </button>
                    </div>
                </div>

                <div class="referral-link" style="background: #e8f4fd; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h4 style="color: #333; margin-bottom: 15px;">
                        <i class="fas fa-link"></i> Enlace de referido
                    </h4>
                    <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                        <div id="referral-link" style="word-break: break-all; font-family: monospace; font-size: 0.9em; color: #333; background: white; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                            <?php
                            $referral_url = 'https://www.codigoamigo.com/registro?ref=' . $codigo_referido;
                            echo htmlspecialchars($referral_url);
                            // Debug: mostrar el código de referido
                            echo '<!-- Debug: codigo_referido = ' . htmlspecialchars($codigo_referido) . ' -->';
                            ?>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <button class="btn btn-info" onclick="copiarEnlace()" style="background: #17a2b8; border-color: #17a2b8;">
                            <i class="fas fa-copy"></i> Copiar enlace
                        </button>
                    </div>
                </div>

                <!-- Botones de compartir -->
                <div class="share-buttons text-center">
                    <h4 style="color: #333; margin-bottom: 15px;">
                        <i class="fas fa-share-alt"></i> Compartir en redes sociales
                    </h4>
                    <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                        <button class="btn btn-success" onclick="compartirWhatsApp()" style="background: #25d366; border-color: #25d366;">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </button>
                        <button class="btn btn-primary" onclick="compartirFacebook()" style="background: #1877f2; border-color: #1877f2;">
                            <i class="fab fa-facebook"></i> Facebook
                        </button>
                        <button class="btn btn-info" onclick="compartirTwitter()" style="background: #1da1f2; border-color: #1da1f2;">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                        <button class="btn btn-secondary" onclick="compartirEmail()" style="background: #6c757d; border-color: #6c757d;">
                            <i class="fas fa-envelope"></i> Email
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cómo funciona -->
            <div class="how-it-works" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h2 style="color: #333; margin-bottom: 25px;">
                    <i class="fas fa-question-circle"></i> ¿Cómo funciona?
                </h2>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="step text-center" style="padding: 20px;">
                            <div style="background: #E30613; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.5em; font-weight: bold;">
                                1
                            </div>
                            <h4 style="color: #333; margin-bottom: 10px;">Comparte tu código</h4>
                            <p style="color: #666; font-size: 0.9em;">Comparte tu código de referido o enlace con tus amigos</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step text-center" style="padding: 20px;">
                            <div style="background: #28a745; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.5em; font-weight: bold;">
                                2
                            </div>
                            <h4 style="color: #333; margin-bottom: 10px;">Tu amigo se registra</h4>
                            <p style="color: #666; font-size: 0.9em;">Tu amigo se registra usando tu código de referido</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step text-center" style="padding: 20px;">
                            <div style="background: #ffc107; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.5em; font-weight: bold;">
                                3
                            </div>
                            <h4 style="color: #333; margin-bottom: 10px;">Verifica su perfil</h4>
                            <p style="color: #666; font-size: 0.9em;">Cuando verifique su email, ¡ganas 5€ automáticamente!</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de amigos referidos -->
            <?php if (!empty($amigos_referidos)): ?>
            <div class="referrals-list" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
                <h2 style="color: #333; margin-bottom: 25px;">
                    <i class="fas fa-users"></i> Tus amigos referidos
                </h2>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Amigo</th>
                                <th>Fecha de registro</th>
                                <th>Estado</th>
                                <th>Recompensa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($amigos_referidos as $amigo): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($amigo['username']); ?></strong>
                                </td>
                                <td><?php echo $amigo['fecha_registro']; ?></td>
                                <td>
                                    <?php if ($amigo['estado'] == 1): ?>
                                        <span class="label label-success">Verificado</span>
                                    <?php else: ?>
                                        <span class="label label-warning">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($amigo['estado'] == 1): ?>
                                        <span style="color: #28a745; font-weight: bold;">+5€</span>
                                    <?php else: ?>
                                        <span style="color: #ffc107;">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="no-referrals text-center" style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
                <i class="fas fa-users" style="font-size: 4em; color: #ddd; margin-bottom: 20px;"></i>
                <h3 style="color: #666; margin-bottom: 15px;">Aún no has invitado a nadie</h3>
                <p style="color: #999;">¡Comparte tu código de referido para empezar a ganar dinero!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Función para copiar código
function copiarCodigo() {
    const codigo = '<?php echo htmlspecialchars($codigo_referido); ?>';
    navigator.clipboard.writeText(codigo).then(function() {
        mostrarNotificacion('¡Código copiado al portapapeles!', 'success');
    }).catch(function(err) {
        console.error('Error al copiar: ', err);
        mostrarNotificacion('Error al copiar el código', 'error');
    });
}

// Función para copiar enlace
function copiarEnlace() {
    const enlace = document.getElementById('referral-link').textContent;
    navigator.clipboard.writeText(enlace).then(function() {
        mostrarNotificacion('¡Enlace copiado al portapapeles!', 'success');
    }).catch(function(err) {
        console.error('Error al copiar: ', err);
        mostrarNotificacion('Error al copiar el enlace', 'error');
    });
}

// Función para compartir en WhatsApp
function compartirWhatsApp() {
    const referralUrl = 'https://www.codigoamigo.com/registro?ref=<?php echo htmlspecialchars($codigo_referido); ?>';
    const texto = '¡Únete a Código Amigo y encuentra los mejores descuentos! Usa mi código de referido: <?php echo htmlspecialchars($codigo_referido); ?> - ' + referralUrl;
    const url = 'https://wa.me/?text=' + encodeURIComponent(texto);
    window.open(url, '_blank');
}

// Función para compartir en Facebook
function compartirFacebook() {
    const referralUrl = 'https://www.codigoamigo.com/registro?ref=<?php echo htmlspecialchars($codigo_referido); ?>';
    const url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(referralUrl);
    window.open(url, '_blank', 'width=600,height=400');
}

// Función para compartir en Twitter
function compartirTwitter() {
    const referralUrl = 'https://www.codigoamigo.com/registro?ref=<?php echo htmlspecialchars($codigo_referido); ?>';
    const texto = '¡Únete a Código Amigo y encuentra los mejores descuentos! Usa mi código: <?php echo htmlspecialchars($codigo_referido); ?>';
    const url = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(texto) + '&url=' + encodeURIComponent(referralUrl);
    window.open(url, '_blank', 'width=600,height=400');
}

// Función para compartir por email
function compartirEmail() {
    const referralUrl = 'https://www.codigoamigo.com/registro?ref=<?php echo htmlspecialchars($codigo_referido); ?>';
    const asunto = 'Únete a Código Amigo - Código de referido';
    const cuerpo = `¡Hola!

Te invito a unirte a Código Amigo, donde puedes encontrar los mejores códigos de descuento y cupones verificados.

Usa mi código de referido: <?php echo htmlspecialchars($codigo_referido); ?>

Regístrate aquí: ${referralUrl}

¡Espero verte pronto en Código Amigo!`;
    
    const url = 'mailto:?subject=' + encodeURIComponent(asunto) + '&body=' + encodeURIComponent(cuerpo);
    window.location.href = url;
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo) {
    const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
    const icon = tipo === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    
    const notificacion = `
        <div class="alert ${alertClass} alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="${icon}"></i> ${mensaje}
        </div>
    `;
    
    $('body').append(notificacion);
    
    // Auto-remover después de 3 segundos
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}
</script>

<style>
/* Estilos adicionales para la página de referidos */
.referral-header {
    background: linear-gradient(135deg, #E30613, #FF4D4D);
    color: white;
    padding: 40px 20px;
    border-radius: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.step {
    padding: 20px;
}

.step:hover {
    background: #f8f9fa;
    border-radius: 10px;
}

@media (max-width: 768px) {
    .referral-header h1 {
        font-size: 2em !important;
    }
    
    .referral-header p {
        font-size: 1em !important;
    }
    
    .share-buttons .btn {
        margin-bottom: 10px;
    }
}
</style>

<?php
// Incluir footer
include_once __DIR__ . '/myphp/_footer.php';
?>
