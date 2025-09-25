<?php
// Función para generar la página de detalle del código con diseño increíble
function generate_code_detail_page($codigo) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    $date = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : new DateTime();
    $destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
    
    // Obtener el código real del campo 'codigo'
    $codigo_real = isset($codigo['codigo']) ? $codigo['codigo'] : '';
    
    // Detectar si es URL y extraer código
    $code_info = detect_url_and_extract_code($codigo_real);
    
    // Obtener información completa del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Obtener información de la marca
    $marca_info = get_brand_info($brand);
    $marca_nombre = $marca_info['nombre'] ?? ucfirst($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    
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
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="brand-logo">';
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
    $html .= '<button class="btn-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Volver</button>';
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
    
    $html .= '<div class="code-display-container">';
    $html .= '<h3><i class="fas fa-tag"></i> Tu código de descuento</h3>';
    $html .= '<div class="code-text" id="codeText">' . strtoupper($code_info['display_text']) . '</div>';
    
    if ($code_info['is_url']) {
        $html .= '<div class="code-url-section">';
        $html .= '<div class="url-label"><i class="fas fa-link"></i> Enlace directo:</div>';
        $html .= '<a href="' . htmlspecialchars($code_info['url']) . '" target="_blank" class="code-url-link">';
        $html .= '<i class="fas fa-external-link-alt"></i>';
        $html .= '<span>' . htmlspecialchars($code_info['url']) . '</span>';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    $html .= '<button class="btn-copy-code" onclick="copyCode()">';
    $html .= '<i class="fas fa-copy"></i> Copiar código';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</div>';
    
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
    $html .= '<h4>Copia el código</h4>';
    $html .= '<p>Haz clic en "Copiar código" para copiarlo al portapapeles</p>';
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
    
    // Información del usuario
    $html .= '<div class="user-info-card">';
    $html .= '<h4><i class="fas fa-user"></i> Publicado por</h4>';
    $html .= '<div class="user-profile">';
    $html .= '<div class="user-avatar">';
    if($user_img && !empty($user_img)) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '">';
    } else {
        $html .= '<div class="user-avatar-placeholder">';
        if(isset($user_info['iniciales']) && !empty($user_info['iniciales'])) {
            $html .= '<span>' . htmlspecialchars($user_info['iniciales']) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="user-details">';
    $html .= '<div class="user-name">' . htmlspecialchars($username) . '</div>';
    $html .= '<div class="user-date">Publicado el ' . date('d/m/Y', $date instanceof DateTime ? $date->getTimestamp() : time()) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
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
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Marca:</span>';
    $html .= '<span class="info-value">' . $marca_nombre . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Botones de acción
    $html .= '<div class="action-buttons-card">';
    $html .= '<button class="btn-primary" onclick="copyCode()">';
    $html .= '<i class="fas fa-copy"></i> Copiar código';
    $html .= '</button>';
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .breadcrumb-nav {
        margin-bottom: 30px;
    }
    
    .breadcrumb-link {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-link:hover {
        color: white;
        text-decoration: none;
    }
    
    .breadcrumb-separator {
        color: rgba(255, 255, 255, 0.6);
        margin: 0 10px;
    }
    
    .breadcrumb-current {
        color: white;
        font-weight: 600;
    }
    
    .code-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .brand-logo {
        width: 80px;
        height: 80px;
        object-fit: contain;
        background: white;
        border-radius: 15px;
        padding: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .header-text h1 {
        color: white;
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .header-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.2rem;
        margin: 0 0 15px 0;
    }
    
    .featured-badge {
        background: #ff6b35;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    }
    
    .header-actions {
        display: flex;
        gap: 15px;
    }
    
    .btn-back, .btn-share {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 12px 24px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        backdrop-filter: blur(10px);
    }
    
    .btn-back:hover, .btn-share:hover {
        background: white;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
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
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
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
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: white;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(44, 62, 80, 0.3);
        border: 3px solid #ff6b35;
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
        color: #ff6b35;
        text-decoration: none;
        padding: 15px 20px;
        background: white;
        border: 2px solid #ff6b35;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        word-break: break-all;
    }
    
    .code-url-link:hover {
        background: #ff6b35;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
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
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
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
        box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
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
    .user-info-card, .code-info-card, .action-buttons-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
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
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .user-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .user-name {
        color: #333;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .user-date {
        color: #666;
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
    
    .btn-primary, .btn-secondary {
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
    
    .btn-primary {
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        color: white;
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(255, 107, 53, 0.4);
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
    
    /* Responsive */
    @media (max-width: 768px) {
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
        
        .header-text h1 {
            font-size: 1.5rem;
        }
        
        .benefit-amount {
            font-size: 2.5rem;
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
    }
    </style>';
    
    // Agregar JavaScript para funcionalidades
    $html .= '<script>
    function copyCode() {
        const codeText = document.getElementById("codeText");
        const text = codeText.textContent;
        
        navigator.clipboard.writeText(text).then(function() {
            // Cambiar el texto del botón temporalmente
            const btn = document.querySelector(".btn-copy-code");
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = "<i class=\"fas fa-check\"></i> ¡Copiado!";
                btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                }, 2000);
            }
        }).catch(function(err) {
            console.error("Error al copiar: ", err);
            alert("Error al copiar el código");
        });
    }
    
    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = "/";
        }
    }
    
    function shareCode() {
        const url = window.location.href;
        const title = document.title;
        
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
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
    </script>';
    
    return $html;
}
?>

