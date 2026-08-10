<?php
// Configuración completa con MongoDB para CodigoAmigo.com
// Incluir sistema de logging organizado
require_once __DIR__ . '/inc/logger.php';

// Logear errores en lugar de mostrarlos en pantalla
// PRODUCCIÓN: No mostrar errores al usuario, solo logearlos
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/admin/web/codigoamigo.com/public_html/php_errors.log');
error_reporting(E_ALL);

// Para activar modo debug temporalmente, añadir ?debug_mode=1 con la IP del admin
// o cambiar esta variable a true
$APP_DEBUG_MODE = false;
if ($APP_DEBUG_MODE || (isset($_GET['debug_mode']) && $_GET['debug_mode'] === '1' && isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']))) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// Iniciar sesión ANTES de output buffering
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegurar que la sesión persista con parámetros URL
if (isset($_COOKIE['PHPSESSID']) && session_id() !== $_COOKIE['PHPSESSID']) {
    // Verificar si la sesión de la cookie es válida antes de recuperarla
    $old_session_id = session_id();
    session_id($_COOKIE['PHPSESSID']);
    session_start();

    // Si la sesión recuperada no tiene datos de usuario válidos, restaurar la sesión anterior
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
        session_id($old_session_id);
        session_start();
    }
}

// Habilitar output buffering para evitar problemas con headers
ob_start();

// Definir variables globales necesarias
$GLOBALS["author"] = "CODIGOAMIGO.COM";

// Función helper para validar ObjectId de MongoDB
function isValidObjectId($id) {
    return is_string($id) && preg_match('/^[a-f\d]{24}$/i', $id);
}

// Función auxiliar para validar sesión de usuario
function validateUserSession() {
    // Verificar que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Asegurar que la sesión esté activa
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Validar que el usuario esté logueado
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || $_SESSION["user_id"] == "") {
        header("Location: https://www.codigoamigo.com/login");
        exit;
    }
    
    // Verificar que el username esté en la sesión
    if (!isset($_SESSION["username"]) || empty($_SESSION["username"])) {
        // Intentar obtener el username de la base de datos
        try {
            $usuario_temp = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
            if ($usuario_temp && isset($usuario_temp["username"])) {
                $_SESSION["username"] = $usuario_temp["username"];
            } else {
                // Si no se puede obtener el username, redirigir al login
                header("Location: https://www.codigoamigo.com/login");
                exit;
            }
        } catch (Exception $e) {
            // Si hay error al obtener el usuario, redirigir al login
            header("Location: https://www.codigoamigo.com/login");
            exit;
        }
    }
}

// Cargar autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Inicializar Sentry lo antes posible
require_once __DIR__ . '/inc/sentry_bootstrap.php';
codigoamigo_init_sentry();

register_shutdown_function(function () {
    if (function_exists('codigoamigo_sentry_capture_last_error')) {
        codigoamigo_sentry_capture_last_error();
    }
    
    // Capturar errores fatales para Telegram
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (class_exists('Logger')) {
            // Incluir el archivo de configuración si no está definido
            if (!defined('TELEGRAM_ADMIN_CHAT_ID')) {
                @include_once __DIR__ . '/config/ai_config.php';
            }
            $url = ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' '
                 . (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://')
                 . ($_SERVER['HTTP_HOST'] ?? 'codigoamigo.com')
                 . ($_SERVER['REQUEST_URI'] ?? '');
            $user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anónimo';
            Logger::critical(
                "Fatal Error: " . $error['message']
                . "\n📄 " . $error['file'] . ":" . $error['line']
                . "\n🌐 " . $url
                . "\n👤 usuario: " . $user
            );
        }
    }
});

// Crear aplicación Slim
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true, // Habilitado temporalmente para debugear el error 500
        'addContentLengthHeader' => false,
        'determineRouteBeforeAppMiddleware' => true,
        'outputBuffering' => 'append',
    ]
]);

$app->add(function ($request, $response, $next) {
    try {
        return $next($request, $response);
    } catch (\Throwable $exception) {
        if (function_exists('codigoamigo_sentry_capture_exception')) {
            codigoamigo_sentry_capture_exception($exception);
        }

        // Notificar excepción crítica a Telegram
        if (class_exists('Logger')) {
             // Incluir el archivo de configuración si no está definido
            if (!defined('TELEGRAM_ADMIN_CHAT_ID')) {
                @include_once __DIR__ . '/config/ai_config.php';
            }
            
            $code = $exception->getCode();
            // Mensaje enriquecido: URL, ubicación, usuario y traza recortada
            $url = ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' '
                 . (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://')
                 . ($_SERVER['HTTP_HOST'] ?? 'codigoamigo.com')
                 . ($_SERVER['REQUEST_URI'] ?? '');
            $user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anónimo';
            $trace = array_slice(explode("\n", $exception->getTraceAsString()), 0, 5);
            Logger::critical(
                "Uncaught Exception ({$code}): " . $exception->getMessage()
                . "\n📄 " . $exception->getFile() . ":" . $exception->getLine()
                . "\n🌐 " . $url
                . "\n👤 usuario: " . $user
                . "\n🔎 Trace:\n" . implode("\n", $trace)
            );
        }

        throw $exception;
    }
});

// Función helper para agregar footer automáticamente a rutas que usan header moderno
function add_footer_to_modern_routes($response) {
    // Incluir footer solo si se usó el header moderno en esta petición
    if (isset($GLOBALS['header_modern_used']) && $GLOBALS['header_modern_used'] === true) {
        // La función get_footer() ya detectará automáticamente si debe usar el footer moderno
        get_footer();
        unset($GLOBALS['header_modern_used']); // Limpiar la variable global
    }
    return $response;
}

// Ruta principal
$app->get('/', function ($request, $response) {
    // Inicializar variables globales
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Inicializar variables por defecto
    $num_inicio = 1;
    $num_fin = 20;
    $codigos_restantes = 0;
    $lista_codigos = array();
    $skip_patrocinados = 0;
    $num_destacados = 0;
    $num_codes = 0;
    
    // Hacer variables globales para que estén disponibles en todos los archivos incluidos
    $GLOBALS['skip_patrocinados'] = $skip_patrocinados;
    $GLOBALS['num_codes'] = $num_codes;
    $GLOBALS['num_destacados'] = $num_destacados;
    $GLOBALS['codigos_restantes'] = $codigos_restantes;
    $GLOBALS['lista_codigos'] = $lista_codigos;

    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_utilidades.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';
    include_once __DIR__ . '/myphp/funciones_busqueda.php';
    include_once __DIR__ . '/myphp/funciones_busqueda.php';

    // Inicializar detector de móviles DESPUÉS de incluir includes.php
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    // Hacer $detect global para que esté disponible en todos los archivos incluidos
    $GLOBALS['detect'] = $detect;

    /************************************************
     * Códigos a mostrar
     ************************************************/

    $newURL = strtok($GLOBALS["actual_url"], '?');

    if (isset($_REQUEST["page"]) && $_REQUEST["page"]) {
        $limit = 97;
        $limit2 = 100;
    } else {
        $limit = 100; // Obtener los últimos 100 códigos destacados
        $limit2 = 16;
    }

    /* Para saber cuantos codigos tengo que saltar segun la página donde estoy */
    $skip = get_skip_in_pagination();
    $skip_patrocinados = get_skip_patrocinados_in_pagination();

    // Determinar el orden según el parámetro recibido
    $sort_order = array('fecha_publicacion' => -1); // orden por defecto: más recientes primero por fecha de publicación

    $orden_param = filter_input(INPUT_GET, 'orden', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if ($orden_param !== null) {
        switch ($orden_param) {
            case 'beneficio':
                $sort_order = array('num_beneficio' => -1);
                break;
            case 'valoraciones':
                $sort_order = array('num_valoraciones' => -1);
                break;
            case 'antiguos':
                $sort_order = array('fecha_publicacion' => 1);
                break;
            case 'recientes':
                $sort_order = array('fecha_publicacion' => -1);
                break;
        }
    }

    

        // Ordenar por fecha de destacado social descendente (quien acaba de destacar sale primero)
        // _id como desempate para orden 100% determinístico
        $sort_order_destacados = array('destacado_social' => -1, '_id' => -1);

        /* Listado PATROCINADOS/DESTACADOS */
        // Filtrar solo códigos Super/Guía que tengan destacado_social > 0 (los que pagan 3,99€+)
        // Los códigos Normal (0,99€) solo tienen 'destacado' y NO deben aparecer en el home
        // Solo mostrar los que NO han expirado
        $array_filtro = array(
            "estado" => 0,
            "destacado_social" => array('$gt' => 0),
            "fecha_fin_destacado" => array('$gt' => new MongoDB\BSON\UTCDateTime())
        );


        $array_skip = array("limit" => 100); // Obtener los últimos 100 códigos destacados
        $array_skip = array_merge($array_skip, array("sort" => $sort_order_destacados)); // Aplicar orden

        //TOMO LOS CODIGOS
        $lista_codigos_patrocinados_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
        $lista_codigos_patrocinados = isset($lista_codigos_patrocinados_pre["results"]) ? $lista_codigos_patrocinados_pre["results"] : [];

    // Listado TODOS LOS CODIGOS RECIENTES (Optimizado: Ordenar por modificación para más justicia y rotación)
    $array_filtro = array(
        "estado" => 0
        // Se elimina el filtro de 30 días ya que el orden por fecha_modificacion descendente se encarga de mostrar los recientes
    );
    // Excluir códigos con fecha_validez expirada
    $array_filtro = array_merge($array_filtro, get_filtro_no_expirados());

    $array_skip = array("limit" => $limit2 * 2); // Duplicar límite ya que ahora obtenemos todos los códigos recientes
    $array_skip = array_merge($array_skip, array("skip" => $skip));
    $array_skip = array_merge($array_skip, array("sort" => $sort_order));

    //TOMO LOS CODIGOS
    $lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);


    $lista_codigos = isset($lista_codigos_pre["results"]) && is_array($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];
        

    $numero_codigos = isset($lista_codigos_pre["total_number"]) ? $lista_codigos_pre["total_number"] : 0;

   
    /* MERGE */
    // $lista_codigos = array_merge(
    //     (is_array($lista_codigos_patrocinados) && !empty($lista_codigos_patrocinados) ? $lista_codigos_patrocinados : []),
    //     (is_array($lista_codigos) && !empty($lista_codigos) ? $lista_codigos : [])
    // );



    $total_pag = (intval($numero_codigos / 10)) + 1;

    // Actualizar variables globales con los datos obtenidos
    $GLOBALS['lista_codigos'] = $lista_codigos;
    $GLOBALS['numero_codigos'] = $numero_codigos;
    $GLOBALS['total_pag'] = $total_pag;
    $GLOBALS['codigos_restantes'] = $numero_codigos;

    // Incluir funciones modernas (guardas defensivas por si algún include falló antes)
    if (!function_exists('generate_modern_code_cards') || !function_exists('generate_modern_pagination')) {
        include_once __DIR__ . '/myphp/funciones_modern.php';
    }
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno

    // Usar el nuevo header moderno
    
    // Llamar a la función del header moderno
    get_header_modern(
        "CodigoAmigo.com - Códigos de Descuento Verificados",
        "Encuentra los mejores códigos de descuento verificados y actualizados diariamente. Ahorra en tus compras favoritas con CodigoAmigo.com",
        "CodigoAmigo.com - Códigos de Descuento",
        "Códigos de descuento verificados para ahorrar en tus compras",
        "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png"
    );



    // Hero Section (movido desde header global)
    ?>
    <?php
    // ────────────────────────────────────────────────────────────────────────
    // BANNER PROMOCIONES ACTIVAS (campañas referido tiempo limitado)
    // ────────────────────────────────────────────────────────────────────────
    if (function_exists('getMarcasConPromocionActiva')) {
        $promos_activas_home = getMarcasConPromocionActiva();
    } else {
        @include_once __DIR__ . '/myphp/funciones_marca.php';
        $promos_activas_home = function_exists('getMarcasConPromocionActiva') ? getMarcasConPromocionActiva() : [];
    }
    if (!empty($promos_activas_home)):
    ?>
    <div class="promo-banner-wrap" style="max-width:1200px;margin:0 auto 24px;padding:0 20px;">
      <div style="background:linear-gradient(135deg,#FF6B35,#E30613);border-radius:16px;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;box-shadow:0 8px 24px rgba(227,6,19,0.25);">
        <div style="display:flex;align-items:center;gap:16px;flex:1;min-width:280px;">
          <div style="width:54px;height:54px;background:rgba(255,255,255,0.18);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;flex-shrink:0;">🔥</div>
          <div style="color:#fff;">
            <div style="font-size:0.7rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;opacity:0.85;">Promo activa · tiempo limitado</div>
            <?php $first = $promos_activas_home[0]; ?>
            <div style="font-size:1.1rem;font-weight:800;line-height:1.25;">
              <?php echo htmlspecialchars($first['nombre']); ?> ·
              <?php echo htmlspecialchars($first['titulo'] ?: $first['bono']); ?>
              <?php if ($first['dias_restantes'] >= 0): ?>
                <span style="background:rgba(0,0,0,0.25);padding:2px 10px;border-radius:12px;font-size:0.85rem;margin-left:6px;">
                  <?php echo $first['dias_restantes'] === 0 ? '⚡ acaba hoy' : '⏳ ' . $first['dias_restantes'] . ' día' . ($first['dias_restantes'] === 1 ? '' : 's'); ?>
                </span>
              <?php endif; ?>
            </div>
            <?php if (count($promos_activas_home) > 1): ?>
              <div style="font-size:0.8rem;opacity:0.9;margin-top:4px;">+ <?php echo count($promos_activas_home) - 1; ?> marca<?php echo count($promos_activas_home) - 1 === 1 ? '' : 's'; ?> más en promoción</div>
            <?php endif; ?>
          </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <a href="/de-<?php echo htmlspecialchars($first['nombre_clave']); ?>" style="background:#fff;color:#E30613;padding:11px 22px;border-radius:30px;font-weight:800;text-decoration:none;font-size:0.9rem;white-space:nowrap;">Ver códigos <?php echo htmlspecialchars($first['nombre']); ?> →</a>
          <a href="/promociones-activas" style="background:rgba(255,255,255,0.18);color:#fff;padding:11px 22px;border-radius:30px;font-weight:700;text-decoration:none;font-size:0.85rem;white-space:nowrap;border:1px solid rgba(255,255,255,0.3);">Todas las promos</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <section class="hero-section">
        <h1 class="hero-title">Descubre marcas nuevas. <span class="hero-title-accent">Gana compartiéndolas.</span></h1>
        <p class="hero-description">Bonos, cashback y descuentos verificados de marcas que no sabías que existían. Comparte los tuyos y monetiza cuando alguien los usa.</p>
        <div class="hero-dual-cta">
            <a href="#codigos-destacados" class="hero-cta hero-cta-primary"><i class="fas fa-search"></i> Descubrir marcas</a>
            <a href="/nuevo_codigo" class="hero-cta hero-cta-secondary"><i class="fas fa-coins"></i> Compartir y ganar</a>
        </div>
    </section>
    <?php
    // VIP Awareness Banner en homepage (solo para usuarios logueados no-VIP)
    $show_vip_banner = false;
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        if (function_exists('es_usuario_vip') && !es_usuario_vip($_SESSION['user_id'])) {
            $show_vip_banner = true;
        }
    }
    if ($show_vip_banner): ?>
    <div id="vip-home-banner" style="max-width: 1200px; margin: 0 auto 30px; padding: 0 20px; display: none;">
        <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 16px; padding: 25px 30px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border: 1px solid rgba(255, 215, 0, 0.25); position: relative;">
            <button onclick="this.parentElement.parentElement.style.display='none'; localStorage.setItem('vip_banner_dismissed', Date.now())" style="position: absolute; top: 10px; right: 15px; background: none; border: none; color: rgba(255,255,255,0.4); font-size: 18px; cursor: pointer; padding: 5px; line-height: 1;">✕</button>
            <div style="display: flex; align-items: center; gap: 15px; flex: 1; min-width: 250px;">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffd700, #E30613); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-crown" style="color: white; font-size: 22px;"></i>
                </div>
                <div>
                    <h4 style="color: #ffd700; font-weight: 700; margin: 0 0 4px 0; font-size: 1.05rem;">Hazte VIP y gana más con tus códigos</h4>
                    <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 0.85rem; line-height: 1.4;">Badge dorado • Chat con viewers • 10€ de saldo gratis cada mes</p>
                </div>
            </div>
            <!-- El botón lleva directo a la pasarela, no al panel de leads.
                 Antes enlazaba a /public/mis_viewers.php: quien pulsaba un CTA
                 con un precio escrito aterrizaba en un cuadro de mando con sus
                 métricas ("55 leads totales", "2.827€ potencial") y sin nada que
                 pagar a la vista. El precio también estaba mal: anunciaba
                 9,99€/mes cuando la entrada real son 4,99€ el primer mes, que
                 además es mejor gancho. -->
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <button type="button" id="btnVipHomeBanner" style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #ffd700, #E30613); color: white; padding: 12px 24px; border: none; border-radius: 25px; font-weight: 700; font-size: 0.9rem; white-space: nowrap; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='scale(1.03)'; this.style.boxShadow='0 8px 25px rgba(255,215,0,0.3)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                    <span class="vip-banner-text"><i class="fas fa-crown"></i> Empieza por 4,99€ →</span>
                    <span class="vip-banner-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
                <small style="color: rgba(255,255,255,0.45); font-size: 0.72rem;">Luego 9,99€/mes. Cancelas cuando quieras.</small>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var dismissed = localStorage.getItem('vip_banner_dismissed');
        // Show banner if not dismissed in last 7 days
        if (!dismissed || (Date.now() - parseInt(dismissed)) > 7 * 24 * 60 * 60 * 1000) {
            document.getElementById('vip-home-banner').style.display = 'block';
        }

        // Del banner a la pasarela en un clic, sin pantallas intermedias.
        var btn = document.getElementById('btnVipHomeBanner');
        if (!btn) return;

        btn.addEventListener('click', async function() {
            var texto = btn.querySelector('.vip-banner-text');
            var spinner = btn.querySelector('.vip-banner-spinner');
            btn.disabled = true;
            btn.style.opacity = '0.8';
            if (texto) texto.style.display = 'none';
            if (spinner) spinner.style.display = 'inline-block';

            function restaurar() {
                btn.disabled = false;
                btn.style.opacity = '1';
                if (texto) texto.style.display = 'inline-flex';
                if (spinner) spinner.style.display = 'none';
            }

            try {
                if (typeof gtag === 'function') {
                    gtag('event', 'begin_checkout', {
                        currency: 'EUR', value: 4.99, source: 'banner_home',
                        items: [{ item_id: 'vip_subscription', item_name: 'Suscripción VIP', price: 4.99, quantity: 1 }]
                    });
                }
                var res = await fetch('/crear_sesion_suscripcion_vip.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ source: 'banner_home' })
                });
                var data = await res.json();
                if (data.success && data.checkout_url) {
                    window.location.href = data.checkout_url;
                    return;
                }
                // Si la pasarela falla, al menos que no se quede en nada: al
                // embudo VIP, donde puede reintentar el alta.
                window.location.href = '/public/mis_viewers.php';
            } catch (e) {
                console.error('VIP banner checkout:', e);
                restaurar();
                window.location.href = '/public/mis_viewers.php';
            }
        });
    })();
    </script>
    <?php endif; ?>

    <?php
    // Generar contenido principal con diseño moderno
    echo '<div class="main-content">';
    
    // AdSense Top — contenido limitado y centrado
    $ad_top_html = get_adsense_top();
    if (trim($ad_top_html) !== '') {
        echo '<div class="adsense-main-top" style="max-width:1200px;margin:0 auto 30px;padding:0 1rem;text-align:center;">' . $ad_top_html . '</div>';
    }

    echo '<div class="codes-section">';
    
    // Mostrar códigos destacados primero (solo en la primera página)
    if(!isset($_GET['page']) || $_GET['page'] == 1) {
        if(!empty($lista_codigos_patrocinados)) {
            echo '<a id="codigos-destacados"></a>';
            echo generate_modern_featured_cards($lista_codigos_patrocinados);
        }

        // Brand discovery: marcas que no conocías (efecto "ostras no sabía...")
        if (function_exists('generate_brand_discovery_section')) {
            echo generate_brand_discovery_section(8);
        }

        // CTA publishers latentes
        if (function_exists('generate_publisher_latent_cta')) {
            echo generate_publisher_latent_cta();
        }

        // Guías destacadas (SEO + tráfico a la sección de guías)
        if (function_exists('generate_guias_section')) {
            echo generate_guias_section(4);
        }

        // Mostrar marcas populares (solo en la primera página)
        echo generate_popular_brands_section(9);

        // Bloque de enlace interno hacia marcas cercanas a página 1 (SEO)
        echo render_marcas_oportunidad();

        // Banner de invitar amigos (solo logueados)
        if (function_exists('generate_referral_home_banner')) {
            echo generate_referral_home_banner();
        }

        // Mostrar categorías populares (solo en la primera página)
        echo generate_popular_categories_section();


    }
    
    // Mostrar información de códigos
    echo '<h2 class="section-title">Últimos Códigos Publicados</h2>';

    // Generar tarjetas de códigos modernas (TODOS los códigos recientes ordenados por fecha)
    if(!empty($lista_codigos)) {
        // Los códigos ya vienen ordenados por fecha (más recientes primero)
        // No necesitamos filtrar adicionales - ya obtenemos los más recientes independientemente del tipo

        echo '<div class="codes-grid">';
        // Limitar visualmente a 9 códigos para la home
        $codigos_home = array_slice($lista_codigos, 0, 9);
        echo generate_modern_code_cards($codigos_home);
        echo '</div>';

        // Botón "Ver todos" que lleva a la nueva ruta
        echo '<div style="text-align: center; margin-top: 30px; margin-bottom: 20px;">';
        echo '<a href="/ultimos-codigos" class="btn" style="background: transparent; border: 2px solid #E30613; color: #E30613; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;">';
        echo 'Ver todos los códigos <i class="fas fa-arrow-right"></i>';
        echo '</a>';
        echo '<style>.btn:hover { background: #E30613 !important; color: white !important; }</style>';
        echo '</div>';


    } else {
        echo '<div style="text-align: center; color: #ccc; padding: 2rem;">';
        echo '<i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: #E30613;"></i>';
        echo '<h3>No se encontraron códigos</h3>';
        echo '<p>Intenta con otros términos de búsqueda</p>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // AdSense Entremedio — contenido limitado y centrado
    $ad_mid_html = get_adsense_entremedio();
    if (trim($ad_mid_html) !== '') {
        echo '<div class="adsense-home-middle" style="max-width:1200px;margin:30px auto;padding:0 1rem;text-align:center;">' . $ad_mid_html . '</div>';
    }

    // Bloque de tendencias de búsqueda (solo home principal)
    if (!isset($_GET['page']) || (int)$_GET['page'] === 1) {
        if (function_exists('get_search_statistics')) {
            $search_stats = get_search_statistics();
        } else {
            $search_stats = [
                'top_terms' => [],
                'total_today' => 0,
                'total_this_week' => 0,
                'total_this_month' => 0
            ];
        }

        echo '<div class="search-stats-section">';
        echo '<div class="container">';
        echo '<div class="search-stats-container">';

        // Bloque de búsquedas populares
        echo '<div class="popular-searches">';
        echo '<h3><i class="fas fa-fire"></i> Búsquedas populares</h3>';
        echo '<p class="popular-searches-subtitle">Descubre lo que la comunidad está buscando ahora mismo.</p>';

        if (!empty($search_stats['top_terms'])) {
            echo '<div class="popular-searches-list">';
            foreach ($search_stats['top_terms'] as $index => $term) {
                if ($index >= 8) {
                    break;
                }
                $rank = $index + 1;
                $term_text = isset($term['_id']) ? $term['_id'] : '';
                $term_count = isset($term['count']) ? (int)$term['count'] : 0;
                echo '<a class="popular-search-chip" href="/ofertas/' . urlencode($term_text) . '">';
                echo '<span class="chip-rank">' . $rank . '</span>';
                echo '<span class="chip-text">' . htmlspecialchars($term_text) . '</span>';
                echo '<span class="chip-count">' . $term_count . '</span>';
                echo '</a>';
            }
            echo '</div>';
        } else {
            echo '<div class="popular-searches-empty">';
            echo '<p>Aún no hay suficientes búsquedas registradas. Prueba explorar términos como ';
            echo '<a href="/ofertas/booking">Booking</a> o ';
            echo '<a href="/ofertas/uber">Uber</a> y vuelve en unos minutos.</p>';
            echo '</div>';
        }
        echo '</div>'; // popular-searches

        // Resumen rápido
        echo '<div class="stats-summary">';
        echo '<div class="row">';

        $summary_cards = [
            [
                'icon' => 'fas fa-search',
                'label' => 'Búsquedas hoy',
                'value' => isset($search_stats['total_today']) ? (int)$search_stats['total_today'] : 0
            ],
            [
                'icon' => 'fas fa-calendar-week',
                'label' => 'Esta semana',
                'value' => isset($search_stats['total_this_week']) ? (int)$search_stats['total_this_week'] : 0
            ],
            [
                'icon' => 'fas fa-calendar-alt',
                'label' => 'Este mes',
                'value' => isset($search_stats['total_this_month']) ? (int)$search_stats['total_this_month'] : 0
            ]
        ];

        foreach ($summary_cards as $card) {
            echo '<div class="col-md-4 col-sm-4 col-xs-12">';
            echo '<div class="stat-card">';
            echo '<div class="stat-icon"><i class="' . $card['icon'] . '"></i></div>';
            echo '<div class="stat-content">';
            echo '<div class="stat-number">' . number_format($card['value']) . '</div>';
            echo '<div class="stat-label">' . htmlspecialchars($card['label']) . '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>'; // row
        echo '</div>'; // stats-summary

        echo '</div>'; // search-stats-container
        echo '</div>'; // container
        echo '</div>'; // search-stats-section
    }

    // CSS adicional
    echo get_modern_additional_css();
    
    // CSS para indicadores de búsqueda
    echo '<style>
    .loading-indicator {
        text-align: center;
        padding: 3rem;
        color: #ccc;
        font-size: 1.1rem;
    }
    
    .loading-indicator i {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: #E30613;
    }
    
    .no-results {
        text-align: center;
        padding: 3rem;
        color: #ccc;
    }
    
    .no-results i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #E30613;
    }
    
    .search-input-header:focus,
    .search-input-hero:focus {
        outline: none;
        border-color: #E30613;
        box-shadow: 0 0 0 2px rgba(227, 6, 19, 0.2);
    }
    
    .search-stats-section {
        background: #f8f9fa;
        padding: 60px 0;
        margin: 40px 0 0;
    }

    .search-stats-container {
        background: #ffffff;
        border-radius: 18px;
        padding: 35px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    }

    .popular-searches {
        margin-bottom: 35px;
        text-align: left;
    }

    .popular-searches h3 {
        font-size: 1.6rem;
        color: #1f3c88;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .popular-searches-subtitle {
        color: #5b6c94;
        margin-bottom: 20px;
    }

    .popular-searches-list {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .popular-search-chip {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 999px;
        background: #eef2ff;
        color: #1f3c88;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
        border: 1px solid #d5ddff;
    }

    .popular-search-chip:hover {
        background: #1f3c88;
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(31, 60, 136, 0.25);
    }

    .popular-search-chip .chip-rank,
    .popular-search-chip .chip-count {
        background: rgba(255, 255, 255, 0.35);
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
    }

    .popular-searches-empty {
        background: #eef2ff;
        border-radius: 12px;
        padding: 18px;
        color: #1f3c88;
        font-weight: 500;
    }

    .popular-searches-empty a {
        color: #1f3c88;
        text-decoration: underline;
        font-weight: 600;
    }

    .stats-summary {
        margin-top: 25px;
    }

    .stat-card {
        background: linear-gradient(135deg, #E30613, #ff8f56);
        color: #ffffff;
        padding: 28px;
        border-radius: 18px;
        text-align: center;
        margin-bottom: 20px;
        box-shadow: 0 12px 30px rgba(227, 6, 19, 0.25);
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-card .stat-icon {
        font-size: 2.4rem;
        margin-bottom: 12px;
        opacity: 0.9;
    }

    .stat-card .stat-number {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 6px;
        letter-spacing: 1px;
    }

    .stat-card .stat-label {
        font-size: 0.95rem;
        text-transform: uppercase;
        opacity: 0.85;
        letter-spacing: 1.2px;
    }

    @media (max-width: 768px) {
        .search-stats-container {
            padding: 25px;
        }

        .popular-searches-list {
            gap: 10px;
        }

        .popular-search-chip {
            width: 100%;
            justify-content: space-between;
        }
    }
    </style>';

    // Incluir footer
    get_footer();

    return $response;
});

// Ruta para Super Landings (Guías)
// Índice de guías (/guias) — landing canónica de la sección
$app->get('/guias', function ($request, $response, $args) {
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    include_once __DIR__ . '/myphp/_super_landing_functions.php';

    $guias = get_active_super_landings(100);

    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
    $GLOBALS['actual_url'] = 'https://www.codigoamigo.com/guias';
    $GLOBALS['header_modern_used'] = true;

    $meta_title = 'Guías para ahorrar y ganar dinero | CodigoAmigo';
    $meta_desc = 'Guías y comparativas sobre banca, neobancos y finanzas para empresas, autónomos y particulares. Elige la mejor opción y ahorra con códigos amigo.';
    get_header_modern(
        $meta_title,
        $meta_desc,
        'Guías de CodigoAmigo',
        $meta_desc,
        'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png'
    );

    include __DIR__ . '/myphp/views/guias_index.php';

    include_once __DIR__ . '/myphp/_footer.php';
    get_footer_modern();

    return $response;
});

$app->get('/guias/{slug}', function ($request, $response, $args) {
    $slug = $args['slug'];

    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    include_once __DIR__ . '/myphp/_super_landing_functions.php';

    // Obtener datos de la Super Landing
    $landing = get_super_landing_by_slug($slug);
    
    if (!$landing) {
        // Si no existe, 404
        include_once __DIR__ . '/404.php';
        return $response->withStatus(404);
    }
    
    // Inicializar variables globales para SEO
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // Preparar Schema
    $faq_schema = '';
    if (isset($landing['sections'])) {
        foreach ($landing['sections'] as $section) {
            if ($section['type'] === 'faq' && !empty($section['faqs'])) {
                $faq_schema = render_faq_schema($section['faqs']);
                break;
            }
        }
    }
    
    // Preparar meta data adicional
    $links_meta = [
        'description' => $landing['meta_description'] ?? '',
        'schema' => $faq_schema
    ];
    
    // Llamar al header moderno
    $GLOBALS['header_modern_used'] = true;
    get_header_modern(
        $landing['meta_title'] ?? $landing['title'],
        $landing['meta_description'] ?? '',
        $landing['title'],
        $landing['meta_description'] ?? '',
        $landing['hero_image'] ?? "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png",
        $links_meta
    );
    
    // Renderizar la vista
    // Pasamos $landing y otros datos necesarios a la vista
    // Usamos include para que tenga acceso a las variables del scope actual si fuera necesario, 
    // pero idealmente pasamos todo explícitamente o usamos globales si el framework lo requiere así.
    // En este estilo procedural, incluimos el archivo.
    include __DIR__ . '/myphp/views/super_landing.php';
    
    // Incluir footer
    include_once __DIR__ . '/myphp/_footer.php';
    get_footer_modern();

    return $response;
});

// Ruta de renovación de destacado al 50%
$app->any('/renovar-destacado', function ($request, $response, $args) {
    global $author_web, $data_usuario, $anula_adsense;
    
    $anula_adsense = true;
    $title = "Renueva tu destacado con descuento";
    $description = "Renueva tu código destacado con un 50% de descuento exclusivo";

    // Hacer vars globales para el include
    $GLOBALS['title'] = $title;
    $GLOBALS['description'] = $description;
    
    // Ejecutar el script (que mandará sus propios headers de HTML)
    ob_start();
    include $_SERVER['DOCUMENT_ROOT'] . '/public/renovar_destacado.php';
    $output = ob_get_clean();
    $response->getBody()->write($output);
    return $response;
});

/********************************************************************
 * FICHA DE CÓDIGO INDIVIDUAL - /codigo/{marca}-{shortId}
 *******************************************************************/

$app->get('/codigo/{slug}', function ($request, $respon, $args) {
    global $author_web, $detect, $data_usuario;
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';

    // Asegurar que $detect esté inicializado
    if (!isset($detect) || !is_object($detect) || !($detect instanceof Mobile_Detect)) {
        if (!class_exists('Mobile_Detect')) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/myphp/librerias/Mobile_Detect.php')) {
                require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/librerias/Mobile_Detect.php';
            } elseif (file_exists(__DIR__ . '/myphp/librerias/Mobile_Detect.php')) {
                require_once __DIR__ . '/myphp/librerias/Mobile_Detect.php';
            }
        }
        if (class_exists('Mobile_Detect')) {
            $detect = new Mobile_Detect();
        } else {
            $detect = null;
        }
    }

    $slug = $args['slug'] ?? '';
    
    // Parsear el slug para obtener marca y short_id
    if (!function_exists('parse_ficha_codigo_slug')) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/myphp/links.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/links.php';
        } elseif (file_exists(__DIR__ . '/myphp/links.php')) {
            require_once __DIR__ . '/myphp/links.php';
        }
    }
    
    if (function_exists('parse_ficha_codigo_slug')) {
        $parsed = parse_ficha_codigo_slug($slug);
    } else {
        // Fallback en caso de que links.php no cargue
        $parts = explode('-', $slug);
        if (count($parts) < 2) {
            $parsed = false;
        } else {
            $short_id = array_pop($parts);
            if (strlen($short_id) !== 8) {
                $parsed = false;
            } else {
                $parsed = [
                    'marca' => implode('-', $parts),
                    'short_id' => $short_id
                ];
            }
        }
    }
    
    if (!$parsed) {
        // Slug inválido → 404
        include_once __DIR__ . '/404.php';
        return $respon->withStatus(404);
    }
    
    $marca_clave = $parsed['marca'];
    $short_id = $parsed['short_id'];
    
    // Buscar el código en MongoDB
    $collection_codigos = getCollectionCodigos();
    $codigo = null;
    
    $codigos_marca = $collection_codigos->find([
        'marca' => new \MongoDB\BSON\Regex('^' . preg_quote($marca_clave) . '$', 'i'),
        'estado' => ['$in' => [0, -1, 1]]
    ], ['limit' => 500]);
    
    foreach ($codigos_marca as $c) {
        $id_str = (string)$c['_id'];
        if (substr($id_str, -8) === $short_id) {
            $codigo = $c;
            break;
        }
    }
    
    if (!$codigo) {
        include_once __DIR__ . '/404.php';
        return $respon->withStatus(404);
    }
    
    // Incrementar vistas
    if (function_exists('añadir_vista_codigo')) {
        añadir_vista_codigo($codigo);
    }
    
    // Redirigir a la página de la marca abriendo el modal de este código particular
    $url_destino = '/de-' . $marca_clave . '?codigo=' . (string)$codigo['_id'];
    return $respon->withRedirect($url_destino, 301);
});

