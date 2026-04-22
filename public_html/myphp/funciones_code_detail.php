<?php
// Incluir funciones de PDF
include_once __DIR__ . '/funciones_pdf.php';

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
    if($destacado) {
        $html .= '<span class="featured-badge"><i class="fas fa-star"></i> Destacado</span>';
    }
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
    $html .= '<p>' . htmlspecialchars($description) . '</p>';
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

    // VIP Awareness Banner (solo para usuarios logueados que no son VIP)
    $current_user_is_vip = ($usuario_logueado && function_exists('es_usuario_vip')) ? es_usuario_vip($_SESSION['user_id']) : false;
    if ($usuario_logueado && !$current_user_is_vip) {
        $html .= '<div class="vip-awareness-banner" style="background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); padding: 20px; border-radius: 15px; margin-bottom: 20px;">';
        $html .= '<div class="vip-awareness-content" style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">';
        $html .= '<div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">';
        $html .= '<i class="fas fa-crown" style="color: white; font-size: 24px;"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h4 style="color: white; font-weight: 700; margin: 0 0 5px 0; font-size: 1rem;">¿Publicas códigos de referido?</h4>';
        $html .= '<p style="color: rgba(255,255,255,0.9); margin: 0; font-size: 0.85rem; line-height: 1.4;">Hazte VIP y contacta directamente con usuarios que ven tus códigos. Badge dorado + 10€/mes de saldo.</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<a href="/public/mis_viewers.php" class="btn-vip-cta" style="display: inline-flex; align-items: center; gap: 8px; background: white; color: #E30613; border: none; padding: 10px 20px; border-radius: 25px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 0.9rem; width: 100%; justify-content: center;">';
        $html .= '<i class="fas fa-crown"></i> Hazte VIP — 9,99€/mes';
        $html .= '</a>';
        $html .= '</div>';
    }



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

    // Botón de estadísticas visible para todos (el gráfico es público; el dueño verá además los usuarios)
    $html .= '<div class="stats-card">';
    $html .= '<h4><i class="fas fa-chart-bar"></i> Estadísticas</h4>';
    $html .= '<button class="btn-stats" data-codigo-id="' . htmlspecialchars((string)$code_id, ENT_QUOTES, 'UTF-8') . '" onclick="viewStatsModal(\'' . htmlspecialchars((string)$code_id, ENT_QUOTES, 'UTF-8') . '\'); return false;">';
    $html .= '<i class="fas fa-chart-line"></i> Ver estadísticas';
    $html .= '</button>';
    $html .= '</div>';


    // Información adicional
    $html .= '<div class="code-info-card">';
    $html .= '<h4><i class="fas fa-shield-alt"></i> Información del código</h4>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Estado:</span>';
    $html .= '<span class="info-value verified"><i class="fas fa-check-circle"></i> Verificado</span>';
    $html .= '</div>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Beneficio:</span>';
    $html .= '<span class="info-value">' . $benefit . '€</span>';
    $html .= '</div>';

    $html .= '</div>';
    
    // Botones de acción
    $html .= '<div class="action-buttons-card">';
    
    // Botón de favoritos (solo si el usuario está logueado)
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
                // Si hay error, mostrar el botón de todas formas
                $html .= '<button class="favorite-btn" data-codigo-id="' . htmlspecialchars($code_id) . '" title="Añadir a favoritos">';
                $html .= '<i class="fas fa-heart"></i> Añadir a favoritos';
                $html .= '</button>';
            }
        } else {
            // Si la función no existe, mostrar el botón de todas formas
            $html .= '<button class="favorite-btn" data-codigo-id="' . htmlspecialchars($code_id) . '" title="Añadir a favoritos">';
            $html .= '<i class="fas fa-heart"></i> Añadir a favoritos';
            $html .= '</button>';
        }
    }
    
    if (isset($code_info['is_url']) && $code_info['is_url']) {
        if ($mostrar_reveal) {
            $html .= '<button class="btn-primary btn-auto-reveal-url" onclick="if(document.querySelector(\'.btn-reveal-code\')){document.querySelector(\'.btn-reveal-code\').click();}">';
            $html .= '<i class="fas fa-external-link-alt"></i> Ir a la web';
            $html .= '</button>';
        } else {
            $urlTrimmed = trim((string)($code_info['url'] ?? ''));
            $html .= '<button class="btn-primary" onclick="window.open(\'' . htmlspecialchars($urlTrimmed, ENT_QUOTES, 'UTF-8') . '\', \'_blank\')">';
            $html .= '<i class="fas fa-external-link-alt"></i> Ir a la web';
            $html .= '</button>';
        }
    } else {
        $html .= '<button class="btn-primary" onclick="copyCode()">';
        $html .= '<i class="fas fa-copy"></i> Copiar código';
        $html .= '</button>';
    }
    $html .= '<button class="btn-secondary" onclick="shareCode()">';
    $html .= '<i class="fas fa-share"></i> Compartir';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</div>'; // col-md-4
    $html .= '</div>'; // row
    $html .= '</div>'; // container
    $html .= '</div>'; // container-fluid
    
    // Agregar estilos CSS increíbles para el nuevo diseño
    $html .= '<style>
    /* Hero Section */
    .code-detail-hero {
        background: linear-gradient(135deg, #ff8a3d 0%, #ff4f0f 100%);
        padding: 20px 0;
        margin-bottom: 30px;
    }
    
    .breadcrumb-nav {
        margin-bottom: 15px;
    }
    
    .breadcrumb-link {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 13px;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-link:hover {
        color: white;
        text-decoration: none;
    }
    
    .breadcrumb-separator {
        color: rgba(255, 255, 255, 0.6);
        margin: 0 8px;
        font-size: 12px;
    }
    
    .breadcrumb-current {
        color: white;
        font-weight: 600;
        font-size: 13px;
    }
    
    .code-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .brand-logo {
        width: 50px;
        height: 50px;
        object-fit: contain;
        background: white;
        border-radius: 12px;
        padding: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .header-text h1 {
        color: white;
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 5px 0;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .header-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1rem;
        margin: 0 0 8px 0;
    }
    
    .featured-badge {
        background: #E30613;
        color: white;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 4px 10px rgba(227, 6, 19, 0.3);
    }
    
    .header-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-back, .btn-share {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        cursor: pointer;
        backdrop-filter: blur(10px);
    }
    
    .btn-back:hover, .btn-share:hover {
        background: white;
        color: #ff4f0f;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    /* Main Content */
    .code-main-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        border: 1px solid #f0f0f0;
    }
    
    .code-benefit-display {
        text-align: center;
        margin-bottom: 40px;
        padding: 30px;
        background: linear-gradient(135deg, #ff7a18, #ff4f0f);
        border-radius: 20px;
        color: white;
    }
    
    .benefit-amount {
        font-size: 4rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .benefit-label {
        font-size: 1.2rem;
        font-weight: 600;
        opacity: 0.9;
    }
    
    .code-display-container h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .code-text {
        font-family: "Courier New", monospace;
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: 4px;
        text-align: center;
        padding: 40px;
        background: linear-gradient(135deg, #2b1f36, #3b2740);
        color: white;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(44, 62, 80, 0.3);
        border: 3px solid #ff7a18;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .code-text::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: shine 3s infinite;
    }
    
    @keyframes shine {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .code-url-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        border: 2px solid #e9ecef;
    }

    /* Cuando es una URL, ocultar elementos innecesarios */
    .code-display-container.code-is-url .code-text {
        display: none !important;
    }

    .code-display-container.code-is-url > .btn-copy-code {
        display: none !important;
    }
    
    .url-label {
        color: #666;
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .code-url-link {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #E30613;
        text-decoration: none;
        padding: 15px 20px;
        background: white;
        border: 2px solid #E30613;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        word-break: break-all;
    }
    
    .code-url-link:hover {
        background: #E30613;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);
    }
    
    .btn-copy-code {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 20px 40px;
        border-radius: 15px;
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
    }
    
    .btn-copy-code:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
    }
    
    /* Cards */
    .code-description-card, .how-to-use-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }
    
    .code-description-card h3, .how-to-use-card h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .code-description-card p {
        color: #666;
        font-size: 1.1rem;
        line-height: 1.8;
        margin: 0;
    }
    
    .steps-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    
    .step-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }
    
    .step-number {
        background: linear-gradient(135deg, #E30613, #FF4D4D);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.2rem;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(227, 6, 19, 0.3);
    }
    
    .step-content h4 {
        color: #333;
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 8px 0;
    }
    
    .step-content p {
        color: #666;
        margin: 0;
        line-height: 1.6;
    }
    
    /* Sidebar */
    .user-info-card, .code-info-card, .action-buttons-card, .owner-actions-card, .stats-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }

    .owner-actions-card h4, .stats-card h4 {
        color: #333;
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .owner-buttons {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .btn-edit, .btn-delete, .btn-stats, .btn-highlight {
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        border: none;
        text-align: left;
    }

    .btn-edit {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }

    .btn-edit:hover {
        background: linear-gradient(135deg, #0056b3, #004085);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
    }

    .btn-delete {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .btn-delete:hover {
        background: linear-gradient(135deg, #c82333, #a71e2a);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
    }

    .btn-stats {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        width: 100%;
    }
    
    .stats-card .btn-stats {
        width: 100%;
        justify-content: center;
    }
    .btn-highlight {
        background: linear-gradient(135deg, #ff9800, #f57c00);
        color: white;
        text-decoration: none;
        justify-content: flex-start;
        font-size: 1rem;
        padding: 14px 22px;
        box-shadow: 0 10px 25px rgba(255,152,0,0.25);
    }

    .btn-highlight:hover {
        background: linear-gradient(135deg, #F57C00, #E65100);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 152, 0, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-promocion {
        background: linear-gradient(135deg, #E30613, #C40510) !important;
        color: white !important;
        text-decoration: none;
        justify-content: flex-start;
        font-size: 1rem;
        padding: 14px 22px;
        box-shadow: 0 10px 25px rgba(227, 6, 19, 0.3);
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-align: left;
        font-weight: 600;
    }

    .btn-promocion:hover {
        background: linear-gradient(135deg, #C40510, #D4491A) !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.4);
        color: white !important;
        text-decoration: none;
    }

    .btn-promocion i {
        color: white !important;
    }

    .btn-stats:hover {
        background: linear-gradient(135deg, #20c997, #1dd1a1);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
    }
    
    .user-info-card h4, .code-info-card h4 {
        color: #333;
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .btn-chat-user {
        width: 100%;
        margin-top: 15px; /* Separación añadida */
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
    }
    
    .btn-chat-user:hover {
        background: linear-gradient(135deg, #0b5ed7, #0a58ca);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
    }
    
    .btn-chat-user i {
        font-size: 1rem;
    }
    
    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #e9ecef;
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .user-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #E30613, #FF4D4D);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .user-name {
        color: #ffffff !important;
        background: transparent !important;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 4px;
        line-height: 1.2;
    }
    
    .user-date {
        color: #6c757d;
        font-size: 0.9rem;
        font-size: 0.9rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #666;
        font-weight: 600;
    }
    
    .info-value {
        color: #333;
        font-weight: 700;
    }
    
    .info-value.verified {
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-primary, .btn-secondary, .favorite-btn {
        width: 100%;
        padding: 15px 25px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 15px;
        border: none;
    }
    
    .favorite-btn {
        background: #f8f9fa !important;
        border: 2px solid #ddd !important;
        color: #666 !important;
        /* Forzar estilo de botón pill (anular estilo circular de JS) */
        width: 100% !important;
        height: auto !important;
        border-radius: 12px !important;
        display: flex !important;
        justify-content: center !important;
        padding: 15px 25px !important;
        margin-bottom: 25px !important;
    }
    
    .favorite-btn i {
        font-size: 16px !important;
        color: inherit !important;
        font-weight: 900 !important;
        margin-right: 8px !important; /* Añadir espacio entre icono y texto */
    }
    
    .favorite-btn:hover {
        border-color: #E30613 !important;
        color: #E30613 !important;
        background: rgba(227, 6, 19, 0.1) !important;
        transform: translateY(-2px) !important;
    }
    
    .favorite-btn.active {
        background: linear-gradient(135deg, #E30613, #FF4D4D) !important;
        border-color: #E30613 !important;
        color: white !important;
        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3) !important;
    }
    
    .favorite-btn.active i {
        color: white !important;
    }
    
    .favorite-btn.active:hover {
        background: linear-gradient(135deg, #C40510, #E30613) !important;
        border-color: #C40510 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 12px 35px rgba(227, 6, 19, 0.4) !important;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #E30613, #FF4D4D);
        color: white;
        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(227, 6, 19, 0.4);
    }
    
    .btn-secondary {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #e9ecef;
    }
    
    .btn-secondary:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    /* PDF Carousel Styles */
    .pdf-carousel-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }
    
    .pdf-carousel-card h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pdf-carousel-container {
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
    }
    
    .pdf-carousel-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        min-height: 500px;
    }
    
    .pdf-pages-container {
        position: relative;
        width: 100%;
        max-width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .pdf-page-item {
        display: none;
        width: 100%;
        text-align: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .pdf-page-item.active {
        display: block;
        opacity: 1;
    }
    
    .pdf-page-image {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        margin: 0 auto;
        display: block;
        max-height: 600px;
        object-fit: contain;
    }
    
    .pdf-page-number {
        margin-top: 15px;
        color: #666;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .pdf-carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        color: #666;
        border: 1px solid #ddd;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        opacity: 0.9;
    }
    
    .pdf-carousel-btn:hover {
        background: #f3f2ef;
        color: #0a66c2;
        border-color: #0a66c2;
        transform: translateY(-50%) scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        opacity: 1;
    }
    
    .pdf-carousel-btn:active {
        transform: translateY(-50%) scale(0.95);
    }
    
    .pdf-carousel-prev {
        left: 15px;
    }
    
    .pdf-carousel-next {
        right: 15px;
    }
    
    .pdf-carousel-wrapper:hover .pdf-carousel-btn {
        opacity: 1;
    }
    
    .pdf-carousel-indicators {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 25px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        flex-wrap: wrap;
    }
    
    .pdf-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #c4c4c4;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .pdf-indicator:hover {
        background: #0a66c2;
        transform: scale(1.3);
        border-color: rgba(10, 102, 194, 0.3);
    }
    
    .pdf-indicator.active {
        background: #0a66c2;
        width: 24px;
        border-radius: 4px;
        border-color: transparent;
    }
    
    /* Contador de páginas estilo LinkedIn */
    .pdf-page-counter {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        z-index: 50;
        backdrop-filter: blur(10px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .breadcrumb-nav {
            display: none;
        }

        .code-detail-header {
            flex-direction: column;
            text-align: center;
        }
        
        .header-left {
            flex-direction: column;
            text-align: center;
        }
        
        .header-text h1 {
            font-size: 2rem;
        }
        
        .code-benefit-display {
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .benefit-amount {
            font-size: 3rem;
        }
        
        .code-text {
            font-size: 1.8rem;
            padding: 30px 20px;
            letter-spacing: 2px;
        }
        
        .code-main-card, .code-description-card, .how-to-use-card {
            padding: 25px;
        }
        
        .user-info-card, .code-info-card, .action-buttons-card {
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .code-detail-hero {
            padding: 20px 0;
        }
        
        .code-main-card, .code-description-card, .how-to-use-card {
            padding: 15px;
        }
        
        .header-text h1 {
            font-size: 1.5rem;
        }
        
        .code-benefit-display {
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .benefit-amount {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .benefit-label {
            font-size: 1rem;
        }
        
        .code-text {
            font-size: 1.5rem;
            padding: 25px 15px;
            letter-spacing: 1px;
        }
        
        .step-item {
            flex-direction: column;
            text-align: center;
        }
        
        .pdf-carousel-card {
            padding: 20px;
        }
        
        .pdf-carousel-wrapper {
            min-height: 400px;
            padding: 10px;
        }
        
        .pdf-page-image {
            max-height: 400px;
        }
        
        .pdf-carousel-btn {
            width: 48px;
            height: 48px;
            font-size: 1.2rem;
        }
        
        .pdf-carousel-prev {
            left: 10px;
        }
        
        .pdf-carousel-next {
            right: 10px;
        }
        
        .pdf-page-counter {
            bottom: 10px;
            right: 10px;
            font-size: 0.8rem;
            padding: 6px 12px;
        }
    }
    /* Help Awareness Card */
    .help-awareness-card {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid #f0f0f0;
        border-left: 5px solid #ff7a18;
    }
    
    .help-awareness-card h4 {
        color: #333;
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .help-awareness-card p {
        font-size: 0.95rem;
        color: #666 !important;
        line-height: 1.5;
        margin-bottom: 15px;
        background: transparent !important;
    }
    
    .help-status-logged {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 12px;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .help-promo-box {
        background: #fff8f1;
        border: 1px dashed #ffb74d;
        padding: 15px;
        border-radius: 15px;
        text-align: center;
    }
    
    .help-promo-box p {
        margin-bottom: 10px !important;
        color: #e65100 !important;
    }
    
    .btn-help-login {
        background: #ff7a18;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
        margin-top: 5px;
        cursor: pointer;
    }
    
    .btn-help-login:hover {
        background: #ff4f0f;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 122, 24, 0.3);
    }
    /* Estilos nuevos para la caja de promoción integrada */
    .integrated-promo-box {
        margin-top: 20px;
        padding: 15px;
        background: rgba(255, 122, 24, 0.05); /* Muy sutil fondo naranja */
        border-radius: 12px;
        border-left: 4px solid #E30613; /* Borde izquierdo destacado */
    }

    .integrated-promo-box .promo-header {
        font-weight: 700;
        color: #E30613;
        font-size: 1.05rem;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .integrated-promo-box .promo-desc {
        font-size: 0.9rem;
        color: #555;
        line-height: 1.4;
        margin: 0;
    }
    
    .integrated-promo-box .promo-desc strong {
        color: #333;
    }

    /* Incentivos de Chat Styles */
    .chat-incentive-wrapper {
        position: relative;
        width: 100%;
    }

    .pulse-chat {
        animation: pulse-blue 2s infinite;
        background: #007bff !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px;
        box-shadow: 0 8px 20px rgba(0, 123, 255, 0.3) !important;
    }

    @keyframes pulse-blue {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
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
                var actionButtonsCard = document.querySelector(".action-buttons-card");
                if (actionButtonsCard) {
                    // Verificar si el botón ya existe
                    var existingFavoriteBtn = actionButtonsCard.querySelector(".favorite-btn");
                    if (!existingFavoriteBtn) {
                        var codeId = "' . htmlspecialchars($code_id, ENT_QUOTES, 'UTF-8') . '";
                        var favoriteBtn = document.createElement("button");
                        favoriteBtn.className = "favorite-btn";
                        favoriteBtn.setAttribute("data-codigo-id", codeId);
                        favoriteBtn.setAttribute("title", "Añadir a favoritos");
                        favoriteBtn.innerHTML = \'<i class="fas fa-heart"></i> Añadir a favoritos\';
                        
                        // Insertar antes del primer botón
                        var firstButton = actionButtonsCard.querySelector("button");
                        if (firstButton) {
                            actionButtonsCard.insertBefore(favoriteBtn, firstButton);
                        } else {
                            actionButtonsCard.appendChild(favoriteBtn);
                        }
                        
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

