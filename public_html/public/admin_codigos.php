<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
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

$collection_codigos = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();
$collection_marcas = getCollectionMarcas();

// Procesar acciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_codigo':
            $codigo_id = $_POST['codigo_id'];
            $nuevo_codigo = trim($_POST['codigo']);
            $descripcion = trim($_POST['descripcion']);
            $marca = trim($_POST['marca']);
            $estado = (int)$_POST['estado'];
            $destacado = (int)$_POST['destacado'];
            $destacado_social = (int)$_POST['destacado_social'];
            
            $update_data = [
                'codigo' => $nuevo_codigo,
                'descripcion' => $descripcion,
                'marca' => strtolower($marca),
                'estado' => $estado,
                'destacado' => $destacado,
                'destacado_social' => $destacado_social,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $result = $collection_codigos->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                ['$set' => $update_data]
            );
            
            if ($result->getModifiedCount() > 0) {
                $_SESSION['success_message'] = "Código actualizado correctamente";
            } else {
                $_SESSION['error_message'] = "No se realizaron cambios en el código";
            }
            break;
            
        case 'toggle_estado':
            $codigo_id = $_POST['codigo_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $collection_codigos->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                ['$set' => [
                    'estado' => (int)$nuevo_estado,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            $_SESSION['success_message'] = "Estado del código actualizado";
            break;
            
        case 'toggle_destacado':
            $codigo_id = $_POST['codigo_id'];
            $nuevo_destacado = $_POST['nuevo_destacado'];
            
            // Si se está activando el destacado, usar timestamp, si no, 0
            $valor_destacado = $nuevo_destacado == 1 ? strtotime('now') : 0;
            
            $result = $collection_codigos->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                ['$set' => [
                    'destacado' => $valor_destacado,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            if ($result->getModifiedCount() > 0) {
                $_SESSION['success_message'] = "Estado destacado actualizado correctamente";
            } else {
                $_SESSION['error_message'] = "No se pudo actualizar el estado destacado";
            }
            break;
            
        case 'toggle_destacado_premium':
            $codigo_id = $_POST['codigo_id'];
            $nuevo_destacado_premium = $_POST['nuevo_destacado_premium'];
            
            // Si se está activando el destacado, usar timestamp, si no, 0
            $valor_destacado_social = $nuevo_destacado_premium == 1 ? strtotime('now') : 0;
            
            $result = $collection_codigos->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                ['$set' => [
                    'destacado_social' => $valor_destacado_social,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            if ($result->getModifiedCount() > 0) {
                $_SESSION['success_message'] = "Estado destacado premium actualizado correctamente";
            } else {
                $_SESSION['error_message'] = "No se pudo actualizar el estado destacado premium";
            }
            break;
            
        case 'editar_fecha_destacado':
            $codigo_id = $_POST['codigo_id'];
            $tipo_destacado = $_POST['tipo_destacado'];
            $nueva_fecha = $_POST['nueva_fecha'];
            
            // Convertir fecha a timestamp
            $timestamp = strtotime($nueva_fecha);
            
            if ($timestamp === false) {
                $_SESSION['error_message'] = "Fecha inválida";
                break;
            }
            
            // Actualizar según el tipo de destacado
            $update_data = [
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            if ($tipo_destacado == 'normal') {
                $update_data['destacado'] = $timestamp;
            } elseif ($tipo_destacado == 'premium') {
                $update_data['destacado_social'] = $timestamp;
            } elseif ($tipo_destacado == 'ambos') {
                $update_data['destacado'] = $timestamp;
                $update_data['destacado_social'] = $timestamp;
            }
            
            $result = $collection_codigos->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                ['$set' => $update_data]
            );
            
            if ($result->getModifiedCount() > 0) {
                $_SESSION['success_message'] = "Fecha de destacado actualizada correctamente";
            } else {
                $_SESSION['error_message'] = "No se pudo actualizar la fecha de destacado";
            }
            break;
            
        case 'delete_codigo':
            $codigo_id = $_POST['codigo_id'];
            
            $result = $collection_codigos->deleteOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
            if ($result->getDeletedCount() > 0) {
                $_SESSION['success_message'] = "Código eliminado correctamente";
            } else {
                $_SESSION['error_message'] = "Error al eliminar el código";
            }
            break;
            
        case 'bulk_action':
            $codigo_ids = $_POST['codigo_ids'] ?? [];
            $bulk_action = $_POST['bulk_action'];
            
            if (empty($codigo_ids)) {
                $_SESSION['error_message'] = "No se seleccionaron códigos";
            } else {
                $object_ids = array_map(function($id) {
                    return new MongoDB\BSON\ObjectId($id);
                }, $codigo_ids);
                
                switch ($bulk_action) {
                    case 'activate':
                        $result = $collection_codigos->updateMany(
                            ['_id' => ['$in' => $object_ids]],
                            ['$set' => [
                                'estado' => 0,
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]]
                        );
                        $_SESSION['success_message'] = "Se activaron {$result->getModifiedCount()} códigos";
                        break;
                        
                    case 'deactivate':
                        $result = $collection_codigos->updateMany(
                            ['_id' => ['$in' => $object_ids]],
                            ['$set' => [
                                'estado' => -1,
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]]
                        );
                        $_SESSION['success_message'] = "Se desactivaron {$result->getModifiedCount()} códigos";
                        break;
                        
                    case 'highlight':
                        $result = $collection_codigos->updateMany(
                            ['_id' => ['$in' => $object_ids]],
                            ['$set' => [
                                'destacado' => 1,
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]]
                        );
                        $_SESSION['success_message'] = "Se destacaron {$result->getModifiedCount()} códigos";
                        break;
                        
                    case 'unhighlight':
                        $result = $collection_codigos->updateMany(
                            ['_id' => ['$in' => $object_ids]],
                            ['$set' => [
                                'destacado' => 0,
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]]
                        );
                        $_SESSION['success_message'] = "Se quitaron de destacados {$result->getModifiedCount()} códigos";
                        break;
                        
                    case 'highlight_premium':
                        $result = $collection_codigos->updateMany(
                            ['_id' => ['$in' => $object_ids]],
                            ['$set' => [
                                'destacado_social' => 1,
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]]
                        );
                        $_SESSION['success_message'] = "Se destacaron premium {$result->getModifiedCount()} códigos";
                        break;
                        
                    case 'unhighlight_premium':
                        $result = $collection_codigos->updateMany(
                            ['_id' => ['$in' => $object_ids]],
                            ['$set' => [
                                'destacado_social' => 0,
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]]
                        );
                        $_SESSION['success_message'] = "Se quitaron de destacados premium {$result->getModifiedCount()} códigos";
                        break;
                        
                    case 'delete':
                        $result = $collection_codigos->deleteMany(['_id' => ['$in' => $object_ids]]);
                        $_SESSION['success_message'] = "Se eliminaron {$result->getDeletedCount()} códigos";
                        break;
                }
            }
            break;
    }
    
    // Mantener los parámetros de filtro en la redirección
    $redirect_params = $_GET;
    $redirect_url = 'admin_codigos.php?' . http_build_query($redirect_params);
    header('Location: ' . $redirect_url);
    exit;
}

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_destacado = $_GET['destacado'] ?? '';
$filtro_marca = $_GET['marca'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_id_codigo = $_GET['id_codigo'] ?? '';
$filtro_nuevos_hoy = $_GET['filtro_nuevos_hoy'] ?? '';

// Obtener parámetros de ordenamiento
$sort_field = $_GET['sort'] ?? '_id';
$sort_direction = $_GET['dir'] ?? 'desc';

// Construir filtros para la consulta
$filtros = [];
$filtros_and = [];

if ($filtro_estado !== '') {
    $filtros['estado'] = (int)$filtro_estado;
}
if ($filtro_destacado !== '') {
    $valor_destacado = (int)$filtro_destacado;
    if ($valor_destacado == 1) {
        // Para destacados, incluir tanto destacado > 0 como destacado_social > 0 (premium)
        $filtros_and[] = [
            '$or' => [
                ['destacado' => ['$gt' => 0]],
                ['destacado_social' => ['$gt' => 0]]
            ]
        ];
    } else {
        // Para no destacados, excluir tanto destacado como premium
        // Un código no está destacado si destacado <= 0 Y destacado_social <= 0
        $filtros_and[] = [
            '$and' => [
                ['$or' => [
                    ['destacado' => ['$exists' => false]],
                    ['destacado' => ['$lte' => 0]]
                ]],
                ['$or' => [
                    ['destacado_social' => ['$exists' => false]],
                    ['destacado_social' => ['$lte' => 0]]
                ]]
            ]
        ];
    }
}
if ($filtro_marca) {
    // Convertir a minúsculas para hacer búsqueda exacta
    $filtro_marca_lower = strtolower($filtro_marca);
    $filtros['marca'] = $filtro_marca_lower;
}
if ($filtro_usuario) {
    try {
        $filtros['id_usuario'] = new MongoDB\BSON\ObjectId($filtro_usuario);
    } catch (Exception $e) {
        $filtros['id_usuario'] = $filtro_usuario;
    }
}
if ($filtro_busqueda) {
    $filtros_and[] = [
        '$or' => [
            ['codigo' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
            ['descripcion' => ['$regex' => $filtro_busqueda, '$options' => 'i']]
        ]
    ];
}

// Combinar filtros AND si existen
if (!empty($filtros_and)) {
    $filtros['$and'] = $filtros_and;
}
if ($filtro_id_codigo) {
    // Buscar por ID completo o parcial
    try {
        // Si es un ObjectId válido, buscar por ID exacto
        $objectId = new MongoDB\BSON\ObjectId($filtro_id_codigo);
        $filtros['_id'] = $objectId;
    } catch (Exception $e) {
        // Si no es un ObjectId válido, buscar por ID parcial como string
        $filtros['_id'] = ['$regex' => $filtro_id_codigo, '$options' => 'i'];
    }
}
// Filtro para códigos nuevos de hoy (desde el dashboard)
if ($filtro_nuevos_hoy) {
    $timestamp_hoy_filtro = strtotime('today');
    $objectId_hoy_filtro = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_hoy_filtro, str_repeat('0', 16)));
    if (isset($filtros['_id'])) {
        // Si ya hay un filtro de _id, combinarlo
        if (is_array($filtros['_id']) && isset($filtros['_id']['$gte'])) {
            // Mantener el más restrictivo
            $filtros['_id']['$gte'] = $objectId_hoy_filtro;
        } else {
            $filtros['_id'] = ['$gte' => $objectId_hoy_filtro];
        }
    } else {
        $filtros['_id'] = ['$gte' => $objectId_hoy_filtro];
    }
}

// Obtener códigos con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 100);
$skip = ($page - 1) * $limit;

// Validar límite para evitar valores extremos
$opciones_limit = [10, 20, 50, 100, 200, 500, 1000, 'todos'];
if (!in_array($limit, $opciones_limit) && $limit !== 'todos') {
    $limit = 100;
}

// Si se selecciona "todos", obtener todos los resultados
if ($limit === 'todos') {
    $limit = 999999; // Número muy grande para obtener todos
}

// Construir ordenamiento
$sort_options = [];
$sort_direction_value = ($sort_direction === 'asc') ? 1 : -1;

switch ($sort_field) {
    case 'fecha_publicacion':
        // Ordenar directamente por fecha_publicacion (campo string)
        $sort_options['fecha_publicacion'] = $sort_direction_value;
        break;
    case 'updated_at':
        $sort_options['updated_at'] = $sort_direction_value;
        break;
    case 'codigo':
        $sort_options['codigo'] = $sort_direction_value;
        break;
    case 'marca':
        $sort_options['marca'] = $sort_direction_value;
        break;
    case 'estado':
        $sort_options['estado'] = $sort_direction_value;
        break;
    case 'destacado':
        $sort_options['destacado'] = $sort_direction_value;
        break;
    case 'destacado_social':
        $sort_options['destacado_social'] = $sort_direction_value;
        break;
    case 'vistas':
        $sort_options['totalclicks'] = $sort_direction_value;
        break;
    default:
        $sort_options['_id'] = -1;
        break;
}

// Ordenamiento optimizado: usar índices de MongoDB directamente
$codigos = $collection_codigos->find($filtros, [
    'sort' => $sort_options,
    'skip' => $skip,
    'limit' => $limit
])->toArray();

$total_codigos = $collection_codigos->countDocuments($filtros);
$total_pages = ceil($total_codigos / $limit);

// Obtener marcas para el filtro
$marcas = $collection_marcas->find([], ['sort' => ['nombre' => 1]])->toArray();

// Obtener estadísticas basadas en los filtros aplicados
// Crear filtros para cada tipo de estadística
$filtros_total = $filtros;
$filtros_activos = array_merge($filtros, ['estado' => 0]);
$filtros_inactivos = array_merge($filtros, ['estado' => -1]);

// Para destacados, incluir tanto destacado como premium
// Un código está destacado si tiene destacado > 0 O destacado_social > 0 (timestamp válido)
$filtros_destacados = $filtros;
// Si ya hay filtros $and, agregar el filtro de destacados
if (isset($filtros_destacados['$and'])) {
    $filtros_destacados['$and'][] = [
        '$or' => [
            ['destacado' => ['$gt' => 0]],
            ['destacado_social' => ['$gt' => 0]]
        ]
    ];
} else {
    // Si no hay $and, crear uno nuevo
    $filtros_destacados['$and'] = [
        [
            '$or' => [
                ['destacado' => ['$gt' => 0]],
                ['destacado_social' => ['$gt' => 0]]
            ]
        ]
    ];
}

// Para códigos nuevos hoy, usar timestamp del ObjectId
$timestamp_hoy = strtotime('today');
$objectId_hoy = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_hoy, str_repeat('0', 16)));
$filtros_hoy = array_merge($filtros, ['_id' => ['$gte' => $objectId_hoy]]);

$estadisticas = [
    'total' => $collection_codigos->countDocuments($filtros_total),
    'activos' => $collection_codigos->countDocuments($filtros_activos),
    'inactivos' => $collection_codigos->countDocuments($filtros_inactivos),
    'destacados' => $collection_codigos->countDocuments($filtros_destacados),
    'nuevos_hoy' => $collection_codigos->countDocuments($filtros_hoy),
    'filtrados' => $total_codigos // Este ya está calculado correctamente arriba
];

// Datos para gráfica de códigos publicados por fecha
// Obtener período seleccionado
$periodo_grafica = $_GET['periodo_grafica'] ?? '30dias';
$fecha_inicio_grafica = $_GET['fecha_inicio_grafica'] ?? '';
$fecha_fin_grafica = $_GET['fecha_fin_grafica'] ?? '';

// Calcular fecha límite según el período seleccionado
$objectId_limite = null;
$agrupar_por = 'day'; // Por defecto agrupar por día

try {
    $timestamp_limite = null;
    
    switch ($periodo_grafica) {
        case 'semana':
            $timestamp_limite = time() - (7 * 24 * 60 * 60);
            break;
        case '30dias':
            $timestamp_limite = time() - (30 * 24 * 60 * 60);
            break;
        case '3meses':
            $timestamp_limite = time() - (90 * 24 * 60 * 60);
            $agrupar_por = 'day'; // Mantener por día para 3 meses
            break;
        case 'inicio':
            $timestamp_limite = 0; // Desde el inicio
            $agrupar_por = 'month'; // Agrupar por mes si es desde el inicio
            break;
        case 'personalizado':
            if (!empty($fecha_inicio_grafica) && !empty($fecha_fin_grafica)) {
                $timestamp_limite = strtotime($fecha_inicio_grafica);
                $timestamp_fin = strtotime($fecha_fin_grafica);
                // Determinar agrupación según el rango
                $dias_diferencia = ($timestamp_fin - $timestamp_limite) / (24 * 60 * 60);
                if ($dias_diferencia > 365) {
                    $agrupar_por = 'month';
                } elseif ($dias_diferencia > 90) {
                    $agrupar_por = 'week';
                } else {
                    $agrupar_por = 'day';
                }
            } else {
                $timestamp_limite = time() - (30 * 24 * 60 * 60); // Por defecto 30 días
            }
            break;
        default:
            $timestamp_limite = time() - (30 * 24 * 60 * 60);
    }
    
    // Crear ObjectId límite si hay límite de tiempo
    if ($timestamp_limite > 0) {
        $objectId_limite = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_limite, str_repeat('0', 16)));
    }
    
    // Construir pipeline de agregación
    $pipeline_codigos_fecha = [];
    
    // Agregar filtro de fecha si hay límite
    if ($objectId_limite) {
        $pipeline_codigos_fecha[] = ['$match' => [
            '_id' => ['$gte' => $objectId_limite]
        ]];
    }
    
    // Si es personalizado y hay fecha fin, agregar filtro de fecha fin
    if ($periodo_grafica === 'personalizado' && !empty($fecha_fin_grafica)) {
        $timestamp_fin = strtotime($fecha_fin_grafica . ' 23:59:59');
        $objectId_fin = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_fin, str_repeat('f', 16)));
        if (isset($pipeline_codigos_fecha[0]['$match'])) {
            $pipeline_codigos_fecha[0]['$match']['_id']['$lte'] = $objectId_fin;
        } else {
            $pipeline_codigos_fecha[] = ['$match' => [
                '_id' => ['$lte' => $objectId_fin]
            ]];
        }
    }
    
    // Construir agrupación según el tipo
    $group_id = [];
    if ($agrupar_por === 'month') {
        $group_id = [
            'year' => ['$year' => '$_id'],
            'month' => ['$month' => '$_id']
        ];
    } elseif ($agrupar_por === 'week') {
        $group_id = [
            'year' => ['$year' => '$_id'],
            'week' => ['$isoWeek' => '$_id']
        ];
    } else {
        // Por día (default)
        $group_id = [
            'year' => ['$year' => '$_id'],
            'month' => ['$month' => '$_id'],
            'day' => ['$dayOfMonth' => '$_id']
        ];
    }
    
    $pipeline_codigos_fecha[] = ['$group' => [
        '_id' => $group_id,
        'total_codigos' => ['$sum' => 1],
        'codigos_activos' => ['$sum' => ['$cond' => [['$eq' => ['$estado', 0]], 1, 0]]],
        'codigos_inactivos' => ['$sum' => ['$cond' => [['$ne' => ['$estado', 0]], 1, 0]]]
    ]];
    
    $pipeline_codigos_fecha[] = ['$sort' => ['_id' => 1]];
    
    $datos_grafica_codigos = $collection_codigos->aggregate($pipeline_codigos_fecha)->toArray();
} catch (Exception $e) {
    error_log("Error en agregación de códigos por fecha: " . $e->getMessage());
    $datos_grafica_codigos = [];
    $agrupar_por = 'day';
}

