<?php
/**
 * Partial: una tarjeta de "Mis anuncios".
 * Requiere en scope: $codigo (array), $potencial_data (array).
 * Helpers usados: getObjectMarca, get_posicion_codigo_en_marca, getObjectCategoria,
 * get_active_super_landings (opcional).
 */
if (!isset($codigo) || !isset($codigo['marca']) || $codigo['marca'] === null) return;
$marca = getObjectMarca('nombre_clave', $codigo['marca']);
if (!$marca) return;
if (!function_exists('link_ficha_codigo')) {
    @require_once __DIR__ . '/../myphp/links.php';
}
?>
                <?php
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
                <?php
                // Super disponible para todos los códigos activos.
                // Sólo necesitamos saber si YA es super para marcar el botón.
                $is_super = false;
                if ($estado_codigo_val >= 0) {
                    $is_super = isset($codigo['tipo_destacado']) && $codigo['tipo_destacado'] === 'super';
                }

                $codigo_id_str = (string)$codigo['_id'];
                $item_potencial = $potencial_data['per_code'][$codigo_id_str] ?? null;
                $descripcion_corta = mb_strlen($descripcion) > 100 ? mb_substr($descripcion, 0, 100) . '…' : $descripcion;

                ?>

                <?php
                // Limpiezas extra
                $fecha_valida = !empty($fecha_formateada);
                if ($fecha_valida && isset($timestamp) && $timestamp < strtotime('2010-01-01')) {
                    $fecha_valida = false; // 01/01/1970 y similares — datos basura
                }
                $pos_label = $posicion >= 100 ? 'Pos lejana' : 'Pos #' . $posicion;
                $marca_nombre_safe = htmlspecialchars($marca['nombre'] ?? $codigo['marca']);
                $marca_inicial = strtoupper(mb_substr($marca['nombre'] ?? $codigo['marca'], 0, 1));
                ?>

                <article class="ma-row code-item <?php echo $estado_class; ?>"
                    data-visibilidad="<?php echo $visibilidad_class; ?>"
                    data-categoria="<?php echo htmlspecialchars($categoria_nombre); ?>"
                    data-fecha="<?php echo $fecha_publicacion; ?>"
                    data-marca="<?php echo $marca_nombre_safe; ?>"
                    data-clicks="<?php echo $codigo['totalclicks'] ?? 0; ?>"
                    data-beneficio="<?php echo $codigo['num_beneficio'] ?? 10; ?>"
                    data-destacado="<?php echo $is_destacado ? 'si' : 'no'; ?>"
                    data-estado="<?php echo $estado_codigo_val; ?>"
                    data-codigo-id="<?php echo $codigo['_id']; ?>"
                    style="<?php echo $estado_codigo_val < 0 ? 'opacity:0.7;' : ''; ?>">

                    <!-- Header: logo + nombre + badges + kebab -->
                    <header class="ma-row-head">
                        <a href="/de-<?php echo $codigo['marca']; ?>?codigo=<?php echo $codigo['_id']; ?>" class="ma-row-logo">
                            <?php if(!empty($imagen_url)): ?>
                                <img loading="lazy" src="<?php echo htmlspecialchars($imagen_url); ?>" alt="<?php echo $marca_nombre_safe; ?>">
                            <?php else: ?>
                                <span class="ma-row-logo-fallback"><?php echo $marca_inicial; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="ma-row-title">
                            <a href="/de-<?php echo $codigo['marca']; ?>" class="ma-row-name"><?php echo $marca_nombre_safe; ?></a>
                            <div class="ma-row-badges">
                                <?php if ($estado_codigo_val === 0): ?>
                                    <span class="ma-pill ma-pill-pos"><?php echo $pos_label; ?></span>
                                <?php endif; ?>
                                <?php if($estado_label): ?>
                                    <span class="ma-pill ma-pill-estado">
                                        <i class="fas fa-<?php echo $estado_codigo_val === -3 ? 'clock' : ($estado_codigo_val === -2 ? 'ban' : 'pause-circle'); ?>"></i>
                                        <?php echo $estado_label; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($fecha_valida): ?>
                                    <span class="ma-pill-date"><?php echo htmlspecialchars($fecha_formateada); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Kebab menu -->
                        <div class="ma-kebab" onclick="event.stopPropagation();">
                            <button type="button" class="ma-kebab-btn" onclick="maToggleKebab(this)" aria-label="Más acciones">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="ma-kebab-menu">
                                <a href="/modificar_codigo/<?php echo $codigo['_id']; ?>"><i class="fas fa-edit"></i> Editar</a>
                                <button type="button" onclick="mostrarEstadisticas('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($codigo['marca'] ?? ''); ?>')"><i class="fas fa-chart-bar"></i> Estadísticas</button>
                                <?php if ($estado_codigo_val < 0): ?>
                                    <button type="button" onclick="reactivarCodigo('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($marca['nombre'] ?? $codigo['marca']); ?>')"><i class="fas fa-redo"></i> Reactivar</button>
                                <?php endif; ?>
                                <button type="button" class="danger" onclick="confirmarEliminarCodigo('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($marca['nombre'] ?? $codigo['marca']); ?>')"><i class="fas fa-trash"></i> Borrar</button>
                            </div>
                        </div>
                    </header>

                    <!-- Descripción -->
                    <?php if (!empty(trim($descripcion_corta))): ?>
                        <p class="ma-row-desc">
                            <?php echo htmlspecialchars($descripcion_corta); ?>
                            <?php if(mb_strlen($descripcion) > 100): ?>
                                <a href="javascript:;" onclick="toggleDescripcion('<?php echo $codigo['_id']; ?>')">ver más</a>
                                <span id="desc-full-<?php echo $codigo['_id']; ?>" style="display:none;"><?php echo htmlspecialchars($descripcion); ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <!-- Stats chips -->
                    <div class="ma-row-stats">
                        <?php if (isset($codigo['num_beneficio']) && $codigo['num_beneficio'] > 0): ?>
                            <span class="ma-chip ma-chip-benef">💰 <?php echo $codigo['num_beneficio']; ?>€</span>
                        <?php endif; ?>
                        <span class="ma-chip" title="Visualizaciones"><i class="far fa-eye"></i> <?php echo number_format($codigo['totalclicks'] ?? 0); ?></span>
                        <?php if ($item_potencial && $item_potencial['count'] > 0): ?>
                            <a href="javascript:;"
                               onclick='initMassMessageModal(<?php echo json_encode($item_potencial['viewer_ids']); ?>, <?php echo $codigo['num_beneficio'] ?? 5; ?>, <?php echo $item_potencial['potential']; ?>)'
                               class="ma-chip ma-chip-potencial" title="Mensaje masivo a interesados">
                                <i class="fas fa-bolt"></i> <?php echo number_format($item_potencial['potential'], 2, ',', '.'); ?>€
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Acción primaria -->
                    <?php
                    $ficha_url = function_exists('link_ficha_codigo')
                        ? link_ficha_codigo($codigo['marca'], $codigo['_id'])
                        : '/de-' . $codigo['marca'] . '?codigo=' . $codigo['_id'];
                    // Niveles de destacado (mutuamente excluyentes)
                    $nivel_super  = $is_super;                         // tipo_destacado === 'super'
                    $nivel_normal = $is_destacado && !$is_super;       // destacado pero no super
                    ?>
                    <div class="ma-row-cta">
                        <a href="<?php echo htmlspecialchars($ficha_url); ?>" class="ma-btn ma-btn-detalle">
                            <i class="far fa-eye"></i> Ver detalle
                        </a>
                        <?php if ($estado_codigo_val < 0): ?>
                            <button type="button" onclick="reactivarCodigo('<?php echo $codigo['_id']; ?>', '<?php echo addslashes($marca['nombre'] ?? $codigo['marca']); ?>')" class="ma-btn ma-btn-reactivar">
                                <i class="fas fa-redo"></i> Reactivar código
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($estado_codigo_val >= 0): ?>
                        <!-- Nivel de destacado: Normal | Super (Super = versión avanzada) -->
                        <div class="ma-dest-label">Visibilidad</div>
                        <div class="ma-dest-control" role="group" aria-label="Nivel de destacado">
                            <a href="/destacar_codigo?codigo=<?php echo $codigo['_id']; ?>"
                               class="ma-dest-seg ma-dest-normal <?php echo $nivel_normal ? 'is-active' : ''; ?>">
                                <i class="fas fa-star"></i> Destacar<?php if ($nivel_normal): ?> <i class="fas fa-check"></i><?php endif; ?>
                            </a>
                            <a href="/destacar_super.php?codigo_id=<?php echo $codigo['_id']; ?>"
                               class="ma-dest-seg ma-dest-super <?php echo $nivel_super ? 'is-active' : ''; ?>">
                                <i class="fas fa-trophy"></i> Super<?php if ($nivel_super): ?> <i class="fas fa-check"></i><?php endif; ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Auto-renovación (todos los códigos activos) -->
                    <?php if ($estado_codigo_val === 0):
                        // Laxo: el campo se guarda a veces como true y a veces como 1.
                        // Con `=== true` un 1 pintaba el interruptor apagado aunque
                        // la renovación estuviera activa.
                        $auto_renovar_on = !empty($codigo['auto_renovar_destacado']);
                    ?>
                        <div class="ma-row-autoren">
                            <span class="ma-autoren-label"><i class="fas fa-sync-alt"></i> Auto-renovación</span>
                            <button type="button" role="switch"
                                aria-checked="<?php echo $auto_renovar_on ? 'true' : 'false'; ?>"
                                class="ma-autoren-switch <?php echo $auto_renovar_on ? 'is-on' : ''; ?>"
                                data-codigo-id="<?php echo $codigo['_id']; ?>"
                                onclick="toggleAutoRenovar(this)"
                                title="<?php echo $auto_renovar_on
                                    ? 'Auto-renovación activada: se renovará desde tu saldo al expirar el destacado'
                                    : 'Activa la auto-renovación para renovar el destacado automáticamente desde tu saldo'; ?>"></button>
                        </div>
                    <?php endif; ?>
                </article>
