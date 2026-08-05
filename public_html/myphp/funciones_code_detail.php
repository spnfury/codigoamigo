<?php
// Incluir funciones de PDF
include_once __DIR__ . '/funciones_pdf.php';

/**
 * Formatea la descripción de un código añadiendo saltos de línea
 * en patrones habituales (emojis numéricos, viñetas, etc.) cuando el
 * usuario ha pegado todo en un solo párrafo. Devuelve HTML seguro.
 */
function formatear_descripcion_codigo($texto) {
    if (!is_string($texto) || $texto === '') {
        return '<p>Descripción no disponible</p>';
    }

    $texto = trim($texto);

    // Si ya tiene saltos de línea propios, respetar pero también separar bloques largos
    $tiene_saltos = (strpos($texto, "\n") !== false);

    // Escape HTML primero
    $safe = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

    // Insertar saltos antes de keycaps numéricos 1️⃣ .. 9️⃣ (precedidos por algo)
    $safe = preg_replace('/(?<!^)(?<!\n)\s*([1-9]\x{FE0F}?\x{20E3})/u', "\n\n$1 ", $safe);

    // Saltos antes de marcadores comunes
    $marcadores = ['⚠️', '✅', '👉', '🔗', '📌', '🎁', '💰', '🚀', '📲', '💸', '🛒', '🤝', '🙌', '🌱'];
    foreach ($marcadores as $m) {
        $safe = str_replace($m, "\n" . $m, $safe);
    }

    // Convertir URLs en enlaces (después del escape: ya están como texto plano)
    $safe = preg_replace_callback(
        '#(https?://[^\s<]+)#',
        function ($m) {
            $url = $m[1];
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>';
        },
        $safe
    );

    // Colapsar saltos múltiples
    $safe = preg_replace("/\n{3,}/", "\n\n", $safe);

    // Dividir en párrafos por dobles saltos, líneas por simples
    $parrafos = preg_split("/\n{2,}/", trim($safe));
    $html = '';
    foreach ($parrafos as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $html .= '<p>' . nl2br($p) . '</p>';
    }
    return $html !== '' ? $html : '<p>' . nl2br($safe) . '</p>';
}

