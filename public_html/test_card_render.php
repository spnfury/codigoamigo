<?php
session_start();
// Simular variables
$username = "TestUser";
$user_id = "12345";
$user_img = "";
$es_propietario = false;
$es_propietario_chat = false;
$usuario_logueado = true;
$marca_nombre = "Nike";
$code_id = "67890";
$benefit = 35;
$brand = "nike";

// Variables para evitar errores
$date = time();
$es_vip = false;
$_SESSION['user_id'] = "abcdf"; 

function link_usuario($user, $id) { return "#"; }

// Bloque HTML copiado de funciones_code_detail.php
$html = '<div style="max-width: 400px; padding: 20px; background: #2c2c2c;">';

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
    $fecha_texto = date('d/m/Y');
    $html .= '<div class="user-date">Publicado el ' . htmlspecialchars($fecha_texto) . '</div></a>';
    $html .= '</div>'; // closes user-details
    $html .= '</div>'; // closes user-profile (flex container)
    
    if (!$es_propietario_chat && !empty($user_id) && $user_id !== null) {
        $user_id_js = json_encode((string)$user_id);
        $username_js = json_encode($username);
        $user_img_js = json_encode($user_img);
        $user_id_js_attr = htmlspecialchars($user_id_js, ENT_QUOTES, 'UTF-8');
        $username_js_attr = htmlspecialchars($username_js, ENT_QUOTES, 'UTF-8');
        $user_img_js_attr = htmlspecialchars($user_img_js, ENT_QUOTES, 'UTF-8');
        $username_title = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        
        $default_msg_js = json_encode("¡Hola! He visto tu código de " . $marca_nombre . " de " . $benefit . "€ y me gustaría conseguirlo. ¿Me ayudas con el proceso?");
        
        $codigo_id_js = json_encode((string)$code_id);
        $marca_slug_js = json_encode($brand);
        $beneficio_js = json_encode((int)$benefit);
        $marca_nombre_js = json_encode($marca_nombre);
        
        $onclick_action = '';
        if ($usuario_logueado) {
            $contexto_obj = '{codigoId:' . htmlspecialchars($codigo_id_js, ENT_QUOTES, 'UTF-8') . 
                           ',marcaSlug:' . htmlspecialchars($marca_slug_js, ENT_QUOTES, 'UTF-8') . 
                           ',beneficio:' . htmlspecialchars($beneficio_js, ENT_QUOTES, 'UTF-8') . 
                           ',marcaNombre:' . htmlspecialchars($marca_nombre_js, ENT_QUOTES, 'UTF-8') . '}';
            $onclick_action = 'if(typeof openChatModal === \'function\') { openChatModal(' . $user_id_js_attr . ', ' . $username_js_attr . ', ' . $user_img_js_attr . ', ' . htmlspecialchars($default_msg_js, ENT_QUOTES, 'UTF-8') . ', ' . $contexto_obj . '); } else { window.location.href=\'/chat?usuario=' . $user_id_js_attr . '\'; }';
        } else {
            $onclick_action = 'if(typeof showLoginModal === \'function\') { showLoginModal(); } else if(typeof openLoginModalWithRedirect === \'function\') { openLoginModalWithRedirect(window.location.href); } else { window.location.href=\'/login\'; } return false;';
        }

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

$html .= '</div>'; // wrapper

// Estilos necesarios
$html .= '<style>
    body { font-family: Arial, sans-serif; background: #1a1a1a;}
    .user-info-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .user-info-card h4 { display: flex; align-items: center; gap: 10px; color: #333; font-size: 1.1rem; margin-top: 0; margin-bottom: 20px; }
    .user-profile { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
    .user-avatar, .user-avatar-placeholder { width: 50px; height: 50px; border-radius: 50%; }
    .user-avatar-placeholder { background: linear-gradient(135deg, #f0f0f0, #e0e0e0); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #888; border: 2px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
    .user-details { display: flex; flex-direction: column; }
    .user-name { font-weight: 700; color: #2C2C2C; font-size: 1.1rem; }
    .user-date { font-size: 0.85rem; color: #888; margin-top: 2px; }
    
    .btn-chat-user {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 20px;
        background: #f8f9fa;
        color: white;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    /* Estilos nuevos */
    .integrated-promo-box {
        margin-top: 20px;
        padding: 15px;
        background: rgba(255, 122, 24, 0.05);
        border-radius: 12px;
        border-left: 4px solid #E30613;
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
    .integrated-promo-box .promo-desc strong { color: #333; }
    
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
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';

echo $html;
?>
