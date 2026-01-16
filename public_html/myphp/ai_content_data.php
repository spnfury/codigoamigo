<?php

function get_ai_brand_content($brand_slug) {
    // Normalizar slug
    $brand_slug = strtolower(trim($brand_slug));
    
    $data = [
        'booking' => [
            'intro_html' => '<p><strong>Booking.com</strong> es la plataforma líder mundial para reservas de alojamiento, conectando a millones de viajeros con experiencias memorables. En <strong>CodigoAmigo</strong>, te ofrecemos una selección verificada de <strong>códigos de descuento para Booking</strong> que te permitirán ahorrar significativamente en tu próxima escapada.</p>',
            'description_html' => '
                <div class="ai-content-block">
                    <h3>💎 Ahorra en tus viajes con los Códigos Promocionales de Booking</h3>
                    <p>Booking.com es la plataforma líder mundial para reservas de alojamiento, conectando a millones de viajeros con experiencias memorables. En <strong>CodigoAmigo</strong>, te ofrecemos una selección verificada de <strong>códigos de descuento para Booking</strong> que te permitirán ahorrar significativamente en tu próxima escapada.</p>
                    
                    <h4>¿Por qué usar un código promocional en Booking?</h4>
                    <p>Los códigos de descuento de Booking son una herramienta fantástica para reducir el coste final de tu reserva. Ya sea que busques un hotel de lujo, un apartamento acogedor o un albergue económico, nuestros cupones te brindan acceso a tarifas exclusivas. Además, gracias al programa <em>Genius</em> de Booking, puedes combinar estos códigos con descuentos directos de fidelidad.</p>
                    
                    <ul>
                        <li><strong>Descuentos directos:</strong> Ahorra desde un 10% hasta un 15% adicional.</li>
                        <li><strong>Cashback:</strong> Recupera parte de tu dinero tras finalizar tu estancia.</li>
                        <li><strong>Ofertas de temporada:</strong> Aprovecha las promociones especiales de verano, Black Friday o Navidad.</li>
                    </ul>
                </div>
            ',
            'faqs' => [
                [
                    'question' => '¿Cómo aplico mi código de descuento en Booking?',
                    'answer' => 'Para aplicar tu código, inicia sesión en tu cuenta de Booking, selecciona tu alojamiento y dirígete al proceso de pago. En la sección de datos finales, busca la casilla "¿Tienes un código promocional?" e ingrésalo allí antes de confirmar la reserva.'
                ],
                [
                    'question' => '¿Son acumulables los códigos con el programa Genius?',
                    'answer' => '¡Sí! En la mayoría de los casos, puedes disfrutar de las ventajas de tu nivel Genius (desayunos gratis, upgrades) y además aplicar un código de descuento o cashback compatible para maximizar tu ahorro.'
                ],
                [
                    'question' => '¿Qué hago si mi código de Booking no funciona?',
                    'answer' => 'Verifica la fecha de caducidad y las condiciones del cupón (mínimo de reserva, destinos específicos). Recuerda que muchos códigos son de un solo uso o exclusivos para la app móvil.'
                ]
            ]
        ],
        'uber' => [
            'intro_html' => '<p><strong>Uber</strong> ha revolucionado la movilidad urbana, ofreciendo una alternativa cómoda, segura y accesible al transporte tradicional. Con los <strong>códigos promocionales de Uber</strong> que recopilamos en CodigoAmigo, tus trayectos por la ciudad pueden salirte mucho más baratos o incluso gratis si es tu primera vez.</p>',
            'description_html' => '
                <div class="ai-content-block">
                    <h3>🚗 Muévete libremente con los Códigos Descuento de Uber</h3>
                    <p>Uber ha revolucionado la movilidad urbana, ofreciendo una alternativa cómoda, segura y accesible al transporte tradicional. Con los <strong>códigos promocionales de Uber</strong> que recopilamos en CodigoAmigo, tus trayectos por la ciudad pueden salirte mucho más baratos o incluso gratis si es tu primera vez.</p>
                    
                    <h4>Ventajas exclusivas para usuarios de Uber</h4>
                    <p>Desde viajes al aeropuerto hasta salidas nocturnas, Uber te ofrece la flexibilidad que necesitas. Nuestros cupones suelen incluir descuentos fijos en tus primeros viajes o porcentajes de ahorro para usuarios recurrentes. Además, no olvides que Uber a menudo lanza promociones cruzadas con Uber Eats.</p>
                </div>
            ',
            'faqs' => [
                [
                    'question' => '¿Dónde introduzco el código promocional en la app de Uber?',
                    'answer' => 'Abre el menú de la app, selecciona "Pago" o "Wallet", baja hasta el final y toca en "Añadir código promocional". Escribe el código y se aplicará automáticamente a tu próximo viaje válido.'
                ],
                [
                    'question' => '¿Los códigos de Uber sirven para Uber Eats?',
                    'answer' => 'Generalmente no. Uber y Uber Eats suelen tener códigos promocionales independientes, aunque a veces existen campañas unificadas. Revisa siempre los términos de cada cupón.'
                ],
                [
                    'question' => '¿Puedo usar varios códigos en un mismo viaje?',
                    'answer' => 'No, solo se puede aplicar un código promocional por viaje. Si tienes varios guardados, la app intentará aplicar el que mayor descuento te ofrezca automáticamente.'
                ]
            ]
        ],
        'nike' => [
            'intro_html' => '<p><strong>Nike</strong> es sinónimo de innovación y estilo en el mundo del deporte. Ya seas un atleta profesional o un amante de la moda urbana, los <strong>códigos de descuento de Nike</strong> te permiten acceder a lo último en zapatillas Air Jordan, ropa técnica y accesorios a precios imbatibles.</p>',
            'description_html' => '
                <div class="ai-content-block">
                    <h3>👟 Eleva tu rendimiento con Códigos Descuento Nike</h3>
                    <p>Nike es sinónimo de innovación y estilo en el mundo del deporte. Ya seas un atleta profesional o un amante de la moda urbana, los <strong>códigos de descuento de Nike</strong> te permiten acceder a lo último en zapatillas Air Jordan, ropa técnica y accesorios a precios imbatibles.</p>
                    
                    <h4>Ahorra en la tienda oficial de Nike</h4>
                    <p>Comprar en la tienda oficial te garantiza autenticidad y acceso a lanzamientos exclusivos. A menudo, Nike ofrece códigos del 20% o 25% para miembros de <em>Nike Membership</em>, además de promociones especiales en fechas señaladas como el Nike Member Days.</p>
                </div>
            ',
            'faqs' => [
                [
                    'question' => '¿Cómo consigo envío gratis en Nike?',
                    'answer' => 'Si te registras como Nike Member (es gratuito), obtendrás envío estándar gratuito en todos tus pedidos, sin importe mínimo. Es la mejor forma de ahorrar siempre.'
                ],
                [
                    'question' => '¿Ofrece Nike descuentos para estudiantes?',
                    'answer' => 'Sí, a través de UNiDAYS, los estudiantes universitarios pueden verificar su estatus y recibir un código de descuento único del 10% para sus compras en Nike.com.'
                ]
            ]
        ],
        'amazon' => [
            'intro_html' => '<p><strong>Amazon</strong> es el gigante del comercio electrónico donde puedes encontrar prácticamente cualquier cosa. Aunque Amazon no suele usar códigos generales, sí existen miles de <strong>cupones aplicables a productos específicos</strong> y códigos promocionales para saldo y servicios Prime.</p>',
            'description_html' => '
                <div class="ai-content-block">
                    <h3>📦 Todo lo que necesitas con Códigos Promocionales Amazon</h3>
                    <p>Amazon es el gigante del comercio electrónico donde puedes encontrar prácticamente cualquier cosa. Aunque Amazon no suele usar códigos de descuento generales para todo el carrito, sí existen miles de <strong>cupones aplicables a productos específicos</strong> y códigos promocionales para saldo de recargas o servicios como Amazon Music y Prime Video.</p>
                </div>
            ',
            'faqs' => [
                [
                    'question' => '¿Cómo funcionan los cupones de Amazon?',
                    'answer' => 'En la página del producto, a veces verás una casilla para "Aplicar cupón de X% o X€". Solo tienes que marcarla y el descuento se aplicará automáticamente al tramitar el pedido.'
                ],
                [
                    'question' => '¿Hay códigos para Amazon Prime?',
                    'answer' => 'Ocasionalmente Amazon ofrece códigos para probar Prime gratis durante más tiempo, o descuentos para estudiantes a través de Prime Student (prueba de 90 días).'
                ]
            ]
        ],
        'shein' => [
            'intro_html' => '<p><strong>Shein</strong> se ha convertido en el referente de la moda rápida online. Su catálogo infinito se renueva a diario, y lo mejor es que siempre hay un <strong>código de descuento Shein</strong> disponible. Desde cupones del 15% sin mínimo hasta descuentos masivos del 25% para pedidos grandes.</p>',
            'description_html' => '
                <div class="ai-content-block">
                    <h3>👗 Moda trendy a precios increíbles con Códigos Shein</h3>
                    <p>Shein se ha convertido en el referente de la moda rápida online. Su catálogo infinito se renueva a diario, y lo mejor es que siempre, SIEMPRE hay un <strong>código de descuento Shein</strong> disponible. Desde cupones del 15% sin mínimo hasta descuentos masivos del 25% para pedidos grandes.</p>
                    
                    <h4>Maximiza tus ahorros en Shein</h4>
                    <p>El scretro de Shein es combinar: usa un código promocional, paga con tarjeta regalo (que a veces tiene descuento) y aplica tus Puntos Shein acumulados. ¡El precio final puede ser ridículamente bajo!</p>
                </div>
            ',
            'faqs' => [
                [
                    'question' => '¿Puedo acumular puntos y códigos descuento en Shein?',
                    'answer' => '¡Sí! Esa es la mejor estrategia. Puedes aplicar un Código de Cupón (porcentaje de descuento) y además usar tus Puntos Shein para pagar hasta el 70% del importe restante.'
                ],
                [
                    'question' => '¿Dónde encuentro los códigos de Shein?',
                    'answer' => 'Además de en CodigoAmigo, Shein suele mostrar códigos activos en su propia home y app. También envían cupones exclusivos por email a los usuarios registrados.'
                ]
            ]
        ]
    ];
    
    return isset($data[$brand_slug]) ? $data[$brand_slug] : null;
}
