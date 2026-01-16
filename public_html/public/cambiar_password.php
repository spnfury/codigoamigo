<?php get_header_modern($title, $description); ?>

<style>
.password-recovery-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: linear-gradient(135deg, #2d2d2d 0%, #404040 50%, #1a1a1a 100%);
}

.password-recovery-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(227, 6, 19, 0.2);
    padding: 40px;
    max-width: 500px;
    width: 100%;
    margin: 0 auto;
    border-top: 4px solid #E30613;
}

.password-recovery-title {
    font-size: 28px;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 10px;
    text-align: center;
}

.password-recovery-subtitle {
    font-size: 16px;
    color: #718096;
    margin-bottom: 30px;
    text-align: center;
    line-height: 1.5;
}

.password-input-wrapper {
    position: relative;
    margin-bottom: 25px;
}

.password-input-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    font-size: 20px;
    pointer-events: none;
}

.password-input {
    width: 100%;
    padding: 15px 15px 15px 50px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f7fafc;
}

.password-input:focus {
    outline: none;
    border-color: #E30613;
    background: white;
    box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
}

.password-submit-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #E30613 0%, #FF4D4D 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
}

.password-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4);
}

.password-submit-btn:active {
    transform: translateY(0);
}

.password-help-text {
    font-size: 14px;
    color: #718096;
    text-align: center;
    line-height: 1.6;
}

.password-help-text a {
    color: #E30613;
    text-decoration: none;
    font-weight: 600;
}

.password-help-text a:hover {
    text-decoration: underline;
}

.alert-success-modern {
    background: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 15px;
}

.alert-error-modern {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
    color: #721c24;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 15px;
}

@media (max-width: 768px) {
    .password-recovery-card {
        padding: 30px 20px;
    }
    
    .password-recovery-title {
        font-size: 24px;
    }
    
    .password-recovery-subtitle {
        font-size: 14px;
    }
    
    .password-input {
        font-size: 16px; /* Prevenir zoom en iOS */
    }
}
</style>

<div class="password-recovery-container">
    <div class="password-recovery-card">
        <?php 
        if(isset($_REQUEST["msg"]) && $_REQUEST["msg"]){
            echo '<div class="alert-success-modern">
                    ✓ ¡Mensaje enviado correctamente! Revisa tu correo.
                  </div>';
        } elseif (isset($_REQUEST["msg_error"]) && $_REQUEST["msg_error"]){
            $error_message = "El correo introducido no aparece en nuestra base de datos";
            if ($_REQUEST["msg_error"] == "invalid_code") {
                $error_message = "Código de recuperación inválido o expirado";
            }
            echo '<div class="alert-error-modern">
                    ✕ ' . htmlspecialchars($error_message) . '
                  </div>';
        } 
        ?>
        
        <h1 class="password-recovery-title">Recuperar contraseña</h1>
        <p class="password-recovery-subtitle">
            Introduce tu dirección de correo electrónico y te enviaremos instrucciones para recuperar tu contraseña.
        </p>
        
        <form role="form" name="form_recuperar_password" id="form_recuperar_password" action="/cambio_password" method="POST">
            <div class="password-input-wrapper">
                <span class="password-input-icon">@</span>
                <input 
                    type="email" 
                    required 
                    class="password-input" 
                    name="mail" 
                    id="mail" 
                    placeholder="tu-email@ejemplo.com"
                    autocomplete="email"
                >
            </div>
            
            <button id="recuperar_pass" class="password-submit-btn" type="submit">
                Recuperar contraseña
            </button>
            
            <p class="password-help-text">
                ¿No tienes acceso a este correo?<br>
                Contáctanos a través de nuestro <a href="/contacto">formulario de contacto</a>
            </p>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('form_recuperar_password');
    var btn = document.getElementById('recuperar_pass');
    
    if (form && btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (form.checkValidity()) {
                btn.textContent = 'Enviando...';
                btn.disabled = true;
                form.submit();
            } else {
                form.reportValidity();
            }
            return false;
        });
    }
});
</script>

<?php get_footer(); ?>