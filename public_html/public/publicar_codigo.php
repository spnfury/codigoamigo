<?php
    // Deshabilitar AdSense en esta página
    $anula_adsense = true;
    
    global $array_descuentos;
    
    // Incluir funciones de AdSense
    include_once __DIR__ . '/../myphp/funciones_adsense.php';
    
    $modo_modificacion = isset($codigo_data);
    $marca_es_nueva = false; // Variable para indicar si la marca es nueva

    if ($modo_modificacion) {
        $marca = $codigo_data['marca'];
        $beneficio = $codigo_data['num_beneficio'];
        $descuento = $codigo_data['descuento'];
        $codigo = $codigo_data['codigo'];
        $descripcion = $codigo_data['descripcion'];
        $provincia = $codigo_data['provincia'];
        $localidad = $codigo_data['localidad'];
        $fecha_caducidad = $codigo_data['fecha_caducidad'];
    } else {
        // Verificar si hay datos preservados de un error anterior
        if (isset($_SESSION['form_data']) && !empty($_SESSION['form_data'])) {
            $form_data = $_SESSION['form_data'];
            $marca = $form_data['marca_valor'] ?? $form_data['marca'] ?? '';
            $beneficio = $form_data['num_beneficio'] ?? '';
            $descuento = $form_data['descuento'] ?? '';
            $codigo = $form_data['codigo'] ?? '';
            $descripcion = $form_data['descripcion'] ?? '';
            $provincia = $form_data['provincia'] ?? '';
            $localidad = $form_data['localidad'] ?? '';
            $fecha_caducidad = $form_data['fecha_caducidad'] ?? '';

            // Limpiar datos de la sesión después de usarlos
            unset($_SESSION['form_data']);
        } else {
            // Si hay una marca en la URL, preseleccionarla
            $marca_param = isset($_GET['marca']) ? $_GET['marca'] : '';
            $beneficio = $descuento = $codigo = $descripcion = $provincia = $localidad = $fecha_caducidad = '';
            
            $marca = '';
            $marca_es_nueva = false;
            
            // Si viene de la URL, verificar si la marca existe en la BD
            if (!empty($marca_param)) {
                // Intentar buscar por slug (nombre_clave) primero
                $marca_existe = getObjectMarca('nombre_clave', strtolower($marca_param));
                
                // Si no se encuentra por slug, intentar por nombre (case-insensitive para mayor robustez)
                if (!$marca_existe) {
                    $marca_existe = getObjectMarca('nombre', $marca_param);
                }
                
                // Si aún no se encuentra, probar con el nombre en mayúsculas (comportamiento anterior)
                if (!$marca_existe) {
                    $marca_existe = getObjectMarca('nombre', strtoupper($marca_param));
                }

                if ($marca_existe) {
                    // Si existe, usar el nombre original de la marca para preselección
                    $marca = $marca_existe['nombre'];
                } else {
                    // Si no existe, marcar como nueva marca y usar el parámetro tal cual
                    $marca_es_nueva = true;
                    $marca = $marca_param;
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $modo_modificacion ? 'Modificar Código' : 'Nuevo Código'; ?> - Código Amigo</title>
    <link rel="shortcut icon" href="/img/favicon_moneda_real.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #2C2C2C;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: #E30613;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
        }
        .back-button:hover {
            background: #C40510;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4);
        }
        .back-button i {
            margin-right: 8px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 80px 20px 40px;
        }

        /* Mejorar el diseño del formulario */
        .form-group {
            margin-bottom: 25px;
        }

        .form-control {
            transition: all 0.3s ease;
        }

        .form-control:focus {
            transform: translateY(-1px);
        }

        /* Mejorar apariencia del botón de submit */
        .btn-custom {
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(227, 6, 19, 0.4);
        }

        /* Responsive design improvements */
        @media (max-width: 768px) {
            .container {
                padding: 70px 15px 30px;
            }

            .page-title {
                font-size: 2rem;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .btn-custom {
                width: 100%;
                padding: 18px 30px;
                font-size: 16px;
            }
        }

        .form-control {
            background: white;
            color: #333;
            border: 2px solid #555;
            border-radius: 8px;
            padding: 2px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #E30613;
            box-shadow: 0 0 0 0.2rem rgba(227, 6, 19, 0.25);
            transform: translateY(-1px);
        }

        .form-control.error {
            border-color: #f44336;
            box-shadow: 0 0 0 0.2rem rgba(244, 67, 54, 0.25);
            background-color: #ffebee;
        }

        .error-message {
            color: #f44336;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        /* Botón de IA Estilo Moderno */
        .btn-ai-magic {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-left: 10px;
            vertical-align: middle;
            box-shadow: 0 2px 8px rgba(110, 142, 251, 0.3);
        }

        .btn-ai-magic:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(110, 142, 251, 0.4);
            filter: brightness(1.1);
        }

        .btn-ai-magic i {
            font-size: 14px;
        }

        .btn-ai-magic.loading {
            opacity: 0.7;
            cursor: wait;
        }

        .btn-ai-magic.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .vip-badge-mini {
            background: #ffd700;
            color: #333;
            font-size: 9px;
            padding: 1px 5px;
            border-radius: 4px;
            font-weight: 800;
            text-transform: uppercase;
        }

        /* Estilos Modal VIP Moderno */
        #modal-vip-upgrade .modal-content {
            background: #1a1a1a;
            color: white;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #333;
        }

        #modal-vip-upgrade .modal-header {
            border-bottom: 1px solid #333;
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
            padding: 20px;
        }

        #modal-vip-upgrade .modal-title {
            color: #ffd700;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vip-feature-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            text-align: left;
        }

        .vip-feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .vip-feature-item i {
            color: #ffd700;
            margin-top: 4px;
        }

        .btn-upgrade-now {
            background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 18px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
        }

        .btn-upgrade-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 6, 19, 0.5);
            color: white;
            text-decoration: none;
        }
        .alert {
            border-radius: 8px;
            font-size: 16px;
            margin: 20px 0;
        }
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: bold;
            color: #E30613;
        }
        .form-group label {
            color: white;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .help-text {
            color: #ccc;
            font-size: 14px;
            margin-top: 5px;
        }
        .warning-text {
            color: #E30613;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            font-size: 16px;
        }
        
        /* Estilos personalizados para Select2 - Tema claro para el formulario */
        .select2-container--default .select2-selection--single {
            background-color: white !important;
            border: 2px solid #555 !important;
            border-radius: 8px !important;
            height: 48px !important;
            color: #333 !important;
        }

        .select2-container--default .select2-selection__rendered {
            color: #333 !important;
            line-height: 44px !important;
            padding-left: 15px !important;
            padding-right: 30px !important;
            font-size: 16px !important;
        }

        .select2-container--default .select2-selection__placeholder {
            color: #999 !important;
        }

        .select2-container--default .select2-dropdown {
            background-color: white !important;
            border: 1px solid #555 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }

        .select2-container--default .select2-results__option {
            background-color: white !important;
            color: #333 !important;
            padding: 12px 15px !important;
        }

        .select2-container--default .select2-results__option--highlighted {
            background-color: #E30613 !important;
            color: white !important;
        }

        .select2-container--default .select2-search__field {
            background-color: white !important;
            border: 1px solid #ddd !important;
            color: #333 !important;
            padding: 10px 15px !important;
            border-radius: 6px !important;
            margin: 8px !important;
        }
        
        /* Estilos para contenedores de publicidad */
        .adsense-container {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #2a2a2a;
            border-radius: 10px;
            border: 1px solid #444;
        }
        
        .adsense-top {
            margin: 30px 0;
        }
        
        .adsense-middle {
            margin: 30px 0;
        }
        
        .adsense-bottom {
            margin: 30px 0;
        }
        
        /* Responsive para publicidad */
        @media (max-width: 768px) {
            .adsense-container {
                margin: 15px 0;
                padding: 10px;
            }
        }
        /* Asegurar que el selector de marcas tenga prioridad sobre otros estilos */
        .select2-container {
            z-index: 9999 !important;
        }

        /* Estilos específicos para el contenedor del selector de marcas */
        #marca + .select2-container {
            width: 100% !important;
        }

        /* Estilos para asegurar que el dropdown se vea correctamente */
        .select2-dropdown {
            z-index: 10000 !important;
        }
        
        /* Botón flotante para modificar código */
        .floating-save-button {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: #E30613;
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(227, 6, 19, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
            justify-content: center;
        }
        
        .floating-save-button:hover {
            background: #C40510;
            transform: translateX(-50%) translateY(-3px);
            box-shadow: 0 12px 35px rgba(227, 6, 19, 0.5);
        }
        
        .floating-save-button:active {
            transform: translateX(-50%) translateY(-1px);
        }

        .floating-save-button:disabled {
            background: #ccc;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
            box-shadow: none;
        }

        .floating-save-button:disabled:hover {
            transform: none;
            box-shadow: none;
            background: #ccc;
        }
        
        /* Ocultar el botón original solo cuando está el flotante (modo modificación) */
        .original-save-button {
            position: relative;
            display: block;
        }
        
        /* Ocultar el botón original cuando está el flotante en modo modificación */
        .modo-modificacion .original-save-button {
            display: none;
        }
        
        /* Ajustar el padding del contenedor para el botón flotante */
        .container {
            padding-bottom: 100px;
        }
        
        /* Responsive para móvil */
        @media (max-width: 768px) {
            .floating-save-button {
                left: 20px;
                right: 20px;
                transform: none;
                width: calc(100% - 40px);
                bottom: 15px;
                padding: 16px 30px;
                font-size: 16px;
            }
            
            .floating-save-button:hover {
                transform: translateY(-3px);
            }
            
            .floating-save-button:active {
                transform: translateY(-1px);
            }

            .floating-save-button:disabled {
                background: #ccc !important;
                color: #999 !important;
                transform: none !important;
            }
        }
        
        /* Estilos para la nueva marca integrada */
        .nueva-marca-container {
            background: #f8f9fa;
            border: 2px solid #E30613;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(227, 6, 19, 0.1);
        }
        
        .nueva-marca-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .nueva-marca-header h4 {
            color: #E30613;
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .btn-cancelar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-cancelar:hover {
            background: #c82333;
        }
        
        .nueva-marca-content {
            background: white;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        
        .nueva-marca-content .form-group {
            margin-bottom: 20px;
        }
        
        .nueva-marca-content .form-group:last-child {
            margin-bottom: 0;
        }
        
        .nueva-marca-content label {
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }
        
        .nueva-marca-content select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E30613;
            border-radius: 6px;
            background: white;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ff6b35' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
            min-height: 48px;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .nueva-marca-content select:focus {
            outline: none;
            border-color: #C40510;
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.2);
        }
        
        .nueva-marca-content select option {
            padding: 8px 12px;
            color: #333;
            background: white;
        }
        
        .nueva-marca-content select option:hover {
            background: #f8f9fa;
        }
        
        .imagenes-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px;
            background: #f8f9fa;
        }
        
        .inserta_imagenes {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }
        
        .inserta_imagenes li {
            position: relative;
        }
        
        .item_imagen {
            border: 2px solid transparent;
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .item_imagen:hover {
            border-color: #E30613;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(227, 6, 19, 0.2);
        }
        
        .item_imagen.img-selected {
            border-color: #E30613;
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.3);
        }
        
        .item_imagen img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            display: block;
        }
        
        /* Responsive para móvil */
        @media (max-width: 768px) {
            .nueva-marca-container {
                padding: 15px;
                margin-top: 10px;
            }
            
            .nueva-marca-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .nueva-marca-header h4 {
                font-size: 16px;
            }
            
            .nueva-marca-content {
                padding: 15px;
            }
            
            .inserta_imagenes {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 8px;
            }
            
            .item_imagen img {
                height: 60px;
            }
        }
    </style>

    <!-- Cargar jQuery y Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <?php
    // Obtener lista de marcas con Super Landing para el JS
    include_once __DIR__ . '/../myphp/_super_landing_functions.php';
    $super_landings_list = [];
    if (function_exists('get_active_super_landings')) {
        $raw_landings = get_active_super_landings(50);
        foreach ($raw_landings as $landing) {
            // Extraer slugs de marcas vinculadas
            if (isset($landing['linked_brand_slugs'])) {
                foreach ($landing['linked_brand_slugs'] as $bslug) {
                    $super_landings_list[$bslug] = $landing['slug'];
                }
            }
            // Fallback slug propio (ej: ing-cuenta-nomina -> ing-direct logic handling needs mapped brand slug)
            // hardcodeamos mapeos comunes si es necesario, o confiamos en linked_brand_slugs
            if (strpos($landing['slug'], 'ing-') !== false) $super_landings_list['ing-direct'] = $landing['slug'];
            if (strpos($landing['slug'], 'ing-') !== false) $super_landings_list['ing'] = $landing['slug'];
        }
    }
    ?>
    <script>
        window.superLandingsMap = <?php echo json_encode($super_landings_list); ?>;
        window.isVipUser = <?php echo (isset($_SESSION['user_id']) && function_exists('es_usuario_vip') && es_usuario_vip($_SESSION['user_id'])) ? 'true' : 'false'; ?>;
    </script>
</head>
<body>
    <button class="back-button" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i>
        Volver
    </button>

    <div class="container<?php echo $modo_modificacion ? ' modo-modificacion' : ''; ?>">
        <h1 class="page-title">
            <?php echo $modo_modificacion ? 'MODIFICAR CÓDIGO' : 'NUEVO CÓDIGO AMIGO'; ?>
        </h1>

        <?php if(isset($_SESSION['msg_error']) && $_SESSION['msg_error'] != "") { ?>
            <div class="alert alert-danger" style="background: #f44336; color: white; padding: 15px; border-radius: 8px; margin: 20px 0; border: none; font-size: 1.6rem; text-align: center; box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);">
                <i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i>
                <?php echo $_SESSION['msg_error']; ?>
            </div>
            <?php unset($_SESSION['msg_error']); ?>
        <?php } ?>

        <?php if(isset($_SESSION['msg_info']) && $_SESSION['msg_info'] != "") { ?>
            <div class="alert alert-info" style="background: #2196F3; color: white; padding: 15px; border-radius: 8px; margin: 20px 0; border: none; font-size: 1.4rem; text-align: center; box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);">
                <i class="fas fa-info-circle" style="margin-right: 10px;"></i>
                <?php echo $_SESSION['msg_info']; ?>
            </div>
            <?php unset($_SESSION['msg_info']); ?>
        <?php } ?>

        <?php if(isset($_SESSION['msg_success']) && $_SESSION['msg_success'] != "") { ?>
            <div class="alert alert-success" style="background: #4CAF50; color: white; padding: 15px; border-radius: 8px; margin: 20px 0; border: none; font-size: 1.6rem; text-align: center; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">
                <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
                <?php echo $_SESSION['msg_success']; ?>
            </div>
            <?php unset($_SESSION['msg_success']); ?>
        <?php } ?>

        <p class="text-center" style="color: #ccc; margin-bottom: 30px;">
            Asegúrate de introducir correctamente estos datos.<br>
            Si tienes alguna duda, puedes comunicarte con nosotros en <strong>info@codigoamigo.com</strong>
        </p>
        
        <?php 
        // Publicidad superior
        if (should_show_adsense()) {
            echo generate_adsense_container(get_adsense_top(), 'adsense-top', 'margin: 30px 0;');
        }
        ?>

        <form id="nuevo_codigo" method="post" action="<?php echo $modo_modificacion ? '/modificar_codigo/' . $codigo_data['codigo_id'] : '/codigo_insertado'; ?>" enctype="multipart/form-data">
             <div class="form-group">
                 <label for="marca">Marca o Servicio</label>
                 <select class="form-control marca-selector" id="marca" name="marca" required style="width: 100%;">
                     <option value="">selecciona una marca</option>
                 </select>
                 <input type="hidden" id="marca_valor" name="marca_valor" value="<?php echo htmlspecialchars($marca); ?>">
                 <div class="error-message" id="marca-error">La marca es obligatoria</div>
                 <div class="help-text">
                     Busca la marca a la que corresponde tu "Código amigo". Si no la encuentras, puedes añadirla fácilmente.
                 </div>
                 
                 <div id="super-landing-alert" style="display:none; background: #fff9fa; border: 2px solid #E30613; border-radius: 8px; padding: 15px; margin-top: 15px;">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <i class="fas fa-trophy" style="font-size: 24px; color: #E30613;"></i>
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#E30613; font-weight:bold;">¡Oportunidad Premium!</h4>
                            <p style="margin:0; color:#333; font-size:14px;">Esta marca tiene una <strong>Guía Oficial 2026</strong>. Si destacas tu código como "Super" aparecerá en la posición más privilegiada.</p>
                        </div>
                    </div>
                 </div>
                 
                 <!-- Div para nueva marca integrado -->
                 <div class="hide" id="div_nueva_marca">
                     <div class="nueva-marca-container">
                         <div class="nueva-marca-header">
                             <h4>Agregar nueva marca: <span class="nombre_nuevo"></span></h4>
                             <button type="button" class="btn-cancelar" id="cancelar">
                                 <i class="fas fa-times"></i> Cancelar
                             </button>
                         </div>
                         
                         <div class="nueva-marca-content">
                             <input id="url_imagen" name="url_imagen" type="hidden">
                             <input id="categoria_valor" name="categoria_valor" type="hidden">
                             <input id="categoria_clave" name="categoria_clave" type="hidden">

                             <div class="form-group">
                                 <label>1. Selecciona una categoría:</label>
                                <select id="categoria" class="form-control">
                                    <option value="select">Selecciona una categoría para tu marca</option>
                                    <?php
                                    $listacategorias = getCategorias();
                                    foreach ($listacategorias as $cat) { ?>
                                        <option value="<?php echo $cat['nombre_clave']?>"><?php echo $cat['nombre']?></option>
                                    <?php } ?>
                                </select>
                             </div>
                             
                             <div class="form-group">
                                 <label>2. Selecciona una imagen para tu marca:</label>
                                 <div class="imagenes-container">
                                     <ul class="inserta_imagenes"></ul>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="form-group">
                 <label for="num_beneficio">Beneficio económico</label>
                 <div class="row">
                     <div class="col-md-2 col-xs-8">
                         <input type="number" class="form-control" id="num_beneficio" name="num_beneficio" placeholder="0" value="<?php echo htmlspecialchars($beneficio); ?>" required>
                         <div class="error-message" id="num_beneficio-error">El beneficio económico es obligatorio</div>
                     </div>
                     <div class="col-md-2 col-xs-2">
                         <select class="form-control" name="tipo_beneficio">
                             <option value="euros" <?php echo ($descuento == 'euros') ? 'selected' : ''; ?>>euros</option>
                             <option value="porcentaje" <?php echo ($descuento == 'porcentaje') ? 'selected' : ''; ?>>%</option>
                         </select>
                     </div>
                 </div>
             </div>

             <div class="form-group">
                 <label for="codigo">Código promocional o URL</label>
                 <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Introduce tu código o URL" value="<?php echo htmlspecialchars($codigo); ?>" required>
                 <div class="error-message" id="codigo-error">El código promocional es obligatorio</div>
             </div>

            <div class="form-group">
                <label for="descuento">Código Simple (opcional)</label>
                <input type="text" class="form-control" id="descuento" name="descuento" placeholder="Introduce tu código promocional" value="<?php echo htmlspecialchars($descuento); ?>">
                <div class="help-text">
                    Si has publicado una URL en el código anterior, introduce aquí el código simple.
                </div>
            </div>

             <div class="form-group">
                 <label for="descripcion">
                    Descripción
                    <button type="button" id="btn-ai-descripcion" class="btn-ai-magic" title="Completar automáticamente con IA">
                        <i class="fas fa-magic"></i> Completar con IA
                        <span class="vip-badge-mini">VIP</span>
                    </button>
                 </label>
                 <textarea class="form-control" id="descripcion" name="descripcion" rows="5" minlength="50" placeholder="Ej: Te regalo 10€ al abrir tu cuenta. Llevo usando este servicio 2 años y nunca me han cobrado comisiones..." required><?php echo htmlspecialchars($descripcion); ?></textarea>
                 <div class="desc-char-counter" id="desc-counter" style="font-size:12px;color:#888;margin-top:4px;"><span id="desc-char-count">0</span>/50 caracteres mínimos</div>
                 <div class="error-message" id="descripcion-error">La descripción debe tener al menos 50 caracteres</div>
             </div>

            <div class="warning-text">
                ⚠️ ESTÁ TOTALMENTE PROHIBIDO PONER EL CÓDIGO EN LA DESCRIPCIÓN. NO CUMPLIR ESTA NORMA CONLLEVA LA EXPULSIÓN DE LA COMUNIDAD.
            </div>
            
            <?php 
            // Publicidad entremedio
            if (should_show_adsense()) {
                echo generate_adsense_container(get_adsense_entremedio(), 'adsense-middle', 'margin: 30px 0;');
            }
            ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="provincia">Tu provincia (Opcional)</label>
                        <input type="text" class="form-control" id="provincia" name="provincia" placeholder="Introduce tu provincia" value="<?php echo htmlspecialchars($provincia); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="localidad">Tu localidad (Opcional)</label>
                        <input type="text" class="form-control" id="localidad" name="localidad" placeholder="Introduce tu localidad" value="<?php echo htmlspecialchars($localidad); ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="fecha_caducidad">Fecha de caducidad (Opcional)</label>
                <input type="date" class="form-control" id="fecha_caducidad" name="fecha_caducidad" value="<?php echo htmlspecialchars($fecha_caducidad); ?>">
            </div>

            <?php /* 
            // Campo PDF temporalmente deshabilitado - pendiente de arreglar
            <div class="form-group">
                <label for="pdf_retencion">PDF de Retención (Opcional)</label>
                <input type="file" class="form-control" id="pdf_retencion" name="pdf_retencion" accept=".pdf" style="padding: 10px;">
                <div class="help-text">
                    Sube un PDF con las condiciones de retención. Se mostrará como un carrusel de páginas.
                </div>
                <div id="pdf-preview" style="display: none; margin-top: 15px;">
                    <div class="alert alert-info" style="background: #2196F3; color: white; padding: 10px; border-radius: 8px;">
                        <i class="fas fa-file-pdf"></i> PDF seleccionado: <span id="pdf-name"></span>
                    </div>
                </div>
            </div>
            */ ?>

            <?php 
            // Publicidad final
            if (should_show_adsense()) {
                echo generate_adsense_container(get_adsense_entremedio(), 'adsense-bottom', 'margin: 30px 0;');
            }
            ?>
            
            <div class="text-center original-save-button hide" style="margin-top: 30px;">
                <input type="submit" 
                       value="<?php echo $modo_modificacion ? 'Modificar código' : 'Añadir código amigo'; ?>"  
                       class="btn btn-custom" />
            </div>
        </form>
    </div>

    <!-- Botón flotante para publicar código -->
    <button type="button" class="floating-save-button" id="floating-save-button" onclick="$('#nuevo_codigo').submit();" disabled>
        <i class="fas fa-save"></i>
        <?php echo $modo_modificacion ? 'Modificar código' : 'Añadir código amigo'; ?>
    </button>

    <!-- Modal de Confirmación de Reemplazo -->
    <div id="modal_confirmar_reemplazo" class="modal fade" role="dialog" style="z-index: 99999;">
      <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content" style="background: #333; color: white; border: 1px solid #555;">
          <div class="modal-header" style="border-bottom: 1px solid #555;">
            <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
            <h4 class="modal-title" style="color: #E30613; font-weight: bold;">⚠️ Código ya existente</h4>
          </div>
          <div class="modal-body" style="text-align: center; font-size: 16px;">
            <p>Ya tienes un código publicado para la marca <strong id="modal_marca_nombre" style="color: #E30613;"></strong>.</p>
            <p>¿Quieres borrar tu código anterior y publicar este nuevo en su lugar?</p>
            <div style="background: #444; padding: 10px; border-radius: 5px; margin-top: 15px; font-size: 14px; color: #ccc;">
                <strong>Nota:</strong> Esta acción eliminará permanentemente tu código anterior y sus estadísticas.
            </div>
          </div>
          <div class="modal-footer" style="border-top: 1px solid #555; text-align: center;">
            <button type="button" class="btn btn-default" data-dismiss="modal" style="background: transparent; color: white; border: 1px solid #999; margin-right: 10px;">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btn_confirmar_reemplazo" style="background: #E30613; border-color: #E30613;">Sí, borrar anterior y publicar este</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal VIP Upgrade -->
    <div id="modal-vip-upgrade" class="modal fade" role="dialog" style="z-index: 99999;">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
            <h4 class="modal-title"><i class="fas fa-crown"></i> VENTAJAS VIP</h4>
          </div>
          <div class="modal-body" style="padding: 30px; text-align: center;">
            <p style="font-size: 18px; margin-bottom: 25px;">La función de <strong>Completar con IA</strong> es exclusiva para usuarios VIP. <br><strong>¡Hazte VIP y disfruta de todas estas ventajas!</strong></p>
            
            <ul class="vip-feature-list">
                <li class="vip-feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>IA Ilimitada:</strong> Completa todas las descripciones de tus códigos con inteligencia artificial profesional.</span>
                </li>
                <li class="vip-feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>Badge VIP Verificado:</strong> Gana confianza y obtén hasta un 40% más de clics en tus códigos.</span>
                </li>
                <li class="vip-feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>Chat Ilimitado:</strong> Contacta directamente con los usuarios que ven tus códigos.</span>
                </li>
                <li class="vip-feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>10€ de Saldo Mensual:</strong> Recibe 10€ cada mes para destacar tus códigos totalmente gratis.</span>
                </li>
            </ul>

            <a href="/public/mis_viewers.php" class="btn-upgrade-now">
                QUIERO SER VIP POR 9,99€
            </a>
            
            <p style="margin-top: 20px; color: #888; font-size: 13px;">Cancela en cualquier momento con un solo clic.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS (necesario para el modal) -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

    <?php
    // No mostrar footer en página nuevo_codigo para evitar puntos de fuga
    // El botón flotante es suficiente para la navegación
    ?>
    <?php // get_footer(); ?>

    <script>
    // Función para inicializar el selector de marcas
    function initBrandSelector() {
        console.log("Inicializando selector de marcas...");

        // Verificar que el elemento existe
        if (!document.getElementById('marca')) {
            console.error("Elemento #marca no encontrado");
            return;
        }

        // Verificar que jQuery esté disponible
        if (typeof $ === 'undefined') {
            console.error("jQuery no está disponible");
            return;
        }

        // Verificar que Select2 esté disponible
        if (typeof $.fn.select2 === 'undefined') {
            console.error("Select2 no está disponible");
            return;
        }

    console.log("Configurando Select2 para el selector de marcas...");

    // Función para validar si todos los campos obligatorios están completos
    function validateAllRequiredFields() {
        var marcaValue = $("#marca_valor").val().trim();
        var beneficio = $("#num_beneficio").val().trim();
        var codigo = $("#codigo").val().trim();
        var descripcion = $("#descripcion").val().trim();

        var allValid = marcaValue !== '' && beneficio !== '' && codigo !== '' && descripcion.length >= 50;

        // Habilitar/deshabilitar botón flotante
        if (allValid) {
            $("#floating-save-button").prop("disabled", false);
        } else {
            $("#floating-save-button").prop("disabled", true);
        }

        return allValid;
    }

    // Función para validar un campo individual
    function validateField(fieldId, errorId, errorMessage) {
        var field = $("#" + fieldId);
        var errorDiv = $("#" + errorId);
        var value = field.val().trim();

        if (value === '') {
            field.addClass('error');
            errorDiv.show();
            return false;
        } else {
            field.removeClass('error');
            errorDiv.hide();
            return true;
        }
    }

        // Función para limpiar errores
        function clearErrors() {
            $('.form-control').removeClass('error');
            $('.error-message').hide();
        }

        // Configurar Select2 para el selector de marcas
        try {
            $("#marca").select2({
                placeholder: "selecciona una marca",
                allowClear: true,
                width: '100%',
                minimumInputLength: 0,
                minimumResultsForSearch: 0,
                ajax: {
                    url: "/ajax/buscar_marcas.php",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });
            console.log("Select2 configurado correctamente");
        } catch (error) {
            console.error("Error configurando Select2:", error);
        }

        // Evento para poner foco en el input cuando se abre el dropdown
        $("#marca").on('select2:open', function(e) {
            // Usar setTimeout para asegurar que el DOM esté completamente renderizado
            setTimeout(function() {
                // Buscar el campo de búsqueda dentro del dropdown
                var searchField = $('.select2-container--open .select2-search__field');
                
                if (searchField.length > 0) {
                    // Enfocar el campo de búsqueda
                    searchField.focus();
                    // Seleccionar todo el texto si hay alguno
                    searchField.select();
                } else {
                    // Si aún no existe, intentar varias veces con intervalos cortos
                    var attempts = 0;
                    var maxAttempts = 10;
                    var interval = setInterval(function() {
                        attempts++;
                        var field = $('.select2-container--open .select2-search__field');
                        if (field.length > 0) {
                            field.focus();
                            field.select();
                            clearInterval(interval);
                        } else if (attempts >= maxAttempts) {
                            clearInterval(interval);
                        }
                    }, 50);
                }
            }, 10);
        });

    // Validación en tiempo real
    $('#marca').on('blur', function() {
        var marcaValue = $("#marca_valor").val();
        if (marcaValue === '') {
            $("#marca").addClass('error');
            $("#marca-error").show();
        } else {
            $("#marca").removeClass('error');
            $("#marca-error").hide();
        }
        validateAllRequiredFields(); // Verificar si habilitar botón
    });

    $('#num_beneficio').on('blur input', function() {
        validateField('num_beneficio', 'num_beneficio-error', 'El beneficio económico es obligatorio');
        validateAllRequiredFields(); // Verificar si habilitar botón
    });

    $('#codigo').on('blur input', function() {
        validateField('codigo', 'codigo-error', 'El código promocional es obligatorio');
        validateAllRequiredFields(); // Verificar si habilitar botón
    });

    $('#descripcion').on('blur input', function() {
        var len = $(this).val().trim().length;
        $('#desc-char-count').text(len);
        var counter = $('#desc-counter');
        if (len >= 50) {
            counter.css('color', '#27ae60');
        } else {
            counter.css('color', len > 30 ? '#e67e22' : '#888');
        }
        var ok = len >= 50;
        $('#descripcion-error').toggle(!ok && $(this).is(':not(:focus)'));
        validateAllRequiredFields();
    });

        // Manejar selección de marca
        $("#marca").on('select2:select', function (e) {
            var data = e.params.data;

            if (data.is_new) {
                // Si es nueva marca, mostrar el formulario de nueva marca
                $('#creada').val(1);
                $("#marca_valor").val(data.nombre); // Establecer el valor para nuevas marcas
                $("#marca").val(data.nombre); // También actualizar el valor del select
                console.log("Nueva marca seleccionada, estableciendo marca_valor:", data.nombre);
                $("#marca").hide();
                $("#div_nueva_marca").show();
                $("#div_nueva_marca").removeClass("hide");
                $("#categoria").focus();
                $(".nombre_nuevo").html(data.nombre);
                $(".inserta_imagenes").empty();

                var busqueda = data.nombre + " logo";

                $.ajax({
                    type: 'GET',
                    data: {
                        q: busqueda + ' logo',
                        num: 10,
                        searchType: "image",
                        key: "AIzaSyBO8kzIr4NtCVBxLxQSxGkq8Whw4kHgAqI",
                        cx: "011289846254342421780:ygm3rzpmf2a"
                    },
                    url: 'https://www.googleapis.com/customsearch/v1',
                    success: function (data) {
                        $.each(data["items"], function(index, item) {
                            var img = $('<li><div class="item_imagen"><img data="' + item["link"] + '" src="' + item["link"] + '"/></div></li>');
                            $('.inserta_imagenes').append(img);
                        });
                    }
                });
        } else {
            // Marca existente seleccionada
            console.log("Marca seleccionada:", data);
            // Usar nombre_clave si está disponible para asegurar coincidencia con BD
            var valorMarca = data.nombre_clave ? data.nombre_clave : data.nombre;
            $("#marca_valor").val(valorMarca);
            $("#marca").removeClass('error');
            $("#marca-error").hide();
            
            // CHECK SUPER LANDINGS
            if (window.superLandingsMap && window.superLandingsMap[valorMarca]) {
                 $('#super-landing-alert').fadeIn();
            } else {
                 $('#super-landing-alert').hide();
            }
            
            validateAllRequiredFields(); // Verificar si habilitar botón
        }
        });

        // Manejar cambio de valor en el select
        $("#marca").on('change', function() {
            var selectedValue = $(this).val();
            console.log("Evento change en marca, valor seleccionado:", selectedValue);
            if (selectedValue && !selectedValue.startsWith('nueva_marca_')) {
                $("#marca_valor").val(selectedValue);
                console.log("Estableciendo marca_valor para marca existente:", selectedValue);
            } else if (selectedValue && selectedValue.startsWith('nueva_marca_')) {
                console.log("Valor de nueva marca detectado, no cambiando marca_valor");
            }
            validateAllRequiredFields(); // Verificar si habilitar botón
        });

        // Validación al enviar el formulario
        $('#nuevo_codigo').on('submit', function(e) {
            clearErrors();

            // Usar la misma validación que para habilitar el botón
            if (!validateAllRequiredFields()) {
                e.preventDefault();
                showValidationErrors();
                return false;
            }

            // Si ya estamos re-enviando confirmado, permitir submit
            if ($(this).data('submitting_confirmed') === true) {
                return true;
            }
            
            // Si estamos en modo modificación, no hace falta chequear duplicados (ya es el mismo)
            var modoModificacion = <?php echo $modo_modificacion ? 'true' : 'false'; ?>;
            if (modoModificacion) {
                return true;
            }

            // Si es nueva marca (creada por usuario), no tendrá duplicados previos
            if ($('#div_nueva_marca').is(':visible')) {
               return true;
            }

            // Interceptar submit para chequear duplicados AJAX
            e.preventDefault();
            var form = $(this);
            var marcaNombre = $('#marca_valor').val();
            
            // Mostrar indicador de carga o deshabilitar botón si se desea...
            
            $.ajax({
                url: '/ajax/check_existing_code.php',
                type: 'GET',
                data: { marca: marcaNombre },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.exists) {
                        // Existe código previo: Mostrar Modal
                        $('#modal_marca_nombre').text(marcaNombre);
                        $('#modal_confirmar_reemplazo').modal('show');
                        
                        // Handler para el botón confirmar del modal
                        $('#btn_confirmar_reemplazo').off('click').on('click', function() {
                            // Añadir flag de reemplazo
                            $('<input>').attr({
                                type: 'hidden',
                                name: 'replace_existing',
                                value: '1'
                            }).appendTo(form);
                            
                            // Marcar como confirmado para evitar loop
                            form.data('submitting_confirmed', true);
                            
                            // Cerrar modal y enviar
                            $('#modal_confirmar_reemplazo').modal('hide');
                            form.submit();
                        });
                        
                    } else {
                        // No existe, enviar normal
                        form.data('submitting_confirmed', true);
                        form.submit();
                    }
                },
                error: function() {
                    // Si falla AJAX, permitir envío y que el backend maneje el error como antes
                    form.data('submitting_confirmed', true);
                    form.submit();
                }
            });
            
            return false;
        });

        function showValidationErrors() {
                 // Mostrar errores visuales para campos vacíos
                var marcaValue = $("#marca_valor").val().trim();
                var beneficio = $("#num_beneficio").val().trim();
                var codigo = $("#codigo").val().trim();
                var descripcion = $("#descripcion").val().trim();

                if (marcaValue === '') {
                    $("#marca").addClass('error');
                    $("#marca-error").show();
                }
                if (beneficio === '') {
                    $("#num_beneficio").addClass('error');
                    $("#num_beneficio-error").show();
                }
                if (codigo === '') {
                    $("#codigo").addClass('error');
                    $("#codigo-error").show();
                }
                if (descripcion === '') {
                    $("#descripcion").addClass('error');
                    $("#descripcion-error").show();
                }

                // Scroll al primer campo con error
                var firstError = $('.form-control.error').first();
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 500);
                }
        }

        // Manejar cancelar nueva marca
        $(document).on("click", "#cancelar", function() {
            $("#marca").show();
            $("#div_nueva_marca").addClass("hide").hide();
            $('#creada').val(0);
            $("#marca").val('').trigger('change');
            $("#marca_valor").val('');
            $("#url_imagen").val('');
            $("#categoria_valor").val('');
            $("#categoria_clave").val('');
            $("#categoria").val('select');
            validateAllRequiredFields(); // Verificar si deshabilitar botón
        });

        // Manejar cambio en el select de categoría
        $("#categoria").on("change", function() {
            var selectedValue = $(this).val();
            var selectedText = $(this).find("option:selected").text();

            if (selectedValue && selectedValue !== "select") {
                $("#categoria_valor").val(selectedText);
                $("#categoria_clave").val(selectedValue);
            }
        });

        // Lógica para el botón de IA
        $('#btn-ai-descripcion').on('click', function() {
            var btn = $(this);
            
            // Si NO es VIP, mostrar modal de ventajas VIP directamente
            if (!window.isVipUser) {
                $.post('/ajax/track_evento_vip.php', { tipo: 'modal_ia_bloqueada' });
                $('#modal-vip-upgrade').modal('show');
                return;
            }
            
            var marca = $('#marca_valor').val();
            var beneficio = $('#num_beneficio').val();
            var tipo_beneficio = $('select[name="tipo_beneficio"]').val();
            
            if (!marca) {
                alert('Por favor, selecciona una marca primero.');
                $('#marca').select2('open');
                return;
            }

            if (btn.hasClass('loading')) return;

            btn.addClass('loading').html('<i class="fas fa-spinner"></i> Generando...');

            $.ajax({
                url: '/ajax/generate_description_ai.php',
                type: 'POST',
                data: {
                    marca: marca,
                    beneficio: beneficio,
                    tipo_beneficio: tipo_beneficio
                },
                dataType: 'json',
                success: function(response) {
                    btn.removeClass('loading').html('<i class="fas fa-magic"></i> Completar con IA <span class="vip-badge-mini">VIP</span>');
                    
                    if (response.success) {
                        $('#descripcion').val(response.description);
                        // Trigger input event to update char count or other listeners
                        $('#descripcion').trigger('input');
                        
                        // Scroll to description
                        $('html, body').animate({
                            scrollTop: $('#descripcion').offset().top - 150
                        }, 500);
                    } else if (response.show_vip_modal) {
                        $('#modal-vip-upgrade').modal('show');
                    } else {
                        alert(response.error || 'Ocurrió un error al generar la descripción.');
                    }
                },
                error: function() {
                    btn.removeClass('loading').html('<i class="fas fa-magic"></i> Completar con IA <span class="vip-badge-mini">VIP</span>');
                    alert('Error de conexión. Inténtalo de nuevo.');
                }
            });
        });

        // Permitir al usuario seleccionar una imagen
        $(document).on("click", ".item_imagen", function(){
            $(".item_imagen").each(function () {
                $(this).removeClass("img-selected");
            });
            $(this).addClass("img-selected");
            $(".selectImagen").prop("checked", false);
            var name = $(this).children("img").attr('src');
            $("#url_imagen").val(name);
            console.log("Imagen seleccionada para nueva marca:", name);
            console.log("Valor actual de marca_valor:", $("#marca_valor").val());
        });

        // Preseleccionar marca si estamos en modo modificación O si viene de la URL
        <?php if ((isset($_GET['marca']) || $modo_modificacion) && !empty($marca)): ?>
        // Obtener información de la marca desde el backend
        var marcaNombre = '<?php echo htmlspecialchars($marca); ?>';
        var marcaClave = '<?php echo htmlspecialchars(isset($codigo_data['marca_clave']) ? $codigo_data['marca_clave'] : ''); ?>';
        var marcaEsNueva = <?php echo $marca_es_nueva ? 'true' : 'false'; ?>;

        // Crear la opción para la marca actual
        var marcaActual = {
            id: marcaNombre,
            text: marcaNombre,
            nombre: marcaNombre,
            imagen: '/img/no_image.png',
            is_new: marcaEsNueva
        };

        // Agregar la opción al Select2
        var newOption = new Option(marcaActual.text, marcaActual.id, true, true);
        $("#marca").append(newOption);

        // Establecer los datos en Select2
        $("#marca").select2('trigger', 'select', { data: marcaActual });

        // Establecer el valor en el campo oculto
        $("#marca_valor").val(marcaActual.nombre);

        console.log("Marca preseleccionada:", marcaActual);
        
        // Si es nueva marca, abrir el div de nueva marca
        if (marcaEsNueva) {
            $('#creada').val(1);
            $("#marca").hide();
            $("#div_nueva_marca").show();
            $("#div_nueva_marca").removeClass("hide");
            $("#categoria").focus();
            $(".nombre_nuevo").html(marcaNombre);
            $(".inserta_imagenes").empty();

            var busqueda = marcaNombre + " logo";

            $.ajax({
                type: 'GET',
                data: {
                    q: busqueda + ' logo',
                    num: 10,
                    searchType: "image",
                    key: "AIzaSyBO8kzIr4NtCVBxLxQSxGkq8Whw4kHgAqI",
                    cx: "011289846254342421780:ygm3rzpmf2a"
                },
                url: 'https://www.googleapis.com/customsearch/v1',
                success: function (data) {
                    $.each(data["items"], function(index, item) {
                        var img = $('<li><div class="item_imagen"><img data="' + item["link"] + '" src="' + item["link"] + '"/></div></li>');
                        $('.inserta_imagenes').append(img);
                    });
                }
            });
        }
        <?php endif; ?>
    }

    // Inicializar cuando la página esté lista
    $(document).ready(function() {
        console.log("jQuery disponible:", typeof $);
        console.log("Página lista, inicializando componentes...");

        // Pequeño delay para asegurar que todo esté cargado
        setTimeout(function() {
            console.log("Inicializando selector de marcas...");
            initBrandSelector();

            // Si estamos en modo modificación, verificar si el botón debe estar habilitado
            <?php if ($modo_modificacion): ?>
            setTimeout(function() {
                validateAllRequiredFields();
            }, 500); // Delay adicional para asegurar que los campos estén cargados
            <?php endif; ?>

        <?php /* 
        // Manejar vista previa del PDF - temporalmente deshabilitado
        $('#pdf_retencion').on('change', function() {
            var file = this.files[0];
            if (file) {
                if (file.type === 'application/pdf') {
                    $('#pdf-name').text(file.name);
                    $('#pdf-preview').show();
                } else {
                    alert('Por favor, selecciona un archivo PDF válido.');
                    $(this).val('');
                    $('#pdf-preview').hide();
                }
            } else {
                $('#pdf-preview').hide();
            }
        });
        */ ?>
        }, 200);
    });
    </script>
</body>
</html>