// Ruta para Comparativas automáticas (/comparar/marca1-vs-marca2)
$app->get('/comparar/{slug}', function ($request, $response, $args) {
    $slug = $args['slug'];
    
    // Parse marca1-vs-marca2
    $parts = explode('-vs-', $slug, 2);
    if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
        include_once __DIR__ . '/404.php';
        return $response->withStatus(404);
    }
    
    $slug_a = trim($parts[0]);
    $slug_b = trim($parts[1]);
    
    // Include necessary files
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    
    // Get brand info
    $marca_a = get_brand_info($slug_a);
    $marca_b = get_brand_info($slug_b);
    
    if (empty($marca_a['nombre']) || empty($marca_b['nombre'])) {
        include_once __DIR__ . '/404.php';
        return $response->withStatus(404);
    }
    
    // Only allow comparisons between brands in the same category
    $cat_a_clave = $marca_a['categoria_clave'] ?? '';
    $cat_b_clave = $marca_b['categoria_clave'] ?? '';
    if (empty($cat_a_clave) || empty($cat_b_clave) || $cat_a_clave !== $cat_b_clave) {
        include_once __DIR__ . '/404.php';
        return $response->withStatus(404);
    }
    
    $nombre_a = $marca_a['nombre'];
    $nombre_b = $marca_b['nombre'];
    
    // Get active codes for each brand
    $filtro_a = ['marca' => ['$regex' => '^' . preg_quote($slug_a) . '$', '$options' => 'i'], 'estado' => 0];
    $filtro_b = ['marca' => ['$regex' => '^' . preg_quote($slug_b) . '$', '$options' => 'i'], 'estado' => 0];
    $opciones = ['limit' => 50, 'sort' => ['destacado' => -1, '_id' => -1]];
    
    $result_a = get_all_listado_codigos_array($filtro_a, $opciones);
    $result_b = get_all_listado_codigos_array($filtro_b, $opciones);
    $codigos_a = $result_a['results'] ?? [];
    $codigos_b = $result_b['results'] ?? [];
    
    // Related brands (from same category)
    $related_brands = [];
    $cat_a = $marca_a['categoria_clave'] ?? '';
    if (!empty($cat_a) && function_exists('get_brands_by_category')) {
        $brands_in_cat = get_brands_by_category($cat_a . '-comparte-y-gana');
        $related_brands = array_filter($brands_in_cat, function($b) use ($slug_a, $slug_b) {
            return ($b['nombre_clave'] ?? '') !== $slug_a && ($b['nombre_clave'] ?? '') !== $slug_b;
        });
        $related_brands = array_values(array_slice($related_brands, 0, 6));
    }
    
    // SEO meta
    $title = "Código amigo $nombre_a vs $nombre_b – Comparativa " . date('Y') . " | CodigoAmigo";
    $description = "Compara los códigos de descuento de $nombre_a y $nombre_b. ¿Cuál ofrece más beneficio? Encuentra el mejor código amigo verificado para " . date('Y') . ".";
    
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $GLOBALS['header_modern_used'] = true;

    // Comparativas programáticas: noindex,follow. ~5000 páginas con ~2 clics/90d
    // queman crawl budget y diluyen calidad del dominio. Se desindexan para
    // reenfocar el crawl de Google en las páginas de marca (que sí rankean).
    $GLOBALS['noindex'] = 1;

    get_header_modern(
        $title,
        $description,
        "$nombre_a vs $nombre_b",
        $description,
        'https://www.codigoamigo.com/images/logo.png',
        ['description' => $description]
    );
    
    // Render the view
    include __DIR__ . '/myphp/views/comparar.php';
    
    // Footer
    include_once __DIR__ . '/myphp/_footer.php';
    get_footer_modern();
    
    return $response;
});

