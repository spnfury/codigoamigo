<?php 
// Verificar que la sesión esté iniciada y que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || $_SESSION["user_id"] == "") {
    header("Location: https://www.codigoamigo.com/login");
    exit;
}

// Verificar que las variables de sesión necesarias existan
if (!isset($_SESSION["username"]) || empty($_SESSION["username"])) {
    // Si no existe username en la sesión, obtenerlo de la base de datos
    if (isset($data_usuario) && isset($data_usuario["username"])) {
        $_SESSION["username"] = $data_usuario["username"];
    } else {
        // Intentar obtener el usuario de la base de datos
        try {
            $usuario_temp = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
            if ($usuario_temp && isset($usuario_temp["username"])) {
                $_SESSION["username"] = $usuario_temp["username"];
            } else {
                // Redirigir al login si no se puede obtener el username
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

get_header_modern($title, $description); 

$link_usuario = enlace_usuario($_SESSION["username"], $_SESSION["user_id"]);

// Definir variables globales necesarias
if (!isset($GLOBALS['website'])) {
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
}
if (!isset($GLOBALS['actual_url'])) {
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Configuración de Stripe
if($_SESSION["user_id"]=='639899bc6321ee0d0e4010d2' || $_SESSION["user_id"]=='58bd851da54e295b8b52f702' || $_SESSION["user_id"]=='5db1af3a2f55c82b47342172'){ //SI ES USUARIO ADMIN PATROCINO GRATIS
    $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
    $stripe_live_secret_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
    $sku_patrocinado_splash = 'sku_GjZv74bm3tOhSU';
}else{ //PRODUCCION 
    $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
    $stripe_live_secret_key = "sk_live_dfMwJTC7REoMy76Bp2PzVoZV00U5KaNCcv";
    $sku_patrocinado_splash = 'sku_H6ViM4K361ELMH';
}

// Los códigos del usuario ya están disponibles desde app_with_mongo.php
?>

<style>
/* Animación para mensaje de cuenta activada */
@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Estilos base - solo para móvil por defecto */
.dashboard-container {
    background: #222222;
    min-height: 100vh;
    padding: 0;
    width: 100%;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
}


.main-content {
    width: 100%;
    margin-left: 0;
    padding: 0;
    background: #333333;
    min-height: calc(100vh - 80px);
    position: relative;
    z-index: 1;
}

.content-wrapper {
    display: block;
    width: 100%;
    min-height: calc(100vh - 80px);
}

.codes-section {
    width: 100%;
    padding: 30px;
    margin: 0;
    box-sizing: border-box;
}

.sidebar-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #444444;
}

.sidebar-title {
    font-size: 2rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    text-align: center;
    padding: 20px 0;
    background: linear-gradient(135deg, #ff6b35, #e55a2b);
    border-radius: 10px;
    margin-bottom: 25px;
}

.filter-section {
    margin-bottom: 25px;
}

.filter-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 25px;
    display: block;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    padding-bottom: 8px;
    border-bottom: 2px solid rgba(255, 107, 53, 0.3);
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.2rem;
    color: #ffffff;
    border-radius: 8px;
    margin-bottom: 6px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.filter-option:hover {
    color: #ff6b35;
    background: rgba(255, 107, 53, 0.1);
    transform: translateX(5px);
}

.filter-option input[type="radio"] {
    margin: 0;
    width: 16px;
    height: 16px;
    accent-color: #ff6b35;
    cursor: pointer;
}

.filter-option input[type="checkbox"] {
    margin: 0;
    width: 16px;
    height: 16px;
    accent-color: #ff6b35;
    cursor: pointer;
}

.filter-option input[type="date"] {
    border: 1px solid #555;
    background: #444;
    color: white;
    font-size: 0.9rem;
    padding: 8px 12px;
    border-radius: 6px;
    width: 100%;
}

.filter-option input[type="date"]:focus {
    outline: none;
    border-color: #ff6b35;
}

.filter-option label {
    margin: 0;
    cursor: pointer;
    flex: 1;
    font-weight: 400;
    color: white;
}

.filter-count {
    background: #ff6b35;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}

.apply-filters {
    background: #ff6b35;
    color: white;
    border: none;
    padding: 16px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 600;
    width: 100%;
    margin: 25px 0 15px 0;
    transition: all 0.3s ease;
}

.apply-filters:hover {
    background: #e55a2b;
    transform: translateY(-1px);
}

.clear-filters {
    background: #444444;
    color: white;
    border: none;
    padding: 16px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 600;
    width: 100%;
    margin-top: 15px;
    transition: all 0.3s ease;
}

.clear-filters:hover {
    background: #555555;
    transform: translateY(-1px);
}

/* Estilos específicos para escritorio */
@media (min-width: 769px) {
    .dashboard-container {
        display: flex;
        min-height: 100vh;
    }
    
    
    .main-content {
        width: 100% !important;
        margin-left: 0 !important;
        padding: 0;
        background: #333333;
        min-height: calc(100vh - 80px);
        position: relative;
        z-index: 1;
        flex: 1;
        box-sizing: border-box;
        display: flex;
        justify-content: center;
    }
    
    .content-wrapper {
        width: 100%;
        max-width: 1200px;
        min-height: calc(100vh - 80px);
        margin: 0 auto;
    }
    
    .codes-section {
        width: 100%;
        padding: 20px;
        margin: 0;
        box-sizing: border-box;
        margin-left: 0;
    }
    
    /* Ocultar filtros móviles en escritorio */
    .filter-section-mobile {
        display: none !important;
    }
}

@media (max-width: 768px) {
    /* Reset general para móvil */
    * {
        box-sizing: border-box;
    }
    
    body {
        background: #f8f9fa;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
    }
    
    .dashboard-container {
        flex-direction: column;
        padding: 0;
        margin: 0;
        background: #f8f9fa;
    }
    
    /* Header simplificado */
    .dashboard-header {
        background: white;
        margin: 0;
        padding: 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .user-info h1 {
        font-size: 1.8rem;
        color: #333;
        margin: 0 0 10px 0;
    }
    
    .user-info p {
        color: #666;
        font-size: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-modern {
        width: 100%;
        text-align: center;
        padding: 12px 20px;
        border-radius: 8px;
        border: 2px solid #ff6b35;
        background: white;
        color: #ff6b35;
        font-weight: 600;
    }
    
    .btn-modern:hover {
        background: #ff6b35;
        color: white;
    }
    
    
    .main-content {
        width: 100% !important;
        margin-left: 0 !important;
        padding: 0;
        flex: none;
    }
    
    .content-wrapper {
        padding: 0;
        margin: 0;
        background: #f8f9fa;
    }
    
    
    .visibility-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 0;
        margin: 0;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-button {
        background: white;
        border: 2px solid #ff6b35;
        color: #ff6b35;
        padding: 10px 16px;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: fit-content;
    }
    
    .tab-button:hover {
        background: #ff6b35;
        color: white;
    }
    
    .tab-button.active {
        background: #ff6b35;
        color: white;
    }
    
    .tab-text {
        font-weight: 600;
    }
    
    .tab-count {
        font-weight: 500;
        opacity: 0.9;
    }
    
    /* Sección de códigos simplificada */
    .codes-section {
        background: #f8f9fa;
        padding: 20px;
        margin: 0;
        border-radius: 0;
    }
    
    .codes-title {
        font-size: 1.4rem;
        color: #333;
        margin: 0 0 20px 0;
        font-weight: 700;
    }
    
    /* Tarjetas de código simplificadas */
    .code-item {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: none;
        transition: all 0.2s ease;
    }
    
    .code-item:hover {
        transform: none;
        box-shadow: none;
        border-color: #ff6b35;
    }
    
    .code-item.featured {
        border-color: #ff6b35;
        background: white;
        box-shadow: none;
    }
    
    .featured-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #ff6b35;
        color: white;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    
    .code-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .code-brand h4 {
        color: #333;
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 8px 0;
    }
    
    .code-brand p {
        color: #666;
        font-size: 0.95rem;
        margin: 0;
        line-height: 1.4;
    }
    
    .code-reward {
        background: #ff6b35;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.9rem;
        
    }
    
    .code-details {
        margin: 15px 0;
    }
    
    .code-details p {
        margin: 8px 0;
        color: #666;
        font-size: 0.9rem;
    }
    
    .code-code {
        background: #f8f9fa;
        padding: 12px 16px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: #333;
        border: 1px solid #e9ecef;
        margin: 10px 0;
        word-break: break-all;
        font-size: 0.85rem;
    }
    
    .code-position {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.8rem;
        margin: 2px 4px 2px 0;
    }
    
    .code-position.alta {
        background: #d4edda;
        color: #155724;
    }
    
    .code-position.media {
        background: #fff3cd;
        color: #856404;
    }
    
    .code-position.baja {
        background: #f8d7da;
        color: #721c24;
    }
    
    .visibility-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .visibility-badge.visibility-alta {
        background: #d4edda;
        color: #155724;
    }
    
    .visibility-badge.visibility-media {
        background: #fff3cd;
        color: #856404;
    }
    
    .visibility-badge.visibility-baja {
        background: #f8d7da;
        color: #721c24;
    }
    
    /* Botones de acción simplificados */
    .code-actions {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
    }
    
    .btn-action {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.2s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 44px;
        text-align: center;
    }
    
    .btn-destacar {
        background: #ff6b35;
        color: white;
    }
    
    .btn-destacar:hover {
        background: #e55a2b;
        color: white;
        text-decoration: none;
    }
    
    .btn-modificar {
        background: #28a745;
        color: white;
    }
    
    .btn-modificar:hover {
        background: #218838;
        color: white;
        text-decoration: none;
    }
    
    .btn-estadisticas {
        background: #17a2b8;
        color: white;
    }
    
    .btn-estadisticas:hover {
        background: #138496;
        color: white;
        text-decoration: none;
    }
    
    .btn-compartir {
        background: #6f42c1;
        color: white;
    }
    
    .btn-compartir:hover {
        background: #5a32a3;
        color: white;
    }
    
    .btn-destacado-disabled {
        background: #6c757d !important;
        color: #fff !important;
        cursor: not-allowed !important;
        opacity: 0.6;
    }
    
    /* Estadísticas simplificadas */
    .stats-grid {
        display: none; /* Ocultar en móvil */
    }
    
    .filter-controls {
        display: none; /* Ocultar en móvil */
    }
    
    /* Sin códigos */
    .no-codes {
        text-align: center;
        padding: 40px 20px;
        color: #666;
        background: white;
        border-radius: 12px;
        margin: 20px 0;
    }
    
    .no-codes i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 15px;
        display: block;
    }
    
    .no-codes h3 {
        color: #333;
        font-size: 1.2rem;
        margin: 0 0 10px 0;
    }
    
    .no-codes p {
        color: #666;
        font-size: 0.9rem;
        margin: 0 0 20px 0;
    }
    
    /* Ocultar elementos no necesarios en móvil */
    .mobile-filter-toggle {
        display: none;
    }
    
    .sidebar-overlay {
        display: none;
    }
    
    /* Mejorar las tarjetas de código en móvil */
    .code-item {
        width: 100%;
        margin: 20px 0;
        padding: 25px 20px;
        box-sizing: border-box;
        min-height: auto;
        border-radius: 20px;
    }
    
    .code-actions {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-top: 25px;
        padding-top: 20px;
    }
    
.btn-action {
    padding: 20px 28px;
    font-size: 1.3rem;
    min-height: 60px;
    border-radius: 12px;
    font-weight: 700;
}
    
    .code-header {
        flex-direction: column;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .code-brand h4 {
        font-size: 1.5rem;
        margin-bottom: 12px;
        line-height: 1.3;
    }
    
    .code-brand p {
        font-size: 1.2rem;
        line-height: 1.7;
        margin-bottom: 15px;
    }
    
    .code-details p {
        font-size: 1.1rem;
        margin: 15px 0;
        line-height: 1.5;
    }
    
    .code-code {
        font-size: 1rem;
        padding: 15px 20px;
        margin: 15px 0;
        word-break: break-all;
        line-height: 1.4;
    }
    
    .featured-badge {
        top: 20px;
        right: 20px;
        padding: 10px 18px;
        font-size: 0.9rem;
    }
    
    .code-reward {
        padding: 12px 24px;
        font-size: 1rem;
        margin-top: 10px;
    }
    
    /* Mejorar visualización de URLs largas */
    .code-code {
        word-break: break-all;
        overflow-wrap: break-word;
        hyphens: auto;
        white-space: pre-wrap;
    }
    
    /* Mejorar espaciado general */
    .codes-section {
        padding: 20px 15px;
        margin: 15px 10px;
    }
    
    .codes-title {
        font-size: 1.6rem;
        margin-bottom: 25px;
    }
    
    /* Mejorar el sidebar en móvil */
    .sidebar {
        padding: 20px 15px;
        display: none; /* Ocultar en móvil */
    }
    
    .sidebar-title {
        font-size: 1.3rem;
        padding: 12px 0;
        margin-bottom: 25px;
    }
    
    .filter-title {
        font-size: 1rem;
        margin-bottom: 15px;
    }
    
    .filter-option {
        padding: 15px 12px;
        font-size: 1.1rem;
        margin-bottom: 8px;
    }
    
    /* Pestañas de visibilidad en móvil */
    .visibility-tabs {
        margin-bottom: 20px;
        padding: 0 5px;
    }
    
    .tab-button {
        padding: 10px 16px;
        font-size: 0.8rem;
        border-radius: 20px;
        min-width: auto;
    }
    
    .tab-text {
        font-size: 0.8rem;
    }
    
    .tab-count {
        font-size: 0.75rem;
    }
    
    /* Ajustar el header del dashboard */
    .dashboard-header {
        margin: 10px;
        padding: 20px;
        border-radius: 10px;
    }
    
    .user-profile {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn-modern {
        width: 100%;
        justify-content: center;
    }
}

/* Estilos para pantallas muy pequeñas */
@media (max-width: 480px) {
    .mobile-filter-toggle {
        top: 15px;
        left: 15px;
        padding: 10px 15px;
        font-size: 13px;
    }
    
    .codes-section {
        padding: 10px;
    }
    
    .dashboard-header {
        margin: 5px;
        padding: 15px;
    }
    
    .sidebar {
        max-width: 100%;
        width: 100%;
    }
    
    .filter-section {
        margin-bottom: 20px;
    }
    
    .filter-option {
        padding: 10px 0;
        font-size: 1rem;
    }
    
    .apply-filters, .clear-filters {
        padding: 15px 20px;
        font-size: 1rem;
    }
}

.mobile-filter-toggle {
    display: none;
}

.dashboard-header {
    background: #2c2c2c;
    border-radius: 15px;
    margin: 20px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    color: white;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #ff6b35;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
    overflow: hidden;
}

.user-avatar-img-large {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    border-radius: 50%;
}

.user-info h1 {
    color: #333;
    font-size: 2.5rem;
    margin: 0;
    font-weight: 700;
}

.user-info p {
    color: #666;
    font-size: 1.1rem;
    margin: 5px 0 0 0;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
}

.btn-modern {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.btn-primary-modern {
    background: #ff6b35;
    color: white;
    box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
    color: white;
    text-decoration: none;
}

.btn-success-modern {
    background: #28a745;
    color: white;
    box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
}

.btn-success-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    color: white;
    text-decoration: none;
}

/* Pestañas de visibilidad */
.visibility-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 30px;
    overflow-x: auto;
    padding: 5px 0;
    -webkit-overflow-scrolling: touch;
}

.tab-button {
    background: transparent;
    border: 2px solid #ff6b35;
    color: #ff6b35;
    padding: 15px 25px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 700;
    font-size: 1.1rem;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: fit-content;
}

.tab-button:hover {
    background: rgba(255, 107, 53, 0.1);
    transform: translateY(-2px);
}

.tab-button.active {
    background: #ff6b35;
    color: white;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

.tab-text {
    font-weight: 700;
}

.tab-count {
    font-weight: 600;
    opacity: 0.8;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: #444444;
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 3px solid transparent;
    color: white;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card.active {
    border-color: #ff6b35;
    background: #fff;
}

.stat-badge {
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 15px;
    display: inline-block;
}

.stat-badge.high {
    background: #ff6b35;
    color: white;
}

.stat-badge.medium {
    background: #ff6b35;
    color: white;
}

.stat-badge.low {
    background: #ff6b35;
    color: white;
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    color: #ffffff;
    margin: 10px 0;
    line-height: 1;
}

.stat-description {
    color: #cccccc;
    font-size: 1rem;
    margin: 0;
}

.filter-controls {
    text-align: center;
    margin: 30px 0;
}

.btn-show-all {
    background: #ff6b35;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
}

.btn-show-all:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
}

.codes-section {
    background: #f8f8f8;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin: 20px;
}

.codes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
}

.codes-title {
    color: #ff6b35;
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
}

/* Estilos para el desplegable de ordenación */
.sort-dropdown {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-dropdown label {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
}

.sort-select {
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    color: #333;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 180px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 16px;
    padding-right: 35px;
}

.sort-select:focus {
    outline: none;
    border-color: #ff6b35;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.sort-select:hover {
    border-color: #ff6b35;
}

/* Responsive para móvil */
@media (max-width: 768px) {
    .codes-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .sort-dropdown {
        width: 100%;
        justify-content: space-between;
    }
    
    .sort-select {
        min-width: 150px;
        flex: 1;
    }
}

.code-item {
    background: #ffffff;
    border-radius: 20px;
    padding: 35px;
    margin-bottom: 30px;
    box-shadow: 0 6px 25px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    color: #1f2937;
    position: relative;
    min-height: 200px;
    display: flex;
    flex-direction: column;
}

.code-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    border-color: #ff6b35;
}

.code-item.featured {
    border-left: 4px solid #ff6b35;
    background: #ffffff;
    box-shadow: 0 8px 30px rgba(255, 107, 53, 0.15);
}

.featured-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, #ff6b35, #e55a2b);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.code-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
    gap: 25px;
}

.code-brand {
    flex: 1;
}

.code-brand h4 {
    color: #ffffff;
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0 0 12px 0;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.code-brand p {
    font-size: 1.2rem;
    margin: 0;
    line-height: 1.6;
}

.code-reward {
    background: #ff6b35;
    color: white;
    padding: 10px 20px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
    box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
    margin-top: 50px !important;
    position: absolute;
}

.code-details {
    margin: 20px 0;
}

.code-details p {
    margin: 12px 0;
    font-size: 1.2rem;
    line-height: 1.6;
}

.code-id {
    font-family: 'Courier New', monospace;
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    font-size: 0.85rem;
    color: #ff6b35;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-block;
    user-select: all;
}

.code-id:hover {
    background: #ff6b35;
    color: white;
    border-color: #e55a2b;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
}

/* Filtros de visibilidad */
.visibility-filters {
    margin: 20px 0 30px 0;
    padding: 0;
}

.filter-tabs {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 20px;
}

.filter-tab {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    color: #6c757d;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 140px;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.filter-tab:hover {
    background: #ff6b35;
    color: white;
    border-color: #ff6b35;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.filter-tab.active {
    background: #ff6b35;
    color: white;
    border-color: #ff6b35;
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
}

.filter-tab i {
    font-size: 1rem;
}

.filter-tab span {
    font-weight: 600;
}

.filter-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 700;
}

.filter-tab:not(.active) .filter-count {
    background: #e9ecef;
    color: #6c757d;
}

/* Responsive para filtros */
@media (max-width: 768px) {
    .filter-tabs {
        flex-direction: column;
        gap: 8px;
        align-items: center;
    }
    
    .filter-tab {
        width: 100%;
        max-width: 280px;
        min-width: auto;
    }
}

.code-code {
    background: #f8f9fa;
    padding: 16px 20px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #2c3e50;
    border: 2px dashed #ff6b35;
    margin: 15px 0;
    word-break: break-all;
    font-size: 1.1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.code-position {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
    margin: 5px 5px 5px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.code-position.alta {
    background: #d4edda;
    color: #155724;
}

.code-position.media {
    background: #fff3cd;
    color: #856404;
}

.code-position.baja {
    background: #f8d7da;
    color: #721c24;
}

.visibility-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 15px;
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.visibility-badge.visibility-alta {
    background: #d4edda;
    color: #155724;
    border: 2px solid #28a745;
}

.visibility-badge.visibility-media {
    background: #fff3cd;
    color: #856404;
    border: 2px solid #ffc107;
}

.visibility-badge.visibility-baja {
    background: #f8d7da;
    color: #721c24;
    border: 2px solid #dc3545;
}

.code-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 15px;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.btn-action {
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 44px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-destacar {
    background: #ff6b35;
    color: white;
    box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
}

.btn-destacar:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
    color: white;
    text-decoration: none;
}

.btn-modificar {
    background: #28a745;
    color: white;
    box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
}

.btn-modificar:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    color: white;
    text-decoration: none;
}

.btn-estadisticas {
    background: #17a2b8;
    color: white;
    box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
}

.btn-estadisticas:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(23, 162, 184, 0.4);
    color: white;
    text-decoration: none;
}

.btn-compartir {
    background: #6f42c1;
    color: white;
    box-shadow: 0 3px 10px rgba(111, 66, 193, 0.3);
}

.btn-compartir:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
    color: white;
    text-decoration: none;
}

.btn-eliminar {
    background: #dc3545;
    color: white;
    box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
}

.btn-eliminar:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
    color: white;
    text-decoration: none;
    background: #c82333;
}

.btn-destacado-disabled {
    background: #666666 !important;
    color: #999999 !important;
    cursor: not-allowed !important;
    opacity: 0.6;
}

.btn-destacado-disabled:hover {
    transform: none !important;
    box-shadow: none !important;
    color: #999999 !important;
}

.no-codes {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-codes i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
    display: block;
}

.no-codes h3 {
    color: #333;
    margin-bottom: 15px;
}

.no-codes p {
    font-size: 1.1rem;
    margin-bottom: 30px;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header {
        margin: 10px;
        padding: 20px;
    }
    
    .user-profile {
        flex-direction: column;
        text-align: center;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .codes-section {
        margin: 0px;
        padding: 5px;
    }
    
    .code-actions {
        justify-content: center;
    }
}
</style>

<div class="dashboard-container">
    
    <div class="content-wrapper">
        <!-- Contenido principal -->
        
        <!-- Contenido principal -->
        <div class="codes-section">
        
        <div class="dashboard-header">
            <?php if(isset($_SESSION['msg_success']) && $_SESSION['msg_success'] != "") { ?>
                <div class="alert alert-success" style="background: #4CAF50; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: none; font-size: 1.6rem; text-align: center; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">
                    <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
                    <?php echo $_SESSION['msg_success']; ?>
                </div>
            <?php } ?>
            
            <?php if(isset($_SESSION['msg_error']) && $_SESSION['msg_error'] != "") { ?>
                <div class="alert alert-danger" style="background: #f44336; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: none; font-size: 1.6rem; text-align: center; box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i>
                    <?php echo $_SESSION['msg_error']; ?>
                </div>
            <?php } ?>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'password_updated') { ?>
                <div class="alert alert-success" style="background: #4CAF50; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: none; font-size: 1.6rem; text-align: center; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">
                    <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
                    ¡Tu contraseña se ha actualizado correctamente!
                </div>
            <?php } ?>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'account_activated') { ?>
                <div class="alert alert-success" style="background: #4CAF50; color: white; padding: 20px; border-radius: 12px; margin-bottom: 30px; border: none; font-size: 1.8rem; text-align: center; box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4); animation: slideInDown 0.6s ease-out;">
                    <i class="fas fa-check-circle" style="margin-right: 15px; font-size: 2rem;"></i>
                    ¡Cuenta activada correctamente! Bienvenido a Código Amigo 🎉
                </div>
            <?php } ?>
            
            <?php unset($_SESSION['msg_success']); ?>
            <?php unset($_SESSION['msg_error']); ?>
            
            <div class="user-profile">
                <div class="user-avatar-large">
                    <?php if (!empty($data_usuario["img"])): ?>
                        <img src="<?php echo htmlspecialchars($data_usuario["img"]); ?>" 
                             alt="Avatar de <?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?>" 
                             class="user-avatar-img-large">
                    <?php else: ?>
                        <?php echo strtoupper(substr($data_usuario["username"] ?? "U", 0, 2)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h1><?php echo $data_usuario["username"] ?? "Usuario"; ?></h1>
                    <p>
                        <?php 
                        if(isset($data_usuario["fecha_registro"])) {
                            $fecha_registro = new DateTime($data_usuario["fecha_registro"]);
                            $hoy = new DateTime();
                            $dias = $hoy->diff($fecha_registro)->days;
                            echo "Miembro desde hace " . $dias . " días";
                        } else {
                            echo "Usuario registrado";
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <!-- Saldo del usuario -->
            <div class="balance-section" style="background: linear-gradient(135deg, #ff6b35, #e55a2b); padding: 20px; border-radius: 15px; margin-bottom: 20px; color: white; text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 15px;">
                    <i class="fas fa-wallet" style="font-size: 2rem;"></i>
                    <div>
                        <h3 style="margin: 0; font-size: 1.8rem; font-weight: bold;">Saldo Disponible</h3>
                        <p style="margin: 5px 0 0 0; font-size: 1.2rem; opacity: 0.9;">Para patrocinar tus códigos</p>
                    </div>
                </div>
                <div style="font-size: 3rem; font-weight: bold; margin-bottom: 20px;">
                    <?php 
                    $saldo_usuario = isset($data_usuario['saldo']) ? $data_usuario['saldo'] : 0;
                    echo number_format($saldo_usuario, 2) . '€';
                    ?>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="mostrarRecargarSaldo()" class="btn-modern" style="background: white; color: #ff6b35; padding: 12px 25px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-plus"></i> Recargar Saldo
                    </button>
                    <a href="/public/historial_recargas.php" class="btn-modern" style="background: #667eea; color: white; padding: 12px 25px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none;">
                        <i class="fas fa-history"></i> Ver Historial
                    </a>
                </div>
            </div>

            <div class="action-buttons">
                <a href="<?php echo $link_usuario; ?>" class="btn-modern btn-primary-modern">
                    <i class="fas fa-eye"></i> Ver mi página
                </a>
                <a href="/nuevo_codigo" class="btn-modern btn-success-modern">
                    <i class="fas fa-plus"></i> Nuevo código
                </a>
                <button onclick="destacarTodosSplash()" class="btn-modern" style="background: #9c27b0; color: white; padding: 12px 25px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                    <i class="fas fa-rocket"></i> Destacar Todos (Splash)
                </button>
            </div>
            
        </div>
        
        
        <!-- Lista de códigos -->
        <div class="codes-section">
            <div class="codes-header">
                <h3 class="codes-title" id="codesTitle">Tus códigos (<?php echo $num_codigos; ?>)</h3>
                
                <?php if (!$mostrando_todos && $codigos_ocultos > 0): ?>
                <div class="alert alert-info" style="margin-top: 10px; padding: 10px; background: #e3f2fd; border: 1px solid #2196f3; border-radius: 4px; color: #1976d2;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Información:</strong> Tienes <?php echo number_format($total_codigos_usuario); ?> códigos en total. 
                    Se están mostrando los <?php echo number_format($num_codigos); ?> más recientes. 
                    <?php if ($codigos_ocultos > 0): ?>
                        <span style="color: #d32f2f;"><?php echo number_format($codigos_ocultos); ?> códigos más antiguos están ocultos.</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Desplegable de ordenación -->
                <div class="sort-dropdown">
                    <label for="sortSelect">Ordenar por:</label>
                    <select id="sortSelect" class="sort-select" onchange="sortCodes(this.value)">
                        <option value="fecha_desc" selected>Más recientes</option>
                        <option value="fecha_asc">Más antiguos</option>
                        <option value="marca_asc">Marca A-Z</option>
                        <option value="marca_desc">Marca Z-A</option>
                        <option value="clicks_desc">Más clicks</option>
                        <option value="clicks_asc">Menos clicks</option>
                        <option value="beneficio_desc">Mayor beneficio</option>
                        <option value="beneficio_asc">Menor beneficio</option>
                    </select>
                </div>
            </div>
            
            <!-- Filtros de visibilidad -->
            <div class="visibility-filters">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-visibility="all" onclick="filterByVisibility('all')">
                        <i class="fas fa-list"></i>
                        <span>Todos</span>
                        <span class="filter-count">(<?php echo $num_codigos; ?>)</span>
                    </button>
                    <button class="filter-tab" data-visibility="alta" onclick="filterByVisibility('alta')">
                        <i class="fas fa-star"></i>
                        <span>Alta Visibilidad</span>
                        <span class="filter-count">(<?php echo $num_1_codes; ?>)</span>
                    </button>
                    <button class="filter-tab" data-visibility="media" onclick="filterByVisibility('media')">
                        <i class="fas fa-eye"></i>
                        <span>Media Visibilidad</span>
                        <span class="filter-count">(<?php echo $num_2_codes; ?>)</span>
                    </button>
                    <button class="filter-tab" data-visibility="baja" onclick="filterByVisibility('baja')">
                        <i class="fas fa-eye-slash"></i>
                        <span>Baja Visibilidad</span>
                        <span class="filter-count">(<?php echo $num_3_codes; ?>)</span>
                    </button>
                </div>
            </div>
        
        <?php if($listado_codigos && count($listado_codigos) > 0): ?>
            <?php foreach($listado_codigos as $codigo): ?>
                <?php
                // Verificar que $codigo['marca'] existe y no es null
                if (!isset($codigo['marca']) || $codigo['marca'] === null) {
                    continue;
                }
                
                $marca = getObjectMarca('nombre_clave', $codigo['marca']);
                if (!$marca) {
                    continue;
                }
                
                $posicion = get_posicion_codigo_en_marca($codigo['_id'], $codigo['marca']);
                $is_destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
                
                // Determinar clase de visibilidad
                $visibilidad_class = '';
                $visibilidad_text = '';
                if($posicion == 1) {
                    $visibilidad_class = 'alta';
                    $visibilidad_text = 'Alta Visibilidad';
                } elseif($posicion == 2) {
                    $visibilidad_class = 'media';
                    $visibilidad_text = 'Media Visibilidad';
                } else {
                    $visibilidad_class = 'baja';
                    $visibilidad_text = 'Baja Visibilidad';
                }
                
                // Obtener categoría para filtros
                $categoria_nombre = '';
                if(isset($codigo['categoria']) && !empty($codigo['categoria'])) {
                    $categoria_obj = getObjectCategoria($codigo['categoria']);
                    if($categoria_obj) {
                        $categoria_nombre = $categoria_obj['nombre'];
                    }
                }
                
                // Formatear fecha para filtros
                $fecha_publicacion = '';
                if(isset($codigo['fecha_publicacion']) && !empty($codigo['fecha_publicacion'])) {
                    $fecha_publicacion = date('Y-m-d', strtotime($codigo['fecha_publicacion']));
                }
                ?>
                
                <div class="code-item <?php echo $is_destacado ? 'featured' : ''; ?>" 
                     data-codigo-id="<?php echo $codigo['_id']; ?>"
                     data-position="<?php echo $posicion; ?>"
                     data-visibilidad="<?php echo $visibilidad_class; ?>"
                     data-categoria="<?php echo htmlspecialchars($categoria_nombre); ?>"
                     data-fecha="<?php echo $fecha_publicacion; ?>"
                     data-marca="<?php echo htmlspecialchars($marca['nombre']); ?>"
                     data-clicks="<?php echo $codigo['totalclicks'] ?? 0; ?>"
                     data-beneficio="<?php echo $codigo['num_beneficio'] ?? 0; ?>">
                    <?php if($is_destacado): ?>
                        <div class="featured-badge">
                            <i class="fas fa-star"></i> Destacado
                        </div>
                    <?php endif; ?>
                    
                    <div class="code-header">
                        <div class="code-brand">
                            <h4>
                                <a href="/de-<?php echo strtolower($codigo['marca']); ?>" 
                                   target="_blank"
                                   style="color: #ff6b35; text-decoration: none; font-weight: 600;"
                                   onmouseover="this.style.textDecoration='underline'"
                                   onmouseout="this.style.textDecoration='none'">
                                    <?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>
                                </a>
                            </h4>
                            <p><?php echo htmlspecialchars($codigo['descripcion'] ?? 'Código de descuento válido'); ?></p>
                        </div>
                    </div>
                    
                    <div class="code-details">
                        <p><strong>Beneficio:</strong> <?php echo $codigo['num_beneficio'] ?? '10'; ?>€</p>
                        <p><strong>Código:</strong> <span class="code-code"><?php echo htmlspecialchars($codigo['codigo'] ?? ''); ?></span></p>
                        <p><strong>Posición:</strong> <span class="code-position <?php echo $visibilidad_class; ?>">#<?php echo $posicion; ?></span></p>
                        <p><strong>Visibilidad:</strong> <span class="visibility-badge visibility-<?php echo $visibilidad_class; ?>"><?php echo $visibilidad_text; ?></span></p>
                        <p><strong>Fecha:</strong> <?php echo isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'N/A'; ?></p>
                        <p><strong>Clicks:</strong> <?php echo $codigo['totalclicks'] ?? '0'; ?></p>
                        <p><strong>ID:</strong> <span class="code-id" onclick="copyToClipboard('<?php echo $codigo['_id']; ?>')" style="cursor: pointer; color: #ff6b35; font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 0.9rem;" title="Haz clic para copiar"><?php echo $codigo['_id']; ?></span></p>
                    </div>
                    
                     <div class="code-actions">
                         <a href="/destacar_codigo?codigo=<?php echo $codigo['_id']; ?>" class="btn-action btn-destacar">
                             <i class="fas fa-star"></i> Destacar
                         </a>
                        <a href="/modificar_codigo/<?php echo $codigo['_id']; ?>" class="btn-action btn-modificar">
                            <i class="fas fa-edit"></i> Modificar
                        </a>
                        <button onclick="mostrarEstadisticas('<?php echo $codigo['_id']; ?>', '<?php echo htmlspecialchars($codigo['marca'] ?? ''); ?>')" class="btn-action btn-estadisticas">
                            <i class="fas fa-chart-bar"></i> Estadísticas
                        </button>
                        <button class="btn-action btn-compartir" onclick="compartirCodigo('<?php echo $codigo['_id']; ?>', '<?php echo htmlspecialchars($codigo['marca'] ?? ''); ?>')">
                            <i class="fas fa-share"></i> Compartir
                        </button>
                        <button class="btn-action btn-eliminar" onclick="confirmarEliminarCodigo('<?php echo $codigo['_id']; ?>', '<?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-codes">
                <i class="fas fa-code"></i>
                <h3>No tienes códigos publicados</h3>
                <p>Comienza a publicar códigos de descuento para ayudar a otros usuarios a ahorrar.</p>
                <a href="/nuevo_codigo" class="btn-modern btn-success-modern">
                    <i class="fas fa-plus"></i> Publicar mi primer código
                </a>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>


<!-- Modal para recargar saldo -->
<div class="modal fade" id="modal_recargar_saldo" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content simple-recharge-modal">
            <div class="modal-header">
                <h5 class="modal-title">💳 Recargar Saldo</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="simple-packages">
                    <!-- Paquete 20€ -->
                    <div class="simple-package" data-package="20" data-amount="25">
                        <div class="package-badge">+25%</div>
                        <div class="package-price">
                            <div class="pay">20€</div>
                            <div class="get">25€</div>
                        </div>
                        <div class="package-savings">+5€ gratis</div>
                    </div>
                    
                    <!-- Paquete 40€ -->
                    <div class="simple-package popular" data-package="40" data-amount="50">
                        <div class="popular-label">MÁS POPULAR</div>
                        <div class="package-badge">+25%</div>
                        <div class="package-price">
                            <div class="pay">40€</div>
                            <div class="get">50€</div>
                        </div>
                        <div class="package-savings">+10€ gratis</div>
                    </div>
                    
                    <!-- Paquete 100€ -->
                    <div class="simple-package" data-package="100" data-amount="150">
                        <div class="package-badge">+50%</div>
                        <div class="package-price">
                            <div class="pay">100€</div>
                            <div class="get">150€</div>
                        </div>
                        <div class="package-savings">+50€ gratis</div>
                    </div>
                </div>
                
                <div class="security-note">
                    <i class="fas fa-shield-alt"></i>
                    <span>Pago seguro con Stripe</span>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Modal para destacar todos (splash) -->
<div class="modal fade" id="modal_destacar_todos" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Destacar Todos los Códigos (Splash)</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres destacar todos tus códigos en posición 1 por 9,99€?</p>
                <p>Esta acción destacará todos tus códigos de una vez con máxima visibilidad.</p>
                <p><strong>Costo total: 9,99€</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmar_destacar_todos">Confirmar y Pagar</button>
            </div>
        </div>
    </div>
</div>

<!-- Cargar Stripe -->
<script src="https://js.stripe.com/v3/"></script>

<script type="text/javascript">
var stripe = Stripe('<?php echo $stripe_live_publishable_key; ?>');

// Función global para filtrar por visibilidad
window.filterByVisibility = function(visibility) {
    // Actualizar botones activos
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Marcar el botón seleccionado como activo
    document.querySelector(`[data-visibility="${visibility}"]`).classList.add('active');
    
    // Obtener todas las tarjetas de código
    var codeItems = document.querySelectorAll('.code-item');
    var visibleCount = 0;
    
    codeItems.forEach(function(item) {
        var itemVisibility = item.getAttribute('data-visibilidad');
        var shouldShow = false;
        
        if (visibility === 'all') {
            shouldShow = true;
        } else if (visibility === 'alta' && itemVisibility === 'alta') {
            shouldShow = true;
        } else if (visibility === 'media' && itemVisibility === 'media') {
            shouldShow = true;
        } else if (visibility === 'baja' && itemVisibility === 'baja') {
            shouldShow = true;
        }
        
        if (shouldShow) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease-in';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Actualizar el título con el número de códigos visibles
    var titleElement = document.getElementById('codesTitle');
    if (titleElement) {
        if (visibility === 'all') {
            titleElement.textContent = 'Tus códigos (' + codeItems.length + ')';
        } else {
            titleElement.textContent = 'Tus códigos (' + visibleCount + ')';
        }
    }
    
    // Mostrar mensaje si no hay resultados
    var noResultsMessage = document.getElementById('no-results-message');
    if (visibleCount === 0 && visibility !== 'all') {
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('div');
            noResultsMessage.id = 'no-results-message';
            noResultsMessage.className = 'no-results';
            noResultsMessage.innerHTML = `
                <i class="fas fa-search"></i>
                <h3>No hay códigos con ${visibility} visibilidad</h3>
                <p>Intenta con otro filtro o publica más códigos.</p>
            `;
            document.querySelector('.codes-section').appendChild(noResultsMessage);
        }
        noResultsMessage.style.display = 'block';
    } else if (noResultsMessage) {
        noResultsMessage.style.display = 'none';
    }
};

// Función para mostrar modal de recargar saldo
function mostrarRecargarSaldo() {
    $('#modal_recargar_saldo').modal('show');
}

// Función para destacar todos los códigos (splash)
function destacarTodosSplash() {
    $('#modal_destacar_todos').modal('show');
}

// Seleccionar paquete de saldo y proceder directamente al pago
$(document).on('click', '.simple-package', function() {
    $('.simple-package').removeClass('selected');
    $(this).addClass('selected');
    paqueteSeleccionado = $(this).data('package');
    
    // Proceder directamente al pago
    if (paqueteSeleccionado) {
        var precio = paqueteSeleccionado;
        var saldo = $(this).data('amount');
        
        // Mostrar loading
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Procesando...');
        
        // Redirigir directamente a crear sesión de Stripe
        var url = '/public/crear_sesion_recarga.php?' + 
                  'paquete=' + encodeURIComponent(paqueteSeleccionado) +
                  '&precio=' + encodeURIComponent(precio) +
                  '&saldo=' + encodeURIComponent(saldo) +
                  '&usuario_id=' + encodeURIComponent('<?php echo $_SESSION["user_id"]; ?>');
        
        console.log('Redirigiendo a:', url);
        window.location.href = url;
    }
});


// Confirmar destacar todos (splash)
$('#confirmar_destacar_todos').click(function() {
    // Redirigir a destacar todos con saldo
    window.location.href = '/destacar_con_saldo?tipo=splash';
});

// Función para obtener el ID de precio según el paquete
function getPriceId(paquete) {
    switch(paquete) {
        case '20': return 'price_20euros_2000'; // 20€ = 2000 céntimos
        case '40': return 'price_40euros_4000'; // 40€ = 4000 céntimos
        case '100': return 'price_100euros_10000'; // 100€ = 10000 céntimos
        default: return 'price_20euros_2000';
    }
}

// Verificar que la función esté disponible
console.log('filterByVisibility function available:', typeof window.filterByVisibility);

// Variables globales para el sistema de saldo
var paqueteSeleccionado = null;

$(document).ready(function() {
    
    // Ordenar códigos por defecto (más recientes primero)
    sortCodes('fecha_desc');
    
    // Destacar todos los códigos
    $('#destacar_todos').click(function() {
        stripe.redirectToCheckout({
            lineItems: [{price: '<?php echo $sku_patrocinado_splash; ?>', quantity: 1}],
            mode: 'payment',
            clientReferenceId: '<?php echo $_SESSION["user_id"]; ?>',
            billingAddressCollection: 'auto',
            successUrl: '<?php echo $GLOBALS["website"];?>felicidades_splash?session_id={CHECKOUT_SESSION_ID}&todos=1',
            cancelUrl: '<?php echo $GLOBALS["actual_url"]; ?>',
        }).then(function (result) {
            if (result.error) {
                var displayError = document.getElementById('error-message');
                if(displayError) displayError.textContent = result.error.message;
            }
        });
    });
});


// Función para mostrar todos los códigos
function showAllCodes() {
    // Remover clase active de todas las pestañas
    $('.tab-button').removeClass('active');
    
    // Activar pestaña "TODOS"
    $('.tab-button[data-visibility="all"]').addClass('active');
    
    // Mostrar todos los códigos
    $('.code-item').show();
    
    // Actualizar contador de códigos visibles
    updateVisibleCount();
}

// Función para actualizar el contador de códigos visibles
function updateVisibleCount() {
    var visibleCount = $('.code-item:visible').length;
    var totalCount = $('.code-item').length;
    
    // Actualizar el título de la sección
    $('#codesTitle').text('Tus códigos (' + visibleCount + ' de ' + totalCount + ')');
}

// Función para compartir código
function compartirCodigo(codigoId) {
    // Aquí puedes implementar la lógica para compartir
    alert('Función de compartir código: ' + codigoId);
}

// Función para seleccionar radio button y aplicar filtros automáticamente
function selectRadio(radioId) {
    document.getElementById(radioId).checked = true;
    applyFilters();
}

// Función para toggle checkbox y aplicar filtros automáticamente
function toggleCheckbox(checkboxId) {
    var checkbox = document.getElementById(checkboxId);
    checkbox.checked = !checkbox.checked;
    applyFilters();
}

// Función para aplicar todos los filtros
function applyFilters() {
    console.log('Aplicando filtros...');
    
    // Obtener filtro de fecha seleccionado
    var fechaFiltro = 'todo'; // Valor por defecto
    var fechaRadio = document.querySelector('input[name="fecha_filtro"]:checked');
    if(fechaRadio) {
        fechaFiltro = fechaRadio.value;
    }
    console.log('Filtro de fecha:', fechaFiltro);
    
    // Obtener categorías seleccionadas
    var categoriasSeleccionadas = [];
    document.querySelectorAll('#categoria-filters input[type="checkbox"]:checked').forEach(function(checkbox) {
        var categoria = checkbox.closest('.filter-option').getAttribute('data-categoria');
        if(categoria) {
            categoriasSeleccionadas.push(categoria);
        }
    });
    console.log('Categorías seleccionadas:', categoriasSeleccionadas);
    
    // Obtener visibilidades seleccionadas
    var visibilidadesSeleccionadas = [];
    document.querySelectorAll('input[type="checkbox"][id^="vis_"]:checked').forEach(function(checkbox) {
        var visibilidad = checkbox.closest('.filter-option').getAttribute('data-visibilidad');
        if(visibilidad) {
            visibilidadesSeleccionadas.push(visibilidad);
        }
    });
    console.log('Visibilidades seleccionadas:', visibilidadesSeleccionadas);
    
    // Obtener ordenamiento seleccionado
    var ordenamiento = 'fecha_desc'; // Valor por defecto
    var ordenRadio = document.querySelector('input[name="orden_filtro"]:checked');
    if(ordenRadio) {
        ordenamiento = ordenRadio.value;
    }
    console.log('Ordenamiento:', ordenamiento);
    
    // Filtrar códigos
    console.log('Total códigos a filtrar:', $('.code-item').length);
    var visibleCount = 0;
    
    $('.code-item').each(function() {
        var $item = $(this);
        var showItem = true;
        
        // Filtro por fecha
        if(fechaFiltro !== 'todo') {
            var fechaCodigo = $item.data('fecha');
            if(fechaCodigo) {
                var hoy = new Date();
                var fechaCodigoObj = new Date(fechaCodigo);
                
                if(fechaFiltro === 'hoy') {
                    // Solo códigos de hoy
                    var inicioDia = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
                    var finDia = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + 1);
                    if(fechaCodigoObj < inicioDia || fechaCodigoObj >= finDia) {
                        showItem = false;
                    }
                } else if(fechaFiltro === 'semana') {
                    // Códigos de la semana pasada
                    var haceUnaSemana = new Date(hoy.getTime() - 7 * 24 * 60 * 60 * 1000);
                    if(fechaCodigoObj < haceUnaSemana) {
                        showItem = false;
                    }
                }
            } else {
                showItem = false;
            }
        }
        
        // Filtro por categoría
        if(categoriasSeleccionadas.length > 0) {
            var categoriaCodigo = $item.data('categoria');
            if(!categoriaCodigo || categoriasSeleccionadas.indexOf(categoriaCodigo) === -1) {
                showItem = false;
            }
        }
        
        // Filtro por visibilidad
        if(visibilidadesSeleccionadas.length > 0) {
            var visibilidadCodigo = $item.data('visibilidad');
            if(!visibilidadCodigo || visibilidadesSeleccionadas.indexOf(visibilidadCodigo) === -1) {
                showItem = false;
            }
        }
        
        if(showItem) {
            $item.show();
            visibleCount++;
        } else {
            $item.hide();
        }
    });
    
    console.log('Códigos visibles después del filtrado:', visibleCount);
    
    // Aplicar ordenamiento a los elementos visibles usando la misma lógica que sortCodes()
    var $visibleItems = $('.code-item:visible');
    var $container = $('.codes-section');
    
    $visibleItems.sort(function(a, b) {
        switch(ordenamiento) {
            case 'fecha_desc':
                return new Date($(b).data('fecha') || 0) - new Date($(a).data('fecha') || 0);
            case 'fecha_asc':
                return new Date($(a).data('fecha') || 0) - new Date($(b).data('fecha') || 0);
            case 'marca_asc':
                return ($(a).data('marca') || '').localeCompare($(b).data('marca') || '');
            case 'marca_desc':
                return ($(b).data('marca') || '').localeCompare($(a).data('marca') || '');
            case 'clicks_desc':
                return parseInt($(b).data('clicks') || 0) - parseInt($(a).data('clicks') || 0);
            case 'clicks_asc':
                return parseInt($(a).data('clicks') || 0) - parseInt($(b).data('clicks') || 0);
            case 'beneficio_desc':
                return parseInt($(b).data('beneficio') || 0) - parseInt($(a).data('beneficio') || 0);
            case 'beneficio_asc':
                return parseInt($(a).data('beneficio') || 0) - parseInt($(b).data('beneficio') || 0);
            case 'visibilidad':
                var visA = $(a).data('visibilidad') || 'baja';
                var visB = $(b).data('visibilidad') || 'baja';
                var order = {'alta': 1, 'media': 2, 'baja': 3};
                return order[visA] - order[visB];
            case 'categoria':
                var catA = $(a).data('categoria') || '';
                var catB = $(b).data('categoria') || '';
                return catA.localeCompare(catB);
            default:
                return 0;
        }
    });
    
    // Reordenar los elementos en el DOM
    if($container.length > 0) {
        $visibleItems.detach().appendTo($container);
    }
    
    // Actualizar contador
    updateVisibleCount();
    
    // Actualizar clases activas de filtros
    updateFilterStates();
}

// Función para limpiar todos los filtros
function clearAllFilters() {
    // Resetear radio button de fecha a "todo el tiempo"
    document.getElementById('fecha_todo').checked = true;
    
    // Desmarcar todos los checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
        checkbox.checked = false;
    });
    
    // Mostrar todos los códigos
    $('.code-item').show();
    
    // Actualizar contador
    updateVisibleCount();
    
    // Actualizar clases activas
    updateFilterStates();
}

// Función para actualizar el estado visual de los filtros
function updateFilterStates() {
    // Actualizar clases de opciones de filtro
    document.querySelectorAll('.filter-option').forEach(function(option) {
        var checkbox = option.querySelector('input[type="checkbox"]');
        if(checkbox && checkbox.checked) {
            option.classList.add('active');
        } else {
            option.classList.remove('active');
        }
    });
}

// Función para alternar la barra lateral en móviles
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
    
    // Prevenir scroll del body cuando el menú está abierto
    if (sidebar.classList.contains('open')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
}

// Función para cerrar la barra lateral
function closeSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = 'auto';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    var overlay = document.getElementById('sidebar-overlay');
    var toggleButton = document.querySelector('.mobile-filter-toggle');
    
    // Cerrar menú al hacer clic en el overlay
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Cerrar menú al hacer clic fuera en móviles
    document.addEventListener('click', function(event) {
        var sidebar = document.getElementById('sidebar');
        
        if(window.innerWidth <= 768 && 
           sidebar && sidebar.classList.contains('open') &&
           !sidebar.contains(event.target) && 
           (!toggleButton || !toggleButton.contains(event.target))) {
            closeSidebar();
        }
    });
    
    // Cerrar menú al redimensionar la ventana a desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
});

// Función para compartir código
function compartirCodigo(codigoId, marca) {
    const shareUrl = `https://www.codigoamigo.com/de-${marca.toLowerCase()}?codigo=${codigoId}`;
    const shareText = `¡Mira este código de descuento de ${marca}!`;
    
    if (navigator.share) {
        // Usar Web Share API si está disponible
        navigator.share({
            title: `Código de descuento ${marca}`,
            text: shareText,
            url: shareUrl
        }).catch(function(err) {
            console.log('Error al compartir:', err);
            // Fallback a copiar al portapapeles
            copiarEnlace(shareUrl);
        });
    } else {
        // Fallback para navegadores que no soportan Web Share API
        copiarEnlace(shareUrl);
    }
}

// Función para copiar enlace al portapapeles
function copiarEnlace(url) {
    navigator.clipboard.writeText(url).then(function() {
        // Mostrar notificación de éxito
        mostrarNotificacion('Enlace copiado al portapapeles', 'success');
    }).catch(function(err) {
        console.error('Error al copiar:', err);
        // Fallback manual
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        mostrarNotificacion('Enlace copiado al portapapeles', 'success');
    });
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    notificacion.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${mensaje}</span>
    `;
    
    // Agregar estilos
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${tipo === 'success' ? '#28a745' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    `;
    
    // Agregar animación CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Agregar al DOM
    document.body.appendChild(notificacion);
    
    // Remover después de 3 segundos
    setTimeout(function() {
        notificacion.style.animation = 'slideOut 0.3s ease';
        setTimeout(function() {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}

// Función global para cerrar todos los modales
function cerrarTodosLosModales() {
    const modales = document.querySelectorAll('[id*="Modal"], .modal');
    modales.forEach(modal => {
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
    });
    document.body.style.overflow = 'auto';
}

// Función para mostrar estadísticas en modal
function mostrarEstadisticas(codigoId, marca) {
    // Cerrar cualquier modal existente antes de abrir uno nuevo
    cerrarTodosLosModales();
    
    // Crear iframe para cargar las estadísticas
    const modal = document.createElement('div');
    modal.id = 'estadisticasModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    `;
    
    const iframe = document.createElement('iframe');
    iframe.src = `/estadisticas?codigo=${codigoId}`;
    iframe.style.cssText = `
        width: 100%;
        max-width: 800px;
        height: 90vh;
        border: none;
        border-radius: 12px;
        background: white;
    `;
    
    const closeButton = document.createElement('button');
    closeButton.innerHTML = '&times;';
    closeButton.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        background: #ff6b35;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
        cursor: pointer;
        z-index: 10001;
    `;
    closeButton.onclick = () => {
        cerrarTodosLosModales();
    };
    
    modal.appendChild(iframe);
    modal.appendChild(closeButton);
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Cerrar modal al hacer click fuera del iframe
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            cerrarTodosLosModales();
        }
    });
    
    // Cerrar modal con tecla Escape
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            cerrarTodosLosModales();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

// Función para copiar ID al portapapeles
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // Usar la API moderna de clipboard
        navigator.clipboard.writeText(text).then(function() {
            showCopyNotification('ID copiado al portapapeles');
        }).catch(function(err) {
            console.error('Error al copiar: ', err);
            fallbackCopyTextToClipboard(text);
        });
    } else {
        // Fallback para navegadores más antiguos
        fallbackCopyTextToClipboard(text);
    }
}

// Función fallback para copiar texto
function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            showCopyNotification('ID copiado al portapapeles');
        } else {
            showCopyNotification('Error al copiar', 'error');
        }
    } catch (err) {
        console.error('Error al copiar: ', err);
        showCopyNotification('Error al copiar', 'error');
    }
    
    document.body.removeChild(textArea);
}

// Función para mostrar notificación de copia
function showCopyNotification(message, type = 'success') {
    // Crear elemento de notificación
    var notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-size: 14px;
        font-weight: 500;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}


// Agregar animación CSS para el fadeIn
var style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-10px); }
    }
    
    .no-results {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
        background: #f8f9fa;
        border-radius: 12px;
        margin: 20px 0;
    }
    
    .no-results i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 20px;
    }
    
    .no-results h3 {
        color: #495057;
        margin-bottom: 10px;
    }
    
    /* Estilos simples para modal de recargar saldo */
    .simple-recharge-modal {
        border-radius: 15px;
        overflow: hidden;
    }
    
    .simple-packages {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin: 20px 0;
    }
    
    .simple-package {
        position: relative;
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .simple-package:hover {
        border-color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }
    
    .simple-package.selected {
        border-color: #007bff;
        background: #f8f9ff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }
    
    .simple-package.popular {
        border-color: #28a745;
        background: #f8fff9;
    }
    
    .popular-label {
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        background: #28a745;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .package-badge {
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 15px;
    }
    
    .package-price {
        margin: 15px 0;
    }
    
    .pay {
        color: #6c757d;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .get {
        color: #007bff;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .package-savings {
        color: #28a745;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .security-note {
        text-align: center;
        color: #6c757d;
        font-size: 0.9rem;
        margin-top: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .security-note i {
        color: #28a745;
        margin-right: 5px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .simple-packages {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .simple-package {
            padding: 15px;
        }
    }
    
    .package-popular:hover {
        border-color: #9c27b0;
        background: linear-gradient(135deg, #f3e5f5, #e1bee7);
        box-shadow: 0 10px 30px rgba(156, 39, 176, 0.3);
    }
    
    .package-premium {
        border-color: #ff9800;
        background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    }
    
    .package-premium:hover {
        border-color: #ff9800;
        background: linear-gradient(135deg, #fff3e0, #ffe0b2);
        box-shadow: 0 10px 30px rgba(255, 152, 0, 0.3);
    }
    
    .package-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: linear-gradient(135deg, #ff6b35, #e55a2b);
        color: white;
        padding: 8px 20px;
        border-radius: 0 20px 0 20px;
        font-size: 0.8rem;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
    }
    
    .package-badge.popular {
        background: linear-gradient(135deg, #9c27b0, #7b1fa2);
    }
    
    .package-badge.premium {
        background: linear-gradient(135deg, #ff9800, #f57c00);
    }
    
    .popular-ribbon {
        position: absolute;
        top: 15px;
        left: -30px;
        background: #9c27b0;
        color: white;
        padding: 5px 40px;
        font-size: 0.7rem;
        font-weight: bold;
        transform: rotate(-45deg);
        box-shadow: 0 2px 8px rgba(156, 39, 176, 0.3);
    }
    
    .package-icon {
        font-size: 3rem;
        margin: 15px 0;
        color: #ff6b35;
    }
    
    .package-popular .package-icon {
        color: #9c27b0;
    }
    
    .package-premium .package-icon {
        color: #ff9800;
    }
    
    .package-pricing {
        margin: 20px 0;
    }
    
    .pay-amount {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 8px;
    }
    
    .get-amount {
        font-size: 1.8rem;
        font-weight: bold;
        color: #ff6b35;
        margin-bottom: 15px;
    }
    
    .package-popular .get-amount {
        color: #9c27b0;
    }
    
    .package-premium .get-amount {
        color: #ff9800;
    }
    
    .package-bonus {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 12px 20px;
        border-radius: 25px;
        font-weight: bold;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    
    .package-bonus.premium-bonus {
        background: linear-gradient(135deg, #ff9800, #ff5722);
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
    }
    
    .package-bonus i {
        font-size: 1.2rem;
    }
`;
document.head.appendChild(style);

// Función para ordenar códigos
window.sortCodes = function(sortBy) {
    const codesContainer = document.querySelector('.codes-section');
    if (!codesContainer) return;
    
    const codes = Array.from(codesContainer.querySelectorAll('.code-item'));
    
    codes.sort((a, b) => {
        switch(sortBy) {
            case 'fecha_desc':
                return new Date(b.dataset.fecha) - new Date(a.dataset.fecha);
            case 'fecha_asc':
                return new Date(a.dataset.fecha) - new Date(b.dataset.fecha);
            case 'marca_asc':
                return (a.dataset.marca || '').localeCompare(b.dataset.marca || '');
            case 'marca_desc':
                return (b.dataset.marca || '').localeCompare(a.dataset.marca || '');
            case 'clicks_desc':
                return parseInt(b.dataset.clicks || 0) - parseInt(a.dataset.clicks || 0);
            case 'clicks_asc':
                return parseInt(a.dataset.clicks || 0) - parseInt(b.dataset.clicks || 0);
            case 'beneficio_desc':
                return parseInt(b.dataset.beneficio || 0) - parseInt(a.dataset.beneficio || 0);
            case 'beneficio_asc':
                return parseInt(a.dataset.beneficio || 0) - parseInt(b.dataset.beneficio || 0);
            default:
                return 0;
        }
    });
    
    // Reorganizar los elementos en el DOM
    codes.forEach(code => codesContainer.appendChild(code));
    
    // Mostrar mensaje de ordenación
    console.log('Códigos ordenados por:', sortBy);
};

// Función para confirmar eliminación de código
function confirmarEliminarCodigo(codigoId, marcaNombre) {
    // Crear modal de confirmación
    const modal = document.createElement('div');
    modal.id = 'modalEliminarCodigo';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        ">
            <div style="color: #dc3545; font-size: 3rem; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="color: #333; margin-bottom: 15px; font-size: 1.5rem;">
                ¿Eliminar código?
            </h3>
            <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">
                ¿Estás seguro de que quieres eliminar el código de <strong>${marcaNombre}</strong>?<br>
                <span style="color: #dc3545; font-weight: bold;">Esta acción no se puede deshacer.</span>
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button id="cancelarEliminar" style="
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 12px 25px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button id="confirmarEliminar" style="
                    background: #dc3545;
                    color: white;
                    border: none;
                    padding: 12px 25px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Event listeners
    document.getElementById('cancelarEliminar').onclick = () => {
        document.body.removeChild(modal);
        document.body.style.overflow = 'auto';
    };
    
    document.getElementById('confirmarEliminar').onclick = () => {
        eliminarCodigo(codigoId, marcaNombre);
        document.body.removeChild(modal);
        document.body.style.overflow = 'auto';
    };
    
    // Cerrar modal al hacer click fuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
            document.body.style.overflow = 'auto';
        }
    };
}

// Función para eliminar el código usando formulario tradicional (evita bloqueo de Cloudflare)
function eliminarCodigo(codigoId, marcaNombre) {
    // Crear formulario para enviar petición POST tradicional
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/delete_code_action';
    form.style.display = 'none';
    
    // Añadir campo oculto con el ID del código
    const codigoInput = document.createElement('input');
    codigoInput.type = 'hidden';
    codigoInput.name = 'codigo_id';
    codigoInput.value = codigoId;
    form.appendChild(codigoInput);
    
    // Añadir formulario al DOM y enviarlo
    document.body.appendChild(form);
    form.submit();
}

// Función para actualizar el contador de códigos
function actualizarContadorCodigos() {
    const codigosVisibles = document.querySelectorAll('.code-item:not([style*="display: none"])').length;
    const titulo = document.getElementById('codesTitle');
    if (titulo) {
        titulo.textContent = `Tus códigos (${codigosVisibles})`;
    }
}
</script>

<!-- Footer con sección de ayuda -->
<footer class="footer-modern" style="background: #2c2c2c; padding: 40px 20px; margin-top: auto; text-align: center; position: relative; bottom: 0; left: 0; right: 0; width: 100%;">
    <div class="container" style="max-width: 1200px; margin: 0 auto;">
        <div class="help-section" style="background: linear-gradient(135deg, #ff6b35, #e55a2b); padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(255, 107, 53, 0.3);">
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 15px;">
                <i class="fab fa-telegram-plane" style="font-size: 2.5rem; color: white;"></i>
                <div>
                    <h3 style="color: white; margin: 0; font-size: 1.8rem; font-weight: bold;">¿Necesitas ayuda?</h3>
                    <p style="color: white; margin: 5px 0 0 0; font-size: 1.2rem; opacity: 0.9;">¡Escríbenos!</p>
                </div>
            </div>
            <a href="https://t.me/spnfury" target="_blank" style="display: inline-block; background: white; color: #ff6b35; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 3px 10px rgba(0,0,0,0.2);">
                <i class="fab fa-telegram" style="margin-right: 8px;"></i>
                Contactar por Telegram
            </a>
        </div>
        
        <div class="footer-links" style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="/" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Inicio</a>
            <a href="/todos-los-codigos" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Todos los Códigos</a>
            <a href="/marcas" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Marcas</a>
            <a href="/categorias" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Categorías</a>
        </div>
        
        <div class="footer-bottom" style="border-top: 1px solid #404040; padding-top: 20px; color: #888888; font-size: 0.9rem;">
            <p style="margin: 0;">© 2024 Código Amigo - Códigos verificados, gente real</p>
        </div>
    </div>
</footer>

<style>
.footer-modern a:hover {
    color: #ff6b35 !important;
    transform: translateY(-2px);
}

.help-section a:hover {
    background: #ff6b35 !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3) !important;
}

@media (max-width: 768px) {
    .footer-links {
        flex-direction: column;
        gap: 15px;
    }
    
    .help-section {
        padding: 20px !important;
    }
    
    .help-section h3 {
        font-size: 1.5rem !important;
    }
    
    .help-section p {
        font-size: 1rem !important;
    }
}
</style>

<?php get_footer(); ?>