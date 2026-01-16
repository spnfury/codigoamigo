<?php
// Versión simplificada para debug del selector de marcas
$anula_adsense = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Código - Código Amigo</title>
    <link rel="shortcut icon" href="/img/favicon_moneda_real.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: bold;
            color: #E30613;
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
            background-color: #E30613 !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">NUEVO CÓDIGO AMIGO</h1>

        <form id="nuevo_codigo" method="post" action="/codigo_insertado">
            <div class="form-group">
                <label for="marca">Marca o Servicio</label>
                <select class="form-control marca-selector" id="marca" name="marca" required style="width: 100%;">
                    <option value="">Busca o selecciona una marca</option>
                </select>
                <input type="hidden" id="marca_valor" name="marca_valor" value="">
                <div class="help-text" style="color: #ccc; font-size: 14px; margin-top: 5px;">
                    Busca la marca a la que corresponde tu código. Si no la encuentras, puedes añadirla fácilmente.
                </div>
            </div>

            <div class="form-group">
                <label for="beneficio">Beneficio económico</label>
                <input type="number" class="form-control" id="beneficio" name="num_beneficio" placeholder="0" required>
            </div>

            <div class="form-group">
                <label for="codigo">Código promocional o URL</label>
                <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Introduce tu código o URL" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" placeholder="Describe tu código amigo" required></textarea>
            </div>

            <div class="text-center" style="margin-top: 30px;">
                <input type="submit" value="Añadir código amigo" class="btn btn-custom" />
            </div>
        </form>
    </div>

    <!-- Cargar jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function() {
        console.log("Inicializando selector de marcas...");

        // Configurar Select2
        $("#marca").select2({
            placeholder: "Busca o selecciona una marca",
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
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

        // Manejar selección de marca
        $("#marca").on('select2:select', function (e) {
            var data = e.params.data;
            $("#marca_valor").val(data.nombre || data.text);
            console.log("Marca seleccionada:", data);
        });

        console.log("Select2 configurado correctamente");
    });
    </script>
</body>
</html>