// Ruta de búsqueda simplificada
$app->get('/ofertas/{termino}', function ($request, $response, $args) {
    $termino = $args['termino'];
    $termino = htmlspecialchars(trim($termino), ENT_QUOTES, 'UTF-8');

    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';

    include_once __DIR__ . '/myphp/funciones_busqueda.php';
    include_once __DIR__ . '/myphp/funciones_usuario.php'; // Required for getCollectionLogs used in record_search_term
    include_once __DIR__ . '/myphp/_header_modern.php';
    include_once __DIR__ . '/myphp/_footer.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar variables globales para SEO
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $GLOBALS['actual_url_limpia'] = 'https://www.codigoamigo.com/ofertas/' . urlencode($termino);
    
    // Preparar meta keywords para mejor targeting de AdSense
    $year = date('Y');
    $search_keywords = htmlspecialchars($termino, ENT_QUOTES, 'UTF-8');
    $titulo_seo = 'Códigos descuento ' . $search_keywords . ', ofertas y cupones activos (' . $year . ')';
    $meta_description = 'Encuentra los mejores códigos descuento, cupones y ofertas para ' . $search_keywords . ' actualizados en ' . $year . '. ¡Ahorra con CodigoAmigo.com!';
    
    $links_meta = array(
        'keywords' => $search_keywords . ', códigos descuento, cupones, ofertas, promociones, chollos',
        'description' => $meta_description
    );

    // Schema.org BreadcrumbList
    $schema_breadcrumb = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => [
            [
                "@type" => "ListItem",
                "position" => 1,
                "name" => "Inicio",
                "item" => "https://www.codigoamigo.com/"
            ],
            [
                "@type" => "ListItem",
                "position" => 2,
                "name" => 'Descuentos ' . $search_keywords,
                "item" => $GLOBALS['actual_url_limpia']
            ]
        ]
    ];
    $links_meta['schema'] = '<script type="application/ld+json">' . json_encode($schema_breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    
    // Llamar a la función del header moderno
    get_header_modern(
        $titulo_seo,
        $titulo_seo . '. Encuentra las mejores ofertas, códigos descuento y cupones para ' . htmlspecialchars($termino),
        $titulo_seo,
        $titulo_seo . " - Cupones, descuentos y ofertas de " . htmlspecialchars($termino),
        "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png",
        $links_meta
    );

    // Registrar la búsqueda si aplica
    $queryParams = $request->getQueryParams();
    $fromTrends = isset($queryParams['from']) && $queryParams['from'] === 'trends';
    if (!$fromTrends && function_exists('record_search_term') && !empty($termino)) {
        try {
            $record_result = record_search_term($termino);
            if (!$record_result) {
                log_error("Error registrando búsqueda (record_search_term devolvió false): " . $termino);
            }
        } catch (Throwable $e) {
            log_error("Error registrando búsqueda simplificada: " . $e->getMessage());
        }
    } else {
        if (!$fromTrends && !function_exists('record_search_term')) {
            log_error("Error: record_search_term no existe al intentar registrar: " . $termino);
        }
    }

    // Preparar filtro base para la búsqueda
    $base_filter = array("estado" => 0);
    $regex_conditions = array();

    if (!empty($termino)) {
        $regex = new MongoDB\BSON\Regex($termino, 'i');
        $regex_conditions[] = array("marca" => $regex);
        $regex_conditions[] = array("descripcion" => $regex);
    }

    if (!empty($regex_conditions)) {
        $base_filter['$or'] = $regex_conditions;
    }

    // Obtener códigos destacados coincidentes
    $featured_filter = $base_filter;
    $featured_conditions = array(
        array(
            '$or' => array(
                array('destacado' => array('$gt' => 0)),
                array('destacado_social' => array('$gt' => 0))
            )
        )
    );

    if (isset($featured_filter['$and']) && is_array($featured_filter['$and'])) {
        $featured_filter['$and'] = array_merge($featured_filter['$and'], $featured_conditions);
    } else {
        $featured_filter['$and'] = $featured_conditions;
    }

    $featured_options = array(
        'limit' => 30,
        'sort' => array('destacado_social' => -1, 'destacado' => -1, '_id' => -1)
    );

    $lista_codigos_destacados = get_all_listado_codigos_array($featured_filter, $featured_options);
    $codigos_destacados = isset($lista_codigos_destacados["results"]) && is_array($lista_codigos_destacados["results"])
        ? $lista_codigos_destacados["results"]
        : array();

    // Obtener códigos generales (excluyendo los destacados)
    $general_filter = $base_filter;
    $general_conditions = array(
        array(
            '$or' => array(
                array('destacado' => 0),
                array('destacado' => array('$exists' => false))
            )
        ),
        array(
            '$or' => array(
                array('destacado_social' => 0),
                array('destacado_social' => array('$exists' => false))
            )
        )
    );

    if (!empty($general_conditions)) {
        if (isset($general_filter['$and']) && is_array($general_filter['$and'])) {
            $general_filter['$and'] = array_merge($general_filter['$and'], $general_conditions);
        } else {
            $general_filter['$and'] = $general_conditions;
        }
    }

    if (!empty($codigos_destacados)) {
        $destacados_ids = array();
        foreach ($codigos_destacados as $codigo_destacado) {
            if (isset($codigo_destacado['_id'])) {
                $destacados_ids[] = $codigo_destacado['_id'];
            }
        }
        if (!empty($destacados_ids)) {
            $general_filter['_id'] = array('$nin' => $destacados_ids);
        }
    }

    $general_options = array(
        'limit' => 50,
        'sort' => array('fecha_publicacion' => -1, '_id' => -1)
    );

    // Relevancia: primero los códigos cuya MARCA coincide con el término;
    // después los que solo lo mencionan en la descripción. Antes iban mezclados
    // por fecha y buscar "netflix" mostraba primero marcas sin relación aparente.
    $codigos_generales = array();
    if (!empty($termino) && isset($regex)) {
        $filtro_marca = $general_filter;
        $filtro_marca['$or'] = array(array('marca' => $regex));
        $res_marca = get_all_listado_codigos_array($filtro_marca, $general_options);
        $codigos_generales = isset($res_marca["results"]) && is_array($res_marca["results"]) ? $res_marca["results"] : array();

        $restante = 50 - count($codigos_generales);
        if ($restante > 0) {
            $filtro_desc = $general_filter;
            $filtro_desc['$or'] = array(array('descripcion' => $regex));
            $filtro_desc['marca'] = array('$not' => $regex);
            $opciones_desc = $general_options;
            $opciones_desc['limit'] = $restante;
            $res_desc = get_all_listado_codigos_array($filtro_desc, $opciones_desc);
            if (isset($res_desc["results"]) && is_array($res_desc["results"])) {
                $codigos_generales = array_merge($codigos_generales, $res_desc["results"]);
            }
        }
    } else {
        $lista_codigos_generales = get_all_listado_codigos_array($general_filter, $general_options);
        $codigos_generales = isset($lista_codigos_generales["results"]) && is_array($lista_codigos_generales["results"])
            ? $lista_codigos_generales["results"]
            : array();
    }



    // Incluir funciones de AdSense
    if (!function_exists('get_adsense_search')) {
        include_once __DIR__ . '/myphp/funciones_adsense.php';
    }
    
    // Mostrar resultados de búsqueda
    echo '<div class="main-content">';
    
    // Migas de pan visuales
    echo '<div class="container breadcrumb-container">';
    echo '<nav class="breadcrumb-nav">';
    echo '<a href="/" style="color: #888; text-decoration: none;">Inicio</a> <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin: 0 8px;"></i> ';
    echo '<span style="color: #E30613; font-weight: 500;">Búsqueda: ' . htmlspecialchars($termino) . '</span>';
    echo '</nav>';
    echo '</div>';

    echo '<style>
    .search-hero {
        position: relative;
        text-align: center;
        padding: 3rem 1.5rem 2.5rem;
        margin: 1.5rem 0 2rem;
        background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 60%, #ffd9d9 100%);
        border-radius: 24px;
        border: 1px solid #ffd0d0;
        overflow: hidden;
    }
    .search-hero::before {
        content: "";
        position: absolute; inset: 0;
        background: radial-gradient(circle at 20% 20%, rgba(227,6,19,0.08), transparent 60%),
                    radial-gradient(circle at 80% 80%, rgba(255,140,0,0.07), transparent 60%);
        pointer-events: none;
    }
    .search-hero h1 {
        position: relative;
        font-size: clamp(1.8rem, 4vw, 2.6rem);
        font-weight: 800;
        color: #1a1a1a;
        margin: 0 0 .6rem;
        letter-spacing: -0.02em;
    }
    .search-hero h1 .term {
        background: linear-gradient(90deg, #E30613, #ff6b35);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .search-hero p {
        position: relative;
        margin: 0;
        color: #555;
        font-size: 1.05rem;
    }
    .search-hero .hero-stats {
        position: relative;
        display: inline-flex;
        gap: .6rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 1.2rem;
    }
    .search-hero .stat-chip {
        background: #fff;
        border: 1px solid #ffd0d0;
        color: #E30613;
        padding: .45rem .9rem;
        border-radius: 999px;
        font-size: .85rem;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(227,6,19,0.08);
    }
    .search-hero .stat-chip i { margin-right: 6px; }
    .section-subtitle {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1a1a1a;
        margin: 2rem 0 1rem;
        padding-left: .9rem;
        border-left: 4px solid #E30613;
    }
    </style>';

    $total_resultados = count($codigos_destacados) + count($codigos_generales);
    echo '<div class="codes-section">';
    echo '<section class="search-hero">';
    echo '<h1>Códigos descuento <span class="term">' . htmlspecialchars($termino) . '</span></h1>';
    echo '<p>Cupones y ofertas verificadas en ' . date('Y') . '</p>';
    echo '<div class="hero-stats">';
    echo '<span class="stat-chip"><i class="fas fa-tags"></i>' . $total_resultados . ' resultado' . ($total_resultados === 1 ? '' : 's') . '</span>';
    if (!empty($codigos_destacados)) {
        echo '<span class="stat-chip"><i class="fas fa-star"></i>' . count($codigos_destacados) . ' destacados</span>';
    }
    echo '<span class="stat-chip"><i class="fas fa-shield-alt"></i>Verificados</span>';
    echo '</div>';
    echo '</section>';

    // Contexto SEO oculto para AdSense
    echo '<div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">';
    echo '<p>Búsqueda de códigos descuento ' . htmlspecialchars($termino) . ', cupones ' . htmlspecialchars($termino) . ', ofertas ' . htmlspecialchars($termino) . ', promociones ' . htmlspecialchars($termino) . '</p>';
    echo '</div>';

    // AdSense Top — sólo render si hay anuncio real
    if (!empty($termino) && function_exists('get_adsense_search')) {
        $ad_top = get_adsense_search($termino, null, 'top');
        if (trim($ad_top) !== '') {
            echo '<div class="adsense-search-top" style="text-align:center;margin:20px 0;padding:20px;background:#fafafa;border-radius:12px;border:1px solid #eee;">' . $ad_top . '</div>';
        }
    }

    
    if (!empty($codigos_destacados)) {
        $slider_title = 'Códigos destacados para "' . $termino . '"';
        $slider_subtitle = 'Estos códigos destacados coinciden con tu búsqueda';
        echo '<div class="search-featured-results">';
        echo generate_featured_codes_slider($codigos_destacados, true, $slider_title, $slider_subtitle);
        echo '</div>';
    }

    if (!empty($codigos_generales)) {
        $subtitle = !empty($codigos_destacados)
            ? 'Más códigos que coinciden con tu búsqueda'
            : 'Códigos encontrados para tu búsqueda';
        echo '<h3 class="section-subtitle">' . htmlspecialchars($subtitle) . '</h3>';
        echo '<div class="codes-grid">';
        echo generate_modern_code_cards($codigos_generales);
        echo '</div>';
        
        // AdSense Bottom — sólo si hay anuncio real
        if (!empty($termino) && function_exists('get_adsense_search')) {
            $ad_bottom = get_adsense_search($termino, null, 'bottom');
            if (trim($ad_bottom) !== '') {
                echo '<div class="adsense-search-bottom" style="text-align:center;margin:30px 0;padding:20px;background:#fafafa;border-radius:12px;border:1px solid #eee;">';
                echo '<h4 style="color:#333;margin-bottom:15px;font-size:15px;font-weight:600;">Anuncios relacionados con <strong style="color:#E30613;">' . htmlspecialchars($termino) . '</strong></h4>';
                echo $ad_bottom;
                echo '</div>';
            }
        }
    }

    if (empty($codigos_destacados) && empty($codigos_generales)) {
        echo '<div class="no-results-card" style="text-align:center;padding:3rem 1.5rem;background:#fff;border:1px solid #eee;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,0.04);margin:2rem 0;">';
        echo '<div style="width:80px;height:80px;margin:0 auto 1.2rem;background:linear-gradient(135deg,#fff5f5,#ffeaea);border-radius:50%;display:flex;align-items:center;justify-content:center;">';
        echo '<i class="fas fa-search" style="font-size:2rem;color:#E30613;"></i>';
        echo '</div>';
        echo '<h3 style="color:#1a1a1a;margin:0 0 .5rem;font-weight:700;">No se encontraron códigos para "' . htmlspecialchars($termino) . '"</h3>';
        echo '<p style="color:#666;margin:0 0 1.8rem;">Prueba con otros términos o sé el primero en publicar uno.</p>';
        echo '<a href="/nuevo_codigo?marca=' . urlencode($termino) . '" style="background:linear-gradient(135deg,#E30613,#ff6b35);color:#fff;padding:14px 32px;border-radius:999px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 15px rgba(227,6,19,0.3);">';
        echo '<i class="fas fa-plus-circle"></i> Publicar código de ' . htmlspecialchars($termino);
        echo '</a>';
        echo '<ul style="list-style:none;padding:0;margin:2rem auto 0;max-width:420px;text-align:left;color:#666;font-size:.9rem;">';
        echo '<li style="padding:.3rem 0;"><i class="fas fa-check" style="color:#28a745;margin-right:8px;"></i>Revisa la ortografía</li>';
        echo '<li style="padding:.3rem 0;"><i class="fas fa-check" style="color:#28a745;margin-right:8px;"></i>Usa palabras más generales</li>';
        echo '<li style="padding:.3rem 0;"><i class="fas fa-check" style="color:#28a745;margin-right:8px;"></i>Prueba sinónimos o el nombre de la marca</li>';
        echo '</ul>';
        echo '</div>';

        // AdSense no-results — sólo si hay anuncios reales
        if (!empty($termino) && function_exists('get_adsense_search')) {
            $ad_nr_mid = get_adsense_search($termino, null, 'no-results-middle');
            if (trim($ad_nr_mid) !== '') {
                echo '<div style="text-align:center;margin:30px 0;padding:20px;background:#fafafa;border-radius:12px;border:1px solid #eee;">';
                echo '<h4 style="color:#333;font-size:15px;font-weight:600;margin-bottom:12px;">Anuncios relacionados con <strong style="color:#E30613;">' . htmlspecialchars($termino) . '</strong></h4>';
                echo $ad_nr_mid;
                echo '</div>';
            }
            $ad_nr_bot = get_adsense_search($termino, null, 'no-results-bottom');
            if (trim($ad_nr_bot) !== '') {
                echo '<div style="text-align:center;margin:30px 0;padding:20px;background:#fafafa;border-radius:12px;border:1px solid #eee;">' . $ad_nr_bot . '</div>';
            }
        }
    }
    
    // Mostrar chollos encontrados
    
    echo '</div>';
    echo '</div>';
    
    // Sección de búsquedas relacionadas
    if (function_exists('get_related_searches')) {
        $related_searches = get_related_searches($termino, 10);
        
        if (!empty($related_searches)) {
            echo '<div class="related-searches-section animate-on-scroll" style="margin-top: 4rem; padding: 4rem 0;">';
            echo '<div class="container">';
            echo '<h3 class="section-title h2-style" style="text-align:center; margin-bottom: 30px; color:#fff;">También te puede interesar</h3>';
            
            echo '<div class="related-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px;">';
            foreach ($related_searches as $related) {
                $related_url = '/ofertas/' . urlencode($related['term']);
                echo '<a href="' . $related_url . '" class="related-chip glass-card" style="padding: 15px; text-decoration: none; border-radius: 12px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px;">';
                echo '<i class="fas fa-search" style="font-size: 0.8rem; color: #E30613;"></i>';
                echo '<span style="color: #ddd; font-weight: 500; font-size: 0.95rem;">' . htmlspecialchars($related['term']) . '</span>';
                echo '</a>';
            }
            echo '</div>'; // related-grid
            echo '</div>'; // container
            echo '</div>'; // related-searches-section
        }
    }

    // Interlacado de pie (Marcas y Categorías)
    echo '<div class="search-footer-interlinking" style="padding: 4rem 0; background: #fafbfc; border-top: 1px solid #e8e8ea;">';
    echo generate_popular_brands_section(9);
    echo generate_popular_categories_section();
    echo '</div>';

    // CSS adicional
    echo get_modern_additional_css();

    // Incluir footer
    get_footer();

    return $response;
});

// Ruta para AJAX actions
$app->post('/myphp/ajax_actions.php', function ($request, $response) {
    // Incluir el archivo de acciones AJAX
    include_once __DIR__ . '/myphp/ajax_actions.php';
    return $response;
});


// Nueva ruta para ver todos los últimos códigos
$app->get('/ultimos-codigos', function ($request, $response) {
    // Inicializar variables globales
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $GLOBALS['header_modern_used'] = true;

    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_utilidades.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    include_once __DIR__ . '/myphp/_footer.php';

    // Configuración de paginación
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $skip = ($page - 1) * $limit;

    // Filtros para códigos recientes
    $array_filtro = array(
        "estado" => 0
        // Se elimina el filtro regex ya que el orden por fecha_modificacion hace el trabajo
    );
    // Excluir códigos con fecha_validez expirada
    $array_filtro = array_merge($array_filtro, get_filtro_no_expirados());
    
    $sort_order = array('fecha_publicacion' => -1);
    
    $array_options = array(
        "limit" => $limit,
        "skip" => $skip,
        "sort" => $sort_order
    );

    // Obtener códigos
    $lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_options);
    $lista_codigos = isset($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];
    $total_codes = isset($lista_codigos_pre["total_number"]) ? $lista_codigos_pre["total_number"] : 0;

    // Renderizar cabecera
    get_header_modern(
        "Últimos Códigos Publicados - CodigoAmigo.com",
        "Explora todos los últimos códigos de descuento y ofertas publicadas por nuestra comunidad.",
        "Últimos Códigos",
        "Lista completa de códigos de descuento recientes",
        "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png"
    );

    echo '<div class="main-content">';
    echo '<div class="codes-section">';
    echo '<h1 class="section-title">Todos los Últimos Códigos</h1>';
    
    if (!empty($lista_codigos)) {
        echo '<div class="codes-grid">';
        echo generate_modern_code_cards($lista_codigos);
        echo '</div>';
        echo generate_modern_pagination($total_codes, $page, $limit);
    } else {
        echo '<p class="text-center">No hay códigos disponibles en este momento.</p>';
    }
    
    echo '</div>'; // codes-section
    echo '</div>'; // main-content

    get_footer();
    return $response;
});

// Ruta para el sistema de shorts (tipo TikTok)
// Chollos Shorts - Eliminado definitivamente (410 Gone) para ahorro de Crawl Budget
$app->get('/chollos-shorts', function ($request, $respon) {
    return $respon->withStatus(410)->write('Este contenido ha sido eliminado permanentemente.');
});

// Ruta de prueba
$app->get('/test-slim', function ($request, $response) {
    $response->getBody()->write("Slim está funcionando");
    return $response;
});

$app->get('/test-contacto-simple', function ($request, $response) {
    $response->getBody()->write("Página de contacto funcionando");
    return $response;
});

// Ruta para Google Login
$app->post('/google-login', function ($request, $response) {
    $data = $request->getParsedBody();
    
    if (!isset($data['credential'])) {
        $response->getBody()->write("No se recibió el credential de Google");
        return $response;
    }
    
    // Incluir funciones de usuario para Google login
    include_once __DIR__ . '/myphp/funciones_usuario.php';
    
    $datos = [
        'credential' => $data['credential']
    ];
    
    google_login($datos);
    return $response;
});

// Ruta para login tradicional
$app->post('/login', function ($request, $response) {
    $data = $request->getParsedBody();
    
    if (!isset($data['mail']) || !isset($data['pass'])) {
        $response->getBody()->write("Faltan datos de login");
        return $response;
    }
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_usuario.php';
    
    $datos = [
        'mail' => $data['mail'],
        'pass' => $data['pass']
    ];
    
    login_user($datos);
    return $response;
});

// Ruta para logout
$app->post('/logout', function ($request, $response) {
    // Limpiar sesión
    session_destroy();
    
    // Limpiar cookies de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    $response->getBody()->write("success");
    return $response;
});

