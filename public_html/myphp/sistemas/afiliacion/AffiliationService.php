<?php

namespace CodigoAmigo\Systems\Affiliation;

use MongoDB\BSON\ObjectId;

class AffiliationService {
    private static $instance = null;
    private $db;
    
    // Nombres de colecciones
    private $col_networks = 'affiliation_networks';
    private $col_programs = 'affiliation_programs';
    private $col_rules = 'affiliation_rules';
    private $col_links = 'affiliation_links'; // Enlaces manuales o específicos

    private function __construct() {
        // Inicializar colecciones
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene el mejor enlace de afiliado para una marca basado en las reglas de prioridad.
     */
    public function getBestLinkForBrand($brand_id) {
        if (empty($brand_id)) return null;

        // 1. Buscar reglas específicas para esta marca
        $rule = $this->getRuleForBrand($brand_id);
        
        if ($rule && !empty($rule['priority_order'])) {
            // Asegurar que priority_order sea un array
            $priority_order = $rule['priority_order'];
            if (is_object($priority_order)) {
                $priority_order = iterator_to_array($priority_order);
            }

            foreach ($priority_order as $network_id) {
                // network_id puede ser string o ObjectId
                $link = $this->getLinkForBrandInNetwork($brand_id, (string)$network_id);
                if ($link) return $link;
            }
        }

        // 2. Si no hay regla o no se encontró enlace en la prioridad, buscar cualquier enlace disponible (orden default)
        return $this->getDefaultLinkForBrand($brand_id);
    }

    private function getRuleForBrand($brand_id) {
        if (function_exists('getCollectionAffiliationRules')) {
             $col = getCollectionAffiliationRules();
             // Intentar buscar regla específica
             $rule = $col->findOne(['brand_id' => $brand_id]);
             
             // Si no existe, buscar regla por defecto
             if (!$rule) {
                 $rule = $col->findOne(['brand_id' => 'default']);
             }
             
             return $rule;
        }
        return null;
    }

    private function getLinkForBrandInNetwork($brand_id, $network_id) {
        if (function_exists('getCollectionAffiliationPrograms')) {
            $col_prog = getCollectionAffiliationPrograms();
            
            try {
                // Convertir string ID a ObjectId con manejo de errores
                $netObjectId = new ObjectId($network_id);
                
                $program = $col_prog->findOne([
                    'brand_id' => $brand_id,
                    'network_id' => $netObjectId,
                    'status' => 'active'
                ]);
    
                if ($program) {
                    return $this->constructDeepLink($program);
                }
            } catch (\Exception $e) {
                // Log error if ObjectId invalid
            }
        }
        return null;
    }

    private function getDefaultLinkForBrand($brand_id) {
        if (function_exists('getCollectionAffiliationPrograms')) {
            $col_prog = getCollectionAffiliationPrograms();
            $program = $col_prog->findOne([
                'brand_id' => $brand_id,
                'status' => 'active'
            ]);
            if ($program) return $this->constructDeepLink($program);
        }
        return null;
    }

    private function constructDeepLink($program) {
        // Por ahora devolvemos el array del programa
        // En el futuro aquí construiremos el link final (añadiendo subids, etc)
        // Ejemplo: Si es Impact y tenemos subid
        return iterator_to_array($program);
    }

    public function recordClick($brand_id, $network_id, $program_id = null) {
        // Usamos logs generales o creamos una colección específica para clicks de afiliación
        if (function_exists('createConnection')) {
            try {
                $db = createConnection();
                $col = $db->selectCollection('affiliation_clicks');
                $col->insertOne([
                    'brand_id' => $brand_id,
                    'network_id' => $network_id,
                    'program_id' => $program_id,
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
            } catch (\Exception $e) {
                // Silent fail for logging
            }
        }
    }
}
