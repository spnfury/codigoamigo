<?php
    // Deshabilitar AdSense en esta página
    $anula_adsense = true;
    
    global $array_descuentos;
    
    $modo_modificacion = isset($codigo_data);

    if ($modo_modificacion) {
        $marca = $codigo_data['marca'];
        $beneficio = $codigo_data['num_beneficio'];
        $descuento = $codigo_data['descuento'];
        $codigo = $codigo_data['codigo'];
        $descripcion = $codigo_data['descripcion'];
        $provincia = $codigo_data['provincia'];
        $localidad = $codigo_data['localidad'];
        $fecha_caducidad = $codigo_data['fecha_caducidad'];
    } else {
        // Si hay una marca en la URL, preseleccionarla
        $marca = isset($_GET['marca']) ? strtoupper($_GET['marca']) : '';
        $beneficio = $descuento = $codigo = $descripcion = $provincia = $localidad = $fecha_caducidad = '';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #2C2C2C;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: #ff6b35;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        .back-button:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }
        .back-button i {
            margin-right: 8px;
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
            border-color: #ff6b35;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }
        .btn-custom {
            background: #ff6b35;
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }
        .alert {
            border-radius: 8px;
            font-size: 16px;
            margin: 20px 0;
        }
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff6b35;
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
            color: #ff6b35;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            font-size: 16px;
        }
        .easy-autocomplete {
            width: 100% !important;
            position: relative !important;
        }
        .easy-autocomplete input {
            border-radius: 10px !important;
            padding: 15px 20px !important;
            font-size: 16px !important;
            background: white !important;
            color: #333 !important;
        }
        .easy-autocomplete input:focus {
            border-color: #ff6b35 !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25) !important;
        }
        .easy-autocomplete ul {
            background: white !important;
            border: 1px solid #ddd !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }
        .easy-autocomplete li {
            color: #333 !important;
            padding: 10px 15px !important;
            border-bottom: 1px solid #eee !important;
        }
        .easy-autocomplete li:hover {
            background: #f8f9fa !important;
        }
        .easy-autocomplete .eac-category {
            background: #ff6b35 !important;
            color: white !important;
            font-weight: bold !important;
        }
    </style>
</head>
<body>
    <button class="back-button" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i>
        Volver
    </button>

    <div class="container">
        <h1 class="page-title">
            <?php echo $modo_modificacion ? 'MODIFICAR CÓDIGO' : 'NUEVO CÓDIGO AMIGO'; ?>
        </h1>

        <?php if(isset($_SESSION['msg_error']) && $_SESSION['msg_error'] != "") { ?>
            <div class="alert alert-danger" style="background: #f44336; color: white; padding: 15px; border-radius: 8px; margin: 20px 0; border: none; font-size: 1.6rem; text-align: center; box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);">
                <i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i>
                <?php echo $_SESSION['msg_error']; ?>
            </div>
            <?php unset($_SESSION['msg_error']); ?>
        <?php } ?>

        <p class="text-center" style="color: #ccc; margin-bottom: 30px;">
            Asegúrate de introducir correctamente estos datos.<br>
            Si tienes alguna duda, puedes comunicarte con nosotros en <strong>info@codigoamigo.com</strong>
        </p>

        <form id="nuevo_codigo" method="post" action="<?php echo $modo_modificacion ? '/modificar_codigo/' . $codigo_data['codigo_id'] : '/codigo_insertado'; ?>">
            <div class="form-group">
                <label for="marca">Marca o Servicio</label>
                <input type="text" class="form-control" id="marca" name="marca" placeholder="Busca la marca" value="<?php echo htmlspecialchars($marca); ?>" required>
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
                        <select class="form-control" name="tipo_beneficio">
                            <option value="euros" <?php echo ($descuento == 'euros') ? 'selected' : ''; ?>>euros</option>
                            <option value="porcentaje" <?php echo ($descuento == 'porcentaje') ? 'selected' : ''; ?>>%</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="codigo">Código promocional o URL</label>
                <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Introduce tu código o URL" value="<?php echo htmlspecialchars($codigo); ?>" required>
            </div>

            <div class="form-group">
                <label for="descuento">Código Simple (opcional)</label>
                <input type="text" class="form-control" id="descuento" name="descuento" placeholder="Introduce tu código promocional" value="<?php echo htmlspecialchars($descuento); ?>">
                <div class="help-text">
                    Si has publicado una URL en el código anterior, introduce aquí el código simple.
                </div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/jquery.easy-autocomplete.min.js"></script>
    <link rel="stylesheet" href="/css/easy-autocomplete.min.css?v=<?php echo time(); ?>">
    
    <script>
    $(document).ready(function() {
        // Autocompletado de marcas
        var options = {
            url: function(phrase) {
                return "/ajax/buscar_marcas.php?q=" + phrase;
            },
            getValue: "nombre",
            template: {
                type: "custom",
                method: function(value, item) {
                    return "<div style='display: flex; align-items: center;'><img src='" + (item.imagen || '/img/no_image.png') + "' style='width: 30px; height: 30px; margin-right: 10px; border-radius: 5px;'><span>" + value + "</span></div>";
                }
            },
            list: {
                onChooseEvent: function() {
                    var selectedItemData = $("#marca").getSelectedItemData();
                    console.log("Marca seleccionada:", selectedItemData);
                }
            },
            onShowListEvent: function() {
                console.log("Mostrando lista de marcas");
            }
        };
        
        $("#marca").easyAutocomplete(options);
    });
    </script>
    
<?php get_footer(); ?>
</body>
</html>
