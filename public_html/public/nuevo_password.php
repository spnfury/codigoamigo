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
    color: #2d3748;
}

.password-input:focus {
    outline: none;
    border-color: #E30613;
    background: white;
    box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
}

.password-input[readonly] {
    background-color: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
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
}
</style>

<div class="password-recovery-container">
    <div class="password-recovery-card">
        <h1 class="password-recovery-title">Nueva contraseña</h1>
        
        <?php if (isset($cadena_desencriptada) && !empty($cadena_desencriptada)): ?>
            <p class="password-recovery-subtitle">
                Por favor, introduce tu nueva contraseña para acceder a tu cuenta.
            </p>
            
            <form role="form" name="form_nuevo_password" id="form_nuevo_password" action="actualizar_usuario" method="POST">
                <div class="password-input-wrapper">
                    <span class="password-input-icon">@</span>
                    <input 
                        readonly 
                        type="email" 
                        class="password-input" 
                        name="mail" 
                        id="mail" 
                        value="<?php echo htmlspecialchars($cadena_desencriptada); ?>"
                        tabindex="-1">
                </div>
                
                <div class="password-input-wrapper">
                    <span class="password-input-icon"><i class="glyphicon glyphicon-lock"></i></span>
                    <input 
                        required 
                        type="password" 
                        class="password-input" 
                        name="nueva_password" 
                        id="nueva_password" 
                        placeholder="Nueva contraseña"
                        minlength="6">
                </div>
                
                <div class="password-input-wrapper">
                    <span class="password-input-icon"><i class="glyphicon glyphicon-lock"></i></span>
                    <input 
                        required 
                        type="password" 
                        class="password-input" 
                        name="nueva_confirm_password" 
                        id="nueva_confirm_password" 
                        placeholder="Confirmar nueva contraseña"
                        minlength="6">
                </div>
                
                <button type="submit" class="password-submit-btn" id="btn_actualizar">
                    Actualizar usuario
                </button>
            </form>
        <?php else: ?>
            <div class="alert-error-modern">
                <strong>Error:</strong> Código de recuperación inválido o expirado. Por favor, solicita uno nuevo.
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="/cambiar_password" class="btn" style="color: #E30613; font-weight: 600;">Volver a intentarlo</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('form_nuevo_password');
    var pass = document.getElementById('nueva_password');
    var confirm = document.getElementById('nueva_confirm_password');
    var btn = document.getElementById('btn_actualizar');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            if (pass.value !== confirm.value) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
                confirm.focus();
                return false;
            }
            btn.textContent = 'Actualizando...';
            btn.disabled = true;
        });
    }
});
</script>

<?php get_footer(); ?>