// Función para generar la página de detalle del código con diseño increíble
function generate_code_detail_page($codigo) {
    // Asegurar que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Asegurar que $_SESSION esté disponible
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Convertir código a array si es objeto
    if (is_object($codigo)) {
        $codigo = (array)$codigo;
    }
    
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? $codigo['_id'] : '';
    // Convertir code_id a string si es ObjectId
    if (is_object($code_id) && method_exists($code_id, '__toString')) {
        $code_id = (string)$code_id;
    } else {
        $code_id = (string)$code_id;
    }
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    $date = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : new DateTime();
    $destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;

    // Convertir usuario_id a string si es ObjectId
    $usuario_id_str = '';
    if ($usuario_id) {
        if (is_object($usuario_id) && method_exists($usuario_id, '__toString')) {
            $usuario_id_str = (string)$usuario_id;
        } else {
            $usuario_id_str = (string)$usuario_id;
        }
    }
    
    // Verificar si el usuario actual es el propietario del código
    // Asegurar que la sesión esté disponible antes de acceder
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $usuario_actual_id = isset($_SESSION['user_id']) ? trim($_SESSION['user_id']) : '';
    
    $es_propietario = ($usuario_actual_id !== '' && trim($usuario_actual_id) == trim($usuario_id_str));
    


    // Obtener el código real del campo 'codigo'
    $codigo_real = isset($codigo['codigo']) ? $codigo['codigo'] : '';
    
    // Detectar si es URL y extraer código
    $code_info = detect_url_and_extract_code($codigo_real);
    
    // Obtener información completa del usuario
    try {
        $user_info = get_user_info($usuario_id_str ? $usuario_id_str : $usuario_id);
        if (!is_array($user_info)) {
            $user_info = [];
        }
    } catch (Exception $e) {
        $user_info = [];
    }
    
    $username = isset($user_info['username']) && !empty($user_info['username']) ? $user_info['username'] : 'Usuario';
    $user_img = isset($user_info['img']) && !empty($user_info['img']) ? $user_info['img'] : '';
    
    // Asegurar que user_id sea un string
    $user_id = $usuario_id_str; // Por defecto usar el ID convertido
    if (isset($user_info['id']) && !empty($user_info['id'])) {
        if (is_object($user_info['id'])) {
            if (method_exists($user_info['id'], '__toString')) {
                $user_id = (string)$user_info['id'];
            } else {
                // Si es ObjectId, convertir a string
                $user_id = (string)$user_info['id'];
            }
        } else {
            $user_id = (string)$user_info['id'];
        }
    }
    
    // Asegurar que user_id no esté vacío - usar el ID del código como último recurso
    if (empty($user_id) || $user_id === '') {
        $user_id = $usuario_id_str;
    }
    
    // Si aún está vacío, no mostrar el botón de chat
    if (empty($user_id) || $user_id === '') {
        $user_id = null;
    }
    
    // Obtener información de la marca
    $marca_info = get_brand_info($brand);
    if (!is_array($marca_info)) {
        $marca_info = [];
    }
    $marca_nombre = isset($marca_info['nombre']) ? $marca_info['nombre'] : ucfirst($brand);
    $marca_imagen = isset($marca_info['imagen']) ? $marca_info['imagen'] : '';
    
    $html = '<div class="container-fluid main_entremedio">';
    
    // Hero Section
    $html .= '<div class="code-detail-hero">';
    $html .= '<div class="container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-12">';
    
    // Breadcrumb
    $html .= '<nav class="breadcrumb-nav">';
    $html .= '<a href="/" class="breadcrumb-link">Inicio</a>';
    $html .= '<span class="breadcrumb-separator">›</span>';
    $html .= '<a href="/de-' . $brand . '" class="breadcrumb-link">' . $marca_nombre . '</a>';
    $html .= '<span class="breadcrumb-separator">›</span>';
    $html .= '<span class="breadcrumb-current">Código de descuento</span>';
    $html .= '</nav>';
    
    // Header principal
    $html .= '<div class="code-detail-header">';
    $html .= '<div class="header-left">';
    if($marca_imagen) {
        $html .= '<a href="/de-' . urlencode($brand) . '" title="Códigos de ' . htmlspecialchars($marca_nombre) . '">';
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="brand-logo">';
        $html .= '</a>';
    }
    $html .= '<div class="header-text">';
    $html .= '<h1>Código de Descuento ' . $marca_nombre . '</h1>';
    $html .= '<p class="header-subtitle">Código verificado y actualizado</p>';
    $html .= '<div class="header-badges">';
    $html .= '<span class="verified-badge"><i class="fas fa-check-circle"></i> Verificado</span>';
    if($destacado) {
        $html .= '<span class="featured-badge"><i class="fas fa-star"></i> Destacado</span>';
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="header-actions">';
    $html .= '<button class="btn-share" onclick="shareCode()"><i class="fas fa-share"></i> Compartir</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Contenido principal
    $html .= '<div class="container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-8">';
    
    // Código principal
    $html .= '<div class="code-main-card">';
    $html .= '<div class="code-benefit-display">';
    $html .= '<div class="benefit-amount">' . $benefit . '€</div>';
    $html .= '<div class="benefit-label">de beneficio</div>';
    $html .= '</div>';

    // Determinar si mostrar el código directamente o con botón de reveal
    // Propietarios siempre ven el código directamente
    $mostrar_reveal = !$es_propietario;
    
    // Si es una URL, mostrar solo el enlace directo sin mostrar el código duplicado
    if (isset($code_info['is_url']) && $code_info['is_url']) {
        $html .= '<div class="code-display-container code-is-url">';
        $html .= '<h3><i class="fas fa-link"></i> Enlace directo</h3>';
        
        if ($mostrar_reveal) {
            // Mostrar botón de reveal para enlaces
            $html .= '<div class="code-reveal-wrapper">';
            $html .= '<div class="code-blurred">';
            $html .= '<div class="code-url-blurred">https://xxxx.xxx/xxxxx...</div>';
            $html .= '</div>';
            $html .= '<button class="btn-reveal-code" onclick="showCodeRevealModal(\'' . htmlspecialchars($code_id, ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($marca_nombre, ENT_QUOTES, 'UTF-8') . '\', ' . intval($benefit) . ', this)">';
            $html .= '<i class="fas fa-unlock"></i> Ver enlace';
            $html .= '</button>';
            $html .= '</div>';
            // Código oculto para mostrar después del reveal
            $html .= '<div class="code-url-section code-revealed-section" style="display: none;" data-codigo="' . htmlspecialchars($code_info['url'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<div class="url-label"><i class="fas fa-link"></i> Enlace directo:</div>';
            $html .= '<a href="' . htmlspecialchars($code_info['url'] ?? '') . '" target="_blank" class="code-url-link">';
            $html .= '<i class="fas fa-external-link-alt"></i>';
            $html .= '<span>' . htmlspecialchars($code_info['url'] ?? '') . '</span>';
            $html .= '</a>';
            $html .= '</div>';
            $html .= '<div class="code-text" id="codeText" style="display: none;">' . htmlspecialchars($code_info['url'] ?? '') . '</div>';
        } else {
            // Propietarios ven el enlace directamente
            $html .= '<div class="code-url-section">';
            $html .= '<div class="url-label"><i class="fas fa-link"></i> Enlace directo:</div>';
            $html .= '<a href="' . htmlspecialchars($code_info['url'] ?? '') . '" target="_blank" class="code-url-link">';
            $html .= '<i class="fas fa-external-link-alt"></i>';
            $html .= '<span>' . htmlspecialchars($code_info['url'] ?? '') . '</span>';
            $html .= '</a>';
            $html .= '</div>';
            $urlTrimmed = trim((string)($code_info['url'] ?? ''));
            $html .= '<div class="code-text" id="codeText" style="display: none;">' . htmlspecialchars($urlTrimmed) . '</div>';
            $html .= '<button class="btn-copy-code" onclick="window.open(\'' . htmlspecialchars($urlTrimmed, ENT_QUOTES, 'UTF-8') . '\', \'_blank\')">';
            $html .= '<i class="fas fa-external-link-alt"></i> Ir a la web';
            $html .= '</button>';
        }
        $html .= '</div>';

    } else {
        $html .= '<div class="code-display-container">';
        $html .= '<h3><i class="fas fa-tag"></i> Tu código de descuento</h3>';
        
        if ($mostrar_reveal) {
            // Mostrar botón de reveal para códigos
            $html .= '<div class="code-reveal-wrapper">';
            $html .= '<div class="code-blurred">';
            $html .= '<div class="code-text-blurred">XXXXXX</div>';
            $html .= '</div>';
            $html .= '<button class="btn-reveal-code" onclick="showCodeRevealModal(\'' . htmlspecialchars($code_id, ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($marca_nombre, ENT_QUOTES, 'UTF-8') . '\', ' . intval($benefit) . ', this)">';
            $html .= '<i class="fas fa-unlock"></i> Ver código';
            $html .= '</button>';
            $html .= '</div>';
            // Código oculto para mostrar después del reveal
            $html .= '<div class="code-text code-revealed-section" id="codeText" style="display: none;" data-codigo="' . htmlspecialchars($code_info['display_text'] ?? '', ENT_QUOTES, 'UTF-8') . '">' . strtoupper($code_info['display_text'] ?? 'CÓDIGO') . '</div>';
        } else {
            // Propietarios ven el código directamente
            $html .= '<div class="code-text" id="codeText">' . strtoupper($code_info['display_text'] ?? 'CÓDIGO') . '</div>';
            $html .= '<button class="btn-copy-code" onclick="copyCode()">';
            $html .= '<i class="fas fa-copy"></i> Copiar código';
            $html .= '</button>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    
    // PDF Carrusel (si existe)
    $paginas_pdf = false;
    if (isset($codigo['pdf_paginas']) && !empty($codigo['pdf_paginas'])) {
        $paginas_pdf = is_array($codigo['pdf_paginas']) ? $codigo['pdf_paginas'] : iterator_to_array($codigo['pdf_paginas']);
    } elseif (isset($codigo['tiene_pdf']) && $codigo['tiene_pdf']) {
        // Si tiene PDF pero no está en el array, intentar obtenerlo
        $paginas_pdf = obtenerPaginasPDF($code_id);
    }
    
    if ($paginas_pdf && is_array($paginas_pdf) && count($paginas_pdf) > 0) {
        $html .= '<div class="pdf-carousel-card">';
        $html .= '<h3><i class="fas fa-file-pdf"></i> Condiciones de Retención</h3>';
        $html .= '<div class="pdf-carousel-container">';
        $html .= '<div class="pdf-carousel-wrapper" id="pdfCarousel">';
        
        // Botón anterior
        $html .= '<button class="pdf-carousel-btn pdf-carousel-prev" onclick="movePDFPage(-1)" id="pdfPrevBtn">';
        $html .= '<i class="fas fa-chevron-left"></i>';
        $html .= '</button>';
        
        // Contenedor de páginas
        $html .= '<div class="pdf-pages-container">';
        foreach ($paginas_pdf as $index => $pagina) {
            $pagina_url = is_array($pagina) ? ($pagina['url'] ?? '') : $pagina;
            $pagina_num = is_array($pagina) ? ($pagina['pagina'] ?? $index + 1) : ($index + 1);
            
            $html .= '<div class="pdf-page-item' . ($index === 0 ? ' active' : '') . '" data-page="' . $index . '">';
            $html .= '<img src="' . htmlspecialchars($pagina_url) . '" alt="Página ' . $pagina_num . '" class="pdf-page-image" loading="lazy">';
            $html .= '<div class="pdf-page-number">Página ' . $pagina_num . ' de ' . count($paginas_pdf) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Contador de páginas estilo LinkedIn
        $html .= '<div class="pdf-page-counter" id="pdfPageCounter">1 / ' . count($paginas_pdf) . '</div>';
        
        // Botón siguiente
        $html .= '<button class="pdf-carousel-btn pdf-carousel-next" onclick="movePDFPage(1)" id="pdfNextBtn">';
        $html .= '<i class="fas fa-chevron-right"></i>';
        $html .= '</button>';
        
        $html .= '</div>';
        
        // Indicadores de página
        $html .= '<div class="pdf-carousel-indicators">';
        foreach ($paginas_pdf as $index => $pagina) {
            $html .= '<span class="pdf-indicator' . ($index === 0 ? ' active' : '') . '" onclick="goToPDFPage(' . $index . ')" data-page="' . $index . '"></span>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Descripción
    $html .= '<div class="code-description-card">';
    $html .= '<h3><i class="fas fa-info-circle"></i> Descripción de la oferta</h3>';
    $html .= '<div class="description-body">' . formatear_descripcion_codigo($description) . '</div>';
    $html .= '</div>';
    
    // Cómo usar
    $html .= '<div class="how-to-use-card">';
    $html .= '<h3><i class="fas fa-question-circle"></i> ¿Cómo usar este código?</h3>';
    $html .= '<div class="steps-container">';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">1</div>';
    $html .= '<div class="step-content">';
    if (isset($code_info['is_url']) && $code_info['is_url']) {
        $html .= '<h4>Abre el enlace</h4>';
        $html .= '<p>Haz clic en "Ir a la web" para acceder directamente con el enlace de invitado</p>';
    } else {
        $html .= '<h4>Copia el código</h4>';
        $html .= '<p>Haz clic en "Copiar código" para copiarlo al portapapeles</p>';
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">2</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Ve a ' . $marca_nombre . '</h4>';
    $html .= '<p>Accede a la web oficial de ' . $marca_nombre . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">3</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Aplica el código</h4>';
    $html .= '<p>Pega el código en el campo correspondiente durante el checkout</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>'; // col-md-8
    
    // Sidebar
    $html .= '<div class="col-md-4">';
    
    // Verificar si es VIP
    if (!function_exists('es_usuario_vip')) {
        include_once __DIR__ . '/funciones_usuario.php';
    }
    $es_vip = es_usuario_vip($user_id);

    // Información del usuario
    $vip_border_style = $es_vip ? 'border: 2px solid #ffd700; box-shadow: 0 5px 15px rgba(255, 215, 0, 0.15);' : '';
    $html .= '<div class="user-info-card" style="' . $vip_border_style . '">';
    $html .= '<h4><i class="fas fa-user"></i> Publicado por</h4>';
    $html .= '<div class="user-profile" >';
    $html .= '<a href="' . link_usuario($username, $user_id) . '" class="" style="display:inline-block;text-decoration:none;">';
    if($user_img && !empty($user_img)) {
        $html .= '<img class="user-avatar" src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '">';
    } else {
        $html .= '<div class="user-avatar-placeholder">';
        if(isset($user_info['iniciales']) && !empty($user_info['iniciales'])) {
            $html .= '<span>' . htmlspecialchars($user_info['iniciales']) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '';
    $html .= '<div class="user-details">';
    $html .= '<div class="user-name">' . htmlspecialchars($username);
    if ($es_vip) {
        $html .= '<span class="vip-badge-gold" style="margin-left:8px; font-size:0.7em; vertical-align: middle;"><i class="fas fa-crown"></i> VIP</span>';
    }
    $html .= '</div>';
    // Manejar fecha de publicación
    $fecha_texto = '';
    try {
        if ($date instanceof DateTime) {
            $fecha_texto = date('d/m/Y', $date->getTimestamp());
        } elseif ($date instanceof MongoDB\BSON\UTCDateTime) {
            $timestamp = $date->toDateTime()->getTimestamp();
            $fecha_texto = date('d/m/Y', $timestamp);
        } elseif (is_numeric($date)) {
            $fecha_texto = date('d/m/Y', $date);
        } elseif (is_string($date) && !empty($date)) {
            $timestamp = strtotime($date);
            $fecha_texto = $timestamp ? date('d/m/Y', $timestamp) : date('d/m/Y');
        } else {
            $fecha_texto = date('d/m/Y');
        }
    } catch (Exception $e) {
        $fecha_texto = date('d/m/Y');
    }
    $html .= '<div class="user-date">Publicado el ' . htmlspecialchars($fecha_texto) . '</div></a>';
    $html .= '</div>'; // closes user-details
    $html .= '</div>'; // closes user-profile (flex container)
    
    // Botón de chat (solo si el usuario está logueado y no es el propietario)
    $usuario_logueado = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $es_propietario_chat = false;
    
    if ($usuario_logueado && !empty($user_id)) {
        $es_propietario_chat = (trim($_SESSION['user_id']) == trim($user_id));
    }
    
    // Botón de chat: Mostrar siempre excepto si es el propietario
    // Si no está logueado, pedir login
    
    if (!$es_propietario_chat && !empty($user_id) && $user_id !== null) {
        // Escapar correctamente para JavaScript
        $user_id_js = json_encode((string)$user_id);
        $username_js = json_encode($username);
        $user_img_js = json_encode($user_img);
        $user_id_js_attr = htmlspecialchars($user_id_js, ENT_QUOTES, 'UTF-8');
        $username_js_attr = htmlspecialchars($username_js, ENT_QUOTES, 'UTF-8');
        $user_img_js_attr = htmlspecialchars($user_img_js, ENT_QUOTES, 'UTF-8');
        $username_title = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        
        $default_msg_js = json_encode("¡Hola! He visto tu código de " . $marca_nombre . " de " . $benefit . "€ y me gustaría conseguirlo. ¿Me ayudas con el proceso?");
        
        // Contexto del código para el chat
        $codigo_id_js = json_encode((string)$code_id);
        $marca_slug_js = json_encode($brand);
        $beneficio_js = json_encode((int)$benefit);
        $marca_nombre_js = json_encode($marca_nombre);
        
        $onclick_action = '';
        if ($usuario_logueado) {
            // Pasar contexto del código: openChatModal(userId, userName, userImg, defaultMsg, codigoContexto)
            $contexto_obj = '{codigoId:' . htmlspecialchars($codigo_id_js, ENT_QUOTES, 'UTF-8') . 
                           ',marcaSlug:' . htmlspecialchars($marca_slug_js, ENT_QUOTES, 'UTF-8') . 
                           ',beneficio:' . htmlspecialchars($beneficio_js, ENT_QUOTES, 'UTF-8') . 
                           ',marcaNombre:' . htmlspecialchars($marca_nombre_js, ENT_QUOTES, 'UTF-8') . '}';
            $onclick_action = 'if(typeof openChatModal === \'function\') { openChatModal(' . $user_id_js_attr . ', ' . $username_js_attr . ', ' . $user_img_js_attr . ', ' . htmlspecialchars($default_msg_js, ENT_QUOTES, 'UTF-8') . ', ' . $contexto_obj . '); } else { window.location.href=\'/chat?usuario=' . $user_id_js_attr . '\'; }';
        } else {
            // Si no está logueado, abrir modal de login
            $onclick_action = 'if(typeof showLoginModal === \'function\') { showLoginModal(); } else if(typeof openLoginModalWithRedirect === \'function\') { openLoginModalWithRedirect(window.location.href); } else { window.location.href=\'/login\'; } return false;';
        }

        // Nueva caja de promoción de chat integrada *dentro* de la tarjeta de usuario
        $html .= '<div class="integrated-promo-box">';
        $html .= '<div class="promo-header"><i class="fas fa-magic"></i> ¿Quieres asegurar tus ' . $benefit . '€?</div>';
        
        if ($usuario_logueado) {
            $html .= '<p class="promo-desc">El autor de este código está esperando para ayudarte. <strong>Envíale un mensaje</strong> ahora mismo para que te guíe paso a paso y no pierdas tu recompensa.</p>';
        } else {
            $html .= '<p class="promo-desc">Los usuarios que chatean con el autor tienen un 90% más de éxito al canjear el código.</p>';
        }
        
        $html .= '<div class="chat-incentive-wrapper" style="margin-top: 15px;">';
        $html .= '<button class="btn-chat-user pulse-chat" style="width: 100%;" onclick="' . $onclick_action . '" title="Chatear con ' . $username_title . '">';
        $html .= '<i class="fas fa-comments"></i> Chatear con ' . htmlspecialchars($username);
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>'; // closes integrated-promo-box
    }
    
    $html .= '</div>'; // closes user-info-card

    // Si el usuario actual es el propietario, mostrar menú de edición
    if ($es_propietario) {
        $html .= '<div class="owner-actions-card">';
        $html .= '<h4><i class="fas fa-cog"></i> Gestionar mi código</h4>';
        $html .= '<div class="owner-buttons">';
        // Destacar primero y más visible
        $html .= '<a class="btn-highlight" href="/destacar_codigo?codigo=' . urlencode((string)$code_id) . '">';
        $html .= '<i class="fas fa-star"></i> Destacar';
        $html .= '</a>';
        // Crear Promoción
        $html .= '<a class="btn-promocion" href="/crear-promocion?codigo_id=' . urlencode((string)$code_id) . '">';
        $html .= '<i class="fas fa-tag"></i> Crear Promoción';
        $html .= '</a>';
        // Editar
        $html .= '<a class="btn-edit" href="/modificar_codigo/' . htmlspecialchars((string)$code_id) . '">';
        $html .= '<i class="fas fa-edit"></i> Editar código';
        $html .= '</a>';
        
        // Eliminar
        $html .= '<button class="btn-delete" onclick="confirmDeleteCode(\'' . htmlspecialchars((string)$code_id, ENT_QUOTES, 'UTF-8') . '\')">';
        $html .= '<i class="fas fa-trash-alt"></i> Eliminar';
        $html .= '</button>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Script para eliminación
        $html .= '<script>
        function confirmDeleteCode(id) {
            Swal.fire({
                title: "¿Estás seguro?",
                text: "No podrás revertir esta acción. El código se eliminará permanentemente.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteCode(id);
                }
            });
        }
        
        function deleteCode(id) {
            $.ajax({
                url: "/ajax/borrar_codigo.php",
                type: "POST",
                data: { id_codigo: id },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            "¡Eliminado!",
                            "Tu código ha sido eliminado.",
                            "success"
                        ).then(() => {
                            window.location.href = "/mis-anuncios";
                        });
                    } else {
                        Swal.fire(
                            "Error",
                            response.error || "No se pudo eliminar el código",
                            "error"
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        "Error",
                        "Hubo un problema de conexión",
                        "error"
                    );
                }
            });
        }
        </script>
        
        <style>
        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-delete:hover {
            background: #dc3545;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }
        </style>';
    }

    // Asegurar que la sesión esté disponible
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION)) {
        session_start();
    }

    // Verificar sesión de múltiples formas: $_SESSION, $usuario_actual_id, y $GLOBALS
    $user_id_para_favorito = '';
    if (!empty($usuario_actual_id)) {
        $user_id_para_favorito = $usuario_actual_id;
    } elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $user_id_para_favorito = trim($_SESSION['user_id']);
    } elseif (isset($GLOBALS['current_user_id']) && !empty($GLOBALS['current_user_id'])) {
        $user_id_para_favorito = trim($GLOBALS['current_user_id']);
    }

    // Calcular stats inline
    $total_clicks = isset($codigo['totalclicks']) ? (int)$codigo['totalclicks'] : 0;
    $dias_activo = 0;
    try {
        if ($date instanceof MongoDB\BSON\UTCDateTime) {
            $dias_activo = (int)((time() - $date->toDateTime()->getTimestamp()) / 86400);
        } elseif ($date instanceof DateTime) {
            $dias_activo = (int)((time() - $date->getTimestamp()) / 86400);
        } elseif (is_string($date) && !empty($date)) {
            $ts = strtotime($date);
            if ($ts) { $dias_activo = (int)((time() - $ts) / 86400); }
        }
    } catch (Exception $e) {}
    if ($dias_activo < 0) { $dias_activo = 0; }

    // Tarjeta combinada: estadísticas + compartir + favoritos
    $code_id_attr = htmlspecialchars((string)$code_id, ENT_QUOTES, 'UTF-8');
    $html .= '<div class="stats-card">';
    $html .= '<h4><i class="fas fa-chart-bar"></i> Estadísticas</h4>';
    $html .= '<div class="stats-grid">';
    $html .= '<div class="stat-item"><div class="stat-lbl"><i class="fas fa-eye"></i> Vistas</div><div class="stat-num">' . number_format($total_clicks, 0, ',', '.') . '</div></div>';
    $html .= '<div class="stat-item"><div class="stat-lbl"><i class="fas fa-clock"></i> Días activo</div><div class="stat-num">' . $dias_activo . '</div></div>';
    $html .= '<div class="stat-item stat-highlight"><div class="stat-lbl"><i class="fas fa-coins"></i> Beneficio</div><div class="stat-num">' . htmlspecialchars((string)$benefit, ENT_QUOTES, 'UTF-8') . '€</div></div>';
    $html .= '</div>';
    $html .= '<button class="btn-stats" data-codigo-id="' . $code_id_attr . '" onclick="viewStatsModal(\'' . $code_id_attr . '\'); return false;">';
    $html .= '<i class="fas fa-chart-line"></i> Ver estadísticas detalladas';
    $html .= '</button>';
    $html .= '<button class="btn-share-stats" onclick="shareCode()">';
    $html .= '<i class="fas fa-share-alt"></i> Compartir código';
    $html .= '</button>';

    if (!empty($user_id_para_favorito)) {
        include_once __DIR__ . '/funciones_favoritos.php';
        if (function_exists('es_favorito')) {
            try {
                $is_favorito = es_favorito($user_id_para_favorito, $code_id);
                $favorito_class = $is_favorito ? 'active' : '';
                $html .= '<button class="favorite-btn ' . $favorito_class . '" data-codigo-id="' . htmlspecialchars($code_id) . '" title="' . ($is_favorito ? 'Quitar de favoritos' : 'Añadir a favoritos') . '">';
                $html .= '<i class="fas fa-heart"></i> ' . ($is_favorito ? 'En favoritos' : 'Añadir a favoritos');
                $html .= '</button>';
            } catch (Exception $e) {
                $html .= '<button class="favorite-btn" data-codigo-id="' . htmlspecialchars($code_id) . '" title="Añadir a favoritos">';
                $html .= '<i class="fas fa-heart"></i> Añadir a favoritos';
                $html .= '</button>';
            }
        } else {
            $html .= '<button class="favorite-btn" data-codigo-id="' . htmlspecialchars($code_id) . '" title="Añadir a favoritos">';
            $html .= '<i class="fas fa-heart"></i> Añadir a favoritos';
            $html .= '</button>';
        }
    }
    $html .= '</div>';


    $html .= '</div>'; // col-md-4
    $html .= '</div>'; // row
    $html .= '</div>'; // container
    $html .= '</div>'; // container-fluid
    
    // Estilos rediseñados — alineados con sistema de tokens v2 (rojo CodigoAmigo)
    $html .= '<style>
    /* ===== Tokens locales (scoped al detalle) ===== */
    .main_entremedio {
        --cad-bg: #f5f7fa;
        --cad-surface: #ffffff;
        --cad-surface-2: #f8fafc;
        --cad-border: #e5e7eb;
        --cad-text: #0f172a;
        --cad-text-2: #334155;
        --cad-text-3: #64748b;
        --cad-brand: #E30613;
        --cad-brand-2: #b3000f;
        --cad-brand-soft: #fef2f3;
        --cad-success: #16a34a;
        --cad-warning: #f59e0b;
        --cad-shadow-sm: 0 2px 8px rgba(15,23,42,0.06);
        --cad-shadow: 0 6px 20px rgba(15,23,42,0.08);
        --cad-r-md: 12px;
        --cad-r-lg: 16px;
        background: var(--cad-bg);
        color: var(--cad-text);
        padding-bottom: 32px;
    }

    /* ===== Hero ===== */
    .code-detail-hero {
        background: var(--cad-surface);
        border-bottom: 1px solid var(--cad-border);
        padding: 18px 0 22px;
        margin-bottom: 24px;
    }

    .breadcrumb-nav { margin-bottom: 12px; font-size: 13px; }
    .breadcrumb-link {
        color: var(--cad-text-3);
        text-decoration: none;
        transition: color .2s ease;
    }
    .breadcrumb-link:hover { color: var(--cad-brand); text-decoration: none; }
    .breadcrumb-separator { color: var(--cad-text-3); margin: 0 6px; }
    .breadcrumb-current { color: var(--cad-text-2); font-weight: 600; }

    .code-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
    }
    .header-left { display: flex; align-items: center; gap: 14px; }
    .brand-logo {
        width: 56px; height: 56px;
        object-fit: contain;
        background: var(--cad-surface);
        border: 1px solid var(--cad-border);
        border-radius: var(--cad-r-md);
        padding: 6px;
    }
    .header-text h1 {
        color: var(--cad-text);
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0 0 4px 0;
        line-height: 1.2;
    }
    .header-subtitle {
        color: var(--cad-text-3);
        font-size: 0.95rem;
        margin: 0 0 8px 0;
    }
    .header-badges { display: flex; flex-wrap: wrap; gap: 6px; }
    .featured-badge, .verified-badge {
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .featured-badge { background: var(--cad-brand); color: #fff; }
    .verified-badge { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .verified-badge i { color: var(--cad-success); }

    .btn-back, .btn-share {
        background: var(--cad-surface);
        color: var(--cad-text-2);
        border: 1px solid var(--cad-border);
        padding: 8px 14px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all .2s ease;
    }
    .btn-back:hover, .btn-share:hover {
        border-color: var(--cad-brand);
        color: var(--cad-brand);
        background: var(--cad-brand-soft);
    }

    /* ===== Cards comunes ===== */
    .code-main-card,
    .code-description-card,
    .how-to-use-card,
    .pdf-carousel-card,
    .user-info-card,
    .owner-actions-card,
    .stats-card,
    .action-buttons-card {
        background: var(--cad-surface);
        border: 1px solid var(--cad-border);
        border-radius: var(--cad-r-lg);
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: var(--cad-shadow-sm);
    }

    .code-main-card h3,
    .code-description-card h3,
    .how-to-use-card h3,
    .pdf-carousel-card h3,
    .user-info-card h4,
    .owner-actions-card h4,
    .stats-card h4 {
        color: var(--cad-text);
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 18px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .code-main-card h3 i,
    .code-description-card h3 i,
    .how-to-use-card h3 i,
    .pdf-carousel-card h3 i,
    .user-info-card h4 i,
    .owner-actions-card h4 i,
    .stats-card h4 i { color: var(--cad-brand); }

    /* ===== Beneficio destacado ===== */
    .code-benefit-display {
        text-align: center;
        margin-bottom: 24px;
        padding: 22px;
        background: linear-gradient(135deg, var(--cad-brand), var(--cad-brand-2));
        border-radius: var(--cad-r-lg);
        color: #fff;
    }
    .benefit-amount {
        font-size: 3rem;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 6px;
    }
    .benefit-label {
        font-size: 0.95rem;
        font-weight: 600;
        opacity: 0.95;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ===== Código ===== */
    .code-text {
        font-family: "SF Mono", "Courier New", monospace;
        font-size: 1.8rem;
        font-weight: 800;
        letter-spacing: 3px;
        text-align: center;
        padding: 26px 18px;
        background: var(--cad-surface-2);
        color: var(--cad-text);
        border: 2px dashed var(--cad-brand);
        border-radius: var(--cad-r-md);
        margin-bottom: 18px;
        word-break: break-all;
    }

    .code-url-section {
        background: var(--cad-surface-2);
        border: 1px solid var(--cad-border);
        border-radius: var(--cad-r-md);
        padding: 18px;
        margin-bottom: 18px;
    }
    .code-display-container.code-is-url .code-text { display: none !important; }
    .code-display-container.code-is-url > .btn-copy-code { display: none !important; }

    .url-label {
        color: var(--cad-text-2);
        font-weight: 600;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
    }
    .code-url-link {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--cad-brand);
        text-decoration: none;
        padding: 12px 16px;
        background: var(--cad-surface);
        border: 1px solid var(--cad-brand);
        border-radius: var(--cad-r-md);
        font-weight: 600;
        transition: all .2s ease;
        word-break: break-all;
    }
    .code-url-link:hover {
        background: var(--cad-brand);
        color: #fff;
        text-decoration: none;
    }

    .btn-copy-code {
        background: var(--cad-brand);
        color: #fff;
        border: none;
        padding: 14px 24px;
        border-radius: var(--cad-r-md);
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all .2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
    }
    .btn-copy-code:hover { background: var(--cad-brand-2); transform: translateY(-1px); }

    /* ===== Reveal ===== */
    .code-reveal-wrapper { position: relative; margin-bottom: 18px; }
    .code-blurred {
        padding: 26px 18px;
        background: var(--cad-surface-2);
        border: 2px dashed var(--cad-border);
        border-radius: var(--cad-r-md);
        text-align: center;
        filter: blur(6px);
        user-select: none;
    }
    .code-text-blurred, .code-url-blurred {
        font-family: "SF Mono", "Courier New", monospace;
        font-size: 1.8rem;
        font-weight: 800;
        letter-spacing: 3px;
        color: var(--cad-text);
    }
    .btn-reveal-code {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        background: var(--cad-brand);
        color: #fff;
        border: none;
        padding: 12px 26px;
        border-radius: 999px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 8px 22px rgba(227,6,19,0.35);
        transition: all .2s ease;
    }
    .btn-reveal-code:hover { background: var(--cad-brand-2); transform: translate(-50%, -50%) scale(1.04); }

    /* ===== Descripción ===== */
    .code-description-card p {
        color: var(--cad-text-2);
        font-size: 1rem;
        line-height: 1.7;
        margin: 0;
    }

    /* ===== Pasos ===== */
    .steps-container { display: flex; flex-direction: column; gap: 16px; }
    .step-item { display: flex; align-items: flex-start; gap: 14px; }
    .step-number {
        background: var(--cad-brand-soft);
        color: var(--cad-brand);
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1rem;
        flex-shrink: 0;
        border: 2px solid var(--cad-brand);
    }
    .step-content h4 {
        color: var(--cad-text);
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 4px 0;
    }
    .step-content p {
        color: var(--cad-text-3);
        margin: 0;
        line-height: 1.5;
        font-size: 0.92rem;
    }

    /* ===== Usuario ===== */
    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .user-avatar {
        width: 52px; height: 52px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: var(--cad-surface-2);
        border: 2px solid var(--cad-border);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .user-avatar-placeholder {
        width: 100%; height: 100%;
        background: var(--cad-brand);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.2rem; font-weight: 700;
    }
    .user-details { min-width: 0; }
    .user-profile a,
    .user-profile a:hover,
    .user-profile a:visited { color: var(--cad-text) !important; text-decoration: none !important; }
    .user-name {
        color: var(--cad-text) !important;
        background: transparent !important;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 2px;
        line-height: 1.2;
    }
    .user-date { color: var(--cad-text-3) !important; font-size: 0.85rem; }
    .description-body p { color: var(--cad-text-2); font-size: 1rem; line-height: 1.7; margin: 0 0 12px 0; }
    .description-body p:last-child { margin-bottom: 0; }
    .description-body a { color: var(--cad-brand); text-decoration: underline; word-break: break-all; }

    /* ===== Promo chat integrada ===== */
    .integrated-promo-box {
        margin-top: 14px;
        padding: 14px;
        background: var(--cad-brand-soft);
        border-radius: var(--cad-r-md);
        border-left: 3px solid var(--cad-brand);
    }
    .integrated-promo-box .promo-header {
        font-weight: 700;
        color: var(--cad-brand);
        font-size: 0.98rem;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .integrated-promo-box .promo-desc {
        font-size: 0.88rem;
        color: var(--cad-text-2);
        line-height: 1.45;
        margin: 0;
    }
    .integrated-promo-box .promo-desc strong { color: var(--cad-text); }

    .chat-incentive-wrapper { width: 100%; }
    .btn-chat-user {
        width: 100%;
        background: var(--cad-brand);
        color: #fff;
        border: none;
        padding: 11px 18px;
        border-radius: var(--cad-r-md);
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all .2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-chat-user:hover { background: var(--cad-brand-2); transform: translateY(-1px); }
    .pulse-chat { animation: cad-pulse 2.4s infinite; }
    @keyframes cad-pulse {
        0% { box-shadow: 0 0 0 0 rgba(227,6,19,0.45); }
        70% { box-shadow: 0 0 0 12px rgba(227,6,19,0); }
        100% { box-shadow: 0 0 0 0 rgba(227,6,19,0); }
    }

    /* ===== Acciones owner ===== */
    .owner-buttons { display: flex; flex-direction: column; gap: 10px; }
    .btn-edit, .btn-stats, .btn-highlight, .btn-promocion, .btn-delete {
        padding: 11px 16px;
        border-radius: var(--cad-r-md);
        font-weight: 600;
        font-size: 0.92rem;
        cursor: pointer;
        transition: all .2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid transparent;
        text-decoration: none;
        text-align: left;
    }
    .btn-highlight {
        background: var(--cad-warning);
        color: #fff;
        justify-content: flex-start;
    }
    .btn-highlight:hover { background: #d97706; color: #fff; text-decoration: none; transform: translateY(-1px); }

    .btn-promocion {
        background: var(--cad-brand) !important;
        color: #fff !important;
        justify-content: flex-start;
    }
    .btn-promocion:hover { background: var(--cad-brand-2) !important; color: #fff !important; text-decoration: none; transform: translateY(-1px); }

    .btn-edit {
        background: var(--cad-surface);
        color: var(--cad-text-2);
        border-color: var(--cad-border);
        justify-content: flex-start;
    }
    .btn-edit:hover { background: var(--cad-surface-2); color: var(--cad-text); text-decoration: none; border-color: var(--cad-text-3); }

    .btn-delete {
        background: var(--cad-surface);
        color: #dc2626;
        border-color: #fecaca;
        justify-content: flex-start;
    }
    .btn-delete:hover { background: #fef2f2; color: #b91c1c; border-color: #dc2626; }

    /* ===== Stats ===== */
    .stats-card { padding: 20px !important; }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 14px;
    }
    .stat-item {
        background: var(--cad-surface-2);
        border-radius: var(--cad-r-md);
        padding: 12px 6px;
        text-align: center;
        border: 1px solid var(--cad-border);
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 0;
    }
    .stat-item .stat-lbl {
        font-size: 0.65rem;
        color: var(--cad-text-3);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        margin-bottom: 6px;
        width: 100%;
        text-align: center;
    }
    .stat-item .stat-lbl i { margin-right: 3px; }
    .stat-item .stat-num {
        font-size: clamp(1.1rem, 2.4vw, 1.5rem);
        font-weight: 800;
        color: var(--cad-text);
        line-height: 1.05;
        width: 100%;
        text-align: center;
        word-break: break-word;
    }
    .stat-item.stat-highlight {
        background: var(--cad-brand-soft);
        border-color: var(--cad-brand);
    }
    .stat-item.stat-highlight .stat-num,
    .stat-item.stat-highlight .stat-lbl,
    .stat-item.stat-highlight .stat-lbl i { color: var(--cad-brand); }

    .btn-stats {
        background: var(--cad-text);
        color: #fff;
        width: 100%;
        justify-content: center;
        border: none;
    }
    .btn-stats:hover { background: #1e293b; transform: translateY(-1px); }

    .btn-share-stats {
        width: 100%;
        margin-top: 10px;
        padding: 11px 16px;
        border-radius: var(--cad-r-md);
        border: 1px solid var(--cad-brand);
        background: var(--cad-surface);
        color: var(--cad-brand);
        font-weight: 700;
        font-size: 0.92rem;
        cursor: pointer;
        transition: all .2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-share-stats:hover { background: var(--cad-brand); color: #fff; }

    /* ===== Favorito ===== */
    .favorite-btn {
        width: 100% !important;
        height: auto !important;
        margin-top: 10px !important;
        margin-bottom: 0 !important;
        padding: 11px 16px !important;
        border-radius: var(--cad-r-md) !important;
        background: var(--cad-surface) !important;
        border: 1px solid var(--cad-border) !important;
        color: var(--cad-text-2) !important;
        font-weight: 600 !important;
        font-size: 0.92rem !important;
        cursor: pointer;
        transition: all .2s ease;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px;
    }
    .favorite-btn i { font-size: 14px !important; color: inherit !important; margin-right: 6px !important; }
    .favorite-btn:hover {
        border-color: var(--cad-brand) !important;
        color: var(--cad-brand) !important;
        background: var(--cad-brand-soft) !important;
    }
    .favorite-btn.active {
        background: var(--cad-brand) !important;
        border-color: var(--cad-brand) !important;
        color: #fff !important;
    }
    .favorite-btn.active i { color: #fff !important; }
    .favorite-btn.active:hover { background: var(--cad-brand-2) !important; border-color: var(--cad-brand-2) !important; }

    /* ===== Botones principales ===== */
    .action-buttons-card { padding: 16px; }
    .btn-primary {
        width: 100%;
        padding: 13px 22px;
        border-radius: var(--cad-r-md);
        font-weight: 700;
        font-size: 0.98rem;
        cursor: pointer;
        transition: all .2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        background: var(--cad-brand);
        color: #fff;
    }
    .btn-primary:hover { background: var(--cad-brand-2); transform: translateY(-1px); }

    .btn-secondary {
        width: 100%;
        padding: 13px 22px;
        border-radius: var(--cad-r-md);
        font-weight: 700;
        background: var(--cad-surface-2);
        color: var(--cad-text-2);
        border: 1px solid var(--cad-border);
    }
    .btn-secondary:hover { background: var(--cad-border); }

    /* ===== PDF Carousel ===== */
    .pdf-carousel-container { position: relative; }
    .pdf-carousel-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: var(--cad-surface-2);
        border-radius: var(--cad-r-md);
        padding: 16px;
        min-height: 400px;
    }
    .pdf-pages-container { width: 100%; display: flex; align-items: center; justify-content: center; }
    .pdf-page-item { display: none; width: 100%; text-align: center; opacity: 0; transition: opacity .25s ease; }
    .pdf-page-item.active { display: block; opacity: 1; }
    .pdf-page-image {
        max-width: 100%; height: auto;
        border-radius: var(--cad-r-md);
        box-shadow: var(--cad-shadow);
        margin: 0 auto; display: block;
        max-height: 560px; object-fit: contain;
    }
    .pdf-page-number { margin-top: 12px; color: var(--cad-text-3); font-size: 0.85rem; font-weight: 600; }
    .pdf-carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: var(--cad-surface);
        color: var(--cad-text-2);
        border: 1px solid var(--cad-border);
        width: 44px; height: 44px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
        transition: all .2s ease;
    }
    .pdf-carousel-btn:hover { background: var(--cad-brand); color: #fff; border-color: var(--cad-brand); }
    .pdf-carousel-prev { left: 12px; }
    .pdf-carousel-next { right: 12px; }

    .pdf-carousel-indicators {
        display: flex;
        justify-content: center;
        gap: 6px;
        margin-top: 18px;
        flex-wrap: wrap;
    }
    .pdf-indicator {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--cad-border);
        cursor: pointer;
        transition: all .2s ease;
    }
    .pdf-indicator:hover { background: var(--cad-brand); transform: scale(1.2); }
    .pdf-indicator.active { background: var(--cad-brand); width: 22px; border-radius: 4px; }

    .pdf-page-counter {
        position: absolute;
        bottom: 14px; right: 14px;
        background: rgba(15,23,42,0.78);
        color: #fff;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        backdrop-filter: blur(6px);
    }

    /* ===== Help awareness (legacy, mantenido) ===== */
    .help-awareness-card {
        background: var(--cad-surface);
        border-radius: var(--cad-r-lg);
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid var(--cad-border);
        border-left: 4px solid var(--cad-brand);
    }
    .help-awareness-card h4 {
        color: var(--cad-text);
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .help-awareness-card p {
        font-size: 0.92rem;
        color: var(--cad-text-2) !important;
        line-height: 1.5;
        margin-bottom: 12px;
        background: transparent !important;
    }
    .help-status-logged {
        background: #f0fdf4;
        color: #166534;
        padding: 10px 12px;
        border-radius: var(--cad-r-md);
        font-size: 0.88rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .help-promo-box {
        background: var(--cad-brand-soft);
        border: 1px dashed var(--cad-brand);
        padding: 14px;
        border-radius: var(--cad-r-md);
        text-align: center;
    }
    .help-promo-box p { margin-bottom: 8px !important; color: var(--cad-brand-2) !important; }
    .btn-help-login {
        background: var(--cad-brand);
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: var(--cad-r-md);
        font-weight: 700;
        width: 100%;
        cursor: pointer;
        transition: all .2s ease;
    }
    .btn-help-login:hover { background: var(--cad-brand-2); }

    /* ===== VIP badge ===== */
    .vip-badge-gold {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #1e3a5f;
        padding: 2px 8px;
        border-radius: 999px;
        font-weight: 700;
    }

    /* ===== Responsive ===== */
    @media (max-width: 768px) {
        .breadcrumb-nav { display: none; }
        .code-detail-header { flex-direction: column; text-align: center; }
        .header-left { flex-direction: column; text-align: center; }
        .header-text h1 { font-size: 1.3rem; }
        .benefit-amount { font-size: 2.4rem; }
        .code-text, .code-text-blurred, .code-url-blurred {
            font-size: 1.4rem;
            letter-spacing: 2px;
        }
        .code-main-card, .code-description-card, .how-to-use-card, .pdf-carousel-card,
        .user-info-card, .owner-actions-card, .stats-card, .action-buttons-card {
            padding: 18px;
        }
    }
    @media (max-width: 480px) {
        .code-detail-hero { padding: 14px 0 18px; }
        .header-text h1 { font-size: 1.2rem; }
        .benefit-amount { font-size: 2rem; }
        .code-text, .code-text-blurred, .code-url-blurred {
            font-size: 1.2rem;
            letter-spacing: 1.5px;
        }
        .step-item { flex-direction: column; text-align: center; align-items: center; }
        .stats-grid { gap: 6px; }
        .stat-item { padding: 10px 4px; }
        .stat-item .stat-num { font-size: 1.1rem; }
        .stat-item .stat-lbl { font-size: 0.6rem; margin-bottom: 4px; }
        .pdf-carousel-wrapper { min-height: 320px; padding: 10px; }
        .pdf-page-image { max-height: 360px; }
        .pdf-carousel-btn { width: 38px; height: 38px; font-size: 0.95rem; }
    }
    </style>';
    
    // Cargar script de favoritos
    $html .= '<script src="/js/favoritos.js?v=' . (file_exists($_SERVER['DOCUMENT_ROOT'] . '/js/favoritos.js') ? filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/favoritos.js') : time()) . '"></script>';
    
    // JavaScript para añadir botón de favoritos si hay usuario logueado
    $html .= '<script>
    // Añadir botón de favoritos si hay usuario logueado
    (function() {
        function addFavoriteButton() {
            // Verificar si hay usuario logueado (según window.serverUserDataBasic del header)
            var userLoggedIn = false;
            var userId = null;
            
            if (typeof window.serverUserDataBasic !== "undefined" && window.serverUserDataBasic !== null) {
                userLoggedIn = true;
                userId = window.serverUserDataBasic.id || null;
            }
            
            // También verificar si hay menú de usuario visible
            var userMenu = document.querySelector("#user-profile, .user-menu, [href*=\'mis-favoritos\']");
            if (userMenu && !userLoggedIn) {
                userLoggedIn = true;
            }
            
            if (userLoggedIn) {
                var statsCard = document.querySelector(".stats-card");
                if (statsCard) {
                    // Verificar si el botón ya existe
                    var existingFavoriteBtn = statsCard.querySelector(".favorite-btn");
                    if (!existingFavoriteBtn) {
                        var codeId = "' . htmlspecialchars($code_id, ENT_QUOTES, 'UTF-8') . '";
                        var favoriteBtn = document.createElement("button");
                        favoriteBtn.className = "favorite-btn";
                        favoriteBtn.setAttribute("data-codigo-id", codeId);
                        favoriteBtn.setAttribute("title", "Añadir a favoritos");
                        favoriteBtn.innerHTML = \'<i class="fas fa-heart"></i> Añadir a favoritos\';

                        // Añadir al final de la tarjeta (después de "Ver estadísticas")
                        statsCard.appendChild(favoriteBtn);
                        
                        // Inicializar el handler de favoritos después de un pequeño delay
                        // El script favoritos.js ya añade los handlers automáticamente
                        // Solo necesitamos esperar a que se cargue
                        setTimeout(function() {
                            // Disparar un evento personalizado para que favoritos.js lo detecte
                            var event = new CustomEvent("favoriteButtonAdded", {
                                detail: { button: favoriteBtn }
                            });
                            document.dispatchEvent(event);
                            
                            // También intentar inicializar manualmente si existe la función
                            if (typeof initFavoritos === "function") {
                                initFavoritos();
                            }
                        }, 200);
                    }
                }
            }
        }
        
        // Ejecutar cuando el DOM esté listo
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", addFavoriteButton);
        } else {
            addFavoriteButton();
        }
        
        // También intentar después de que se cargue el header (por si el usuario se loguea después)
        setTimeout(addFavoriteButton, 500);
    })();
    </script>';
    
    // Agregar JavaScript para funcionalidades
    $html .= '<script>
    function copyCode() {
        const codeText = document.getElementById("codeText");
        if (!codeText) {
            alert("Error: No se pudo encontrar el elemento del código");
            return;
        }

        const text = codeText.textContent || codeText.innerText;

        if (!text || text.trim() === "") {
            alert("Error: El código está vacío");
            return;
        }

        navigator.clipboard.writeText(text).then(function() {
            // Cambiar el texto del botón temporalmente
            const btn = document.querySelector(".btn-copy-code");
            if (btn) {
                const originalText = btn.innerHTML;
                const isUrl = text.startsWith("http");
                const successText = isUrl ? "¡Enlace copiado!" : "¡Código copiado!";
                btn.innerHTML = "<i class=\"fas fa-check\"></i> " + successText;
                btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";

                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = "";
                }, 2000);
            }

            // Mostrar notificación adicional
            showNotification(text.startsWith("http") ? "Enlace copiado correctamente al portapapeles" : "Código copiado correctamente al portapapeles");
        }).catch(function(err) {
            alert("Error al copiar " + (text.startsWith("http") ? "el enlace" : "el código") + ". Inténtalo de nuevo.");
        });
    }

    function showNotification(message) {
        // Crear elemento de notificación
        const notification = document.createElement("div");
        notification.className = "copy-notification";
        notification.innerHTML = "<i class=\"fas fa-check-circle\"></i> " + message;
        notification.style.position = "fixed";
        notification.style.top = "20px";
        notification.style.right = "20px";
        notification.style.background = "linear-gradient(135deg, #28a745, #20c997)";
        notification.style.color = "white";
        notification.style.padding = "15px 20px";
        notification.style.borderRadius = "10px";
        notification.style.boxShadow = "0 4px 15px rgba(40, 167, 69, 0.3)";
        notification.style.zIndex = "10000";
        notification.style.fontWeight = "600";
        notification.style.animation = "slideInRight 0.3s ease-out";

        document.body.appendChild(notification);

        // Eliminar después de 3 segundos
        setTimeout(function() {
            notification.style.animation = "slideOutRight 0.3s ease-in";
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Animaciones CSS para notificaciones
    if (!document.getElementById("notification-styles")) {
        const notificationStyles = document.createElement("style");
        notificationStyles.id = "notification-styles";
        notificationStyles.textContent = "@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } } @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }";
        document.head.appendChild(notificationStyles);
    }
    
    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = "/";
        }
    }

    function editCode(codeId) {
        // Redirigir a la página de edición del código
        window.location.href = "/modificar_codigo/" + codeId;
    }

    function deleteCode(codeId) {
        console.log("deleteCode llamado con codeId:", codeId);
        
        if (!codeId) {
            alert("Error: ID de código no válido");
            return;
        }
        
        if (!confirm("¿Estás seguro de que quieres eliminar este código? Esta acción no se puede deshacer.")) {
            return;
        }
        
        const formData = new FormData();
        formData.append("metodo", "borrar_codigo");
        formData.append("id_codigo", codeId);
        
        console.log("Enviando petición para eliminar código:", codeId);
        
        fetch("/myphp/ajax_actions.php", { 
            method: "POST", 
            body: formData 
        })
            .then(function(response) {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    throw new Error("HTTP error! status: " + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log("Respuesta recibida:", data);
                if (data.success) {
                    alert(data.message || "Código eliminado correctamente");
                    window.location.href = "/mis-anuncios";
                } else {
                    alert("Error: " + (data.error || "Error desconocido"));
                }
            })
            .catch(function(error) {
                console.error("Error al eliminar código:", error);
                alert("Error al eliminar el código: " + (error.message || "Error desconocido"));
            });
    }
    
    // Asegurar que la función esté disponible globalmente
    window.deleteCode = deleteCode;

    function viewStatsModal(codeId) {
        console.log("viewStatsModal llamado con codeId:", codeId);
        
        if (!codeId) {
            alert("Error: ID de código no válido");
            return;
        }
        
        // Eliminar modal anterior si existe
        const existingModal = document.getElementById("estadisticasModal");
        if (existingModal) {
            existingModal.remove();
        }
        
        // Crear el contenedor del modal usando iframe para cargar el contenido completo
        const modal = document.createElement("div");
        modal.id = "estadisticasModal";
        modal.style.position = "fixed";
        modal.style.top = "0";
        modal.style.left = "0";
        modal.style.width = "100%";
        modal.style.height = "100%";
        modal.style.background = "rgba(0, 0, 0, 0.8)";
        modal.style.zIndex = "10000";
        modal.style.display = "flex";
        modal.style.alignItems = "center";
        modal.style.justifyContent = "center";
        modal.style.padding = "20px";
        modal.style.boxSizing = "border-box";
        
        // Crear iframe para cargar las estadísticas
        const iframe = document.createElement("iframe");
        iframe.src = "/estadisticas?codigo=" + encodeURIComponent(codeId);
        iframe.style.width = "100%";
        iframe.style.maxWidth = "900px";
        iframe.style.height = "90vh";
        iframe.style.border = "none";
        iframe.style.borderRadius = "12px";
        iframe.style.background = "white";
        
        // Botón de cerrar
        const closeButton = document.createElement("button");
        closeButton.innerHTML = "&times;";
        closeButton.style.position = "absolute";
        closeButton.style.top = "20px";
        closeButton.style.right = "20px";
        closeButton.style.background = "#E30613";
        closeButton.style.color = "white";
        closeButton.style.border = "none";
        closeButton.style.borderRadius = "50%";
        closeButton.style.width = "40px";
        closeButton.style.height = "40px";
        closeButton.style.fontSize = "1.5rem";
        closeButton.style.cursor = "pointer";
        closeButton.style.zIndex = "10001";
        closeButton.style.display = "flex";
        closeButton.style.alignItems = "center";
        closeButton.style.justifyContent = "center";
        closeButton.style.fontWeight = "bold";
        closeButton.style.lineHeight = "1";
        closeButton.style.transition = "all 0.3s ease";
        
        closeButton.onmouseover = function() {
            this.style.background = "#C40510";
            this.style.transform = "scale(1.1)";
        };
        closeButton.onmouseout = function() {
            this.style.background = "#E30613";
            this.style.transform = "scale(1)";
        };
        
        closeButton.onclick = function() {
            modal.style.opacity = "0";
            setTimeout(function() {
                modal.remove();
                document.body.style.overflow = "";
            }, 300);
        };
        
        modal.appendChild(iframe);
        modal.appendChild(closeButton);
        document.body.appendChild(modal);
        document.body.style.overflow = "hidden";
        
        // Cerrar al hacer clic fuera del iframe
        modal.addEventListener("click", function(e) {
            if (e.target === modal) {
                closeButton.onclick();
            }
        });
        
        // Cerrar con tecla Escape
        const handleEscape = function(e) {
            if (e.key === "Escape" || e.keyCode === 27) {
                closeButton.onclick();
                document.removeEventListener("keydown", handleEscape);
            }
        };
        document.addEventListener("keydown", handleEscape);
        
        console.log("Modal de estadísticas mostrado correctamente");
    }
    
    // Asegurar que la función esté disponible globalmente
    window.viewStatsModal = viewStatsModal;
    
    // Agregar event listeners a los botones de estadísticas como respaldo
    function initStatsButtons() {
        const statsButtons = document.querySelectorAll(".btn-stats");
        statsButtons.forEach(function(btn) {
            const codigoId = btn.getAttribute("data-codigo-id");
            if (codigoId && !btn.hasAttribute("data-stats-initialized")) {
                // Marcar como inicializado
                btn.setAttribute("data-stats-initialized", "true");
                // Remover onclick inline y agregar event listener
                btn.removeAttribute("onclick");
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    viewStatsModal(codigoId);
                    return false;
                });
            }
        });
    }
    
    // Inicializar cuando el DOM esté listo o inmediatamente si ya está listo
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initStatsButtons);
    } else {
        initStatsButtons();
    }

    function viewMyCodes() {
        // Redirigir a la página de mis códigos
        window.location.href = "/mis_codigos";
    }

    function showLoadingModal() {
        const loadingModal = document.createElement("div");
        loadingModal.id = "loadingModal";
        loadingModal.style.cssText = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; display: flex; align-items: center; justify-content: center;";
        const loadingContent = document.createElement("div");
        loadingContent.style.cssText = "background: white; padding: 30px; border-radius: 10px; text-align: center;";
        const spinner = document.createElement("div");
        spinner.style.cssText = "width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #E30613; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px;";
        const loadingText = document.createElement("p");
        loadingText.style.cssText = "margin: 0; color: #333;";
        loadingText.textContent = "Cargando estadísticas...";
        loadingContent.appendChild(spinner);
        loadingContent.appendChild(loadingText);
        loadingModal.appendChild(loadingContent);
        if (!document.getElementById("loading-styles")) {
            const style = document.createElement("style");
            style.id = "loading-styles";
            style.textContent = "@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }";
            document.head.appendChild(style);
        }
        document.body.appendChild(loadingModal);
    }

    function hideLoadingModal() {
        const loadingModal = document.getElementById("loadingModal");
        if (loadingModal) {
            loadingModal.remove();
        }
    }
    
    function shareCode() {
        const url = window.location.href;
        const title = document.title;
        
        if (navigator.share) {
            navigator.share({
                title: "Código de descuento",
                text: "Mira este código de descuento que encontré",
                url: window.location.href
            }).catch(function(err) {
                if (err.name !== "AbortError") {
                    console.error("Error sharing:", err);
                }
            });
        } else {
            // Fallback para navegadores que no soportan Web Share API
            navigator.clipboard.writeText(url).then(function() {
                alert("Enlace copiado al portapapeles");
            });
        }
    }
    
    function toggleFavorite() {
        // Implementar funcionalidad de favoritos
        alert("Funcionalidad de favoritos próximamente");
    }
    
    // Funciones del carrusel de PDF
    let currentPDFPage = 0;
    let totalPDFPages = 0;
    
    function initPDFCarousel() {
        const pages = document.querySelectorAll(".pdf-page-item");
        totalPDFPages = pages.length;
        
        if (totalPDFPages > 0) {
            updatePDFButtons();
            updatePDFCounter();
        }
    }
    
    function movePDFPage(direction) {
        const pages = document.querySelectorAll(".pdf-page-item");
        const indicators = document.querySelectorAll(".pdf-indicator");
        
        if (pages.length === 0) return;
        
        // Remover clase active de la página actual
        pages[currentPDFPage].classList.remove("active");
        if (indicators[currentPDFPage]) {
            indicators[currentPDFPage].classList.remove("active");
        }
        
        // Calcular nueva página
        currentPDFPage += direction;
        
        if (currentPDFPage < 0) {
            currentPDFPage = totalPDFPages - 1;
        } else if (currentPDFPage >= totalPDFPages) {
            currentPDFPage = 0;
        }
        
        // Agregar clase active a la nueva página
        pages[currentPDFPage].classList.add("active");
        if (indicators[currentPDFPage]) {
            indicators[currentPDFPage].classList.add("active");
        }
        
        updatePDFButtons();
        
        // Scroll suave a la página
        pages[currentPDFPage].scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
    
    function goToPDFPage(pageIndex) {
        const pages = document.querySelectorAll(".pdf-page-item");
        const indicators = document.querySelectorAll(".pdf-indicator");
        
        if (pageIndex < 0 || pageIndex >= pages.length) return;
        
        // Remover clase active de la página actual
        pages[currentPDFPage].classList.remove("active");
        if (indicators[currentPDFPage]) {
            indicators[currentPDFPage].classList.remove("active");
        }
        
        currentPDFPage = pageIndex;
        
        // Agregar clase active a la nueva página
        pages[currentPDFPage].classList.add("active");
        if (indicators[currentPDFPage]) {
            indicators[currentPDFPage].classList.add("active");
        }
        
        updatePDFButtons();
        updatePDFCounter();
        
        // Scroll suave a la página
        pages[currentPDFPage].scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
    
    function updatePDFCounter() {
        const counter = document.getElementById("pdfPageCounter");
        if (counter && totalPDFPages > 0) {
            counter.textContent = (currentPDFPage + 1) + " / " + totalPDFPages;
        }
    }
    
    function updatePDFButtons() {
        const prevBtn = document.getElementById("pdfPrevBtn");
        const nextBtn = document.getElementById("pdfNextBtn");
        
        if (prevBtn && nextBtn) {
            prevBtn.style.opacity = totalPDFPages > 1 ? "1" : "0.5";
            nextBtn.style.opacity = totalPDFPages > 1 ? "1" : "0.5";
            prevBtn.style.cursor = totalPDFPages > 1 ? "pointer" : "not-allowed";
            nextBtn.style.cursor = totalPDFPages > 1 ? "pointer" : "not-allowed";
        }
    }
    
    // Inicializar carrusel cuando la página esté lista
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initPDFCarousel);
    } else {
        initPDFCarousel();
    }
    
    // Navegación con teclado
    document.addEventListener("keydown", function(e) {
        const pdfCarousel = document.getElementById("pdfCarousel");
        if (pdfCarousel && pdfCarousel.offsetParent !== null) {
            if (e.key === "ArrowLeft") {
                e.preventDefault();
                movePDFPage(-1);
            } else if (e.key === "ArrowRight") {
                e.preventDefault();
                movePDFPage(1);
            }
        }
    });';
    
    $html .= '
    </script>';
    
    return $html;
}
?>