// Ruta para páginas de marca y detalle de código
$app->get('/de-{marca}', function ($request, $response, $args) {
    $marca = $args['marca'];
    $codigo_id = $request->getQueryParam('codigo');

    // Incluir funciones de marca para manejo de redirecciones
    include_once __DIR__ . '/myphp/funciones_marca.php';

    // Verificar si hay una redirección para esta marca
    $redirect = get_brand_redirect($marca);
    if ($redirect && isset($redirect['new_brand_key'])) {
        // Crear URL completa para redirección
        $new_url = 'https://www.codigoamigo.com' . $redirect['redirect_url'];
        return $response->withRedirect($new_url, 301);
    }

    // Inicializar variables globales
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $GLOBALS['actual_url_limpia'] = 'https://www.codigoamigo.com/de-' . $marca;

    // Incluir archivos necesarios.
    // funciones.php va primero y aparte: la comprobación de marca de más abajo
    // solo necesita ese, y los siguientes (modern/adsense) emiten output al
    // cargarse — si se incluyeran antes del 404, el body saldría con restos.
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';

    // Marca inexistente -> 404 real.
    //
    // Hasta 2026-07-28 CUALQUIER /de-loquesea respondía 200 con título
    // generado ("Cupones descuento Asdfghjkl"), canonical auto-referente y
    // sin noindex: un generador infinito de soft-404. Google penaliza ese
    // patrón y gasta crawl budget en páginas vacías.
    //
    // Comprobado contra GSC antes de activarlo: de 601 URLs /de-* con datos
    // en 90 días, 599 tienen marca en BD; las 2 huérfanas se resolvieron
    // ('iqos iluma i' con un 301 a /de-iqos, 'nuevamarcatribbu' se deja caer).
    // Las redirecciones de marca se comprueban antes, así que siguen vivas.
    if (!getObjectMarca('nombre_clave', $marca)) {
        log_info('404 marca inexistente', ['marca' => $marca, 'ref' => $_SERVER['HTTP_REFERER'] ?? '']);
        return $response->withStatus(404)->withHeader('X-Robots-Tag', 'noindex');
    }

    include_once __DIR__ . '/myphp/funciones_utilidades.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';
    include_once __DIR__ . '/myphp/funciones_adsense.php';

    // Inicializar detector de móviles
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;

    // Incluir funciones modernas
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno

    // Incluir funciones de título de marca
    include_once __DIR__ . '/myphp/funciones_titulo_marca.php';
    
    if ($codigo_id) {
        // Mostrar detalle del código específico
        $codigo = null;
        try {
            $objId = new MongoDB\BSON\ObjectId($codigo_id);
            $array_filtro = array("estado" => 0, "_id" => $objId);
            $array_opciones = array('limit' => 1);
            $lista_codigos = get_all_listado_codigos_array($array_filtro, $array_opciones);
            $codigo = isset($lista_codigos["results"][0]) ? $lista_codigos["results"][0] : null;
        } catch (Exception $e) {
            $codigo = null;
        }
        
        if ($codigo) {
            // Convertir código a array si es objeto
            if (is_object($codigo)) {
                $codigo_arr = (array)$codigo;
            } else {
                $codigo_arr = $codigo;
            }
            
            // Asegurar que el código tenga _id
            if (!isset($codigo_arr['_id'])) {
                $codigo_arr['_id'] = $objId;
            }
            
            // Registrar impresión: al mostrar el código en la página de detalle (1 impresión)
            try {
                if (function_exists('añadir_impresion_codigo')) {
                    añadir_impresion_codigo($objId);
                }
            } catch (Exception $e) {
                // Silencioso pero registrar para debug si es necesario
            }
            
            // Registrar click: al entrar a la página de detalle se considera un click (1 click)
            try {
                if (function_exists('añadir_vista_codigo') && isset($codigo_arr['_id'])) {
                    añadir_vista_codigo($codigo_arr);
                    
                    // Registrar visita identificada si el usuario está logueado y no es el dueño
                    if (function_exists('addVista') && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                        // Evitar registrar visitas del propio dueño del código
                        $id_owner = isset($codigo_arr['id_usuario']) ? (string)$codigo_arr['id_usuario'] : '';
                        if ($_SESSION['user_id'] != $id_owner) {
                            addVista($codigo_arr);
                        }
                    }
                }
            } catch (Exception $e) {
                // Silencioso pero registrar para debug si es necesario
            }
            // Desactivar AdSense ANTES del header para que no se cargue el script
            // (los bloques in-feed/auto-ads se inyectan vía page-level cuando adsbygoogle.js está presente)
            $GLOBALS['anula_adsense'] = true;
            $anula_adsense = true;

            // Incluir el header moderno

            // Mes/año actual para frescura en título (mismo patrón que
            // generate_titulo_marca_mejorado en funciones_titulo_marca.php)
            $meses_es_detalle = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            $fecha_detalle_actual = new DateTime();
            $string_fecha_detalle = $meses_es_detalle[(int)$fecha_detalle_actual->format('n')] . ' ' . $fecha_detalle_actual->format('Y');

            // Llamar a la función del header moderno
            get_header_modern(
                "Código amigo " . ucfirst($marca) . " (verificado) " . $string_fecha_detalle . " - CodigoAmigo.com",
                "Usa este código amigo de " . ucfirst($marca) . " verificado por la comunidad. Ahorra hasta " . ($codigo_arr['num_beneficio'] ?? '') . "€ en tu Registro. Válido " . $string_fecha_detalle . ".",
                "Código amigo " . ucfirst($marca) . " Verificado " . $string_fecha_detalle,
                "Código amigo " . ucfirst($marca) . " (verificado) - ¡Ahorra ahora!",
                "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png"
            );
            
            // Asegurar que la sesión esté disponible antes de generar la página
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION)) {
                session_start();
            }
            
            // Guardar user_id en GLOBALS para que esté disponible en la función
            if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                $GLOBALS['current_user_id'] = $_SESSION['user_id'];
            }
            
            // Incluir la nueva función de detalle
            include_once __DIR__ . '/myphp/funciones_code_detail.php';

            // Generar página de detalle
            echo generate_code_detail_page($codigo);
        } else {
            // Incluir el header moderno
            
            // Código no encontrado
            get_header_modern(
                "Código no encontrado - CodigoAmigo.com",
                "El código solicitado no existe o ha expirado",
                "Código no encontrado",
                "Código no encontrado",
                "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png"
            );
            
            echo '<div class="main-content">';
            echo '<div style="text-align: center; color: #ccc; padding: 4rem 2rem;">';
            echo '<i class="fas fa-exclamation-triangle" style="font-size: 4rem; margin-bottom: 2rem; color: #E30613;"></i>';
            echo '<h2>Código no encontrado</h2>';
            echo '<p>El código que buscas no existe o ha expirado.</p>';
            echo '<a href="/de-' . $marca . '" class="btn_codigo_amigo" style="margin-top: 2rem; display: inline-block;" title="Códigos descuento ' . ucfirst($marca) . '">Ver todos los códigos de ' . ucfirst($marca) . '</a>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        // Mostrar listado de códigos de la marca
        $array_filtro = array("estado" => 0);
        $array_filtro = array_merge($array_filtro, array("marca" => $marca));
        // Excluir códigos con fecha_validez expirada
        $array_filtro = array_merge($array_filtro, get_filtro_no_expirados());

        // Aplicar filtro de fecha si está presente
        $fecha_filtro = $request->getQueryParam('fecha');
        if ($fecha_filtro) {
            $fecha_actual = new DateTime();
            
            switch ($fecha_filtro) {
                case 'hoy':
                    // Códigos publicados hoy
                    $fecha_inicio = clone $fecha_actual;
                    $fecha_inicio->setTime(0, 0, 0);
                    $fecha_fin = clone $fecha_actual;
                    $fecha_fin->setTime(23, 59, 59);
                    
                    $array_filtro['fecha_publicacion'] = array(
                        '$gte' => new MongoDB\BSON\UTCDateTime($fecha_inicio->getTimestamp() * 1000),
                        '$lte' => new MongoDB\BSON\UTCDateTime($fecha_fin->getTimestamp() * 1000)
                    );
                    break;
                    
                case 'semana':
                    // Códigos publicados la semana pasada
                    $fecha_inicio = clone $fecha_actual;
                    $fecha_inicio->modify('-1 week');
                    $fecha_inicio->setTime(0, 0, 0);
                    $fecha_fin = clone $fecha_actual;
                    $fecha_fin->modify('-1 day');
                    $fecha_fin->setTime(23, 59, 59);
                    
                    $array_filtro['fecha_publicacion'] = array(
                        '$gte' => new MongoDB\BSON\UTCDateTime($fecha_inicio->getTimestamp() * 1000),
                        '$lte' => new MongoDB\BSON\UTCDateTime($fecha_fin->getTimestamp() * 1000)
                    );
                    break;
                    
                case 'todo':
                default:
                    // Sin filtro de fecha (mostrar todos)
                    break;
            }
        }

        // Primero obtener códigos destacados de la marca (incluyendo destacados sociales)
        // Usar $gt (greater than) en lugar de $ne para asegurar que el campo existe y es mayor que 0
        $array_filtro_destacados = array_merge(
            $array_filtro,
            array(
                '$or' => array(
                    array('destacado' => array('$gt' => 0)),
                    array('destacado_social' => array('$gt' => 0))
                )
            )
        );
        $array_opciones_destacados = array(
            'limit' => 50, // Aumentar límite para asegurar que todos los destacados aparezcan
            'sort' => array('destacado' => -1, 'destacado_social' => -1, '_id' => -1) // En marcas, destacado prevalece sobre destacado_social
        );
        
        $lista_codigos_destacados = get_all_listado_codigos_array($array_filtro_destacados, $array_opciones_destacados);
        $codigos_destacados = isset($lista_codigos_destacados["results"]) ? $lista_codigos_destacados["results"] : [];
        
        // Debug: Verificar ordenamiento de códigos destacados
        if (!empty($codigos_destacados) && isset($_GET['debug_destacados'])) {
            log_info("DEBUG DESTACADOS - Total: " . count($codigos_destacados));
            foreach ($codigos_destacados as $idx => $cod) {
                $destacado_val = isset($cod['destacado']) ? $cod['destacado'] : 'NO';
                $destacado_social_val = isset($cod['destacado_social']) ? $cod['destacado_social'] : 'NO';
                log_info("  [$idx] ID: " . (string)$cod['_id'] . " | destacado: $destacado_val | destacado_social: $destacado_social_val");
            }
        }
        
        // Luego obtener códigos normales (excluyendo los destacados)
        // Nota: No filtramos por visibilidad aquí, todos los códigos activos deben aparecer
        // Usar $or para excluir códigos que tengan destacado > 0 o destacado_social > 0
        $array_filtro_normales = array_merge(
            $array_filtro, 
            array(
                '$and' => array(
                    array('$or' => array(
                        array('destacado' => array('$exists' => false)),
                        array('destacado' => 0),
                        array('destacado' => array('$lte' => 0))
                    )),
                    array('$or' => array(
                        array('destacado_social' => array('$exists' => false)),
                        array('destacado_social' => 0),
                        array('destacado_social' => array('$lte' => 0))
                    ))
                )
            )
        );
        $array_opciones_normales = array(
            'limit' => 50, // Aumentado para mostrar más códigos
            'sort' => array('_id' => -1) // Ordenar por ID descendente (más recientes primero)
        );

        // Obtener el total de códigos normales para la paginación
        $total_codigos_normales = count_all_listado_codigos_array($array_filtro_normales);
        $total_codigos_destacados = count($codigos_destacados);
        $total_codigos = $total_codigos_destacados + $total_codigos_normales;
        
        // Configurar paginación: 9 códigos por página
        $items_per_page = 9;
        $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        
        // Calcular cuántos códigos mostrar
        $codigos_destacados_a_mostrar = [];
        $codigos_normales_a_mostrar = [];
        
        if ($current_page == 1) {
            // En la primera página, mostrar todos los destacados + los normales necesarios para completar 9
            $codigos_destacados_a_mostrar = $codigos_destacados;
            $espacios_disponibles = max(0, $items_per_page - count($codigos_destacados));
            if ($espacios_disponibles > 0) {
                $array_opciones_normales['limit'] = $espacios_disponibles;
                $array_opciones_normales['skip'] = 0;
                $lista_codigos_normales = get_all_listado_codigos_array($array_filtro_normales, $array_opciones_normales);
                $codigos_normales_a_mostrar = isset($lista_codigos_normales["results"]) ? $lista_codigos_normales["results"] : [];
            }
        } else {
            // En páginas siguientes, calcular skip considerando los destacados que ya se mostraron
            $skip_normales = ($current_page - 1) * $items_per_page - $total_codigos_destacados;
            if ($skip_normales < 0) {
                $skip_normales = 0;
            }
            $array_opciones_normales['limit'] = $items_per_page;
            $array_opciones_normales['skip'] = $skip_normales;
            $lista_codigos_normales = get_all_listado_codigos_array($array_filtro_normales, $array_opciones_normales);
            $codigos_normales_a_mostrar = isset($lista_codigos_normales["results"]) ? $lista_codigos_normales["results"] : [];
        }
        
        // Combinar códigos destacados primero, luego normales
        $codigos = array_merge($codigos_destacados_a_mostrar, $codigos_normales_a_mostrar);
        
        // Obtener información de la marca
        $marca_info = get_brand_info($marca);
        
        // Si get_brand_info no devuelve la estructura esperada, crear una básica
        if (!$marca_info || !isset($marca_info['nombre'])) {
            $marca_info = [
                'nombre' => ucfirst($marca),
                'nombre_clave' => $marca,
                'categoria' => 'general'
            ];
        }
        
        $numero_codigos = $total_codigos; // Total para la paginación

        // Ficha sin códigos activos -> noindex,follow.
        //
        // El 404 de arriba solo cubre marcas que no existen en BD. Una marca que
        // SÍ existe pero se ha quedado sin códigos vigentes seguía devolviendo 200
        // indexable, con description "0 códigos verificados válidos para <mes>":
        // el mismo soft-404 por otra puerta (38 fichas así a 2026-08-07).
        //
        // Es reversible solo: en cuanto alguien publica un código para la marca,
        // $total_codigos deja de ser 0 y la ficha vuelve al índice sin tocar nada.
        // Se deja follow para no cortar el flujo de enlaces del silo de marcas.
        //
        // Solo meta robots, no X-Robots-Tag: a esta altura de la ruta los includes
        // de _header_modern/adsense ya han emitido output, así que un withHeader()
        // aquí llegaría tarde y provocaría "headers already sent".
        if ($total_codigos === 0) {
            $GLOBALS['noindex'] = 1;
        }

        // Generar título y descripción mejorados
        $titulo_mejorado = generate_titulo_marca_mejorado($marca_info, $codigos);
        $descripcion_mejorada = generate_descripcion_marca_mejorada($marca_info, $codigos, $numero_codigos);

        // Incluir el header moderno
        
        // Llamar a la función del header moderno
        get_header_modern(
            $titulo_mejorado . " - CodigoAmigo.com",
            $descripcion_mejorada,
            $titulo_mejorado,
            $descripcion_mejorada,
            "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png"
        );

        // Incluir funciones auxiliares para la página de marca
        include_once __DIR__ . '/myphp/funciones_marca_home.php';
        
        // Generar contenido principal con diseño moderno específico de marca
        include_once __DIR__ . '/public/marca_detalle.php';
    }
    
    // CSS adicional
    echo get_modern_additional_css();

    // Adjuntar footer moderno si corresponde
    $response = add_footer_to_modern_routes($response);

    return $response;
});


// Rutas de autenticación
$app->get('/login', function ($request, $response, $args) {
    global $author_web, $noindex;
    
    $noindex = 1;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_usuario.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Capturar parámetro de redirección
    $redirect_path = $_GET['redirect'] ?? '';
    $autologin_token = $_GET['autologin'] ?? '';

    // Manejar autologin si está presente y el usuario no está logueado
    if (!empty($autologin_token) && (!isset($_SESSION["user_id"]) || $_SESSION["user_id"] == "")) {
        try {
            $collection_usuarios = getCollectionUsuarios();
            $usuario = $collection_usuarios->findOne(['autologin_token' => $autologin_token]);
            
            if ($usuario) {
                // Iniciar sesión
                $id_object = $usuario["_id"];
                $_SESSION["user_id"] = ((string) new MongoDB\BSON\ObjectId($id_object));
                $_SESSION["mail"] = $usuario["mail"];
                $_SESSION["username"] = $usuario["username"];


                // Eliminar token para que sea de un solo uso
                $collection_usuarios->updateOne(
                    ['_id' => $id_object],
                    ['$unset' => ['autologin_token' => '']]
                );
            }
        } catch (Throwable $e) {
            log_error("Error in autologin: " . $e->getMessage());
        }
    }
    
    // Verificar si el usuario ya está logueado
    if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] != "") {
        $final_redirect = !empty($redirect_path) ? $redirect_path : 'https://www.codigoamigo.com';
        echo "<script> window.location.href = '$final_redirect' </script>";
        exit;
    }
    
    // Almacenar el redirect en localStorage si existe, para que los handlers de login (Google, etc) lo usen
    if (!empty($redirect_path)) {
        echo "<script> localStorage.setItem('redirectAfterLogin', " . json_encode($redirect_path) . "); </script>";
    }
    
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    $title = "Iniciar sesión en " . $author;
    $description = "Accede a tu cuenta para gestionar tus códigos amigo";
    
    // Mostrar página de login unificada
    get_header_modern($title, $description);
    ?>
    
    <style>
    /* Estilos para la página de login unificada */
    .login-page-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 60px 0;
    }
    
    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 40px;
        max-width: 450px;
        margin: 0 auto;
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .login-header h1 {
        color: #333;
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .login-header p {
        color: #666;
        font-size: 1.1rem;
        margin: 0;
    }
    
    .login-form .form-group {
        margin-bottom: 20px;
    }
    
    .login-form .form-control {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 15px 20px;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    
    .login-form .form-control:focus {
        border-color: #E30613;
        box-shadow: 0 0 0 0.2rem rgba(227, 6, 19, 0.25);
    }
    
    .btn-login {
        background: #E30613;
        border: none;
        border-radius: 10px;
        padding: 15px 30px;
        font-size: 18px;
        font-weight: 600;
        color: white;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(227, 6, 19, 0.3);
    }
    
    .btn-login:hover {
        background: #C40510;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(227, 6, 19, 0.4);
    }
    
    .login-links {
        text-align: center;
        margin-top: 30px;
    }
    
    .login-links a {
        color: #E30613;
        text-decoration: none;
        font-weight: 600;
        margin: 0 10px;
    }
    
    .login-links a:hover {
        color: #C40510;
        text-decoration: underline;
    }
    
    .google-login-btn {
        background: #4285f4;
        border: none;
        border-radius: 10px;
        padding: 15px 30px;
        font-size: 16px;
        font-weight: 600;
        color: white;
        width: 100%;
        margin-top: 15px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .google-login-btn:hover {
        background: #3367d6;
        transform: translateY(-2px);
    }
    
    .divider {
        text-align: center;
        margin: 25px 0;
        position: relative;
    }
    
    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #e9ecef;
    }
    
    .divider span {
        background: white;
        padding: 0 20px;
        color: #666;
        font-size: 14px;
    }
    </style>
    
    <div class="login-page-container">
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <div class="login-card">
                        <div class="login-header">
                            <h1>Iniciar Sesión</h1>
                            <p>Accede a tu cuenta para gestionar tus códigos amigo</p>
                        </div>
                        
                        <form role="form" id="login" method="post">
                            <div class="form-group">
                                <input type="email" class="form-control" name="mail_login" id="mail_login" placeholder="Correo electrónico" required>
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" name="pass_login" id="pass_login" placeholder="Contraseña" required>
                            </div>
                            <button type="submit" class="btn btn-login">Iniciar Sesión</button>
                        </form>
                        
                        <div class="divider">
                            <span>o</span>
                        </div>
                        
                        <button class="google-login-btn" id="google-login-btn">
                            <i class="fab fa-google"></i>
                            Continuar con Google
                        </button>
                        
                        <div class="login-links">
                            <a href="registro">¿No tienes cuenta? Regístrate aquí</a><br>
                            <a href="cambiar_password">¿Olvidaste tu contraseña?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    $(document).ready(function() {

        // Manejar login con Google (mismo código que el modal)
        $(document).on('click', '#google-login-btn', function() {
            console.log('[GoogleLogin] Botón Google clicado');
            // Cargar Google Identity Services
            if (typeof google === 'undefined') {
                console.warn('[GoogleLogin] google undefined, cargando script GSI');
                $.getScript('https://accounts.google.com/gsi/client', function() {
                    console.log('[GoogleLogin] Script GSI cargado, inicializando');
                    initializeGoogleLogin();
                });
            } else {
                console.log('[GoogleLogin] Google ya disponible, inicializando');
                initializeGoogleLogin();
            }
        });
        
        function initializeGoogleLogin() {
            console.log('[GoogleLogin] initializeGoogleLogin');
            google.accounts.id.initialize({
                client_id: '298004995594-1qm1qpjok7lq4lo1kdd45406j9rarcqd.apps.googleusercontent.com',
                callback: handleGoogleResponse
            });
            
            console.log('[GoogleLogin] Lanzando prompt');
            google.accounts.id.prompt();
        }
        
        function handleGoogleResponse(response) {
            // Google response recibida
            console.log('[GoogleLogin] handleGoogleResponse', response);

            $.ajax({
                type: "POST",
                url: "/api/login.php",
                data: {
                    metodo: "google_login",
                    credential: response.credential
                },
                cache: false
            }).done(function(data) {
                // Respuesta del servidor Google login recibida
                console.log('Respuesta Google login:', data);

                var parsedResponse = null;

                if (data === null || typeof data === 'undefined') {
                    parsedResponse = null;
                } else if (typeof data === 'string') {
                    var trimmed = data.trim();
                    if (trimmed) {
                        try {
                            parsedResponse = JSON.parse(trimmed);
                        } catch (e) {
                            parsedResponse = null;
                        }
                    }
                } else {
                    parsedResponse = data;
                }

                if (parsedResponse && parsedResponse.success && parsedResponse.user) {
                    console.log('[GoogleLogin] Éxito, aplicando sesión', parsedResponse.user);
                    if (typeof window.applyUserSession === 'function') {
                        window.applyUserSession(parsedResponse.user);
                    }

                    if (typeof updateMenuSession === 'function') {
                        updateMenuSession();
                    }

                    var redirectUrl = localStorage.getItem('redirectAfterLogin');
                    if (redirectUrl && redirectUrl !== window.location.href) {
                        console.log('Redirigiendo a:', redirectUrl);
                        localStorage.removeItem('redirectAfterLogin');
                        window.location.href = redirectUrl;
                    } else {
                        console.log('[GoogleLogin] Recargando página');
                        location.reload();
                    }
                } else if (parsedResponse && parsedResponse.error) {
                    console.error('[GoogleLogin] Error recibido del backend:', parsedResponse.error);
                    alert('Error en el login con Google: ' + parsedResponse.error);
                } else {
                    console.error('[GoogleLogin] Respuesta inesperada del backend:', parsedResponse);
                    alert('No se pudo completar el login con Google. Inténtalo de nuevo.');
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                // Error en Google login
                console.error('[GoogleLogin] AJAX fail', textStatus, errorThrown, jqXHR);
                alert('Error al procesar el login con Google. Inténtalo de nuevo.');
            });
        }
    });
    </script>
    <?php
    
    return $response;
});

$app->get('/registro', function ($request, $response, $args) {
    global $author_web, $noindex;
    
    $noindex = 1;
    
    if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] != "") {
        echo "<script> window.location.href = 'https://www.codigoamigo.com' </script>";
    }
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_utilidades.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    $title = "Registrate en la web para acceder a cientos de códigos amigo y códigos descuento";
    $description = "Miles de códigos amigo te esperan en " . $author_web;
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/registro.php';
    
    return $response;
});

$app->get('/usuario', function ($request, $response, $args) {
    global $data_usuario, $author_web;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_utilidades.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Validar sesión de usuario
    validateUserSession();
    
    // Obtener datos del usuario si no están disponibles
    if (!isset($data_usuario) || empty($data_usuario)) {
        $data_usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
    }
    
    $username = isset($data_usuario["username"]) ? $data_usuario["username"] : "Usuario";
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    
    $title = "Panel del usuario de " . $username;
    $description = "Miembro de la comunidad de " . $author;
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/usuario.php';
    
    return $response;
});

$app->get('/chat', function ($request, $response, $args) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/chat_usuario.php';
    return $response;
});

$app->get('/chat-usuario', function ($request, $response, $args) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/chat_usuario.php';
    return $response;
});

$app->get('/perfil', function ($request, $response, $args) {
    global $data_usuario, $author_web;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Validar sesión de usuario
    validateUserSession();
    
    // Obtener datos del usuario si no están disponibles
    if (!isset($data_usuario) || empty($data_usuario)) {
        $data_usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
    }
    
    $username = isset($data_usuario["username"]) ? $data_usuario["username"] : "Usuario";
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    
    $title = "Editar perfil - " . $username;
    $description = "Edita tu perfil en " . $author;
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/usuario.php';
    
    return $response;
});

$app->get('/invitar-amigos', function ($request, $response, $args) {
    global $data_usuario, $author_web;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Validar sesión de usuario
    validateUserSession();
    
    // Obtener datos del usuario si no están disponibles
    if (!isset($data_usuario) || empty($data_usuario)) {
        $data_usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
    }
    
    $username = isset($data_usuario["username"]) ? $data_usuario["username"] : "Usuario";
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    
    $title = "Invita a tus amigos y gana dinero - Código Amigo";
    $description = "Invita a tus amigos a Código Amigo y gana 5€ por cada amigo que se registre y verifique su perfil. ¡Comparte tu código de referido!";
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/invitar-amigos.php';
    
    return $response;
});

