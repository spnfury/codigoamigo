
<?php

// Ensure db connection functions are available
require_once __DIR__ . '/funciones.php';
file_put_contents(__DIR__ . '/../public/debug_destacar_logic.log', "Executed _super_landing_functions.php\n", FILE_APPEND);

if (!function_exists('get_super_landing_by_slug')) {
    /**
     * Obtener una Super Landing por su slug
     * @param string $slug
     * @return array|null
     */
    function get_super_landing_by_slug($slug) {
        try {
            $db = createConnection();
            $collection = $db->selectCollection('super_landings');
            
            $landing = $collection->findOne(['slug' => $slug, 'status' => 'active']);
            
            if ($landing) {
                return iterator_to_array($landing);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error obteniendo super landing: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Renderizar Schema.org para FAQ
 * @param array $faqs
 * @return string JSON-LD script
 */
if (!function_exists('render_faq_schema')) {
    function render_faq_schema($faqs) {
        if (empty($faqs)) return '';

        $schema = [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => []
        ];

        foreach ($faqs as $faq) {
            $schema['mainEntity'][] = [
                "@type" => "Question",
                "name" => strip_tags($faq['question']),
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $faq['answer'] // Permitir HTML en respuesta
                ]
            ];
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>';
    }
}

/**
 * Obtener lista de Super Landings activas para el menú
 */
if (!function_exists('get_active_super_landings')) {
    function get_active_super_landings($limit = 10) {
        $db = createConnection();
        $collection = $db->selectCollection('super_landings');
        
        $cursor = $collection->find(
            ['status' => 'active'], 
            [
                'limit' => $limit,
                'sort' => ['title' => 1],
                'projection' => ['title' => 1, 'slug' => 1, 'meta_title' => 1, 'linked_brand_slugs' => 1]
            ]
        );
        
        return iterator_to_array($cursor);
    }
}


if (!function_exists('get_super_landing_codes')) {
    /**
     * Obtener códigos para una super landing (ya sea de marca única o colección)
     * @param array $landing_data
     * @param int $limit
     * @return array
     */
    function get_super_landing_codes($landing_data, $limit = 20) {
        $db = createConnection();
        $collection_codigos = $db->selectCollection('codigos');
        
        $filter = ['estado' => 0];

        // Filtrar por autor de la guía si existe
        if (isset($landing_data['author_id']) && !empty($landing_data['author_id'])) {
            $author_str = (string)$landing_data['author_id'];
            // id_usuario puede estar almacenado como string o como ObjectId
            $author_variants = [$author_str];
            if (strlen($author_str) === 24 && ctype_xdigit($author_str)) {
                $author_variants[] = new \MongoDB\BSON\ObjectId($author_str);
            }
            $filter['id_usuario'] = ['$in' => $author_variants];
        }
        
        $or_conditions = [];

        if (isset($landing_data['linked_brand_id'])) {
            $or_conditions[] = ['marca_id' => (string)$landing_data['linked_brand_id']];
        }

        if (isset($landing_data['linked_brand_ids']) && is_array($landing_data['linked_brand_ids'])) {
            $brand_ids = array_map(function($id) { return (string)$id; }, $landing_data['linked_brand_ids']);
            $or_conditions[] = ['marca_id' => ['$in' => $brand_ids]];
        }
        
        // Convert BSONArray to array if necessary
        $slugs = null;
        if (isset($landing_data['linked_brand_slugs'])) {
            if ($landing_data['linked_brand_slugs'] instanceof \MongoDB\Model\BSONArray || $landing_data['linked_brand_slugs'] instanceof \MongoDB\Model\BSONDocument) {
                 $slugs = $landing_data['linked_brand_slugs']->getArrayCopy();
            } elseif (is_object($landing_data['linked_brand_slugs']) && method_exists($landing_data['linked_brand_slugs'], 'getArrayCopy')) {
                 $slugs = $landing_data['linked_brand_slugs']->getArrayCopy(); 
            } elseif (is_object($landing_data['linked_brand_slugs'])) {
                 $slugs = iterator_to_array($landing_data['linked_brand_slugs']);
            } else {
                 $slugs = $landing_data['linked_brand_slugs'];
            }
        }

        if ($slugs && is_array($slugs)) {
             $or_conditions[] = ['marca' => ['$in' => $slugs]];
        }

            if (!empty($or_conditions)) {
                if (count($or_conditions) > 1) {
                    $filter['$or'] = $or_conditions;
                } else {
                    $filter = array_merge($filter, $or_conditions[0]);
                }
            }
        
        // Ordenar por:
        // 1. Super Destacados (tipo_destacado: 'super') - we handle sorting and expiry in the loop or with complex query
        // For simplicity and since volume is low, we fetch and then sort/filter in PHP
        $options = [
            'sort' => [
                'destacado' => -1, 
                'fecha_publicacion' => -1
            ],
            'limit' => $limit * 2 // Fetch more to account for expiry filtering if needed
        ];
        
        $cursor = $collection_codigos->find($filter, $options);
        
        $codigos = [];
        $now = time();
        $thirty_days_ago = $now - (30 * 24 * 60 * 60);
        
        foreach ($cursor as $codigo) {
            $codigo_array = iterator_to_array($codigo);
            
            // Expiry check for Super highlights
            if (isset($codigo_array['tipo_destacado']) && $codigo_array['tipo_destacado'] === 'super') {
                $is_expired = false;
                
                // Check designated expiry field first
                if (isset($codigo_array['super_destacado_expira'])) {
                     $expiry_date = $codigo_array['super_destacado_expira'];
                     if ($expiry_date instanceof \MongoDB\BSON\UTCDateTime) {
                         $expiry_ts = $expiry_date->toDateTime()->getTimestamp();
                         if ($now > $expiry_ts) $is_expired = true;
                     }
                } 
                // Fallback to destacado timestamp + 30 days
                elseif (isset($codigo_array['destacado']) && is_numeric($codigo_array['destacado'])) {
                    if ($codigo_array['destacado'] < $thirty_days_ago) {
                        $is_expired = true;
                    }
                }
                
                if ($is_expired) {
                    $codigo_array['tipo_destacado'] = null; // Downgrade to normal
                }
            }
            
            $codigos[] = $codigo_array;
            if (count($codigos) >= $limit) break;
        }
        
        // Re-sort to ensure Super stay on top even after downgrade
        usort($codigos, function($a, $b) {
            $type_a = isset($a['tipo_destacado']) && $a['tipo_destacado'] === 'super' ? 1 : 0;
            $type_b = isset($b['tipo_destacado']) && $b['tipo_destacado'] === 'super' ? 1 : 0;
            
            if ($type_a !== $type_b) {
                return $type_b - $type_a;
            }
            
            $destacado_a = $a['destacado'] ?? 0;
            $destacado_b = $b['destacado'] ?? 0;
            return $destacado_b - $destacado_a;
        });
        
        return $codigos;
    }
}
?>
