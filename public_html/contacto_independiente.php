<?php
// Página de contacto independiente
$title = "Contacto - CodigoAmigo.com";
$description = "Contacta con nosotros para cualquier duda, consulta o sugerencia. Te responderemos lo más pronto posible.";

// Incluir archivos necesarios
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/_header_modern.php';

// Configurar variables globales
$author_web = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
$noindex = 1;

get_header_new($title, $description);
?>

<!-- reCAPTCHA v3 -->
<script src="https://www.google.com/recaptcha/api.js?render=6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS" async defer></script>

<style>
/* Asegurar que el widget de reCAPTCHA sea visible */
/* (Ya no se usa el widget v2 visible) */

/* Estilos adicionales para el contenedor de reCAPTCHA */
.recaptcha-container {
    margin: 20px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    text-align: center;
}

/* Fallback si reCAPTCHA no carga */
.recaptcha-fallback {
    display: none;
    padding: 20px;
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 5px;
    color: #856404;
}
</style>

<div class="container container-top container-bottom">
    <div class="row">
    	<div class="col-md-12 col-xs-12 text-center"><h1 class="title_page">Formulario de contacto</h1></div>
        <div class="col-md-8 col-md-offset-2">
        	<form id="contacto_usuarios" class="formulario">
        		<div class="row">
        			<div class="col-md-2"><label>Nombre: </label></div>
        			<div class="col-md-8">
        				<input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ana Gándara" required>
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-2"><label>Correo: </label></div>
        			<div class="col-md-8">
        				<input type="email" class="form-control" name="correo" id="correo" placeholder="alguien@alguien.com" required>
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-2"><label>Teléfono: </label></div>
        			<div class="col-md-8">
        				<input type="number" class="form-control" name="telefono" id="telefono" placeholder="Introduce tu teléfono (opcional)">
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-2"><label>Mensaje: </label></div>
        			<div class="col-md-8">
        				<textarea class="form-control" name="mensaje" id="mensaje" rows="4" required placeholder="Explícanos tus dudas, consultas o sugerencias. Te responderemos lo más pronto posible."></textarea>
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-12">
                    <div class="recaptcha-container">
                        <p><strong>Verificación de seguridad:</strong></p>
                        <p><small>Protegido por reCAPTCHA v3. La verificación se realiza en segundo plano.</small></p>
                    </div>
        			</div>
        		</div><br>
				<div class="text-center">
					<input class="btn btn_codigo_amigo" value="Enviar Mensaje" type="submit">
				</div>

				<p class="text-justify">
                        		Si deseas ponerte en contacto con nosotros de forma más directa, puedes escribirnos a <b class="enlace">info@codigoamigo.com</b>
                        		donde estaremos encantados de atenderte.
                    		</p><br>

                        <!-- Fallback para reCAPTCHA -->
                        <div class="recaptcha-fallback" id="recaptcha-fallback" style="display: none;">
                            <p><strong>⚠️ reCAPTCHA no disponible</strong></p>
                            <p>El sistema de verificación no está disponible. Por favor, contacta directamente a <strong>info@codigoamigo.com</strong></p>
                        </div>
            </form>
        </div>
    </div>
</div>

<script>
// Envío con reCAPTCHA v3 (invisible)
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('contacto_usuarios');
    if (!form) { return; }

    var submitBtn = form.querySelector('input[type="submit"]');
    var fallbackDiv = document.getElementById('recaptcha-fallback');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (typeof grecaptcha === 'undefined' || typeof grecaptcha.execute !== 'function') {
            if (fallbackDiv) fallbackDiv.style.display = 'block';
            alert('El sistema de verificación no está disponible. Por favor, contacta a info@codigoamigo.com');
            return false;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.value = 'Enviando...';
        }

        grecaptcha.execute('6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS', { action: 'contact' }).then(function(token) {
            // Enviar vía AJAX al endpoint existente (ajax_actions.php)
            $.ajax({
                type: 'POST',
                url: '/myphp/ajax_actions.php',
                data: {
                    metodo: 'formulario_contacto',
                    nombre: $('#nombre').val(),
                    correo: $('#correo').val(),
                    telefono: $('#telefono').val(),
                    mensaje: $('#mensaje').val(),
                    origin: 'Formulario contacto usuarios (v3)',
                    recaptcha_response: token
                },
                cache: false,
                success: function(data) {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.value = 'Enviar Mensaje'; }
                    if (data.trim() === 'success') {
                        alert('Mensaje enviado correctamente');
                        form.reset();
                    } else if (data.trim() === 'recaptcha_error') {
                        alert('Error de verificación reCAPTCHA. Por favor, inténtalo de nuevo.');
                    } else {
                        alert('Error al enviar el mensaje. Por favor, inténtalo de nuevo.');
                    }
                },
                error: function() {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.value = 'Enviar Mensaje'; }
                    alert('Error al enviar el mensaje. Por favor, inténtalo de nuevo.');
                }
            });
        });
    });
});
</script>

<?php get_footer(); ?>


