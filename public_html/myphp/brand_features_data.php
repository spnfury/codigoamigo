<?php
/**
 * Datos de características de marcas para comparativas
 * Cada marca tiene features específicos de su categoría
 * 
 * Estructura:
 * - features: array de ['icon', 'label', 'value'] para cada característica
 * - ventajas: array de strings con ventajas principales
 * - desventajas: array de strings con desventajas
 * - rating: valoración general 1-5
 * - ideal_para: string con el perfil ideal de usuario
 */

function get_brand_features($slug) {
    $data = get_all_brand_features();
    return $data[$slug] ?? null;
}

/**
 * Obtener las categorías de features que aplican a una categoría de marcas
 */
function get_category_comparison_labels($categoria_clave) {
    $labels = [
        'banca-y-criptomonedas' => [
            ['key' => 'comision_mantenimiento', 'label' => 'Comisión mantenimiento', 'icon' => 'fas fa-euro-sign'],
            ['key' => 'tarjetas_gratis', 'label' => 'Tarjetas gratuitas', 'icon' => 'fas fa-credit-card'],
            ['key' => 'retiradas_atm', 'label' => 'Retiradas ATM gratis', 'icon' => 'fas fa-money-bill-wave'],
            ['key' => 'transferencias', 'label' => 'Transferencias', 'icon' => 'fas fa-exchange-alt'],
            ['key' => 'app_movil', 'label' => 'App móvil', 'icon' => 'fas fa-mobile-alt'],
            ['key' => 'cashback', 'label' => 'Cashback', 'icon' => 'fas fa-percent'],
            ['key' => 'criptomonedas', 'label' => 'Compra/venta cripto', 'icon' => 'fab fa-bitcoin'],
            ['key' => 'seguro_depositos', 'label' => 'Seguro de depósitos', 'icon' => 'fas fa-shield-alt'],
        ],
        'viajes-y-alojamiento' => [
            ['key' => 'tipo_alojamiento', 'label' => 'Tipo de alojamiento', 'icon' => 'fas fa-bed'],
            ['key' => 'cancelacion_gratis', 'label' => 'Cancelación gratis', 'icon' => 'fas fa-times-circle'],
            ['key' => 'programa_fidelidad', 'label' => 'Programa fidelidad', 'icon' => 'fas fa-award'],
            ['key' => 'cobertura', 'label' => 'Cobertura mundial', 'icon' => 'fas fa-globe'],
        ],
        'telefonia-y-comunicaciones' => [
            ['key' => 'datos_moviles', 'label' => 'Datos móviles', 'icon' => 'fas fa-wifi'],
            ['key' => 'llamadas', 'label' => 'Llamadas incluidas', 'icon' => 'fas fa-phone'],
            ['key' => 'roaming', 'label' => 'Roaming UE', 'icon' => 'fas fa-globe-europe'],
            ['key' => 'permanencia', 'label' => 'Sin permanencia', 'icon' => 'fas fa-lock-open'],
        ],
        'vehiculos-y-movilidad' => [
            ['key' => 'tipo_vehiculo', 'label' => 'Tipo de vehículo', 'icon' => 'fas fa-car'],
            ['key' => 'ciudades', 'label' => 'Ciudades disponibles', 'icon' => 'fas fa-map-marker-alt'],
            ['key' => 'precio_minuto', 'label' => 'Precio por minuto', 'icon' => 'fas fa-clock'],
            ['key' => 'app_disponible', 'label' => 'App disponible', 'icon' => 'fas fa-mobile-alt'],
        ],
        'deportes-y-nutricion' => [
            ['key' => 'tipo_producto', 'label' => 'Tipo de producto', 'icon' => 'fas fa-dumbbell'],
            ['key' => 'envio_gratis', 'label' => 'Envío gratis', 'icon' => 'fas fa-truck'],
            ['key' => 'certificaciones', 'label' => 'Certificaciones', 'icon' => 'fas fa-certificate'],
            ['key' => 'origen', 'label' => 'País de origen', 'icon' => 'fas fa-flag'],
        ],
        'alimentacion-y-gastronomia' => [
            ['key' => 'tipo_servicio', 'label' => 'Tipo de servicio', 'icon' => 'fas fa-concierge-bell'],
            ['key' => 'coste_envio', 'label' => 'Coste de envío', 'icon' => 'fas fa-truck'],
            ['key' => 'ciudades', 'label' => 'Ciudades disponibles', 'icon' => 'fas fa-map-marker-alt'],
            ['key' => 'pedido_minimo', 'label' => 'Pedido mínimo', 'icon' => 'fas fa-shopping-basket'],
        ],
        'plataformas-y-suscripciones' => [
            ['key' => 'precio_mensual', 'label' => 'Precio mensual', 'icon' => 'fas fa-tag'],
            ['key' => 'prueba_gratis', 'label' => 'Prueba gratis', 'icon' => 'fas fa-gift'],
            ['key' => 'contenido', 'label' => 'Tipo de contenido', 'icon' => 'fas fa-film'],
            ['key' => 'dispositivos', 'label' => 'Dispositivos simultáneos', 'icon' => 'fas fa-laptop'],
        ],
    ];
    
    return $labels[$categoria_clave] ?? [];
}

