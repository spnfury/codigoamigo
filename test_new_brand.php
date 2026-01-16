<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Nueva Marca - Código Amigo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">

    <!-- Cargar jQuery y Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        body {
            background: #2C2C2C;
            color: white;
            font-family: Arial, sans-serif;
            padding: 40px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-control {
            background: white;
            color: #333;
            border: 2px solid #555;
            border-radius: 8px;
            padding: 2px 15px;
            font-size: 16px;
        }

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
            font-size: 16px !important;
        }

        .debug-info {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
        }

        .test-result {
            background: #28a745;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Nueva Marca - Debug</h1>

        <div class="debug-info">
            <h4>🐛 Estado Actual:</h4>
            <div id="debug-output"></div>
        </div>

        <form id="test_form">
            <div class="form-group">
                <label for="marca">Marca o Servicio</label>
                <select class="form-control" id="marca" name="marca" required style="width: 100%;">
                    <option value="">Busca o selecciona una marca</option>
                </select>
                <input type="hidden" id="marca_valor" name="marca_valor" value="">
                <input type="hidden" id="creada" name="creada" value="0">
            </div>

            <div class="form-group" id="div_nueva_marca" style="display: none;">
                <label>Nombre de nueva marca:</label>
                <input type="text" class="form-control" id="nombre_nuevo_display" readonly>
                <button type="button" class="btn btn-danger" id="cancelar">Cancelar</button>
            </div>

            <button type="submit" class="btn btn-primary">Enviar (Ver logs)</button>
        </form>

        <div id="test-results"></div>
    </div>

    <script>
    // Función de debug
    function debug(msg) {
        const debugDiv = document.getElementById('debug-output');
        debugDiv.innerHTML += '<div>' + new Date().toLocaleTimeString() + ' - ' + msg + '</div>';
        console.log(msg);
    }

    $(document).ready(function() {
        debug('=== INICIANDO TEST ===');

        // Configurar Select2
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
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    debug('Datos recibidos: ' + data.length + ' marcas');
                    return { results: data };
                },
                cache: true
            }
        });

        // Manejar selección de marca
        $("#marca").on('select2:select', function (e) {
            var data = e.params.data;
            debug('Marca seleccionada: ' + JSON.stringify(data));

            if (data.is_new) {
                debug('✅ Nueva marca detectada');
                $('#creada').val(1);
                $("#marca_valor").val(data.nombre);
                debug('✅ marca_valor establecido: ' + data.nombre);
                $("#div_nueva_marca").show();
                $("#nombre_nuevo_display").val(data.nombre);
            } else {
                debug('📦 Marca existente');
                $("#marca_valor").val(data.nombre);
                $("#div_nueva_marca").hide();
            }
        });

        // Manejar submit del formulario
        $('#test_form').on('submit', function(e) {
            e.preventDefault();
            debug('=== VALIDACIÓN DEL FORMULARIO ===');

            var marcaValue = $("#marca_valor").val();
            var creadaValue = $("#creada").val();

            debug('marca_valor: "' + marcaValue + '"');
            debug('creada: "' + creadaValue + '"');

            if (marcaValue === '') {
                debug('❌ ERROR: marca_valor está vacío');
                $("#test-results").html('<div class="test-result" style="background: #dc3545;">❌ ERROR: El campo marca está vacío</div>');
            } else {
                debug('✅ Marca válida: ' + marcaValue);
                $("#test-results").html('<div class="test-result">✅ Marca válida: ' + marcaValue + '</div>');
            }
        });

        // Manejar cancelar
        $(document).on("click", "#cancelar", function() {
            debug('🔄 Cancelando nueva marca');
            $("#marca").show();
            $("#div_nueva_marca").hide();
            $('#creada').val(0);
            $("#marca").val('').trigger('change');
            $("#marca_valor").val('');
            debug('✅ Valores reseteados');
        });

        debug('=== TEST LISTO ===');
    });
    </script>
</body>
</html>

