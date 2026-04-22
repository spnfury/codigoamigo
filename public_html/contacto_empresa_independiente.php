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
        		<!-- reCAPTCHA v3 handled by js/funciones.js -->

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

<?php get_footer(); ?>


