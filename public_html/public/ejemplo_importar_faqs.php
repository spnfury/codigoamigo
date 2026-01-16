<?php
/**
 * Ejemplo de cómo importar FAQs en bloque
 * Este archivo muestra el formato correcto para copiar y pegar FAQs
 */

session_start();

// Verificar permisos de admin
if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != 1) {
    die("Acceso denegado");
}

// Incluir funciones
include_once __DIR__ . '/../myphp/funciones_faq.php';
include_once __DIR__ . '/../myphp/funciones_marca.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_POST) {
    $marca_clave = $_POST['marca_clave'] ?? '';
    $texto_faqs = $_POST['texto_faqs'] ?? '';
    
    if (!empty($marca_clave) && !empty($texto_faqs)) {
        $faqs_creadas = importarFAQsBulk($marca_clave, $texto_faqs);
        if ($faqs_creadas) {
            $mensaje = count($faqs_creadas) . ' FAQs importadas exitosamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al importar las FAQs';
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener todas las marcas
$todas_marcas = get_all_marcas_panel_control(1000);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplo Importación FAQs - CodigoAmigo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .example-text {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            font-family: monospace;
            white-space: pre-line;
            margin: 20px 0;
        }
        .step {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-upload"></i> Ejemplo de Importación Masiva de FAQs</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Formato de Importación</h5>
                    </div>
                    <div class="card-body">
                        <div class="step">
                            <strong>Paso 1:</strong> Selecciona la marca para la cual quieres importar FAQs
                        </div>
                        
                        <div class="step">
                            <strong>Paso 2:</strong> Copia y pega las preguntas y respuestas en el formato mostrado abajo
                        </div>
                        
                        <div class="step">
                            <strong>Paso 3:</strong> Haz clic en "Importar FAQs" para procesar el texto
                        </div>
                        
                        <h6>Formato correcto:</h6>
                        <div class="example-text">¿Qué es esta marca?
Esta marca es una plataforma que ofrece servicios de...

¿Cómo me registro?
Para registrarte, ve a la página web oficial y...

¿Dónde encuentro códigos de descuento?
Los códigos están disponibles en nuestra página...

¿Cuánto tiempo tardan en llegar los beneficios?
Los beneficios suelen llegar en un plazo de...

¿Hay algún costo oculto?
No, todos los costos están claramente especificados...</div>
                        
                        <h6>Formulario de importación:</h6>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="marca_clave" class="form-label">Seleccionar Marca</label>
                                <select name="marca_clave" id="marca_clave" class="form-select" required>
                                    <option value="">Selecciona una marca...</option>
                                    <?php foreach ($todas_marcas as $marca): ?>
                                        <option value="<?php echo htmlspecialchars($marca['nombre_clave']); ?>">
                                            <?php echo htmlspecialchars($marca['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="texto_faqs" class="form-label">Preguntas y Respuestas</label>
                                <textarea name="texto_faqs" id="texto_faqs" class="form-control" rows="15" 
                                          placeholder="Pega aquí las preguntas y respuestas en el formato mostrado arriba..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Importar FAQs
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Consejos de Importación</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                Cada pregunta debe terminar con "?"
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                La respuesta va en las líneas siguientes
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                Deja una línea en blanco entre preguntas
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                Las preguntas se ordenan automáticamente
                            </li>
                        </ul>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Nota:</strong> Las FAQs importadas se marcan como activas por defecto y se pueden editar después desde el panel de administración.
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Ejemplo Completo</h5>
                    </div>
                    <div class="card-body">
                        <div class="example-text">¿Qué es Amazon?
Amazon es una plataforma de comercio electrónico que permite comprar productos de todo tipo online.

¿Cómo me registro en Amazon?
Ve a amazon.es, haz clic en "Mi cuenta" y luego en "Crear cuenta".

¿Dónde encuentro códigos de descuento de Amazon?
Los códigos están disponibles en nuestra página y se actualizan regularmente.

¿Cuánto tiempo tardan en llegar los pedidos?
Los pedidos suelen llegar en 1-2 días laborables con Prime.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="/public/admin_faqs.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Panel de Administración
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
