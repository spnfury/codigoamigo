<?php

// Incluir funciones de FAQs
if (!function_exists('getFAQsByMarca')) {
    include_once __DIR__ . '/funciones_faq.php';
}

/**
 * Genera el HTML para mostrar las FAQs de una marca con formato Google-style
 */
function mostrarFAQsMarca($marca_clave, $titulo_seccion = 'Preguntas Frecuentes') {
    $faqs = getFAQsByMarca($marca_clave, true); // Solo activas
    
    if (empty($faqs)) {
        return '';
    }
    
    $html = '';
    
    // Estilos CSS para el acordeón estilo Google
    $html .= '<style>
        .faq-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .faq-section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .faq-section-title:after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .faq-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 8px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .faq-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .faq-question {
            background: none;
            border: none;
            width: 100%;
            padding: 20px 24px;
            text-align: left;
            font-size: 1rem;
            font-weight: 500;
            color: #333;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .faq-question:hover {
            background: #f8f9fa;
        }
        
        .faq-question.active {
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .faq-question-text {
            flex: 1;
            margin-right: 20px;
            line-height: 1.5;
        }
        
        .faq-icon {
            font-size: 1.2rem;
            color: #666;
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }
        
        .faq-question.active .faq-icon {
            transform: rotate(180deg);
            color: #667eea;
        }
        
        .faq-answer {
            padding: 0 24px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }
        
        .faq-answer.active {
            padding: 20px 24px;
            max-height: 500px;
        }
        
        .faq-answer-content {
            color: #555;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .faq-answer-content p {
            margin-bottom: 12px;
        }
        
        .faq-answer-content p:last-child {
            margin-bottom: 0;
        }
        
        .faq-answer-content ul, .faq-answer-content ol {
            margin: 12px 0;
            padding-left: 20px;
        }
        
        .faq-answer-content li {
            margin-bottom: 6px;
        }
        
        .faq-answer-content strong {
            color: #333;
            font-weight: 600;
        }
        
        .faq-answer-content a {
            color: #667eea;
            text-decoration: none;
        }
        
        .faq-answer-content a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .faq-container {
                margin: 30px auto;
                padding: 0 15px;
            }
            
            .faq-section-title {
                font-size: 1.5rem;
                margin-bottom: 25px;
            }
            
            .faq-question {
                padding: 16px 18px;
                font-size: 0.95rem;
            }
            
            .faq-question-text {
                margin-right: 15px;
            }
            
            .faq-answer.active {
                padding: 16px 18px;
            }
            
            .faq-icon {
                font-size: 1.1rem;
            }
        }
        
        /* Animación suave para el contenido */
        .faq-answer-content {
            animation: fadeInUp 0.3s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>';
    
    // HTML del contenedor de FAQs
    $html .= '<div class="faq-container">';
    $html .= '<h2 class="faq-section-title">' . htmlspecialchars($titulo_seccion) . '</h2>';
    
    foreach ($faqs as $index => $faq) {
        $faq_id = 'faq-' . $index;
        $question_text = htmlspecialchars($faq['titulo']);
        $answer_text = nl2br(htmlspecialchars($faq['respuesta']));
        
        $html .= '<div class="faq-item">';
        $html .= '<button class="faq-question" onclick="toggleFAQ(\'' . $faq_id . '\')">';
        $html .= '<span class="faq-question-text">' . $question_text . '</span>';
        $html .= '<i class="fas fa-chevron-down faq-icon"></i>';
        $html .= '</button>';
        $html .= '<div class="faq-answer" id="' . $faq_id . '">';
        $html .= '<div class="faq-answer-content">' . $answer_text . '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // JavaScript para el funcionamiento del acordeón
    $html .= '<script>
        function toggleFAQ(faqId) {
            const answer = document.getElementById(faqId);
            const question = answer.previousElementSibling;
            
            // Toggle active class
            question.classList.toggle("active");
            answer.classList.toggle("active");
            
            // Cerrar otros FAQs si es necesario (comportamiento tipo Google)
            // Descomenta las siguientes líneas si quieres que solo un FAQ esté abierto a la vez
            
            /*
            const allQuestions = document.querySelectorAll(".faq-question");
            const allAnswers = document.querySelectorAll(".faq-answer");
            
            allQuestions.forEach(q => {
                if (q !== question) {
                    q.classList.remove("active");
                }
            });
            
            allAnswers.forEach(a => {
                if (a !== answer) {
                    a.classList.remove("active");
                }
            });
            */
        }
        
        // Opcional: Abrir primer FAQ por defecto
        document.addEventListener("DOMContentLoaded", function() {
            const firstFAQ = document.querySelector(".faq-question");
            if (firstFAQ) {
                // Descomenta la siguiente línea para abrir el primer FAQ automáticamente
                // toggleFAQ(firstFAQ.nextElementSibling.id);
            }
        });
    </script>';
    
    return $html;
}

/**
 * Genera el schema JSON-LD para SEO de las FAQs
 */
function generarSchemaFAQs($marca_clave, $marca_nombre) {
    $faqs = getFAQsByMarca($marca_clave, true);
    
    if (empty($faqs)) {
        return '';
    }
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];
    
    foreach ($faqs as $faq) {
        $schema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $faq['titulo'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => strip_tags($faq['respuesta'])
            ]
        ];
    }
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Función helper para incluir FAQs en páginas de marca
 */
function incluirFAQsEnMarca($marca_clave, $marca_nombre) {
    $faqs_html = mostrarFAQsMarca($marca_clave, 'Preguntas Frecuentes sobre ' . $marca_nombre);
    $schema_html = generarSchemaFAQs($marca_clave, $marca_nombre);
    
    return $faqs_html . $schema_html;
}

?>