$app->get('/mis-anuncios', function ($request, $response, $args) {
    global $data_usuario, $author_web;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Verificar que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Asegurar que la sesión esté activa
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Debug temporal - remover en producción

    // Validar sesión de usuario
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || $_SESSION["user_id"] == "") {
        // log_debug("Redirigiendo al login - user_id no válido", ['route' => 'mis-anuncios']);
        header("Location: https://www.codigoamigo.com/login");
        exit;
    }
    
    // Verificar que el username esté en la sesión
    if (!isset($_SESSION["username"]) || empty($_SESSION["username"])) {
        // Intentar obtener el username de la base de datos
        try {
            $usuario_temp = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
            if ($usuario_temp && isset($usuario_temp["username"])) {
                $_SESSION["username"] = $usuario_temp["username"];
            } else {
                // Si no se puede obtener el username, redirigir al login
                header("Location: https://www.codigoamigo.com/login");
                exit;
            }
        } catch (Exception $e) {
            // Si hay error al obtener el usuario, redirigir al login
            header("Location: https://www.codigoamigo.com/login");
            exit;
        }
    }
    
    // Obtener datos del usuario si no están disponibles
    if (!isset($data_usuario) || empty($data_usuario)) {
        $data_usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
    }
    
    $username = isset($data_usuario["username"]) ? $data_usuario["username"] : "Usuario";
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    
    $title = "Mis anuncios - " . $username;
    $description = "Gestiona tus códigos y anuncios en " . $author;
    
    // Desactivar AdSense en la página de mis anuncios
    $anula_adsense = true;
    
    // IMPORTANTE: Procesar destacado ANTES de obtener los códigos para que aparezcan actualizados
    // Fallback: si venimos de un pago por saldo/stripe y hay señal de éxito en la URL,
    // aseguramos el disparo de notificaciones de destacado (idempotente con guardado en sesión)
    try {
        if (isset($_GET['success']) && $_GET['success'] === 'destacado' && isset($_GET['codigo'])) {
            $codigo_id_qs = $_GET['codigo'];
            $tipo_qs = isset($_GET['tipo']) && in_array($_GET['tipo'], ['normal','super']) ? $_GET['tipo'] : 'normal';

            if (!isset($_SESSION['last_destacado_notify']) || $_SESSION['last_destacado_notify'] !== $codigo_id_qs) {
                include_once __DIR__ . '/myphp/funciones.php';
                if (function_exists('destacar_codigo_moderno')) {
                    $resultado = destacar_codigo_moderno($codigo_id_qs, $tipo_qs);
                    if ($resultado) {
                        log_info("Código destacado exitosamente: $codigo_id_qs, tipo: $tipo_qs");
                    } else {
                        log_error("Error al destacar código: $codigo_id_qs");
                    }
                }
                $_SESSION['last_destacado_notify'] = $codigo_id_qs;
            }
        }
    } catch (Exception $e) {
        log_error("Error procesando destacado en app_with_mongo: " . $e->getMessage());
    }
    
    // ========================================================================
    // PAGINACIÓN + TABS (24 por página, filtro por estado vía ?estado=...)
    // ========================================================================
    $user_oid = new MongoDB\BSON\ObjectId($_SESSION["user_id"]);
    $collection_codigos = getCollectionCodigos();

    // Tabs admitidos
    $estado_tab = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
    $estados_validos = ['todos', 'activos', 'caducados', 'desactivados', 'inactivos', 'destacados'];
    if (!in_array($estado_tab, $estados_validos, true)) {
        $estado_tab = 'todos';
    }

    // Contadores por estado — un count() por categoría (cheap con índice por id_usuario)
    $base_filter = ['id_usuario' => $user_oid];
    $total_codigos_usuario = $collection_codigos->count($base_filter);
    $num_activos      = $collection_codigos->count(array_merge($base_filter, ['$or' => [['estado' => 0], ['estado' => ['$exists' => false]]]]));
    $num_caducados    = $collection_codigos->count(array_merge($base_filter, ['estado' => -3]));
    $num_desactivados = $collection_codigos->count(array_merge($base_filter, ['estado' => -2]));
    $num_inactivos    = $collection_codigos->count(array_merge($base_filter, ['estado' => -1]));
    $num_destacados   = $collection_codigos->count(array_merge($base_filter, [
        '$and' => [
            ['$or' => [['estado' => 0], ['estado' => ['$exists' => false]]]],
            ['destacado' => ['$gt' => 0]],
        ]
    ]));

    // Filtro según tab
    $array_filtro = $base_filter;
    switch ($estado_tab) {
        case 'activos':
            $array_filtro['$or'] = [['estado' => 0], ['estado' => ['$exists' => false]]];
            break;
        case 'caducados':
            $array_filtro['estado'] = -3;
            break;
        case 'desactivados':
            $array_filtro['estado'] = -2;
            break;
        case 'inactivos':
            $array_filtro['estado'] = -1;
            break;
        case 'destacados':
            $array_filtro['$and'] = [
                ['$or' => [['estado' => 0], ['estado' => ['$exists' => false]]]],
                ['destacado' => ['$gt' => 0]],
            ];
            break;
        // 'todos' → sin filtro adicional
    }

    // Paginación
    $por_pagina = 24;
    $pagina = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $skip = ($pagina - 1) * $por_pagina;

    $total_filtrado = $collection_codigos->count($array_filtro);
    $total_paginas = max(1, (int)ceil($total_filtrado / $por_pagina));
    if ($pagina > $total_paginas) {
        $pagina = $total_paginas;
        $skip = ($pagina - 1) * $por_pagina;
    }

    // Orden: estado desc (null/0 primero → activos arriba, caducados/desactivados al final),
    // luego destacado desc (destacados arriba dentro de activos), luego fecha desc.
    $cursor_codigos = $collection_codigos->find($array_filtro, [
        'sort'  => ['estado' => -1, 'destacado' => -1, 'fecha_publicacion' => -1],
        'skip'  => $skip,
        'limit' => $por_pagina,
    ]);
    $listado_codigos = iterator_to_array($cursor_codigos);
    $num_codigos = count($listado_codigos);

    // Compat: variables esperadas por el template legacy
    $mostrando_todos = true;
    $codigos_ocultos = 0;
    $num_sin_estado = 0;

    // Visibilidad — calcular sólo para los 24 visibles (no N+1 sobre miles)
    $num_1_codes = 0;
    $num_2_codes = 0;
    $num_3_codes = 0;
    foreach ($listado_codigos as $codigo) {
        $estado_codigo = isset($codigo['estado']) ? (int)$codigo['estado'] : null;
        $es_activo = ($estado_codigo === 0 || $estado_codigo === null);
        if ($es_activo && isset($codigo['marca']) && $codigo['marca'] !== null) {
            $marca_obj = getObjectMarca('nombre_clave', $codigo['marca']);
            if ($marca_obj) {
                $posicion = get_posicion_codigo_en_marca($codigo['_id'], $codigo['marca']);
                if ($posicion == 1) $num_1_codes++;
                elseif ($posicion == 2) $num_2_codes++;
                else $num_3_codes++;
            }
        }
    }
    
    // Incluir la página específica de mis anuncios
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/mis_anuncios.php';
    
    return $response;
});

// ============================================================================
// LANDING /promociones-activas — campañas referido tiempo limitado
// ============================================================================
$app->get('/promociones-activas', function ($request, $response, $args) {
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_marca.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true;

    if (!isset($detect)) { $detect = new Mobile_Detect(); }
    $GLOBALS['detect'] = $detect;

    $promos = getMarcasConPromocionActiva();

    get_header_modern(
        'Promociones activas — bonos de referido por tiempo limitado',
        'Marcas con campañas activas de referido: bonos extra por compartir tu código. Aprovecha antes de que acabe.'
    );
    ?>
    <main style="max-width:1100px;margin:30px auto;padding:0 20px;">
      <header style="text-align:center;margin-bottom:30px;">
        <h1 style="margin:0 0 10px;font-size:2rem;font-weight:800;color:#1a1a2e;">🔥 Promociones activas ahora</h1>
        <p style="color:#666;font-size:1.05rem;max-width:680px;margin:0 auto;">Marcas con bonos de referido extra por tiempo limitado. Comparte tu código antes de que acabe y multiplica tus ganancias.</p>
      </header>

      <?php if (empty($promos)): ?>
        <div style="background:#fff;border-radius:14px;padding:50px;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.06);">
          <div style="font-size:3rem;margin-bottom:14px;">⏳</div>
          <h2 style="margin:0 0 12px;color:#333;">Sin promociones activas ahora mismo</h2>
          <p style="color:#666;">Vuelve pronto — solemos publicar campañas de N26, Revolut, Vinted y más.</p>
          <a href="/" style="display:inline-block;margin-top:18px;background:#E30613;color:#fff;padding:12px 26px;border-radius:25px;font-weight:700;text-decoration:none;">Volver a la home</a>
        </div>
      <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;">
          <?php foreach ($promos as $p): ?>
            <article style="background:#fff;border-radius:14px;padding:22px;box-shadow:0 4px 14px rgba(0,0,0,0.06);border-left:6px solid <?php echo $p['dias_restantes'] <= 3 ? '#E30613' : '#FF9800'; ?>;display:flex;flex-direction:column;gap:12px;">
              <div style="display:flex;align-items:center;gap:14px;">
                <?php if (!empty($p['logo'])): ?>
                  <img src="<?php echo htmlspecialchars($p['logo']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" style="width:54px;height:54px;border-radius:12px;object-fit:contain;background:#f5f5f5;padding:6px;" loading="lazy">
                <?php else: ?>
                  <div style="width:54px;height:54px;border-radius:12px;background:linear-gradient(135deg,#FF6B35,#E30613);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.4rem;"><?php echo strtoupper(substr($p['nombre'], 0, 1)); ?></div>
                <?php endif; ?>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:1.15rem;font-weight:800;color:#1a1a2e;"><?php echo htmlspecialchars($p['nombre']); ?></div>
                  <?php if (!empty($p['bono'])): ?>
                    <div style="font-size:0.95rem;color:#E30613;font-weight:700;">💰 <?php echo htmlspecialchars($p['bono']); ?></div>
                  <?php endif; ?>
                </div>
              </div>

              <?php if (!empty($p['titulo'])): ?>
                <p style="margin:0;color:#444;font-size:0.95rem;line-height:1.5;"><?php echo htmlspecialchars($p['titulo']); ?></p>
              <?php endif; ?>

              <div style="display:flex;align-items:center;gap:8px;font-size:0.85rem;">
                <?php if ($p['dias_restantes'] === 0): ?>
                  <span style="background:#E30613;color:#fff;padding:5px 12px;border-radius:20px;font-weight:800;">⚡ Acaba HOY</span>
                <?php elseif ($p['dias_restantes'] <= 3): ?>
                  <span style="background:#E30613;color:#fff;padding:5px 12px;border-radius:20px;font-weight:800;">⏳ <?php echo $p['dias_restantes']; ?> día<?php echo $p['dias_restantes'] === 1 ? '' : 's'; ?></span>
                <?php else: ?>
                  <span style="background:#FFF3E0;color:#E65100;padding:5px 12px;border-radius:20px;font-weight:700;">⏳ <?php echo $p['dias_restantes']; ?> días</span>
                <?php endif; ?>
                <span style="color:#999;">hasta <?php echo date('d/m/Y', strtotime($p['fecha_fin'])); ?></span>
              </div>

              <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;">
                <a href="/de-<?php echo htmlspecialchars($p['nombre_clave']); ?>" style="flex:1;text-align:center;background:#E30613;color:#fff;padding:10px 14px;border-radius:10px;font-weight:700;text-decoration:none;font-size:0.9rem;">Ver códigos <?php echo htmlspecialchars($p['nombre']); ?></a>
                <a href="/nuevo_codigo" style="text-align:center;background:#f5f5f5;color:#333;padding:10px 14px;border-radius:10px;font-weight:700;text-decoration:none;font-size:0.9rem;">+ Publicar el tuyo</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <section style="margin-top:40px;background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;padding:30px;border-radius:16px;text-align:center;">
          <h2 style="margin:0 0 12px;font-size:1.4rem;">¿Tienes código de alguna de estas marcas?</h2>
          <p style="margin:0 0 18px;opacity:0.85;">Publícalo ahora. Durante la promoción cobras el bono extra cuando alguien lo use.</p>
          <a href="/nuevo_codigo" style="display:inline-block;background:#FFD700;color:#1a1a2e;padding:14px 30px;border-radius:30px;font-weight:800;text-decoration:none;">Publicar mi código →</a>
        </section>
      <?php endif; ?>
    </main>
    <?php
    return $response;
});

// Ruta para URLs de Afiliados
$app->get('/afiliados', function ($request, $response, $args) {
    global $data_usuario, $author_web;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Configurar variables globales
    $author_web = "CODIGOAMIGO.COM";
    $GLOBALS['author_web'] = $author_web;
    $GLOBALS['name_page'] = "Mis URLs de Afiliados";
    $GLOBALS['show_adsense'] = 0; // Deshabilitar ads en página de usuario
    
    // Incluir la página de afiliados
    include_once __DIR__ . '/afiliados.php';
    
    return $response;
});

// Ruta para la página de felicidades de recarga de saldo
$app->get('/felicidades_recarga', function ($request, $response, $args) {
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Verificar que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Asegurar que la sesión esté activa
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Incluir el archivo de felicidades_recarga
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/felicidades_recarga.php';
    
    return $response;
});

$app->get('/logout', function ($request, $response, $args) {
    session_destroy();
    echo "<script>
        localStorage.setItem('user_session_changed', Date.now());
        if (typeof updateMenuSession === 'function') updateMenuSession();
        window.location.href = 'https://www.codigoamigo.com';
    </script>";

    return $response;
});

// Página de felicitaciones por publicar código
$app->get('/codigo-publicado', function ($request, $response, $args) {
    // Verificar que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Incluir el archivo de felicitaciones
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/codigo_publicado.php';

    return $response;
});

// Rutas de listados y páginas principales
$app->get('/listado', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 1;
    
    $title = "Listado de códigos amigo y códigos descuento";
    $description = "Todos los códigos amigo y códigos descuento disponibles en " . (isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo");
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/listado.php';
    
    return $response;
});

$app->get('/ficha/{id}', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 1;
    
    $id = $args['id'];
    $title = "Ficha del código amigo";
    $description = "Detalles del código amigo";
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/ficha.php';
    
    return $response;
});

// Rutas de páginas informativas
$app->get('/preguntas-frecuentes', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 0; // Permitir indexación
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/preguntas_frecuentes.php';
    
    return $response;
});

$app->get('/blog', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 0; // Permitir indexación
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/blog.php';
    
    return $response;
});

// ─── Baja de correo en un clic (sin login) ───
//
// GET  = la persona pincha el enlace del pie del correo.
// POST = el proveedor (Gmail, Yahoo...) ejecuta la baja por su cuenta cuando el
//        usuario pulsa "Cancelar suscripción" en su bandeja: es el one-click de
//        la RFC 8058, y sin él los correos de volumen se filtran a spam.
//
// El enlace va firmado con HMAC, así que no hace falta sesión y nadie puede dar
// de baja a otra persona cambiando el parámetro de la URL.
$baja_email_handler = function ($request, $response, $args) {
    global $noindex;
    $noindex = 1;

    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_baja_email.php';

    $email = (string)($request->getQueryParam('e') ?? ($request->getParsedBody()['e'] ?? ''));
    $token = (string)($request->getQueryParam('t') ?? ($request->getParsedBody()['t'] ?? ''));

    $ok = $email !== '' && baja_email_token_valido($email, $token);
    if ($ok) {
        registrar_baja_email($email, $request->getMethod() === 'POST' ? 'one-click' : 'enlace');
    } else {
        log_info('[baja_email] intento con firma inválida', ['email' => $email]);
    }

    // El one-click del proveedor no enseña nada a nadie: solo espera un 200.
    if ($request->getMethod() === 'POST') {
        return $response->withStatus($ok ? 200 : 400)->withHeader('Content-Type', 'text/plain');
    }

    $titulo = $ok ? 'Baja confirmada' : 'Enlace no válido';
    $mensaje = $ok
        ? 'Hemos dado de baja a <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong>. No volverás a recibir nuestros correos.'
        : 'Este enlace de baja no es válido o ha caducado. Escríbenos y lo hacemos a mano.';

    $response->getBody()->write(
        '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="robots" content="noindex"><title>' . $titulo . ' - Código Amigo</title></head>'
        . '<body style="margin:0;background:#f4f7fa;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">'
        . '<div style="max-width:520px;margin:60px auto;background:#fff;border-radius:12px;padding:40px 32px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.05);">'
        . '<img src="https://www.codigoamigo.com/img/logo_codigoamigo.png" alt="Código Amigo" style="max-width:160px;margin-bottom:24px;">'
        . '<h1 style="font-size:22px;color:#222;margin:0 0 16px;">' . $titulo . '</h1>'
        . '<p style="color:#555;line-height:1.6;margin:0 0 28px;">' . $mensaje . '</p>'
        . '<a href="https://www.codigoamigo.com" style="background:#E30613;color:#fff;padding:14px 30px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Volver a Código Amigo</a>'
        . '</div></body></html>'
    );

    return $response->withStatus($ok ? 200 : 400)->withHeader('Content-Type', 'text/html; charset=UTF-8');
};
$app->get('/baja', $baja_email_handler);
$app->post('/baja', $baja_email_handler);

// URL antigua del embudo VIP. No existía como ruta, así que caía en el catch-all
// y terminaba en la home: quien pulsaba "hazte VIP" desde el chat o desde la
// ficha de marca acababa en la portada sin entender qué había pasado. Los
// enlaces del código ya apuntan a /public/mis_viewers.php; esto cubre los que
// puedan seguir vivos en correos enviados o enlaces externos.
$app->get('/suscripciones_y_creditos', function ($request, $response, $args) {
    return $response->withRedirect('/public/mis_viewers.php', 301);
});

// Rutas de páginas legales
$app->get('/politica-de-privacidad', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 1;
    
    $title = "Política de privacidad";
    $description = "Política de privacidad de " . (isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo");
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/politica_de_privacidad.php';
    
    return $response;
});

$app->get('/politica-de-cookies', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 1;
    
    $title = "Política de cookies";
    $description = "Política de cookies de " . (isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo");
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/politica_de_cookies.php';
    
    return $response;
});

$app->get('/aviso-legal', function ($request, $response, $args) {
    global $noindex;

    $noindex = 1;

    $title = "Aviso legal";
    $description = "Aviso legal de " . (isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo");

    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/aviso_legal.php';

    return $response;
});

// Rutas de contacto - redirigir a archivos independientes
$app->get('/contacto', function ($request, $response, $args) {
    // Incluir archivos necesarios para mantener consistencia
    include_once __DIR__ . '/inc/includes.php';

    // Redirigir a archivo independiente
    include_once $_SERVER['DOCUMENT_ROOT'] . '/contacto_independiente.php';
    return $response;
});

$app->get('/contacto_empresa', function ($request, $response, $args) {
    // Incluir archivos necesarios para mantener consistencia
    include_once __DIR__ . '/inc/includes.php';

    // Redirigir a archivo independiente
    include_once $_SERVER['DOCUMENT_ROOT'] . '/contacto_empresa_independiente.php';
    return $response;
});

// Rutas de cambio de contraseña
$app->get('/cambiar_password', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 1;
    
    // Incluir archivos necesarios para get_header_modern()
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    $title = "Cambiar contraseña";
    $description = "Recupera la contraseña de tu usuario de " . $author;
    include __DIR__ . '/public/cambiar_password.php';
    
    return $response;
});

$app->post('/cambio_password', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 1;
    
    // Incluir archivos necesarios para getObjectUser() y funciones de correo
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/inc/funciones.php';  // Para enviarMailRecuerdoPass
    include_once __DIR__ . '/myphp/funciones_mail.php';  // Para enviar_mail_activacion
    
    $mail_ = filter_input(INPUT_POST, "mail", FILTER_SANITIZE_EMAIL);
    $pass_ = filter_input(INPUT_POST, "pass_login", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    return include __DIR__ . '/php/cambio_password.php';
    
    return $response;
});

