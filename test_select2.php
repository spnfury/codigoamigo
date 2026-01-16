<?php
// Página de prueba para verificar que Select2 funciona correctamente sin conflictos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Select2 - Código Amigo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        body {
            background: #2C2C2C;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
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
            padding: 12px 15px;
            font-size: 16px;
        }

        .form-control:focus {
            border-color: #ff6b35;
        }

        /* Select2 styles */
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
            background-color: #ff6b35 !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">TEST SELECT2 - SIN CONFLICTOS</h1>

        <div class="form-group">
            <label for="marca_test">Marca o Servicio (Select2)</label>
            <select class="form-control" id="marca_test" name="marca_test" required style="width: 100%;">
                <option value="">Busca o selecciona una marca</option>
            </select>
            <div class="help-text" style="color: #ccc; font-size: 14px; margin-top: 5px;">
                Este selector usa Select2 y NO debería tener conflictos con EasyAutocomplete.
            </div>
        </div>

        <div class="form-group">
            <label for="campo_normal">Campo de texto normal</label>
            <input type="text" class="form-control" id="campo_normal" name="campo_normal" placeholder="Campo normal sin autocompletado">
        </div>
    </div>

    <script>
    $(document).ready(function() {
        console.log("=== TEST SELECT2 - INICIANDO ===");

        // Verificar que jQuery esté disponible
        console.log("jQuery disponible:", typeof $ !== 'undefined');

        // Verificar que Select2 esté disponible
        console.log("Select2 disponible:", typeof $.fn.select2 !== 'undefined');

        // Verificar que NO haya conflictos con EasyAutocomplete
        console.log("EasyAutocomplete disponible:", typeof $.fn.easyAutocomplete !== 'undefined');

        // Configurar Select2 para el selector de marcas
        try {
            $("#marca_test").select2({
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
        } catch (error) {
            console.error("❌ Error configurando Select2:", error);
        }

        // Manejar selección de marca
        $("#marca_test").on('select2:select', function (e) {
            var data = e.params.data;
            console.log("✅ Marca seleccionada:", data);
        });

        console.log("=== TEST SELECT2 - COMPLETADO ===");
    });
    </script>
</body>
</html>
