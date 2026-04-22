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

// POTENCIAL DE GANANCIAS
include_once __DIR__ . '/../myphp/funciones_usuario.php';
$potencial_data = obtener_potencial_completo_usuario($_SESSION["user_id"]);
$total_potential = $potencial_data['total_potential'];
$total_viewers = $potencial_data['total_unique_viewers'];
$all_viewer_ids = $potencial_data['all_viewer_ids'];

// Comprobar si es VIP (para mass-message.js)
$is_vip_user = es_usuario_vip($_SESSION["user_id"]);

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
<link rel="stylesheet" href="/assets/css/mis-anuncios.css?v=<?php echo time(); ?>">
<script src="/assets/js/mis-anuncios.js?<?php echo time(); ?>" defer></script>
<script src="/js/mass-message.js?v=<?php echo time(); ?>" defer></script>
<script>
window.userIsVip = <?php echo $is_vip_user ? 'true' : 'false'; ?>;
window.jsConfig = {
    userId: <?php echo json_encode($_SESSION["user_id"] ?? ""); ?>,
    skuPatrocinadoSplash: <?php echo json_encode($sku_patrocinado_splash ?? ""); ?>,
    website: <?php echo json_encode($GLOBALS["website"] ?? ""); ?>,
    actualUrl: <?php echo json_encode($GLOBALS["actual_url"] ?? ""); ?>
};

// Wrapper defensivo: asegura que exista la función global para los onclick inline
function filterByVisibility(visibility){
    if (window.filterByVisibility) { return window.filterByVisibility(visibility); }
    document.addEventListener('DOMContentLoaded', function(){
        if (window.filterByVisibility) window.filterByVisibility(visibility);
    });
}
</script>

