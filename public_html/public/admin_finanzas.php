<?php

session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

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

// Obtener colecciones
$collection_marcas = getCollectionMarcas();
$collection_codigos = getCollectionCodigos();

// Procesar acciones
$message = '';
$error = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_financial_data':
            $marca_id = $_POST['marca_id'];
            $financial_data = [
                'enlace_afiliado' => trim($_POST['enlace_afiliado'] ?? ''),
                'tipo_comision' => $_POST['tipo_comision'] ?? 'fijo',
                'comision_fija' => floatval($_POST['comision_fija'] ?? 0),
                'comision_porcentaje' => floatval($_POST['comision_porcentaje'] ?? 0),
                'notas_financieras' => trim($_POST['notas_financieras'] ?? ''),
                'fecha_actualizacion' => date('Y-m-d H:i:s')
            ];

            try {
                $result = $collection_marcas->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($marca_id)],
                    ['$set' => ['datos_financieros' => $financial_data]]
                );

                if ($result->getModifiedCount() > 0) {
                    $message = 'Datos financieros actualizados correctamente';
                } else {
                    $error = 'Error al actualizar los datos financieros';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
    }
}

// Obtener todas las marcas con datos financieros
$marcas = $collection_marcas->find(
    ['estado' => 1],
    [
        'sort' => ['nombre' => 1],
        'projection' => [
            'nombre' => 1,
            'nombre_clave' => 1,
            'categoria' => 1,
            'imagen' => 1,
            'datos_financieros' => 1,
            'url_register' => 1
        ]
    ]
)->toArray();

// Función para obtener datos financieros de una marca
function getFinancialData($marca) {
    $datos = $marca['datos_financieros'] ?? [];
    return [
        'enlace_afiliado' => $datos['enlace_afiliado'] ?? '',
        'tipo_comision' => $datos['tipo_comision'] ?? 'fijo',
        'comision_fija' => $datos['comision_fija'] ?? 0,
        'comision_porcentaje' => $datos['comision_porcentaje'] ?? 0,
        'notas_financieras' => $datos['notas_financieras'] ?? '',
        'fecha_actualizacion' => $datos['fecha_actualizacion'] ?? ''
    ];
}

