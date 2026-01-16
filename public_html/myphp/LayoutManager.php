<?php

class LayoutManager {
    
    private static $instance = null;
    private $current_layout = 'base';
    private $layouts = [
        'base' => 'header_base.php',
        'chollos' => 'header_chollos.php', 
        'chat' => 'header_clean.php',
        'admin' => 'header_admin.php'
    ];
    
    // Prevent direct instantiation
    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new LayoutManager();
        }
        return self::$instance;
    }

    /**
     * Set the current layout context
     * @param string $layout_name
     */
    public function setLayout($layout_name) {
        if (array_key_exists($layout_name, $this->layouts)) {
            $this->current_layout = $layout_name;
        } else {
            // Fallback or log warning? For now fallback to base
            $this->current_layout = 'base';
        }
    }

    /**
     * Render the Header based on current layout
     * @param array $params Optional parameters to pass to the view
     */
    public function renderHeader($params = []) {
        global $author_web, $img_compartir_pagina, $ubicacion_actual, $force_css, $name_page;
        global $provincia, $data_usuario, $detect, $author_web, $datos_usuario, $que_es;
        global $noindex, $nombre_pag, $anula_adsense;
        
        // Extract params to be available in included file
        extract($params);
        
        // Setup default variables if not set (mirroring original _header_modern logic)
        
        // Ensure modern functions are available
        if (!function_exists('add_mobile_javascript')) {
            include_once __DIR__ . '/funciones_modern.php';
        }
        
         if (!isset($anula_adsense)) {
            $anula_adsense = isset($GLOBALS['anula_adsense']) ? $GLOBALS['anula_adsense'] : false;
        }
        if (!isset($noindex)) {
            $noindex = 0;
        }

        $layout_file = $this->layouts[$this->current_layout];
        $file_path = __DIR__ . '/layouts/' . $layout_file;

        if (file_exists($file_path)) {
            include $file_path;
        } else {
            // Fallback if specific layout file missing: use original monolithic header for safety for now
            // OR create a simple fallback. Let's try to notify dev.
            error_log("Layout file missing: " . $file_path);
            // Ideally we should have a reliable fallback.
            // For this refactor, I will ensure the files exist.
        }
    }
}