function get_all_brand_features() {
    return [
        // =============================================
        // BANCA Y CRIPTOMONEDAS
        // =============================================
        'ing' => [
            'categoria_clave' => 'banca-y-criptomonedas',
            'features' => [
                'comision_mantenimiento' => 'Gratis (sin condiciones)',
                'tarjetas_gratis' => '1 débito + 1 crédito',
                'retiradas_atm' => 'Ilimitadas en España',
                'transferencias' => 'Gratis nacionales e internacionales (SEPA)',
                'app_movil' => '⭐ 4.6/5',
                'cashback' => 'No',
                'criptomonedas' => 'No',
                'seguro_depositos' => 'Sí (hasta 100.000€)',
            ],
            'ventajas' => [
                'Sin comisiones de ningún tipo',
                'Retiradas ilimitadas en cualquier cajero',
                'Tarjeta de crédito gratis sin cambiar de banco',
                'Bizum integrado',
                'Hipotecas competitivas',
            ],
            'desventajas' => [
                'Sin oficinas físicas',
                'No ofrece compra de criptomonedas',
                'Sin cashback en compras',
            ],
            'rating' => 4.5,
            'ideal_para' => 'Quienes buscan una cuenta sin comisiones con tarjeta de crédito gratis y retiradas ilimitadas.',
        ],
        
        'revolut' => [
            'categoria_clave' => 'banca-y-criptomonedas',
            'features' => [
                'comision_mantenimiento' => 'Gratis (plan Standard)',
                'tarjetas_gratis' => '1 virtual + 1 física (envío 6€)',
                'retiradas_atm' => '200€/mes gratis',
                'transferencias' => 'Gratis entre usuarios + interbancarias',
                'app_movil' => '⭐ 4.7/5',
                'cashback' => 'Sí (planes premium)',
                'criptomonedas' => 'Sí (80+ criptos)',
                'seguro_depositos' => 'Sí (licencia bancaria UE)',
            ],
            'ventajas' => [
                'Cambio de divisa sin comisiones',
                'Compra/venta de criptomonedas',
                'Tarjetas virtuales desechables para seguridad',
                'Espacios compartidos para gastos en grupo',
                'Analytics de gastos avanzado',
            ],
            'desventajas' => [
                'Retirada ATM limitada a 200€/mes en plan gratis',
                'Soporte al cliente lento en plan básico',
                'Envío de tarjeta física no gratuito',
            ],
            'rating' => 4.6,
            'ideal_para' => 'Viajeros frecuentes y quienes quieren acceso a criptomonedas y cambio de divisa sin comisiones.',
        ],
        
        'n26' => [
            'categoria_clave' => 'banca-y-criptomonedas',
            'features' => [
                'comision_mantenimiento' => 'Gratis (plan Standard)',
                'tarjetas_gratis' => '1 virtual Mastercard',
                'retiradas_atm' => '3 gratis/mes',
                'transferencias' => 'Gratis SEPA',
                'app_movil' => '⭐ 4.5/5',
                'cashback' => 'Sí (planes premium)',
                'criptomonedas' => 'Sí (a través de partners)',
                'seguro_depositos' => 'Sí (hasta 100.000€)',
            ],
            'ventajas' => [
                'Apertura de cuenta en 8 minutos',
                'Subcuentas (Espacios) para organizar ahorros',
                'Diseño de app ultra moderno',
                'Pagos con Apple/Google Pay',
                'Seguro de viaje en planes premium',
            ],
            'desventajas' => [
                'Solo 3 retiradas gratis/mes',
                'Sin oficinas físicas en España',
                'Tarjeta física con coste en plan gratis',
            ],
            'rating' => 4.4,
            'ideal_para' => 'Millennials y Gen-Z que valoran una app moderna y organización de gastos.',
        ],
        
        'trade-republic' => [
            'categoria_clave' => 'banca-y-criptomonedas',
            'features' => [
                'comision_mantenimiento' => 'Gratis',
                'tarjetas_gratis' => '1 tarjeta Visa (1% saveback)',
                'retiradas_atm' => 'Sí (cajeros Visa)',
                'transferencias' => 'Gratis SEPA',
                'app_movil' => '⭐ 4.3/5',
                'cashback' => '1% saveback en compras',
                'criptomonedas' => 'Sí (50+ criptos)',
                'seguro_depositos' => 'Sí (hasta 100.000€)',
            ],
            'ventajas' => [
                '1% de saveback en todas las compras con tarjeta',
                'Inversión en acciones y ETFs desde 1€',
                '2% de interés en efectivo (cuenta remunerada)',
                'Planes de ahorro automáticos',
                'Sin comisiones en compraventa de acciones',
            ],
            'desventajas' => [
                'No es un banco tradicional (broker)',
                'Funcionalidades bancarias limitadas vs N26/Revolut',
                'Sin Bizum',
            ],
            'rating' => 4.3,
            'ideal_para' => 'Ahorradores e inversores que quieren cuenta remunerada + inversión + saveback.',
        ],
        
        'wise' => [
            'categoria_clave' => 'banca-y-criptomonedas',
            'features' => [
                'comision_mantenimiento' => 'Gratis',
                'tarjetas_gratis' => '1 Visa (envío 7€)',
                'retiradas_atm' => '200£/mes gratis',
                'transferencias' => 'Baratas (comisión 0.3-1%)',
                'app_movil' => '⭐ 4.5/5',
                'cashback' => 'No',
                'criptomonedas' => 'No',
                'seguro_depositos' => 'Fondos protegidos (regulado FCA)',
            ],
            'ventajas' => [
                'Tipo de cambio real del mercado (sin markup)',
                'Cuentas en 40+ divisas simultáneas',
                'Ideal para recibir pagos internacionales',
                'Transferencias internacionales las más baratas',
                'Datos bancarios locales en múltiples países',
            ],
            'desventajas' => [
                'No es un banco (no tiene licencia bancaria UE)',
                'Sin Bizum',
                'ATM limitado a 200£/mes',
            ],
            'rating' => 4.5,
            'ideal_para' => 'Freelancers, nómadas digitales y quienes hacen transferencias internacionales frecuentes.',
        ],

        'vivid-money' => [
            'categoria_clave' => 'banca-y-criptomonedas',
            'features' => [
                'comision_mantenimiento' => 'Gratis',
                'tarjetas_gratis' => '1 Visa metálica (Prime)',
                'retiradas_atm' => '200€/mes gratis',
                'transferencias' => 'Gratis SEPA',
                'app_movil' => '⭐ 4.2/5',
                'cashback' => 'Hasta 10% en marcas seleccionadas',
                'criptomonedas' => 'Sí (50+ criptos y acciones)',
                'seguro_depositos' => 'Sí (Solarisbank AG)',
            ],
            'ventajas' => [
                'Cashback real de hasta 10% en tiendas',
                'Acciones fraccionadas desde 0.01€',
                'Pockets para organizar dinero',
                'Tarjeta metálica en plan Prime',
            ],
            'desventajas' => [
                'Cashback varía mucho según tienda',
                'Marca menos conocida en España',
                'Funcionalidades bancarias básicas',
            ],
            'rating' => 4.0,
            'ideal_para' => 'Quienes buscan cashback generoso y acceso a inversión en acciones/cripto.',
        ],

        // =============================================
        // VIAJES Y ALOJAMIENTO
        // =============================================
        'airbnb' => [
            'categoria_clave' => 'viajes-y-alojamiento',
            'features' => [
                'tipo_alojamiento' => 'Casas, apartamentos y experiencias',
                'cancelacion_gratis' => 'Depende del anfitrión',
                'programa_fidelidad' => 'No (descuentos por estancias largas)',
                'cobertura' => '220+ países, 7M+ alojamientos',
            ],
            'ventajas' => [
                'Alojamientos únicos y con personalidad',
                'Experiencias locales exclusivas',
                'Ideal para estancias largas',
                'Cocina disponible (ahorro en comidas)',
            ],
            'desventajas' => [
                'Comisiones de servicio del 14-16%',
                'Check-in no siempre 24h',
                'Calidad variable entre anfitriones',
            ],
            'rating' => 4.4,
            'ideal_para' => 'Viajeros que buscan experiencias auténticas y estancias largas.',
        ],
        
        'bookingcom' => [
            'categoria_clave' => 'viajes-y-alojamiento',
            'features' => [
                'tipo_alojamiento' => 'Hoteles, apartamentos, hostales',
                'cancelacion_gratis' => 'Sí (mayoría de hoteles)',
                'programa_fidelidad' => 'Genius (3 niveles)',
                'cobertura' => '228 países, 28M+ alojamientos',
            ],
            'ventajas' => [
                'Cancelación gratuita en la mayoría',
                'Programa Genius con descuentos automáticos',
                'Mayor catálogo de hoteles del mundo',
                'Paga en el hotel (sin adelantar dinero)',
            ],
            'desventajas' => [
                'Precios a veces más altos por comisiones',
                'Fotos de alojamientos a veces engañosas',
            ],
            'rating' => 4.5,
            'ideal_para' => 'Cualquier viajero que valore flexibilidad de cancelación y variedad.',
        ],

        // =============================================
        // TELEFONÍA
        // =============================================
        'finetwork' => [
            'categoria_clave' => 'telefonia-y-comunicaciones',
            'features' => [
                'datos_moviles' => 'Desde 10GB hasta ilimitados',
                'llamadas' => 'Ilimitadas',
                'roaming' => 'Incluido (según tarifa)',
                'permanencia' => 'Sin permanencia',
            ],
            'ventajas' => [
                'Precio competitivo desde 6.90€/mes',
                'Sin permanencia ni letra pequeña',
                'Fibra + móvil combinado',
                'Cobertura Orange/Movistar',
            ],
            'desventajas' => [
                'Marca menos conocida',
                'Tiendas físicas limitadas',
            ],
            'rating' => 4.2,
            'ideal_para' => 'Quienes buscan tarifas baratas sin ataduras de permanencia.',
        ],
        
        // =============================================
        // DEPORTES Y NUTRICIÓN
        // =============================================
        'myprotein' => [
            'categoria_clave' => 'deportes-y-nutricion',
            'features' => [
                'tipo_producto' => 'Suplementos y nutrición deportiva',
                'envio_gratis' => 'Sí (pedidos +30€)',
                'certificaciones' => 'Informed Sport, ISO 17025',
                'origen' => 'Reino Unido',
            ],
            'ventajas' => [
                'Precios muy competitivos',
                'Gran variedad de sabores',
                'Ofertas constantes (40-60% descuento)',
                'Marca líder en Europa',
            ],
            'desventajas' => [
                'Envío desde UK (puede tardar)',
                'Algunos sabores no disponibles siempre',
            ],
            'rating' => 4.3,
            'ideal_para' => 'Deportistas que buscan suplementos de calidad a buen precio.',
        ],

        'prozis' => [
            'categoria_clave' => 'deportes-y-nutricion',
            'features' => [
                'tipo_producto' => 'Suplementos, ropa y accesorios',
                'envio_gratis' => 'Sí (pedidos +20€)',
                'certificaciones' => 'GMP, Halal',
                'origen' => 'Portugal',
            ],
            'ventajas' => [
                'Envío gratis desde 20€',
                'Amplio catálogo: suplementos + ropa + accesorios',
                'Almacén en UE (envío rápido)',
                'Marca conocida con influencers',
            ],
            'desventajas' => [
                'Algunos productos genéricos',
                'Precios base más altos que MyProtein',
            ],
            'rating' => 4.1,
            'ideal_para' => 'Deportistas que quieren todo en un solo sitio: suplementos, ropa y accesorios.',
        ],
        
        // =============================================
        // ALIMENTACIÓN Y GASTRONOMÍA
        // =============================================
        'glovo' => [
            'categoria_clave' => 'alimentacion-y-gastronomia',
            'features' => [
                'tipo_servicio' => 'Delivery multiservicios',
                'coste_envio' => 'Desde 0.99€ (Glovo Prime gratis)',
                'ciudades' => '100+ ciudades en España',
                'pedido_minimo' => 'Variable según restaurante',
            ],
            'ventajas' => [
                'No solo comida: supermercado, farmacia, recados',
                'Glovo Prime: envíos gratis ilimitados',
                'Entrega en 30 min de media',
                'Gran selección de restaurantes',
            ],
            'desventajas' => [
                'Precios menú más altos que en restaurante',
                'Recargos en horas punta',
            ],
            'rating' => 4.0,
            'ideal_para' => 'Quienes quieren delivery de todo tipo, no solo comida.',
        ],
    ];
}
