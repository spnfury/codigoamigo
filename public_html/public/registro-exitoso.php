<?php
    // Usar el header moderno
    get_header_modern("Registro Exitoso - Código Amigo", "Tu registro ha sido exitoso. Revisa tu email para activar tu cuenta.");
?>

<div class="container-fluid" style="background: linear-gradient(180deg, #1a1a1a 0%, #2a2a2a 100%); min-height: 100vh; padding: 0;">
    <!-- Banner de éxito -->
    <div style="background: linear-gradient(135deg, #4CAF50, #45a049); padding: 50px 0; text-align: center;">
        <div class="container">
            <div style="display: inline-block; background: rgba(255,255,255,0.15); padding: 20px; border-radius: 50%; margin-bottom: 25px;">
                <i class="fas fa-envelope" style="font-size: 3rem; color: white;"></i>
            </div>
            <h1 style="color: white; font-size: 3rem; font-weight: bold; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                ¡Registro Exitoso! 📧
            </h1>
            <p style="color: white; font-size: 1.3rem; margin: 20px 0 0 0; opacity: 0.95;">
                Te hemos enviado un email de activación
            </p>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container" style="padding: 60px 0;">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Mensaje principal -->
                <div class="success-message" style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white; padding: 50px; border-radius: 20px; margin: 40px 0; text-align: center; box-shadow: 0 15px 35px rgba(33, 150, 243, 0.4);">
                    <div style="font-size: 100px; margin-bottom: 30px;">✉️</div>
                    <h2 style="margin: 0 0 25px 0; font-size: 2.5rem; font-weight: 600;">Email Enviado</h2>
                    <p style="margin: 0 0 30px 0; font-size: 1.3rem; opacity: 0.95; line-height: 1.6;">
                        Hemos enviado un email de activación a tu dirección de correo electrónico.
                        <br><strong>Revisa tu bandeja de entrada</strong> y haz clic en el enlace para activar tu cuenta.
                    </p>

                    <!-- Instrucciones -->
                    <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 15px; margin: 30px 0; text-align: left;">
                        <h3 style="margin: 0 0 15px 0; font-size: 1.4rem; color: #fff;">📋 Pasos a seguir:</h3>
                        <ol style="color: #fff; font-size: 1.1rem; line-height: 1.8; padding-left: 20px;">
                            <li>Revisa tu <strong>bandeja de entrada</strong> (y también la carpeta de <strong>spam</strong>)</li>
                            <li>Busca un email de <strong>Código Amigo</strong> con asunto "Bienvenido a Código Amigo"</li>
                            <li>Haz clic en el botón <strong>"Activar mi cuenta"</strong> del email</li>
                            <li>¡Listo! Tu cuenta estará activada y podrás iniciar sesión</li>
                        </ol>
                    </div>

                    <!-- Información adicional -->
                    <div style="margin: 30px 0; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 10px;">
                        <p style="margin: 0; font-size: 1.1rem; opacity: 0.9;">
                            <strong>¿No recibiste el email?</strong><br>
                            Espera unos minutos y revisa tu carpeta de spam. Si no lo encuentras,
                            puedes solicitar un nuevo email de activación desde la página de login.
                        </p>
                    </div>
                </div>

                <!-- Acciones -->
                <div style="text-align: center; margin: 40px 0;">
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                        <a href="/login" style="background: #4CAF50; color: white; padding: 18px 35px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 1.2rem; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3); display: inline-flex; align-items: center; gap: 10px;">
                            <i class="fas fa-sign-in-alt"></i>
                            Ir a Iniciar Sesión
                        </a>

                        <a href="/registro" style="background: #E30613; color: white; padding: 18px 35px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 1.2rem; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(227, 6, 19, 0.3); display: inline-flex; align-items: center; gap: 10px;">
                            <i class="fas fa-redo"></i>
                            Registrar Otra Cuenta
                        </a>
                    </div>
                </div>

                <!-- Información de contacto -->
                <div style="text-align: center; margin: 40px 0; padding: 30px; background: #2a2a2a; border-radius: 15px; border: 1px solid #333;">
                    <h3 style="color: #E30613; margin-bottom: 15px; font-size: 1.5rem;">¿Necesitas ayuda?</h3>
                    <p style="margin: 0 0 20px 0; font-size: 1.1rem; color: #ccc;">
                        Si tienes problemas con la activación de tu cuenta, contáctanos:
                    </p>
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                        <a href="mailto:info@codigoamigo.com" style="color: #E30613; text-decoration: none; font-weight: 600; padding: 10px 20px; border: 2px solid #E30613; border-radius: 8px; transition: all 0.3s ease;">
                            <i class="fas fa-envelope" style="margin-right: 8px;"></i>
                            info@codigoamigo.com
                        </a>
                        <a href="https://t.me/spnfury" target="_blank" style="color: #0088cc; text-decoration: none; font-weight: 600; padding: 10px 20px; border: 2px solid #0088cc; border-radius: 8px; transition: all 0.3s ease;">
                            <i class="fa-brands fa-telegram" style="margin-right: 8px;"></i>
                            Soporte Telegram
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Animaciones suaves */
.success-message, .quick-info, .contact-info {
    animation: fadeInUp 0.8s ease-out;
}

.action-buttons a:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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

    .success-message {
        padding: 30px !important;
        margin: 20px 0 !important;
    }

    .success-message h2 {
        font-size: 2rem !important;
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