$app->get('/nuevo_password', function ($request, $response, $args) {
    global $noindex;
    
    $noindex = 1;
    
    // Incluir archivos necesarios para get_header_modern()
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    $title = "Cambio de contraseña";
    $description = "Cambia la contraseña de tu usuario de " . $author;
    /* filter_input(INPUT_GET, "codigo", FILTER_SANITIZE_FULL_SPECIAL_CHARS) es el correo encriptado del usuario que quiere confirmar su mail.*/
    $mail_encriptado = filter_input(INPUT_GET, "codigo", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    /* Hay errores en la desencriptación al encontrar espacios y convertirlos en +. Por eso,
     * corregimos estos + por espacios para que la desencriptación sea correcta. */
    $cadena_encriptada_correcta = str_replace(" ", "+", $mail_encriptado);
    /* Desencriptamos la cadena para poder trabajar con el correo del usuario */
    $cadena_desencriptada = desencriptar($cadena_encriptada_correcta);
    
    include __DIR__ . '/public/nuevo_password.php';
    
    return $response;
});

$app->post('/actualizar_usuario', function ($request, $response, $args) {
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    
    $new_password = filter_input(INPUT_POST, "nueva_password", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $new_confirm_password = filter_input(INPUT_POST, "nueva_confirm_password", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $mail = filter_input(INPUT_POST, "mail", FILTER_SANITIZE_EMAIL);
    include __DIR__ . '/php/actualizar_usuario.php';
    
    return $response;
});

// Rutas de categorías
$app->get('/categoria', function ($request, $response, $args) {
    $categoria = getObjectCategoria('nombre_clave', filter_input(INPUT_GET, "categoria", FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $title = "Códigos de amigo de " . $categoria["nombre"];
    $description = $categoria["descripcion"] . " - " . (isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo");
    include __DIR__ . '/public/categoria.php';
    
    return $response;
});

$app->get('/listado_categorias', function ($request, $response, $args) {
    return $response->withRedirect("https://www.codigoamigo.com/listado-categorias", 301);
});

$app->get('/listado-categorias', function ($request, $response, $args) {
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    
    $title = "Categorías de códigos de descuento y cupones | CodigoAmigo";
    $description = "Todas las categorías de códigos de descuento: alimentación, viajes, banca, telefonía, cursos y más. Encuentra cupones y códigos amigo verificados para ahorrar.";
    $title_social = $title;
    $description_social = $description;
    $imagen_social = "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png";
    
    include __DIR__ . '/public/listado_categorias.php';
    
    return $response;
});

$app->get('/{categoria}-comparte-y-gana', function ($request, $response, $args) {
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    $categoria_url = $args['categoria'];

    
    
    // Verificar si es una categoría válida
    $categorias_validas = [
        'alimentacion-y-gastronomia',
        'banca-y-criptomonedas',
        'deportes-y-nutricion',
        'cursos',
        'club-de-compras',
        'telefonia-y-comunicaciones',
        'vehiculos-y-movilidad',
        'apuestas',
        'herramientas',
        'viajes-y-alojamiento',
        'seguros',
        'suministros-y-servicios',
        'mascota',
        'mensajeria',
        'inteligencia-artificial',
        'plataformas-y-suscripciones',
        'ocio-y-entretenimiento',
        'select',
        // Categorías antiguas mantenidas por compatibilidad
        'tecnologia-y-electronica',
        'moda-y-belleza',
        'hogar-y-jardin',
        'deportes-y-ocio',
        'viajes-y-turismo',
        'finanzas-y-seguros'
    ];
    
    if(!in_array($categoria_url, $categorias_validas)) {
        // Si no es una categoría válida, redirigir a la página principal
        return $response->withRedirect('/', 301);
    }
    
    // Obtener información de la categoría
    $categoria_info = get_category_info($categoria_url . '-comparte-y-gana');
    $nombre_categoria = $categoria_info['nombre'];
    $descripcion_categoria = $categoria_info['descripcion'];
    
    // Obtener marcas de esta categoría
    $marcas_categoria = get_brands_by_category($categoria_url . '-comparte-y-gana');
    
    // Construir prev/next SEO links para paginación de marcas
    $per_page = 24;
    $total_brands = is_array($marcas_categoria) ? count($marcas_categoria) : 0;
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $total_pages = $per_page > 0 ? max(1, (int)ceil($total_brands / $per_page)) : 1;
    $qs_params = $_GET ?? [];
    unset($qs_params['page']);
    $buildUrl = function($page) use ($qs_params) {
        $params = $qs_params;
        if ($page > 1) { $params['page'] = $page; }
        $qs = http_build_query($params);
        $suffix = $qs ? ('?' . $qs) : '';
        return $suffix;
    };
    $base_path = '/' . $categoria_url . '-comparte-y-gana';
    $links_meta_html = '';
    if ($current_page > 1) {
        $links_meta_html .= '<link rel="prev" href="' . $base_path . $buildUrl($current_page - 1) . '" />';
    }
    if ($current_page < $total_pages) {
        $links_meta_html .= '<link rel="next" href="' . $base_path . $buildUrl($current_page + 1) . '" />';
    }
    $links_meta = [ 'schema' => $links_meta_html ];

    // Llamar a la función del header moderno
    get_header_modern(
        "Códigos de descuento " . $nombre_categoria . " - CodigoAmigo.com",
        "Encuentra los mejores códigos de descuento en " . $nombre_categoria . " verificados y actualizados diariamente",
        "Códigos de descuento " . $nombre_categoria,
        "Códigos de descuento " . $nombre_categoria,
        "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png",
        $links_meta
    );

    // Generar contenido de la página de categoría
    echo generate_category_page_layout($categoria_url . '-comparte-y-gana', $nombre_categoria, $descripcion_categoria, $marcas_categoria);

    // Contenido SEO (intro rico + FAQs + schema FAQPage) — convierte la página
    // de categoría de thin content a cuerpo real para competir por head terms.
    echo render_categoria_seo($categoria_url);

    // CSS adicional
    echo get_modern_additional_css();
    
    // Footer
    get_footer();
    
    return $response;
});

// Rutas de códigos
$app->get('/nuevo_codigo', function ($request, $response, $args) {
    global $author_web, $show_adsense, $name_page, $data_usuario;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    include_once __DIR__ . '/myphp/herramientas/var_globals.php';
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Validar sesión de usuario
    validateUserSession();
    
    // Obtener datos del usuario si no están disponibles
    if (!isset($data_usuario) || empty($data_usuario)) {
        $data_usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
    }
    
    $username = isset($data_usuario["username"]) ? $data_usuario["username"] : "Usuario";
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    
    $show_adsense = 0;
    $title = "Insertar nuevo código - " . $username;
    $description = "Únete a la comunidad de " . $author;
    $name_page = "nuevo_codigo";
    
    // Incluir la página específica de publicar código
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/publicar_codigo.php';
    
    return $response;
});

$app->get('/modificar_codigo/{codigo_id}', function ($request, $response, $args) {
    global $author_web, $show_adsense, $name_page, $detect;
    
    // Incluir archivos necesarios
    include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/includes.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($GLOBALS['detect'])) {
        $GLOBALS['detect'] = new Mobile_Detect;
    }
    
    if (!isset($_SESSION["user_id"])) { 
        header("Location: /login"); 
        exit; 
    }
    
    // Obtener datos del código
    $codigo_id = $args['codigo_id'];
    $codigo = getObjectCodigo($codigo_id);
    
    if (!$codigo || $codigo['id_usuario'] != $_SESSION["user_id"]) {
        header("Location: /mis-anuncios");
        exit;
    }
    
    // Obtener información completa de la marca
    $marca_info = null;
    if (isset($codigo['marca'])) {
        $marca_info = getObjectMarca('nombre_clave', $codigo['marca']);
    }
    
    // Preparar datos para el formulario de publicación
    $codigo_data = [
        'marca' => $marca_info ? $marca_info['nombre'] : ($codigo['marca'] ?? ''), // Usar nombre completo de la marca
        'marca_clave' => $codigo['marca'] ?? '', // Mantener también el nombre_clave para referencia
        'num_beneficio' => $codigo['num_beneficio'] ?? '',
        'descuento' => $codigo['codigo_descuento'] ?? '',
        'codigo' => $codigo['codigo'] ?? '',
        'descripcion' => $codigo['descripcion'] ?? '',
        'provincia' => $codigo['provincia'] ?? '',
        'localidad' => $codigo['localidad'] ?? '',
        'fecha_caducidad' => $codigo['fecha_validez'] ?? '',
        'codigo_id' => $codigo_id
    ];
    
    $show_adsense = 0;
    $title = "Modificar Código";
    $description = "Modifica el código existente en " . $author_web;
    $name_page = "modificar_codigo";
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/publicar_codigo.php';
    
    return $response;
});

$app->post('/modificar_codigo/{codigo_id}', function ($request, $response, $args) {
    global $author_web, $show_adsense, $name_page, $detect;
    
    // Incluir archivos necesarios
    include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/includes.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($GLOBALS['detect'])) {
        $GLOBALS['detect'] = new Mobile_Detect;
    }
    
    if (!isset($_SESSION["user_id"])) { 
        header("Location: /login"); 
        exit; 
    }
    
    // Obtener datos del código
    $codigo_id = $args['codigo_id'];
    $codigo = getObjectCodigo($codigo_id);
    
    if (!$codigo || $codigo['id_usuario'] != $_SESSION["user_id"]) {
        header("Location: /mis-anuncios");
        exit;
    }
    
    // Procesar formulario de modificación
    $marca = $_POST['marca'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $num_beneficio = $_POST['num_beneficio'] ?? '';
    $codigo_descuento = $_POST['descuento'] ?? '';
    $codigo_url = $_POST['codigo'] ?? '';
    $provincia = $_POST['provincia'] ?? '';
    $localidad = $_POST['localidad'] ?? '';
    $fecha_caducidad = $_POST['fecha_caducidad'] ?? '';
    
    if (!empty($marca) && !empty($descripcion) && !empty($num_beneficio) && !empty($codigo_url)) {
        // Actualizar el código en la base de datos
        $update_data = [
            'marca' => $marca,
            'descripcion' => $descripcion,
            'num_beneficio' => $num_beneficio,
            'codigo_descuento' => $codigo_descuento,
            'codigo' => $codigo_url,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'fecha_validez' => $fecha_caducidad,
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ];
        
        $result = updateCodigo($codigo_id, $update_data);
        
        /* 
        // Procesamiento de PDF temporalmente deshabilitado - pendiente de arreglar
        // Procesar PDF si se subió uno
        if (isset($_FILES['pdf_retencion']) && $_FILES['pdf_retencion']['error'] === UPLOAD_ERR_OK) {
            include_once __DIR__ . '/myphp/funciones_pdf.php';
            
            $pdf_file = $_FILES['pdf_retencion'];
            
            // Validar que sea PDF
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $pdf_file['tmp_name']);
            finfo_close($finfo);
            
            if ($mime_type === 'application/pdf') {
                // Directorio para guardar las imágenes del PDF
                $uploads_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/pdfs';
                
                // Mover el PDF a un directorio temporal
                $pdf_temp_path = $uploads_dir . '/temp_' . $codigo_id . '.pdf';
                if (!is_dir($uploads_dir)) {
                    mkdir($uploads_dir, 0755, true);
                }
                
                if (move_uploaded_file($pdf_file['tmp_name'], $pdf_temp_path)) {
                    // Directorio para las imágenes
                    $imagenes_dir = $uploads_dir . '/' . $codigo_id;
                    
                    // Procesar PDF y convertir a imágenes
                    $paginas = procesarPDF($pdf_temp_path, $imagenes_dir, $codigo_id);
                    
                    if ($paginas && is_array($paginas) && count($paginas) > 0) {
                        // Guardar las páginas en MongoDB
                        guardarPaginasPDF($codigo_id, $paginas);
                    }
                    
                    // Eliminar PDF temporal
                    if (file_exists($pdf_temp_path)) {
                        @unlink($pdf_temp_path);
                    }
                }
            }
        }
        */
        
        if ($result) {
            $_SESSION['msg_success'] = '¡Código modificado exitosamente! 🎉';
            header("Location: /mis-anuncios");
            exit;
        } else {
            $_SESSION['msg_error'] = 'Error al actualizar el código. Inténtalo de nuevo.';
            header("Location: /modificar_codigo/" . $codigo_id);
            exit;
        }
    } else {
        $_SESSION['msg_error'] = 'Todos los campos obligatorios son requeridos.';
        header("Location: /modificar_codigo/" . $codigo_id);
        exit;
    }
    
    // Preparar datos para el formulario de publicación (con errores)
    $codigo_data = [
        'marca' => $codigo['marca'] ?? '',
        'num_beneficio' => $num_beneficio ?: $codigo['num_beneficio'] ?? '',
        'descuento' => $codigo_descuento ?: $codigo['codigo_descuento'] ?? '',
        'codigo' => $codigo_url ?: $codigo['codigo'] ?? '',
        'descripcion' => $descripcion ?: $codigo['descripcion'] ?? '',
        'provincia' => $provincia ?: $codigo['provincia'] ?? '',
        'localidad' => $localidad ?: $codigo['localidad'] ?? '',
        'fecha_caducidad' => $fecha_caducidad ?: $codigo['fecha_validez'] ?? '',
        'codigo_id' => $codigo_id
    ];
    
    $show_adsense = 0;
    $title = "Modificar Código";
    $description = "Modifica el código existente en " . $author_web;
    $name_page = "modificar_codigo";
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/publicar_codigo.php';
    
    return $response;
});

$app->post('/codigo_insertado', function ($request, $response, $args) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/php/codigo_insertado.php';
    
    return $response;
});

// Rutas de listados de marcas
$app->get('/listado_marcas', function ($request, $response, $args) {
    return $response->withRedirect("https://www.codigoamigo.com/listado-marcas", 301);
});

// El menú (header_base, header_chollos, _header_mobile_new, funciones_modern)
// enlaza a /marcas, que no tenía ruta y caía en la home (soft-404)
$app->get('/marcas', function ($request, $response, $args) {
    return $response->withRedirect("https://www.codigoamigo.com/listado-marcas", 301);
});

$app->get('/listado-marcas', function ($request, $response, $args) {
    global $author_web, $show_adsense, $name_page, $detect;
    include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/includes.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';
    
    // Inicializar detector de móviles DESPUÉS de incluir includes.php
    if (!isset($GLOBALS['detect'])) {
        $GLOBALS['detect'] = new Mobile_Detect;
    }
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    $title = "Todas las marcas con códigos de descuento y referido | CodigoAmigo";
    $description = "Explora todas las marcas con códigos de descuento, cupones y códigos amigo verificados por la comunidad. Encuentra tu marca y ahorra en tu próxima compra.";
    include __DIR__ . '/public/listado_marcas.php';
    
    return $response;
});

// Rutas de páginas especiales
$app->get('/bienvenido_de_nuevo', function ($request, $response, $args) {
    global $author_web;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/inc/funciones.php';
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($GLOBALS['detect'])) {
        $GLOBALS['detect'] = new Mobile_Detect();
    }
    
    $title = "Bienvenido a Código Amigo";
    $description = "La única web donde puedes compartir tus códigos - " . $author_web;
    
    // Obtener el código de activación
    $codigo_recibido = isset($_GET['codigo']) ? htmlspecialchars($_GET['codigo'], ENT_QUOTES, 'UTF-8') : null;
    
    if (!$codigo_recibido) {
        echo "Error: Código de activación no proporcionado";
        return $response;
    }

    // Corregir espacios por + para la desencriptación
    $cadena_encriptada_correcta = str_replace(" ", "+", $codigo_recibido);
    
    // Desencriptar el código para obtener el email del usuario
    $email_usuario = desencriptar($cadena_encriptada_correcta);
    
    if (!$email_usuario) {
        echo "Error: Código de activación inválido o expirado";
        return $response;
    }

    // Verificar estado del usuario y activar si es necesario
    try {
        $collection_usuarios = getCollectionUsuarios();
        $usuario = $collection_usuarios->findOne(['mail' => $email_usuario]);
        
        if ($usuario) {
            if ($usuario['estado'] == 1) {
                // Usuario ya estaba activado, mostrar página de bienvenida
                include_once __DIR__ . '/public/bienvenido_de_nuevo.php';
            } else {
                // Usuario no activado, intentar activarlo
                $resultado_activacion = activeUserToLogin($usuario['_id']);
                if ($resultado_activacion) {
                    // Usuario activado correctamente, mostrar página de bienvenida
                    include_once __DIR__ . '/public/bienvenido_de_nuevo.php';
                } else {
                    echo "Error: No se pudo activar el usuario";
                    return $response;
                }
            }
        } else {
            echo "Error: Usuario no encontrado";
            return $response;
        }
        
    } catch (Exception $e) {
        log_error("Error activando usuario", ['error' => $e->getMessage(), 'user_id' => $user_id]);
        echo "Error: No se pudo activar el usuario. Inténtalo de nuevo.";
        return $response;
    }
    
    return $response;
});

// Ruta para reenviar email de activación
$app->post('/reenviar-activacion', function ($request, $response, $args) {
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/inc/funciones.php';
    
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        echo "error";
        return $response;
    }
    
    try {
        $collection_usuarios = getCollectionUsuarios();
        $usuario = $collection_usuarios->findOne(['mail' => $email]);
        
        if (!$usuario) {
            echo "not_found";
            return $response;
        }
        
        // Verificar si ya está activado
        if ($usuario['estado'] == 1) {
            echo "already_verified";
            return $response;
        }
        
        // Reenviar email de activación
        $datos_usuario = [
            'mail' => $usuario['mail'],
            'username' => $usuario['username']
        ];
        $resultado = enviarMailActivacion($datos_usuario);
        
        if ($resultado) {
            echo "success";
        } else {
            echo "error";
        }
        
    } catch (Exception $e) {
        log_error("Error reenviando email de activación", ['error' => $e->getMessage(), 'user_id' => $user_id]);
        echo "error";
    }
    
    return $response;
});

$app->get('/felicidades', function ($request, $response, $args) {
    global $author_web;
    
    $title = "¡Felicidades!";
    $description = "¡Bienvenido a la comunidad de " . $author_web . "!";
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/felicidades.php';
    
    return $response;
});

$app->get('/felicidades_splash', function ($request, $response, $args) {
    global $author_web;
    
    $title = "¡Felicidades!";
    $description = "¡Bienvenido a la comunidad de " . $author_web . "!";
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/felicidades_splash.php';
    
    return $response;
});

$app->get('/felicidades_destacar', function ($request, $response, $args) {
    global $author_web;
    
    $title = "¡Código destacado exitosamente!";
    $description = "Tu código ha sido destacado correctamente en " . $author_web;

    // Incluir archivos necesarios (getCodeByID, getObjectMarca, get_header_new, get_footer)
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';

    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/felicidades_destacar.php';
    
    return $response;
});

$app->get('/destaca', function ($request, $response, $args) {
    global $author_web;

    $title = "Destaca tu código";
    $description = "Haz que tu código destaque en " . $author_web;

    // Incluir archivos necesarios (getCodeByID, getObjectMarca, get_header_new, get_footer)
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/funciones_modern.php';

    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/destaca.php';
    
    return $response;
});

$app->get('/estadisticas', function ($request, $response, $args) {
    global $author_web;
    
    $title = "Estadísticas";
    $description = "Estadísticas de " . $author_web;
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/estadisticas.php';
    
    return $response;
});

$app->get('/mis-favoritos', function ($request, $response, $args) {
    global $data_usuario, $author_web;
    
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Configurar variables globales
    $author_web = "CODIGOAMIGO.COM";
    $GLOBALS['author_web'] = $author_web;
    $GLOBALS['name_page'] = "Mis Favoritos";
    $GLOBALS['show_adsense'] = 0; // Deshabilitar ads en página de usuario
    
    // Verificar que el usuario esté logueado
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
        header("Location: /login");
        exit;
    }
    
    // Incluir la página de favoritos
    include_once __DIR__ . '/public/mis_favoritos.php';
    
    return $response;
});

// Rutas AJAX - Soporte para CORS preflight
$app->options('/ajax_actions', function ($request, $response, $args) {
    return $response->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
});

$app->options('/ajax/', function ($request, $response, $args) {
    return $response->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
});

$app->post('/ajax_actions', function ($request, $response, $args) {
    // Incluir funciones necesarias para votos y otras acciones
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones_codigo.php';
    
    // Obtener datos del body si vienen como JSON
    $parsedBody = $request->getParsedBody();
    $postData = !empty($parsedBody) ? $parsedBody : $_POST;
    
    $action = $postData['metodo'] ?? $postData['action'] ?? '';
    
    // Configurar respuesta JSON
    $response = $response->withHeader('Content-Type', 'application/json')
                          ->withHeader('Access-Control-Allow-Origin', '*');
    
    if ($action === 'votar_codigo') {
        if (function_exists('votar_codigo')) {
            $result = votar_codigo($postData);
            return $response->write(json_encode($result));
        }
    }
    
    // Sistema de favoritos
    if ($action === 'añadir_favorito' || $action === 'eliminar_favorito') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
            return $response->write(json_encode(['success' => false, 'message' => 'Debes iniciar sesión']));
        }
        
        include_once __DIR__ . '/myphp/funciones_favoritos.php';
        
        $usuario_id = $_SESSION["user_id"];
        $codigo_id = $postData['codigo_id'] ?? '';
        $tipo = $postData['tipo'] ?? 'codigo';
        
        if (empty($codigo_id)) {
            return $response->write(json_encode(['success' => false, 'message' => 'ID no válido']));
        }
        
        try {
            if ($action === 'añadir_favorito') {
                $result = añadir_favorito($usuario_id, $codigo_id, $tipo);
            } else {
                $result = eliminar_favorito($usuario_id, $codigo_id, $tipo);
            }
            
            return $response->write(json_encode($result));
        } catch (Exception $e) {
            log_error('Error en favoritos: ' . $e->getMessage());
            return $response->write(json_encode(['success' => false, 'message' => 'Error al procesar la solicitud']));
        }
    }
    
    // Mantener compatibilidad con código antiguo
    if (function_exists('votar_codigo') && !empty($postData)) {
        votar_codigo($postData);
    }
    
    return $response->write(json_encode(['success' => false, 'message' => 'Acción no reconocida']));
});

// También permitir GET para debugging (aunque debería ser POST)
$app->get('/ajax_actions', function ($request, $response, $args) {
    return $response->withHeader('Content-Type', 'application/json')
                    ->write(json_encode(['success' => false, 'message' => 'Esta ruta requiere método POST']));
});

// Manejar /ajax sin barra final (critico para llamadas desde JS)
$app->post('/ajax', function ($request, $response, $args) {
    // Incluir funciones necesarias para votos y otras acciones
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones_codigo.php';
    
    $parsedBody = $request->getParsedBody();
    $postData = !empty($parsedBody) ? $parsedBody : $_POST;
    
    $action = $postData['metodo'] ?? $postData['action'] ?? '';
    
    $response = $response->withHeader('Content-Type', 'application/json')
                          ->withHeader('Access-Control-Allow-Origin', '*');
    
    if ($action === 'votar_codigo') {
        if (function_exists('votar_codigo')) {
            $result = votar_codigo($postData);
            return $response->write(json_encode($result));
        }
    }
    
    if ($action === 'añadir_favorito' || $action === 'eliminar_favorito') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
            return $response->write(json_encode(['success' => false, 'message' => 'Debes iniciar sesión']));
        }
        
        include_once __DIR__ . '/myphp/funciones_favoritos.php';
        
        $usuario_id = $_SESSION["user_id"];
        $codigo_id = $postData['codigo_id'] ?? '';
        
        if (empty($codigo_id)) {
            return $response->write(json_encode(['success' => false, 'message' => 'ID de código no válido']));
        }
        
        try {
            if ($action === 'añadir_favorito') {
                $result = añadir_favorito($usuario_id, $codigo_id);
            } else {
                $result = eliminar_favorito($usuario_id, $codigo_id);
            }
            
            return $response->write(json_encode($result));
        } catch (Exception $e) {
            log_error('Error en favoritos: ' . $e->getMessage());
            return $response->write(json_encode(['success' => false, 'message' => 'Error al procesar la solicitud']));
        }
    }
    
    if (function_exists('votar_codigo') && !empty($postData)) {
        votar_codigo($postData);
    }
    
    return $response->write(json_encode(['success' => false, 'message' => 'Acción no reconocida']));
});

// Manejar /ajax/ con barra final también
$app->post('/ajax/', function ($request, $response, $args) {
    // Incluir funciones necesarias para votos y otras acciones
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones_codigo.php';
    
    $parsedBody = $request->getParsedBody();
    $postData = !empty($parsedBody) ? $parsedBody : $_POST;
    
    $action = $postData['metodo'] ?? $postData['action'] ?? '';
    
    $response = $response->withHeader('Content-Type', 'application/json')
                          ->withHeader('Access-Control-Allow-Origin', '*');
    
    if ($action === 'votar_codigo') {
        if (function_exists('votar_codigo')) {
            $result = votar_codigo($postData);
            return $response->write(json_encode($result));
        }
    }
    
    if ($action === 'añadir_favorito' || $action === 'eliminar_favorito') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
            return $response->write(json_encode(['success' => false, 'message' => 'Debes iniciar sesión']));
        }
        
        include_once __DIR__ . '/myphp/funciones_favoritos.php';
        
        $usuario_id = $_SESSION["user_id"];
        $codigo_id = $postData['codigo_id'] ?? '';
        
        if (empty($codigo_id)) {
            return $response->write(json_encode(['success' => false, 'message' => 'ID de código no válido']));
        }
        
        try {
            if ($action === 'añadir_favorito') {
                $result = añadir_favorito($usuario_id, $codigo_id);
            } else {
                $result = eliminar_favorito($usuario_id, $codigo_id);
            }
            
            return $response->write(json_encode($result));
        } catch (Exception $e) {
            log_error('Error en favoritos: ' . $e->getMessage());
            return $response->write(json_encode(['success' => false, 'message' => 'Error al procesar la solicitud']));
        }
    }
    
    if (function_exists('votar_codigo') && !empty($postData)) {
        votar_codigo($postData);
    }
    
    return $response->write(json_encode(['success' => false, 'message' => 'Acción no reconocida']));
});

