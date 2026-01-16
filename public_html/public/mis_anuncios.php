<?php
// Verificar que la sesión esté iniciada y que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || $_SESSION["user_id"] == "") {
    header("Location: https://www.codigoamigo.com/login");
    exit;
}

// Verificar que las variables de sesión necesarias existan
if (!isset($_SESSION["username"]) || empty($_SESSION["username"])) {
    if (isset($data_usuario) && isset($data_usuario["username"])) {
        $_SESSION["username"] = $data_usuario["username"];
    } else {
        try {
            $usuario_temp = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
            if ($usuario_temp && isset($usuario_temp["username"])) {
                $_SESSION["username"] = $usuario_temp["username"];
            } else {
                header("Location: https://www.codigoamigo.com/login");
                exit;
            }
        } catch (Exception $e) {
            header("Location: https://www.codigoamigo.com/login");
            exit;
        }
    }
}

// Desactivar AdSense para esta página
$anula_adsense = true;
$GLOBALS['anula_adsense'] = true; // Asegurar que esté disponible globalmente

// Stripe config (evitar notices si no está seteado previamente)
if (!isset($stripe_live_publishable_key)) {
    if (isset($_SESSION["user_id"]) && in_array($_SESSION["user_id"], [
        '639899bc6321ee0d0e4010d2', // admins con modo test
        '58bd851da54e295b8b52f702',
        '5db1af3a2f55c82b47342172'
    ])) {
        $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
    } else {
        $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
    }
}
if (!isset($sku_patrocinado_splash)) {
    // Precio de Stripe para destacar todos (splash)
    $sku_patrocinado_splash = 'sku_H6ViM4K361ELMH';
}

get_header_modern("Mis Códigos - CodigoAmigo.com", "Administra todos tus códigos descuento publicados. Gestiona tu visibilidad, estadísticas y saldo para destacar tus ofertas.");