<style>
/* INLINE CSS to bypass Cloudflare CDN cache */
.btn-modificar-neutral, .btn-stats-neutral, .btn-eliminar-neutral, .btn-compartir {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #b0b0b0 !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}
.btn-modificar-neutral:hover {
    background: #28a745 !important; color: white !important; border-color: #28a745 !important; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3) !important;
}
.btn-stats-neutral:hover {
    background: #17a2b8 !important; color: white !important; border-color: #17a2b8 !important; box-shadow: 0 4px 10px rgba(23, 162, 184, 0.3) !important;
}
.btn-eliminar-neutral:hover {
    background: #dc3545 !important; color: white !important; border-color: #dc3545 !important; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3) !important;
}
.btn-compartir:hover {
    background: #6f42c1 !important; color: white !important; border-color: #6f42c1 !important; box-shadow: 0 4px 10px rgba(111, 66, 193, 0.3) !important;
}
.btn-action.btn-reactivar {
    background: linear-gradient(135deg, #6f42c1, #59339d) !important;
    color: white !important;
    border: none !important;
}
.btn-action.btn-reactivar:hover {
    background: linear-gradient(135deg, #59339d, #45267c) !important;
    box-shadow: 0 4px 12px rgba(111, 66, 193, 0.35) !important;
}
</style>

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
            <div style="background: #fff; color: #1a1a2e; padding: 24px 28px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-left: 4px solid #E30613;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="background: #f5f5f5; color: #E30613; padding: 10px; border-radius: 50%;">
                            <i class="fas fa-user" style="font-size: 1.3rem;"></i>
                        </div>
                        <div>
                            <h2 style="margin: 0 0 3px 0; font-size: 1.5rem; font-weight: 700; color: #1a1a2e;">
                                Hola <?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?> 👋
                                <?php if (isset($is_vip_user) && $is_vip_user): ?>
                                    <span style="background: #FFF8E1; color: #F57C00; padding: 3px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #FFE0B2; margin-left: 8px; vertical-align: middle;">
                                        <i class="fas fa-crown"></i> VIP
                                    </span>
                                <?php endif; ?>
                            </h2>
                            <p style="margin: 0; color: #888; font-size: 0.9rem;">
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

            <!-- Dashboard de Potencial de Ganancias -->
            <div class="potential-summary-card" style="background: #fff; padding: 20px 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="background: #FFF5F5; color: #E30613; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: #999; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Ganancias Potenciales</div>
                        <div style="display: flex; align-items: baseline; gap: 8px;">
                            <span style="font-size: 1.8rem; font-weight: 800; color: #1a1a2e; line-height: 1;"><?php echo number_format($total_potential, 2, ',', '.'); ?>€</span>
                            <span style="font-size: 0.85rem; color: #4CAF50; font-weight: 600;"><i class="fas fa-arrow-up"></i> <?php echo number_format($total_viewers, 0, ',', '.'); ?> interesados</span>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="text-align: right; max-width: 220px;">
                        <div style="font-size: 0.8rem; color: #999; line-height: 1.4;">Usuarios esperando tus códigos</div>
                    </div>
                    <button onclick='initMassMessageModal(<?php echo json_encode($all_viewer_ids); ?>, 0, <?php echo $total_potential; ?>)' 
                            style="background: #E30613; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; transition: all 0.2s ease; white-space: nowrap;">
                        <i class="fas fa-paper-plane"></i> Mensaje Masivo
                    </button>
                </div>
            </div>

            <!-- Compact Actions Bar -->
            <div style="background: #fff; padding: 16px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <div class="compact-actions-bar" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: center;">
                    
                    <!-- Mi Página Pública -->
                    <a href="<?php echo link_usuario($_SESSION["username"], $_SESSION["user_id"]); ?>" target="_blank" style="display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8f9fa; border-radius: 10px; text-decoration: none; cursor: pointer; transition: all 0.2s ease; border: 1px solid #eee;">
                        <div style="background: #EDE7F6; color: #7C4DFF; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-external-link-alt" style="font-size: 1rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #333;">Mi página pública</div>
                            <div style="font-size: 0.75rem; color: #999;">Comparte tu perfil</div>
                        </div>
                    </a>

                    <!-- Saldo -->
                    <div style="display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; border: 1px solid #eee;" onclick="document.getElementById('btn-recargar-saldo').click();">
                        <div style="background: #E8F5E9; color: #4CAF50; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-piggy-bank" style="font-size: 1rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #333;">Saldo</div>
                            <div style="font-size: 1rem; font-weight: 700; color: #4CAF50;">
                                <?php
                                $collection_usuarios = getCollectionUsuarios();
                                $usuario_actualizado = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
                                $saldo_usuario = $usuario_actualizado['saldo'] ?? 0;
                                echo number_format($saldo_usuario, 2) . '€';
                                ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 6px;" onclick="event.stopPropagation();">
                            <button style="background: #E8F5E9; color: #4CAF50; padding: 6px 10px; border: none; border-radius: 6px; font-size: 0.7rem; font-weight: 600; cursor: pointer;" id="btn-recargar-saldo">
                                <i class="fas fa-plus" style="margin-right: 2px;"></i>Añadir
                            </button>
                            <a href="/public/historial_recargas.php" style="background: #f0f0f0; color: #666; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 0.7rem; font-weight: 600;">
                                <i class="fas fa-history" style="margin-right: 2px;"></i>Historial
                            </a>
                        </div>
                    </div>

                    <!-- Publicar -->
                    <a href="/nuevo_codigo" onclick="return confirmPublish();" style="display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8f9fa; border-radius: 10px; text-decoration: none; cursor: pointer; transition: all 0.2s ease; border: 1px solid #eee;">
                        <div style="background: #FFF3E0; color: #FF9800; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-plus-circle" style="font-size: 1rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #333;">Publicar código</div>
                            <div style="font-size: 0.75rem; color: #999;">Crea una nueva oferta</div>
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
            
            <!-- Filtros de códigos -->
            <div style="background: #fff; padding: 20px 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">

                <!-- Filtro de texto -->
                <div style="margin-bottom: 20px;">
                    <div style="position: relative; max-width: 480px; margin: 0 auto;">
                        <input type="text" id="textFilter" placeholder="Buscar por marca, descripción o código..." 
                               style="color: #555; width: 100%; padding: 12px 16px 12px 44px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 0.9rem; background: #f8f9fa; transition: all 0.2s ease; box-sizing: border-box;"
                               onkeyup="filterByText(this.value)">
                        <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 0.95rem;"></i>
                        <button id="clearTextFilter" onclick="clearTextFilter()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #aaa; cursor: pointer; font-size: 1rem; display: none;" title="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Filtros por ESTADO -->
                <div style="margin-bottom: 14px;">
                    <div style="font-size: 0.75rem; color: #aaa; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Estado</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <button class="filter-button active" data-visibility="all" onclick="filterByVisibility('all')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            <i class="fas fa-list" style="margin-right: 5px;"></i>
                            Todos (<?php echo count($listado_codigos); ?>)
                        </button>

                        <button class="filter-button" data-visibility="activos" onclick="filterByVisibility('activos')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            <i class="fas fa-check-circle" style="margin-right: 5px;"></i>
                            ✅ Activos (<?php echo $num_activos; ?>)
                        </button>

                        <?php if ($num_caducados > 0): ?>
                        <button class="filter-button" data-visibility="caducados" onclick="filterByVisibility('caducados')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            <i class="fas fa-clock" style="margin-right: 5px;"></i>
                            ⏰ Caducados (<?php echo $num_caducados; ?>)
                        </button>
                        <?php endif; ?>

                        <?php if ($num_desactivados > 0): ?>
                        <button class="filter-button" data-visibility="desactivados" onclick="filterByVisibility('desactivados')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            <i class="fas fa-ban" style="margin-right: 5px;"></i>
                            🚫 Desactivados (<?php echo $num_desactivados; ?>)
                        </button>
                        <?php endif; ?>

                        <?php if ($num_inactivos > 0): ?>
                        <button class="filter-button" data-visibility="inactivos" onclick="filterByVisibility('inactivos')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            <i class="fas fa-pause-circle" style="margin-right: 5px;"></i>
                            ⏸️ Inactivos (<?php echo $num_inactivos; ?>)
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filtros por VISIBILIDAD -->
                <div style="margin-bottom: 8px;">
                    <div style="font-size: 0.75rem; color: #aaa; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Visibilidad (activos)</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <button class="filter-button" data-visibility="alta" onclick="filterByVisibility('alta')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            ⭐ Más visibles (<?php echo $num_1_codes; ?>)
                        </button>

                        <button class="filter-button" data-visibility="media" onclick="filterByVisibility('media')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            👁️ Visibles (<?php echo $num_2_codes; ?>)
                        </button>

                        <button class="filter-button" data-visibility="baja" onclick="filterByVisibility('baja')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            😴 Poco visibles (<?php echo $num_3_codes; ?>)
                        </button>

                        <button class="filter-button" data-visibility="destacados" onclick="filterByVisibility('destacados')" style="background: #f0f0f0; color: #555; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;">
                            🌟 Destacados (<?php echo count(array_filter($listado_codigos, function($c) { return isset($c['destacado']) && $c['destacado'] > 0 && (!isset($c['estado']) || (int)$c['estado'] === 0); })); ?>)
                        </button>
                    </div>
                </div>

                <?php 
                // Calcular destacados caducados
                $destacados_caducados = [];
                if (isset($listado_codigos) && is_array($listado_codigos)) {
                    foreach ($listado_codigos as $c) {
                        if (isset($c['destacado']) && (int)$c['destacado'] > 0 && isset($c['fecha_fin_destacado'])) {
                            $fin = $c['fecha_fin_destacado'];
                            if ($fin instanceof MongoDB\BSON\UTCDateTime) {
                                $fin_ts = $fin->toDateTime()->getTimestamp();
                            } elseif (is_numeric($fin)) {
                                $fin_ts = (int)$fin;
                            } else {
                                $fin_ts = strtotime((string)$fin);
                            }
                            // Si ya pasó la fecha fin y el código sigue estando "activo" (estado=0)
                            if ($fin_ts < time() && (!isset($c['estado']) || (int)$c['estado'] === 0)) {
                                $destacados_caducados[] = (string)$c['_id'];
                            }
                        }
                    }
                }
                $num_destacados_caducados = count($destacados_caducados);
                ?>

                <?php if ($num_caducados > 0): ?>
                <div id="btn-reactivar-todos-container" style="display: none; margin-top: 12px; text-align: center;">
                    <button onclick="reactivarTodosLosCodigos()" style="background: #E30613; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; transition: all 0.2s ease;">
                        <i class="fas fa-sync-alt"></i> Reactivar todos los caducados (<?php echo $num_caducados; ?>)
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($num_destacados_caducados > 0): ?>
                <div style="margin-top: 15px; margin-bottom: 20px;">
                    <button onclick="renovarDestacadosMasivo(<?php echo htmlspecialchars(json_encode($destacados_caducados)); ?>)" style="width: 100%; background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 15px 20px; border: none; border-radius: 12px; font-weight: 700; font-size: 1.05rem; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fas fa-bolt" style="margin-right: 8px; font-size: 1.2rem;"></i> 
                        Renovar <?php echo $num_destacados_caducados; ?> código(s) destacado(s) caducado(s) a la vez
                    </button>
                </div>
                <?php endif; ?>
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
                
                <?php
                // Determinar estado del código para filtros y estilos
                $estado_codigo_val = isset($codigo['estado']) ? (int)$codigo['estado'] : 0;
                $estado_class = '';
                $estado_label = '';
                if ($estado_codigo_val === -3) {
                    $estado_class = 'estado-caducado';
                    $estado_label = 'Caducado';
                } elseif ($estado_codigo_val === -2) {
                    $estado_class = 'estado-desactivado';
                    $estado_label = 'Desactivado';
                } elseif ($estado_codigo_val === -1) {
                    $estado_class = 'estado-inactivo';
                    $estado_label = 'Inactivo';
                }
                ?>
                <div class="code-card code-item mis-anuncios-card <?php echo $estado_class; ?>" data-visibilidad="<?php echo $visibilidad_class; ?>" data-categoria="<?php echo htmlspecialchars($categoria_nombre); ?>" data-fecha="<?php echo $fecha_publicacion; ?>" data-marca="<?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>" data-clicks="<?php echo $codigo['totalclicks'] ?? 0; ?>" data-beneficio="<?php echo $codigo['num_beneficio'] ?? 10; ?>" data-destacado="<?php echo $is_destacado ? 'si' : 'no'; ?>" data-estado="<?php echo $estado_codigo_val; ?>" data-codigo-id="<?php echo $codigo['_id']; ?>">

                    <!-- Badge de estado (caducado/desactivado/inactivo) -->
                    <?php if($estado_label): ?>
                        <div class="estado-badge estado-badge-<?php echo $estado_class; ?>">
                            <i class="fas fa-<?php echo $estado_codigo_val === -3 ? 'clock' : ($estado_codigo_val === -2 ? 'ban' : 'pause-circle'); ?>"></i>
                            <?php echo $estado_label; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Badge de destacado -->
                    <?php if($is_destacado && $estado_codigo_val === 0): ?>
                        <div class="featured-badge"><i class="fas fa-star"></i> Destacado</div>
                    <?php endif; ?>

                    <!-- Imagen de la marca (clickeable) - Estilo moderno -->
                    <?php if($estado_codigo_val >= 0): // Solo mostrar imagen grande si está activo ?>
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

                        <!-- Badge flotante con visitas (izquierda, abajo) -->
                        <div class="visits-badge">
                            <i class="far fa-eye"></i>
                            <span>Visitas <?php echo number_format($codigo['totalclicks'] ?? 0); ?></span>
                        </div>
                        
                        <!-- Badge de Potencial -->
                        <?php 
                        $codigo_id_str = (string)$codigo['_id'];
                        $item_potencial = $potencial_data['per_code'][$codigo_id_str] ?? null;
                        if ($item_potencial && $item_potencial['count'] > 0): ?>
                            <div class="potential-badge-card" 
                                 onclick='initMassMessageModal(<?php echo json_encode($item_potencial['viewer_ids']); ?>, <?php echo $codigo['num_beneficio'] ?? 5; ?>, <?php echo $item_potencial['potential']; ?>)' 
                                 title="¡Haz clic para enviar mensaje masivo a los interesados!"
                                 style="position: absolute; bottom: 10px; right: 10px; background: rgba(255, 255, 255, 0.95); color: #E30613; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 800; display: flex; align-items: center; gap: 6px; cursor: pointer; border: 2px solid #E30613; box-shadow: 0 4px 10px rgba(227, 6, 19, 0.15); transition: all 0.3s ease; z-index: 5;">
                                <i class="fas fa-bolt" style="font-size: 0.9rem;"></i>
                                <span>Potencial: <?php echo number_format($item_potencial['potential'], 2, ',', '.'); ?>€</span>
                                <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.7;"></i>
                            </div>
                            <style>
                                .potential-badge-card:hover { transform: scale(1.05); background: #E30613; color: white; }
                            </style>
                        <?php endif; ?>
                    </div>
                    <?php else: // Si está inactivo, mostrar solo un header simplificado ?>
                    <div style="padding: 15px 15px 5px 15px; font-weight: bold; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; color: #fff;">
                        <?php if(!empty($imagen_url)): ?>
                            <img src="<?php echo htmlspecialchars($imagen_url); ?>" style="width: 30px; height: 30px; border-radius: 5px; object-fit: cover;" alt="<?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>">
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?></span>
                    </div>
                    <?php endif; ?>


                    
                    <!-- Badge de visibilidad con posición (fuera del contenedor de imagen para evitar z-index) -->
                    <?php if($estado_codigo_val >= 0): // Solo si está activo ?>
                    <div class="top-left-badges">
                        <div class="visibility-badge visibility-<?php echo $visibilidad_class; ?>">
                            <span class="position-in-badge">Posición #<?php echo $posicion; ?></span>
                            <?php echo $visibilidad_text; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Descripción -->
                    <?php if($estado_codigo_val >= 0): // Solo si está activo ?>
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
                    <?php endif; ?>

                    <!-- Beneficio -->
                    <?php if($estado_codigo_val >= 0 && isset($codigo['num_beneficio']) && $codigo['num_beneficio'] > 0): ?>
                        <div class="code-meta-info">
                            <div class="beneficio-destacado">
                                <div class="beneficio-icono">💰</div>
                                <div class="beneficio-contenido">
                                    <div class="beneficio-cantidad"><?php echo $codigo['num_beneficio']; ?>€</div>
                                    <div class="beneficio-tipo">BENEFICIO</div>
                                </div>
                            </div>
                        </div>
                    <?php elseif($estado_codigo_val < 0 && isset($codigo['num_beneficio']) && $codigo['num_beneficio'] > 0): // Compacto para inactivos ?>
                        <div style="padding: 0 15px 10px 15px; color: #10b981; font-weight: bold;">
                            Beneficio asociado: <?php echo $codigo['num_beneficio']; ?>€
                        </div>
                    <?php endif; ?>

                    <!-- Código de descuento -->
                    <div class="code-code-display">
                        <span class="code-label">Código:</span>
                        <span class="code-value"><?php echo htmlspecialchars($codigo['codigo'] ?? 'N/A'); ?></span>
                    </div>

                    <!-- Botones de acción -->
                    <div class="code-actions">
                        <?php if($estado_codigo_val >= 0): // Solo mostrar botones Destacar/Super si está activo ?>
                            <a href="/destacar_codigo?codigo=<?php echo $codigo['_id']; ?>" class="btn-action btn-destacar">
                                <i class="fas fa-star"></i> Destacar
                            </a>
                            <?php 
                            // Check if brand has Super Landing
                            $has_super_landing = false;
                            if (!function_exists('get_active_super_landings')) {
                                require_once __DIR__ . '/../myphp/funciones.php';
                                include_once __DIR__ . '/../myphp/_super_landing_functions.php';
                            }
                            if (function_exists('get_active_super_landings')) {
                                $all_sl = get_active_super_landings(50);
                                foreach ($all_sl as $sl) {
                                    $linked_slugs = isset($sl['linked_brand_slugs']) ? 
                                        (is_array($sl['linked_brand_slugs']) ? $sl['linked_brand_slugs'] : iterator_to_array($sl['linked_brand_slugs'])) : [];
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
                            <?php endif; // has_super_landing ?>
                        <?php else: // Si está inactivo/caducado, mostrar botón Reactivar grande ?>
                            <button onclick="reactivarCodigo('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($marca['nombre'] ?? $codigo['marca']); ?>')" class="btn-action btn-reactivar">
                                <i class="fas fa-redo"></i> Reactivar
                            </button>
                        <?php endif; // active code condition ?>
                        
                        <!-- Botones de Gestión (Edit, Stats, Delete) compactos y neutros -->
                        <div style="display: flex; gap: 8px; margin-top: 4px; align-items: center;">
                            <a href="/modificar_codigo/<?php echo $codigo['_id']; ?>" class="btn-action btn-modificar-neutral" style="flex: 2;">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <button onclick="mostrarEstadisticas('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($codigo['marca'] ?? ''); ?>')" class="btn-action btn-stats-neutral" style="flex: 1;" title="Ver Estadísticas">
                                <i class="fas fa-chart-bar"></i>
                            </button>
                            <button onclick="confirmarEliminarCodigo('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($marca['nombre'] ?? $codigo['marca']); ?>')" class="btn-action btn-eliminar-neutral" style="flex: 1;" title="Borrar Código">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php if(!empty($fecha_formateada)): ?>
                                <div class="brand-date-badge">
                                    <i class="far fa-clock"></i>
                                    <span><?php echo htmlspecialchars($fecha_formateada); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
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


<!-- Modal para recargar saldo - Diseño premium CodigoAmigo -->
<div class="modal fade" id="modal_recargar_saldo" tabindex="-1" role="dialog" aria-labelledby="modalRecargarSaldoLabel">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" id="modal_saldo_content">
            <!-- Header -->
            <div id="modal_saldo_header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; background: #E30613; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-wallet" style="font-size: 1rem; color: white;"></i>
                    </div>
                    <h4 class="modal-title" id="modalRecargarSaldoLabel" style="font-size: 1.15rem; margin: 0; font-weight: 700; color: #fff;">Recargar Saldo</h4>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="background: rgba(255,255,255,0.1); border: none; color: rgba(255,255,255,0.6); width: 32px; height: 32px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; padding: 0; margin: 0; position: static; opacity: 1; line-height: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Subtítulo -->
            <div style="padding: 16px 20px 0 20px; text-align: center; background: #1c1c2e;">
                <p style="margin: 0; color: #aaa; font-size: 0.85rem; font-weight: 400;">Selecciona un paquete. Cuanto más añadas, más recibes.</p>
            </div>

            <!-- Paquetes -->
            <div class="modal-body" id="modal_saldo_body">
                <!-- Paquete 20€ -->
                <div class="package-card saldo-pkg" data-package="20" data-amount="25">
                    <div class="saldo-pkg-left">
                        <div class="saldo-pkg-price">20€</div>
                        <div class="saldo-pkg-sub">Recibes <span>25€</span></div>
                    </div>
                    <div class="saldo-pkg-right">
                        <div class="saldo-pkg-bonus">+5€</div>
                        <div class="saldo-pkg-pct">+25%</div>
                    </div>
                </div>

                <!-- Paquete 40€ - Recomendado -->
                <div class="package-card saldo-pkg saldo-pkg-best" data-package="40" data-amount="50">
                    <div class="saldo-pkg-best-badge">RECOMENDADO</div>
                    <div class="saldo-pkg-left">
                        <div class="saldo-pkg-price">40€</div>
                        <div class="saldo-pkg-sub">Recibes <span>50€</span></div>
                    </div>
                    <div class="saldo-pkg-right">
                        <div class="saldo-pkg-bonus">+10€</div>
                        <div class="saldo-pkg-pct">+25%</div>
                    </div>
                </div>

                <!-- Paquete 100€ -->
                <div class="package-card saldo-pkg" data-package="100" data-amount="150">
                    <div class="saldo-pkg-left">
                        <div class="saldo-pkg-price">100€</div>
                        <div class="saldo-pkg-sub">Recibes <span>150€</span></div>
                    </div>
                    <div class="saldo-pkg-right">
                        <div class="saldo-pkg-bonus">+50€</div>
                        <div class="saldo-pkg-pct">+50%</div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div id="modal_saldo_footer">
                <i class="fas fa-lock" style="font-size: 0.7rem; opacity: 0.5;"></i>
                <span>Pago seguro con Stripe · Sin almacenar datos</span>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Modal Recargar Saldo - Premium CodigoAmigo ===== */
#modal_saldo_content {
    border-radius: 16px !important;
    border: none !important;
    box-shadow: 0 25px 60px rgba(0,0,0,0.5) !important;
    overflow: hidden !important;
    max-width: 400px !important;
    margin: 0 auto !important;
    background: #1c1c2e !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

#modal_saldo_header {
    background: #1c1c2e !important;
    padding: 18px 20px 0 20px !important;
    border: none !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}

#modal_saldo_header .close:hover {
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}

#modal_saldo_body {
    padding: 14px 16px 10px 16px !important;
    background: #1c1c2e !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 10px !important;
}

/* === Package cards === */
.saldo-pkg {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 16px 18px !important;
    border-radius: 12px !important;
    border: 1.5px solid rgba(255,255,255,0.08) !important;
    background: rgba(255,255,255,0.04) !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    position: relative !important;
    -webkit-tap-highlight-color: transparent;
}

.saldo-pkg:hover {
    border-color: #E30613 !important;
    background: rgba(227, 6, 19, 0.06) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 15px rgba(227, 6, 19, 0.1) !important;
}

.saldo-pkg:active {
    transform: scale(0.98) !important;
}

/* Best/recommended package */
.saldo-pkg-best {
    border-color: #E30613 !important;
    background: rgba(227, 6, 19, 0.08) !important;
    box-shadow: 0 0 0 1px rgba(227, 6, 19, 0.15) !important;
}

.saldo-pkg-best-badge {
    position: absolute;
    top: -9px;
    left: 50%;
    transform: translateX(-50%);
    background: #E30613;
    color: white;
    font-size: 0.6rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 4px;
    white-space: nowrap;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Left side */
.saldo-pkg-left {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.saldo-pkg-price {
    font-size: 1.6rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
}

.saldo-pkg-sub {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.45);
    font-weight: 400;
}

.saldo-pkg-sub span {
    color: #E30613;
    font-weight: 700;
}

/* Right side */
.saldo-pkg-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 3px;
}

.saldo-pkg-bonus {
    font-size: 1rem;
    font-weight: 800;
    color: #E30613;
    line-height: 1;
}

.saldo-pkg-pct {
    font-size: 0.65rem;
    color: rgba(255,255,255,0.35);
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Footer */
#modal_saldo_footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px 20px 16px 20px;
    color: rgba(255,255,255,0.3);
    font-size: 0.7rem;
    background: #1c1c2e;
    border-radius: 0 0 16px 16px;
}

/* Selected state */
.saldo-pkg.selected {
    border-color: #E30613 !important;
    background: rgba(227, 6, 19, 0.12) !important;
    box-shadow: 0 0 0 2px rgba(227, 6, 19, 0.2) !important;
}
</style>

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

</script>
<style>
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
</style>


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