<?php 

    /* MONGO DB */
	require '/home/admin/web/codigoamigo.com/public_html'.'/vendor/autoload.php';
	
	/* SENTRY */
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/inc/sentry_bootstrap.php';
	if (function_exists('codigoamigo_init_sentry')) {
		codigoamigo_init_sentry();
	}
	

	/* LIBRERIAS */
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/librerias/Mobile_Detect.php';

	/* HERRAMIENTAS */
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/herramientas/links.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/herramientas/publicidad.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/herramientas/var_globals.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/herramientas/textos_marca.php';	
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/herramientas/funciones_html_home.php';

	/* FUNCIONES */

	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/funciones.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/funciones_html.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/funciones_mail.php';

	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/funciones_marca.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/funciones_codigo.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/funciones_usuario.php';

	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/_header.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/_header_modern.php';
	include_once '/home/admin/web/codigoamigo.com/public_html'.'/myphp/_footer.php';
			
	/**********************************************************
	 *  VIEJOS INCLUDES
	 *********************************************************/
	
	include_once '/home/admin/web/codigoamigo.com/public_html' . '/inc/funciones.php';

	include_once '/home/admin/web/codigoamigo.com/public_html' . '/inc/conexion.php';
	
	/**********************************************************
	 *  DESTACADOS: Duraciones y precios por tier
	 *********************************************************/
	if (!defined('DESTACADO_DURACION_NORMAL')) {
		define('DESTACADO_DURACION_NORMAL', 7);    // días
		define('DESTACADO_DURACION_SUPER', 14);    // días
		define('DESTACADO_DURACION_GUIA', 30);     // días
		define('DESTACADO_PRECIO_NORMAL', 0.99);   // euros
		define('DESTACADO_PRECIO_SUPER', 3.99);    // euros
		define('DESTACADO_PRECIO_GUIA', 9.99);     // euros
	}

?>