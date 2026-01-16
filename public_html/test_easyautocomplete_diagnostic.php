<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test EasyAutocomplete - Diagnóstico</title>
    
    <!-- Cargar jQuery primero -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Cargar EasyAutocomplete -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/easy-autocomplete/1.3.5/jquery.easy-autocomplete.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/easy-autocomplete/1.3.5/easy-autocomplete.min.css">
    
    <style>
        body {
            background: #2C2C2C;
            color: white;
            font-family: Arial, sans-serif;
            padding: 40px;
        }
        
        .diagnostic-box {
            background: #1a1a1a;
            border: 2px solid #E30613;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin: 5px 0;
        }
        
        .status.success {
            background: #28a745;
        }
        
        .status.error {
            background: #dc3545;
        }
        
        input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #555;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico EasyAutocomplete</h1>
    
    <div class="diagnostic-box">
        <h3>Estado de Librerías:</h3>
        <div id="library-status"></div>
    </div>
    
    <div class="diagnostic-box">
        <h3>Campo de Prueba:</h3>
        <input type="text" id="test-field" placeholder="Escribe aquí para probar autocompletado...">
    </div>
    
    <div class="diagnostic-box">
        <h3>Console Log:</h3>
        <div id="console-log" style="font-family: monospace; font-size: 12px;"></div>
    </div>
    
    <script>
    // Función para log personalizado
    function log(msg, type) {
        type = type || 'info';
        var consoleDiv = document.getElementById('console-log');
        var timestamp = new Date().toLocaleTimeString();
        var color = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8';
        consoleDiv.innerHTML += '<div style="color: ' + color + ';">[' + timestamp + '] ' + msg + '</div>';
        console.log(msg);
    }
    
    // Diagnóstico inmediato
    log('=== INICIO DEL DIAGNÓSTICO ===');
    log('jQuery cargado: ' + (typeof jQuery !== 'undefined' ? 'SÍ (v' + jQuery.fn.jquery + ')' : 'NO'), 
        typeof jQuery !== 'undefined' ? 'success' : 'error');
    
    var statusDiv = document.getElementById('library-status');
    
    // Verificar jQuery
    if (typeof jQuery !== 'undefined') {
        statusDiv.innerHTML += '<div class="status success">✅ jQuery: v' + jQuery.fn.jquery + '</div><br>';
    } else {
        statusDiv.innerHTML += '<div class="status error">❌ jQuery: NO DISPONIBLE</div><br>';
    }
    
    // Esperar a que el DOM esté listo
    $(document).ready(function() {
        log('DOM Ready ejecutado');
        
        // Verificar EasyAutocomplete
        setTimeout(function() {
            log('Verificando EasyAutocomplete...');
            
            if (typeof $.fn.easyAutocomplete !== 'undefined') {
                statusDiv.innerHTML += '<div class="status success">✅ EasyAutocomplete: DISPONIBLE</div><br>';
                log('EasyAutocomplete disponible', 'success');
                
                // Configurar EasyAutocomplete
                try {
                    var options = {
                        url: function(phrase) {
                            return "/ajax/buscar_marcas.php?q=" + phrase;
                        },
                        getValue: "nombre",
                        ajaxSettings: {
                            dataType: "json",
                            method: "GET",
                            data: {
                                dataType: "json"
                            }
                        },
                        list: {
                            maxNumberOfElements: 10,
                            match: {
                                enabled: true
                            }
                        }
                    };
                    
                    $("#test-field").easyAutocomplete(options);
                    log('✅ EasyAutocomplete aplicado al campo de prueba', 'success');
                    statusDiv.innerHTML += '<div class="status success">✅ Autocompletado configurado correctamente</div><br>';
                } catch (error) {
                    log('❌ Error aplicando EasyAutocomplete: ' + error.message, 'error');
                    statusDiv.innerHTML += '<div class="status error">❌ Error: ' + error.message + '</div><br>';
                }
            } else {
                statusDiv.innerHTML += '<div class="status error">❌ EasyAutocomplete: NO DISPONIBLE</div><br>';
                log('EasyAutocomplete NO disponible', 'error');
            }
            
            log('=== FIN DEL DIAGNÓSTICO ===');
        }, 1000);
    });
    
    // Verificación adicional cuando la página está completamente cargada
    $(window).on('load', function() {
        log('Evento window.load ejecutado');
        log('Estado final - EasyAutocomplete: ' + (typeof $.fn.easyAutocomplete !== 'undefined' ? 'DISPONIBLE' : 'NO DISPONIBLE'));
    });
    </script>
</body>
</html>

