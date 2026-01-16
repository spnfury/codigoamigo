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
    $tipo_beneficio = $codigo_data['tipo_beneficio'] ?? 'euros';
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
        $tipo_beneficio = $form_data['tipo_beneficio'] ?? 'euros';
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
        $beneficio = $tipo_beneficio = $codigo = $descripcion = $provincia = $localidad = $fecha_caducidad = '';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Cargar jQuery y Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        body {
            background: #2C2C2C;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
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
            border-color: #E30613;
        }

        .btn-custom {
            background: #E30613;
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
        }

        .btn-custom:hover {
            background: #C40510;
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

        /* Select2 styles específicos */
        .select2-container--default .select2-selection--single {
            background-color: white !important;
            border: 2px solid #555 !important;
            border-radius: 8px !important;
            height: 48px !important;
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

        .select2-dropdown {
            background-color: white !important;
            border: 1px solid #555 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }

        .select2-results__option {
            color: #333 !important;
            padding: 12px 15px !important;
        }

        .select2-results__option--highlighted {
            background-color: #E30613 !important;
            color: white !important;
        }

        /* Asegurar prioridad sobre otros estilos */
        .select2-container {
            z-index: 9999 !important;
        }

        .select2-dropdown {
            z-index: 10000 !important;
        }
    </style>
</head>
<body>
    <div class="container<?php echo $modo_modificacion ? ' modo-modificacion' : ''; ?>">
        <h1 class="page-title">
            <?php echo $modo_modificacion ? 'MODIFICAR CÓDIGO' : 'NUEVO CÓDIGO AMIGO'; ?>
        </h1>

        <p class="text-center" style="color: #ccc; margin-bottom: 30px;">
            Asegúrate de introducir correctamente estos datos.<br>
            Si tienes alguna duda, puedes comunicarte con nosotros en <strong>info@codigoamigo.com</strong>
        </p>

        <form id="nuevo_codigo" method="post" action="<?php echo $modo_modificacion ? '/modificar_codigo/' . $codigo_data['codigo_id'] : '/codigo_insertado'; ?>">
            <div class="form-group">
                <label for="marca">Marca o Servicio</label>
                <select class="form-control marca-selector" id="marca" name="marca" required style="width: 100%;">
                    <option value="">Busca o selecciona una marca</option>
                </select>
                <input type="hidden" id="marca_valor" name="marca_valor" value="<?php echo htmlspecialchars($marca); ?>">
                <div class="help-text">
                    Busca la marca a la que corresponde tu "Código amigo". Si no la encuentras, puedes añadirla fácilmente.
                </div>
            </div>

            <div class="form-group">
                <label for="num_beneficio">Beneficio económico</label>
                <div class="row">
                    <div class="col-md-6">
                        <input type="number" class="form-control" id="num_beneficio" name="num_beneficio" placeholder="0" value="<?php echo htmlspecialchars($beneficio); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <?php 
                        $standard_options = ['euros', 'porcentaje', 'minutos', 'creditos', 'tokens'];
                        $is_standard = in_array($tipo_beneficio, $standard_options);
                        $select_value = $is_standard ? $tipo_beneficio : 'otros';
                        $custom_value = $is_standard ? '' : $tipo_beneficio;
                        ?>
                        <div class="beneficio-selector-group">
                            <select class="form-control" id="tipo_beneficio_select">
                                <option value="euros" <?php echo ($select_value == 'euros') ? 'selected' : ''; ?>>euros (€)</option>
                                <option value="porcentaje" <?php echo ($select_value == 'porcentaje') ? 'selected' : ''; ?>>%</option>
                                <option value="minutos" <?php echo ($select_value == 'minutos') ? 'selected' : ''; ?>>Minutos Gratis</option>
                                <option value="creditos" <?php echo ($select_value == 'creditos') ? 'selected' : ''; ?>>Créditos</option>
                                <option value="tokens" <?php echo ($select_value == 'tokens') ? 'selected' : ''; ?>>Tokens</option>
                                <option value="otros" <?php echo ($select_value == 'otros') ? 'selected' : ''; ?>>Otros (Escribir texto)</option>
                            </select>
                            <input type="text" class="form-control" id="tipo_beneficio_custom" placeholder="Ej: Semanas, GB, Puntos..." value="<?php echo htmlspecialchars($custom_value); ?>" style="display: <?php echo ($select_value == 'otros') ? 'block' : 'none'; ?>; margin-top: 10px;">
                            <input type="hidden" name="tipo_beneficio" id="tipo_beneficio_final" value="<?php echo htmlspecialchars($tipo_beneficio); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            $(document).ready(function() {
                var $select = $('#tipo_beneficio_select');
                var $custom = $('#tipo_beneficio_custom');
                var $final = $('#tipo_beneficio_final');

                function updateFinalValue() {
                    if ($select.val() === 'otros') {
                        $custom.show();
                        $final.val($custom.val());
                        $custom.attr('required', true);
                    } else {
                        $custom.hide();
                        $final.val($select.val());
                        $custom.removeAttr('required');
                    }
                }

                $select.change(updateFinalValue);
                $custom.on('input', updateFinalValue);
                
                // Inicializar estado
                updateFinalValue();
            });
            </script>

            <div class="form-group">
                <label for="codigo">Código promocional o URL</label>
                <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Introduce tu código o URL" value="<?php echo htmlspecialchars($codigo); ?>" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="5" placeholder="Describe tu código amigo" required><?php echo htmlspecialchars($descripcion); ?></textarea>
            </div>

            <div class="warning-text">
                ⚠️ ESTÁ TOTALMENTE PROHIBIDO PONER EL CÓDIGO EN LA DESCRIPCIÓN. NO CUMPLIR ESTA NORMA CONLLEVA LA EXPULSIÓN DE LA COMUNIDAD.
            </div>

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

            <div class="text-center" style="margin-top: 30px;">
                <input type="submit"
                       value="<?php echo $modo_modificacion ? 'Modificar código' : 'Añadir código amigo'; ?>"
                       class="btn btn-custom" />
            </div>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        console.log("=== INICIANDO SELECTOR DE MARCAS ===");
        console.log("jQuery disponible:", typeof $ !== 'undefined');
        console.log("Select2 disponible:", typeof $.fn.select2 !== 'undefined');
        console.log("EasyAutocomplete disponible:", typeof $.fn.easyAutocomplete !== 'undefined');

        // Configurar Select2 para el selector de marcas
        $("#marca").select2({
            placeholder: "Busca o selecciona una marca",
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

        console.log("✅ Select2 configurado correctamente");

        // Manejar selección de marca
        $("#marca").on('select2:select', function (e) {
            var data = e.params.data;
            $("#marca_valor").val(data.nombre || data.text);
            console.log("Marca seleccionada:", data);
        });

        // Preseleccionar marca si estamos en modo modificación
        <?php if ($modo_modificacion && !empty($marca)): ?>
        var marcaNombre = '<?php echo htmlspecialchars($marca); ?>';
        var marcaOption = new Option(marcaNombre, marcaNombre, true, true);
        $("#marca").append(marcaOption).trigger('change');
        $("#marca_valor").val(marcaNombre);
        console.log("Marca preseleccionada:", marcaNombre);
        <?php endif; ?>

        console.log("=== SELECTOR DE MARCAS COMPLETADO ===");
    });
    </script>
</body>
</html>
