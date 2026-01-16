<?php
/**
 * Funciones para procesar PDFs y convertirlos a imágenes por página
 */

/**
 * Procesa un PDF y convierte cada página a una imagen
 * @param string $pdf_path Ruta al archivo PDF
 * @param string $output_dir Directorio donde guardar las imágenes
 * @param string $codigo_id ID del código para nombrar las imágenes
 * @return array|false Array con las URLs de las imágenes o false si hay error
 */
function procesarPDF($pdf_path, $output_dir, $codigo_id) {
    try {
        // Verificar que el archivo PDF existe
        if (!file_exists($pdf_path)) {
            throw new Exception('El archivo PDF no existe');
        }

        // Crear el directorio de salida si no existe
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0755, true);
        }

        // Intentar usar convert primero (ImageMagick CLI - puede funcionar aunque Imagick PHP esté bloqueado)
        $convert_path = trim(shell_exec('which convert 2>/dev/null'));
        if (!empty($convert_path)) {
            try {
                return procesarPDFConConvert($pdf_path, $output_dir, $codigo_id, $convert_path);
            } catch (Exception $e) {
                // Si convert falla, continuar con otras opciones
                if (function_exists('log_warning')) {
                    log_warning("convert falló, intentando alternativa", ['error' => $e->getMessage()]);
                }
            }
        }
        
        // Intentar usar pdftoppm (preferido, más rápido, no afectado por políticas de Imagick)
        $pdftoppm_path = trim(shell_exec('which pdftoppm 2>/dev/null'));
        if (!empty($pdftoppm_path)) {
            return procesarPDFConPdftoppm($pdf_path, $output_dir, $codigo_id, $pdftoppm_path);
        }
        
        // Como último recurso, intentar con Imagick PHP (puede fallar por políticas)
        if (extension_loaded('imagick')) {
            return procesarPDFConImagick($pdf_path, $output_dir, $codigo_id);
        }
        
        throw new Exception('No se encontró ninguna herramienta para procesar PDFs (pdftoppm, convert, o imagick)');
        
    } catch (Exception $e) {
        // Log del error (sin usar error_log según las reglas del usuario)
        if (function_exists('log_error')) {
            log_error("Error procesando PDF", ['error' => $e->getMessage(), 'pdf_path' => $pdf_path]);
        }
        return false;
    }
}

/**
 * Procesa PDF usando pdftoppm (Ghostscript)
 */
function procesarPDFConPdftoppm($pdf_path, $output_dir, $codigo_id, $pdftoppm_path) {
    // Base para nombres de archivo
    $base_name = $output_dir . '/' . $codigo_id . '_pagina';
    
    // Comando: pdftoppm -jpeg -r 300 archivo.pdf salida
    // -jpeg: salida en JPEG
    // -r 300: resolución 300 DPI
    $command = escapeshellarg($pdftoppm_path) . ' -jpeg -r 300 ' . 
               escapeshellarg($pdf_path) . ' ' . escapeshellarg($base_name) . ' 2>&1';
    
    exec($command, $output, $return_code);
    
    if ($return_code !== 0) {
        throw new Exception('Error ejecutando pdftoppm: ' . implode("\n", $output));
    }
    
    // Buscar archivos generados
    $imagenes = [];
    $pattern = $base_name . '-*.jpg';
    $files = glob($pattern);
    
    if (empty($files)) {
        throw new Exception('No se generaron imágenes del PDF');
    }
    
    // Ordenar archivos por nombre (que incluye el número de página)
    sort($files);
    
    foreach ($files as $index => $file) {
        // Renombrar a formato estándar
        $new_name = $output_dir . '/' . $codigo_id . '_pagina_' . ($index + 1) . '.jpg';
        rename($file, $new_name);
        
        // Generar URL relativa
        $url_relativa = str_replace($_SERVER['DOCUMENT_ROOT'], '', $new_name);
        $url_relativa = str_replace('\\', '/', $url_relativa);
        
        if (substr($url_relativa, 0, 1) !== '/') {
            $url_relativa = '/' . $url_relativa;
        }
        
        $imagenes[] = [
            'pagina' => $index + 1,
            'url' => $url_relativa,
            'ruta' => $new_name
        ];
    }
    
    return $imagenes;
}

/**
 * Procesa PDF usando convert (ImageMagick CLI)
 */
