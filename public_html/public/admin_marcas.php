<?php

session_start();

// Mostrar errores en pantalla (solo admin)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/../myphp/funciones_marcas_ia.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$collection_marcas = getCollectionMarcas();
$collection_codigos = getCollectionCodigos();
$collection_categorias = getCollectionCategoriasEvo();
$collection_redirects = getCollectionRedirects();

// Procesar acciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_marca':
            $nombre = trim($_POST['nombre']);
            $categoria = $_POST['categoria'];
            $descripcion = trim($_POST['descripcion']);
            $url = trim($_POST['url']);
            $url_register = trim($_POST['url_register']);
            $seo_que_es = trim($_POST['seo_que_es'] ?? '');
            $seo_como_usar = trim($_POST['seo_como_usar'] ?? '');
            $seo_tips = trim($_POST['seo_tips'] ?? '');
            $seo_faq = trim($_POST['seo_faq'] ?? '');
            $offer_valid_through = trim($_POST['offer_valid_through'] ?? '');
            $ultima_actualizacion_manual = trim($_POST['ultima_actualizacion_manual'] ?? '');

            // Verificar si la marca ya existe
            $marca_existente = $collection_marcas->findOne(['nombre_clave' => strtolower($nombre)]);
            if ($marca_existente) {
                $_SESSION['error_message'] = "La marca '$nombre' ya existe";
            } else {
                $nueva_marca = [
                    'estado' => 1,
                    'nombre' => $nombre,
                    'nombre_clave' => strtolower($nombre),
                    'categoria' => $categoria,
                    'categoria_clave' => strtolower($categoria),
                    'imagen' => '/img/no_image.png',
                    'descripcion' => $descripcion,
                    'descripcion_larga' => $descripcion,
                    'fecha_publicacion' => date('d-m-Y H:i', strtotime('now')),
                    'usuario_creador' => $_SESSION["user_id"],
                    'url' => $url,
                    'url_register' => $url_register,
                    'seo_que_es' => $seo_que_es,
                    'seo_como_usar' => $seo_como_usar,
                    'seo_tips' => $seo_tips,
                    'seo_faq' => $seo_faq,
                    'offer_valid_through' => $offer_valid_through,
                    'ultima_actualizacion_manual' => $ultima_actualizacion_manual,
                    'datos_financieros' => [
                        'enlace_afiliado' => '',
                        'tipo_comision' => 'fijo',
                        'comision_fija' => 0,
                        'comision_porcentaje' => 0,
                        'notas_financieras' => '',
                        'fecha_actualizacion' => date('Y-m-d H:i:s')
                    ],
                    'aviso' => 'Marca creada por administrador'
                ];
                
                // Beneficio oficial (si se definió)
                $bo_cantidad = trim($_POST['beneficio_oficial_cantidad'] ?? '');
                if ($bo_cantidad !== '' && is_numeric($bo_cantidad) && floatval($bo_cantidad) > 0) {
                    $nueva_marca['beneficio_oficial'] = [
                        'cantidad' => floatval($bo_cantidad),
                        'tipo' => $_POST['beneficio_oficial_tipo'] ?? 'euros',
                        'texto' => trim($_POST['beneficio_oficial_texto'] ?? ''),
                        'origen' => 'manual',
                        'actualizado' => date('Y-m-d')
                    ];
                }
                
                $result = $collection_marcas->insertOne($nueva_marca);
                if ($result->getInsertedId()) {
                    $_SESSION['success_message'] = "Marca '$nombre' creada correctamente";
                } else {
                    $_SESSION['error_message'] = "Error al crear la marca";
                }
            }
            break;
            
        case 'update_marca':
            $marca_id = $_POST['marca_id'];
            $nombre = trim($_POST['nombre']);
            $categoria = $_POST['categoria'];
            $descripcion = trim($_POST['descripcion']);
            $url = trim($_POST['url']);
            $url_register = trim($_POST['url_register']);
            $imagen = trim($_POST['imagen']);
            $video = trim($_POST['video'] ?? '');
            $seo_que_es = trim($_POST['seo_que_es'] ?? '');
            $seo_como_usar = trim($_POST['seo_como_usar'] ?? '');
            $seo_tips = trim($_POST['seo_tips'] ?? '');
            $seo_faq = trim($_POST['seo_faq'] ?? '');
            $offer_valid_through = trim($_POST['offer_valid_through'] ?? '');
            $ultima_actualizacion_manual = trim($_POST['ultima_actualizacion_manual'] ?? '');

            // Campos nuevos para marcas destacadas
            $destacada_home = isset($_POST['destacada_home']) && $_POST['destacada_home'] == '1' ? true : false;
            $ventaja_1 = trim($_POST['ventaja_1'] ?? '');
            $ventaja_2 = trim($_POST['ventaja_2'] ?? '');
            $ventaja_3 = trim($_POST['ventaja_3'] ?? '');
            
            // Construir array de ventajas principales
            $ventajas_principales = [];
            if (!empty($ventaja_1)) $ventajas_principales[] = $ventaja_1;
            if (!empty($ventaja_2)) $ventajas_principales[] = $ventaja_2;
            if (!empty($ventaja_3)) $ventajas_principales[] = $ventaja_3;
            
            // Primero obtener la marca actual para preservar el nombre_clave original
            $marca_actual = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_id)]);
            $nombre_clave_original = $marca_actual['nombre_clave'] ?? strtolower($nombre);
            
            $update_data = [
                'nombre' => $nombre,
                'categoria' => $categoria,
                'categoria_clave' => strtolower($categoria),
                'descripcion' => $descripcion,
                'descripcion_larga' => $descripcion,
                'url' => $url,
                'url_register' => $url_register,
                'imagen' => $imagen,
                'video' => $video,
                'seo_que_es' => $seo_que_es,
                'seo_como_usar' => $seo_como_usar,
                'seo_tips' => $seo_tips,
                'seo_faq' => $seo_faq,
                'offer_valid_through' => $offer_valid_through,
                'ultima_actualizacion_manual' => $ultima_actualizacion_manual,
                'destacada_home' => $destacada_home,
                'ventajas_principales' => $ventajas_principales
            ];
            
            // Beneficio oficial
            $bo_cantidad = trim($_POST['beneficio_oficial_cantidad'] ?? '');
            if ($bo_cantidad !== '' && is_numeric($bo_cantidad) && floatval($bo_cantidad) > 0) {
                $update_data['beneficio_oficial'] = [
                    'cantidad' => floatval($bo_cantidad),
                    'tipo' => $_POST['beneficio_oficial_tipo'] ?? 'euros',
                    'texto' => trim($_POST['beneficio_oficial_texto'] ?? ''),
                    'origen' => 'manual',
                    'actualizado' => date('Y-m-d')
                ];
            } elseif ($bo_cantidad === '' || $bo_cantidad === '0') {
                // Si se borra el campo, eliminar el beneficio oficial
                $update_data['beneficio_oficial'] = null;
            }
            
            // Si se marca como destacada y no tiene fecha, añadirla
            if ($destacada_home && empty($marca_actual['fecha_destacada'])) {
                $update_data['fecha_destacada'] = new MongoDB\BSON\UTCDateTime();
            }
            
            // Solo actualizar nombre_clave si el nombre realmente cambió
            if ($marca_actual && $marca_actual['nombre'] !== $nombre) {
                $nuevo_nombre_clave = strtolower($nombre);
                $update_data['nombre_clave'] = $nuevo_nombre_clave;
                
                // Actualizar todos los códigos asociados a esta marca
                $collection_codigos = getCollectionCodigos();
                $collection_codigos->updateMany(
                    ['marca' => $nombre_clave_original],
                    ['$set' => ['marca' => $nuevo_nombre_clave]]
                );
            }
            
            // CORRECCIÓN ADICIONAL: Buscar y corregir códigos con inconsistencias de mayúsculas/minúsculas
            $collection_codigos = getCollectionCodigos();
            
            // Buscar códigos que usen el nombre actual en mayúsculas/minúsculas mixtas
            $codigos_inconsistentes = $collection_codigos->find([
                '$or' => [
                    ['marca' => $marca_actual['nombre']], // Nombre exacto
                    ['marca' => strtoupper($marca_actual['nombre'])], // Todo mayúsculas
                    ['marca' => strtolower($marca_actual['nombre'])], // Todo minúsculas
                    ['marca' => ucfirst(strtolower($marca_actual['nombre']))], // Primera mayúscula
                ]
            ])->toArray();
            
            // Corregir códigos inconsistentes
            foreach ($codigos_inconsistentes as $codigo) {
                if ($codigo['marca'] !== $nombre_clave_original) {
                    $collection_codigos->updateOne(
                        ['_id' => $codigo['_id']],
                        ['$set' => ['marca' => $nombre_clave_original]]
                    );
                }
            }
            
            $result = $collection_marcas->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($marca_id)],
                ['$set' => $update_data]
            );
            
            if ($result->getModifiedCount() > 0) {
                $_SESSION['success_message'] = "Marca actualizada correctamente";
            } else {
                $_SESSION['error_message'] = "No se realizaron cambios en la marca";
            }
            break;
            
        case 'toggle_estado':
            $marca_id = $_POST['marca_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $collection_marcas->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($marca_id)],
                ['$set' => ['estado' => (int)$nuevo_estado]]
            );
            
            $_SESSION['success_message'] = "Estado de la marca actualizado";
            break;
            
        case 'delete_marca':
            $marca_id = $_POST['marca_id'];
            
            // Obtener la marca para acceder a nombre_clave
            $marca = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_id)]);
            
            // Verificar si tiene códigos asociados
            $codigos_count = 0;
            if ($marca && isset($marca['nombre_clave'])) {
                $codigos_count = $collection_codigos->countDocuments(['marca' => $marca['nombre_clave']]);
            }
            
            // Si es una petición AJAX, devolver JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                
                if ($codigos_count > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => "No se puede eliminar la marca porque tiene $codigos_count códigos asociados"
                    ]);
                } else {
                    $result = $collection_marcas->deleteOne(['_id' => new MongoDB\BSON\ObjectId($marca_id)]);
                    if ($result->getDeletedCount() > 0) {
                        echo json_encode([
                            'success' => true,
                            'message' => "Marca eliminada correctamente"
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => "Error al eliminar la marca"
                        ]);
                    }
                }
                exit;
            }
            
            // Si no es AJAX, comportamiento tradicional
            if ($codigos_count > 0) {
                $_SESSION['error_message'] = "No se puede eliminar la marca porque tiene $codigos_count códigos asociados";
            } else {
                $result = $collection_marcas->deleteOne(['_id' => new MongoDB\BSON\ObjectId($marca_id)]);
                if ($result->getDeletedCount() > 0) {
                    $_SESSION['success_message'] = "Marca eliminada correctamente";
                } else {
                    $_SESSION['error_message'] = "Error al eliminar la marca";
                }
            }
            break;
            
        case 'fusionar_marcas':
            $marca_origen_id = $_POST['marca_origen_id'] ?? '';
            $marca_destino_id = $_POST['marca_destino_id'] ?? '';

            if (!$marca_origen_id || !$marca_destino_id) {
                $_SESSION['error_message'] = "Faltan parámetros para la fusión de marcas";
                break;
            }

            // Obtener información de las marcas
            $marca_origen = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_origen_id)]);
            $marca_destino = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_destino_id)]);

            if (!$marca_origen) {
                $_SESSION['error_message'] = "La marca origen no existe";
            } elseif (!$marca_destino) {
                $_SESSION['error_message'] = "La marca destino no existe";
            } elseif ($marca_origen_id === $marca_destino_id) {
                $_SESSION['error_message'] = "No puedes fusionar una marca consigo misma";
            } else {
                // Contar códigos antes de la fusión para mostrar estadísticas
                $codigos_origen_count = $collection_codigos->countDocuments(['marca' => $marca_origen['nombre_clave']]);

                    // Actualizar códigos para usar la marca destino
                    $result_codigos = $collection_codigos->updateMany(
                        ['marca' => $marca_origen['nombre_clave']],
                        ['$set' => ['marca' => $marca_destino['nombre_clave']]]
                    );

                // Crear redirección 301 antes de eliminar la marca origen
                $redirect_created = create_brand_redirect(
                    $marca_origen['nombre_clave'],
                    $marca_destino['nombre_clave'],
                    'fusion'
                );

                // Eliminar marca origen
                $collection_marcas->deleteOne(['_id' => new MongoDB\BSON\ObjectId($marca_origen_id)]);

                $success_message = "✅ Fusión completada exitosamente!<br>";
                $success_message .= "📦 Marca origen eliminada: <strong>{$marca_origen['nombre']}</strong><br>";
                $success_message .= "🎯 Marca destino: <strong>{$marca_destino['nombre']}</strong><br>";
                $success_message .= "🔄 Códigos transferidos: <strong>{$result_codigos->getModifiedCount()}</strong>";

                if ($redirect_created) {
                    $success_message .= "<br>🔗 Redirección 301 creada: <code>/de-{$marca_origen['nombre_clave']} → /de-{$marca_destino['nombre_clave']}</code>";
                }

                $_SESSION['success_message'] = $success_message;
            }
            break;

        case 'fusionar_masiva':
            $marcas_origen_ids = $_POST['marcas_origen_ids'] ?? [];
            $marca_destino_id = $_POST['marca_destino_id'] ?? '';

            if (empty($marcas_origen_ids) || !$marca_destino_id) {
                $_SESSION['error_message'] = "Faltan parámetros para la fusión masiva";
            break;
            }

            // Usar la función de fusión masiva
            $resultado_fusion = fusionar_marcas_masiva($marcas_origen_ids, $marca_destino_id);

            if (isset($resultado_fusion['error'])) {
                $_SESSION['error_message'] = $resultado_fusion['error'];
            } else {
                $success_message = "🔥 ¡Fusión Masiva Completada!<br>";
                $success_message .= "📦 Marcas origen eliminadas: <strong>{$resultado_fusion['fusionadas']}</strong><br>";
                $success_message .= "🔄 Códigos transferidos: <strong>{$resultado_fusion['codigos_transferidos']}</strong><br>";
                $success_message .= "🔗 Redirecciones creadas: <strong>{$resultado_fusion['redirecciones_creadas']}</strong>";

                if (!empty($resultado_fusion['errores'])) {
                    $success_message .= "<br><br><strong>Errores encontrados:</strong><br>";
                    foreach ($resultado_fusion['errores'] as $error) {
                        $success_message .= "• {$error}<br>";
                    }
                }

                $_SESSION['success_message'] = $success_message;
            }
            break;

        case 'delete_redirect':
            $redirect_id = $_POST['redirect_id'];
            if (delete_redirect($redirect_id)) {
                $_SESSION['success_message'] = "Redirección eliminada correctamente.";
            } else {
                $_SESSION['error_message'] = "Error al eliminar la redirección.";
            }
            break;

        case 'toggle_redirect':
            $redirect_id = $_POST['redirect_id'];
            if (toggle_redirect_status($redirect_id)) {
                $_SESSION['success_message'] = "Estado de la redirección actualizado.";
            } else {
                $_SESSION['error_message'] = "Error al actualizar el estado de la redirección.";
            }
            break;

        case 'cleanup_redirects':
            $cleaned_count = cleanup_orphan_redirects();
            if ($cleaned_count > 0) {
                $_SESSION['success_message'] = "Se limpiaron {$cleaned_count} redirecciones huérfanas.";
            } else {
                $_SESSION['info_message'] = "No se encontraron redirecciones huérfanas.";
            }
            break;

        // Endpoints AJAX para IA
        case 'generar_descripcion_ia':
            header('Content-Type: application/json');
            $nombre_marca = trim($_POST['nombre_marca'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            
            if (empty($nombre_marca)) {
                echo json_encode(['success' => false, 'error' => 'Nombre de marca requerido']);
                exit;
            }
            
            $resultado = generarDescripcionMarcaIA($nombre_marca, $categoria);
            echo json_encode($resultado);
            exit;

        case 'generar_descripcion_larga_ia':
            header('Content-Type: application/json');
            $nombre_marca = trim($_POST['nombre_marca'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            
            if (empty($nombre_marca)) {
                echo json_encode(['success' => false, 'error' => 'Nombre de marca requerido']);
                exit;
            }
            
            $resultado = generarDescripcionLargaMarcaIA($nombre_marca, $categoria);
            echo json_encode($resultado);
            exit;

        case 'generar_ventaja_ia':
            header('Content-Type: application/json');
            $nombre_marca = trim($_POST['nombre_marca'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $numero_ventaja = intval($_POST['numero_ventaja'] ?? 1);
            
            if (empty($nombre_marca)) {
                echo json_encode(['success' => false, 'error' => 'Nombre de marca requerido']);
                exit;
            }
            
            $resultado = generarVentajaMarcaIA($nombre_marca, $categoria, $numero_ventaja);
            echo json_encode($resultado);
            exit;

        case 'buscar_imagenes_ia':
            header('Content-Type: application/json');
            $nombre_marca = trim($_POST['nombre_marca'] ?? '');
            
            if (empty($nombre_marca)) {
                echo json_encode(['success' => false, 'error' => 'Nombre de marca requerido']);
                exit;
            }
            
            $resultado = buscarImagenesMarca($nombre_marca, 10);
            echo json_encode($resultado);
            exit;
    }
    
    // Solo redirigir si no es una petición AJAX
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        header('Location: admin_marcas.php');
        exit;
    }
}

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Obtener parámetros de ordenamiento
$sort_field = $_GET['sort'] ?? 'fecha_publicacion';
$sort_direction = $_GET['dir'] ?? 'desc';


// Construir filtros para la consulta
$filtros = [];
if ($filtro_estado !== '') {
    $filtros['estado'] = (int)$filtro_estado;
}
if ($filtro_categoria) {
    $filtros['categoria'] = $filtro_categoria;
}
if ($filtro_busqueda) {
    $filtros['$or'] = [
        ['nombre' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
        ['descripcion' => ['$regex' => $filtro_busqueda, '$options' => 'i']]
    ];
}

// Obtener marcas con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);
$skip = ($page - 1) * $limit;

// Validar límite para evitar valores extremos
$opciones_limit = [10, 20, 50, 100, 200, 500, 1000, 'todos'];
if (!in_array($limit, $opciones_limit) && $limit !== 'todos') {
    $limit = 20;
}

// Si se selecciona "todos", obtener todos los resultados
if ($limit === 'todos') {
    $limit = 999999; // Número muy grande para obtener todos
}

// Construir ordenamiento
$sort_options = [];
$sort_direction_value = ($sort_direction === 'asc') ? 1 : -1;

switch ($sort_field) {
    case 'nombre':
        $sort_options['nombre'] = $sort_direction_value;
        break;
    case 'categoria':
        $sort_options['categoria'] = $sort_direction_value;
        break;
    case 'estado':
        $sort_options['estado'] = $sort_direction_value;
        break;
    case 'fecha_publicacion':
        $sort_options['fecha_publicacion'] = $sort_direction_value;
        break;
    case 'codigos':
        // Para ordenar por número de códigos, usamos _id como fallback
        // En una implementación más avanzada se podría usar aggregation pipeline
        $sort_options['_id'] = $sort_direction_value;
        break;
    case 'destacada_home':
        $sort_options['destacada_home'] = $sort_direction_value;
        // Ordenar también por fecha_destacada si existe
        $sort_options['fecha_destacada'] = -1;
        break;
    default:
        $sort_options['fecha_publicacion'] = -1;
        break;
}

// Si se ordena por códigos, usar aggregation pipeline
if ($sort_field === 'codigos') {
    $pipeline = [];
    
    // Añadir $match solo si hay filtros
    if (!empty($filtros)) {
        $pipeline[] = ['$match' => $filtros];
    }
    
    $pipeline[] = [
        '$lookup' => [
            'from' => 'codigos',
            'localField' => 'nombre_clave',
            'foreignField' => 'marca',
            'as' => 'codigos_relacionados'
        ]
    ];
    
    $pipeline[] = [
        '$addFields' => [
            'codigos_count' => ['$size' => '$codigos_relacionados']
        ]
    ];
    
    // ORDENAMIENTO COMPLETO: Primero ordenar TODOS los resultados
    $pipeline[] = ['$sort' => ['codigos_count' => $sort_direction_value]];
    
    // Luego aplicar paginación
    $pipeline[] = ['$skip' => $skip];
    $pipeline[] = ['$limit' => $limit];
    
    $pipeline[] = [
        '$project' => [
            'codigos_relacionados' => 0,
            'codigos_count' => 0
        ]
    ];
    
    $marcas = $collection_marcas->aggregate($pipeline)->toArray();
} else {
    // Ordenamiento optimizado: usar índices de MongoDB directamente
    $marcas = $collection_marcas->find($filtros, [
        'sort' => $sort_options,
        'skip' => $skip,
        'limit' => $limit
    ])->toArray();
}

$total_marcas = $collection_marcas->countDocuments($filtros);
$total_pages = ceil($total_marcas / $limit);

// Obtener categorías para el filtro
$categorias = $collection_categorias->find([], ['sort' => ['nombre' => 1]])->toArray();

// Obtener estadísticas
$estadisticas = [
    'total' => $collection_marcas->countDocuments([]),
    'activas' => $collection_marcas->countDocuments(['estado' => 1]),
    'inactivas' => $collection_marcas->countDocuments(['estado' => 0]),
    'sin_imagen' => $collection_marcas->countDocuments(['imagen' => '/img/no_image.png'])
];

// Obtener redirecciones para mostrar en el panel
$redirects = get_all_redirects(50);

// Crear mapa de redirecciones activas para mostrar indicadores en la tabla de marcas
$active_redirects = [];
foreach ($redirects as $redirect) {
    if (($redirect['is_active'] ?? true) && isset($redirect['old_brand_key'], $redirect['new_brand_key'])) {
        $active_redirects[$redirect['old_brand_key']] = $redirect['new_brand_key'];
    }
}

// Obtener marcas duplicadas detectadas automáticamente
$marcas_duplicadas = obtener_marcas_duplicadas_para_panel(75); // Umbral del 75% de similitud

// Obtener marcas más populares (con más códigos)
$pipeline_populares = [
    ['$group' => [
        '_id' => '$marca',
        'total_codigos' => ['$sum' => 1]
    ]],
    ['$sort' => ['total_codigos' => -1]],
    ['$limit' => 10]
];
$marcas_populares = $collection_codigos->aggregate($pipeline_populares)->toArray();

$title = "Gestión de Marcas - Panel de Administración";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .marca-imagen {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        /* Estilos para el enlace de marca */
        .marca-link {
            color: #333 !important;
            transition: all 0.3s ease;
        }
        
        .marca-link:hover {
            color: #E30613 !important;
            text-decoration: none !important;
            transform: translateX(2px);
        }
        
        .marca-link i {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .marca-link:hover i {
            opacity: 1;
        }
        
        /* Estilos para encabezados ordenables */
        .sortable-header {
            color: #333 !important;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .sortable-header:hover {
            color: #E30613 !important;
            text-decoration: none !important;
        }
        
        .sortable-header i {
            font-size: 0.8em;
        }
        
        .sortable-header:hover i {
            color: #E30613 !important;
        }

        /* Estilos para el modal de fusión mejorado */
        .search-results-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }

        .list-group-item-action:hover {
            background-color: #f8f9fa;
        }

        .fusion-step {
            min-height: 200px;
        }

        .brand-search-input {
            font-size: 1.1em;
            padding: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_marcas.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">Gestión de Marcas</h5>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalCrearMarca">
                                <i class="fas fa-plus me-1"></i>Nueva Marca
                            </button>
                            <span class="text-muted me-3">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                            <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="p-4">
                    <!-- Mensajes -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['info_message'])): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['info_message']; unset($_SESSION['info_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo number_format($estadisticas['total']); ?></h3>
                                    <p class="text-muted mb-0">Total Marcas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['activas']); ?></h3>
                                    <p class="text-muted mb-0">Activas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-danger"><?php echo number_format($estadisticas['inactivas']); ?></h3>
                                    <p class="text-muted mb-0">Inactivas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-warning"><?php echo number_format($estadisticas['sin_imagen']); ?></h3>
                                    <p class="text-muted mb-0">Sin Imagen</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navegación por pestañas -->
                    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="marcas-tab" data-bs-toggle="tab" data-bs-target="#marcas-panel" type="button" role="tab">
                                <i class="fas fa-tags me-2"></i>Gestión de Marcas
                                                </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="duplicadas-tab" data-bs-toggle="tab" data-bs-target="#duplicadas-panel" type="button" role="tab">
                                <i class="fas fa-clone me-2"></i>Duplicadas
                                <?php if (!empty($marcas_duplicadas)): ?>
                                    <span class="badge bg-warning ms-1"><?php echo count($marcas_duplicadas); ?> grupos</span>
                                <?php endif; ?>
                                </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="redirects-tab" data-bs-toggle="tab" data-bs-target="#redirects-panel" type="button" role="tab">
                                <i class="fas fa-exchange-alt me-2"></i>Redirecciones 301
                                <?php if (!empty($redirects)): ?>
                                    <span class="badge bg-info ms-1"><?php echo count($redirects); ?></span>
                                                    <?php endif; ?>
                                    </button>
                        </li>
                    </ul>

                    <!-- Contenido de pestañas -->
                    <div class="tab-content" id="adminTabContent">

                        <!-- Panel de Marcas -->
                        <div class="tab-pane fade show active" id="marcas-panel" role="tabpanel">

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todas</option>
                                        <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Activa</option>
                                        <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Inactiva</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Categoría</label>
                                    <select name="categoria" class="form-select">
                                        <option value="">Todas</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['nombre']); ?>" 
                                                <?php echo $filtro_categoria === $categoria['nombre'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Buscar</label>
                                    <input type="text" name="busqueda" class="form-control" 
                                           placeholder="Nombre o descripción" value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Mostrar</label>
                                    <select name="limit" class="form-select">
                                        <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="20" <?php echo $limit === 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                                        <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200</option>
                                        <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                                        <option value="1000" <?php echo $limit === 1000 ? 'selected' : ''; ?>>1000</option>
                                        <option value="todos" <?php echo $limit === 'todos' || $limit >= 999999 ? 'selected' : ''; ?>>Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de marcas -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Lista de Marcas</h5>
                                <?php if ($sort_field && $sort_field !== 'fecha_publicacion'): ?>
                                    <small class="text-muted">
                                        Ordenado por: <strong><?php echo ucfirst($sort_field); ?></strong> 
                                        (<?php echo $sort_direction === 'asc' ? 'Ascendente' : 'Descendente'; ?>)
                                    </small>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-primary"><?php echo number_format($total_marcas); ?> marcas</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Imagen</th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'nombre', 'dir' => ($sort_field === 'nombre' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Nombre
                                                    <?php if ($sort_field === 'nombre'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'categoria', 'dir' => ($sort_field === 'categoria' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Categoría
                                                    <?php if ($sort_field === 'categoria'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'estado', 'dir' => ($sort_field === 'estado' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Estado
                                                    <?php if ($sort_field === 'estado'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'destacada_home', 'dir' => ($sort_field === 'destacada_home' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Destacada
                                                    <?php if ($sort_field === 'destacada_home'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'codigos', 'dir' => ($sort_field === 'codigos' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Códigos
                                                    <?php if ($sort_field === 'codigos'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'fecha_publicacion', 'dir' => ($sort_field === 'fecha_publicacion' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Fecha
                                                    <?php if ($sort_field === 'fecha_publicacion'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                Acciones
                                                <br><small class="text-muted">Editar | Estado | Fusionar | Eliminar</small>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($marcas as $marca): 
                                            // Contar códigos de esta marca
                                            $codigos_count = $collection_codigos->countDocuments(['marca' => $marca['nombre_clave']]);
                                        ?>
                                        <tr id="marca-row-<?php echo $marca['_id']; ?>">
                                            <td>
                                                <img src="<?php echo htmlspecialchars($marca['imagen'] ?? '/img/no_image.png'); ?>" 
                                                     class="marca-imagen" 
                                                     onerror="this.src='/img/no_image.png'">
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>
                                                        <a href="https://www.codigoamigo.com/de-<?php echo urlencode($marca['nombre_clave']); ?>" 
                                                           target="_blank" 
                                                           class="text-decoration-none marca-link"
                                                           title="Códigos descuento <?php echo htmlspecialchars(trim($marca['nombre'])); ?>">
                                                            <?php echo htmlspecialchars(trim($marca['nombre'])); ?>
                                                            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                                        </a>
                                                    </strong>
                                                    <?php if (isset($active_redirects[$marca['nombre_clave']])): ?>
                                                        <br><small class="text-warning">
                                                            <i class="fas fa-exchange-alt me-1"></i>
                                                            Redirige desde: <?php echo htmlspecialchars($active_redirects[$marca['nombre_clave']]); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if (isset($marca['descripcion']) && $marca['descripcion']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($marca['descripcion'], 0, 50)) . '...'; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($marca['categoria'] ?? 'Sin categoría'); ?></span>
                                            </td>
                                            <td>
                                                <?php if (($marca['estado'] ?? 0) == 1): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($marca['destacada_home']) && $marca['destacada_home']): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-star"></i> Destacada
                                                    </span>
                                                    <?php if (isset($marca['ventajas_principales']) && !empty($marca['ventajas_principales'])): ?>
                                                        <br><small class="text-muted" title="<?php echo htmlspecialchars(implode(' | ', $marca['ventajas_principales'])); ?>">
                                                            <i class="fas fa-check-circle"></i> <?php echo count($marca['ventajas_principales']); ?> ventajas
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $codigos_count; ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    if (isset($marca['fecha_publicacion'])) {
                                                        echo date('d/m/Y', strtotime($marca['fecha_publicacion']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#modalEditarMarca" 
                                                            data-marca-id="<?php echo $marca['_id']; ?>"
                                                            data-marca-nombre="<?php echo htmlspecialchars(trim($marca['nombre'])); ?>"
                                                            data-marca-categoria="<?php echo htmlspecialchars($marca['categoria'] ?? ''); ?>"
                                                            data-marca-descripcion="<?php echo htmlspecialchars($marca['descripcion'] ?? ''); ?>"
                                                            data-marca-url="<?php echo htmlspecialchars($marca['url'] ?? ''); ?>"
                                                            data-marca-url-register="<?php echo htmlspecialchars($marca['url_register'] ?? ''); ?>"
                                                            data-marca-imagen="<?php echo htmlspecialchars($marca['imagen'] ?? ''); ?>"
                                                            data-marca-video="<?php echo htmlspecialchars($marca['video'] ?? ''); ?>"
                                                            data-marca-seo-que-es="<?php echo htmlspecialchars($marca['seo_que_es'] ?? ''); ?>"
                                                            data-marca-seo-como-usar="<?php echo htmlspecialchars($marca['seo_como_usar'] ?? ''); ?>"
                                                            data-marca-seo-tips="<?php echo htmlspecialchars($marca['seo_tips'] ?? ''); ?>"
                                                            data-marca-seo-faq="<?php echo htmlspecialchars($marca['seo_faq'] ?? ''); ?>"
                                                            data-marca-offer-valid="<?php echo htmlspecialchars($marca['offer_valid_through'] ?? ''); ?>"
                                                            data-marca-ultima-actualizacion="<?php echo htmlspecialchars($marca['ultima_actualizacion_manual'] ?? ''); ?>"
                                                            data-marca-destacada="<?php echo isset($marca['destacada_home']) && $marca['destacada_home'] ? '1' : '0'; ?>"
                                                            data-marca-ventaja-1="<?php echo htmlspecialchars($marca['ventajas_principales'][0] ?? ''); ?>"
                                                            data-marca-ventaja-2="<?php echo htmlspecialchars($marca['ventajas_principales'][1] ?? ''); ?>"
                                                            data-marca-ventaja-3="<?php echo htmlspecialchars($marca['ventajas_principales'][2] ?? ''); ?>"
                                                            data-marca-bo-cantidad="<?php echo htmlspecialchars($marca['beneficio_oficial']['cantidad'] ?? ''); ?>"
                                                            data-marca-bo-tipo="<?php echo htmlspecialchars($marca['beneficio_oficial']['tipo'] ?? 'euros'); ?>"
                                                            data-marca-bo-texto="<?php echo htmlspecialchars($marca['beneficio_oficial']['texto'] ?? ''); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="toggleEstado('<?php echo $marca['_id']; ?>', <?php echo $marca['estado'] ?? 0; ?>)">
                                                        <i class="fas fa-toggle-<?php echo ($marca['estado'] ?? 0) == 1 ? 'on' : 'off'; ?>"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#modalFusionar" 
                                                            data-marca-id="<?php echo $marca['_id']; ?>"
                                                            data-marca-nombre="<?php echo htmlspecialchars(trim($marca['nombre'])); ?>"
                                                            title="Fusionar esta marca con otra marca existente">
                                                        <i class="fas fa-compress-arrows-alt"></i>
                                                        <span class="d-none d-lg-inline ms-1">Fusionar</span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarMarca('<?php echo $marca['_id']; ?>', '<?php echo htmlspecialchars($marca['nombre']); ?>', <?php echo $codigos_count; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación de marcas">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&estado=<?php echo $filtro_estado; ?>&categoria=<?php echo urlencode($filtro_categoria); ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Marcas más populares -->
                    <?php if (!empty($marcas_populares)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Marcas Más Populares</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($marcas_populares as $marca_popular): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="d-flex align-items-center p-2 border rounded">
                                        <div class="me-3">
                                            <span class="badge bg-primary fs-6"><?php echo $marca_popular['total_codigos']; ?></span>
                                        </div>
                                        <div>
                                            <strong><?php echo ucfirst($marca_popular['_id']); ?></strong>
                                            <br><small class="text-muted">códigos</small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
                        <!-- Fin del panel de marcas -->

                        <!-- Panel de Duplicadas -->
                        <div class="tab-pane fade" id="duplicadas-panel" role="tabpanel">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Marcas Duplicadas Detectadas</h5>
                                    <div>
                                        <?php if (!empty($marcas_duplicadas)): ?>
                                            <span class="badge bg-warning me-2"><?php echo count($marcas_duplicadas); ?> grupos encontrados</span>
                                            <button type="button" class="btn btn-warning btn-sm" onclick="fusionarSeleccionadas()">
                                                <i class="fas fa-compress-arrows-alt me-1"></i>Fusionar Seleccionadas
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($marcas_duplicadas)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h5 class="text-success">¡Excelente!</h5>
                                            <p class="text-muted">No se detectaron marcas duplicadas con el umbral de similitud actual (75%).</p>
                                            <small class="text-muted">Puedes ajustar el umbral de similitud si necesitas detectar duplicados más sutiles.</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Detección automática:</strong> Se encontraron grupos de marcas con similitud ≥ 75%.
                                            Selecciona las marcas que quieres fusionar y elige una marca destino para cada grupo.
                                        </div>

                                        <?php foreach ($marcas_duplicadas as $grupo): ?>
                                            <div class="card mb-4 border-warning">
                                                <div class="card-header bg-warning bg-opacity-10">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-0">
                                                                <i class="fas fa-clone me-2"></i>Grupo de Duplicadas
                                                                <span class="badge bg-warning ms-2"><?php echo $grupo['similitud_promedio']; ?>% similitud</span>
                                                            </h6>
                                                            <small class="text-muted">
                                                                <?php echo count($grupo['marcas']); ?> marcas similares •
                                                                <?php echo $grupo['total_codigos']; ?> códigos totales
                                                            </small>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input group-checkbox" type="checkbox"
                                                                   id="grupo-<?php echo $grupo['grupo_id']; ?>"
                                                                   onchange="toggleGrupoSeleccion(this, '<?php echo $grupo['grupo_id']; ?>')">
                                                            <label class="form-check-label" for="grupo-<?php echo $grupo['grupo_id']; ?>">
                                                                Seleccionar grupo
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <?php foreach ($grupo['marcas'] as $marca): ?>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="card border-light">
                                                                    <div class="card-body">
                                                                        <div class="d-flex align-items-start">
                                                                            <div class="form-check me-3">
                                                                                <input class="form-check-input marca-checkbox"
                                                                                       type="checkbox"
                                                                                       id="marca-<?php echo $marca['id']; ?>"
                                                                                       data-grupo="<?php echo $grupo['grupo_id']; ?>"
                                                                                       data-marca-id="<?php echo $marca['id']; ?>"
                                                                                       data-marca-nombre="<?php echo htmlspecialchars(trim($marca['nombre'])); ?>"
                                                                                       onchange="actualizarSeleccionGrupo('<?php echo $grupo['grupo_id']; ?>')">
                                                                                <label class="form-check-label" for="marca-<?php echo $marca['id']; ?>">
                                                                                    Seleccionar
                                                                                </label>
                                                                            </div>
                                                                            <div class="flex-grow-1">
                                                                                <h6 class="mb-1">
                                                                                    <a href="https://www.codigoamigo.com/de-<?php echo urlencode($marca['nombre_clave']); ?>"
                                                                                       target="_blank" class="text-decoration-none"
                                                                                       title="Códigos descuento <?php echo htmlspecialchars(trim($marca['nombre'])); ?>">
                                                                                        <?php echo htmlspecialchars(trim($marca['nombre'])); ?>
                                                                                        <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                                                                    </a>
                                                                                </h6>
                                                                                <p class="text-muted mb-2">
                                                                                    <small><?php echo htmlspecialchars($marca['nombre_clave']); ?></small>
                                                                                </p>
                                                                                <div class="d-flex justify-content-between align-items-center">
                                                                                    <span class="badge bg-primary">
                                                                                        <?php echo $marca['codigos_count']; ?> códigos
                                                                                    </span>
                                                                                    <?php if (isset($active_redirects[$marca['nombre_clave']])): ?>
                                                                                        <small class="text-warning">
                                                                                            <i class="fas fa-exchange-alt me-1"></i>
                                                                                            Redirige desde: <?php echo htmlspecialchars($active_redirects[$marca['nombre_clave']]); ?>
                                                                                        </small>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>

                                                    <!-- Selector de marca destino para este grupo -->
                                                    <div class="mt-3 p-3 bg-light rounded">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-3">
                                                                <label class="form-label mb-0">Marca Destino:</label>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <input type="text" class="form-control mb-2"
                                                                       placeholder="Buscar marca..."
                                                                       onkeyup="filtrarMarcas(this, 'destino-<?php echo $grupo['grupo_id']; ?>')">
                                                                <select class="form-select marca-destino-select"
                                                                        id="destino-<?php echo $grupo['grupo_id']; ?>"
                                                                        data-grupo="<?php echo $grupo['grupo_id']; ?>"
                                                                        onchange="actualizarMarcaDestino(this)">
                                                                    <option value="">Seleccionar marca destino...</option>
                                                                    <?php
                                                                    // Obtener todas las marcas activas con su conteo de códigos
                                                                    $marcas_con_codigos = $collection_codigos->aggregate([
                                                                        ['$match' => ['estado' => 0]], // Solo códigos activos
                                                                        ['$group' => [
                                                                            '_id' => '$marca',
                                                                            'total_codigos' => ['$sum' => 1]
                                                                        ]]
                                                                    ])->toArray();

                                                                    // Crear array asociativo para fácil acceso
                                                                    $conteo_codigos = [];
                                                                    foreach ($marcas_con_codigos as $item) {
                                                                        $conteo_codigos[$item['_id']] = $item['total_codigos'];
                                                                    }

                                                                    $todas_marcas = $collection_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1], 'limit' => 10000])->toArray();
                                                                    foreach ($todas_marcas as $marca_opcion):
                                                                        // Nota: Permitimos seleccionar cualquier marca activa como destino,
                                                                        // incluyendo las del mismo grupo, para mayor flexibilidad
                                                                            // Limpiar y formatear el nombre de la marca
                                                                            $nombre_limpio = trim($marca_opcion['nombre']);
                                                                            $categoria_limpia = trim($marca_opcion['categoria']);
                                                                            $nombre_clave_limpio = trim($marca_opcion['nombre_clave']);
                                                                            $codigos_count = isset($conteo_codigos[$nombre_clave_limpio]) ? $conteo_codigos[$nombre_clave_limpio] : 0;
                                                                    ?>
                                                                        <option value="<?php echo $marca_opcion['_id']; ?>">
                                                                            <?php echo htmlspecialchars($nombre_limpio); ?>
                                                                            <?php if ($categoria_limpia): ?>
                                                                                <small class="text-muted"> (<?php echo htmlspecialchars($categoria_limpia); ?>)</small>
                                                                            <?php endif; ?>
                                                                            <small class="text-info"> | <?php echo $codigos_count; ?> códigos</small>
                                                                            <small class="text-secondary"> | <?php echo htmlspecialchars($nombre_clave_limpio); ?></small>
                                                                            <?php if (isset($active_redirects[$marca_opcion['nombre_clave']])): ?>
                                                                                <small class="text-warning"> • Redirige</small>
                                                                            <?php endif; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <button type="button" class="btn btn-success btn-sm"
                                                                        onclick="fusionarGrupo('<?php echo $grupo['grupo_id']; ?>')"
                                                                        style="display: none;"
                                                                        id="btn-fusionar-<?php echo $grupo['grupo_id']; ?>">
                                                                    <i class="fas fa-compress-arrows-alt me-1"></i>Fusionar Grupo
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <!-- Área para fusión masiva -->
                                        <div class="card mt-4 border-primary">
                                            <div class="card-header bg-primary bg-opacity-10">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-magic me-2"></i>Fusión Masiva Global
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <p class="text-muted mb-3">
                                                            Selecciona múltiples marcas de diferentes grupos para fusionarlas todas hacia una marca destino común.
                                                        </p>
                                                        <div class="mb-3">
                                                            <label class="form-label">Marca Destino Global:</label>
                                                            <select class="form-select" id="marca-destino-global">
                                                                <option value="">Seleccionar marca destino global...</option>
                                                                <?php
                                                                // Obtener todas las marcas activas con su conteo de códigos
                                                                $marcas_con_codigos_global = $collection_codigos->aggregate([
                                                                    ['$match' => ['estado' => 0]], // Solo códigos activos
                                                                    ['$group' => [
                                                                        '_id' => '$marca',
                                                                        'total_codigos' => ['$sum' => 1]
                                                                    ]]
                                                                ])->toArray();

                                                                // Crear array asociativo para fácil acceso
                                                                $conteo_codigos_global = [];
                                                                foreach ($marcas_con_codigos_global as $item) {
                                                                    $conteo_codigos_global[$item['_id']] = $item['total_codigos'];
                                                                }

                                                                $todas_marcas = $collection_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1], 'limit' => 10000])->toArray();
                                                                foreach ($todas_marcas as $marca):
                                                                    // Limpiar y formatear el nombre de la marca
                                                                    $nombre_limpio = trim($marca['nombre']);
                                                                    $categoria_limpia = trim($marca['categoria']);
                                                                    $nombre_clave_limpio = trim($marca['nombre_clave']);
                                                                    $codigos_count = isset($conteo_codigos_global[$nombre_clave_limpio]) ? $conteo_codigos_global[$nombre_clave_limpio] : 0;
                                                                ?>
                                                                    <option value="<?php echo $marca['_id']; ?>">
                                                                        <?php echo htmlspecialchars($nombre_limpio); ?>
                                                                        <?php if ($categoria_limpia): ?>
                                                                            <small class="text-muted"> (<?php echo htmlspecialchars($categoria_limpia); ?>)</small>
                                                                        <?php endif; ?>
                                                                        <small class="text-info"> | <?php echo $codigos_count; ?> códigos</small>
                                                                        <small class="text-secondary"> | <?php echo htmlspecialchars($nombre_clave_limpio); ?></small>
                                                                        <?php if (isset($active_redirects[$marca['nombre_clave']])): ?>
                                                                            <small class="text-warning"> • Redirige</small>
                                                                        <?php endif; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">Buscar Marca:</label>
                                                            <input type="text" class="form-control"
                                                                   placeholder="Buscar marca..."
                                                                   onkeyup="filtrarMarcas(this, 'marca-destino-global')">
                                                        </div>
                                                        <button type="button" class="btn btn-primary"
                                                                onclick="fusionarMasivaGlobal()"
                                                                id="btn-fusion-masiva"
                                                                style="display: none;">
                                                            <i class="fas fa-rocket me-2"></i>Fusionar Todas las Seleccionadas
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Fin del panel de duplicadas -->

                        <!-- Panel de Redirecciones -->
                        <div class="tab-pane fade" id="redirects-panel" role="tabpanel">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Gestión de Redirecciones 301</h5>
                                    <div>
                                        <button type="button" class="btn btn-outline-warning btn-sm me-2"
                                                onclick="cleanupRedirects()">
                                            <i class="fas fa-broom me-1"></i>Limpiar Huérfanas
                                        </button>
                                        <span class="badge bg-info"><?php echo count($redirects); ?> redirecciones</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($redirects)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay redirecciones configuradas</h5>
                                            <p class="text-muted">Las redirecciones se crean automáticamente cuando fusionas marcas.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Marca Antigua</th>
                                                        <th>Redirige a</th>
                                                        <th>Motivo</th>
                                                        <th>Creada</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($redirects as $redirect): ?>
                                                    <tr>
                                                        <td>
                                                            <strong>/de-<?php echo htmlspecialchars($redirect['old_brand_key'] ?? 'borrada'); ?></strong>
                                                        </td>
                                                        <td>
                                                            <a href="https://www.codigoamigo.com<?php echo htmlspecialchars($redirect['redirect_url'] ?? '#'); ?>"
                                                               target="_blank" class="text-decoration-none">
                                                                /de-<?php echo htmlspecialchars($redirect['new_brand_key'] ?? 'desconocida'); ?>
                                                                <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $redirect['reason'] === 'fusion' ? 'warning' : 'info'; ?>">
                                                                <?php echo ucfirst($redirect['reason']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('d/m/Y H:i', strtotime($redirect['created_at'])); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if (($redirect['is_active'] ?? true)): ?>
                                                                <span class="badge bg-success">Activa</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Inactiva</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                                        onclick="toggleRedirect('<?php echo $redirect['_id']; ?>')">
                                                                    <i class="fas fa-toggle-<?php echo ($redirect['is_active'] ?? true) ? 'on' : 'off'; ?>"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                                        onclick="deleteRedirect('<?php echo $redirect['_id']; ?>', '<?php echo htmlspecialchars($redirect['old_brand_key'] ?? ''); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Fin del panel de redirecciones -->

    </div>

    <!-- Modal para crear marca -->
    <div class="modal fade" id="modalCrearMarca" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nueva Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_marca">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la Marca *</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria" class="form-select" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL Principal</label>
                                <input type="url" class="form-control" name="url" placeholder="https://...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL de Registro</label>
                                <input type="url" class="form-control" name="url_register" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Marca</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar marca -->
    <div class="modal fade" id="modalEditarMarca" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_marca">
                        <input type="hidden" name="marca_id" id="edit_marca_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la Marca *</label>
                                <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria" class="form-select" id="edit_categoria" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <div class="input-group">
                                <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="3"></textarea>
                                <button type="button" class="btn btn-outline-primary" id="btn_generar_descripcion_ia" title="Generar descripción con IA">
                                    <i class="fas fa-robot"></i> IA
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL Principal</label>
                                <input type="url" class="form-control" name="url" id="edit_url" placeholder="https://...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL de Registro</label>
                                <input type="url" class="form-control" name="url_register" id="edit_url_register" placeholder="https://...">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL de Imagen</label>
                            <div class="input-group">
                                <input type="url" class="form-control" name="imagen" id="edit_imagen" placeholder="https://...">
                                <button type="button" class="btn btn-outline-primary" id="btn_buscar_imagenes_ia" title="Buscar imágenes con IA">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Video (Código Embed)</label>
                            <textarea class="form-control" name="video" id="edit_video" rows="4" placeholder="Pega aquí el código embed del video (iframe de YouTube, Vimeo, etc.)"></textarea>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Puedes pegar el código completo del iframe o embed del video.
                            </small>
                        </div>

                        <!-- SEO de Contenido -->
                        <hr class="my-3">
                        <h6 class="mb-2"><i class="fas fa-search text-primary"></i> Contenido SEO de la marca</h6>
                        <div class="mb-3">
                            <label class="form-label">¿Qué es la marca? (SEO)</label>
                            <textarea class="form-control" name="seo_que_es" id="edit_seo_que_es" rows="3" placeholder="Describe qué es la marca y cómo funciona"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cómo usar códigos / paso a paso</label>
                            <textarea class="form-control" name="seo_como_usar" id="edit_seo_como_usar" rows="3" placeholder="Explica los pasos para usar códigos de la marca"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Consejos / Tips para ahorrar</label>
                            <textarea class="form-control" name="seo_tips" id="edit_seo_tips" rows="3" placeholder="Consejos para maximizar cashback o ahorro"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">FAQ (formato: pregunta|respuesta por línea)</label>
                            <textarea class="form-control" name="seo_faq" id="edit_seo_faq" rows="4" placeholder="Ejemplo:\n¿Los códigos caducan?|Sí, suelen tener fecha límite\n¿Puedo usar más de un código?|Normalmente no, uno por cuenta"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Última actualización (manual)</label>
                                <input type="date" class="form-control" name="ultima_actualizacion_manual" id="edit_ultima_actualizacion_manual">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Validez del Offer/Coupon (validThrough)</label>
                                <input type="date" class="form-control" name="offer_valid_through" id="edit_offer_valid_through">
                            </div>
                        </div>
                        
                        <!-- Sección de Marca Destacada -->
                        <hr class="my-4">
                        <h6 class="mb-3"><i class="fas fa-star text-warning"></i> Configuración de Marca Destacada</h6>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="destacada_home" id="edit_destacada_home" value="1">
                                <label class="form-check-label" for="edit_destacada_home">
                                    <strong>Marcar como destacada en la home</strong>
                                    <small class="d-block text-muted">Esta marca aparecerá en la sección de marcas destacadas de la página principal</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ventajas Principales (3 ventajas que se mostrarán en la home)</label>
                            <small class="d-block text-muted mb-2">Estas ventajas se mostrarán como bullets en la tarjeta de la marca destacada</small>
                            
                            <div class="mb-2">
                                <label class="form-label small">Ventaja 1</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="ventaja_1" id="edit_ventaja_1" placeholder="Ej: Descuentos exclusivos para nuevos usuarios">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn_generar_ventaja_1_ia" title="Generar ventaja 1 con IA">
                                        <i class="fas fa-robot"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small">Ventaja 2</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="ventaja_2" id="edit_ventaja_2" placeholder="Ej: Envío gratis en pedidos superiores a 50€">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn_generar_ventaja_2_ia" title="Generar ventaja 2 con IA">
                                        <i class="fas fa-robot"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small">Ventaja 3</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="ventaja_3" id="edit_ventaja_3" placeholder="Ej: Ofertas exclusivas solo para nuevos usuarios">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn_generar_ventaja_3_ia" title="Generar ventaja 3 con IA">
                                        <i class="fas fa-robot"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Si dejas las ventajas vacías, se generarán automáticamente basándose en la categoría y descripción de la marca.
                            </small>
                        </div>
                        
                        <!-- Beneficio Oficial -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-shield-alt text-success"></i> Beneficio Oficial de la Marca</label>
                            <small class="d-block text-muted mb-2">Si se define, los usuarios NO podrán publicar códigos con un beneficio superior a esta cantidad.</small>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label small">Cantidad máxima</label>
                                    <input type="number" class="form-control" name="beneficio_oficial_cantidad" id="edit_bo_cantidad" placeholder="Ej: 50" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Tipo</label>
                                    <select class="form-select" name="beneficio_oficial_tipo" id="edit_bo_tipo">
                                        <option value="euros">€ Euros</option>
                                        <option value="porcentaje">% Porcentaje</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small">Texto promocional</label>
                                    <input type="text" class="form-control" name="beneficio_oficial_texto" id="edit_bo_texto" placeholder="Ej: 50€ para ti y tu amigo">
                                </div>
                            </div>
                            <small class="text-warning mt-1 d-block">
                                <i class="fas fa-exclamation-triangle"></i> Déjalo vacío si no quieres limitar el beneficio para esta marca.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Marca</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para buscar y fusionar marcas -->
    <div class="modal fade" id="modalFusionar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fusionar Marcas Duplicadas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                    <div class="modal-body">
                    <!-- Paso 1: Selección inicial -->
                    <div id="fusion-step-1">
                        <input type="hidden" name="marca_origen_id" id="fusion_origen_id">
                        <div class="mb-3">
                            <label class="form-label">Marca Origen (se eliminará)</label>
                            <input type="text" class="form-control bg-light" id="fusion_origen_nombre" readonly>
                            <small class="text-muted">Esta marca duplicada será eliminada permanentemente</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Buscar Marca Destino</label>
                            <input type="text" class="form-control brand-search-input" id="search_marca_destino" placeholder="Escribe el nombre de la marca..." autocomplete="off">
                            <small class="text-muted">Busca la marca correcta que recibirá todos los códigos</small>
                        </div>

                        <div id="search-results" class="mb-3" style="display: none;">
                            <label class="form-label">Resultados de búsqueda:</label>
                            <div class="search-results-container">
                                <div class="list-group" id="search-results-list"></div>
                        </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-search me-2"></i>
                            <strong>Paso 1:</strong> Busca y selecciona la marca destino escribiendo su nombre arriba.
        </div>
    </div>

                    <!-- Paso 2: Confirmación (oculto inicialmente) -->
                    <div id="fusion-step-2" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Marca Origen (se eliminará)</label>
                            <input type="text" class="form-control bg-light" id="confirm_origen_nombre" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Marca Destino (recibirá los códigos)</label>
                            <input type="text" class="form-control bg-light" id="confirm_destino_nombre" readonly>
                            <input type="hidden" name="marca_destino_id" id="confirm_destino_id">
                        </div>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Confirmación:</strong> Se creará automáticamente una redirección 301 para preservar el SEO.
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Advertencia:</strong> Esta acción moverá todos los códigos de la marca origen a la marca destino y eliminará la marca origen. Esta acción no se puede deshacer.
                        </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-siguiente-paso" onclick="siguientePasoFusion()">Siguiente</button>
                    <button type="submit" class="btn btn-warning" id="btn-fusionar" style="display: none;" form="fusion-form">Fusionar Marcas</button>
                    </div>
                <form method="POST" id="fusion-form" style="display: none;">
                    <input type="hidden" name="action" value="fusionar_marcas">
                    <input type="hidden" name="marca_origen_id" id="form_origen_id">
                    <input type="hidden" name="marca_destino_id" id="form_destino_id">
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Modal de editar marca
        document.getElementById('modalEditarMarca').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('edit_marca_id').value = button.getAttribute('data-marca-id');
            document.getElementById('edit_nombre').value = button.getAttribute('data-marca-nombre');
            document.getElementById('edit_categoria').value = button.getAttribute('data-marca-categoria');
            document.getElementById('edit_descripcion').value = button.getAttribute('data-marca-descripcion');
            document.getElementById('edit_url').value = button.getAttribute('data-marca-url');
            document.getElementById('edit_url_register').value = button.getAttribute('data-marca-url-register');
            document.getElementById('edit_imagen').value = button.getAttribute('data-marca-imagen');
            document.getElementById('edit_video').value = button.getAttribute('data-marca-video') || '';
            document.getElementById('edit_seo_que_es').value = button.getAttribute('data-marca-seo-que-es') || '';
            document.getElementById('edit_seo_como_usar').value = button.getAttribute('data-marca-seo-como-usar') || '';
            document.getElementById('edit_seo_tips').value = button.getAttribute('data-marca-seo-tips') || '';
            document.getElementById('edit_seo_faq').value = button.getAttribute('data-marca-seo-faq') || '';
            document.getElementById('edit_offer_valid_through').value = button.getAttribute('data-marca-offer-valid') || '';
            document.getElementById('edit_ultima_actualizacion_manual').value = button.getAttribute('data-marca-ultima-actualizacion') || '';
            
            // Campos de marca destacada
            var destacada = button.getAttribute('data-marca-destacada') === '1';
            document.getElementById('edit_destacada_home').checked = destacada;
            
            // Ventajas principales
            document.getElementById('edit_ventaja_1').value = button.getAttribute('data-marca-ventaja-1') || '';
            document.getElementById('edit_ventaja_2').value = button.getAttribute('data-marca-ventaja-2') || '';
            document.getElementById('edit_ventaja_3').value = button.getAttribute('data-marca-ventaja-3') || '';
            
            // Beneficio oficial
            document.getElementById('edit_bo_cantidad').value = button.getAttribute('data-marca-bo-cantidad') || '';
            document.getElementById('edit_bo_tipo').value = button.getAttribute('data-marca-bo-tipo') || 'euros';
            document.getElementById('edit_bo_texto').value = button.getAttribute('data-marca-bo-texto') || '';
        });

        // Modal de fusionar
        document.getElementById('modalFusionar').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var marcaId = button.getAttribute('data-marca-id');
            var marcaNombre = button.getAttribute('data-marca-nombre');

            // Resetear el modal al estado inicial
            resetFusionModal();

            document.getElementById('fusion_origen_id').value = marcaId;
            document.getElementById('fusion_origen_nombre').value = marcaNombre;
            document.getElementById('confirm_origen_nombre').value = marcaNombre;

            // Configurar búsqueda
            configurarBusquedaMarcas();
        });

        // Función para resetear el modal
        function resetFusionModal() {
            document.getElementById('fusion-step-1').style.display = 'block';
            document.getElementById('fusion-step-2').style.display = 'none';
            document.getElementById('btn-siguiente-paso').style.display = 'inline-block';
            document.getElementById('btn-fusionar').style.display = 'none';

            // Limpiar campos
            document.getElementById('search_marca_destino').value = '';
            document.getElementById('search-results').style.display = 'none';
            document.getElementById('confirm_destino_nombre').value = '';
            document.getElementById('confirm_destino_id').value = '';
        }

        // Función para configurar la búsqueda de marcas
        function configurarBusquedaMarcas() {
            var searchInput = document.getElementById('search_marca_destino');
            var searchResults = document.getElementById('search-results');
            var searchResultsList = document.getElementById('search-results-list');

            var marcasData = <?php
                $todas_marcas = $collection_marcas->find([], ['sort' => ['nombre' => 1]])->toArray();
                $marcas_array = [];
                foreach ($todas_marcas as $marca) {
                    $marcas_array[] = [
                        'id' => (string)$marca['_id'],
                        'nombre' => trim($marca['nombre']),
                        'nombre_clave' => $marca['nombre_clave'] ?? strtolower(trim($marca['nombre']))
                    ];
                }
                echo json_encode($marcas_array);
            ?>;

            searchInput.addEventListener('input', function() {
                var query = this.value.toLowerCase().trim();

                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                var matches = marcasData.filter(function(marca) {
                    return marca.nombre.toLowerCase().includes(query) ||
                           marca.nombre_clave.toLowerCase().includes(query);
                });

                if (matches.length > 0) {
                    searchResultsList.innerHTML = '';

                    matches.forEach(function(marca) {
                        var item = document.createElement('div');
                        item.className = 'list-group-item list-group-item-action';
                        item.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${marca.nombre}</strong>
                                    <br><small class="text-muted">Clave: ${marca.nombre_clave}</small>
                                </div>
                                <button class="btn btn-sm btn-primary" onclick="seleccionarMarcaDestino('${marca.id}', '${marca.nombre.replace(/'/g, "\\'")}')">
                                    Seleccionar
                                </button>
                            </div>
                        `;
                        searchResultsList.appendChild(item);
                    });

                    searchResults.style.display = 'block';
                } else {
                    searchResults.style.display = 'none';
                }
            });
        }

        // Función para seleccionar marca destino
        function seleccionarMarcaDestino(marcaId, marcaNombre) {
            document.getElementById('confirm_destino_id').value = marcaId;
            document.getElementById('confirm_destino_nombre').value = marcaNombre;
            document.getElementById('search-results').style.display = 'none';
            document.getElementById('search_marca_destino').value = marcaNombre;
        }

        // Función para avanzar al siguiente paso
        function siguientePasoFusion() {
            var destinoId = document.getElementById('confirm_destino_id').value;
            var destinoNombre = document.getElementById('confirm_destino_nombre').value;

            if (!destinoId || !destinoNombre) {
                alert('Por favor, selecciona una marca destino válida.');
                return;
            }

            // Cambiar a la vista de confirmación
            document.getElementById('fusion-step-1').style.display = 'none';
            document.getElementById('fusion-step-2').style.display = 'block';
            document.getElementById('btn-siguiente-paso').style.display = 'none';
            document.getElementById('btn-fusionar').style.display = 'inline-block';

            // Preparar formulario oculto
            document.getElementById('form_origen_id').value = document.getElementById('fusion_origen_id').value;
            document.getElementById('form_destino_id').value = destinoId;
        }

        // Función para manejar el envío del formulario de fusión
        document.getElementById('fusion-form').addEventListener('submit', function(e) {
            // Mostrar indicador de carga en el botón
            var submitBtn = document.getElementById('btn-fusionar');
            var originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Fusionando...';
            submitBtn.disabled = true;

            // El formulario se enviará normalmente después de esto
        });

        // Función para cerrar modal automáticamente después de fusión exitosa
        function checkFusionSuccess() {
            <?php if (isset($_SESSION['success_message']) && (strpos($_SESSION['success_message'], 'Fusión completada') !== false || strpos($_SESSION['success_message'], 'Fusión Masiva') !== false)): ?>
                // Si hay un mensaje de éxito de fusión, cerrar el modal después de 3 segundos
                setTimeout(function() {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalFusionar'));
                    if (modal) {
                        modal.hide();
                    }
                }, 3000);
            <?php endif; ?>
        }

        // Función para seleccionar/deseleccionar todo un grupo
        function toggleGrupoSeleccion(checkbox, grupoId) {
            var grupoCheckboxes = document.querySelectorAll('.marca-checkbox[data-grupo="' + grupoId + '"]');
            grupoCheckboxes.forEach(function(cb) {
                cb.checked = checkbox.checked;
            });
            actualizarSeleccionGrupo(grupoId);
        }

        // Función para actualizar la selección de un grupo
        function actualizarSeleccionGrupo(grupoId) {
            var grupoCheckbox = document.getElementById('grupo-' + grupoId);
            var marcaCheckboxes = document.querySelectorAll('.marca-checkbox[data-grupo="' + grupoId + '"]:checked');
            var marcaDestinoSelect = document.getElementById('destino-' + grupoId);
            var btnFusionar = document.getElementById('btn-fusionar-' + grupoId);

            // Si hay marcas seleccionadas y una marca destino, mostrar botón de fusión
            if (marcaCheckboxes.length > 0 && marcaDestinoSelect.value) {
                btnFusionar.style.display = 'inline-block';
            } else {
                btnFusionar.style.display = 'none';
            }

            // Actualizar estado del checkbox del grupo
            var totalMarcas = document.querySelectorAll('.marca-checkbox[data-grupo="' + grupoId + '"]').length;
            grupoCheckbox.checked = marcaCheckboxes.length === totalMarcas && totalMarcas > 0;
            grupoCheckbox.indeterminate = marcaCheckboxes.length > 0 && marcaCheckboxes.length < totalMarcas;
        }

        // Función para actualizar marca destino
        function actualizarMarcaDestino(select) {
            var grupoId = select.getAttribute('data-grupo');
            actualizarSeleccionGrupo(grupoId);
        }

        // Función para fusionar un grupo específico
        function fusionarGrupo(grupoId) {
            var marcaDestinoSelect = document.getElementById('destino-' + grupoId);
            var marcaDestinoId = marcaDestinoSelect.value;
            var marcaDestinoNombre = marcaDestinoSelect.options[marcaDestinoSelect.selectedIndex].text;

            if (!marcaDestinoId) {
                alert('Por favor, selecciona una marca destino.');
                return;
            }

            var marcasSeleccionadas = [];
            var marcaCheckboxes = document.querySelectorAll('.marca-checkbox[data-grupo="' + grupoId + '"]:checked');

            if (marcaCheckboxes.length === 0) {
                alert('Por favor, selecciona al menos una marca para fusionar.');
                return;
            }

            marcaCheckboxes.forEach(function(cb) {
                marcasSeleccionadas.push(cb.getAttribute('data-marca-id'));
            });

            if (confirm('¿Estás seguro de fusionar ' + marcasSeleccionadas.length + ' marcas hacia "' + marcaDestinoNombre + '"?\n\nEsta acción no se puede deshacer.\n\n✅ Se abrirá en una nueva ventana para continuar trabajando.')) {
                // Abrir en nueva ventana
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_marcas.php';
                form.target = '_blank'; // Abrir en nueva ventana
                form.innerHTML = `
                    <input type="hidden" name="action" value="fusionar_masiva">
                    <input type="hidden" name="marcas_origen_ids[]" value="${marcasSeleccionadas.join('"><input type="hidden" name="marcas_origen_ids[]" value="')}">
                    <input type="hidden" name="marca_destino_id" value="${marcaDestinoId}">
                `;
                document.body.appendChild(form);
                form.submit();

                // Mostrar indicador visual de que se abrió nueva ventana
                var btnFusionar = document.getElementById('btn-fusionar-' + grupoId);
                btnFusionar.innerHTML = '<i class="fas fa-check me-1"></i>Procesando...';
                btnFusionar.disabled = true;
            }
        }

        // Función para fusionar todas las seleccionadas globalmente
        function fusionarMasivaGlobal() {
            var marcaDestinoGlobal = document.getElementById('marca-destino-global').value;
            var marcaDestinoNombre = document.getElementById('marca-destino-global').options[document.getElementById('marca-destino-global').selectedIndex].text;

            if (!marcaDestinoGlobal) {
                alert('Por favor, selecciona una marca destino global.');
                return;
            }

            var marcasSeleccionadas = [];
            var marcaCheckboxes = document.querySelectorAll('.marca-checkbox:checked');

            if (marcaCheckboxes.length === 0) {
                alert('Por favor, selecciona al menos una marca para fusionar.');
                return;
            }

            marcaCheckboxes.forEach(function(cb) {
                marcasSeleccionadas.push(cb.getAttribute('data-marca-id'));
            });

            if (confirm('¡FUSIÓN MASIVA!\n\n¿Estás seguro de fusionar ' + marcasSeleccionadas.length + ' marcas de diferentes grupos hacia "' + marcaDestinoNombre + '"?\n\nEsta acción masiva no se puede deshacer.\n\n✅ Se abrirá en una nueva ventana para continuar trabajando.')) {
                // Abrir en nueva ventana
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_marcas.php';
                form.target = '_blank'; // Abrir en nueva ventana
                form.innerHTML = `
                    <input type="hidden" name="action" value="fusionar_masiva">
                    <input type="hidden" name="marcas_origen_ids[]" value="${marcasSeleccionadas.join('"><input type="hidden" name="marcas_origen_ids[]" value="')}">
                    <input type="hidden" name="marca_destino_id" value="${marcaDestinoGlobal}">
                `;
                document.body.appendChild(form);
                form.submit();

                // Mostrar indicador visual
                var btnFusionMasiva = document.getElementById('btn-fusion-masiva');
                btnFusionMasiva.innerHTML = '<i class="fas fa-check me-2"></i>Procesando...';
                btnFusionMasiva.disabled = true;
            }
        }

        // Función para mostrar/ocultar botón de fusión masiva global
        function actualizarBotonFusionMasiva() {
            var marcaDestinoGlobal = document.getElementById('marca-destino-global').value;
            var marcasSeleccionadas = document.querySelectorAll('.marca-checkbox:checked');
            var btnFusionMasiva = document.getElementById('btn-fusion-masiva');

            if (marcaDestinoGlobal && marcasSeleccionadas.length > 0) {
                btnFusionMasiva.style.display = 'inline-block';
            } else {
                btnFusionMasiva.style.display = 'none';
            }
        }

        // Función para mostrar todas las marcas seleccionadas
        function fusionarSeleccionadas() {
            var marcasSeleccionadas = [];
            var marcaCheckboxes = document.querySelectorAll('.marca-checkbox:checked');

            marcaCheckboxes.forEach(function(cb) {
                marcasSeleccionadas.push(cb.getAttribute('data-marca-nombre'));
            });

            if (marcasSeleccionadas.length === 0) {
                alert('No hay marcas seleccionadas.');
                return;
            }

            alert('Marcas seleccionadas para fusión:\n\n' + marcasSeleccionadas.join('\n') + '\n\nSelecciona una marca destino en cada grupo para proceder con la fusión.');
        }

        // Eventos para actualizar la interfaz
        document.addEventListener('DOMContentLoaded', function() {
            checkFusionSuccess();

            // Actualizar botones cuando cambie la selección
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('marca-checkbox')) {
                    var grupoId = e.target.getAttribute('data-grupo');
                    actualizarSeleccionGrupo(grupoId);
                }
                if (e.target.id === 'marca-destino-global') {
                    actualizarBotonFusionMasiva();
                }
            });

            // Inicializar estado de los grupos
            var gruposIds = <?php echo json_encode(array_column($marcas_duplicadas, 'grupo_id')); ?>;
            gruposIds.forEach(function(grupoId) {
                actualizarSeleccionGrupo(grupoId);
            });
        });

        // Toggle estado
        function toggleEstado(marcaId, currentEstado) {
            if (confirm('¿Estás seguro de cambiar el estado de esta marca?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_estado">
                    <input type="hidden" name="marca_id" value="${marcaId}">
                    <input type="hidden" name="nuevo_estado" value="${currentEstado == 1 ? 0 : 1}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Eliminar marca
        function eliminarMarca(marcaId, marcaNombre, codigosCount) {
            if (codigosCount > 0) {
                alert(`No se puede eliminar la marca "${marcaNombre}" porque tiene ${codigosCount} códigos asociados.`);
                return;
            }

            if (confirm(`¿Estás seguro de eliminar la marca "${marcaNombre}"? Esta acción no se puede deshacer.`)) {
                // Crear FormData para enviar por AJAX
                var formData = new FormData();
                formData.append('action', 'delete_marca');
                formData.append('marca_id', marcaId);

                // Enviar petición AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remover la fila del DOM
                        var rowElement = document.getElementById('marca-row-' + marcaId);
                        if (rowElement) {
                            // Agregar efecto de fade out antes de eliminar
                            rowElement.style.transition = 'opacity 0.3s';
                            rowElement.style.opacity = '0';
                            setTimeout(function() {
                                rowElement.remove();
                                // Mostrar mensaje de éxito
                                alert(data.message);
                            }, 300);
                        } else {
                            alert(data.message);
                            location.reload();
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la marca. Por favor, recarga la página.');
                });
            }
        }

        // Gestionar redirecciones
        function toggleRedirect(redirectId) {
            if (confirm('¿Estás seguro de cambiar el estado de esta redirección?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_redirect">
                    <input type="hidden" name="redirect_id" value="${redirectId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteRedirect(redirectId, oldBrandKey) {
            if (confirm(`¿Estás seguro de eliminar la redirección de /de-${oldBrandKey}? Esta acción no se puede deshacer.`)) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_redirect">
                    <input type="hidden" name="redirect_id" value="${redirectId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Funciones para IA de marcas
        function mostrarLoading(btn, texto = 'Generando...') {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + texto;
        }

        function ocultarLoading(btn, textoOriginal) {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        }

        function mostrarError(mensaje) {
            alert('Error: ' + mensaje);
        }

        function mostrarExito(mensaje) {
            // Podríamos usar un toast aquí, por ahora usamos alert
            console.log('Éxito: ' + mensaje);
        }

        // Botón generar descripción
        document.getElementById('btn_generar_descripcion_ia')?.addEventListener('click', function() {
            var btn = this;
            var nombre = document.getElementById('edit_nombre').value;
            var categoria = document.getElementById('edit_categoria').value;
            var textarea = document.getElementById('edit_descripcion');
            var textoOriginal = btn.innerHTML;

            if (!nombre) {
                alert('Por favor, ingresa el nombre de la marca primero');
                return;
            }

            mostrarLoading(btn, 'Generando...');

            $.ajax({
                url: 'admin_marcas.php',
                method: 'POST',
                data: {
                    action: 'generar_descripcion_ia',
                    nombre_marca: nombre,
                    categoria: categoria
                },
                dataType: 'json',
                success: function(response) {
                    ocultarLoading(btn, textoOriginal);
                    if (response.success) {
                        textarea.value = response.descripcion || '';
                        document.getElementById('edit_descripcion').value = response.descripcion || '';
                        document.getElementById('edit_video').value = response.video || '';
                        document.getElementById('edit_imagen').value = response.imagen || document.getElementById('edit_imagen').value;
                        document.getElementById('edit_ventaja_1').value = response.ventaja_1 || '';
                        document.getElementById('edit_ventaja_2').value = response.ventaja_2 || '';
                        document.getElementById('edit_ventaja_3').value = response.ventaja_3 || '';
                        document.getElementById('edit_seo_que_es').value = response.seo_que_es || '';
                        document.getElementById('edit_seo_como_usar').value = response.seo_como_usar || '';
                        document.getElementById('edit_seo_tips').value = response.seo_tips || '';
                        document.getElementById('edit_seo_faq').value = response.seo_faq || '';
                        mostrarExito('Contenido generado con IA');
                    } else {
                        mostrarError(response.error || 'Error al generar descripción');
                    }
                },
                error: function() {
                    ocultarLoading(btn, textoOriginal);
                    mostrarError('Error de conexión');
                }
            });
        });

        // Botón buscar imágenes
        document.getElementById('btn_buscar_imagenes_ia')?.addEventListener('click', function() {
            var btn = this;
            var nombre = document.getElementById('edit_nombre').value;
            var textoOriginal = btn.innerHTML;

            if (!nombre) {
                alert('Por favor, ingresa el nombre de la marca primero');
                return;
            }

            mostrarLoading(btn, 'Buscando...');

            $.ajax({
                url: 'admin_marcas.php',
                method: 'POST',
                data: {
                    action: 'buscar_imagenes_ia',
                    nombre_marca: nombre
                },
                dataType: 'json',
                success: function(response) {
                    ocultarLoading(btn, textoOriginal);
                    if (response.success && response.imagenes && response.imagenes.length > 0) {
                        mostrarSelectorImagenes(response.imagenes);
                    } else {
                        mostrarError(response.error || 'No se encontraron imágenes');
                    }
                },
                error: function() {
                    ocultarLoading(btn, textoOriginal);
                    mostrarError('Error de conexión');
                }
            });
        });

        // Función para mostrar selector de imágenes
        function mostrarSelectorImagenes(imagenes) {
            var modalHtml = '<div class="modal fade" id="modalImagenes" tabindex="-1">' +
                '<div class="modal-dialog modal-lg">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title">Seleccionar Imagen</h5>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                '</div>' +
                '<div class="modal-body">' +
                '<div class="row" id="imagenes-container">';

            imagenes.forEach(function(img, index) {
                modalHtml += '<div class="col-md-4 mb-3">' +
                    '<div class="card imagen-selector" data-url="' + img.url + '" style="cursor: pointer;">' +
                    '<img src="' + (img.thumbnail || img.url) + '" class="card-img-top" style="height: 150px; object-fit: contain;">' +
                    '<div class="card-body p-2">' +
                    '<small class="text-muted">' + (img.title || 'Imagen ' + (index + 1)) + '</small>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            });

            modalHtml += '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';

            // Remover modal anterior si existe
            $('#modalImagenes').remove();
            
            // Agregar nuevo modal
            $('body').append(modalHtml);
            
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalImagenes'));
            modal.show();

            // Event listeners para seleccionar imagen
            $(document).off('click', '.imagen-selector').on('click', '.imagen-selector', function() {
                var url = $(this).data('url');
                document.getElementById('edit_imagen').value = url;
                modal.hide();
                $('#modalImagenes').remove();
            });
        }

        // Botones generar ventajas
        for (var i = 1; i <= 3; i++) {
            (function(numero) {
                var btnId = 'btn_generar_ventaja_' + numero + '_ia';
                var btn = document.getElementById(btnId);
                if (btn) {
                    btn.addEventListener('click', function() {
                        var btn = this;
                        var nombre = document.getElementById('edit_nombre').value;
                        var categoria = document.getElementById('edit_categoria').value;
                        var input = document.getElementById('edit_ventaja_' + numero);
                        var textoOriginal = btn.innerHTML;

                        if (!nombre) {
                            alert('Por favor, ingresa el nombre de la marca primero');
                            return;
                        }

                        mostrarLoading(btn, 'Generando...');

                        $.ajax({
                            url: 'admin_marcas.php',
                            method: 'POST',
                            data: {
                                action: 'generar_ventaja_ia',
                                nombre_marca: nombre,
                                categoria: categoria,
                                numero_ventaja: numero
                            },
                            dataType: 'json',
                            success: function(response) {
                                ocultarLoading(btn, textoOriginal);
                                if (response.success) {
                                    input.value = response.ventaja;
                                    mostrarExito('Ventaja ' + numero + ' generada correctamente');
                                } else {
                                    mostrarError(response.error || 'Error al generar ventaja');
                                }
                            },
                            error: function() {
                                ocultarLoading(btn, textoOriginal);
                                mostrarError('Error de conexión');
                            }
                        });
                    });
                }
            })(i);
        }

        function cleanupRedirects() {
            if (confirm('¿Estás seguro de limpiar las redirecciones huérfanas? Se desactivarán las redirecciones que apuntan a marcas inexistentes.')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cleanup_redirects">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Función para filtrar marcas en tiempo real
        function filtrarMarcas(input, selectId) {
            var filter = input.value.toUpperCase();
            var select = document.getElementById(selectId);
            var options = select.getElementsByTagName('option');

            // Siempre mostrar la primera opción (Seleccionar marca destino...)
            for (var i = 1; i < options.length; i++) {
                var txtValue = options[i].textContent || options[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    options[i].style.display = "";
                } else {
                    options[i].style.display = "none";
                }
            }
        }
    </script>


    <!-- Amazon Link Auto-Repair Section -->
    <div class="container-fluid mb-5">
        <div class="row">
            <div class="col-12" id="amazon-repair-container">
                <!-- Se poblará vía amazon_repair.js -->
            </div>
        </div>
    </div>

    <script src="/public/js/amazon_repair.js"></script>

<?php get_footer(); ?>
</body>
</html>


