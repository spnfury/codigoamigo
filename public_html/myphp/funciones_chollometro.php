<?php

/**
 * Funciones para el diseño estilo Chollometro
 */

 

/**
 * Genera una tarjeta de código estilo Chollometro
 */
function generate_chollometro_code_card($codigo, $is_featured = false) {
    global $detect_device, $url_usuario_sin_imagen, $data_usuario, $provincia, $marca, $num_codigos_global, $keywords, $actual_link;
    
    if (is_object($codigo)) {
        $codigo = (array)$codigo;
    }
    
    // Obtener datos del usuario
    $datos_usuario = array();
    try {
        $usuario = getObjectUser('_id', $codigo["id_usuario"]);
        if($usuario && $usuario != "") { 
            $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($codigo["id_usuario"])); 
        }
        if($usuario && $usuario != "") {
            $datos_usuario = get_array_de_usuario($usuario); 
        }
    } catch (Exception $e) {
        // Si hay error obteniendo el usuario, continuar con valores por defecto
        error_log("Error obteniendo usuario en generate_chollometro_code_card: " . $e->getMessage());
    }
    
    // Obtener información de la marca
    $marca_data = getObjectMarca('nombre_clave', $codigo["marca"]);
    if($marca_data && isset($marca_data["nombre"])) {
        $marca_data["nombre"] = ucfirst(strtolower($marca_data["nombre"]));
    } else {
        $marca_data = array("nombre" => ucfirst($codigo["marca"]));
    }
    
    // Información del código
    $codigo_id = (string)$codigo["_id"];
    $marca_nombre = $marca_data["nombre"];
    $descripcion = $codigo["descripcion"] ?? 'Código de descuento válido';
    $beneficio = $codigo["num_beneficio"] ?? 0;
    $tipo_beneficio = $codigo["tipo_descuento"] ?? 'euros';
    $fecha_publicacion = 'N/A';
    if (isset($codigo["fecha_publicacion"]) && !empty($codigo["fecha_publicacion"])) {
        try {
            // Intentar convertir la fecha directamente
            $timestamp = strtotime($codigo["fecha_publicacion"]);
            if ($timestamp !== false) {
                $fecha_publicacion = date('d/m/Y', $timestamp);
            }
        } catch (Exception $e) {
            // Si hay error, usar valor por defecto
            $fecha_publicacion = 'N/A';
        }
    }
    $total_clicks = $codigo["totalclicks"] ?? 0;
    $votos_positivos = $codigo["votos_positivos"] ?? 0;
    $votos_negativos = $codigo["votos_negativos"] ?? 0;
    
    // Información del usuario
    $username = $datos_usuario["username"] ?? 'Usuario';
    $user_img = $datos_usuario["img"] ?? '';
    $is_premium = isset($datos_usuario["pro_user"]) && $datos_usuario["pro_user"] == 1;
    
    // Obtener ID del usuario para el enlace
    $user_id = '';
    if(isset($codigo["id_usuario"])) {
        if(is_object($codigo["id_usuario"])) {
            $user_id = (string)$codigo["id_usuario"];
        } else {
            $user_id = (string)$codigo["id_usuario"];
        }
    } elseif(isset($datos_usuario["_id"])) {
        if(is_object($datos_usuario["_id"])) {
            $user_id = (string)$datos_usuario["_id"];
        } else {
            $user_id = (string)$datos_usuario["_id"];
        }
    }

    // Check VIP
    if (!function_exists('es_usuario_vip')) { 
        if(file_exists(__DIR__ . '/funciones_usuario.php')) {
             include_once __DIR__ . '/funciones_usuario.php'; 
        }
    }
    $es_vip = false;
    if(function_exists('es_usuario_vip') && !empty($user_id)) {
        $es_vip = es_usuario_vip($user_id);
    }
    
    // Generar enlace al perfil del usuario
    $user_link = '';
    if($user_id && $username && $username !== 'Usuario') {
        $user_link = link_usuario($username, $user_id);
    }
    
    // Generar HTML
    $html = '<div class="chollometro-code-card' . ($is_featured ? ' featured' : '') . ($es_vip ? ' vip-user' : '') . '" data-code-id="' . htmlspecialchars($codigo_id) . '">';
    
    // Badge de destacado
    if($is_featured) {
        $html .= '<div class="featured-badge">';
        $html .= '<i class="fas fa-star"></i> Destacado';
        $html .= '</div>';
    }
    
    // Header de la tarjeta
    $html .= '<div class="code-card-header">';
    
    // Imagen de la marca
    $html .= '<div class="brand-image-container" style="position: relative; z-index: 100;">';
    $marca_url = '/de-' . strtolower($codigo["marca"]);
    $html .= '<a href="' . htmlspecialchars($marca_url) . '" class="brand-link" style="position: relative; z-index: 101; display: block;" onclick="event.preventDefault(); event.stopPropagation(); window.location.href=\'' . htmlspecialchars($marca_url) . '\';">';
    if(isset($marca_data["imagen"]) && $marca_data["imagen"]) {
        $html .= '<img src="' . htmlspecialchars($marca_data["imagen"]) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="brand-image">';
    } else {
        $html .= '<div class="brand-placeholder">';
        $html .= '<i class="fas fa-tag"></i>';
        $html .= '</div>';
    }
    $html .= '</a>';
    $html .= '</div>';
    
    // Información principal
    $html .= '<div class="code-main-info">';
    $html .= '<h3 class="brand-name" style="position: relative; z-index: 100;">';
    $html .= '<a href="' . htmlspecialchars($marca_url) . '" style="position: relative; z-index: 101;" onclick="event.preventDefault(); event.stopPropagation(); window.location.href=\'' . htmlspecialchars($marca_url) . '\';">' . htmlspecialchars($marca_nombre) . '</a>';
    $html .= '</h3>';
    $html .= '<p class="code-description">' . htmlspecialchars($descripcion) . '</p>';
    
    // Información del usuario
    $html .= '<div class="user-info">';
    $html .= '<div class="user-avatar">';
    if($user_img) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '" class="user-img">';
    } else {
        $html .= '<div class="user-placeholder">';
        if(isset($datos_usuario["iniciales"]) && !empty($datos_usuario["iniciales"])) {
            $html .= '<span class="user-iniciales">' . htmlspecialchars($datos_usuario["iniciales"]) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="user-details">';
    if($user_link) {
        $html .= '<a href="' . htmlspecialchars($user_link) . '" class="username-link">';
        $html .= '<span class="username">' . htmlspecialchars($username) . '</span>';
        $html .= '</a>';
    } else {
        $html .= '<span class="username">' . htmlspecialchars($username) . '</span>';
    }
    if($is_premium) {
        $html .= '<span class="premium-badge">Premium</span>';
    }
    if($es_vip) {
        $html .= '<span class="vip-badge-gold" style="font-size: 0.7em; margin-left: 5px;"><i class="fas fa-crown"></i> VIP</span>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // Beneficio destacado
    $html .= '<div class="code-benefit">';
    $html .= '<div class="benefit-amount">';
    if($beneficio > 0) {
        $html .= '<span class="amount">' . $beneficio . '</span>';
        $html .= '<span class="currency">' . ($tipo_beneficio == 'porcentaje' ? '%' : '€') . '</span>';
    } else {
        $html .= '<span class="amount">Descuento</span>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // Footer de la tarjeta
    $html .= '<div class="code-card-footer">';
    
    // Estadísticas
    $html .= '<div class="code-stats">';
    $html .= '<div class="stat-item">';
    $html .= '<i class="fas fa-eye"></i>';
    $html .= '<span>' . $total_clicks . '</span>';
    $html .= '</div>';
    $html .= '<div class="stat-item">';
    $html .= '<i class="fas fa-thumbs-up"></i>';
    $html .= '<span>' . $votos_positivos . '</span>';
    $html .= '</div>';
    $html .= '<div class="stat-item">';
    $html .= '<i class="fas fa-thumbs-down"></i>';
    $html .= '<span>' . $votos_negativos . '</span>';
    $html .= '</div>';
    $html .= '<div class="stat-item">';
    $html .= '<i class="fas fa-calendar"></i>';
    $html .= '<span>' . $fecha_publicacion . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Botón de acción
    $html .= '<div class="code-action">';
    $html .= '<button class="btn-view-code" data-code-id="' . htmlspecialchars($codigo_id) . '">';
    $html .= '<i class="fas fa-eye"></i>';
    $html .= 'Ver Código';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

// Función generate_chollometro_filter_menu() movida a funciones_modern.php para evitar duplicación

?>
