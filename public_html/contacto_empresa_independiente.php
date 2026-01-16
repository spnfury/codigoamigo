<?php
// Página de contacto para empresas independiente
$title = "Contacto para empresas - CodigoAmigo.com";
$description = "Contacta con nuestro equipo empresarial para colaboraciones, partnerships y propuestas comerciales.";

// Incluir archivos necesarios
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/_header_modern.php';

// Configurar variables globales
$author_web = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
$noindex = 1;

get_header_new($title, $description);
?>
<!-- Script de reCAPTCHA v3 -->
<script src="https://www.google.com/recaptcha/api.js?render=6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS" async defer></script>

<!-- Estilos removidos - reCAPTCHA v3 no necesita estilos especiales -->
<div class="container container-top container-bottom">
    <div class="row">
    	<div class="col-md-12 col-xs-12 text-center"><h1 class="title_page">Formulario de contacto para empresas</h1></div>
        <div class="col-md-8 col-md-offset-2">
        	<form id="contacto_empresas" class="formulario">
        		<div class="row">
        			<div class="col-md-2"><label>Nombre: </label></div>
        			<div class="col-md-8">
        				<input type="text" class="form-control" name="nombre" id="nombre" placeholder="Empresa SL" required>
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
        				<textarea class="form-control" name="mensaje" id="mensaje" rows="4" required placeholder="Hablanos sobre tu marca y que propuesta quieres hacernos llegar. Te responderemos lo más pronto posible."></textarea>
        			</div>
        		</div><br>
        		<!-- reCAPTCHA v3 funciona en segundo plano -->

				<div class="text-center">
					<input class="btn btn_codigo_amigo" value="Enviar Mensaje" type="submit">
				</div>

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
// Manejo de formulario empresarial con reCAPTCHA v3
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página cargada, inicializando formulario empresarial con reCAPTCHA v3...');

    var form = document.getElementById('contacto_empresas');
    var submitBtn = form.querySelector('input[type="submit"]');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Cambiar estado del botón
        submitBtn.disabled = true;
        submitBtn.value = 'Enviando...';

        // Ejecutar reCAPTCHA v3
        grecaptcha.execute('6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS', {action: 'contact_business'})
        .then(function(token) {
            // Crear FormData con el token
            var formData = new FormData(form);
            formData.append('g-recaptcha-response', token);
            formData.append('form_type', 'empresarial');

            // Enviar formulario
            return fetch('procesar_contacto.php', {
                method: 'POST',
                body: formData
            });
        })
        .then(response => response.text())
        .then(result => {
            console.log('Respuesta del servidor:', result);

            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.value = 'Enviar Mensaje';

            if (result.includes('SUCCESS')) {
                alert('✅ Mensaje enviado correctamente. Te responderemos pronto.');
                form.reset();
            } else {
                alert('❌ Error al enviar el mensaje: ' + result);
            }
        })
        .catch(error => {
            console.error('Error:', error);

            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.value = 'Enviar Mensaje';

            alert('❌ Error de conexión. Por favor, inténtalo de nuevo.');
        });
    });
});
</script>

<?php get_footer(); ?>


