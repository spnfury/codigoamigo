<?php
require_once __DIR__ . '/LayoutManager.php';
require_once __DIR__ . '/funciones_modern.php';

function get_header_modern($title = "", $description = "", $title_social = "", $description_social = "", $imagen_social = "", $links_meta = '') {
    $params = [
        'title' => $title,
        'description' => $description,
        'title_social' => $title_social,
        'description_social' => $description_social,
        'imagen_social' => $imagen_social,
        'links_meta' => $links_meta
    ];

    $layout = 'base';

    $manager = LayoutManager::getInstance();
    $manager->setLayout($layout);
    $manager->renderHeader($params);
}

// Alias para compatibilidad con código existente
function get_header_new($title = "", $description = "", $title_social = "", $description_social = "", $imagen_social = "", $links_meta = '') {
    return get_header_modern($title, $description, $title_social, $description_social, $imagen_social, $links_meta);
}
