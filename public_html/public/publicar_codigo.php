<?php
    // Deshabilitar AdSense en esta página
    $anula_adsense = true;
    
    global $array_descuentos;
    
    // Incluir funciones de AdSense
    include_once __DIR__ . '/../myphp/funciones_adsense.php';
    
    $modo_modificacion = isset($codigo_data);

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
            $marca = $form_data['marca'] ?? '';
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
            $marca = isset($_GET['marca']) ? strtoupper($_GET['marca']) : '';
            $beneficio = $descuento = $codigo = $descripcion = $provincia = $localidad = $fecha_caducidad = '';
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
            background: #ff6b35;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        .back-button:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }
        .back-button i {
            margin-right: 8px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 80px 20px 20px;
        }
        .form-control {
            background: white;
            color: #333;
            border: 2px solid #555;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 16px;
        }
         .form-control:focus {
             border-color: #ff6b35;
             box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
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
        .btn-custom {
            background: #ff6b35;
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
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
            color: #ff6b35;
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
            color: #ff6b35;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            font-size: 16px;
        }
        .easy-autocomplete {
            width: 100% !important;
            position: relative !important;
        }
        
        /* Estilos para Select2 con tema oscuro */
        .select2-container--bootstrap .select2-selection--single {
            background-color: #3a3a3a !important;
            border: 1px solid #555 !important;
            color: white !important;
            height: 40px !important;
        }
        
        .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
            color: white !important;
            line-height: 38px !important;
        }
        
        .select2-container--bootstrap .select2-selection--single .select2-selection__placeholder {
            color: #ccc !important;
        }
        
        .select2-container--bootstrap .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
        }
        
        .select2-container--bootstrap .select2-dropdown {
            background-color: #3a3a3a !important;
            border: 1px solid #555 !important;
        }
        
        .select2-container--bootstrap .select2-results__option {
            background-color: #3a3a3a !important;
            color: white !important;
        }
        
        .select2-container--bootstrap .select2-results__option--highlighted[aria-selected] {
            background-color: #ff6b35 !important;
            color: white !important;
        }
        
        .select2-container--bootstrap .select2-results__option[aria-selected=true] {
            background-color: #555 !important;
            color: white !important;
        }
        
        .select2-container--bootstrap .select2-search--dropdown .select2-search__field {
            background-color: #2a2a2a !important;
            border: 1px solid #555 !important;
            color: white !important;
        }
        
        .select2-container--bootstrap .select2-results__message {
            color: #ccc !important;
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
        .easy-autocomplete input {
            border: 0px solid #e9ecef !important;
            border-radius: 10px !important;
            padding: 15px 20px !important;
            font-size: 16px !important;
            background: white !important;
            color: #333 !important;
        }
        .easy-autocomplete input:focus {
            border-color: #ff6b35 !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25) !important;
        }
        .easy-autocomplete ul {
            background: white !important;
            border: 1px solid #ddd !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }
        .easy-autocomplete li {
            color: #333 !important;
            padding: 10px 15px !important;
            border-bottom: 1px solid #eee !important;
        }
        .easy-autocomplete li:hover {
            background: #f8f9fa !important;
        }
        .easy-autocomplete .eac-category {
            background: #ff6b35 !important;
            color: white !important;
            font-weight: bold !important;
        }
        
        /* Botón flotante para modificar código */
        .floating-save-button {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: #ff6b35;
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
            justify-content: center;
        }
        
        .floating-save-button:hover {
            background: #e55a2b;
            transform: translateX(-50%) translateY(-3px);
            box-shadow: 0 12px 35px rgba(255, 107, 53, 0.5);
        }
        
        .floating-save-button:active {
            transform: translateX(-50%) translateY(-1px);
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
        }
        
        /* Estilos para la nueva marca integrada */
        .nueva-marca-container {
            background: #f8f9fa;
            border: 2px solid #ff6b35;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.1);
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
            color: #ff6b35;
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
            border: 2px solid #ff6b35;
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
            border-color: #e55a2b;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
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
            border-color: #ff6b35;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 107, 53, 0.2);
        }
        
        .item_imagen.img-selected {
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.3);
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

        <form id="nuevo_codigo" method="post" action="<?php echo $modo_modificacion ? '/modificar_codigo/' . $codigo_data['codigo_id'] : '/codigo_insertado'; ?>">
             <div class="form-group">
                 <label for="marca">Marca o Servicio</label>
                 <select class="form-control" id="marca" name="marca" required>
                     <option value="">Busca o selecciona una marca</option>
                 </select>
                 <input type="hidden" id="marca_valor" name="marca_valor" value="<?php echo htmlspecialchars($marca); ?>">
                 <div class="error-message" id="marca-error">La marca es obligatoria</div>
                 <div class="help-text">
                     Busca la marca a la que corresponde tu "Código amigo". Si no la encuentras, puedes añadirla fácilmente.
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
                             
                             <div class="form-group">
                                 <label>1. Selecciona una categoría:</label>
                                <select id="categoria" name="categoria" class="form-control">
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
                     <div class="col-md-6">
                         <input type="number" class="form-control" id="num_beneficio" name="num_beneficio" placeholder="0" value="<?php echo htmlspecialchars($beneficio); ?>" required>
                         <div class="error-message" id="num_beneficio-error">El beneficio económico es obligatorio</div>
                     </div>
                     <div class="col-md-6">
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
                 <label for="descripcion">Descripción</label>
                 <textarea class="form-control" id="descripcion" name="descripcion" rows="5" placeholder="Describe tu código amigo" required><?php echo htmlspecialchars($descripcion); ?></textarea>
                 <div class="error-message" id="descripcion-error">La descripción es obligatoria</div>
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

            <?php 
            // Publicidad final
            if (should_show_adsense()) {
                echo generate_adsense_container(get_adsense_entremedio(), 'adsense-bottom', 'margin: 30px 0;');
            }
            ?>
            
            <div class="text-center original-save-button" style="margin-top: 30px;">
                <input type="submit" 
                       value="<?php echo $modo_modificacion ? 'Modificar código' : 'Añadir código amigo'; ?>"  
                       class="btn btn-custom" />
            </div>
        </form>
    </div>

    <!-- Botón flotante para modificar código -->
    <?php if ($modo_modificacion) { ?>
    <button type="button" class="floating-save-button" onclick="document.getElementById('nuevo_codigo').submit();">
        <i class="fas fa-save"></i>
        Modificar código
    </button>
    <?php } ?>

    <link rel="stylesheet" href="/css/easy-autocomplete.min.css">
    
     <script>
     function initBrandSelector() {
         // Verificar que el elemento existe
         if (!$("#marca").length) {
             console.error("Elemento #marca no encontrado");
             return;
         }
         
         // Función para validar un campo
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
         });
         
         $('#num_beneficio').on('blur', function() {
             validateField('num_beneficio', 'num_beneficio-error', 'El beneficio económico es obligatorio');
         });
         
         $('#codigo').on('blur', function() {
             validateField('codigo', 'codigo-error', 'El código promocional es obligatorio');
         });
         
         $('#descripcion').on('blur', function() {
             validateField('descripcion', 'descripcion-error', 'La descripción es obligatoria');
         });
         
         // Validación al enviar el formulario
         $('#nuevo_codigo').on('submit', function(e) {
             clearErrors();
             
             var isValid = true;
             
             // Validar marca
             var marcaValue = $("#marca_valor").val();
             if (marcaValue === '') {
                 $("#marca").addClass('error');
                 $("#marca-error").show();
                 isValid = false;
             }
             
             // Validar beneficio económico
             if (!validateField('num_beneficio', 'num_beneficio-error', 'El beneficio económico es obligatorio')) {
                 isValid = false;
             }
             
             // Validar código
             if (!validateField('codigo', 'codigo-error', 'El código promocional es obligatorio')) {
                 isValid = false;
             }
             
             // Validar descripción
             if (!validateField('descripcion', 'descripcion-error', 'La descripción es obligatoria')) {
                 isValid = false;
             }
             
             if (!isValid) {
                 e.preventDefault();
                 // Scroll al primer campo con error
                 var firstError = $('.form-control.error').first();
                 if (firstError.length) {
                     $('html, body').animate({
                         scrollTop: firstError.offset().top - 100
                     }, 500);
                 }
                 return false;
             }
         });
         
        // Configurar Select2 para el selector de marcas
        $("#marca").select2({
            placeholder: "Busca o selecciona una marca",
            allowClear: true,
            width: '100%',
            theme: 'bootstrap',
            minimumInputLength: 0, // Permitir búsqueda sin mínimo de caracteres
            language: {
                noResults: function() {
                    return "No se encontraron marcas";
                },
                searching: function() {
                    return "Buscando...";
                }
            },
            ajax: {
                url: "/ajax/buscar_marcas.php",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '', // Enviar cadena vacía si no hay término
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    
                    // Filtrar resultados válidos
                    var validResults = data.filter(function(item) {
                        return item.nombre && item.nombre !== 'false' && item.nombre.trim() !== '';
                    });
                    
                    // Agregar opción de "No encontrado" si no hay resultados y hay término de búsqueda
                    if (validResults.length === 0 && params.term && params.term.length >= 2) {
                        var nuevaOpcion = {
                            id: 'nueva_marca_' + params.term,
                            text: params.term + ' (no encontrado) + Agregar nueva marca',
                            nombre: params.term,
                            is_new: true,
                            imagen: '/img/no_image.png'
                        };
                        validResults.push(nuevaOpcion);
                    }
                    
                    return {
                        results: validResults.map(function(item) {
                            return {
                                id: item.nombre,
                                text: item.text || item.nombre,
                                nombre: item.nombre,
                                imagen: item.imagen || '/img/no_image.png',
                                is_new: item.is_new || false
                            };
                        }),
                        pagination: {
                            more: false
                        }
                    };
                },
                cache: true
            },
             templateResult: function(marca) {
                 if (marca.is_new) {
                     var nombreMarca = marca.nombre || marca.text || 'Nueva marca';
                     var texto = 'Añadir nueva marca: ' + nombreMarca;
                     return $('<div style="color: #ff6b35; font-weight: 600; text-align: center; padding: 10px;">' + texto + '</div>');
                 }
                 
                 if (!marca.nombre) {
                     return marca.text || marca.id;
                 }
                 
                 var $result = $(
                     '<div style="display: flex; align-items: center;">' +
                         '<img src="' + (marca.imagen || '/img/no_image.png') + '" style="width: 30px; height: 30px; margin-right: 10px; border-radius: 5px;">' +
                         '<span>' + (marca.nombre || marca.text) + '</span>' +
                     '</div>'
                 );
                 
                 return $result;
             },
             templateSelection: function(marca) {
                 if (marca.is_new) {
                     var nombreMarca = marca.nombre || marca.text || 'Nueva marca';
                     return 'Añadir nueva marca: ' + nombreMarca;
                 }
                 
                 if (!marca.nombre) {
                     return marca.text || marca.id;
                 }
                 
                 return marca.nombre;
             }
        });
        
        // Preseleccionar marca si estamos en modo modificación
        <?php if ($modo_modificacion && !empty($marca)): ?>
        $(document).ready(function() {
            // Obtener información de la marca desde el backend
            var marcaNombre = '<?php echo htmlspecialchars($marca); ?>';
            var marcaClave = '<?php echo htmlspecialchars($codigo_data['marca_clave'] ?? ''); ?>';
            
            // Crear la opción para la marca actual
            var marcaActual = {
                id: marcaNombre,
                text: marcaNombre,
                nombre: marcaNombre,
                imagen: '/img/no_image.png' // Se cargará la imagen correcta desde el backend
            };
            
            // Agregar la opción al Select2
            var newOption = new Option(marcaActual.text, marcaActual.id, true, true);
            $("#marca").append(newOption).trigger('change');
            
            // Establecer el valor en el campo oculto
            $("#marca_valor").val(marcaActual.nombre);
            
            console.log("Marca preseleccionada:", marcaActual);
        });
        <?php endif; ?>
        
        // Manejar selección de marca
         $("#marca").on('select2:select', function (e) {
             var data = e.params.data;
             
             if (data.is_new) {
                 // Si es nueva marca, mostrar el formulario de nueva marca
                 $('#creada').val(1);
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
                 $("#marca_valor").val(data.nombre);
                 $("#marca").removeClass('error');
                 $("#marca-error").hide();
             }
         });
         
         // Manejar cambio de valor en el select
         $("#marca").on('change', function() {
             var selectedValue = $(this).val();
             if (selectedValue && !selectedValue.startsWith('nueva_marca_')) {
                 $("#marca_valor").val(selectedValue);
             }
         });
         
         
         // Manejar cancelar nueva marca
         $(document).on("click", "#cancelar", function() {
             $("#marca").show();
             $("#div_nueva_marca").addClass("hide").hide();
             $('#creada').val(0);
             $("#marca").val('').trigger('change');
             $("#marca_valor").val('');
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
         });
     }
     
     </script>
     
<?php get_footer(); ?>

<script>
// Función para cargar Select2 dinámicamente
function loadSelect2() {
    return new Promise(function(resolve, reject) {
        if (typeof $.fn.select2 !== 'undefined') {
            resolve();
            return;
        }
        
        // Cargar Select2 CSS si no está cargado
        if (!document.querySelector('link[href*="select2"]')) {
            var css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
            document.head.appendChild(css);
        }
        
        // Cargar Select2 JS
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        script.onload = function() {
            resolve();
        };
        script.onerror = function() {
            console.error("Error cargando Select2");
            reject();
        };
        document.head.appendChild(script);
    });
}

// Inicializar Select2 después de que se cargue el footer completo
$(document).ready(function() {
    console.log("jQuery disponible:", typeof $);
    
    // Cargar Select2 y luego inicializar
    loadSelect2().then(function() {
        console.log("Inicializando selector de marcas...");
        initBrandSelector();
    }).catch(function() {
        console.error("No se pudo cargar Select2");
    });
});
</script>
</body>
</html>
