<?php

// Incluir funciones de FAQs
if (!function_exists('getFAQsByMarca')) {
    include_once __DIR__ . '/funciones_faq.php';
}

/**
 * Genera el HTML para mostrar las FAQs de una marca con formato Google-style
 */
/**
 * Genera el HTML para mostrar las FAQs de una marca con formato moderno
 */
function mostrarFAQsMarca($marca_clave, $titulo_seccion = 'Preguntas Frecuentes') {
    $faqs = getFAQsByMarca($marca_clave, true); // Solo activas
    
    if (empty($faqs)) {
        return '';
    }
    
    // Mapear al formato esperado por renderFAQAccordion
    $mapped_faqs = [];
    foreach ($faqs as $faq) {
        $mapped_faqs[] = [
            'q' => $faq['titulo'] ?? $faq['pregunta'],
            'a' => $faq['respuesta']
        ];
    }
    
    return renderFAQAccordion($mapped_faqs, $marca_clave, $titulo_seccion);
}

/**
 * Genera el schema JSON-LD para SEO de las FAQs
 */
function generarSchemaFAQs($marca_clave, $marca_nombre) {
    $faqs = getFAQsByMarca($marca_clave, true);
    
    if (empty($faqs)) {
        return '';
    }
    
    return generarSchemaFAQsFromArray($faqs);
}

/**
 * Renderiza un array de FAQs en formato acordeón
 * $faqs must be an array of ['q' => '...', 'a' => '...']
 */
function renderFAQAccordion($faqs, $brand_name = '', $titulo_seccion = 'Preguntas Frecuentes') {
    if (empty($faqs)) {
        return '';
    }
    
    $html = '';
    
    // Solo incluir estilos si no se han incluido antes
    static $styles_included = false;
    if (!$styles_included) {
        $html .= '<style>
            .faq-container-modern {
                margin: 30px 0;
            }
            
            .faq-modern-title {
                font-size: 1.6rem;
                font-weight: 700;
                color: #fff;
                margin-bottom: 25px;
            }
            
            .faq-modern-item {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                margin-bottom: 15px;
                overflow: hidden;
                transition: all 0.3s ease;
            }
            
            .faq-modern-item:hover {
                background: rgba(255, 255, 255, 0.08);
                border-color: rgba(227, 6, 19, 0.3);
            }
            
            .faq-modern-question {
                width: 100%;
                padding: 18px 25px;
                background: none;
                border: none;
                display: flex;
                justify-content: space-between;
                align-items: center;
                cursor: pointer;
                text-align: left;
                color: #fff;
                font-size: 1.1rem;
                font-weight: 600;
            }
            
            .faq-modern-question i {
                color: #E30613;
                transition: transform 0.3s ease;
            }
            
            .faq-modern-item.active .faq-modern-question i {
                transform: rotate(180deg);
            }
            
            .faq-modern-answer {
                max-height: 0;
                overflow: hidden;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                background: rgba(0, 0, 0, 0.1);
            }
            
            .faq-modern-item.active .faq-modern-answer {
                max-height: 1000px;
                border-top: 1px solid rgba(255, 255, 255, 0.05);
            }
            
            .faq-modern-answer-content {
                padding: 20px 25px;
                color: #ccc;
                line-height: 1.7;
                font-size: 1rem;
            }
        </style>';
        
        $html .= '<script>
            function handleFAQToggle(btn) {
                const item = btn.parentElement;
                const isActive = item.classList.contains("active");
                
                // Opcional: Cerrar otros (comentado por ahora)
                /*
                document.querySelectorAll(".faq-modern-item").forEach(i => i.classList.remove("active"));
                */
                
                if (isActive) {
                    item.classList.remove("active");
                } else {
                    item.classList.add("active");
                }
            }
        </script>';
        $styles_included = true;
    }
    
    $html .= '<div class="faq-container-modern">';
    if ($titulo_seccion) {
        $html .= '<h2 class="faq-modern-title">' . htmlspecialchars($titulo_seccion) . '</h2>';
    }
    
    foreach ($faqs as $index => $faq) {
        $q = $faq['q'] ?? $faq['titulo'] ?? $faq['pregunta'] ?? '';
        $a = $faq['a'] ?? $faq['respuesta'] ?? '';
        
        if (empty($q) || empty($a)) continue;
        
        $html .= '<div class="faq-modern-item">';
        $html .= '<button class="faq-modern-question" onclick="handleFAQToggle(this)">';
        $html .= '<span>' . htmlspecialchars($q) . '</span>';
        $html .= '<i class="fas fa-chevron-down"></i>';
        $html .= '</button>';
        $html .= '<div class="faq-modern-answer">';
        $html .= '<div class="faq-modern-answer-content">' . nl2br($a) . '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Función helper para incluir FAQs en páginas de marca
 * Ahora soporta pasar un array personalizado de FAQs
 */
function incluirFAQsEnMarca($marca_clave, $marca_nombre, $custom_faqs = null) {
    if ($custom_faqs !== null && is_array($custom_faqs)) {
        $faqs_html = renderFAQAccordion($custom_faqs, $marca_nombre, 'Preguntas Frecuentes sobre ' . $marca_nombre);
        // El esquema todavía requiere el formato de DB o uno mapeado
        $mapped_faqs = [];
        foreach($custom_faqs as $cf) {
            $mapped_faqs[] = [
                'titulo' => $cf['q'] ?? $cf['titulo'] ?? '',
                'respuesta' => $cf['a'] ?? $cf['respuesta'] ?? ''
            ];
        }
        $schema_html = generarSchemaFAQsFromArray($mapped_faqs);
    } else {
        $faqs_html = mostrarFAQsMarca($marca_clave, 'Preguntas Frecuentes sobre ' . $marca_nombre);
        $schema_html = generarSchemaFAQs($marca_clave, $marca_nombre);
    }
    
    return $faqs_html . $schema_html;
}

/**
 * Genera schema a partir de array
 */
function generarSchemaFAQsFromArray($faqs) {
    if (empty($faqs)) return '';
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];
    foreach ($faqs as $faq) {
        $schema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $faq['titulo'] ?? $faq['q'] ?? '',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => strip_tags($faq['respuesta'] ?? $faq['a'] ?? '')
            ]
        ];
    }
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>';
}
