<?php
/**
 * Chollo Content Generator
 * Servicio principal para generar contenido enriquecido
 */

class CholloContentGenerator {
    private $cache;
    private $faq_templates;
    private $guide_templates;
    
    public function __construct() {
        require_once __DIR__ . '/ContentCacheService.php';
        $this->cache = new ContentCacheService();
        $this->faq_templates = include __DIR__ . '/../content_templates/faq_templates.php';
        $this->guide_templates = include __DIR__ . '/../content_templates/purchase_guide_templates.php';
    }
    
    /**
     * Genera todo el contenido para un chollo
     */
    public function generateFullContent($chollo) {
        $chollo_id = (string)($chollo['_id'] ?? $chollo['id'] ?? '');
        
        // Intentar obtener del cache primero
        $cached = $this->cache->get($chollo_id);
        if ($cached !== null) {
            return $cached;
        }
        
        // Generar contenido nuevo
        $category = $this->extractCategory($chollo);
        
        $content = [
            'faqs' => $this->generateFAQs($category),
            'purchase_guide' => $this->generatePurchaseGuide($category),
            'analysis' => $this->generateAnalysis($chollo, $category),
            'pros_cons' => $this->generateProsConsAnalysis($chollo, $category),
            'who_for' => $this->generateWhoIsItFor($chollo, $category),
            'seo_content' => $this->generateSEOContent($chollo, $category),
        ];
        
        // Cachear
        $this->cache->set($chollo_id, $content);
        
        return $content;
    }
    
    /**
     * Extrae la categoría principal del chollo
     */
    private function extractCategory($chollo) {
        $categoria_raw = $chollo['categoria'] ?? 'general';
        
        if (is_object($categoria_raw) && method_exists($categoria_raw, 'getArrayCopy')) {
            $categoria_raw = $categoria_raw->getArrayCopy();
        }
        
        if (is_array($categoria_raw)) {
            // Buscar una categoría específica (no 'general')
            foreach ($categoria_raw as $cat) {
                $cat_lower = strtolower($cat);
                if ($cat_lower !== 'general' && $cat_lower !== 'black-friday') {
                    return $cat_lower;
                }
            }
            return isset($categoria_raw[0]) ? strtolower($categoria_raw[0]) : 'general';
        }
        
        return strtolower((string)$categoria_raw);
    }
    
    /**
     * Genera FAQs basadas en templates
     */
    public function generateFAQs($category) {
        // Intentar obtener FAQs específicas de la categoría
        if (isset($this->faq_templates[$category])) {
            return $this->faq_templates[$category];
        }
        
        // Fallback a FAQs generales
        return $this->faq_templates['general'] ?? [];
    }
    
    /**
     * Genera guía de compra
     */
    public function generatePurchaseGuide($category) {
        if (isset($this->guide_templates[$category])) {
            return $this->guide_templates[$category];
        }
        
        return $this->guide_templates['general'] ?? [];
    }
    
    /**
     * Genera análisis básico del chollo
     */
    public function generateAnalysis($chollo, $category) {
        $titulo = htmlspecialchars($chollo['titulo'] ?? 'este producto');
        $precio = $chollo['precio_descuento'] ?? $chollo['precio_original'] ?? 0;
        $descuento = $chollo['porcentaje_descuento'] ?? 0;
        
        $analysis = [
            'titulo' => '¿Por qué este chollo es una oportunidad?',
            'contenido' => '',
        ];
        
        // Generar contenido basado en los datos disponibles
        $parrafos = [];
        
        if ($descuento > 30) {
            $parrafos[] = "Con un <strong>descuento del {$descuento}%</strong>, esta oferta representa un ahorro significativo sobre el precio habitual. Es una excelente oportunidad para conseguir {$titulo} a un precio realmente competitivo.";
        } elseif ($descuento > 0) {
            $parrafos[] = "Esta oferta incluye un <strong>descuento del {$descuento}%</strong>, permitiéndote ahorrar en la compra de {$titulo}.";
        }
        
        // Personalización por categoría
        $category_insights = [
            'moda' => "En el sector de la moda, encontrar prendas de calidad a buen precio no es fácil. Esta oferta te permite actualizar tu armario sin comprometer tu presupuesto.",
            'tecnologia' => "Los productos tecnológicos suelen mantener precios elevados durante mucho tiempo. Aprovechar descuentos como este puede significar un ahorro considerable en dispositivos que necesitas.",
            'hogar' => "Equipar tu hogar puede resultar costoso, pero con ofertas como esta puedes conseguir artículos de calidad sin gastar de más.",
            'deportes' => "El equipamiento deportivo de calidad es fundamental para un buen rendimiento. Esta oferta te permite acceder a productos de nivel intermedio o profesional a precios más accesibles.",
            'belleza' => "Los productos de belleza pueden acumularse en el presupuesto mensual. Aprovechar descuentos te permite probar productos premium o hacer acopio de tus favoritos.",
        ];
        
        if (isset($category_insights[$category])) {
            $parrafos[] = $category_insights[$category];
        }
        
        $parrafos[] = "Recuerda que las <strong>ofertas flash pueden agotarse rápidamente</strong>, especialmente cuando se trata de productos populares a precios reducidos. Si este producto cumple con lo que buscas, considera aprovechar la oferta antes de que finalice.";
        
        $analysis['contenido'] = implode("\n\n", $parrafos);
        
        return $analysis;
    }
    
