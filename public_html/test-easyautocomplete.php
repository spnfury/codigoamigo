<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test EasyAutoComplete</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.easy-autocomplete/1.3.5/jquery.easy-autocomplete.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.easy-autocomplete/1.3.5/easy-autocomplete.min.css">
</head>
<body>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px;">
        <h1>Test EasyAutoComplete</h1>
        
        <div>
            <label for="test-input">Campo de prueba:</label>
            <input type="text" id="test-input" placeholder="Escribe para probar..." style="width: 100%; padding: 10px; margin: 10px 0;">
        </div>
        
        <div id="debug-info" style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
            <h3>Debug Info:</h3>
            <p id="jquery-status">jQuery: <span id="jquery-version">Cargando...</span></p>
            <p id="autocomplete-status">EasyAutoComplete: <span id="autocomplete-version">Cargando...</span></p>
        </div>
    </div>

    <script>
        // Verificar jQuery
        $(document).ready(function() {
            $('#jquery-version').text(typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'No cargado');
            
            if (typeof jQuery.fn.easyAutocomplete !== 'undefined') {
                $('#autocomplete-version').text('Cargado correctamente');
                
                // Configurar autocompletado simple para prueba
                var testOptions = {
                    data: ["Apple", "Banana", "Cherry", "Date", "Elderberry", "Fig", "Grape"],
                    maxListSize: 5
                };
                
                $("#test-input").easyAutocomplete(testOptions);
            } else {
                $('#autocomplete-version').text('No cargado');
            }
        });

        // Verificar errores de JavaScript
        window.onerror = function(message, source, lineno, colno, error) {
            console.error('JavaScript Error:', message, 'at', source, ':', lineno);
            alert('Error: ' + message + ' en línea ' + lineno);
            return false;
        };
    </script>
</body>
</html>