$app->get('/ajax/', function ($request, $response, $args) {
    return $response->withHeader('Content-Type', 'application/json')
                    ->write(json_encode(['success' => false, 'message' => 'Esta ruta requiere método POST']));
});

$app->post('/list_elements_bd', function ($request, $response, $args) {
    include __DIR__ . '/php/list_elements_bd.php';
    
    return $response;
});

$app->post('/comprobar_existe_codigo', function ($request, $response, $args) {
    include __DIR__ . '/php/comprobar_existe_codigo.php';
    
    return $response;
});

$app->post('/cargar_localidades', function ($request, $response, $args) {
    include __DIR__ . '/php/cargar_localidades.php';
    
    return $response;
});

// Rutas de gestión de usuario
$app->post('/remove_photo_user', function ($request, $response, $args) {
    include __DIR__ . '/php/remove_photo_user.php';
    
    return $response;
});

$app->post('/cambiar_foto_usuario', function ($request, $response, $args) {
    include __DIR__ . '/php/cambiar_foto_usuario.php';
    
    return $response;
});

// Rutas de API
$app->get('/api_final', function ($request, $response, $args) {
    $title = "API de Código Amigo";
    $description = "API para desarrolladores de " . (isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo");
    
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/api_final.php';
    
    return $response;
});

$app->post('/api_final/verify', function ($request, $response, $args) {
    debug_log('API /verify called');
    debug_log('Request headers:', getallheaders());
    debug_log('Request body:', file_get_contents('php://input'));

    try {
        $data = $request->getParsedBody();
        debug_log('Parsed request data:', $data);

        if (!$data) {
            debug_log('No data received in request');
            return $response->withJson([
                'error' => 'No data received'
            ], 400);
        }

        $phone = $data['phone'] ?? '';
        debug_log('Phone number:', $phone);

        if (!$phone) {
            debug_log('Phone number missing');
            return $response->withJson([
                'error' => 'Phone number is required'
            ], 400);
        }

        debug_log('Looking up user with phone:', $phone);
        $usuario = getObjectUser('telefono', $phone);
        debug_log('User lookup result:', $usuario);

        if ($usuario) {
            return $response->withJson([
                'success' => true,
                'user_id' => (string) $usuario['_id'],
                'username' => $usuario['username']
            ]);
        }

        return $response->withJson([
            'error' => 'User not found'
        ], 404);

    } catch (Exception $e) {
        debug_log('Exception in /verify:', $e);
        return $response->withJson([
            'error' => 'Internal server error'
        ], 500);
    }
});

$app->get('/api_final/codes', function ($request, $response, $args) {
    try {
        $collection_codigos = getCollectionCodigos();
        $codigos = $collection_codigos->find([], ['limit' => 50])->toArray();
        
        $result = [];
        foreach ($codigos as $codigo) {
            $result[] = [
                'id' => (string) $codigo['_id'],
                'titulo' => $codigo['titulo'] ?? '',
                'descripcion' => $codigo['descripcion'] ?? '',
                'marca' => $codigo['marca'] ?? '',
                'beneficio' => $codigo['beneficio'] ?? 0
            ];
        }
        
        return $response->withJson([
            'success' => true,
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        return $response->withJson([
            'error' => 'Internal server error'
        ], 500);
    }
});

$app->post('/api_final/codes', function ($request, $response, $args) {
    try {
        $data = $request->getParsedBody();
        
        if (!$data || !isset($data['titulo']) || !isset($data['descripcion'])) {
            return $response->withJson([
                'error' => 'Missing required fields'
            ], 400);
        }
        
        $collection_codigos = getCollectionCodigos();
        $result = $collection_codigos->insertOne([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'marca' => $data['marca'] ?? '',
            'beneficio' => $data['beneficio'] ?? 0,
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_publicacion' => date('Y-m-d H:i:s'),
            'estado' => 0,
            'destacado' => 0,
            'destacado_social' => 0,
            'clicks' => 0,
            'totalclicks' => 0,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'fecha_modificacion' => date('Y-m-d H:i:s'),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ]);
        
        return $response->withJson([
            'success' => true,
            'id' => (string) $result->getInsertedId()
        ]);
        
    } catch (Exception $e) {
        return $response->withJson([
            'error' => 'Internal server error'
        ], 500);
    }
});

$app->put('/api_final/codes/{id}', function ($request, $response, $args) {
    try {
        $id = $args['id'];
        $data = $request->getParsedBody();
        
        if (!$data) {
            return $response->withJson([
                'error' => 'No data provided'
            ], 400);
        }
        
        $collection_codigos = getCollectionCodigos();
        $result = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            ['$set' => $data]
        );
        
        if ($result->getMatchedCount() === 0) {
            return $response->withJson([
                'error' => 'Code not found'
            ], 404);
        }
        
        return $response->withJson([
            'success' => true
        ]);
        
    } catch (Exception $e) {
        return $response->withJson([
            'error' => 'Internal server error'
        ], 500);
    }
});

// Incluir Stripe fuera de la ruta para evitar output accidental
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';

// Ruta para crear sesión de recarga de saldo - ELIMINADA
// Se usa el archivo directo /public/crear_sesion_recarga.php para evitar conflictos

// Ruta para procesar destacado con saldo
$app->post('/procesar_destacado_saldo', function ($request, $response, $args) {
    // Log de entrada
    log_info("Procesando destacado con saldo", ['post_data' => $_POST, 'session_id' => session_id()]);
    
    // Detectar si es petición AJAX
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
        log_warning("Usuario no autenticado en procesar_destacado_saldo");
        if ($isAjax) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Usuario no autenticado']));
        } else {
            return $response->withRedirect('/login', 302);
        }
    }
    
    // Obtener datos del POST
    $codigo_id = $_POST['codigo_id'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    
    if (empty($codigo_id) || empty($tipo) || empty($precio)) {
        if ($isAjax) {
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Datos incompletos']));
        } else {
            return $response->withRedirect('/mis-anuncios?error=datos_incompletos', 302);
        }
    }
    
    try {
        // Incluir archivos necesarios
        include_once __DIR__ . '/inc/includes.php';
        include_once __DIR__ . '/myphp/funciones.php';
        include_once __DIR__ . '/myphp/funciones_usuario.php';
        include_once __DIR__ . '/myphp/funciones_codigo.php';
        
        // Obtener el usuario y verificar saldo
        $collection_usuarios = getCollectionUsuarios();
        $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
        
        if (!$usuario) {
            if ($isAjax) {
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Usuario no encontrado']));
            } else {
                return $response->withRedirect('/mis-anuncios?error=usuario_no_encontrado', 302);
            }
        }
        
        $saldo_actual = $usuario['saldo'] ?? 0;
        $precio_euros = $precio / 100;
        
        if ($saldo_actual < $precio_euros) {
            if ($isAjax) {
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Saldo insuficiente']));
            } else {
                return $response->withRedirect('/mis-anuncios?error=saldo_insuficiente', 302);
            }
        }
        
        // Obtener el código y verificar que pertenece al usuario
        $collection_codigos = getCollectionCodigos();
        $codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
        
        if (!$codigo || $codigo['id_usuario'] != $_SESSION["user_id"]) {
            if ($isAjax) {
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Código no encontrado o no autorizado']));
            } else {
                return $response->withRedirect('/mis-anuncios?error=codigo_no_autorizado', 302);
            }
        }
        
        // Actualizar el código para destacarlo con duración fija
        $duracion_dias = ($tipo === 'super' || $tipo === 'super_landing') ? DESTACADO_DURACION_SUPER : DESTACADO_DURACION_NORMAL;
        $auto_renovar = isset($_POST['auto_renovar']) && $_POST['auto_renovar'] === '1';
        $update_data_codigo = [
            'destacado' => time(),
            'tipo_destacado' => $tipo,
            'fecha_destacado' => new MongoDB\BSON\UTCDateTime(),
            'fecha_fin_destacado' => new MongoDB\BSON\UTCDateTime((time() + ($duracion_dias * 86400)) * 1000),
            'prioridad_pago' => time(),
            'auto_renovar_destacado' => $auto_renovar,
            'aviso_expiracion_enviado' => false,
            'aviso_expirado_enviado' => false
        ];
        
        // Para destacado "super", establecer también destacado_social (aparece en home y tiene prioridad)
        if ($tipo === 'super') {
            $update_data_codigo['destacado_social'] = time();
        } elseif ($tipo === 'super_landing') {
            // Lógica específica para Destacado Super (Guía Oficial) - 30 días
            $update_data_codigo['tipo_destacado'] = 'super'; // Se guarda como 'super' en la DB
            $update_data_codigo['destacado_social'] = time();
            $update_data_codigo['super_destacado_fecha'] = new MongoDB\BSON\UTCDateTime();
            $update_data_codigo['super_destacado_expira'] = new MongoDB\BSON\UTCDateTime((time() + (30 * 24 * 60 * 60)) * 1000); // 30 días
        }
        
        $resultado_codigo = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            ['$set' => $update_data_codigo]
        );
        
        if ($resultado_codigo->getModifiedCount() == 0) {
            if ($isAjax) {
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Error al destacar el código']));
            } else {
                return $response->withRedirect('/mis-anuncios?error=error_destacar_codigo', 302);
            }
        }
        
        // Obtener saldo anterior antes de descontar
        $usuario_actual = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
        $saldo_anterior = $usuario_actual['saldo'] ?? 0;
        
        // Descontar el saldo del usuario
        $resultado_saldo = $collection_usuarios->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])],
            ['$inc' => ['saldo' => -$precio_euros]]
        );
        
        if ($resultado_saldo->getModifiedCount() == 0) {
            // Si falla el descuento, revertir el destacado
            $collection_codigos->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                [
                    '$unset' => [
                        'destacado' => '',
                        'tipo_destacado' => '',
                        'fecha_destacado' => '',
                        'fecha_fin_destacado' => ''
                    ]
                ]
            );
            if ($isAjax) {
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Error al descontar el saldo']));
            } else {
                return $response->withRedirect('/mis-anuncios?error=error_descontar_saldo', 302);
            }
        }
        
        // Registrar la transacción en la base de datos
        $mongo = createConnection();
        $collection_transacciones = $mongo->selectCollection('transacciones');
        $transaccion = [
            'usuario_id' => $_SESSION["user_id"],
            'tipo' => 'destacado',
            'subtipo' => $tipo,
            'cantidad' => -$precio_euros,
            'descripcion' => "Destacado de código - Tipo: {$tipo}",
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'estado' => 'completada',
            'codigo_id' => $codigo_id,
            'marca' => $codigo['marca'] ?? '',
            'tipo_destacado' => $tipo,
            'metodo_pago' => 'saldo',
            'stripe_session_id' => null, // null para pagos con saldo
            'stripe_payment_intent' => null,
            'saldo_anterior' => $saldo_anterior,
            'saldo_nuevo' => $saldo_anterior - $precio_euros
        ];
        $collection_transacciones->insertOne($transaccion);
        
        // Log de la transacción
        log_application("Destacado con saldo exitoso", [
            'user_id' => $_SESSION["user_id"],
            'codigo_id' => $codigo_id,
            'tipo' => $tipo,
            'precio_euros' => $precio_euros
        ]);
        
        // Guardar datos para notificaciones en background
        $bg_notify_data = [
            'codigo_id' => $codigo_id,
            'user_id' => $_SESSION["user_id"],
            'tipo' => $tipo,
            'marca_clave' => $codigo['marca'] ?? '',
            'modified' => $resultado_codigo->getModifiedCount() > 0
        ];
        
        if ($isAjax) {
            // Enviar respuesta JSON al cliente ANTES de procesar emails
            $response = $response->withHeader('Content-Type', 'application/json')->write(json_encode([
                'success' => true,
                'message' => 'Código destacado exitosamente',
                'saldo_restante' => $saldo_actual - $precio_euros
            ]));
            
            // Registrar función para enviar emails DESPUÉS de cerrar la conexión HTTP
            register_shutdown_function(function() use ($bg_notify_data) {
                // Cerrar la conexión con el cliente para que no espere
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                
                try {
                    // Notificar competencia home (solo para destacado super)
                    if ($bg_notify_data['modified'] && $bg_notify_data['tipo'] === 'super') {
                        if (function_exists('notificar_competencia_home_destacado_super')) {
                            $collection_codigos = getCollectionCodigos();
                            $codigo_actualizado = $collection_codigos->findOne(['_id' => new \MongoDB\BSON\ObjectId($bg_notify_data['codigo_id'])]);
                            $emails_enviados = notificar_competencia_home_destacado_super(
                                $bg_notify_data['codigo_id'],
                                $bg_notify_data['user_id'],
                                $codigo_actualizado
                            );
                            log_info("Notificaciones de competencia home enviadas (background): $emails_enviados");
                        }
                    }
                    
                    // Notificar competencia en la misma marca
                    if ($bg_notify_data['modified'] && !empty($bg_notify_data['marca_clave'])) {
                        if (!function_exists('notificarCompetenciaDestacado')) {
                            require_once __DIR__ . '/myphp/funciones_destacados_email.php';
                        }
                        $notifs = notificarCompetenciaDestacado($bg_notify_data['marca_clave'], $bg_notify_data['user_id'], $bg_notify_data['tipo']);
                        log_info("Notificaciones competencia marca (background) ({$bg_notify_data['marca_clave']}): $notifs enviadas");
                    }
                } catch (\Exception $e) {
                    log_error("Error enviando notificaciones en background: " . $e->getMessage());
                }
            });
            
            return $response;
        } else {
            // Redirigir a mis-anuncios con mensaje de éxito
            return $response->withRedirect('/mis-anuncios?success=destacado&codigo=' . $codigo_id . '&tipo=' . $tipo, 302);
        }
        
    } catch (Exception $e) {
        log_error("Error procesando destacado con saldo", [
            'error' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
            'post_data' => $_POST,
            'session_id' => session_id()
        ]);
        
        if ($isAjax) {
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]));
        } else {
            return $response->withRedirect('/mis-anuncios?error=error_interno', 302);
        }
    }
});

// Rutas de páginas especiales
$app->get('/exchanges-que-no-informan-a-hacienda', function ($request, $response, $args) {
    global $author_web, $name_page;
    
    $title = '¿Cuáles son los exchanges que no informan a Hacienda? Descúbrelos';
    $description = 'Explora cuáles son los exchanges de criptomonedas que no informan a Hacienda, sus características y las implicaciones legales para los usuarios. - ' . $author_web;
    $imagen_social = 'https://www.codigoamigo.com/img/guias/exchanges-hacienda-social.jpg';
    $categoria = 'crypto';
    
    $name_page = 'exchanges_hacienda';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/guias/exchanges_hacienda.php';
    
    return $response;
});

$app->get('/neobancos-que-no-informan-a-hacienda', function ($request, $response, $args) {
    global $author_web, $name_page;
    
    $title = '¿Qué neobancos no informan a Hacienda? Lista completa';
    $description = 'Descubre qué neobancos no informan automáticamente a Hacienda, sus ventajas y desventajas, y las consideraciones legales importantes. - ' . $author_web;
    $imagen_social = 'https://www.codigoamigo.com/img/guias/neobancos-hacienda-social.jpg';
    $categoria = 'fintech';
    
    $name_page = 'neobancos_hacienda';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/guias/neobancos_hacienda.php';
    
    return $response;
});

$app->get('/bienvenida-login', function ($request, $response, $args) {
    global $author_web;

    if (empty($_SESSION["user_id"])) {
        return $response->withRedirect('/', 302);
    }

    $title = '¡Bienvenido a Código Amigo!';
    $description = 'Descubre las mejores oportunidades para compartir y ahorrar en nuestra comunidad - ' . $author_web;

    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/bienvenida-login.php';

    return $response;
});

// Rutas adicionales críticas
$app->post('/google_sign', function ($request, $response, $args) {
    $response = $response->withHeader('Content-Type', 'application/json');

    global $author_web, $detect, $num_inicio;

    ob_start();
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/google-sign-in.php';
    $payload = ob_get_clean();
    $trimmedPayload = trim($payload);

    if ($trimmedPayload === '') {
        $trimmedPayload = json_encode([
            'success' => false,
            'error' => 'Respuesta vacía del servicio de autenticación'
        ]);
        log_error('[google_sign route] Respuesta vacía después de incluir google-sign-in.php');
    } elseif ($trimmedPayload[0] !== '{' && $trimmedPayload[0] !== '[') {
        log_error('[google_sign route] Respuesta inesperada: ' . substr($trimmedPayload, 0, 400));
    }

    $response->getBody()->write($trimmedPayload);

    return $response;
});

// Rutas de códigos (versiones directas sin /api_final)
$app->get('/codes', function ($request, $response, $args) {
    $userId = $request->getHeaderLine('user_id');

    if (!$userId) {
        return $response->withJson([
            'error' => 'User ID is required'
        ], 400);
    }

    try {
        $array_filtro = [
            "id_usuario" => $userId,
            "estado" => 0
        ];

        $array_opciones = [
            "limit" => 50,
            "sort" => ["_id" => -1]
        ];

        $lista_codigos = get_all_listado_codigos_array($array_filtro, $array_opciones);
        $codigos = isset($lista_codigos["results"]) ? $lista_codigos["results"] : [];

        $result = [];
        foreach ($codigos as $codigo) {
            $result[] = [
                'id' => (string) $codigo['_id'],
                'titulo' => $codigo['titulo'] ?? '',
                'descripcion' => $codigo['descripcion'] ?? '',
                'marca' => $codigo['marca'] ?? '',
                'beneficio' => $codigo['beneficio'] ?? 0,
                'fecha_creacion' => $codigo['fecha_creacion'] ?? null
            ];
        }

        return $response->withJson([
            'success' => true,
            'data' => $result
        ]);

    } catch (Exception $e) {
        return $response->withJson([
            'error' => 'Internal server error'
        ], 500);
    }
});

$app->post('/codes', function ($request, $response, $args) {
    try {
        $data = $request->getParsedBody();
        
        if (!$data || !isset($data['titulo']) || !isset($data['descripcion'])) {
            return $response->withJson([
                'error' => 'Missing required fields'
            ], 400);
        }
        
        $collection_codigos = getCollectionCodigos();
        $result = $collection_codigos->insertOne([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'marca' => $data['marca'] ?? '',
            'beneficio' => $data['beneficio'] ?? 0,
            'id_usuario' => $data['user_id'] ?? '',
            'estado' => 0,
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_publicacion' => date('Y-m-d H:i:s'),
            'destacado' => 0,
            'destacado_social' => 0,
            'clicks' => 0,
            'totalclicks' => 0,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'fecha_modificacion' => date('Y-m-d H:i:s'),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ]);
        
        return $response->withJson([
            'success' => true,
            'id' => (string) $result->getInsertedId()
        ]);
        
    } catch (Exception $e) {
        return $response->withJson([
            'error' => 'Internal server error'
        ], 500);
    }
});

$app->put('/codes/{id}', function ($request, $response, $args) {
    try {
        $id = $args['id'];
        $data = $request->getParsedBody();
        
        if (!$data) {
            return $response->withJson([
                'error' => 'No data provided'
            ], 400);
        }
        
        $collection_codigos = getCollectionCodigos();
        $result = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            ['$set' => $data]
        );
        
        if ($result->getMatchedCount() === 0) {
            return $response->withJson([
                'error' => 'Code not found'
            ], 404);
        }
        
        return $response->withJson([
            'success' => true
        ]);
        
    } catch (Exception $e) {
        return $response->withJson([
            'error' => 'Internal server error'
        ], 500);
    }
});

$app->post('/verify', function ($request, $response, $args) {
    debug_log('API /verify called');
    debug_log('Request headers:', getallheaders());
    debug_log('Request body:', file_get_contents('php://input'));

    try {
        $data = $request->getParsedBody();
        debug_log('Parsed request data:', $data);

        if (!$data) {
            debug_log('No data received in request');
            return $response->withJson([
                'error' => 'No data received'
            ], 400);
        }

        $phone = $data['phone'] ?? '';
        debug_log('Phone number:', $phone);

        if (!$phone) {
            debug_log('Phone number missing');
            return $response->withJson([
                'error' => 'Phone number is required'
            ], 400);
        }

        debug_log('Looking up user with phone:', $phone);
        $usuario = getObjectUser('telefono', $phone);
        debug_log('User lookup result:', $usuario);

        if ($usuario) {
            return $response->withJson([
                'success' => true,
                'user_id' => (string) $usuario['_id'],
                'username' => $usuario['username']
            ]);
        }

        return $response->withJson([
            'error' => 'User not found'
        ], 404);

    } catch (Exception $e) {
        debug_log('Exception in /verify:', $e);
        return $response->withJson([
            'error' => 'Internal server error'
        ], 500);
    }
});

// Ruta para destacar código
$app->get('/destacar_codigo', function ($request, $response, $args) {
    global $author_web, $show_adsense, $name_page, $detect;
    include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/includes.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    $title = "Destacar código - CodigoAmigo.com";
    $description = "Destaca tu código de descuento para obtener mayor visibilidad";
    
    include __DIR__ . '/public/destacar_codigo.php';
    
    return $response;
});

// Ruta para página de éxito después de destacar - eliminada para evitar duplicación
// El archivo felicidades_destacar.php se maneja directamente

