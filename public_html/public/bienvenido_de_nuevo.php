<?php
    // Usar el header moderno
    get_header_modern($title, $description);

    // Procesar activación de usuario
    if (isset($_GET['codigo']) && !empty($_GET['codigo'])) {
        $codigo_activacion = $_GET['codigo'];

        // Incluir funciones necesarias
        include_once __DIR__ . '/../myphp/funciones.php';
        include_once __DIR__ . '/../myphp/funciones_usuario.php';

        // Debug: mostrar información del código recibido
        error_log("Código de activación recibido: " . $codigo_activacion);

        // Desencriptar el código para obtener el email
        $email_usuario = desencriptar($codigo_activacion);
        error_log("Email desencriptado: " . $email_usuario);

        if ($email_usuario && filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
            error_log("Email válido: " . $email_usuario);

            // Buscar al usuario por email
            $usuario = getObjectUser('mail', $email_usuario);
            error_log("Usuario encontrado: " . ($usuario ? 'Sí' : 'No'));
            error_log("Estado del usuario: " . ($usuario ? $usuario['estado'] : 'N/A'));

            if ($usuario && $usuario['estado'] == 0) {
                // Activar al usuario
                $activacion_resultado = activar_usuario($email_usuario);
                error_log("Resultado de activación: " . ($activacion_resultado ? 'Éxito' : 'Error'));

                if ($activacion_resultado) {
                    $activacion_exitosa = true;
                    error_log("Usuario activado correctamente: " . $email_usuario);
                } else {
                    $error_activacion = "Error al activar el usuario en la base de datos";
                    error_log("Error al activar usuario: " . $email_usuario);
                }
            } else {
                $error_activacion = "Usuario no encontrado o ya activado";
                error_log("Usuario no encontrado o ya activado: " . $email_usuario . " - Estado: " . ($usuario ? $usuario['estado'] : 'No encontrado'));
            }
        } else {
            $error_activacion = "Código de activación inválido";
            error_log("Código de activación inválido: " . $codigo_activacion . " -> " . $email_usuario);
        }
    }
?>

