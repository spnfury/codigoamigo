<?php
/**
 * Content Cache Service
 * Maneja el cacheo de contenido generado para chollos
 */

class ContentCacheService {
    private $collection;
    private $ttl = 86400; // 24 horas
    
    public function __construct() {
        require_once __DIR__ . '/../funciones_chollos.php';
        $this->collection = getCollectionChollos();
    }
    
    /**
     * Obtiene contenido cacheado para un choll
o
     */
    public function get($chollo_id) {
        try {
            $chollo = $this->collection->findOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)]);
            
            if (!$chollo || !isset($chollo['generated_content'])) {
                return null;
            }
            
            $content = $chollo['generated_content'];
            
            // Verificar si el cache ha expirado
            if (isset($content['generated_at'])) {
                $generated_at = $content['generated_at'];
                if ($generated_at instanceof MongoDB\BSON\UTCDateTime) {
                    $timestamp = $generated_at->toDateTime()->getTimestamp();
                    if (time() - $timestamp > $this->ttl) {
                        return null; // Cache expirado
                    }
                }
            }
            
            return $content;
        } catch (Exception $e) {
            error_log("Error getting cached content: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Guarda contenido generado en cache
     */
    public function set($chollo_id, $content) {
        try {
            $content['generated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($chollo_id)],
                ['$set' => ['generated_content' => $content]]
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error caching content: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Invalida el cache para un chollo específico
     */
    public function invalidate($chollo_id) {
        try {
            $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($chollo_id)],
                ['$unset' => ['generated_content' => '']]
            );
            return true;
        } catch (Exception $e) {
            error_log("Error invalidating cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpia cache expirado (para ejecutar en cron)
     */
    public function cleanExpired() {
        try {
            $expiry_time = new MongoDB\BSON\UTCDateTime((time() - $this->ttl) * 1000);
            
            $result = $this->collection->updateMany(
                ['generated_content.generated_at' => ['$lt' => $expiry_time]],
                ['$unset' => ['generated_content' => '']]
            );
            
            return $result->getModifiedCount();
        } catch (Exception $e) {
            error_log("Error cleaning expired cache: " . $e->getMessage());
            return 0;
        }
    }
}