$title = "Gestión de Códigos - Panel de Administración";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .codigo-text {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .descripcion-text {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .codigo-text {
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Optimizar columnas de la tabla */
        .table th:nth-child(5), .table td:nth-child(5) { /* Columna Usuario */
            max-width: 100px;
            min-width: 100px;
        }
        
        .table th:nth-child(6), .table td:nth-child(6) { /* Columna Marca */
            max-width: 80px;
            min-width: 80px;
        }
        
        .table th:nth-child(7), .table td:nth-child(7) { /* Columna Código */
            max-width: 120px;
            min-width: 120px;
        }
        
        .table th:nth-child(8), .table td:nth-child(8) { /* Columna Descripción */
            max-width: 150px;
            min-width: 150px;
        }
        
        .table th:nth-child(9), .table td:nth-child(9) { /* Columna Estado */
            max-width: 70px;
            min-width: 70px;
        }
        
        .table th:nth-child(10), .table td:nth-child(10) { /* Columna Destacado */
            max-width: 80px;
            min-width: 80px;
        }
        
        .table th:nth-child(11), .table td:nth-child(11) { /* Columna Premium */
            max-width: 80px;
            min-width: 80px;
        }
        
        .table th:nth-child(12), .table td:nth-child(12) { /* Columna Impresiones */
            max-width: 70px;
            min-width: 70px;
        }
        
        .table th:nth-child(13), .table td:nth-child(13) { /* Columna Clicks */
            max-width: 70px;
            min-width: 70px;
        }
        
        .table th:nth-child(14), .table td:nth-child(14) { /* Columna % Conv */
            max-width: 70px;
            min-width: 70px;
        }
        
        .table th:nth-child(15), .table td:nth-child(15) { /* Columna Acciones */
            min-width: 120px;
            white-space: nowrap;
        }
        
        /* Hacer la tabla más compacta */
        .table td {
            padding: 0.5rem;
            vertical-align: middle;
        }
        
        .table th {
            padding: 0.5rem;
            font-size: 0.9rem;
        }
        
        /* Estilos para encabezados ordenables */
        .sortable-header {
            color: #333 !important;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .sortable-header:hover {
            color: #667eea !important;
            text-decoration: none !important;
        }
        
        .sortable-header i {
            font-size: 0.8em;
        }
        
        .sortable-header:hover i {
            color: #667eea !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_codigos.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">Gestión de Códigos</h5>
                        <div class="d-flex align-items-center">
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

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo number_format($estadisticas['total']); ?></h3>
                                    <p class="text-muted mb-0">Total</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['activos']); ?></h3>
                                    <p class="text-muted mb-0">Activos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-danger"><?php echo number_format($estadisticas['inactivos']); ?></h3>
                                    <p class="text-muted mb-0">Inactivos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-warning"><?php echo number_format($estadisticas['destacados']); ?></h3>
                                    <p class="text-muted mb-0">Destacados</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-info"><?php echo number_format($estadisticas['nuevos_hoy']); ?></h3>
                                    <p class="text-muted mb-0">Hoy</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-secondary"><?php echo number_format($total_codigos); ?></h3>
                                    <p class="text-muted mb-0">Filtrados</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de códigos publicados por fecha -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Gráfica de Códigos Publicados por Fecha
                                    </h5>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Muestra códigos agrupados por fecha de publicación. 
                                        "Inactivos" son códigos publicados en esa fecha que actualmente están desactivados.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filtros de período -->
                            <form method="GET" id="formFiltroGrafica" class="mb-3">
                                <!-- Mantener otros filtros existentes -->
                                <?php 
                                foreach ($_GET as $key => $value): 
                                    if ($key !== 'periodo_grafica' && $key !== 'fecha_inicio_grafica' && $key !== 'fecha_fin_grafica'):
                                ?>
                                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                                
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">Período</label>
                                        <select name="periodo_grafica" id="periodo_grafica" class="form-select" onchange="toggleFechaPersonalizada()">
                                            <option value="semana" <?php echo $periodo_grafica === 'semana' ? 'selected' : ''; ?>>Última semana</option>
                                            <option value="30dias" <?php echo $periodo_grafica === '30dias' ? 'selected' : ''; ?>>Últimos 30 días</option>
                                            <option value="3meses" <?php echo $periodo_grafica === '3meses' ? 'selected' : ''; ?>>Últimos 3 meses</option>
                                            <option value="inicio" <?php echo $periodo_grafica === 'inicio' ? 'selected' : ''; ?>>Desde el inicio</option>
                                            <option value="personalizado" <?php echo $periodo_grafica === 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="fecha_inicio_container" style="display: <?php echo $periodo_grafica === 'personalizado' ? 'block' : 'none'; ?>;">
                                        <label class="form-label">Fecha Inicio</label>
                                        <input type="date" name="fecha_inicio_grafica" id="fecha_inicio_grafica" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($fecha_inicio_grafica); ?>">
                                    </div>
                                    <div class="col-md-3" id="fecha_fin_container" style="display: <?php echo $periodo_grafica === 'personalizado' ? 'block' : 'none'; ?>;">
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" name="fecha_fin_grafica" id="fecha_fin_grafica" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($fecha_fin_grafica); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i>Filtrar
                                        </button>
                                        <a href="admin_codigos.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-redo me-1"></i>Resetear
                                        </a>
                                    </div>
                                </div>
                            </form>
                            
                            <canvas id="graficaCodigosFecha" width="400" height="150"></canvas>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="-1" <?php echo $filtro_estado === '-1' ? 'selected' : ''; ?>>Inactivo</option>
                                        <option value="-2" <?php echo $filtro_estado === '-2' ? 'selected' : ''; ?>>Desactivado por usuario</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Destacado</label>
                                    <select name="destacado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $filtro_destacado === '1' ? 'selected' : ''; ?>>Sí</option>
                                        <option value="0" <?php echo $filtro_destacado === '0' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Marca</label>
                                    <select name="marca" class="form-select">
                                        <option value="">Todas</option>
                                        <?php foreach ($marcas as $marca): ?>
                                        <option value="<?php echo htmlspecialchars($marca['nombre_clave']); ?>" 
                                                <?php echo $filtro_marca === $marca['nombre_clave'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($marca['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Buscar</label>
                                    <input type="text" name="busqueda" class="form-control" 
                                           placeholder="Código o descripción" value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">ID Código</label>
                                    <input type="text" name="id_codigo" class="form-control" 
                                           placeholder="67a43ea6a241517ce506ef23" value="<?php echo htmlspecialchars($filtro_id_codigo); ?>">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Usuario ID</label>
                                    <input type="text" name="usuario" class="form-control" 
                                           placeholder="ID Usuario" value="<?php echo htmlspecialchars($filtro_usuario); ?>">
                                </div>
                                <div class="col-md-1">
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
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Acciones masivas -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" id="bulkForm">
                                <input type="hidden" name="action" value="bulk_action">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <label class="form-label">Acción masiva:</label>
                                        <select name="bulk_action" class="form-select" id="bulkAction">
                                            <option value="">Seleccionar acción</option>
                                            <option value="activate">Activar seleccionados</option>
                                            <option value="deactivate">Desactivar seleccionados</option>
                                            <option value="highlight">Destacar seleccionados</option>
                                            <option value="unhighlight">Quitar destacado</option>
                                            <option value="highlight_premium">Destacar premium</option>
                                            <option value="unhighlight_premium">Quitar destacado premium</option>
                                            <option value="delete">Eliminar seleccionados</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                                            <i class="fas fa-check-square me-1"></i>Seleccionar todos
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="deselectAll()">
                                            <i class="fas fa-square me-1"></i>Deseleccionar
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-warning" id="bulkSubmit" disabled>
                                            <i class="fas fa-cogs me-1"></i>Ejecutar acción
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted" id="selectedCount">0 seleccionados</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de códigos -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Lista de Códigos</h5>
                                <?php if ($sort_field && $sort_field !== '_id'): ?>
                                    <small class="text-muted">
                                        Ordenado por: <strong><?php echo ucfirst($sort_field); ?></strong> 
                                        (<?php echo $sort_direction === 'asc' ? 'Ascendente' : 'Descendente'; ?>)
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">
                                        Ordenado por: <strong>Fecha de creación</strong> 
                                        (<?php echo $sort_direction === 'asc' ? 'Ascendente' : 'Descendente'; ?>)
                                    </small>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-primary"><?php echo number_format($total_codigos); ?> códigos</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="30">
                                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
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
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'updated_at', 'dir' => ($sort_field === 'updated_at' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Actualizado
                                                    <?php if ($sort_field === 'updated_at'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>ID</th>
                                            <th>Usuario</th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'marca', 'dir' => ($sort_field === 'marca' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Marca
                                                    <?php if ($sort_field === 'marca'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'codigo', 'dir' => ($sort_field === 'codigo' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Código
                                                    <?php if ($sort_field === 'codigo'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>Descripción</th>
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
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'destacado', 'dir' => ($sort_field === 'destacado' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Destacado
                                                    <?php if ($sort_field === 'destacado'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'destacado_social', 'dir' => ($sort_field === 'destacado_social' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Premium
                                                    <?php if ($sort_field === 'destacado_social'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'impressions', 'dir' => ($sort_field === 'impressions' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Impr.
                                                    <?php if ($sort_field === 'impressions'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'vistas', 'dir' => ($sort_field === 'vistas' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Clicks
                                                    <?php if ($sort_field === 'vistas'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>% Conv</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($codigos as $codigo): 
                                            // Obtener información del usuario (verificar que existe id_usuario)
                                            $usuario = null;
                                            if (isset($codigo['id_usuario']) && $codigo['id_usuario']) {
                                                $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo['id_usuario'])]);
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="codigo-checkbox" value="<?php echo $codigo['_id']; ?>" 
                                                       onchange="updateSelectedCount()">
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    $fecha_mostrar = 'N/A';
                                                    
                                                    // Intentar obtener fecha de fecha_publicacion
                                                    if (isset($codigo['fecha_publicacion'])) {
                                                        if ($codigo['fecha_publicacion'] instanceof MongoDB\BSON\UTCDateTime) {
                                                            $fecha_mostrar = date('d/m/Y H:i', $codigo['fecha_publicacion']->toDateTime()->getTimestamp());
                                                        } elseif (is_string($codigo['fecha_publicacion'])) {
                                                            $fecha_mostrar = date('d/m/Y H:i', strtotime($codigo['fecha_publicacion']));
                                                        }
                                                    }
                                                    
                                                    // Si no hay fecha_creacion, usar el timestamp del ObjectId
                                                    if ($fecha_mostrar === 'N/A' && isset($codigo['_id'])) {
                                                        $objectId = $codigo['_id'];
                                                        if ($objectId instanceof MongoDB\BSON\ObjectId) {
                                                            $timestamp = $objectId->getTimestamp();
                                                            $fecha_mostrar = date('d/m/Y H:i', $timestamp);
                                                        }
                                                    }
                                                    
                                                    echo $fecha_mostrar;
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    $fecha_actualizado = 'N/A';
                                                    
                                                    // Intentar obtener fecha de updated_at
                                                    if (isset($codigo['updated_at'])) {
                                                        if ($codigo['updated_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                                            $fecha_actualizado = date('d/m/Y H:i', $codigo['updated_at']->toDateTime()->getTimestamp());
                                                        } elseif (is_string($codigo['updated_at'])) {
                                                            $fecha_actualizado = date('d/m/Y H:i', strtotime($codigo['updated_at']));
                                                        }
                                                    }
                                                    
                                                    echo $fecha_actualizado;
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if (!empty($codigo['codigo']) && !empty($codigo['marca'])): ?>
                                                    <a href="/de-<?php echo htmlspecialchars(strtolower($codigo['marca'])); ?>?codigo=<?php echo $codigo['_id']; ?>"
                                                       target="_blank" title="Ver código público" class="text-decoration-none">
                                                        <small class="text-primary codigo-id-elipsis-css" title="<?php echo $codigo['_id']; ?>">
                                                            <?php echo $codigo['_id']; ?>
                                                        </small>
                                                    </a>
                                                    <style>
                                                        .codigo-id-elipsis-css {
                                                            display: inline-block;
                                                            max-width: 50px !important;
                                                            white-space: nowrap;
                                                            overflow: hidden;
                                                            text-overflow: ellipsis;
                                                            vertical-align: bottom;
                                                        }
                                                    </style>
                                                <?php else: ?>
                                                    <small class="text-muted"><?php echo substr($codigo['_id'], 0, 8) . '...'; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($usuario && isset($usuario['img']) && $usuario['img']): ?>
                                                        <img src="<?php echo htmlspecialchars($usuario['img']); ?>" 
                                                             class="rounded-circle me-1" width="20" height="20" 
                                                             onerror="this.src='https://via.placeholder.com/20'">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle me-1 d-flex align-items-center justify-content-center" 
                                                             style="width: 20px; height: 20px;">
                                                            <i class="fas fa-user text-white" style="font-size: 8px;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <?php if (isset($codigo['id_usuario']) && $codigo['id_usuario']): ?>
                                                            <a href="admin_usuario_detalle.php?id=<?php echo htmlspecialchars($codigo['id_usuario']); ?>" 
                                                               class="text-decoration-none" title="Ver usuario: <?php echo htmlspecialchars($usuario['username'] ?? 'Usuario'); ?>">
                                                                <small class="text-primary"><?php echo htmlspecialchars(substr($usuario['username'] ?? 'Usuario no encontrado', 0, 10)) . '...'; ?></small>
                                                            </a>
                                                        <?php else: ?>
                                                            <small><?php echo htmlspecialchars(substr($usuario['username'] ?? 'Usuario no encontrado', 0, 10)) . '...'; ?></small>
                                                        <?php endif; ?>
                                                        <br><small class="text-muted"><?php echo isset($codigo['id_usuario']) ? substr($codigo['id_usuario'], 0, 6) . '...' : 'Sin usuario'; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $marca_nombre = $codigo['marca'] ?? '';
                                                if (!empty($marca_nombre)) {
                                                    $marca_url = '/de-' . strtolower($marca_nombre);
                                                    echo '<a href="' . $marca_url . '" target="_blank" class="badge bg-info text-decoration-none" title="Ver página de marca">';
                                                    echo ucfirst($marca_nombre);
                                                    echo '</a>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">Sin marca</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="codigo-text" title="<?php echo htmlspecialchars($codigo['codigo'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars(substr($codigo['codigo'] ?? '', 0, 15)) . '...'; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="descripcion-text" title="<?php echo htmlspecialchars($codigo['descripcion'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars(substr($codigo['descripcion'] ?? '', 0, 30)) . '...'; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $estado = $codigo['estado'] ?? 0;
                                                $estado_class = $estado == 0 ? 'success' : ($estado == -1 ? 'danger' : 'warning');
                                                $estado_text = $estado == 0 ? 'Activo' : ($estado == -1 ? 'Inactivo' : 'Desactivado');
                                                ?>
                                                <span class="badge bg-<?php echo $estado_class; ?>"><?php echo $estado_text; ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $destacado = $codigo['destacado'] ?? 0;
                                                if ($destacado > 0): 
                                                    $fecha_destacado = date('d/m/Y H:i', $destacado);
                                                ?>
                                                    <span class="badge bg-warning" title="Fecha: <?php echo $fecha_destacado; ?>">
                                                        <i class="fas fa-star me-1"></i>
                                                        <?php echo date('d/m/Y', $destacado); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $destacado_social = $codigo['destacado_social'] ?? 0;
                                                if ($destacado_social > 0): 
                                                    $fecha_premium = date('d/m/Y H:i', $destacado_social);
                                                ?>
                                                    <span class="badge bg-success" title="Fecha: <?php echo $fecha_premium; ?>">
                                                        <i class="fas fa-crown me-1"></i>
                                                        <?php echo date('d/m/Y', $destacado_social); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info" title="Impresiones"><?php echo number_format($codigo['total_impressions'] ?? 0); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success" title="Clicks"><?php echo number_format($codigo['totalclicks'] ?? 0); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $impressions = $codigo['total_impressions'] ?? 0;
                                                $clicks = $codigo['totalclicks'] ?? 0;
                                                $conversion = $impressions > 0 ? round(($clicks / $impressions) * 100, 1) : 0;
                                                $conversion_color = $conversion > 10 ? 'success' : ($conversion > 5 ? 'warning' : 'danger');
                                                ?>
                                                <span class="badge bg-<?php echo $conversion_color; ?>" title="Tasa de conversión"><?php echo $conversion; ?>%</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary btn-sm p-1" 
                                                            data-bs-toggle="modal" data-bs-target="#modalEditarCodigo" 
                                                            data-codigo-id="<?php echo $codigo['_id']; ?>"
                                                            data-codigo-codigo="<?php echo htmlspecialchars($codigo['codigo'] ?? ''); ?>"
                                                            data-codigo-descripcion="<?php echo htmlspecialchars($codigo['descripcion'] ?? ''); ?>"
                                                            data-codigo-marca="<?php echo htmlspecialchars($codigo['marca'] ?? ''); ?>"
                                                            data-codigo-estado="<?php echo $codigo['estado'] ?? 0; ?>"
                                                            data-codigo-destacado="<?php echo $codigo['destacado'] ?? 0; ?>"
                                                            data-codigo-destacado-social="<?php echo $codigo['destacado_social'] ?? 0; ?>"
                                                            title="Editar">
                                                        <i class="fas fa-edit" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning btn-sm p-1" 
                                                            onclick="toggleEstado('<?php echo $codigo['_id']; ?>', <?php echo $codigo['estado'] ?? 0; ?>)"
                                                            title="Toggle Estado">
                                                        <i class="fas fa-toggle-<?php echo ($codigo['estado'] ?? 0) == 0 ? 'on' : 'off'; ?>" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info btn-sm p-1" 
                                                            onclick="toggleDestacado('<?php echo $codigo['_id']; ?>', <?php echo $codigo['destacado'] ?? 0; ?>)"
                                                            title="Toggle Destacado Normal">
                                                        <i class="fas fa-star" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success btn-sm p-1" 
                                                            onclick="toggleDestacadoPremium('<?php echo $codigo['_id']; ?>', <?php echo $codigo['destacado_social'] ?? 0; ?>)"
                                                            title="Toggle Destacado Premium (Home)">
                                                        <i class="fas fa-crown" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <?php if (($codigo['destacado'] ?? 0) > 0 || ($codigo['destacado_social'] ?? 0) > 0): ?>
                                                    <button type="button" class="btn btn-outline-warning btn-sm p-1" 
                                                            onclick="editarFechaDestacado('<?php echo $codigo['_id']; ?>', <?php echo $codigo['destacado'] ?? 0; ?>, <?php echo $codigo['destacado_social'] ?? 0; ?>)"
                                                            title="Editar fecha de destacado">
                                                        <i class="fas fa-calendar" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm p-1" 
                                                            onclick="eliminarCodigo('<?php echo $codigo['_id']; ?>')"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash" style="font-size: 0.8rem;"></i>
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
                            <nav aria-label="Paginación de códigos">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&estado=<?php echo $filtro_estado; ?>&destacado=<?php echo $filtro_destacado; ?>&marca=<?php echo urlencode($filtro_marca); ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&id_codigo=<?php echo urlencode($filtro_id_codigo); ?>&sort=<?php echo $sort_field; ?>&dir=<?php echo $sort_direction; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar código -->
    <div class="modal fade" id="modalEditarCodigo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Código</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_codigo">
                        <input type="hidden" name="codigo_id" id="edit_codigo_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código *</label>
                                <input type="text" class="form-control" name="codigo" id="edit_codigo" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marca *</label>
                                <input type="text" class="form-control" name="marca" id="edit_marca" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select" id="edit_estado">
                                    <option value="0">Activo</option>
                                    <option value="-1">Inactivo</option>
                                    <option value="-2">Desactivado por usuario</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Destacado</label>
                                <select name="destacado" class="form-select" id="edit_destacado">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Destacado Premium (Home)</label>
                                <select name="destacado_social" class="form-select" id="edit_destacado_social">
                                    <option value="0">No</option>
                                    <option value="1">Sí (Aparece en Home)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Código</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar fecha de destacado -->
    <div class="modal fade" id="modalEditarFechaDestacado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Fecha de Destacado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEditarFecha">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar_fecha_destacado">
                        <input type="hidden" name="codigo_id" id="fecha_codigo_id">
                        <input type="hidden" name="tipo_destacado" id="fecha_tipo_destacado">
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de Destacado</label>
                            <div id="fecha_tipo_info" class="alert alert-info"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nueva Fecha y Hora</label>
                            <input type="datetime-local" class="form-control" name="nueva_fecha" id="fecha_input" required>
                            <small class="form-text text-muted">Esta fecha determiná el orden en el home (más reciente aparece primero)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Fecha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Modal de editar código
        document.getElementById('modalEditarCodigo').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('edit_codigo_id').value = button.getAttribute('data-codigo-id');
            document.getElementById('edit_codigo').value = button.getAttribute('data-codigo-codigo');
            document.getElementById('edit_descripcion').value = button.getAttribute('data-codigo-descripcion');
            document.getElementById('edit_marca').value = button.getAttribute('data-codigo-marca');
            document.getElementById('edit_estado').value = button.getAttribute('data-codigo-estado');
            document.getElementById('edit_destacado').value = button.getAttribute('data-codigo-destacado');
            document.getElementById('edit_destacado_social').value = button.getAttribute('data-codigo-destacado-social') || '0';
        });

        // Selección masiva
        function toggleSelectAll() {
            var selectAll = document.getElementById('selectAllCheckbox');
            var checkboxes = document.querySelectorAll('.codigo-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateSelectedCount();
        }

        function selectAll() {
            var checkboxes = document.querySelectorAll('.codigo-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
            document.getElementById('selectAllCheckbox').checked = true;
            updateSelectedCount();
        }

        function deselectAll() {
            var checkboxes = document.querySelectorAll('.codigo-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            document.getElementById('selectAllCheckbox').checked = false;
            updateSelectedCount();
        }

        function updateSelectedCount() {
            var checkboxes = document.querySelectorAll('.codigo-checkbox:checked');
            var count = checkboxes.length;
            document.getElementById('selectedCount').textContent = count + ' seleccionados';
            
            // Actualizar botón de acción masiva
            var bulkSubmit = document.getElementById('bulkSubmit');
            var bulkAction = document.getElementById('bulkAction');
            bulkSubmit.disabled = count === 0 || bulkAction.value === '';
        }

        // Actualizar formulario de acciones masivas
        document.getElementById('bulkAction').addEventListener('change', function() {
            updateSelectedCount();
        });

        // Formulario de acciones masivas
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            var checkboxes = document.querySelectorAll('.codigo-checkbox:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Por favor selecciona al menos un código');
                return;
            }
            
            var action = document.getElementById('bulkAction').value;
            if (!action) {
                e.preventDefault();
                alert('Por favor selecciona una acción');
                return;
            }
            
            if (action === 'delete') {
                if (!confirm('¿Estás seguro de eliminar los códigos seleccionados? Esta acción no se puede deshacer.')) {
                    e.preventDefault();
                    return;
                }
            }
            
            // Añadir IDs seleccionados al formulario
            checkboxes.forEach(function(checkbox) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'codigo_ids[]';
                input.value = checkbox.value;
                this.appendChild(input);
            }, this);
        });

        // Toggle estado
        function toggleEstado(codigoId, currentEstado) {
            if (confirm('¿Estás seguro de cambiar el estado de este código?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_estado">
                    <input type="hidden" name="codigo_id" value="${codigoId}">
                    <input type="hidden" name="nuevo_estado" value="${currentEstado == 0 ? -1 : 0}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Toggle destacado
        function toggleDestacado(codigoId, currentDestacado) {
            if (confirm('¿Estás seguro de cambiar el estado destacado de este código?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_destacado">
                    <input type="hidden" name="codigo_id" value="${codigoId}">
                    <input type="hidden" name="nuevo_destacado" value="${currentDestacado == 1 ? 0 : 1}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Toggle destacado premium
        function toggleDestacadoPremium(codigoId, currentDestacadoPremium) {
            if (confirm('¿Estás seguro de cambiar el estado destacado premium de este código? (Esto afecta la visibilidad en la home)')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_destacado_premium">
                    <input type="hidden" name="codigo_id" value="${codigoId}">
                    <input type="hidden" name="nuevo_destacado_premium" value="${currentDestacadoPremium == 1 ? 0 : 1}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Eliminar código
        function eliminarCodigo(codigoId) {
            if (confirm('¿Estás seguro de eliminar este código? Esta acción no se puede deshacer.')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_codigo">
                    <input type="hidden" name="codigo_id" value="${codigoId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Editar fecha de destacado
        function editarFechaDestacado(codigoId, destacado, destacadoSocial) {
            // Determinar qué tipo de destacado editar
            var tipo = '';
            var tipoInfo = '';
            var fechaActual = '';
            
            if (destacadoSocial > 0 && destacado > 0) {
                // Tiene ambos, permitir elegir
                tipo = 'ambos';
                tipoInfo = 'Ambos (Premium y Normal)';
                // Usar el más reciente
                fechaActual = new Date(Math.max(destacadoSocial, destacado) * 1000);
            } else if (destacadoSocial > 0) {
                tipo = 'premium';
                tipoInfo = '<i class="fas fa-crown"></i> Destacado Premium (Home)';
                fechaActual = new Date(destacadoSocial * 1000);
            } else if (destacado > 0) {
                tipo = 'normal';
                tipoInfo = '<i class="fas fa-star"></i> Destacado Normal';
                fechaActual = new Date(destacado * 1000);
            }
            
            // Configurar el modal
            document.getElementById('fecha_codigo_id').value = codigoId;
            document.getElementById('fecha_tipo_destacado').value = tipo;
            document.getElementById('fecha_tipo_info').innerHTML = tipoInfo;
            
            // Convertir fecha a formato datetime-local
            var year = fechaActual.getFullYear();
            var month = String(fechaActual.getMonth() + 1).padStart(2, '0');
            var day = String(fechaActual.getDate()).padStart(2, '0');
            var hours = String(fechaActual.getHours()).padStart(2, '0');
            var minutes = String(fechaActual.getMinutes()).padStart(2, '0');
            var fechaFormato = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            
            document.getElementById('fecha_input').value = fechaFormato;
            
            // Mostrar el modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditarFechaDestacado'));
            modal.show();
        }

        // Gráfica de códigos publicados por fecha
        document.addEventListener('DOMContentLoaded', function() {
            <?php
            // Preparar datos para la gráfica
            $labels = [];
            $datos_total = [];
            $datos_activos = [];
            $datos_inactivos = [];

            if (!empty($datos_grafica_codigos)) {
                foreach ($datos_grafica_codigos as $dato) {
                    $fecha = '';
                    // Formatear fecha según el tipo de agrupación
                    if (isset($dato['_id']['day']) && isset($dato['_id']['month']) && isset($dato['_id']['year'])) {
                        // Agrupación por día
                        $fecha = sprintf('%02d/%02d/%d', $dato['_id']['day'], $dato['_id']['month'], $dato['_id']['year']);
                    } elseif (isset($dato['_id']['week']) && isset($dato['_id']['year'])) {
                        // Agrupación por semana
                        $fecha = 'Semana ' . $dato['_id']['week'] . '/' . $dato['_id']['year'];
                    } elseif (isset($dato['_id']['month']) && isset($dato['_id']['year'])) {
                        // Agrupación por mes
                        $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                        $fecha = $meses[$dato['_id']['month']] . ' ' . $dato['_id']['year'];
                    }
                    
                    if (!empty($fecha)) {
                        $labels[] = $fecha;
                        $datos_total[] = $dato['total_codigos'] ?? 0;
                        $datos_activos[] = $dato['codigos_activos'] ?? 0;
                        $datos_inactivos[] = $dato['codigos_inactivos'] ?? 0;
                    }
                }
            }

            // Si no hay datos, crear datos de ejemplo para mostrar la gráfica
            if (empty($labels)) {
                $labels = ['No hay datos'];
                $datos_total = [0];
                $datos_activos = [0];
                $datos_inactivos = [0];
            }
            ?>

            const ctxCodigos = document.getElementById('graficaCodigosFecha').getContext('2d');
            const graficaCodigosFecha = new Chart(ctxCodigos, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Total Códigos',
                        data: <?php echo json_encode($datos_total); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true
                    }, {
                        label: 'Códigos Activos',
                        data: <?php echo json_encode($datos_activos); ?>,
                        borderColor: 'rgb(40, 167, 69)',
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        tension: 0.1,
                        fill: true
                    }, {
                        label: 'Códigos Inactivos',
                        data: <?php echo json_encode($datos_inactivos); ?>,
                        borderColor: 'rgb(220, 53, 69)',
                        backgroundColor: 'rgba(220, 53, 69, 0.2)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Códigos Publicados por Fecha<?php 
                                $titulo_periodo = '';
                                switch($periodo_grafica) {
                                    case 'semana': $titulo_periodo = ' (Última semana)'; break;
                                    case '30dias': $titulo_periodo = ' (Últimos 30 días)'; break;
                                    case '3meses': $titulo_periodo = ' (Últimos 3 meses)'; break;
                                    case 'inicio': $titulo_periodo = ' (Desde el inicio)'; break;
                                    case 'personalizado': 
                                        if (!empty($fecha_inicio_grafica) && !empty($fecha_fin_grafica)) {
                                            $titulo_periodo = ' (' . date('d/m/Y', strtotime($fecha_inicio_grafica)) . ' - ' . date('d/m/Y', strtotime($fecha_fin_grafica)) . ')';
                                        }
                                        break;
                                }
                                echo $titulo_periodo;
                            ?>'
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Número de Códigos'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Fecha'
                            }
                        }
                    }
                }
            });
        });

        // Función para mostrar/ocultar campos de fecha personalizada
        function toggleFechaPersonalizada() {
            const periodo = document.getElementById('periodo_grafica').value;
            const fechaInicioContainer = document.getElementById('fecha_inicio_container');
            const fechaFinContainer = document.getElementById('fecha_fin_container');
            
            if (periodo === 'personalizado') {
                fechaInicioContainer.style.display = 'block';
                fechaFinContainer.style.display = 'block';
            } else {
                fechaInicioContainer.style.display = 'none';
                fechaFinContainer.style.display = 'none';
            }
        }

        // Validar fechas personalizadas al enviar el formulario
        document.getElementById('formFiltroGrafica').addEventListener('submit', function(e) {
            const periodo = document.getElementById('periodo_grafica').value;
            if (periodo === 'personalizado') {
                const fechaInicio = document.getElementById('fecha_inicio_grafica').value;
                const fechaFin = document.getElementById('fecha_fin_grafica').value;
                
                if (!fechaInicio || !fechaFin) {
                    e.preventDefault();
                    alert('Por favor, selecciona ambas fechas (inicio y fin) para el período personalizado.');
                    return false;
                }
                
                if (new Date(fechaInicio) > new Date(fechaFin)) {
                    e.preventDefault();
                    alert('La fecha de inicio debe ser anterior a la fecha de fin.');
                    return false;
                }
            }
        });
    </script>
    
<?php get_footer(); ?>
</body>
</html>


