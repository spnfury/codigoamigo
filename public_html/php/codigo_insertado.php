<?php
// Incluir archivos necesarios
include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/includes.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';

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
$marca = $_POST['marca'] ?? '';
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
        'marca' => $marca,
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
        'visibilidad' => 'media'
    ];
    
    // Usar función unificada para crear el código
    $resultado = createNewCode($datos_codigo, $_SESSION["user_id"]);
    unset($_SESSION['msg_error']);
    if ($resultado) {
        // Limpiar datos del formulario de la sesión
        unset($_SESSION['form_data']);
        
        // Obtener el ID del código insertado
        $codigo_id = (string)$resultado['_id'];
        
        // Mensaje de éxito
        $_SESSION['msg_success'] = '¡Código publicado exitosamente! 🎉';
        
        // Redirigir a la página del código
        $url = "/de-" . strtolower($marca) . "?codigo=" . $codigo_id;
        header("Location: " . $url);
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
            'marca' => $marca,
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
    error_log("Error al publicar código: " . $e->getMessage());
    $_SESSION['msg_error'] = "Error al publicar el código. Inténtalo de nuevo.";
    
    // Preservar datos del formulario en la sesión para el error
    $_SESSION['form_data'] = [
        'marca' => $marca,
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