    /**
     * Genera pros y contras básicos
     */
    public function generateProsConsAnalysis($chollo, $category) {
        $descuento = $chollo['porcentaje_descuento'] ?? 0;
        $precio_desc = $chollo['precio_descuento'] ?? 0;
        $precio_orig = $chollo['precio_original'] ?? 0;
        
        $pros = [];
        $contras = [];
        
        // Pros universales
        if ($descuento > 20) {
            $pros[] = "Descuento significativo del {$descuento}%";
        }
        if ($precio_desc > 0 && $precio_desc < 50) {
            $pros[] = "Precio muy accesible";
        }
        $pros[] = "Disponible para envío rápido";
        $pros[] = "Vendedor verificado";
        
        // Contras según precio
        if (!isset($chollo['envio_gratis']) || !$chollo['envio_gratis']) {
            $contras[] = "Pueden aplicarse gastos de envío";
        }
        $contras[] = "Stock limitado - puede agotarse rápido";
        $contras[] = "El precio puede variar según disponibilidad";
        
        // Personalizar por categoría
        $category_specific = [
            'moda' => [
                'pros' => ['Actualiza tu guardarropa', 'Estilo versátil'],
                'contras' => ['Verifica bien tu talla', 'Los colores pueden variar ligeramente']
            ],
            'tecnologia' => [
                'pros' => ['Tecnología moderna', 'Buena relación especificaciones/precio'],
                'contras' => ['Puede haber modelos más nuevos próximamente', 'Revisa compatibilidad con tus dispositivos']
            ],
            'hogar' => [
                'pros' => ['Mejora tu espacio', 'Funcional y práctico'],
                'contras' => ['Requiere espacio de almacenamiento', 'Puede necesitar montaje']
            ],
        ];
        
        if (isset($category_specific[$category])) {
            $pros = array_merge($pros, $category_specific[$category]['pros']);
            $contras = array_merge($contras, $category_specific[$category]['contras']);
        }
        
        return [
            'pros' => array_slice($pros, 0, 5),
            'contras' => array_slice($contras, 0, 5),
        ];
    }
    
    /**
     * Genera sección "Para quién es"
     */
    public function generateWhoIsItFor($chollo, $category) {
        $titulo = $chollo['titulo'] ?? 'producto';
        
        $category_personas = [
            'moda' => [
                'ideal' => ['Personas que buscan actualizar su estilo', 'Quienes valoran la relación calidad-precio', 'Fanáticos de las tendencias actuales'],
                'no_ideal' => ['Si buscas piezas de lujo exclusivas', 'Si prefieres comprar en tienda física para probarte']
            ],
            'tecnologia' => [
                'ideal' => ['Entusiastas de la tecnología', 'Profesionales que buscan herramientas de trabajo', 'Usuarios que quieren actualizar su equipo'],
                'no_ideal' => ['Si necesitas lo último en innovación absoluta', 'Si prefieres marcas muy específicas']
            ],
            'hogar' => [
                'ideal' => ['Personas amueblando un nuevo hogar', 'Quienes buscan renovar espacios', 'Familias en búsqueda de soluciones prácticas'],
                'no_ideal' => ['Si buscas diseños ultra-personalizados', 'Si el espacio disponible es muy limitado']
            ],
            'deportes' => [
                'ideal' => ['Deportistas aficionados', 'Personas comenzando en fitness', 'Atletas en busca de value for money'],
                'no_ideal' => ['Atletas profesionales con necesidades muy específicas', 'Si requieres equipo de competición certificado']
            ],
            'general' => [
                'ideal' => ['Compradores que buscan buenas ofertas', 'Personas con presupuesto ajustado', 'Quienes valoran el ahorro inteligente'],
                'no_ideal' => ['Si buscas personalización extrema', 'Si el tiempo de espera es un factor crítico']
            ]
        ];
        
        $personas = $category_personas[$category] ?? $category_personas['general'];
        
        return [
            'titulo' => '¿Para quién es ideal este producto?',
            'ideal_para' => $personas['ideal'],
            'no_recomendado' => $personas['no_ideal'],
        ];
    }
    
    /**
     * Genera contenido SEO optimizado
     */
    public function generateSEOContent($chollo, $category) {
        $titulo = $chollo['titulo'] ?? '';
        $year = date('Y');
        
        return [
            'h1' => $titulo . ' - Análisis Completo y Opinión ' . $year,
            'meta_description' => "Descubre si vale la pena: análisis detallado de {$titulo}, precios, características y opiniones. Guía completa de compra {$year}.",
            'intro' => "En esta guía completa analizamos a fondo <strong>{$titulo}</strong>, evaluando su relación calidad-precio, características destacadas y para quién está especialmente recomendado. Si estás considerando esta compra, aquí encontrarás toda la información que necesitas.",
        ];
    }
}
