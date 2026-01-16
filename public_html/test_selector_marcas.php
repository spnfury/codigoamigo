<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Selector de Marcas - Select2</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Select2 JS -->
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
        
        .test-box {
            background: #1a1a1a;
            border: 2px solid #E30613;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }
        
        .form-control {
            background: white;
            color: #333;
            border: 2px solid #555;
            border-radius: 8px;
            padding: 12px 15px;
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
        
        .select2-dropdown {
            background-color: white !important;
            border: 1px solid #555 !important;
            border-radius: 8px !important;
        }
        
        .select2-results__option {
            color: #333 !important;
            padding: 12px 15px !important;
        }
        
        .select2-results__option--highlighted {
            background-color: #E30613 !important;
            color: white !important;
        }
        
        .debug-log {
            background: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .success {
            color: #28a745;
        }
        
        .error {
            color: #dc3545;
        }
        
        .info {
            color: #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #E30613; text-align: center; margin-bottom: 30px;">
            🔍 Test Selector de Marcas con Select2
        </h1>
        
        <div class="test-box">
            <h3>Selector de Marcas:</h3>
            <div class="form-group">
                <label for="marca">Busca una marca:</label>
                <select class="form-control" id="marca" name="marca" style="width: 100%;">
                    <option value="">Escribe para buscar...</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Marca Seleccionada:</label>
                <input type="text" class="form-control" id="marca_seleccionada" readonly placeholder="Ninguna">
            </div>
        </div>
        
        <div class="test-box">
            <h3>Debug Log:</h3>
            <div class="debug-log" id="debug-log"></div>
        </div>
    </div>
    
    <script>
    // Función de logging
    function log(msg, type) {
        type = type || 'info';
        var timestamp = new Date().toLocaleTimeString();
        var logDiv = document.getElementById('debug-log');
        var className = type === 'success' ? 'success' : type === 'error' ? 'error' : 'info';
        logDiv.innerHTML += '<div class="' + className + '">[' + timestamp + '] ' + msg + '</div>';
        console.log(msg);
        logDiv.scrollTop = logDiv.scrollHeight;
    }
    
    $(document).ready(function() {
        log('=== INICIO DEL TEST ===');
        log('jQuery disponible: ' + (typeof $ !== 'undefined'), 'success');
        log('Select2 disponible: ' + (typeof $.fn.select2 !== 'undefined'), 'success');
        
        // Configurar Select2
        try {
            log('Configurando Select2...');
            
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
                        log('Consulta AJAX: "' + (params.term || '(vacío)') + '"', 'info');
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        log('Resultados recibidos: ' + data.length + ' marcas', 'success');
                        if (data.length > 0) {
                            log('Primera marca: ' + data[0].text, 'info');
                        }
                        return {
                            results: data
                        };
                    },
                    error: function(xhr, status, error) {
                        log('Error en AJAX: ' + error, 'error');
                        log('Status: ' + status, 'error');
                    },
                    cache: true
                }
            });
            
            log('✅ Select2 configurado correctamente', 'success');
            
            // Manejar selección
            $("#marca").on('select2:select', function (e) {
                var data = e.params.data;
                log('✅ Marca seleccionada: ' + data.text, 'success');
                $("#marca_seleccionada").val(data.text);
                
                if (data.is_new) {
                    log('⚠️ Esta es una NUEVA marca', 'info');
                } else {
                    log('📦 Marca existente: ' + JSON.stringify(data), 'info');
                }
            });
            
            // Manejar apertura del dropdown
            $("#marca").on('select2:opening', function (e) {
                log('📂 Abriendo dropdown...', 'info');
            });
            
            // Manejar cierre del dropdown
            $("#marca").on('select2:close', function (e) {
                log('📁 Cerrando dropdown', 'info');
            });
            
        } catch (error) {
            log('❌ Error configurando Select2: ' + error.message, 'error');
        }
        
        log('=== TEST COMPLETADO ===');
    });
    </script>
</body>
</html>