// Definir variables globales necesarias
if (!isset($GLOBALS['website'])) {
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
}
if (!isset($GLOBALS['actual_url'])) {
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Fallback: si venimos de un pago por saldo/stripe y hay señal de éxito en la URL,
// aseguramos el disparo de notificaciones de destacado (idempotente con guardado en sesión)
try {
    if (isset($_GET['success']) && $_GET['success'] === 'destacado' && isset($_GET['codigo'])) {
        $codigo_id_qs = $_GET['codigo'];
        $tipo_qs = isset($_GET['tipo']) && in_array($_GET['tipo'], ['normal','super']) ? $_GET['tipo'] : 'normal';

        if (!isset($_SESSION['last_destacado_notify']) || $_SESSION['last_destacado_notify'] !== $codigo_id_qs) {
            include_once __DIR__ . '/../myphp/funciones.php';
            if (function_exists('destacar_codigo_moderno')) {
                destacar_codigo_moderno($codigo_id_qs, $tipo_qs);
            }
            $_SESSION['last_destacado_notify'] = $codigo_id_qs;
        }
    }
} catch (Exception $e) {
    // silencioso
}

// Los códigos del usuario ya están disponibles desde app_with_mongo.php
?>

<!-- Incluir archivos CSS y JavaScript externos -->
<link rel="stylesheet" href="/assets/css/mis-anuncios.css">
<script src="/assets/js/mis-anuncios.js?<?php echo time(); ?>" defer></script>
<script>
// Wrapper defensivo: asegura que exista la función global para los onclick inline
function filterByVisibility(visibility){
    if (window.filterByVisibility) { return window.filterByVisibility(visibility); }
    document.addEventListener('DOMContentLoaded', function(){
        if (window.filterByVisibility) window.filterByVisibility(visibility);
    });
}
</script>


            <?php if(isset($_SESSION['msg_error']) && $_SESSION['msg_error'] != ""): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        mostrarModalError('Error', '<?php echo addslashes($_SESSION['msg_error']); ?>');
                    });
                </script>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'password_updated'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        mostrarModalExito('¡Contraseña Actualizada!', 'Tu contraseña se ha actualizado correctamente.');
                    });
                </script>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'account_activated'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        mostrarModalExito('¡Cuenta Activada!', '¡Cuenta activada correctamente! Bienvenido a Código Amigo 🎉');
                    });
                </script>
            <?php endif; ?>
            
            <?php unset($_SESSION['msg_success']); ?>
            <?php unset($_SESSION['msg_error']); ?>
            <!-- Welcome Section -->
            <div style="background: linear-gradient(135deg, #E30613, #667eea); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);">
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 12px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, 0.3);">
                            <i class="fas fa-user" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h2 style="margin: 0 0 5px 0; font-size: 1.8rem; font-weight: 700;">
                                ¡Hola <?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?>! 👋
                            </h2>
                            <p style="margin: 0; opacity: 0.9; font-size: 1rem;">
                                Gestiona todos tus códigos de descuento
                                <?php if(isset($data_usuario["fecha_registro"])): ?>
                                    <?php
                                    $dias = null;
                                    $raw = $data_usuario["fecha_registro"]; 
                                    try {
                                        if ($raw instanceof MongoDB\BSON\UTCDateTime) {
                                            $fecha_registro_dt = $raw->toDateTime();
                                        } elseif (is_numeric($raw)) {
                                            $fecha_registro_dt = (new DateTime())->setTimestamp((int)$raw);
                                        } elseif (is_string($raw) && strtotime($raw)) {
                                            $fecha_registro_dt = new DateTime($raw);
                                        } else {
                                            $fecha_registro_dt = null;
                                        }
                                        if ($fecha_registro_dt) {
                                            $hoy = new DateTime();
                                            $dias = $hoy->diff($fecha_registro_dt)->days;
                                        }
                                    } catch (Throwable $e) {
                                        $dias = null;
                                    }
                                    ?>
                                    <?php if($dias !== null): ?>• <?php echo (int)$dias; ?> días en la plataforma<?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Actions Bar (Inspirado en Chollometro) -->
            <div style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e9ecef;">
                <div class="compact-actions-bar" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; align-items: center;">
                    
                    <!-- Mi Página Pública -->
                    <a href="<?php echo link_usuario($_SESSION["username"], $_SESSION["user_id"]); ?>" target="_blank" class="action-card-purple" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: linear-gradient(135deg, #667eea, #764ba2); color: white !important; border-radius: 10px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); text-decoration: none; cursor: pointer; transition: all 0.3s ease;">
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 10px; border-radius: 50%;">
                            <i class="fas fa-external-link-alt" style="font-size: 1.2rem; color: white !important;"></i>
                        </div>
                        <div style="flex: 1; color: white !important;">
                            <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; color: white !important;">Mi página pública</div>
                            <div style="font-size: 0.8rem; opacity: 0.9; color: white !important;">Comparte tu perfil</div>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.2); color: white !important; padding: 8px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                            <i class="fas fa-link" style="margin-right: 4px; color: white !important;"></i>Ver
                        </div>
                    </a>

                    <!-- Saldo -->
                    <div class="action-card-green" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: linear-gradient(135deg, #4CAF50, #45a049); color: white !important; border-radius: 10px; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3); cursor: pointer; transition: all 0.3s ease;" onclick="document.getElementById('btn-recargar-saldo').click();">
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 10px; border-radius: 50%;">
                            <i class="fas fa-piggy-bank" style="font-size: 1.2rem; color: white !important;"></i>
                        </div>
                        <div style="flex: 1; color: white !important;">
                            <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; color: white !important;">Tu saldo</div>
                            <div style="font-size: 1.2rem; font-weight: 700; color: white !important;">
                                <?php
                                // Obtener saldo actualizado directamente de la base de datos
                                $collection_usuarios = getCollectionUsuarios();
                                $usuario_actualizado = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
                                $saldo_usuario = $usuario_actualizado['saldo'] ?? 0;
                                echo number_format($saldo_usuario, 2) . '€';
                                ?>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px;" onclick="event.stopPropagation();">
                            <button style="background: rgba(255, 255, 255, 0.2); color: white !important; padding: 6px 10px; border: none; border-radius: 4px; font-size: 0.7rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;" id="btn-recargar-saldo" onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.color='white';" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.color='white';">
                                <i class="fas fa-plus" style="margin-right: 2px;"></i>Añadir
                            </button>
                            <a href="/public/historial_recargas.php" style="background: rgba(255, 255, 255, 0.2); color: white !important; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 0.7rem; font-weight: 600; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.color='white';" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.color='white';">
                                <i class="fas fa-history" style="margin-right: 2px;"></i>Historial
                            </a>
                        </div>
                    </div>

                    <!-- Publicar -->
                    <a href="/nuevo_codigo" onclick="return confirmPublish();" class="action-card-orange" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: linear-gradient(135deg, #FF9800, #F57C00); color: white !important; border-radius: 10px; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3); text-decoration: none; cursor: pointer; transition: all 0.3s ease;">
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 10px; border-radius: 50%;">
                            <i class="fas fa-plus-circle" style="font-size: 1.2rem; color: white !important;"></i>
                        </div>
                        <div style="flex: 1; color: white !important;">
                            <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; color: white !important;">Publicar código</div>
                            <div style="font-size: 0.8rem; opacity: 0.9; color: white !important;">Crea una nueva oferta</div>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.2); color: white !important; padding: 8px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                            <i class="fas fa-plus" style="margin-right: 4px; color: white !important;"></i>Crear
                        </div>
                    </a>

                </div>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'destacado'): 
                // Obtener información del código destacado
                $codigo_id_modal = isset($_GET['codigo']) ? $_GET['codigo'] : '';
                $tipo_modal = isset($_GET['tipo']) && in_array($_GET['tipo'], ['normal','super']) ? $_GET['tipo'] : 'normal';
                
                $marca_nombre_modal = '';
                $marca_clave_modal = '';
                $enlaces_modal = '';
                
                try {
                    if ($codigo_id_modal) {
                        // Asegurar que las funciones estén disponibles
                        if (!function_exists('getObjectCodigo')) {
                            include_once __DIR__ . '/../myphp/funciones.php';
                        }
                        if (!function_exists('getObjectMarca')) {
                            include_once __DIR__ . '/../myphp/funciones.php';
                        }
                        
                        $codigo_modal = getObjectCodigo($codigo_id_modal);
                        
                        if ($codigo_modal && isset($codigo_modal['marca'])) {
                            $marca_clave_modal = $codigo_modal['marca'];
                            $marca_obj = getObjectMarca('nombre_clave', $marca_clave_modal);
                            
                            if ($marca_obj) {
                                $marca_nombre_modal = $marca_obj['nombre'] ?? $marca_clave_modal;
                                $marca_url_modal = '/de-' . $marca_clave_modal;
                                
                                if ($tipo_modal === 'normal') {
                                    $enlaces_modal = '<a href="' . htmlspecialchars($marca_url_modal) . '" style="color: #4CAF50; text-decoration: underline; font-weight: 600;">Ver en la página de ' . htmlspecialchars($marca_nombre_modal) . '</a>';
                                } else {
                                    $enlaces_modal = '<div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px; align-items: center;">';
                                    $enlaces_modal .= '<a href="' . htmlspecialchars($marca_url_modal) . '" style="color: #4CAF50; text-decoration: underline; font-weight: 600;">Ver en la página de ' . htmlspecialchars($marca_nombre_modal) . '</a>';
                                    $enlaces_modal .= '<a href="/" style="color: #4CAF50; text-decoration: underline; font-weight: 600;">Ver en la página principal</a>';
                                    $enlaces_modal .= '</div>';
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Si hay error, usar valores por defecto
                    error_log("Error obteniendo información del código destacado: " . $e->getMessage());
                }
                
                // Mensaje por defecto si no se encontró la marca
                if (empty($marca_nombre_modal)) {
                    $mensaje_modal = 'Tu código ha sido destacado y aparecerá en primera posición con el badge "Destacado".';
                    if ($tipo_modal === 'super') {
                        $mensaje_modal = 'Tu código ha sido destacado y aparecerá en primera posición con badge dorado, en la página de la marca y en la página principal.';
                    }
                } else {
                    $mensaje_modal = 'Tu código de <strong>' . htmlspecialchars($marca_nombre_modal) . '</strong> ha sido destacado y aparecerá en primera posición con el badge "Destacado".';
                    if ($tipo_modal === 'super') {
                        $mensaje_modal = 'Tu código de <strong>' . htmlspecialchars($marca_nombre_modal) . '</strong> ha sido destacado y aparecerá en primera posición con badge dorado, en la página de ' . htmlspecialchars($marca_nombre_modal) . ' y en la página principal.';
                    }
                }
            ?>
                <script>
                    // Verificar si ya se mostró el modal para este código (usando localStorage)
                    var codigoDestacadoId = <?php echo json_encode($codigo_id_modal); ?>;
                    var modalKey = 'destacado_modal_' + codigoDestacadoId;
                    var yaMostrado = localStorage.getItem(modalKey);
                    
                    // Solo mostrar si no se ha mostrado antes o si han pasado más de 5 minutos
                    var mostrarModal = true;
                    if (yaMostrado) {
                        var timestamp = parseInt(yaMostrado);
                        var ahora = Date.now();
                        // Si pasaron menos de 5 minutos, no mostrar
                        if (ahora - timestamp < 300000) {
                            mostrarModal = false;
                        }
                    }
                    
                    if (mostrarModal) {
                        // Guardar timestamp en localStorage
                        localStorage.setItem(modalKey, Date.now().toString());
                        
                        // Guardar los datos en variables globales para usarlas después
                        window.destacadoModalData = {
                            titulo: '¡Código Destacado!',
                            mensaje: <?php echo json_encode($mensaje_modal); ?>,
                            enlaces: <?php echo json_encode($enlaces_modal); ?>
                        };
                        
                        // Función para mostrar el modal cuando esté listo
                        function mostrarModalDestacadoCuandoListo() {
                            if (typeof mostrarModalExitoDestacado === 'function') {
                                mostrarModalExitoDestacado(
                                    window.destacadoModalData.titulo,
                                    window.destacadoModalData.mensaje,
                                    window.destacadoModalData.enlaces
                                );
                                
                                // Limpiar la URL después de mostrar el modal (sin recargar)
                                if (window.history && window.history.replaceState) {
                                    var nuevaUrl = window.location.pathname;
                                    window.history.replaceState({}, document.title, nuevaUrl);
                                }
                            } else if (typeof mostrarModalExito === 'function') {
                                // Fallback a la función original
                                mostrarModalExito(
                                    window.destacadoModalData.titulo,
                                    window.destacadoModalData.mensaje.replace(/<[^>]*>/g, '')
                                );
                                
                                // Limpiar la URL después de mostrar el modal
                                if (window.history && window.history.replaceState) {
                                    var nuevaUrl = window.location.pathname;
                                    window.history.replaceState({}, document.title, nuevaUrl);
                                }
                            } else {
                                // Si aún no está disponible, intentar de nuevo después de un breve delay
                                setTimeout(mostrarModalDestacadoCuandoListo, 100);
                            }
                        }
                        
                        // Intentar mostrar el modal cuando el DOM esté listo
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', mostrarModalDestacadoCuandoListo);
                        } else {
                            // Si el DOM ya está cargado, esperar un poco más para que las funciones estén definidas
                            setTimeout(mostrarModalDestacadoCuandoListo, 500);
                        }
                    } else {
                        // Si ya se mostró, limpiar la URL de todos modos
                        if (window.history && window.history.replaceState) {
                            var nuevaUrl = window.location.pathname;
                            window.history.replaceState({}, document.title, nuevaUrl);
                        }
                    }
                </script>
            <?php endif; ?>

            <!-- Close dashboard cards container and start codes section -->
            <div style="clear: both;"></div>

            <style>
            /* Estilos para la barra compacta inspirada en Chollometro */
            .compact-actions-bar > div > div {
                transition: all 0.3s ease;
                cursor: pointer;
            }
            
            .compact-actions-bar > div > div:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
            }
            
            .compact-actions-bar a:hover {
                background: rgba(255, 255, 255, 0.3) !important;
                transform: scale(1.05);
            }
            
            .compact-actions-bar button:hover {
                background: rgba(255, 255, 255, 0.3) !important;
                transform: scale(1.05);
            }
            
            /* Responsive para móviles */
            @media (max-width: 768px) {
                .compact-actions-bar {
                    grid-template-columns: 1fr !important;
                }
                
                .compact-actions-bar > div > div {
                    padding: 12px !important;
                }
                
                .compact-actions-bar > div > div > div:first-child {
                    padding: 8px !important;
                }
                
                .compact-actions-bar > div > div > div:first-child i {
                    font-size: 1rem !important;
                }
            }
            
            @media (max-width: 480px) {
                .compact-actions-bar > div > div {
                    flex-direction: column;
                    text-align: center;
                    gap: 10px !important;
                }
                
                .compact-actions-bar > div > div > div:last-child {
                    flex-direction: row !important;
                    justify-content: center;
                }
            /* Animaciones para eliminación de códigos */
            .code-card.code-item {
                transition: all 0.3s ease;
            }
            
            .code-card.code-item.eliminando {
                opacity: 0;
                transform: translateX(-100%);
                pointer-events: none;
            }
            
            .no-codes-message {
                text-align: center;
                padding: 40px;
                color: #666;
                font-size: 1.2rem;
                background: #f8f9fa;
                border-radius: 10px;
                margin: 20px 0;
            }
            
            .no-codes-message a {
                color: #2196F3;
                text-decoration: none;
                font-weight: bold;
            }
            
            .no-codes-message a:hover {
                text-decoration: underline;
            }
            
            /* Mejorar la animación de fadeIn para códigos */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .code-card.code-item {
                animation: fadeIn 0.3s ease-in;
            }
            </style>

            
            
        </div>
        
        
        <!-- Lista de códigos -->
        <div class="codes-section">
            <div class="codes-header">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                    

                    
                </div>
                
                <?php if (!$mostrando_todos && $codigos_ocultos > 0): ?>
                <div class="alert alert-info" style="margin-top: 10px; padding: 10px; background: #e3f2fd; border: 1px solid #2196f3; border-radius: 4px; color: #1976d2;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Información:</strong> Tienes <?php echo number_format($total_codigos_usuario); ?> códigos en total. 
                    Se están mostrando los <?php echo number_format($num_codigos); ?> más recientes. 
                    <?php if ($codigos_ocultos > 0): ?>
                        <span style="color: #d32f2f;"><?php echo number_format($codigos_ocultos); ?> códigos más antiguos están ocultos.</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Filtros de visibilidad - Diseño simplificado -->
            <div style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 1.5rem;">
                        🔍 Filtrar mis códigos
                    </h3>
                    <p style="margin: 0; color: #666; font-size: 1rem;">
                        Busca por texto o filtra por visibilidad
                    </p>
                </div>

                <!-- Filtro de texto -->
                <div style="margin-bottom: 25px;">
                    <div style="position: relative; max-width: 500px; margin: 0 auto;">
                        <input type="text" id="textFilter" placeholder="Buscar códigos por marca, descripción o código..." 
                               style="color:grey;width: 100%; padding: 15px 20px 15px 50px; border: 2px solid #e9ecef; border-radius: 25px; font-size: 1rem; background: #f8f9fa; transition: all 0.3s ease; box-sizing: border-box;"
                               onkeyup="filterByText(this.value)">
                        <i class="fas fa-search" style="position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem;"></i>
                        <button id="clearTextFilter" onclick="clearTextFilter()" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6c757d; cursor: pointer; font-size: 1.1rem; display: none;" title="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <button class="filter-button active" data-visibility="all" onclick="filterByVisibility('all')" style="background: #E3F2FD; color: #1976D2; padding: 15px; border: 2px solid #2196F3; border-radius: 10px; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-list" style="margin-right: 8px;"></i>
                        📋 Todos mis códigos (<?php echo count($listado_codigos); ?>)
                    </button>

                    <button class="filter-button" data-visibility="alta" onclick="filterByVisibility('alta')" style="background: #E8F5E8; color: #2E7D32; padding: 15px; border: 2px solid #4CAF50; border-radius: 10px; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-star" style="margin-right: 8px;"></i>
                        ⭐ Más visibles (<?php echo count(array_filter($listado_codigos, function($c) { return get_posicion_codigo_en_marca($c['_id'], $c['marca']) == 1; })); ?>)
                    </button>

                    <button class="filter-button" data-visibility="media" onclick="filterByVisibility('media')" style="background: #FFF3E0; color: #E65100; padding: 15px; border: 2px solid #FF9800; border-radius: 10px; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-eye" style="margin-right: 8px;"></i>
                        👁️ Visibles (<?php echo count(array_filter($listado_codigos, function($c) { return get_posicion_codigo_en_marca($c['_id'], $c['marca']) == 2; })); ?>)
                    </button>

                    <button class="filter-button" data-visibility="baja" onclick="filterByVisibility('baja')" style="background: #FFEBEE; color: #C62828; padding: 15px; border: 2px solid #F44336; border-radius: 10px; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-eye-slash" style="margin-right: 8px;"></i>
                        😴 Poco visibles (<?php echo count(array_filter($listado_codigos, function($c) { return get_posicion_codigo_en_marca($c['_id'], $c['marca']) > 2; })); ?>)
                    </button>

                    <button class="filter-button" data-visibility="destacados" onclick="filterByVisibility('destacados')" style="background: #FFF8E1; color: #F57C00; padding: 15px; border: 2px solid #FFC107; border-radius: 10px; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-star" style="margin-right: 8px;"></i>
                        🌟 Destacados (<?php echo count(array_filter($listado_codigos, function($c) { return isset($c['destacado']) && $c['destacado'] > 0; })); ?>)
                    </button>

                    <button class="filter-button" data-visibility="no_destacados" onclick="filterByVisibility('no_destacados')" style="background: #F3E5F5; color: #7B1FA2; padding: 15px; border: 2px solid #9C27B0; border-radius: 10px; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-star-o" style="margin-right: 8px;"></i>
                        ⭐ No destacados (<?php echo count(array_filter($listado_codigos, function($c) { return !isset($c['destacado']) || $c['destacado'] <= 0; })); ?>)
                    </button>

                    
                </div>

                <div style="margin-top: 15px; text-align: center;">
                    <p style="font-size: 0.9rem; color: #666; margin: 0;">
                        💡 <strong>Consejos:</strong> Los códigos "⭐ Más visibles" aparecen en primer lugar. Los "🌟 Destacados" tienen badge dorado y mayor visibilidad.
                    </p>
                </div>
                <?/*<br>

                <button onclick="destacarTodosCodigos()" style="width: 100%;background: linear-gradient(135deg, #FF9800, #F57C00); color: white; padding: 24px 30px; border: none; border-radius: 18px; font-weight: 700; font-size: 1.15rem; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 8px 25px rgba(255, 152, 0, 0.3); border: 2px solid transparent; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: -100%;  height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transition: left 0.5s;"></div>
                    <div style="margin-bottom: 8px;">
                        <i class="fas fa-star" style="font-size: 2.2rem; color: rgba(255,255,255,0.9);"></i>
                    </div>
                    <div>
                        <span style="display: block; line-height: 1.2; font-size: 1.1rem;">⭐ Destacar todos mis códigos</span>
                        <span style="display: block; font-size: 0.9rem; opacity: 0.9; margin-top: 4px;">9,99€ - Ahorra vs individual</span>
                    </div>
                </button>


                <!-- Explicación de la función destacar -->
            <div style="background: linear-gradient(135deg, #FFF3E0, #FFE0B2); padding: 25px; border-radius: 18px; margin-bottom: 40px; border-left: 6px solid #FF9800; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.1);">
                <div style="display: flex; align-items: flex-start; gap: 18px;">
                    <div style="background: #FF9800; color: white; padding: 12px; border-radius: 50%; flex-shrink: 0;">
                        <i class="fas fa-lightbulb" style="font-size: 1.4rem;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0 0 12px 0; color: #E65100; font-size: 1.25rem; font-weight: 700; line-height: 1.3;">💡 ¿Qué hace "Destacar todos mis códigos"?</h3>
                        <p style="margin: 0 0 15px 0; color: #BF360C; font-size: 1.05rem; line-height: 1.6;">
                            Con un solo click, todos tus códigos aparecerán en las <strong style="color: #D84315;">primeras posiciones</strong> de cada marca. Así serán mucho más visibles para los usuarios que buscan códigos de descuento.
                        </p>
                        <p style="margin: 0; font-size: 0.95rem; color: #E65100; font-weight: 600; background: rgba(255, 152, 0, 0.1); padding: 8px 12px; border-radius: 8px; display: inline-block;">
                            💰 Costo: 9,99€ <span style="font-weight: 400; color: #2E7D32;">(¡Ahorra vs destacar individual!)</span>
                        </p>
                    </div>
                </div>
            </div>*/?>
              

                
            </div>
        
        <?php if($listado_codigos && count($listado_codigos) > 0): ?>
            <div class="sort-controls" style="display: flex; align-items: center; gap: 12px;">
                        <label for="sortSelect" style="color: #495057; font-weight: 600; font-size: 1rem; white-space: nowrap;">Ordenar por:</label>
                        <select id="sortSelect" onchange="sortCodes(this.value)" style="padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 10px; background: white; color: #495057; font-weight: 500; cursor: pointer; transition: all 0.3s ease; min-width: 160px; font-size: 1rem;">
                            <option value="fecha_desc" selected>Más recientes</option>
                            <option value="fecha_asc">Más antiguos</option>
                            <option value="marca_asc">Marca A-Z</option>
                            <option value="marca_desc">Marca Z-A</option>
                            <option value="clicks_desc">Más visitados</option>
                            <option value="clicks_asc">Menos visitados</option>
                            <option value="beneficio_desc">Mayor beneficio</option>
                            <option value="beneficio_asc">Menor beneficio</option>
                        </select>
                    </div>
            <div class="codes-grid">
            <?php foreach($listado_codigos as $codigo): ?>
                <?php
                // Verificar que $codigo['marca'] existe y no es null
                if (!isset($codigo['marca']) || $codigo['marca'] === null) {
                    continue;
                }
                
                $marca = getObjectMarca('nombre_clave', $codigo['marca']);
                if (!$marca) {
                    continue;
                }
                
                $posicion = get_posicion_codigo_en_marca($codigo['_id'], $codigo['marca']);
                // Verificar si está destacado: puede ser timestamp (número) o boolean
                $is_destacado = false;
                if (isset($codigo['destacado'])) {
                    // Convertir a array si es un objeto MongoDB
                    if (is_object($codigo['destacado'])) {
                        $codigo['destacado'] = (string)$codigo['destacado'];
                    }
                    
                    if (is_numeric($codigo['destacado'])) {
                        $is_destacado = (float)$codigo['destacado'] > 0;
                    } elseif (is_bool($codigo['destacado'])) {
                        $is_destacado = $codigo['destacado'];
                    } elseif (is_string($codigo['destacado']) && $codigo['destacado'] !== '' && $codigo['destacado'] !== '0') {
                        $is_destacado = true;
                    }
                }
                
                // Determinar clase de visibilidad
                $visibilidad_class = '';
                $visibilidad_text = '';
                if($posicion == 1) {
                    $visibilidad_class = 'alta';
                    $visibilidad_text = 'Alta Visibilidad';
                } elseif($posicion == 2) {
                    $visibilidad_class = 'media';
                    $visibilidad_text = 'Media Visibilidad';
                } else {
                    $visibilidad_class = 'baja';
                    $visibilidad_text = 'Baja Visibilidad';
                }
                
                // Obtener categoría para filtros
                $categoria_nombre = '';
                if(isset($codigo['categoria']) && !empty($codigo['categoria'])) {
                    $categoria_obj = getObjectCategoria($codigo['categoria']);
                    if($categoria_obj) {
                        $categoria_nombre = $categoria_obj['nombre'];
                    }
                }
                
                // Formatear fecha para filtros
                $fecha_publicacion = '';
                if(isset($codigo['fecha_publicacion']) && !empty($codigo['fecha_publicacion'])) {
                    $fecha_publicacion = date('Y-m-d', strtotime($codigo['fecha_publicacion']));
                }
                ?>
                
                <?php 
                // Sistema mejorado de búsqueda de imágenes
                $imagen_url = '';
                $marca_nombre_clave = strtolower($codigo['marca']);
                
                // 1. Buscar en los campos de la marca
                if (isset($marca['url_imagen']) && !empty($marca['url_imagen'])) {
                    $imagen_url = $marca['url_imagen'];
                } elseif (isset($marca['imagen']) && !empty($marca['imagen'])) {
                    $imagen_url = $marca['imagen'];
                } elseif (isset($marca['logo']) && !empty($marca['logo'])) {
                    $imagen_url = $marca['logo'];
                }
                
                // 2. Si no hay imagen específica, buscar en el directorio de marcas
                if (empty($imagen_url)) {
                    $formatos = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
                    $imagen_encontrada = false;
                    
                    foreach ($formatos as $formato) {
                        $ruta_imagen = "/img/marcas/{$marca_nombre_clave}.{$formato}";
                        $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . $ruta_imagen;
                        
                        if (file_exists($ruta_fisica)) {
                            $imagen_url = $ruta_imagen;
                            $imagen_encontrada = true;
                            break;
                        }
                    }
                    
                    // 3. Si no se encuentra, usar imagen por defecto
                    if (!$imagen_encontrada) {
                        $imagen_url = '/img/no_image.png';
                    }
                }
                
                // Procesar URL de imagen
                if (!empty($imagen_url)) {
                    // Convertir URLs de cdn.codigoamigo.com a URLs directas del servidor
                    if (strpos($imagen_url, 'cdn.codigoamigo.com') !== false) {
                        // Extraer el path de la URL del CDN
                        $path = parse_url($imagen_url, PHP_URL_PATH);
                        if ($path) {
                            // Convertir a URL directa del servidor
                            // Si es panel_marcas, necesita /img/ antes
                            if (strpos($path, '/panel_marcas/') !== false) {
                                $imagen_url = 'https://www.codigoamigo.com/img' . $path;
                            } else {
                                $imagen_url = 'https://www.codigoamigo.com' . $path;
                            }
                        }
                    }
                    // Si no empieza con http/https/data:, convertir a URL relativa y luego absoluta
                    elseif (!str_starts_with($imagen_url, 'http') && !str_starts_with($imagen_url, 'data:')) {
                        if (!str_starts_with($imagen_url, '/')) {
                            $imagen_url = '/' . $imagen_url;
                        }
                        $imagen_url = 'https://www.codigoamigo.com' . $imagen_url;
                    }
                    // Convertir http a https si es necesario
                    if (strpos($imagen_url, 'http://') !== false) {
                        $imagen_url = str_replace('http://', 'https://', $imagen_url);
                    }
                }
                
                $marca_url = '/de-' . strtolower($codigo['marca']);
                
                // Formatear fecha
                $fecha_formateada = '';
                if(isset($codigo['fecha_publicacion']) && !empty($codigo['fecha_publicacion'])) {
                    $timestamp = strtotime($codigo['fecha_publicacion']);
                    $diferencia = time() - $timestamp;
                    if($diferencia < 86400) { // Menos de un día
                        $fecha_formateada = 'Hoy';
                    } elseif($diferencia < 172800) { // Menos de 2 días
                        $fecha_formateada = 'Ayer';
                    } elseif($diferencia < 604800) { // Menos de una semana
                        $fecha_formateada = 'Hace ' . floor($diferencia / 86400) . ' días';
                    } else {
                        $fecha_formateada = date('d/m/Y', $timestamp);
                    }
                }
                
                $descripcion = $codigo['descripcion'] ?? 'Código de descuento válido';
                ?>
                
                <div class="code-card code-item mis-anuncios-card" data-visibilidad="<?php echo $visibilidad_class; ?>" data-categoria="<?php echo htmlspecialchars($categoria_nombre); ?>" data-fecha="<?php echo $fecha_publicacion; ?>" data-marca="<?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>" data-clicks="<?php echo $codigo['totalclicks'] ?? 0; ?>" data-beneficio="<?php echo $codigo['num_beneficio'] ?? 10; ?>" data-destacado="<?php echo $is_destacado ? 'si' : 'no'; ?>" data-codigo-id="<?php echo $codigo['_id']; ?>">

                    <!-- Badge de destacado -->
                    <?php if($is_destacado): ?>
                        <div class="featured-badge"><i class="fas fa-star"></i> Destacado</div>
                    <?php endif; ?>

                    <!-- Imagen de la marca (clickeable) - Estilo moderno -->
                    <div class="code-brand-image">
                        <a href="/de-<?php echo $codigo['marca']; ?>?codigo=<?php echo $codigo['_id']; ?>" class="brand-link">
                            <?php if(!empty($imagen_url)): ?>
                                <img loading="lazy" src="<?php echo htmlspecialchars($imagen_url); ?>" alt="<?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>" class="brand-image">
                            <?php else: ?>
                                <div class="brand-placeholder"><i class="fas fa-tag"></i></div>
                            <?php endif; ?>
                        </a>
                        <!-- Badge flotante con nombre de marca -->
                        <div class="brand-name-badge">
                            <?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>
                        </div>
                        <!-- Badge flotante con fecha -->
                        <?php if(!empty($fecha_formateada)): ?>
                            <div class="brand-date-badge">
                                <i class="far fa-clock"></i>
                                <span><?php echo htmlspecialchars($fecha_formateada); ?></span>
                            </div>
                        <?php endif; ?>
                        <!-- Badge flotante con visitas (izquierda, abajo) -->
                        <div class="visits-badge">
                            <i class="far fa-eye"></i>
                            <span>Visitas <?php echo number_format($codigo['totalclicks'] ?? 0); ?></span>
                        </div>
                    </div>
                    
                    <!-- Badge de visibilidad con posición (fuera del contenedor de imagen para evitar z-index) -->
                    <div class="top-left-badges">
                        <div class="visibility-badge visibility-<?php echo $visibilidad_class; ?>">
                            <span class="position-in-badge">Posición #<?php echo $posicion; ?></span>
                            <?php echo $visibilidad_text; ?>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="code-description">
                        <div class="code-description-text">
                            <?php 
                            $descripcion_corta = mb_strlen($descripcion) > 120 ? mb_substr($descripcion, 0, 120) . '...' : $descripcion;
                            echo htmlspecialchars($descripcion_corta); 
                            if(mb_strlen($descripcion) > 120): ?>
                                <span class="read-more-link" onclick="toggleDescripcion('<?php echo $codigo['_id']; ?>')">ver más</span>
                            <?php endif; ?>
                            <p id="desc-full-<?php echo $codigo['_id']; ?>" style="display: none; margin-top: 0.5rem;">
                                <?php echo htmlspecialchars($descripcion); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Beneficio -->
                    <?php if(isset($codigo['num_beneficio']) && $codigo['num_beneficio'] > 0): ?>
                        <div class="code-meta-info">
                            <div class="beneficio-destacado">
                                <div class="beneficio-icono">💰</div>
                                <div class="beneficio-contenido">
                                    <div class="beneficio-cantidad"><?php echo $codigo['num_beneficio']; ?>€</div>
                                    <div class="beneficio-tipo">BENEFICIO</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Código de descuento -->
                    <div class="code-code-display">
                        <span class="code-label">Código:</span>
                        <span class="code-value"><?php echo htmlspecialchars($codigo['codigo'] ?? 'N/A'); ?></span>
                    </div>

                    <!-- Botones de acción -->
                    <div class="code-actions">
                        <a href="/destacar_codigo?codigo=<?php echo $codigo['_id']; ?>" class="btn-action btn-destacar">
                            <i class="fas fa-star"></i> Destacar
                        </a>
                        <?php 
                        // Check if brand has Super Landing
                        $has_super_landing = false;
                        if (!function_exists('get_active_super_landings')) {
    // Ensure createConnection is available
    require_once __DIR__ . '/../myphp/funciones.php';
    include_once __DIR__ . '/../myphp/_super_landing_functions.php';
}
                        if (function_exists('get_active_super_landings')) {
                            $all_sl = get_active_super_landings(50);
                            foreach ($all_sl as $sl) {
                                // Convert MongoDB BSON Array to PHP array
                                $linked_slugs = isset($sl['linked_brand_slugs']) ? 
                                    (is_array($sl['linked_brand_slugs']) ? $sl['linked_brand_slugs'] : iterator_to_array($sl['linked_brand_slugs'])) : 
                                    [];
                                    
                                if (in_array($codigo['marca'], $linked_slugs)) {
                                    $has_super_landing = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($has_super_landing): 
                            $is_super = isset($codigo['tipo_destacado']) && $codigo['tipo_destacado'] === 'super';
                        ?>
                            <a href="/destacar_super.php?codigo_id=<?php echo $codigo['_id']; ?>" 
                               class="btn-action btn-super <?php echo $is_super ? 'super-active' : ''; ?>" 
                               title="Destacar en la Guía Oficial">
                                <i class="fas fa-trophy"></i> Super
                                <?php if ($is_super): ?><i class="fas fa-check-circle" style="margin-left: 4px;"></i><?php endif; ?>
                            </a>
                        <?php endif; ?>
                        
                        <a href="/modificar_codigo/<?php echo $codigo['_id']; ?>" class="btn-action btn-modificar">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        
                        <button onclick="mostrarEstadisticas('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($codigo['marca'] ?? ''); ?>')" class="btn-action btn-estadisticas">
                            <i class="fas fa-chart-bar"></i> Stats
                        </button>
                        <button onclick="confirmarEliminarCodigo('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($marca['nombre'] ?? $codigo['marca']); ?>')" class="btn-action btn-eliminar">
                            <i class="fas fa-trash"></i> Borrar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: white; border-radius: 20px; padding: 50px; text-align: center; box-shadow: 0 6px 20px rgba(0,0,0,0.1); margin: 40px 0;">
                <div style="font-size: 5rem; margin-bottom: 20px;">🎯</div>
                <h2 style="margin: 0 0 20px 0; color: #333; font-size: 2rem;">¡Aún no tienes códigos publicados!</h2>
                <p style="margin: 0 0 30px 0; color: #666; font-size: 1.2rem; line-height: 1.6;">
                    Comienza a publicar tus códigos de descuento para ayudar a otros usuarios a ahorrar dinero.
                    <br><br>
                    <strong>¿Por qué publicar códigos?</strong><br>
                    • Otras personas usarán tus códigos y te darán una comisión<br>
                    • Ayudas a la comunidad a encontrar las mejores ofertas<br>
                    • Ganas dinero por cada compra que generes
                </p>
                <a href="/nuevo_codigo" style="background: #4CAF50; color: white; padding: 15px 30px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">
                    <i class="fas fa-plus-circle"></i>
                    Publicar mi primer código
                </a>

                <div style="margin-top: 30px; padding: 20px; background: #E8F5E8; border-radius: 10px; border-left: 5px solid #4CAF50;">
                    <p style="margin: 0; color: #2E7D32; font-size: 1rem;">
                        💡 <strong>Consejo:</strong> Empieza con códigos de tiendas que conozcas bien. ¡Es muy fácil y puedes empezar a ganar dinero desde el primer día!
                    </p>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>


<!-- Modal para recargar saldo - Diseño simplificado -->
<div class="modal fade" id="modal_recargar_saldo" tabindex="-1" role="dialog" aria-labelledby="modalRecargarSaldoLabel">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 100%;">
            <div class="modal-header" style="background: linear-gradient(135deg, #4CAF50, #45a049); color: white; border-radius: 20px 20px 0 0; padding: 30px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white; font-size: 2rem; opacity: 0.8; margin: 0; position: absolute; right: 20px; top: 15px;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalRecargarSaldoLabel" style="font-size: 2rem; margin: 0; display: flex; align-items: center; gap: 15px; width: 100%;">
                    <i class="fas fa-piggy-bank" style="font-size: 2.5rem;"></i>
                    💳 Recargar Saldo
                </h4>
            </div>

            <div class="modal-body" style="padding: 40px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h5 style="color: #FFFFFF !important; font-size: 1.5rem; margin-bottom: 10px; font-weight: 700 !important;">¿Cuánto dinero quieres añadir?</h5>
                    <p style="color: #E0E0E0 !important; margin: 0; font-size: 1rem; font-weight: 500;">Elige un paquete y obtén saldo extra gratis</p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <!-- Paquete 20€ -->
                    <div class="package-card" data-package="20" data-amount="25" style="background: #ffffff; border: 3px solid #4CAF50; border-radius: 15px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.3s ease; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div style="position: absolute; top: -12px; right: -12px; background: #4CAF50; color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; box-shadow: 0 2px 6px rgba(76, 175, 80, 0.3); z-index: 10;">+25%</div>
                        <div style="margin-bottom: 15px; margin-top: 10px;">
                            <div style="font-size: 2.2rem; font-weight: 800; color: #212529; line-height: 1.2;">20€</div>
                            <div style="font-size: 1.3rem; color: #4CAF50; font-weight: 700; margin-top: 5px;">→ 25€</div>
                        </div>
                        <div style="color: #2E7D32; font-weight: 700; font-size: 1.1rem; background: #E8F5E9; padding: 8px; border-radius: 8px;">+5€ GRATIS</div>
                    </div>

                    <!-- Paquete 40€ - POPULAR -->
                    <div class="package-card popular" data-package="40" data-amount="50" style="background: linear-gradient(135deg, #FFF8E1, #FFE082); border: 3px solid #FF9800; border-radius: 15px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.3s ease; position: relative; transform: scale(1.05); box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);">
                        <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: #FF9800; color: white; padding: 8px 20px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; box-shadow: 0 2px 8px rgba(255, 152, 0, 0.4); z-index: 10; white-space: nowrap;">★ MÁS POPULAR ★</div>
                        <div style="margin: 25px 0 15px 0;">
                            <div style="font-size: 2.8rem; font-weight: 800; color: #212529; line-height: 1.2;">40€</div>
                            <div style="font-size: 1.6rem; color: #FF9800; font-weight: 700; margin-top: 5px;">→ 50€</div>
                        </div>
                        <div style="color: #E65100; font-weight: 700; font-size: 1.2rem; background: #FFF3E0; padding: 8px; border-radius: 8px;">+10€ GRATIS</div>
                    </div>

                    <!-- Paquete 100€ -->
                    <div class="package-card" data-package="100" data-amount="150" style="background: #ffffff; border: 3px solid #9C27B0; border-radius: 15px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.3s ease; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div style="position: absolute; top: -12px; right: -12px; background: #9C27B0; color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; box-shadow: 0 2px 6px rgba(156, 39, 176, 0.3); z-index: 10;">+50%</div>
                        <div style="margin-bottom: 15px; margin-top: 10px;">
                            <div style="font-size: 2.2rem; font-weight: 800; color: #212529; line-height: 1.2;">100€</div>
                            <div style="font-size: 1.3rem; color: #9C27B0; font-weight: 700; margin-top: 5px;">→ 150€</div>
                        </div>
                        <div style="color: #7B1FA2; font-weight: 700; font-size: 1.1rem; background: #F3E5F5; padding: 8px; border-radius: 8px;">+50€ GRATIS</div>
                    </div>
                </div>

                <div style="background: rgba(76, 175, 80, 0.2); padding: 20px; border-radius: 10px; border-left: 5px solid #4CAF50; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-shield-alt" style="color: #4CAF50; font-size: 1.8rem;"></i>
                        <div>
                            <h6 style="margin: 0 0 5px 0; color: #4CAF50 !important; font-size: 1.15rem; font-weight: 700;">🔒 Pago 100% Seguro</h6>
                            <p style="margin: 0; color: #E0E0E0 !important; font-size: 0.95rem; font-weight: 500;">Procesamos tu pago con Stripe, líder mundial en pagos online</p>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: #6c757d; color: #ffffff; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background='#5a6268';" onmouseout="this.style.background='#6c757d';">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>

            <div class="modal-footer" style="border: none; padding: 20px 40px 30px; text-align: center; border-radius: 0 0 20px 20px;">
                <small style="color: #E0E0E0 !important; font-size: 0.9rem; font-weight: 500;">
                    🔒 Tus pagos están protegidos por Stripe. Nunca almacenamos tus datos de tarjeta.
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal para destacar todos (splash) -->
<div class="modal fade" id="modal_destacar_todos" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Destacar Todos los Códigos (Splash)</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres destacar todos tus códigos en posición 1 por 9,99€?</p>
                <p>Esta acción destacará todos tus códigos de una vez con máxima visibilidad.</p>
                <p><strong>Costo total: 9,99€</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmar_destacar_todos">Confirmar y Pagar</button>
            </div>
        </div>
    </div>
</div>

<!-- Cargar Stripe -->
<script src="https://js.stripe.com/v3/"></script>

<script type="text/javascript">
var stripe = Stripe('<?php echo $stripe_live_publishable_key; ?>');

// Función global para filtrar por texto
window.filterByText = function(searchText) {
    const textFilter = document.getElementById('textFilter');
    const clearButton = document.getElementById('clearTextFilter');
    
    // Mostrar/ocultar botón de limpiar
    if (searchText.length > 0) {
        clearButton.style.display = 'block';
    } else {
        clearButton.style.display = 'none';
    }
    
    // Obtener todas las tarjetas de código
    const codeItems = document.querySelectorAll('.code-item');
    let visibleCount = 0;
    
    codeItems.forEach(function(item) {
        const marca = item.getAttribute('data-marca') || '';
        const descElement = item.querySelector('p[id^="desc"]');
        const descripcion = descElement ? descElement.textContent : '';
        const codigoElement = item.querySelector('p[style*="monospace"]');
        const codigo = codigoElement ? codigoElement.textContent : '';
        
        const searchLower = searchText.toLowerCase();
        const marcaLower = marca.toLowerCase();
        const descripcionLower = descripcion.toLowerCase();
        const codigoLower = codigo.toLowerCase();
        
        const shouldShow = searchText === '' || 
                         marcaLower.includes(searchLower) || 
                         descripcionLower.includes(searchLower) || 
                         codigoLower.includes(searchLower);
        
        if (shouldShow) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease-in';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Actualizar contadores en los botones de filtro
    updateFilterCounts();
    
    // Mostrar mensaje si no hay resultados
    showNoResultsMessage(visibleCount === 0 && searchText.length > 0, 'No se encontraron códigos que coincidan con "' + searchText + '"');
};

// Función para limpiar el filtro de texto
window.clearTextFilter = function() {
    const textFilter = document.getElementById('textFilter');
    const clearButton = document.getElementById('clearTextFilter');
    
    textFilter.value = '';
    clearButton.style.display = 'none';
    
    // Mostrar todos los códigos
    const codeItems = document.querySelectorAll('.code-item');
    codeItems.forEach(function(item) {
        item.style.display = 'block';
    });
    
    updateFilterCounts();
    showNoResultsMessage(false);
};

// Función para actualizar contadores de filtros
function updateFilterCounts() {
    const codeItems = document.querySelectorAll('.code-item');
    const visibleItems = document.querySelectorAll('.code-item:not([style*="display: none"])');
    
    // Actualizar contador de "Todos"
    const allButton = document.querySelector('[data-visibility="all"]');
    if (allButton) {
        allButton.innerHTML = `<i class="fas fa-list" style="margin-right: 8px;"></i>📋 Todos mis códigos (${visibleItems.length})`;
    }
    
    // Contar por visibilidad solo de los elementos visibles
    let altaCount = 0, mediaCount = 0, bajaCount = 0;
    visibleItems.forEach(function(item) {
        const visibilidad = item.getAttribute('data-visibilidad');
        if (visibilidad === 'alta') altaCount++;
        else if (visibilidad === 'media') mediaCount++;
        else if (visibilidad === 'baja') bajaCount++;
    });
    
    // Actualizar botones de visibilidad
    const altaButton = document.querySelector('[data-visibility="alta"]');
    const mediaButton = document.querySelector('[data-visibility="media"]');
    const bajaButton = document.querySelector('[data-visibility="baja"]');
    
    if (altaButton) altaButton.innerHTML = `<i class="fas fa-star" style="margin-right: 8px;"></i>⭐ Más visibles (${altaCount})`;
    if (mediaButton) mediaButton.innerHTML = `<i class="fas fa-eye" style="margin-right: 8px;"></i>👁️ Visibles (${mediaCount})`;
    if (bajaButton) bajaButton.innerHTML = `<i class="fas fa-eye-slash" style="margin-right: 8px;"></i>😴 Poco visibles (${bajaCount})`;
}

// Función para mostrar mensaje de no resultados
function showNoResultsMessage(show, message = '') {
    let noResultsMessage = document.getElementById('no-results-message');
    
    if (show && !noResultsMessage) {
        noResultsMessage = document.createElement('div');
        noResultsMessage.id = 'no-results-message';
        noResultsMessage.className = 'no-results';
        noResultsMessage.innerHTML = `
            <i class="fas fa-search"></i>
            <h3>No se encontraron códigos</h3>
            <p>${message}</p>
        `;
        document.querySelector('.codes-section').appendChild(noResultsMessage);
    } else if (noResultsMessage) {
        noResultsMessage.style.display = show ? 'block' : 'none';
        if (show && message) {
            noResultsMessage.querySelector('p').textContent = message;
        }
    }
}

// Función global para filtrar por visibilidad
window.filterByVisibility = function(visibility) {
    // Actualizar botones activos
    document.querySelectorAll('.filter-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Marcar el botón seleccionado como activo
    const selectedButton = document.querySelector(`[data-visibility="${visibility}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // Obtener todas las tarjetas de código
    const codeItems = document.querySelectorAll('.code-item');
    let visibleCount = 0;
    
    codeItems.forEach(function(item) {
        const itemVisibility = item.getAttribute('data-visibilidad');
        let shouldShow = false;
        
        if (visibility === 'all') {
            shouldShow = true;
        } else if (visibility === 'alta' && itemVisibility === 'alta') {
            shouldShow = true;
        } else if (visibility === 'media' && itemVisibility === 'media') {
            shouldShow = true;
        } else if (visibility === 'baja' && itemVisibility === 'baja') {
            shouldShow = true;
        }
        
        if (shouldShow) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease-in';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Actualizar contadores
    updateFilterCounts();
    
    // Mostrar mensaje si no hay resultados
    const visibilityNames = {
        'alta': 'alta visibilidad',
        'media': 'media visibilidad', 
        'baja': 'baja visibilidad'
    };
    const message = visibility !== 'all' ? `No hay códigos con ${visibilityNames[visibility] || visibility} visibilidad` : '';
    showNoResultsMessage(visibleCount === 0 && visibility !== 'all', message);
};


/* Estilos adicionales para el diseño mejorado */
.filter-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.code-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Animación suave para los filtros */
.filter-button {
    animation: fadeInUp 0.3s ease-out;
}

/* Mejorar accesibilidad */
.filter-button:focus,
.code-card button:focus,
.code-card a:focus {
    outline: 2px solid #2196F3;
    outline-offset: 2px;
}

/* Responsive mejorado */
@media (max-width: 768px) {
    .main-actions {
        grid-template-columns: 1fr;
    }

    .balance-section .d-flex {
        flex-direction: column;
    }

    .code-card {
        padding: 20px;
    }

    .code-card .main-actions {
        grid-template-columns: 1fr 1fr;
    }

    .package-card {
        padding: 20px 15px;
    }

    .package-card.popular {
        transform: none;
    }
}

/* Estilos para los paquetes de saldo */
.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.package-card:not(.popular):hover {
    border-color: #2196F3;
}

.package-card.popular:hover {
    transform: translateY(-5px) scale(1.05);
}

/* Animación para seleccionar paquete */
.package-card.selected {
    border-color: #4CAF50;
    background: #E8F5E8;
    animation: pulse 0.3s ease-in-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

/* Mejorar el header principal */
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    color: white;
    text-align: center;
}

/* Content wrapper styles moved to CSS file */

// Función para destacar todos los códigos (splash)
function destacarTodosSplash() {
    $('#modal_destacar_todos').modal('show');
}

// Seleccionar paquete de saldo y proceder directamente al pago
$(document).on('click', '.simple-package', function() {
    $('.simple-package').removeClass('selected');
    $(this).addClass('selected');
    paqueteSeleccionado = $(this).data('package');
    
    // Proceder directamente al pago
    if (paqueteSeleccionado) {
        var precio = paqueteSeleccionado;
        var saldo = $(this).data('amount');
        
        // Mostrar loading
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Procesando...');
        
        // Redirigir directamente a crear sesión de Stripe
        var url = '/public/crear_sesion_recarga.php?' + 
                  'paquete=' + encodeURIComponent(paqueteSeleccionado) +
                  '&precio=' + encodeURIComponent(precio) +
                  '&saldo=' + encodeURIComponent(saldo) +
                  '&usuario_id=' + encodeURIComponent('<?php echo $_SESSION["user_id"]; ?>');
        
        window.location.href = url;
    }
});


// Confirmar destacar todos (splash)
$('#confirmar_destacar_todos').click(function() {
    // Redirigir a destacar todos con saldo
    window.location.href = '/destacar_con_saldo?tipo=splash';
});

// Función para obtener el ID de precio según el paquete
function getPriceId(paquete) {
    switch(paquete) {
        case '20': return 'price_20euros_2000'; // 20€ = 2000 céntimos
        case '40': return 'price_40euros_4000'; // 40€ = 4000 céntimos
        case '100': return 'price_100euros_10000'; // 100€ = 10000 céntimos
        default: return 'price_20euros_2000';
    }
}

// Verificar que la función esté disponible

// Variables globales para el sistema de saldo
var paqueteSeleccionado = null;

$(document).ready(function() {
    
    // Ordenar códigos por defecto (más recientes primero)
    sortCodes('fecha_desc');
    
    // Destacar todos los códigos
    $('#destacar_todos').click(function() {
        stripe.redirectToCheckout({
            lineItems: [{price: '<?php echo $sku_patrocinado_splash; ?>', quantity: 1}],
            mode: 'payment',
            clientReferenceId: '<?php echo $_SESSION["user_id"]; ?>',
            billingAddressCollection: 'auto',
            successUrl: '<?php echo $GLOBALS["website"];?>felicidades_splash?session_id={CHECKOUT_SESSION_ID}&todos=1',
            cancelUrl: '<?php echo $GLOBALS["actual_url"]; ?>',
        }).then(function (result) {
            if (result.error) {
                var displayError = document.getElementById('error-message');
                if(displayError) displayError.textContent = result.error.message;
            }
        });
    });
});


// Función para mostrar todos los códigos
function showAllCodes() {
    // Remover clase active de todas las pestañas
    $('.tab-button').removeClass('active');
    
    // Activar pestaña "TODOS"
    $('.tab-button[data-visibility="all"]').addClass('active');
    
    // Mostrar todos los códigos
    $('.code-item').show();
    
    // Actualizar contador de códigos visibles
    updateVisibleCount();
}

// Función para actualizar el contador de códigos visibles
function updateVisibleCount() {
    var visibleCount = $('.code-item:visible').length;
    var totalCount = $('.code-item').length;
    
    // Actualizar el título de la sección
    $('#codesTitle').text('Tus códigos (' + visibleCount + ' de ' + totalCount + ')');
}

// Función para compartir código
function compartirCodigo(codigoId) {
    // Aquí puedes implementar la lógica para compartir
    alert('Función de compartir código: ' + codigoId);
}

// Función para seleccionar radio button y aplicar filtros automáticamente
function selectRadio(radioId) {
    document.getElementById(radioId).checked = true;
    applyFilters();
}

// Función para toggle checkbox y aplicar filtros automáticamente
function toggleCheckbox(checkboxId) {
    var checkbox = document.getElementById(checkboxId);
    checkbox.checked = !checkbox.checked;
    applyFilters();
}

// Función para aplicar todos los filtros
function applyFilters() {
    // Obtener filtro de fecha seleccionado
    var fechaFiltro = 'todo'; // Valor por defecto
    var fechaRadio = document.querySelector('input[name="fecha_filtro"]:checked');
    if(fechaRadio) {
        fechaFiltro = fechaRadio.value;
    }
    
    // Obtener categorías seleccionadas
    var categoriasSeleccionadas = [];
    document.querySelectorAll('#categoria-filters input[type="checkbox"]:checked').forEach(function(checkbox) {
        var categoria = checkbox.closest('.filter-option').getAttribute('data-categoria');
        if(categoria) {
            categoriasSeleccionadas.push(categoria);
        }
    });
    
    // Obtener visibilidades seleccionadas
    var visibilidadesSeleccionadas = [];
    document.querySelectorAll('input[type="checkbox"][id^="vis_"]:checked').forEach(function(checkbox) {
        var visibilidad = checkbox.closest('.filter-option').getAttribute('data-visibilidad');
        if(visibilidad) {
            visibilidadesSeleccionadas.push(visibilidad);
        }
    });
    
    // Obtener ordenamiento seleccionado
    var ordenamiento = 'fecha_desc'; // Valor por defecto
    var ordenRadio = document.querySelector('input[name="orden_filtro"]:checked');
    if(ordenRadio) {
        ordenamiento = ordenRadio.value;
    }
    
    // Filtrar códigos
    var visibleCount = 0;
    
    $('.code-item').each(function() {
        var $item = $(this);
        var showItem = true;
        
        // Filtro por fecha
        if(fechaFiltro !== 'todo') {
            var fechaCodigo = $item.data('fecha');
            if(fechaCodigo) {
                var hoy = new Date();
                var fechaCodigoObj = new Date(fechaCodigo);
                
                if(fechaFiltro === 'hoy') {
                    // Solo códigos de hoy
                    var inicioDia = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
                    var finDia = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + 1);
                    if(fechaCodigoObj < inicioDia || fechaCodigoObj >= finDia) {
                        showItem = false;
                    }
                } else if(fechaFiltro === 'semana') {
                    // Códigos de la semana pasada
                    var haceUnaSemana = new Date(hoy.getTime() - 7 * 24 * 60 * 60 * 1000);
                    if(fechaCodigoObj < haceUnaSemana) {
                        showItem = false;
                    }
                }
            } else {
                showItem = false;
            }
        }
        
        // Filtro por categoría
        if(categoriasSeleccionadas.length > 0) {
            var categoriaCodigo = $item.data('categoria');
            if(!categoriaCodigo || categoriasSeleccionadas.indexOf(categoriaCodigo) === -1) {
                showItem = false;
            }
        }
        
        // Filtro por visibilidad
        if(visibilidadesSeleccionadas.length > 0) {
            var visibilidadCodigo = $item.data('visibilidad');
            if(!visibilidadCodigo || visibilidadesSeleccionadas.indexOf(visibilidadCodigo) === -1) {
                showItem = false;
            }
        }
        
        if(showItem) {
            $item.show();
            visibleCount++;
        } else {
            $item.hide();
        }
    });
    
    // Aplicar ordenamiento a los elementos visibles usando la misma lógica que sortCodes()
    var $visibleItems = $('.code-item:visible');
    var $container = $('.codes-section');
    
    $visibleItems.sort(function(a, b) {
        switch(ordenamiento) {
            case 'fecha_desc':
                return new Date($(b).data('fecha') || 0) - new Date($(a).data('fecha') || 0);
            case 'fecha_asc':
                return new Date($(a).data('fecha') || 0) - new Date($(b).data('fecha') || 0);
            case 'marca_asc':
                return ($(a).data('marca') || '').localeCompare($(b).data('marca') || '');
            case 'marca_desc':
                return ($(b).data('marca') || '').localeCompare($(a).data('marca') || '');
            case 'clicks_desc':
                return parseInt($(b).data('clicks') || 0) - parseInt($(a).data('clicks') || 0);
            case 'clicks_asc':
                return parseInt($(a).data('clicks') || 0) - parseInt($(b).data('clicks') || 0);
            case 'beneficio_desc':
                return parseInt($(b).data('beneficio') || 0) - parseInt($(a).data('beneficio') || 0);
            case 'beneficio_asc':
                return parseInt($(a).data('beneficio') || 0) - parseInt($(b).data('beneficio') || 0);
            case 'visibilidad':
                var visA = $(a).data('visibilidad') || 'baja';
                var visB = $(b).data('visibilidad') || 'baja';
                var order = {'alta': 1, 'media': 2, 'baja': 3};
                return order[visA] - order[visB];
            case 'categoria':
                var catA = $(a).data('categoria') || '';
                var catB = $(b).data('categoria') || '';
                return catA.localeCompare(catB);
            default:
                return 0;
        }
    });
    
    // Reordenar los elementos en el DOM
    if($container.length > 0) {
        $visibleItems.detach().appendTo($container);
    }
    
    // Actualizar contador
    updateVisibleCount();
    
    // Actualizar clases activas de filtros
    updateFilterStates();
}

// Función para limpiar todos los filtros
function clearAllFilters() {
    // Resetear radio button de fecha a "todo el tiempo"
    document.getElementById('fecha_todo').checked = true;
    
    // Desmarcar todos los checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
        checkbox.checked = false;
    });
    
    // Mostrar todos los códigos
    $('.code-item').show();
    
    // Actualizar contador
    updateVisibleCount();
    
    // Actualizar clases activas
    updateFilterStates();
}

// Función para actualizar el estado visual de los filtros
function updateFilterStates() {
    // Actualizar clases de opciones de filtro
    document.querySelectorAll('.filter-option').forEach(function(option) {
        var checkbox = option.querySelector('input[type="checkbox"]');
        if(checkbox && checkbox.checked) {
            option.classList.add('active');
        } else {
            option.classList.remove('active');
        }
    });
}

// Función para alternar la barra lateral en móviles
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
    
    // Prevenir scroll del body cuando el menú está abierto
    if (sidebar.classList.contains('open')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
}

// Función para cerrar la barra lateral
function closeSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = 'auto';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    var overlay = document.getElementById('sidebar-overlay');
    var toggleButton = document.querySelector('.mobile-filter-toggle');
    
    // Cerrar menú al hacer clic en el overlay
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Cerrar menú al hacer clic fuera en móviles
    document.addEventListener('click', function(event) {
        var sidebar = document.getElementById('sidebar');
        
        if(window.innerWidth <= 768 && 
           sidebar && sidebar.classList.contains('open') &&
           !sidebar.contains(event.target) && 
           (!toggleButton || !toggleButton.contains(event.target))) {
            closeSidebar();
        }
    });
    
    // Cerrar menú al redimensionar la ventana a desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
});

// Función para compartir código
function compartirCodigo(codigoId, marca) {
    const shareUrl = `https://www.codigoamigo.com/de-${marca.toLowerCase()}?codigo=${codigoId}`;
    const shareText = `¡Mira este código de descuento de ${marca}!`;
    
    if (navigator.share) {
        // Usar Web Share API si está disponible
        navigator.share({
            title: `Código de descuento ${marca}`,
            text: shareText,
            url: shareUrl
        }).catch(function(err) {
            // Fallback a copiar al portapapeles
            copiarEnlace(shareUrl);
        });
    } else {
        // Fallback para navegadores que no soportan Web Share API
        copiarEnlace(shareUrl);
    }
}

// Función para copiar enlace al portapapeles
function copiarEnlace(url) {
    navigator.clipboard.writeText(url).then(function() {
        // Mostrar notificación de éxito
        mostrarNotificacion('Enlace copiado al portapapeles', 'success');
    }).catch(function(err) {
        // Fallback manual
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        mostrarNotificacion('Enlace copiado al portapapeles', 'success');
    });
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    notificacion.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${mensaje}</span>
    `;
    
    // Agregar estilos
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${tipo === 'success' ? '#28a745' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    `;
    
    // Agregar animación CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Agregar al DOM
    document.body.appendChild(notificacion);
    
    // Remover después de 3 segundos
    setTimeout(function() {
        notificacion.style.animation = 'slideOut 0.3s ease';
        setTimeout(function() {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}

// Variable global para rastrear si hay un modal abierto
let modalAbierto = false;

// Función global para cerrar todos los modales
function cerrarTodosLosModales() {
    // Resetear la variable de control
    modalAbierto = false;
    
    // Cerrar todos los modales (incluyendo el modal de estadísticas)
    const modales = document.querySelectorAll('[id*="Modal"], .modal, #estadisticasModal');
    modales.forEach(modal => {
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
    });
    
    // También eliminar cualquier modal que pueda estar dentro de un iframe
    try {
        const iframes = document.querySelectorAll('iframe');
        iframes.forEach(iframe => {
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                const iframeModals = iframeDoc.querySelectorAll('[id*="Modal"], .modal');
                iframeModals.forEach(modal => {
                    if (modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                });
            } catch (e) {
                // Ignorar errores de cross-origin
            }
        });
    } catch (e) {
        // Ignorar errores de cross-origin
    }
    
    document.body.style.overflow = 'auto';
}

// Función para mostrar estadísticas en modal
function mostrarEstadisticas(codigoId, marca) {
    // Si ya hay un modal abierto, no abrir otro
    if (modalAbierto) {
        return;
    }
    
    // Verificar que el DOM esté listo
    if (document.readyState !== 'loading') {
        // Cerrar cualquier modal existente antes de abrir uno nuevo
        cerrarTodosLosModales();
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            cerrarTodosLosModales();
        });
    }
    
    // Verificar si ya existe un modal de estadísticas
    const modalExistente = document.getElementById('estadisticasModal');
    if (modalExistente) {
        modalExistente.remove();
    }
    
    // Marcar que hay un modal abierto
    modalAbierto = true;
    
    // Crear iframe para cargar las estadísticas
    const modal = document.createElement('div');
    modal.id = 'estadisticasModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    `;
    
    const iframe = document.createElement('iframe');
    iframe.src = `/estadisticas?codigo=${codigoId}`;
    iframe.style.cssText = `
        width: 100%;
        max-width: 800px;
        height: 90vh;
        border: none;
        border-radius: 12px;
        background: white;
    `;
    
    const closeButton = document.createElement('button');
    closeButton.innerHTML = '&times;';
    closeButton.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        background: #E30613;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
        cursor: pointer;
        z-index: 10001;
    `;
    closeButton.onclick = () => {
        modalAbierto = false;
        cerrarTodosLosModales();
    };
    
    modal.appendChild(iframe);
    modal.appendChild(closeButton);
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Cerrar modal al hacer click fuera del iframe
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modalAbierto = false;
            cerrarTodosLosModales();
        }
    });
    
    // Cerrar modal con tecla Escape
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            modalAbierto = false;
            cerrarTodosLosModales();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

// Función para copiar ID al portapapeles
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // Usar la API moderna de clipboard
        navigator.clipboard.writeText(text).then(function() {
            showCopyNotification('ID copiado al portapapeles');
        }).catch(function(err) {
            fallbackCopyTextToClipboard(text);
        });
    } else {
        // Fallback para navegadores más antiguos
        fallbackCopyTextToClipboard(text);
    }
}

// Función fallback para copiar texto
function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            showCopyNotification('ID copiado al portapapeles');
        } else {
            showCopyNotification('Error al copiar', 'error');
        }
    } catch (err) {
        showCopyNotification('Error al copiar', 'error');
    }
    
    document.body.removeChild(textArea);
}

// Función para mostrar notificación de copia
function showCopyNotification(message, type = 'success') {
    // Crear elemento de notificación
    var notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-size: 14px;
        font-weight: 500;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}


// Agregar animación CSS para el fadeIn
var style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-10px); }
    }
    
    .no-results {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
        background: #f8f9fa;
        border-radius: 12px;
        margin: 20px 0;
    }
    
    .no-results i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 20px;
    }
    
    .no-results h3 {
        color: #495057;
        margin-bottom: 10px;
    }
    
    /* Estilos para tarjetas de acción superior */
    .action-card-purple {
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
        color: white !important;
    }
    
    .action-card-purple:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
        color: white !important;
    }
    
    .action-card-purple:hover * {
        color: white !important;
    }
    
    .action-card-green {
        background: linear-gradient(135deg, #4CAF50, #45a049) !important;
        color: white !important;
    }
    
    .action-card-green:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4) !important;
        background: linear-gradient(135deg, #4CAF50, #45a049) !important;
        color: white !important;
    }
    
    .action-card-green:hover * {
        color: white !important;
    }
    
    .action-card-orange {
        background: linear-gradient(135deg, #FF9800, #F57C00) !important;
        color: white !important;
    }
    
    .action-card-orange:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4) !important;
        background: linear-gradient(135deg, #FF9800, #F57C00) !important;
        color: white !important;
    }
    
    .action-card-orange:hover * {
        color: white !important;
    }
    
    /* Estilos para modal de recargar saldo */
    #modal_recargar_saldo.modal {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: center !important;
        padding: 0 !important;
        padding-top: 10vh !important;
    }
    
    #modal_recargar_saldo.modal.show {
        display: flex !important;
    }
    
    #modal_recargar_saldo.modal.fade .modal-dialog {
        transition: transform 0.3s ease-out;
        transform: translate(0, 0) !important;
    }
    
    #modal_recargar_saldo.modal.show .modal-dialog {
        transform: translate(0, 0) !important;
    }
    
    #modal_recargar_saldo .modal-dialog {
        margin: 0 auto !important;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        min-height: auto;
        max-height: 90vh;
        padding: 20px;
        position: relative;
        top: 0 !important;
        transform: translate(0, 0) !important;
        max-width: 900px;
        margin-top: 10vh !important;
    }
    
    #modal_recargar_saldo .modal-dialog-centered {
        display: flex;
        align-items: flex-start;
        min-height: auto;
    }
    
    #modal_recargar_saldo .modal-content {
        width: 100%;
        max-width: 900px;
    }
    
    #modal_recargar_saldo .modal-body {
        background-color: #2C2C2C !important;
        color: #FFFFFF !important;
    }
    
    #modal_recargar_saldo .modal-body h5 {
        color: #FFFFFF !important;
    }
    
    #modal_recargar_saldo .modal-body p {
        color: #E0E0E0 !important;
    }
    
    #modal_recargar_saldo .modal-content {
        background-color: #2C2C2C !important;
    }
    
    #modal_recargar_saldo .modal-footer {
        background-color: #2C2C2C !important;
        color: #E0E0E0 !important;
    }
    
    #modal_recargar_saldo .modal-footer small {
        color: #E0E0E0 !important;
    }
    
    #modal_recargar_saldo .package-card {
        min-height: 200px;
    }
    
    #modal_recargar_saldo .package-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
    }
    
    #modal_recargar_saldo .package-card.popular:hover {
        transform: scale(1.05) translateY(-5px);
        box-shadow: 0 8px 25px rgba(255, 152, 0, 0.4) !important;
    }
    
    .simple-recharge-modal {
        border-radius: 15px;
        overflow: hidden;
    }
    
    .simple-packages {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin: 20px 0;
    }
    
    .simple-package {
        position: relative;
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .simple-package:hover {
        border-color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }
    
    .simple-package.selected {
        border-color: #007bff;
        background: #f8f9ff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }
    
    .simple-package.popular {
        border-color: #28a745;
        background: #f8fff9;
    }
    
    .popular-label {
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        background: #28a745;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .package-badge {
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 15px;
    }
    
    .package-price {
        margin: 15px 0;
    }
    
    .pay {
        color: #6c757d;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .get {
        color: #007bff;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .package-savings {
        color: #28a745;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .security-note {
        text-align: center;
        color: #6c757d;
        font-size: 0.9rem;
        margin-top: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .security-note i {
        color: #28a745;
        margin-right: 5px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .simple-packages {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .simple-package {
            padding: 15px;
        }
    }
    
    .package-popular:hover {
        border-color: #9c27b0;
        background: linear-gradient(135deg, #f3e5f5, #e1bee7);
        box-shadow: 0 10px 30px rgba(156, 39, 176, 0.3);
    }
    
    .package-premium {
        border-color: #ff9800;
        background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    }
    
    .package-premium:hover {
        border-color: #ff9800;
        background: linear-gradient(135deg, #fff3e0, #ffe0b2);
        box-shadow: 0 10px 30px rgba(255, 152, 0, 0.3);
    }
    
    .package-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: linear-gradient(135deg, #E30613, #C40510);
        color: white;
        padding: 8px 20px;
        border-radius: 0 20px 0 20px;
        font-size: 0.8rem;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 12px rgba(227, 6, 19, 0.4);
    }
    
    .package-badge.popular {
        background: linear-gradient(135deg, #9c27b0, #7b1fa2);
    }
    
    .package-badge.premium {
        background: linear-gradient(135deg, #ff9800, #f57c00);
    }
    
    .popular-ribbon {
        position: absolute;
        top: 15px;
        left: -30px;
        background: #9c27b0;
        color: white;
        padding: 5px 40px;
        font-size: 0.7rem;
        font-weight: bold;
        transform: rotate(-45deg);
        box-shadow: 0 2px 8px rgba(156, 39, 176, 0.3);
    }
    
    .package-icon {
        font-size: 3rem;
        margin: 15px 0;
        color: #E30613;
    }
    
    .package-popular .package-icon {
        color: #9c27b0;
    }
    
    .package-premium .package-icon {
        color: #ff9800;
    }
    
    .package-pricing {
        margin: 20px 0;
    }
    
    .pay-amount {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 8px;
    }
    
    .get-amount {
        font-size: 1.8rem;
        font-weight: bold;
        color: #E30613;
        margin-bottom: 15px;
    }
    
    .package-popular .get-amount {
        color: #9c27b0;
    }
    
    .package-premium .get-amount {
        color: #ff9800;
    }
    
    .package-bonus {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 12px 20px;
        border-radius: 25px;
        font-weight: bold;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    
    .package-bonus.premium-bonus {
        background: linear-gradient(135deg, #ff9800, #ff5722);
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
    }
    
    .package-bonus i {
        font-size: 1.2rem;
    }
`;
document.head.appendChild(style);

// Función para ordenar códigos
window.sortCodes = function(sortBy) {
    const codesContainer = document.querySelector('.codes-section');
    if (!codesContainer) return;
    
    const codes = Array.from(codesContainer.querySelectorAll('.code-item'));
    
    codes.sort((a, b) => {
        switch(sortBy) {
            case 'fecha_desc':
                return new Date(b.dataset.fecha) - new Date(a.dataset.fecha);
            case 'fecha_asc':
                return new Date(a.dataset.fecha) - new Date(b.dataset.fecha);
            case 'marca_asc':
                return (a.dataset.marca || '').localeCompare(b.dataset.marca || '');
            case 'marca_desc':
                return (b.dataset.marca || '').localeCompare(a.dataset.marca || '');
            case 'clicks_desc':
                return parseInt(b.dataset.clicks || 0) - parseInt(a.dataset.clicks || 0);
            case 'clicks_asc':
                return parseInt(a.dataset.clicks || 0) - parseInt(b.dataset.clicks || 0);
            case 'beneficio_desc':
                return parseInt(b.dataset.beneficio || 0) - parseInt(a.dataset.beneficio || 0);
            case 'beneficio_asc':
                return parseInt(a.dataset.beneficio || 0) - parseInt(b.dataset.beneficio || 0);
            default:
                return 0;
        }
    });
    
    // Reorganizar los elementos en el DOM
    codes.forEach(code => codesContainer.appendChild(code));
    
    // Mostrar mensaje de ordenación
};

// Función para confirmar eliminación de código
function confirmarEliminarCodigo(codigoId, marcaNombre) {
    // Verificar que el DOM esté listo
    if (document.readyState !== 'loading') {
        crearModalEliminar();
    } else {
        document.addEventListener('DOMContentLoaded', crearModalEliminar);
    }

    function crearModalEliminar() {
    const modal = document.createElement('div');
    modal.id = 'modalEliminarCodigo';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        ">
            <div style="color: #dc3545; font-size: 3rem; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="color: #333; margin-bottom: 15px; font-size: 1.5rem;">
                ¿Eliminar código?
            </h3>
            <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">
                ¿Estás seguro de que quieres eliminar el código de <strong>${marcaNombre}</strong>?<br>
                <span style="color: #dc3545; font-weight: bold;">Esta acción no se puede deshacer.</span>
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button id="cancelarEliminar" style="
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 12px 25px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button id="confirmarEliminar" style="
                    background: #dc3545;
                    color: white;
                    border: none;
                    padding: 12px 25px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Event listeners
    document.getElementById('cancelarEliminar').onclick = () => {
        document.body.removeChild(modal);
        document.body.style.overflow = 'auto';
    };
    
    document.getElementById('confirmarEliminar').onclick = () => {
        eliminarCodigo(codigoId, marcaNombre);
        document.body.removeChild(modal);
        document.body.style.overflow = 'auto';
    };
    
    // Cerrar modal al hacer click fuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
            document.body.style.overflow = 'auto';
        }
    };
    }
}

// Función para eliminar el código usando formulario tradicional (evita bloqueo de Cloudflare)
function eliminarCodigo(codigoId, marcaNombre) {
    // Crear formulario para enviar petición POST tradicional
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/delete_code_action';
    form.style.display = 'none';
    
    // Añadir campo oculto con el ID del código
    const codigoInput = document.createElement('input');
    codigoInput.type = 'hidden';
    codigoInput.name = 'codigo_id';
    codigoInput.value = codigoId;
    form.appendChild(codigoInput);
    
    // Añadir formulario al DOM y enviarlo
    document.body.appendChild(form);
    form.submit();
}

// Función para toggle de descripción
function toggleDescripcion(codigoId) {
    const descFull = document.getElementById('desc-full-' + codigoId);
    const descText = descFull ? descFull.parentElement.querySelector('.code-description-text') : null;
    const readMoreLink = descText ? descText.querySelector('.read-more-link') : null;

    if (descFull && descFull.style.display === 'none') {
        descFull.style.display = 'block';
        if (readMoreLink) {
            readMoreLink.textContent = 'ver menos';
        }
    } else if (descFull) {
        descFull.style.display = 'none';
        if (readMoreLink) {
            readMoreLink.textContent = 'ver más';
        }
    }
}

// Función para actualizar el contador de códigos
function actualizarContadorCodigos() {
    const codigosVisibles = document.querySelectorAll('.code-item:not([style*="display: none"])').length;
    const titulo = document.getElementById('codesTitle');
    if (titulo) {
        titulo.textContent = `Tus códigos (${codigosVisibles})`;
    }
}

// Función para mostrar modal de éxito
function mostrarModalExito(titulo, mensaje, autoCerrar = true) {
    // Cerrar cualquier modal existente
    cerrarTodosLosModales();

    const modal = document.createElement('div');
    modal.id = 'modalExito';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        animation: fadeIn 0.3s ease-out;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideInUp 0.4s ease-out;
            position: relative;
        ">
            <button id="cerrarModalExito" style="
                position: absolute;
                top: 15px;
                right: 15px;
                background: #f0f0f0;
                border: none;
                border-radius: 50%;
                width: 35px;
                height: 35px;
                font-size: 1.2rem;
                cursor: pointer;
                transition: background 0.3s ease;
            ">&times;</button>

            <div style="color: #4CAF50; font-size: 4rem; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 2rem; font-weight: bold;">
                ${titulo}
            </h2>

            <p style="color: #666; font-size: 1.2rem; line-height: 1.5; margin: 0 0 30px 0;">
                ${mensaje}
            </p>

            <button id="aceptarModalExito" style="
                background: linear-gradient(135deg, #4CAF50, #45a049);
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 1.1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            ">
                <i class="fas fa-check" style="margin-right: 8px;"></i>
                ¡Perfecto!
            </button>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Event listeners
    const cerrarBtn = document.getElementById('cerrarModalExito');
    const aceptarBtn = document.getElementById('aceptarModalExito');

    const cerrarModal = () => {
        document.body.removeChild(modal);
        document.body.style.overflow = 'auto';
    };

    cerrarBtn.onclick = cerrarModal;
    aceptarBtn.onclick = cerrarModal;

    // Cerrar modal al hacer click fuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            cerrarModal();
        }
    };

    // Cerrar modal con tecla Escape
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            cerrarModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);

    // Auto-cerrar después de 5 segundos si está habilitado
    if (autoCerrar) {
        setTimeout(cerrarModal, 5000);
    }
}

// Función para mostrar modal de éxito con enlaces (para destacados)
function mostrarModalExitoDestacado(titulo, mensaje, enlaces = '', autoCerrar = false) {
    // Cerrar cualquier modal existente
    cerrarTodosLosModales();

    const modal = document.createElement('div');
    modal.id = 'modalExito';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        animation: fadeIn 0.3s ease-out;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideInUp 0.4s ease-out;
            position: relative;
        ">
            <button id="cerrarModalExito" style="
                position: absolute;
                top: 15px;
                right: 15px;
                background: #f0f0f0;
                border: none;
                border-radius: 50%;
                width: 35px;
                height: 35px;
                font-size: 1.2rem;
                cursor: pointer;
                transition: background 0.3s ease;
            ">&times;</button>

            <div style="color: #4CAF50; font-size: 4rem; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 2rem; font-weight: bold;">
                ${titulo}
            </h2>

            <div style="color: #666; font-size: 1.2rem; line-height: 1.5; margin: 0 0 30px 0;">
                ${mensaje}
            </div>

            ${enlaces ? '<div style="margin: 20px 0;">' + enlaces + '</div>' : ''}

            <button id="aceptarModalExito" style="
                background: linear-gradient(135deg, #4CAF50, #45a049);
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 1.1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
                margin-top: 20px;
            ">
                <i class="fas fa-check" style="margin-right: 8px;"></i>
                ¡Perfecto!
            </button>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Event listeners
    const cerrarBtn = document.getElementById('cerrarModalExito');
    const aceptarBtn = document.getElementById('aceptarModalExito');

    const cerrarModal = () => {
        document.body.removeChild(modal);
        document.body.style.overflow = 'auto';
    };

    cerrarBtn.onclick = cerrarModal;
    aceptarBtn.onclick = cerrarModal;

    // Cerrar modal al hacer click fuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            cerrarModal();
        }
    };

    // Cerrar modal con tecla Escape
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            cerrarModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

// Función para confirmar publicación de código
function confirmPublish() {
    return confirm('¿Estás seguro de que quieres publicar un nuevo código? Serás redirigido al formulario de publicación.');
}

// Función para mostrar modal de error
function mostrarModalError(titulo, mensaje, autoCerrar = false) {
    // Cerrar cualquier modal existente
    cerrarTodosLosModales();

    const modal = document.createElement('div');
    modal.id = 'modalError';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        animation: fadeIn 0.3s ease-out;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideInUp 0.4s ease-out;
            position: relative;
        ">
            <button id="cerrarModalError" style="
                position: absolute;
                top: 15px;
                right: 15px;
                background: #f0f0f0;
                border: none;
                border-radius: 50%;
                width: 35px;
                height: 35px;
                font-size: 1.2rem;
                cursor: pointer;
                transition: background 0.3s ease;
            ">&times;</button>

            <div style="color: #f44336; font-size: 4rem; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 2rem; font-weight: bold;">
                ${titulo}
            </h2>

            <p style="color: #666; font-size: 1.2rem; line-height: 1.5; margin: 0 0 30px 0;">
                ${mensaje}
            </p>

            <button id="aceptarModalError" style="
                background: linear-gradient(135deg, #f44336, #d32f2f);
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 1.1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
            ">
                <i class="fas fa-check" style="margin-right: 8px;"></i>
                Entendido
            </button>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Event listeners
    const cerrarBtn = document.getElementById('cerrarModalError');
    const aceptarBtn = document.getElementById('aceptarModalError');

    const cerrarModal = () => {
        document.body.removeChild(modal);
        document.body.style.overflow = 'auto';
    };

    cerrarBtn.onclick = cerrarModal;
    aceptarBtn.onclick = cerrarModal;

    // Cerrar modal al hacer click fuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            cerrarModal();
        }
    };

    // Cerrar modal con tecla Escape
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            cerrarModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);

    // Auto-cerrar después de 7 segundos si está habilitado (errores no se auto-cierran por defecto)
    if (autoCerrar) {
        setTimeout(cerrarModal, 7000);
    }
}
</script>

<!-- Footer con sección de ayuda -->
<footer class="footer-modern" style="background: #2c2c2c; padding: 40px 20px; margin-top: auto; text-align: center; position: relative; bottom: 0; left: 0; right: 0; width: 100%;">
    <div class="container" style="max-width: 1200px; margin: 0 auto;">
        <div class="help-section" style="background: linear-gradient(135deg, #E30613, #C40510); padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(227, 6, 19, 0.3);">
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 15px;">
                <i class="fa-brands fa-telegram" style="font-size: 2.5rem; color: white;"></i>
                <div>
                    <h3 style="color: white; margin: 0; font-size: 1.8rem; font-weight: bold;">¿Necesitas ayuda?</h3>
                    <p style="color: white; margin: 5px 0 0 0; font-size: 1.2rem; opacity: 0.9;">¡Escríbenos!</p>
                </div>
            </div>
            <a href="https://t.me/spnfury" target="_blank" style="display: inline-block; background: white; color: #E30613; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 3px 10px rgba(0,0,0,0.2);">
                <i class="fa-brands fa-telegram" style="margin-right: 8px;"></i>
                Contactar por Telegram
            </a>
        </div>
        
        <div class="footer-links" style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="/" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Inicio</a>
            <a href="/todos-los-codigos" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Todos los Códigos</a>
            <a href="/marcas" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Marcas</a>
            <a href="/categorias" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Categorías</a>
        </div>
        
        <div class="footer-bottom" style="border-top: 1px solid #404040; padding-top: 20px; color: #888888; font-size: 0.9rem;">
            <p style="margin: 0;">© 2024 Código Amigo - Códigos verificados, gente real</p>
        </div>
    </div>
</footer>


<!-- Cerrar el HTML correctamente -->
</body>
</html>