// Ruta para páginas públicas de usuario
$app->get('/usuario_{user_info:[^/]+}', function ($request, $response, $args) {
    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno
    
    // Inicializar Mobile_Detect si no está definido
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;
    
    // Extraer user_id y username del parámetro user_info
    $user_info = $args['user_info'];
    
    // Debug: Log para ver qué está recibiendo
    // error_log("User info recibido: " . $user_info);
    
    // El formato es: sergio+de+la+rosa_58bd851da54e295b8b52f702
    // Buscar el último _ para separar username e user_id
    $last_underscore_pos = strrpos($user_info, '_');
    
    if ($last_underscore_pos === false) {
        log_warning("No se encontró underscore en user_info", ['user_info' => $user_info]);
        return $response->withRedirect('/', 301);
    }
    
    $username = str_replace('+', ' ', substr($user_info, 0, $last_underscore_pos));
    $user_id = substr($user_info, $last_underscore_pos + 1);

    // log_debug("Username extraído: " . $username);
    // log_debug("User ID extraído: " . $user_id);

            // Validar que el user_id sea un ObjectId válido
    if (!isValidObjectId($user_id)) {
        log_warning("User ID inválido - no es un ObjectId válido", ['user_id' => $user_id, 'user_info' => $user_info]);
        return $response->withRedirect('/', 301);
    }

    // Obtener datos del usuario
    try {
        $data_usuario = getObjectUser('_id', new MongoDB\BSON\ObjectId($user_id));
    } catch (Exception $e) {
        log_error("Error al obtener datos del usuario", ['user_id' => $user_id, 'error' => $e->getMessage()]);
        return $response->withRedirect('/', 301);
    }
    
    if (!$data_usuario) {
        return $response->withRedirect('/', 301);
    }
    
    $author = isset($GLOBALS["author"]) ? $GLOBALS["author"] : "Código Amigo";
    
    $title = "Códigos de " . $username . " - " . $author;
    $description = "Descubre los códigos de descuento compartidos por " . $username . " en " . $author;
    
    // Obtener códigos del usuario - intentar diferentes enfoques
    $array_skip = array("limit" => 1000);
    $array_skip = array_merge($array_skip, array("sort" => array('fecha_publicacion' => -1)));

    $listado_codigos = array();

    // Método 1: Buscar por ObjectId
    try {
        $array_filtro = array("id_usuario" => new MongoDB\BSON\ObjectId($user_id));
        $array_filtro_activos = array_merge($array_filtro, array("estado" => 0));
        $resultado_codigos = get_all_listado_codigos_array($array_filtro_activos, $array_skip);
        $listado_codigos = isset($resultado_codigos["results"]) ? $resultado_codigos["results"] : array();
    } catch (Exception $e) {
        // Si hay error con ObjectId, intentar con string
        $array_filtro = array("id_usuario" => $user_id);
        $array_filtro_activos = array_merge($array_filtro, array("estado" => 0));
        $resultado_codigos = get_all_listado_codigos_array($array_filtro_activos, $array_skip);
        $listado_codigos = isset($resultado_codigos["results"]) ? $resultado_codigos["results"] : array();
    }

    // Si no hay códigos activos, intentar con otros estados
    if (empty($listado_codigos)) {
        // Asegurar variables auxiliares inicializadas
        $listado_codigos_inactivos = [];
        $listado_codigos_desactivados = [];
        // Intentar obtener códigos con estado -1 (inactivos)
        $array_filtro_inactivos = array_merge($array_filtro, array("estado" => -1));
        $resultado_codigos = get_all_listado_codigos_array($array_filtro_inactivos, $array_skip);
        $listado_codigos_inactivos = isset($resultado_codigos["results"]) ? $resultado_codigos["results"] : array();

        // Intentar obtener códigos con estado -2 (desactivados por usuario)
        if (empty($listado_codigos_inactivos)) {
            $array_filtro_desactivados = array_merge($array_filtro, array("estado" => -2));
            $resultado_codigos = get_all_listado_codigos_array($array_filtro_desactivados, $array_skip);
            $listado_codigos_desactivados = isset($resultado_codigos["results"]) ? $resultado_codigos["results"] : array();
        }

        // Combinar todos los códigos encontrados
        $listado_codigos = array_merge($listado_codigos, $listado_codigos_inactivos, $listado_codigos_desactivados);

        // Si aún no hay códigos, intentar sin filtro de estado (códigos sin campo estado)
        if (empty($listado_codigos)) {
            $resultado_codigos = get_all_listado_codigos_array($array_filtro, $array_skip);
            $listado_codigos_sin_estado = isset($resultado_codigos["results"]) ? $resultado_codigos["results"] : array();
            $listado_codigos = array_merge($listado_codigos, $listado_codigos_sin_estado);
        }
    }

    $num_codigos = count($listado_codigos);

    // Calcular total de clicks e impresiones sumando todos los códigos
    $total_clicks_usuario = 0;
    $total_impresiones_usuario = 0;
    foreach($listado_codigos as $codigo) {
        $total_clicks_usuario += isset($codigo['totalclicks']) ? (int)$codigo['totalclicks'] : 0;
        $total_impresiones_usuario += isset($codigo['total_impressions']) ? (int)$codigo['total_impressions'] : 0;
    }

    // Debug: mostrar información sobre códigos encontrados
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo "<!-- Debug: User ID: $user_id, ObjectId: " . new MongoDB\BSON\ObjectId($user_id) . ", Códigos encontrados: $num_codigos -->";
        if (!empty($listado_codigos)) {
            echo "<!-- Debug: Primeros códigos IDs: ";
            foreach (array_slice($listado_codigos, 0, 3) as $codigo) {
                echo $codigo['_id'] . ", ";
            }
            echo " -->";
        }
    }

    // Obtener categorías únicas de los códigos del usuario
    $categorias_usuario = array();
    foreach($listado_codigos as $codigo) {
        if(isset($codigo['clave_categoria']) && !in_array($codigo['clave_categoria'], $categorias_usuario)) {
            $categorias_usuario[] = $codigo['clave_categoria'];
        }
    }
    
    // Incluir la página específica de usuario público
    include_once $_SERVER['DOCUMENT_ROOT'] . '/public/usuario_publico.php';
    
    return $response;
});

/********************************************************************
 * AMAZON GRATIS - Servicios digitales Amazon
 *******************************************************************/
$app->get('/amazon', function ($request, $response) {
    // Incluir la página de servicios Amazon
    include_once $_SERVER['DOCUMENT_ROOT'] . '/amazon_services.php';
    return $response;
});

/********************************************************************
 * CHOLLOS
 *******************************************************************/

/********************************************************************
 * CHOLLOS - Redirects a malprecio.com
 *******************************************************************/

// Rutas de chollos - Eliminadas definitivamente (410 Gone) para ahorro de Crawl Budget
$app->get('/chollos', function ($request, $respon) {
    return $respon->withStatus(410)->write('Este contenido ha sido eliminado permanentemente.');
});

$app->get("/chollo/{id}", function ($request, $respon, $args) {
    $id = $args["id"];
    return $respon->withStatus(301)->withHeader("Location", "https://malprecio.com/chollo/" . $id);
});




$app->get('/chollos/{categoria}', function ($request, $respon, $args) {
    return $respon->withStatus(410)->write('Este contenido ha sido eliminado permanentemente.');
});

$app->get('/chollos/{categoria}/{id}', function ($request, $respon, $args) {
    return $respon->withStatus(410)->write('Este contenido ha sido eliminado permanentemente.');
});

$app->get('/chollos/{categoria:.*}', function ($request, $respon, $args) {
    return $respon->withStatus(410)->write('Este contenido ha sido eliminado permanentemente.');
});
    


// Webhook para Telegram chollos - Redirect a malprecio.com
$app->post('/webhook/telegram-chollos', function ($request, $respon) {
    // Webhook deshabilitado - chollos se gestionan desde malprecio.com
    return $respon->withJson(['success' => false, 'message' => 'Webhook moved to malprecio.com']);
});

// Ruta para páginas de categoría (debe ir al final para evitar conflictos)
$app->get('/{categoria}', function ($request, $response, $args) {
    $categoria_url = $args['categoria'];
    
    // Verificar si es una categoría válida (no es una marca)
    $categorias_validas = [
        'alimentacion-y-gastronomia-comparte-y-gana',
        'tecnologia-y-electronica-comparte-y-gana',
        'moda-y-belleza-comparte-y-gana',
        'hogar-y-jardin-comparte-y-gana',
        'deportes-y-ocio-comparte-y-gana',
        'viajes-y-turismo-comparte-y-gana',
        'finanzas-y-seguros-comparte-y-gana',
        'salud-y-bienestar-comparte-y-gana'
    ];
    
    if(!in_array($categoria_url, $categorias_validas)) {
        // Si no es una categoría válida, redirigir a la página principal
        return $response->withRedirect('/', 301);
    }
    
    // Inicializar variables globales
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Incluir archivos necesarios
    include_once __DIR__ . '/inc/includes.php';
    include_once __DIR__ . '/myphp/funciones.php';

    // Inicializar detector de móviles
    if (!isset($detect)) {
        $detect = new Mobile_Detect();
    }
    $GLOBALS['detect'] = $detect;

    // Incluir funciones modernas
    include_once __DIR__ . '/myphp/funciones_modern.php';
    include_once __DIR__ . '/myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno

    // Usar el header moderno
    
    // Obtener información de la categoría
    $categoria_info = get_category_info($categoria_url);
    $nombre_categoria = $categoria_info['nombre'];
    $descripcion_categoria = $categoria_info['descripcion'];
    
    // Obtener marcas de esta categoría
    $marcas_categoria = get_brands_by_category($categoria_url);
    
    // Llamar a la función del header moderno
    get_header_modern(
        "Códigos de descuento " . $nombre_categoria . " - CodigoAmigo.com",
        "Encuentra los mejores códigos de descuento en " . $nombre_categoria . " verificados y actualizados diariamente",
        "Códigos de descuento " . $nombre_categoria,
        "Códigos de descuento " . $nombre_categoria,
        "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png"
    );

    // Generar contenido de la página de categoría
    echo generate_category_page_layout($categoria_url, $nombre_categoria, $descripcion_categoria, $marcas_categoria);
    
    // CSS adicional
    echo get_modern_additional_css();

    return $response;
});

// Manejo de errores 404
$app->get('/{path:.*}', function ($request, $response, $args) {
    $path = $args['path'];
    
    // Excluir rutas de chollos
    if (strpos($path, 'chollos') === 0) {
        return $response->withStatus(404);
    }
    
    // Si la ruta empieza con 'usuario_', no redirigir (dejar que Slim maneje el error)
    if (strpos($path, 'usuario_') === 0) {
        return $response->withStatus(404);
    }
    
    // Para otras rutas no encontradas, redirigir a la página principal
    return $response->withRedirect('/', 301);
});

// Ruta para eliminar códigos (POST para formularios tradicionales)
$app->post('/delete_code_action', function ($request, $response, $args) {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
        $_SESSION['msg_error'] = 'Usuario no autenticado';
        return $response->withRedirect('/mis-anuncios', 302);
    }

    // Obtener datos del POST
    $codigo_id = $_POST['codigo_id'] ?? '';

    if (empty($codigo_id)) {
        $_SESSION['msg_error'] = 'ID de código requerido';
        return $response->withRedirect('/mis-anuncios', 302);
    }

    try {
        // Incluir solo las funciones necesarias para MongoDB
        require_once __DIR__ . '/vendor/autoload.php';
        include_once __DIR__ . '/myphp/funciones.php';
        include_once __DIR__ . '/myphp/funciones_codigo.php';

        // Obtener el código y verificar que pertenece al usuario
        $collection_codigos = getCollectionCodigos();
        $codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

        if (!$codigo) {
            $_SESSION['msg_error'] = 'Código no encontrado';
            return $response->withRedirect('/mis-anuncios', 302);
        }

        // Comparar ObjectId con string correctamente
        if ((string)$codigo['id_usuario'] != $_SESSION["user_id"]) {
            $_SESSION['msg_error'] = 'No tienes permisos para eliminar este código';
            return $response->withRedirect('/mis-anuncios', 302);
        }

        // Eliminar el código
        $resultado = $collection_codigos->deleteOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

        if ($resultado->getDeletedCount() == 0) {
            $_SESSION['msg_error'] = 'Error al eliminar el código';
            return $response->withRedirect('/mis-anuncios', 302);
        }

        // Log de la eliminación
        log_application("Código eliminado", ['user_id' => $_SESSION["user_id"], 'codigo_id' => $codigo_id]);

        // Redirigir con mensaje de éxito
        $_SESSION['msg_success'] = 'Código eliminado correctamente';
        return $response->withRedirect('/mis-anuncios', 302);

    } catch (Exception $e) {
        log_error("Error eliminando código", ['error' => $e->getMessage(), 'user_id' => $_SESSION["user_id"], 'codigo_id' => $codigo_id]);
        $_SESSION['msg_error'] = 'Error interno del servidor';
        return $response->withRedirect('/mis-anuncios', 302);
    }
});

// Ruta para eliminar códigos (usando nombre diferente para evitar bloqueo de Cloudflare)
$app->get('/delete_code/{codigo_id}', function ($request, $response, $args) {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Usuario no autenticado']));
    }

    // Obtener ID del código desde la URL
    $codigo_id = $args['codigo_id'] ?? '';

    if (empty($codigo_id)) {
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'ID de código requerido']));
    }

    try {
        // Incluir solo las funciones necesarias para MongoDB
        require_once __DIR__ . '/vendor/autoload.php';
        include_once __DIR__ . '/myphp/funciones.php';
        include_once __DIR__ . '/myphp/funciones_codigo.php';

        // Obtener el código y verificar que pertenece al usuario
        $collection_codigos = getCollectionCodigos();
        $codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

        if (!$codigo) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Código no encontrado']));
        }

        // Comparar ObjectId con string correctamente
        if ((string)$codigo['id_usuario'] != $_SESSION["user_id"]) {
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'No tienes permisos para eliminar este código']));
        }

        // Eliminar el código
        $resultado = $collection_codigos->deleteOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

        if ($resultado->getDeletedCount() == 0) {
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Error al eliminar el código']));
        }

        // Log de la eliminación
        log_application("Código eliminado via AJAX", ['user_id' => $_SESSION["user_id"], 'codigo_id' => $codigo_id]);

        return $response->withHeader('Content-Type', 'application/json')->write(json_encode([
            'success' => true,
            'message' => 'Código eliminado correctamente'
        ]));

    } catch (Exception $e) {
        log_error("Error eliminando código via AJAX", ['error' => $e->getMessage(), 'user_id' => $_SESSION["user_id"], 'codigo_id' => $codigo_id]);
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode(['error' => 'Error interno del servidor']));
    }
});

// Ejecutar la aplicación
$app->run();
?>

<?php
$shouldOutputFooterScript = true;

if (function_exists('headers_list')) {
    foreach (headers_list() as $headerLine) {
        if (stripos($headerLine, 'Content-Type: application/json') !== false ||
            stripos($headerLine, 'Content-Type: application/xml') !== false) {
            $shouldOutputFooterScript = false;
            break;
        }
    }
}

if ($shouldOutputFooterScript):
?>
<script>
// JavaScript para el slider de marcas populares
(function() {
let currentSlide = 0;
const slidesPerView = window.innerWidth <= 768 ? 1 : window.innerWidth <= 1024 ? 2 : 3;
const totalSlides = Math.ceil(9 / slidesPerView); // 9 marcas totales

function moveSlider(direction) {
    const slider = document.getElementById('brandsSlider');
    if (!slider) return;
    
    // En móvil, mover slide por slide. En desktop, mover por grupos
    const moveAmount = window.innerWidth <= 768 ? 1 : slidesPerView;
    const maxSlides = window.innerWidth <= 768 ? 9 - slidesPerView : Math.ceil(9 / slidesPerView);
    
    currentSlide += moveAmount * direction;
    
    // Limitar el rango
    if (currentSlide < 0) {
        currentSlide = maxSlides;
    } else if (currentSlide > maxSlides) {
        currentSlide = 0;
    }
    
    // Calcular el desplazamiento
    const slideWidth = 100 / slidesPerView;
    const translateX = -(currentSlide * slideWidth);
    
    slider.style.transform = `translateX(${translateX}%)`;
    
    // Actualizar indicadores
    updateDots();
}

function goToSlide(slideNumber) {
    currentSlide = slideNumber - 1;
    const slider = document.getElementById('brandsSlider');
    if (!slider) return;
    
    const slideWidth = 100 / slidesPerView;
    const translateX = -(currentSlide * slideWidth);
    
    slider.style.transform = `translateX(${translateX}%)`;
    
    // Actualizar indicadores
    updateDots();
}

function updateDots() {
    const dots = document.querySelectorAll('.dot');
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar indicadores
    updateDots();
});

// Redimensionar ventana
window.addEventListener('resize', function() {
    // Recalcular slides por vista
    const newSlidesPerView = window.innerWidth <= 768 ? 1 : window.innerWidth <= 1024 ? 2 : 3;
    if (newSlidesPerView !== slidesPerView) {
        currentSlide = 0;
        updateDots();
    }
});

// SCROLL INFINITO PARA CÓDIGOS DESTACADOS
let featuredCodesLoaded = 6;
let featuredCodesPerLoad = 6;
let isLoadingFeatured = false;

function loadMoreFeaturedCodes() {
    console.log('loadMoreFeaturedCodes llamada');
    console.log('Estado:', {isLoadingFeatured, hasData: !!window.featuredCodesData});
    
    if (isLoadingFeatured || !window.featuredCodesData) {
        console.log('No se puede cargar:', {isLoadingFeatured, hasData: !!window.featuredCodesData});
        return;
    }
    
    const loadMoreBtn = document.getElementById('loadMoreFeatured');
    const featuredGrid = document.getElementById('featuredGrid');
    const totalCodes = parseInt(loadMoreBtn.dataset.total);
    
    console.log('Elementos encontrados:', {loadMoreBtn: !!loadMoreBtn, featuredGrid: !!featuredGrid, totalCodes});
    
    if (featuredCodesLoaded >= totalCodes) {
        loadMoreBtn.style.display = 'none';
        return;
    }
    
    isLoadingFeatured = true;
    
    // Mostrar estado de carga
    loadMoreBtn.querySelector('.btn-text').style.display = 'none';
    loadMoreBtn.querySelector('.btn-loading').style.display = 'flex';
    loadMoreBtn.disabled = true;
    
    // Simular carga (en producción esto sería una llamada AJAX)
    setTimeout(() => {
        const startIndex = featuredCodesLoaded - 6; // Índice en el array de códigos restantes
        const endIndex = Math.min(startIndex + featuredCodesPerLoad, window.featuredCodesData.length);
        const codesToLoad = window.featuredCodesData.slice(startIndex, endIndex);
        
        console.log('Cargando códigos:', {startIndex, endIndex, codesToLoad: codesToLoad.length, totalData: window.featuredCodesData.length});
        
        // Añadir códigos al grid
        const newCards = [];
        codesToLoad.forEach((codigo, index) => {
            const cardHtml = generateFeaturedCardHTML(codigo, featuredCodesLoaded + index);
            const cardElement = document.createElement('div');
            cardElement.innerHTML = cardHtml;
            const actualCard = cardElement.firstElementChild;
            actualCard.classList.add('featured-card-loading');
            featuredGrid.appendChild(actualCard);
            newCards.push(actualCard);
        });
        
        featuredCodesLoaded = endIndex;
        loadMoreBtn.dataset.loaded = featuredCodesLoaded;
        
        // Actualizar texto del botón
        if (featuredCodesLoaded >= totalCodes) {
            loadMoreBtn.style.display = 'none';
        } else {
            loadMoreBtn.querySelector('.btn-text').textContent = `Ver más códigos destacados (${totalCodes - featuredCodesLoaded} restantes)`;
        }
        
        // Restaurar estado del botón
        loadMoreBtn.querySelector('.btn-text').style.display = 'inline';
        loadMoreBtn.querySelector('.btn-loading').style.display = 'none';
        loadMoreBtn.disabled = false;
        
        isLoadingFeatured = false;
        
        // Animar las nuevas tarjetas inmediatamente después de añadirlas
        newCards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.remove('featured-card-loading');
            }, index * 100);
        });
        
    }, 1000); // Simular tiempo de carga
}

function generateFeaturedCardHTML(codigo, index) {
    const brand = codigo.marca || 'Marca desconocida';
    const description = codigo.descripcion || 'Descripción no disponible';
    const codeId = codigo._id || '';
    const benefit = codigo.num_beneficio || 0;
    const ratings = codigo.num_valoraciones || 0;
    const userId = codigo.id_usuario || '';
    
    // Obtener información del usuario (simplificado para JavaScript)
    const username = codigo.usuario_username || 'Usuario';
    const userImage = codigo.usuario_imagen || '/img/no_image.png';
    
    // Obtener información de la marca (simplificado para JavaScript)
    const brandImage = codigo.marca_imagen || '/img/no_image.png';
    
    return `
        <div class="featured-card">
            <div class="featured-badge">
                <i class="fas fa-star"></i>
                <span>Destacado</span>
            </div>
            <div class="featured-brand">
                <img src="${brandImage}" alt="${brand}" onerror="this.src='/img/no_image.png'">
                <h3>${brand}</h3>
            </div>
            <div class="featured-publisher">
                <img src="${userImage}" alt="${username}" onerror="this.src='/img/no_image.png'">
                <span>${username}</span>
            </div>
            <p class="featured-description">${description}</p>
            <div class="featured-benefit">
                <span class="beneficio-icono">💰</span>
                <span class="beneficio-cantidad">${benefit}€ beneficio</span>
            </div>
            <button class="featured-btn" onclick="window.location.href='/codigo/${codeId}'">Ver Código</button>
        </div>
    `;
}

// Inicializar scroll infinito cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('loadMoreFeatured');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreFeaturedCodes);
        
        // Scroll infinito automático
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            
            scrollTimeout = setTimeout(() => {
                if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 1000) {
                    loadMoreFeaturedCodes();
                }
            }, 100);
        });
    }
});
})(); // Cerrar IIFE
</script>

<?php endif; ?>