function procesarPDFConConvert($pdf_path, $output_dir, $codigo_id, $convert_path) {
    // Obtener número de páginas primero
    $info_command = escapeshellarg($convert_path) . ' ' . escapeshellarg($pdf_path) . ' -format "%n" info: 2>&1';
    $page_count = trim(shell_exec($info_command));
    
    if (empty($page_count) || !is_numeric($page_count)) {
        throw new Exception('No se pudo determinar el número de páginas del PDF');
    }
    
    $num_pages = (int)$page_count;
    if ($num_pages == 0) {
        throw new Exception('El PDF no tiene páginas');
    }
    
    $imagenes = [];
    
    // Procesar cada página
    for ($i = 0; $i < $num_pages; $i++) {
        $nombre_archivo = $codigo_id . '_pagina_' . ($i + 1) . '.jpg';
        $ruta_completa = $output_dir . '/' . $nombre_archivo;
        
        // Comando: convert -density 300 archivo.pdf[0] -quality 85 salida.jpg
        $command = escapeshellarg($convert_path) . 
                   ' -density 300 ' . 
                   escapeshellarg($pdf_path . '[' . $i . ']') . 
                   ' -quality 85 ' . 
                   escapeshellarg($ruta_completa) . ' 2>&1';
        
        exec($command, $output, $return_code);
        
        if ($return_code === 0 && file_exists($ruta_completa)) {
            // Generar URL relativa
            $url_relativa = str_replace($_SERVER['DOCUMENT_ROOT'], '', $ruta_completa);
            $url_relativa = str_replace('\\', '/', $url_relativa);
            
            if (substr($url_relativa, 0, 1) !== '/') {
                $url_relativa = '/' . $url_relativa;
            }
            
            $imagenes[] = [
                'pagina' => $i + 1,
                'url' => $url_relativa,
                'ruta' => $ruta_completa
            ];
        }
    }
    
    if (empty($imagenes)) {
        throw new Exception('No se pudieron generar imágenes del PDF');
    }
    
    return $imagenes;
}

/**
 * Procesa PDF usando Imagick PHP (puede fallar por políticas de seguridad)
 */
function procesarPDFConImagick($pdf_path, $output_dir, $codigo_id) {
    // Crear objeto Imagick
    $imagick = new Imagick();
    
    // Configurar resolución para buena calidad (300 DPI)
    $imagick->setResolution(300, 300);
    
    // Leer el PDF
    $imagick->readImage($pdf_path);
    
    // Obtener número de páginas
    $num_pages = $imagick->getNumberImages();
    
    if ($num_pages == 0) {
        throw new Exception('El PDF no tiene páginas');
    }

    $imagenes = [];
    
    // Procesar cada página
    for ($i = 0; $i < $num_pages; $i++) {
        // Seleccionar la página actual
        $imagick->setIteratorIndex($i);
        
        // Configurar formato de salida
        $imagick->setImageFormat('jpg');
        
        // Configurar calidad de compresión
        $imagick->setImageCompressionQuality(85);
        
        // Nombre del archivo de salida
        $nombre_archivo = $codigo_id . '_pagina_' . ($i + 1) . '.jpg';
        $ruta_completa = $output_dir . '/' . $nombre_archivo;
        
        // Escribir la imagen
        if ($imagick->writeImage($ruta_completa)) {
            // Generar URL relativa
            $url_relativa = str_replace($_SERVER['DOCUMENT_ROOT'], '', $ruta_completa);
            $url_relativa = str_replace('\\', '/', $url_relativa);
            
            if (substr($url_relativa, 0, 1) !== '/') {
                $url_relativa = '/' . $url_relativa;
            }
            
            $imagenes[] = [
                'pagina' => $i + 1,
                'url' => $url_relativa,
                'ruta' => $ruta_completa
            ];
        }
    }
    
    // Liberar recursos
    $imagick->clear();
    $imagick->destroy();
    
    return $imagenes;
}

/**
 * Guarda las páginas del PDF en MongoDB asociadas a un código
 * @param string $codigo_id ID del código
 * @param array $paginas Array de URLs de las páginas
 * @return bool True si se guardó correctamente, false en caso contrario
 */
function guardarPaginasPDF($codigo_id, $paginas) {
    try {
        $collection_codigos = getCollectionCodigos();
        
        $result = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            ['$set' => [
                'pdf_paginas' => $paginas,
                'tiene_pdf' => true,
                'fecha_modificacion' => date('Y-m-d H:i:s'),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]]
        );
        
        return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        
    } catch (Exception $e) {
        if (function_exists('log_error')) {
            log_error("Error guardando páginas PDF", ['error' => $e->getMessage(), 'codigo_id' => $codigo_id]);
        }
        return false;
    }
}

/**
 * Obtiene las páginas del PDF de un código
 * @param string $codigo_id ID del código
 * @return array|false Array de páginas o false si no hay
 */
function obtenerPaginasPDF($codigo_id) {
    try {
        $collection_codigos = getCollectionCodigos();
        
        $codigo = $collection_codigos->findOne([
            '_id' => new MongoDB\BSON\ObjectId($codigo_id)
        ], [
            'projection' => ['pdf_paginas' => 1, 'tiene_pdf' => 1]
        ]);
        
        if ($codigo && isset($codigo['pdf_paginas']) && !empty($codigo['pdf_paginas'])) {
            return iterator_to_array($codigo['pdf_paginas']);
        }
        
        return false;
        
    } catch (Exception $e) {
        if (function_exists('log_error')) {
            log_error("Error obteniendo páginas PDF", ['error' => $e->getMessage(), 'codigo_id' => $codigo_id]);
        }
        return false;
    }
}

?>

