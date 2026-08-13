<?php
// Incluir archivos necesarios
include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/includes.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_pdf.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: /login");
    exit;
}

// Verificar que se recibieron datos POST
if (empty($_POST)) {
    $_SESSION['msg_error'] = "No se recibieron datos para publicar";
    header("Location: /nuevo_codigo");
    exit;
}

// Obtener datos del formulario
$marca = $_POST['marca_valor'] ?? $_POST['marca'] ?? '';
$num_beneficio = $_POST['num_beneficio'] ?? '';
$tipo_beneficio = $_POST['tipo_beneficio'] ?? 'euros';
$codigo = $_POST['codigo'] ?? '';
$descuento = $_POST['descuento'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$provincia = $_POST['provincia'] ?? '';
$localidad = $_POST['localidad'] ?? '';
$fecha_caducidad = $_POST['fecha_caducidad'] ?? '';

// Validar campos obligatorios
$errores = [];

if (empty($marca)) {
    $errores[] = "La marca es obligatoria";
}

if (empty($num_beneficio) || !is_numeric($num_beneficio)) {
    $errores[] = "El beneficio económico es obligatorio y debe ser un número";
}

if (empty($codigo)) {
    $errores[] = "El código promocional es obligatorio";
}

if (empty($descripcion)) {
    $errores[] = "La descripción es obligatoria";
}

// Si hay errores, redirigir de vuelta al formulario con datos preservados
if (!empty($errores)) {
    $_SESSION['msg_error'] = implode('. ', $errores);
    
    // Preservar datos del formulario en la sesión
    $_SESSION['form_data'] = [
        'marca' => $_POST['marca'] ?? '',
        'marca_valor' => $marca,
        'num_beneficio' => $num_beneficio,
        'tipo_beneficio' => $tipo_beneficio,
        'codigo' => $codigo,
        'descuento' => $descuento,
        'descripcion' => $descripcion,
        'provincia' => $provincia,
        'localidad' => $localidad,
        'fecha_caducidad' => $fecha_caducidad
    ];
    
    header("Location: /nuevo_codigo");
    exit;
}

try {
    // Preparar datos para la función unificada
    $datos_codigo = [
        'marca' => $marca,
        'num_beneficio' => (int)$num_beneficio,
        'tipo_beneficio' => $tipo_beneficio,
        'codigo' => $codigo,
        'codigo_descuento' => $descuento,
        'descripcion' => $descripcion,
        'provincia' => $provincia,
        'localidad' => $localidad,
        'fecha_caducidad' => $fecha_caducidad,
        'visibilidad' => 'baja', // Los códigos nuevos empiezan con baja visibilidad
        'url_imagen' => $_POST['url_imagen'] ?? null,
        'categoria_valor' => $_POST['categoria_valor'] ?? null,
        'categoria_clave' => $_POST['categoria_clave'] ?? null
    ];
    
    // Usar función unificada para crear el código
    
    // Si se solicitó reemplazar código existente
    if (isset($_POST['replace_existing']) && $_POST['replace_existing'] == '1') {
        try {
            $marca_normalizada = normalizeMarcaName($marca);
            $collection = getCollectionCodigos();
            // Borrar código anterior de esta marca para este usuario
            $collection->deleteOne([
                'marca' => $marca_normalizada,
                'id_usuario' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])
            ]);
        } catch (Exception $e) {
            log_error("Error al borrar código para reemplazo: " . $e->getMessage());
            // Continuamos intentando crear el nuevo aunque falle el borrado (MongoDB manejará unicidad si hay índice, sino se creará duplicado que luego se detectará)
        }
    }

    $resultado = createNewCode($datos_codigo, $_SESSION["user_id"]);
    if ($resultado) {
        // Solo limpiamos el error en caso de éxito. Si createNewCode falló y
        // dejó un msg_error específico (beneficio > oficial, descripción corta,
        // etc.), NO lo borramos para que el usuario vea el motivo real.
        unset($_SESSION['msg_error']);
        /* 
        // Procesamiento de PDF temporalmente deshabilitado - pendiente de arreglar
        // Procesar PDF si se subió uno
        if (isset($_FILES['pdf_retencion']) && $_FILES['pdf_retencion']['error'] === UPLOAD_ERR_OK) {
            $pdf_file = $_FILES['pdf_retencion'];
            $codigo_id = (string)$resultado['_id'];
            
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
        // Limpiar datos del formulario de la sesión
        unset($_SESSION['form_data']);
        
        // Obtener el ID del código insertado
        $codigo_id = (string)$resultado['_id'];
        
        // Guardar información del código publicado en la sesión
        $_SESSION['codigo_publicado'] = [
            'id' => $codigo_id,
            'marca' => $marca,
            'codigo' => $codigo,
            'beneficio' => $num_beneficio . ' ' . $tipo_beneficio,
            'descripcion' => $descripcion
        ];

        // --- NOTIFICACIÓN A SEGUIDORES ---
        try {
            if (!function_exists('enviarEmailNotificacionPublicacionUsuario')) {
                include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/email_helper.php';
            }
            if (!function_exists('getCollectionFavoritos')) {
                include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_favoritos.php';
            }
            if (!function_exists('getCollectionUsuarios')) {
                include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_usuario.php';
            }
            
            $collection_favoritos = getCollectionFavoritos();
            $collection_usuarios = getCollectionUsuarios();
            
            // Buscar la información básica del autor
            $autor = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
            $autor_nombre = $autor['username'] ?? 'Un usuario';
            
            // Buscar todos los seguidores
            $seguidores = $collection_favoritos->find([
                'codigo_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"]),
                'tipo' => 'usuario'
            ]);
            
            $marca_slug = strtolower(str_replace(' ', '-', $marca));
            $url_codigo = "https://www.codigoamigo.com/de-" . $marca_slug . "?codigo=" . $codigo_id;
            
            foreach ($seguidores as $seg) {
                $seguidor = $collection_usuarios->findOne(['_id' => $seg['usuario_id']]);
                if ($seguidor && !empty($seguidor['mail'])) {
                    // Enviar notificacion asincronamente si fuera posible, sino síncrono.
                    enviarEmailNotificacionPublicacionUsuario(
                        $seguidor['mail'],
                        $seguidor['username'] ?? 'Usuario',
                        $autor_nombre,
                        'código',
                        "Se ha publicado un nuevo código para la marca " . $marca,
                        $url_codigo,
                        null
                    );
                }
            }
        } catch (Exception $e) {
            log_error("Error al notificar a seguidores sobre nuevo código: " . $e->getMessage());
        }
        // --- FIN NOTIFICACIÓN ---

        // Redirigir a la página de felicitaciones
        header("Location: /codigo-publicado");
        exit;
    } elseif (!empty($_SESSION['msg_error'])) {
        // createNewCode ya dejó un motivo específico (beneficio > oficial,
        // descripción demasiado corta, etc.). Preservamos ese mensaje y
        // volvemos al formulario sin sobrescribirlo con el genérico.
        $_SESSION['form_data'] = [
            'marca' => $_POST['marca'] ?? '',
            'marca_valor' => $marca,
            'num_beneficio' => $num_beneficio,
            'tipo_beneficio' => $tipo_beneficio,
            'codigo' => $codigo,
            'descuento' => $descuento,
            'descripcion' => $descripcion,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'fecha_caducidad' => $fecha_caducidad
        ];
        header("Location: /nuevo_codigo");
        exit;
    } else {
        // Determinar el tipo de error específico
        $marca_normalizada = normalizeMarcaName($marca);


        // Verificar el tipo específico de error
        try {
            $collection = getCollectionCodigos();
            
            // Verificar si es problema de marca duplicada
            $codigo_existente_marca = $collection->findOne([
                'marca' => $marca_normalizada,
                'id_usuario' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])
            ]);

            // Verificar si es problema de código duplicado
            $codigo_existente_codigo = null;
            if (!empty($codigo)) {
                $codigo_existente_codigo = $collection->findOne([
                    'codigo' => $codigo,
                    'id_usuario' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])
                ]);
            }

            if ($codigo_existente_marca) {
                $codigo_id_existente = (string)$codigo_existente_marca['_id'];
               
                $_SESSION['msg_error'] = "Ya tienes un código publicado para la marca '$marca'. Solo puedes tener un código por marca. Si quieres actualizar tu código, usa la opción 'Modificar' desde tus códigos existentes.";
                $_SESSION['msg_info'] = "Tu código existente: <a href='/de-" . strtolower($marca) . "?codigo=" . $codigo_id_existente . "' target='_blank'>Ver código actual</a> | <a href='/modificar_codigo/" . $codigo_id_existente . "'>Modificar código</a>";
            } elseif ($codigo_existente_codigo) {
                $codigo_id_existente = (string)$codigo_existente_codigo['_id'];
                $_SESSION['msg_error'] = "Ya tienes un código con '$codigo'. No puedes usar el mismo código dos veces. Si quieres actualizar tu código, usa la opción 'Modificar' desde tus códigos existentes.";
                $_SESSION['msg_info'] = "Tu código existente: <a href='/de-" . strtolower($codigo_existente_codigo['marca']) . "?codigo=" . $codigo_id_existente . "' target='_blank'>Ver código actual</a> | <a href='/modificar_codigo/" . $codigo_id_existente . "'>Modificar código</a>";
            } else {
                $_SESSION['msg_error'] = "Error al publicar el código. Inténtalo de nuevo.";
            }
        } catch (Exception $e) {
            $_SESSION['msg_error'] = "Error al publicar el código. Inténtalo de nuevo.";
        }
        
        // Preservar datos del formulario en la sesión para el error
        $_SESSION['form_data'] = [
            'marca' => $_POST['marca'] ?? '',
            'marca_valor' => $marca,
            'num_beneficio' => $num_beneficio,
            'tipo_beneficio' => $tipo_beneficio,
            'codigo' => $codigo,
            'descuento' => $descuento,
            'descripcion' => $descripcion,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'fecha_caducidad' => $fecha_caducidad
        ];
        
        header("Location: /nuevo_codigo");
        exit;
    }
    
} catch (Exception $e) {
    log_error("Error al publicar código: " . $e->getMessage());
    $_SESSION['msg_error'] = "Error al publicar el código. Inténtalo de nuevo.";
    
    // Preservar datos del formulario en la sesión para el error
    $_SESSION['form_data'] = [
        'marca' => $_POST['marca'] ?? '',
        'marca_valor' => $marca,
        'num_beneficio' => $num_beneficio,
        'tipo_beneficio' => $tipo_beneficio,
        'codigo' => $codigo,
        'descuento' => $descuento,
        'descripcion' => $descripcion,
        'provincia' => $provincia,
        'localidad' => $localidad,
        'fecha_caducidad' => $fecha_caducidad
    ];
    
    header("Location: /nuevo_codigo");
    exit;
}
?>