// Función para calcular estadísticas financieras básicas
function getMarcaStats($marca) {
    global $collection_codigos;
    $marca_clave = $marca['nombre_clave'];

    $codigos_count = $collection_codigos->countDocuments([
        'marca' => $marca_clave,
        'estado' => 0
    ]);

    return [
        'codigos_count' => $codigos_count,
        'clicks_total' => 0, // Puedes implementar esto si tienes datos de clicks
        'ingresos_estimados' => 0 // Puedes implementar esto basado en los códigos activos
    ];
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Financiero de Marcas - Código Amigo</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">

    <style>
        :root {
            --primary-color: #E30613;
            --secondary-color: #2C2C2C;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .main-header {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            background-color: var(--light-color);
            border-bottom: 2px solid var(--primary-color);
            font-weight: 600;
        }

        .table-excel {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .table-excel th {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            font-weight: 600;
            padding: 12px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-excel td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            vertical-align: middle;
        }

        .table-excel tbody tr:hover {
            background-color: #f8f9fa;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(227, 6, 19, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #C40510;
            border-color: #C40510;
        }

        .marca-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .financial-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-fijo {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }

        .badge-porcentaje {
            background-color: var(--success-color);
            color: white;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .form-inline-cell {
            margin: 0;
        }

        .form-inline-cell .form-control {
            height: 32px;
            font-size: 0.875rem;
        }

        .cell-input {
            width: 100%;
            border: none;
            background: transparent;
            padding: 4px;
        }

        .cell-input:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: -2px;
        }

        .cell-textarea {
            width: 100%;
            min-height: 60px;
            border: none;
            background: transparent;
            resize: vertical;
        }

        .cell-textarea:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: -2px;
        }

        .cell-select {
            width: 100%;
            border: none;
            background: transparent;
            padding: 4px;
        }

        .cell-select:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: -2px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-chart-line"></i> Control Financiero de Marcas</h1>
                    <p class="mb-0">Gestión de ingresos y enlaces de afiliados por marca</p>
                </div>
                <div class="col-md-4 text-right">
                    <a href="admin_dashboard" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-tags fa-2x mb-2"></i>
                        <div class="stats-number"><?php echo count($marcas); ?></div>
                        <div>Marcas Totales</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-euro-sign fa-2x mb-2"></i>
                        <div class="stats-number">
                            <?php
                            $marcas_con_datos = array_filter($marcas, function($marca) {
                                return isset($marca['datos_financieros']);
                            });
                            echo count($marcas_con_datos);
                            ?>
                        </div>
                        <div>Con Datos Financieros</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-link fa-2x mb-2"></i>
                        <div class="stats-number">
                            <?php
                            $marcas_con_enlace = array_filter($marcas, function($marca) {
                                $datos = getFinancialData($marca);
                                return !empty($datos['enlace_afiliado']);
                            });
                            echo count($marcas_con_enlace);
                            ?>
                        </div>
                        <div>Con Enlace Afiliado</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-percentage fa-2x mb-2"></i>
                        <div class="stats-number">
                            <?php
                            $total_ingresos = array_sum(array_map(function($marca) {
                                $datos = getFinancialData($marca);
                                return $datos['comision_fija'] + $datos['comision_porcentaje'];
                            }, $marcas));
                            echo number_format($total_ingresos, 2) . '€';
                            ?>
                        </div>
                        <div>Ingresos Configurados</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla Principal Estilo Excel -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-table"></i> Gestión Financiera de Marcas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-excel" id="financialTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="10%">Logo</th>
                                <th width="20%">Marca</th>
                                <th width="15%">Categoría</th>
                                <th width="25%">Enlace Afiliado</th>
                                <th width="10%">Tipo Comisión</th>
                                <th width="10%">Comisión</th>
                                <th width="15%">Notas</th>
                                <th width="10%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marcas as $index => $marca): ?>
                            <?php
                                $datos_financieros = getFinancialData($marca);
                                $stats = getMarcaStats($marca);
                            ?>
                            <tr data-marca-id="<?php echo $marca['_id']; ?>">
                                <td class="text-center">
                                    <span class="badge badge-secondary"><?php echo $index + 1; ?></span>
                                </td>
                                <td class="text-center">
                                    <img src="<?php echo str_replace('http://', 'https://', $marca['imagen']); ?>"
                                         alt="<?php echo $marca['nombre']; ?>"
                                         class="marca-logo"
                                         onerror="this.src='/img/no_image.png'">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($marca['nombre']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($marca['nombre_clave']); ?></small>
                                    <br>
                                    <small class="text-info">
                                        <i class="fas fa-code"></i> <?php echo $stats['codigos_count']; ?> códigos
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($marca['categoria']); ?></span>
                                </td>
                                <td>
                                    <input type="url"
                                           class="cell-input"
                                           value="<?php echo htmlspecialchars($datos_financieros['enlace_afiliado']); ?>"
                                           placeholder="https://enlace-afiliado.com"
                                           onchange="updateFinancialData(this)">
                                </td>
                                <td>
                                    <select class="cell-select" onchange="updateFinancialData(this)">
                                        <option value="fijo" <?php echo $datos_financieros['tipo_comision'] == 'fijo' ? 'selected' : ''; ?>>Fijo</option>
                                        <option value="porcentaje" <?php echo $datos_financieros['tipo_comision'] == 'porcentaje' ? 'selected' : ''; ?>>Porcentaje</option>
                                        <option value="mixto" <?php echo $datos_financieros['tipo_comision'] == 'mixto' ? 'selected' : ''; ?>>Mixto</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <input type="number"
                                               step="0.01"
                                               class="cell-input comision-fija"
                                               value="<?php echo $datos_financieros['comision_fija']; ?>"
                                               placeholder="0.00"
                                               onchange="updateFinancialData(this)"
                                               style="display: <?php echo $datos_financieros['tipo_comision'] == 'porcentaje' ? 'none' : 'block'; ?>;">

                                        <div class="input-group input-group-sm comision-porcentaje" style="display: <?php echo $datos_financieros['tipo_comision'] == 'fijo' ? 'none' : 'flex'; ?>;">
                                            <input type="number"
                                                   step="0.01"
                                                   class="form-control"
                                                   value="<?php echo $datos_financieros['comision_porcentaje']; ?>"
                                                   placeholder="0"
                                                   onchange="updateFinancialData(this)">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <textarea class="cell-textarea"
                                              placeholder="Notas sobre ingresos, contactos, etc."
                                              onchange="updateFinancialData(this)"><?php echo htmlspecialchars($datos_financieros['notas_financieras']); ?></textarea>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-success" onclick="saveMarcaData('<?php echo $marca['_id']; ?>')" title="Guardar cambios">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="viewMarcaDetails('<?php echo $marca['_id']; ?>')" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            $('#financialTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-es.json"
                },
                "pageLength": 50,
                "order": [[ 2, "asc" ]], // Ordenar por nombre de marca
                "columnDefs": [
                    { "orderable": false, "targets": [0, 1, 8] }, // Columnas no ordenables
                    { "width": "5%", "targets": 0 },
                    { "width": "10%", "targets": 1 },
                    { "width": "20%", "targets": 2 },
                    { "width": "15%", "targets": 3 }
                ]
            });
        });

        // Función para actualizar datos financieros
        function updateFinancialData(element) {
            const row = $(element).closest('tr');
            const marcaId = row.data('marca-id');

            // Marcar la fila como modificada
            row.addClass('table-warning');

            // Mostrar indicador de cambios pendientes
            if (!row.find('.changes-indicator').length) {
                row.find('td:last-child').append('<small class="changes-indicator text-warning"><i class="fas fa-circle"></i> Cambios pendientes</small>');
            }
        }

        // Función para guardar datos de una marca
        function saveMarcaData(marcaId) {
            const row = $(`tr[data-marca-id="${marcaId}"]`);

            // Recopilar datos del formulario
            const formData = {
                action: 'update_financial_data',
                marca_id: marcaId,
                enlace_afiliado: row.find('input[type="url"]').val(),
                tipo_comision: row.find('.cell-select').val(),
                comision_fija: row.find('.comision-fija').val(),
                comision_porcentaje: row.find('.comision-porcentaje input').val(),
                notas_financieras: row.find('.cell-textarea').val()
            };

            // Enviar datos vía AJAX
            $.ajax({
                url: '',
                method: 'POST',
                data: formData,
                success: function(response) {
                    row.removeClass('table-warning');
                    row.find('.changes-indicator').remove();

                    if (response.success) {
                        // Mostrar mensaje de éxito
                        showAlert('Datos guardados correctamente', 'success');
                    } else {
                        showAlert('Error al guardar: ' + (response.error || 'Error desconocido'), 'danger');
                    }
                },
                error: function() {
                    showAlert('Error de conexión', 'danger');
                }
            });
        }

        // Función para mostrar alertas
        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;

            $('.container-fluid').prepend(alertHtml);

            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                $('.alert').fadeOut();
            }, 5000);
        }

        // Función para ver detalles de una marca
        function viewMarcaDetails(marcaId) {
            // Implementar modal con detalles completos
            alert('Función de detalles - pendiente de implementar');
        }

        // Función para manejar cambios en tipo de comisión
        $(document).on('change', '.cell-select', function() {
            const row = $(this).closest('tr');
            const tipo = $(this).val();

            if (tipo === 'fijo') {
                row.find('.comision-fija').show();
                row.find('.comision-porcentaje').hide();
            } else if (tipo === 'porcentaje') {
                row.find('.comision-fija').hide();
                row.find('.comision-porcentaje').show();
            } else if (tipo === 'mixto') {
                row.find('.comision-fija').show();
                row.find('.comision-porcentaje').show();
            }
        });

        // Guardar cambios automáticamente cada 30 segundos
        setInterval(function() {
            $('.table-warning').each(function() {
                const marcaId = $(this).data('marca-id');
                if (marcaId) {
                    saveMarcaData(marcaId);
                }
            });
        }, 30000);
    </script>

</body>
</html>

