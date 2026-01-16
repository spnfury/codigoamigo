<?php
session_start();

// Verificar que el usuario esté logueado y sea admin
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_id'], $array_codigos_acceso)) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Incluir funciones
include_once '../myphp/funciones.php';
include_once '../myphp/funciones_chollos.php';
include_once '../myphp/funciones_chollos_amazon.php';
include_once '../myphp/funciones_chollos_groq.php';

// Obtener el método de la petición
$metodo = $_POST['metodo'] ?? $_GET['metodo'] ?? '';

try {
    switch ($metodo) {
        case 'crear_chollo':
            $datos = [
                'titulo' => $_POST['titulo'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'precio_original' => $_POST['precio_original'] ?? null,
                'precio_descuento' => $_POST['precio_descuento'] ?? null,
                'porcentaje_descuento' => $_POST['porcentaje_descuento'] ?? null,
                'enlace' => $_POST['enlace'] ?? '',
                'imagen' => $_POST['imagen'] ?? '',
                'categoria' => isset($_POST['categoria']) ? (is_array($_POST['categoria']) ? $_POST['categoria'] : (json_decode($_POST['categoria'], true) ?: [$_POST['categoria']])) : ['general'],
                'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d H:i:s'),
                'fecha_fin' => $_POST['fecha_fin'] ?? null,
                'fuente' => $_POST['fuente'] ?? 'manual',
                'estado' => isset($_POST['estado']) ? intval($_POST['estado']) : 1
            ];
            
            // Convertir enlace de Amazon si es necesario
            if (!empty($datos['enlace']) && esEnlaceAmazon($datos['enlace'])) {
                $datos['enlace_original'] = $datos['enlace'];
                $datos['enlace'] = convertirEnlaceAmazon($datos['enlace']);
            }
            
            // Reescribir texto con Groq si está activado
            if (isset($_POST['reescribir_automatico']) && $_POST['reescribir_automatico'] == '1') {
                if (!empty($datos['titulo'])) {
                    $resultado_titulo = reescribirTextoGroq($datos['titulo'], 'titulo');
                    if ($resultado_titulo['success']) {
                        $datos['titulo'] = $resultado_titulo['texto_reescrito'];
                        $datos['texto_reescrito'] = true;
                    }
                }
                
                if (!empty($datos['descripcion'])) {
                    // Pasar texto completo para mejor categorización automática (aunque aquí ya tenemos categorías manuales, podría mejorarlas)
                    $resultado_desc = procesarCholloConGroq($datos['titulo'], $datos['descripcion']);
                    if ($resultado_desc['success']) {
                        $datos['descripcion'] = $resultado_desc['descripcion_reescrita'];
                        // Si Groq devuelve categorías y el usuario no especificó (o dejó general), podríamos usarlas
                        if (!empty($resultado_desc['categoria']) && (empty($datos['categoria']) || $datos['categoria'] === ['general'])) {
                           $datos['categoria'] = $resultado_desc['categoria'];
                        }
                    }
                }
            }
            
            $resultado = crearChollo($datos);
            
            // Regenerar sitemaps si la creación fue exitosa
            if ($resultado['success']) {
                include_once dirname(__DIR__) . '/cron/generar_sitemap_chollos.php';
                generarSitemapChollos();
            }
            
            echo json_encode($resultado);
            break;
            
        case 'actualizar_chollo':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                exit;
            }
            
            $datos = [];
            if (isset($_POST['titulo'])) $datos['titulo'] = $_POST['titulo'];
            if (isset($_POST['descripcion'])) $datos['descripcion'] = $_POST['descripcion'];
            if (isset($_POST['precio_original'])) $datos['precio_original'] = $_POST['precio_original'];
            if (isset($_POST['precio_descuento'])) $datos['precio_descuento'] = $_POST['precio_descuento'];
            if (isset($_POST['porcentaje_descuento'])) $datos['porcentaje_descuento'] = $_POST['porcentaje_descuento'];
            if (isset($_POST['enlace'])) {
                $enlace = $_POST['enlace'];
                if (esEnlaceAmazon($enlace)) {
                    $datos['enlace'] = convertirEnlaceAmazon($enlace);
                } else {
                    $datos['enlace'] = $enlace;
                }
            }
            if (isset($_POST['imagen'])) $datos['imagen'] = $_POST['imagen'];
            if (isset($_POST['categoria'])) {
                 $datos['categoria'] = is_array($_POST['categoria']) ? $_POST['categoria'] : (json_decode($_POST['categoria'], true) ?: [$_POST['categoria']]);
            }
            if (isset($_POST['fecha_inicio'])) $datos['fecha_inicio'] = $_POST['fecha_inicio'];
            if (isset($_POST['fecha_fin'])) $datos['fecha_fin'] = $_POST['fecha_fin'];
            if (isset($_POST['fuente'])) $datos['fuente'] = $_POST['fuente'];
            if (isset($_POST['estado'])) $datos['estado'] = intval($_POST['estado']);
            
            $resultado = actualizarChollo($id, $datos);
            
            // Regenerar sitemaps si la actualización fue exitosa y afectó categorías
            if ($resultado['success'] && isset($datos['categoria'])) {
                include_once dirname(__DIR__) . '/cron/generar_sitemap_chollos.php';
                generarSitemapChollos();
            }
            
            echo json_encode($resultado);
            break;
            
        case 'eliminar_chollo':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                exit;
            }
            
            $resultado = eliminarChollo($id);
            echo json_encode($resultado);
            break;
            
        case 'reescribir_texto':
            $id = $_POST['id'] ?? '';
            $tipo = $_POST['tipo'] ?? 'general'; // titulo, descripcion, ambos
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                exit;
            }
            
            $chollo = obtenerCholloPorId($id);
            if (!$chollo) {
                echo json_encode(['success' => false, 'error' => 'Chollo no encontrado']);
                exit;
            }
            
            $actualizaciones = [];
            
            if ($tipo === 'titulo' || $tipo === 'ambos') {
                if (!empty($chollo['titulo'])) {
                    $resultado = reescribirTextoGroq($chollo['titulo'], 'titulo');
                    if ($resultado['success']) {
                        $actualizaciones['titulo'] = $resultado['texto_reescrito'];
                        $actualizaciones['texto_reescrito'] = true;
                    }
                }
            }
            
            if ($tipo === 'descripcion' || $tipo === 'ambos') {
                if (!empty($chollo['descripcion'])) {
                     // Usar procesarCholloConGroq para obtener también categorías si es posible
                    $resultado = procesarCholloConGroq($chollo['titulo'], $chollo['descripcion']);
                    if ($resultado['success']) {
                        $actualizaciones['descripcion'] = $resultado['descripcion_reescrita'];
                        if (!empty($resultado['categoria'])) {
                            $actualizaciones['categoria'] = $resultado['categoria'];
                        }
                    }
                }
            }
            
            if (!empty($actualizaciones)) {
                $resultado = actualizarChollo($id, $actualizaciones);
                echo json_encode($resultado);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se pudo reescribir el texto']);
            }
            break;
            
        case 'publicar_telegram':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                exit;
            }
            
            include_once '../myphp/telegram_chollos_bot.php';
            $resultado = publicarCholloEnTelegram($id);
            echo json_encode($resultado);
            break;
            
        case 'importar_desde_texto':
            $texto = $_POST['texto'] ?? '';
            if (empty($texto)) {
                echo json_encode(['success' => false, 'error' => 'Texto vacío']);
                exit;
            }
            
            // Extraer información del texto
            $info = extraerInfoChollo($texto);
            
            // Convertir enlace de Amazon si es necesario
            if (!empty($info['enlace']) && esEnlaceAmazon($info['enlace'])) {
                $info['enlace_original'] = $info['enlace'];
                $info['enlace'] = convertirEnlaceAmazon($info['enlace']);
            }
            
            // Reescribir y categorizar con Groq
            // Usamos la nueva función que hace todo de una vez
            $resultado_groq = procesarCholloConGroq($info['titulo'], $info['descripcion'], $texto);
            
            if ($resultado_groq['success']) {
                $info['titulo'] = $resultado_groq['titulo_reescrito'] ?: $info['titulo'];
                $info['descripcion'] = $resultado_groq['descripcion_reescrita'] ?: $info['descripcion'];
                $info['categoria'] = $resultado_groq['categoria']; // Array de categorías
                $info['texto_reescrito'] = true;
            } else {
                 // Fallback si falla Groq
                 $info['categoria'] = ['general'];
            }
            
            // Añadir campos adicionales
            $info['fuente'] = $_POST['fuente'] ?? 'telegram';
            $info['estado'] = isset($_POST['estado']) ? intval($_POST['estado']) : 0; // Por defecto inactivo para revisar
            
            echo json_encode(['success' => true, 'datos' => $info]);
            break;
            
        case 'obtener_estadisticas':
            $chollo_id = $_POST['chollo_id'] ?? '';
            if (empty($chollo_id)) {
                echo json_encode(['success' => false, 'error' => 'ID de chollo requerido']);
                exit;
            }
            
            $estadisticas = obtenerEstadisticasChollo($chollo_id);
            echo json_encode(['success' => true, 'estadisticas' => $estadisticas]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Método no válido']);
            break;
    }
} catch (Throwable $e) {
    error_log("Error en chollos_handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}