<div class="container-fluid" style="background: #1a1a1a; min-height: 100vh; padding: 0;">
    <!-- Banner de activación -->
    <div style="background: linear-gradient(135deg, #E30613, #f7931e); padding: 40px 0; text-align: center;">
        <div class="container">
            <h1 style="color: white; font-size: 48px; font-weight: bold; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                ¡Cuenta Activada! 🎉
            </h1>
            <p style="color: white; font-size: 20px; margin: 15px 0 0 0; opacity: 0.9;">
                Redirigiendo a tu área personal...
            </p>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container" style="padding: 60px 0;">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <?php if (isset($activacion_exitosa) && $activacion_exitosa): ?>
                <!-- Mensaje de éxito moderno -->
                <div class="success-message" style="background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 40px; border-radius: 15px; margin: 40px 0; text-align: center; box-shadow: 0 10px 30px rgba(76, 175, 80, 0.4);">
                    <div style="font-size: 80px; margin-bottom: 25px;">✅</div>
                    <h2 style="margin: 0 0 20px 0; font-size: 32px; font-weight: 600;">¡Usuario activado correctamente!</h2>
                    <p style="margin: 0 0 25px 0; font-size: 20px; opacity: 0.9;">Tu cuenta ha sido activada exitosamente. Te estamos redirigiendo a tu área personal...</p>

                    <!-- Spinner de carga -->
                    <div class="loading-spinner" style="margin: 30px 0;">
                        <div style="width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.3); border-top: 4px solid white; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                        <p style="margin: 15px 0 0 0; font-size: 16px; opacity: 0.8;">Redirigiendo...</p>
                    </div>
                </div>
                <?php elseif (isset($error_activacion)): ?>
                <!-- Mensaje de error -->
                <div class="error-message" style="background: linear-gradient(135deg, #f44336, #d32f2f); color: white; padding: 40px; border-radius: 15px; margin: 40px 0; text-align: center; box-shadow: 0 10px 30px rgba(244, 67, 54, 0.4);">
                    <div style="font-size: 80px; margin-bottom: 25px;">❌</div>
                    <h2 style="margin: 0 0 20px 0; font-size: 32px; font-weight: 600;">Error al activar usuario</h2>
                    <p style="margin: 0 0 25px 0; font-size: 20px; opacity: 0.9;"><?php echo htmlspecialchars($error_activacion); ?></p>

                    <!-- Botón para intentar de nuevo -->
                    <div style="margin: 30px 0;">
                        <a href="/registro" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 30px; border: 2px solid white; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                            <i class="fas fa-arrow-left" style="margin-right: 10px;"></i> Ir al Registro
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <!-- Mensaje por defecto (sin código de activación) -->
                <div class="info-message" style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white; padding: 40px; border-radius: 15px; margin: 40px 0; text-align: center; box-shadow: 0 10px 30px rgba(33, 150, 243, 0.4);">
                    <div style="font-size: 80px; margin-bottom: 25px;">ℹ️</div>
                    <h2 style="margin: 0 0 20px 0; font-size: 32px; font-weight: 600;">Activación de Cuenta</h2>
                    <p style="margin: 0 0 25px 0; font-size: 20px; opacity: 0.9;">Para activar tu cuenta, necesitas hacer clic en el enlace que recibiste por email.</p>

                    <!-- Botón para solicitar nuevo email -->
                    <div style="margin: 30px 0;">
                        <a href="/registro" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 30px; border: 2px solid white; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                            <i class="fas fa-envelope" style="margin-right: 10px;"></i> Solicitar Nuevo Email
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Información rápida -->
                <div class="quick-info" style="background: #2a2a2a; padding: 30px; border-radius: 15px; margin: 30px 0; text-align: center; border: 1px solid #333;">
                    <h3 style="color: #E30613; margin-bottom: 15px; font-size: 24px; font-weight: 600;">🎉 ¡Bienvenido a Código Amigo!</h3>
                    <p style="font-size: 18px; color: #ccc; margin: 0;">
                        Ahora puedes <strong style="color: #E30613;">publicar códigos</strong>, <strong style="color: #E30613;">buscar ofertas</strong> y <strong style="color: #E30613;">ahorrar dinero</strong> con nuestra comunidad.
                    </p>
                </div>
                
                <!-- Botón de acción manual (por si falla la redirección) -->
                <div class="manual-redirect" style="text-align: center; margin: 30px 0;">
                    <p style="color: #ccc; margin-bottom: 20px; font-size: 16px;">
                        Si no eres redirigido automáticamente, haz clic aquí:
                    </p>
                    <a href="/mis-anuncios" style="padding: 15px 30px; font-size: 18px; text-decoration: none; display: inline-block; border-radius: 10px; background: #E30613; color: white; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(227, 6, 19, 0.3);">
                        📋 Ir a Mis Anuncios
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Animación del spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Animaciones suaves */
.success-message, .community-info, .additional-info {
    animation: fadeInUp 0.8s ease-out;
}

.stat-item {
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 25px rgba(227, 6, 19, 0.2) !important;
    border-color: #E30613 !important;
}

.action-buttons a:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3) !important;
}

.action-buttons a:first-child:hover {
    box-shadow: 0 8px 20px rgba(227, 6, 19, 0.4) !important;
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

/* Efectos adicionales para el diseño moderno */
.container-fluid {
    background: linear-gradient(180deg, #1a1a1a 0%, #2a2a2a 100%);
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
    }
    
    .additional-info > div {
        grid-template-columns: 1fr !important;
    }
    
    .action-buttons a {
        display: block !important;
        margin: 15px auto !important;
        width: 80%;
    }
    
    .container-fluid h1 {
        font-size: 36px !important;
    }
    
    .container-fluid p {
        font-size: 16px !important;
    }
}

@media (max-width: 480px) {
    .container-fluid h1 {
        font-size: 28px !important;
    }
    
    .success-message h2 {
        font-size: 24px !important;
    }
    
    .community-info h2 {
        font-size: 24px !important;
    }
}
</style>

<script>
// Redirección automática solo si la activación fue exitosa
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($activacion_exitosa) && $activacion_exitosa): ?>
    // Mostrar mensaje de éxito
    console.log('Cuenta activada correctamente');

    // Redirigir después de 3 segundos
    setTimeout(function() {
        window.location.href = '/mis-anuncios?msg=account_activated';
    }, 3000);

    // Mostrar countdown visual
    let countdown = 3;
    const countdownElement = document.querySelector('.loading-spinner p');

    const countdownInterval = setInterval(function() {
        countdown--;
        if (countdownElement) {
            countdownElement.textContent = `Redirigiendo en ${countdown} segundos...`;
        }

        if (countdown <= 0) {
            clearInterval(countdownInterval);
        }
    }, 1000);
    <?php endif; ?>
});
</script>

<?php get_footer(); ?>