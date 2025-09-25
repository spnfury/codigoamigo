<?php 
    // Usar el header moderno
    get_header_modern($title, $description);
?>

<div class="container-fluid" style="background: #1a1a1a; min-height: 100vh; padding: 0;">
    <!-- Banner de activación -->
    <div style="background: linear-gradient(135deg, #ff6b35, #f7931e); padding: 40px 0; text-align: center;">
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
                
                <!-- Información rápida -->
                <div class="quick-info" style="background: #2a2a2a; padding: 30px; border-radius: 15px; margin: 30px 0; text-align: center; border: 1px solid #333;">
                    <h3 style="color: #ff6b35; margin-bottom: 15px; font-size: 24px; font-weight: 600;">🎉 ¡Bienvenido a Código Amigo!</h3>
                    <p style="font-size: 18px; color: #ccc; margin: 0;">
                        Ahora puedes <strong style="color: #ff6b35;">publicar códigos</strong>, <strong style="color: #ff6b35;">buscar ofertas</strong> y <strong style="color: #ff6b35;">ahorrar dinero</strong> con nuestra comunidad.
                    </p>
                </div>
                
                <!-- Botón de acción manual (por si falla la redirección) -->
                <div class="manual-redirect" style="text-align: center; margin: 30px 0;">
                    <p style="color: #ccc; margin-bottom: 20px; font-size: 16px;">
                        Si no eres redirigido automáticamente, haz clic aquí:
                    </p>
                    <a href="/mis-anuncios" style="padding: 15px 30px; font-size: 18px; text-decoration: none; display: inline-block; border-radius: 10px; background: #ff6b35; color: white; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);">
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
    box-shadow: 0 10px 25px rgba(255, 107, 53, 0.2) !important;
    border-color: #ff6b35 !important;
}

.action-buttons a:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3) !important;
}

.action-buttons a:first-child:hover {
    box-shadow: 0 8px 20px rgba(255, 107, 53, 0.4) !important;
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
// Redirección automática a mis anuncios después de 3 segundos
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

<?php get_footer(); ?>