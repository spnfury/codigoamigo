<?php
/**
 * Sistema de Trust Score para Código Amigo
 * 
 * Calcula un "peso" para cada código que determina su probabilidad
 * de ser seleccionado en el sistema "Obtener código".
 */

// Incluir dependencias necesarias
if (!function_exists('createConnection')) {
    require_once __DIR__ . '/funciones.php';
}
if (!function_exists('getCollectionCodigos')) {
    require_once __DIR__ . '/funciones_codigo.php';
}
if (!function_exists('get_object_user')) {
    require_once __DIR__ . '/funciones_usuario.php';
}

/**
 * Calcula el Trust Score de un código basado en sus atributos y los del usuario.
 *
 * @param array $codigo - Documento del código de MongoDB
 * @param array|null $usuario - Documento del usuario (opcional, se carga si no se pasa)
 * @return int - Peso numérico (mínimo 1)
 */
function calculateTrustScore($codigo, $usuario = null) {
    global $url_usuario_sin_foto;
    
    $score = 1; // Base: todos empiezan con 1
    
    // Cargar usuario si no se pasó
    if (!$usuario && isset($codigo['id_usuario'])) {
        $usuario = get_object_user('_id', $codigo['id_usuario']);
        if ($usuario && !is_array($usuario)) {
            $usuario = iterator_to_array($usuario);
        }
    }
    
    // --- Factores del código ---
    
    // Destacado (pago): +10
    if (!empty($codigo['destacado']) && $codigo['destacado'] != 0) {
        $score += 10;
    }
    
    // Tiene descripción personalizada: +2
    if (!empty($codigo['descripcion']) && strlen(trim($codigo['descripcion'])) > 10) {
        $score += 2;
    }
    
    // Votos positivos: +1 cada uno
    $votos_positivos = intval($codigo['votos_positivos'] ?? 0);
    $score += $votos_positivos;
    
    // Votos negativos: -2 cada uno
    $votos_negativos = intval($codigo['votos_negativos'] ?? 0);
    $score -= ($votos_negativos * 2);
    
    // Código reportado: -5
    if (!empty($codigo['reportado']) && $codigo['reportado'] > 0) {
        $score -= 5;
    }
    
    // --- Factores del usuario ---
    if ($usuario) {
        // Tiene foto de perfil: +2 / No tiene: -1
        $tiene_foto = false;
        if (!empty($usuario['img'])) {
            $img = $usuario['img'];
            // Verificar que no sea la foto por defecto
            if ($url_usuario_sin_foto && $img != $url_usuario_sin_foto 
                && strpos($img, 'sin_foto') === false
                && strpos($img, 'default') === false) {
                $tiene_foto = true;
            }
        }
        
        if ($tiene_foto) {
            $score += 2;
        } else {
            $score -= 1;
        }
        
        // Email verificado: +1
        if (!empty($usuario['verificado']) || !empty($usuario['email_verificado']) || (!empty($usuario['estado']) && $usuario['estado'] == 1)) {
            $score += 1;
        }
        
        // Antigüedad del usuario: +1 por año
        if (!empty($usuario['fecha_registro'])) {
            $fecha_registro = $usuario['fecha_registro'];
            if (is_string($fecha_registro)) {
                $timestamp = strtotime($fecha_registro);
            } elseif ($fecha_registro instanceof MongoDB\BSON\UTCDateTime) {
                $timestamp = $fecha_registro->toDateTime()->getTimestamp();
            } else {
                $timestamp = time();
            }
            $years = floor((time() - $timestamp) / (365.25 * 24 * 3600));
            $score += max(0, min($years, 5)); // Máximo 5 puntos por antigüedad
        } elseif (!empty($usuario['_id']) && $usuario['_id'] instanceof MongoDB\BSON\ObjectId) {
            // Fallback: extract creation date from ObjectId
            $timestamp = $usuario['_id']->getTimestamp();
            $years = floor((time() - $timestamp) / (365.25 * 24 * 3600));
            $score += max(0, min($years, 5));
        }
        
        // Usuario activo (tiene códigos en múltiples marcas): +1
        if (!empty($usuario['num_marcas']) && $usuario['num_marcas'] > 1) {
            $score += 1;
        } else {
            // Consultar si tiene códigos en más de una marca
            try {
                $collection_codigos = getCollectionCodigos();
                $user_id = $usuario['_id'];
                // Try ObjectId first (most codes store id_usuario as ObjectId)
                $query_id = is_object($user_id) ? $user_id : new MongoDB\BSON\ObjectId((string)$user_id);
                $marcas_usuario = $collection_codigos->distinct('marca', [
                    'id_usuario' => $query_id,
                    'estado' => ['$in' => [0, 1]]
                ]);
                // Fallback to string if ObjectId returned nothing
                if (empty($marcas_usuario)) {
                    $marcas_usuario = $collection_codigos->distinct('marca', [
                        'id_usuario' => (string)$user_id,
                        'estado' => ['$in' => [0, 1]]
                    ]);
                }
                if (count($marcas_usuario) > 1) {
                    $score += 1;
                }
            } catch (Exception $e) {
                // Silently skip
            }
        }
        
        // Bonus para usuarios VIP
        $is_vip = !empty($usuario['is_vip']) || !empty($usuario['vip']) || !empty($usuario['suscripcion_vip']);
        if ($is_vip) {
            $score += 15;
        }
    }
    
    // Mínimo siempre 1
    return max(1, $score);
}

/**
 * Determina el nivel de confianza (estrellas) basado en el Trust Score.
 *
 * @param int $score - Trust Score calculado
 * @param bool $es_destacado - Si el código es destacado (pago)
 * @param bool $is_vip - Si el usuario es VIP
 * @return array - ['stars' => int, 'label' => string, 'class' => string]
 */
function getTrustLevel($score, $es_destacado = false, $is_vip = false) {
    // Si es destacado (pago) o el usuario es VIP, siempre 5 estrellas "Premium"
    if ($es_destacado || $is_vip) {
        return [
            'stars' => 5,
            'label' => 'Premium',
            'class' => 'trust-premium',
            'color' => '#f59e0b'
        ];
    }
    
    // Umbrales actualizados para mayor exigencia
    if ($score >= 20) {
        return [
            'stars' => 4,
            'label' => 'Recomendado',
            'class' => 'trust-recommended',
            'color' => '#10b981'
        ];
    } elseif ($score >= 12) {
        return [
            'stars' => 3,
            'label' => 'De confianza',
            'class' => 'trust-trusted',
            'color' => '#3b82f6'
        ];
    } elseif ($score >= 6) {
        return [
            'stars' => 2,
            'label' => 'Verificado',
            'class' => 'trust-verified',
            'color' => '#6b7280'
        ];
    } else {
        return [
            'stars' => 1,
            'label' => 'Nuevo',
            'class' => 'trust-new',
            'color' => '#9ca3af'
        ];
    }
}

/**
 * Genera las estrellas HTML para un nivel de confianza.
 *
 * @param int $stars - Número de estrellas (1-5)
 * @param string $color - Color de las estrellas
 * @return string - HTML de las estrellas
 */
function generateTrustStarsHTML($stars, $color = '#f59e0b') {
    $html = '<span class="trust-stars" style="color: ' . $color . ';">';
    for ($i = 0; $i < 5; $i++) {
        if ($i < $stars) {
            $html .= '<i class="fas fa-star"></i>';
        } else {
            $html .= '<i class="far fa-star" style="color: #d1d5db;"></i>';
        }
    }
    $html .= '</span>';
    return $html;
}

/**
 * Selecciona un código aleatorio ponderado de una marca.
 *
 * @param string $marca_nombre_clave - Nombre clave de la marca
 * @param string|null $exclude_id - ID de código a excluir (para "prueba otro")
 * @return array|null - Código seleccionado con info del usuario, o null si no hay códigos
 */
function selectWeightedRandomCode($marca_nombre_clave, $exclude_id = null) {
    global $url_usuario_sin_foto;
    
    $collection_codigos = getCollectionCodigos();
    
    // Obtener solo los códigos activos de la marca (case insensitive)
    $filtro = [
        'marca' => ['$regex' => '^' . preg_quote($marca_nombre_clave) . '$', '$options' => 'i'],
        'estado' => 0
    ];
    
    // Excluir un código específico (para "prueba otro")
    if ($exclude_id) {
        try {
            $filtro['_id'] = ['$ne' => new MongoDB\BSON\ObjectId($exclude_id)];
        } catch (Exception $e) {
            // ID inválido, ignorar
        }
    }
    
    $codigos_cursor = $collection_codigos->find($filtro, [
        'sort' => ['destacado' => -1, 'fecha_publicacion' => -1],
        'limit' => 1000 // Aumentado para contar toda la competencia real
    ]);
    
    // Clasificar códigos en 3 tiers
    $codigos_premium = [];  // Destacado o VIP (5★)
    $codigos_quality = [];  // ≥3 estrellas (De confianza, Recomendado)
    $codigos_normales = []; // 1-2 estrellas
    
    foreach ($codigos_cursor as $codigo) {
        $codigo_arr = is_array($codigo) ? $codigo : iterator_to_array($codigo);
        
        // Cargar usuario
        $usuario = null;
        if (isset($codigo_arr['id_usuario'])) {
            $usuario = get_object_user('_id', $codigo_arr['id_usuario']);
            if ($usuario && !is_array($usuario)) {
                $usuario = iterator_to_array($usuario);
            }
        }
        
        $score = calculateTrustScore($codigo_arr, $usuario);
        
        $entry = [
            'codigo' => $codigo_arr,
            'usuario' => $usuario,
            'score' => $score
        ];
        
        // Determinar tier
        $es_destacado = !empty($codigo_arr['destacado']) && $codigo_arr['destacado'] != 0;
        $es_vip = false;
        if ($usuario) {
            $es_vip = !empty($usuario['is_vip']) || !empty($usuario['vip']) || !empty($usuario['suscripcion_vip']);
        }
        
        if ($es_destacado || $es_vip) {
            $codigos_premium[] = $entry;
        } else {
            $trust = getTrustLevel($score);
            if ($trust['stars'] >= 3) {
                $codigos_quality[] = $entry;
            } else {
                $codigos_normales[] = $entry;
            }
        }
    }
    
    // Total de todos los códigos para info
    $total_all = count($codigos_premium) + count($codigos_quality) + count($codigos_normales);
    
    if ($total_all === 0) {
        return null;
    }
    
    // ══════════════════════════════════════════════════════════════
    // SELECCIÓN DINÁMICA BASADA EN COMPETENCIA
    // ══════════════════════════════════════════════════════════════
    // Alta competencia (≥16 códigos): SOLO Premium (VIP/Destacado)
    // Media competencia (6-15 códigos): Premium + Quality (≥3★)
    // Baja competencia (≤5 códigos):   Todos los códigos
    // ══════════════════════════════════════════════════════════════
    
    if ($total_all >= 16) {
        // Alta competencia: solo Premium
        // Fallback → Quality → Todos
        if (!empty($codigos_premium)) {
            $pool = $codigos_premium;
        } elseif (!empty($codigos_quality)) {
            $pool = $codigos_quality;
        } else {
            $pool = $codigos_normales;
        }
    } elseif ($total_all >= 6) {
        // Media competencia: Premium + Quality (≥3★)
        $pool = array_merge($codigos_premium, $codigos_quality);
        // Fallback si no hay ni premium ni quality
        if (empty($pool)) {
            $pool = $codigos_normales;
        }
    } else {
        // Baja competencia: todos los códigos, priorización cuadrática hará el resto
        $pool = array_merge($codigos_premium, $codigos_quality, $codigos_normales);
    }
    
    // Selección aleatoria ponderada (cuadrática) dentro del pool elegido
    $pesos = [];
    $total_peso = 0;
    foreach ($pool as $entry) {
        $peso = $entry['score'] * $entry['score'];
        $pesos[] = $peso;
        $total_peso += $peso;
    }
    
    $random = mt_rand(1, max(1, $total_peso));
    $acumulado = 0;
    $seleccionado = null;
    
    for ($i = 0; $i < count($pool); $i++) {
        $acumulado += $pesos[$i];
        if ($random <= $acumulado) {
            $seleccionado = $pool[$i];
            break;
        }
    }
    
    // Fallback: seleccionar el primero del pool si algo falla
    if (!$seleccionado) {
        $seleccionado = $pool[0];
    }
    
    $codigo_sel = $seleccionado['codigo'];
    $usuario_sel = $seleccionado['usuario'];
    $score_sel = $seleccionado['score'];
    
    // Incrementar totalclicks
    try {
        $collection_codigos->updateOne(
            ['_id' => $codigo_sel['_id']],
            ['$inc' => ['totalclicks' => 1]]
        );
    } catch (Exception $e) {
        // Silently skip
    }
    
    // Preparar datos del usuario
    $usuario_nombre = 'Usuario anónimo';
    
    if ($usuario_sel) {
        $usuario_nombre = $usuario_sel['username'] ?? $usuario_sel['nombre'] ?? 'Usuario';
    }
    
    $usuario_img = function_exists('get_user_avatar_url') 
        ? get_user_avatar_url($usuario_sel, $usuario_nombre, 80) 
        : ($url_usuario_sin_foto ?? '/img/user-default.png');
    
    // Determinar si el usuario es VIP
    $is_vip = false;
    if ($usuario_sel) {
        $is_vip = !empty($usuario_sel['is_vip']) || !empty($usuario_sel['vip']) || !empty($usuario_sel['suscripcion_vip']);
    }

    // Calcular trust level
    $es_destacado = !empty($codigo_sel['destacado']) && $codigo_sel['destacado'] != 0;
    $trust = getTrustLevel($score_sel, $es_destacado, $is_vip);
    
    // Beneficio display
    $beneficio_texto = '';
    if (!empty($codigo_sel['num_beneficio']) && $codigo_sel['num_beneficio'] > 0) {
        $tipo = $codigo_sel['tipo_descuento'] ?? 'euros';
        if ($tipo === '% de descuento') {
            $beneficio_texto = $codigo_sel['num_beneficio'] . '% de descuento';
        } elseif ($tipo === 'minutos gratis') {
            $beneficio_texto = $codigo_sel['num_beneficio'] . ' minutos gratis';
        } else {
            $beneficio_texto = $codigo_sel['num_beneficio'] . '€';
        }
    }
    
    return [
        'success' => true,
        'codigo_id' => (string)$codigo_sel['_id'],
        'codigo' => $codigo_sel['codigo'] ?? '',
        'descripcion' => $codigo_sel['descripcion'] ?? '',
        'beneficio_texto' => $beneficio_texto,
        'es_destacado' => $es_destacado,
        'is_vip' => $is_vip,
        'usuario_nombre' => $usuario_nombre,
        'usuario_img' => $usuario_img,
        'usuario_id' => isset($usuario_sel['_id']) ? (string)$usuario_sel['_id'] : '',
        'trust_score' => $score_sel,
        'trust_stars' => $trust['stars'],
        'trust_label' => $trust['label'],
        'trust_class' => $trust['class'],
        'trust_color' => $trust['color'],
        'votos_positivos' => intval($codigo_sel['votos_positivos'] ?? 0),
        'votos_negativos' => intval($codigo_sel['votos_negativos'] ?? 0),
        'total_codigos' => $total_all + ($exclude_id ? 1 : 0)
    ];
}

/**
 * Obtiene todos los códigos de una marca con sus trust scores, ordenados por score.
 *
 * @param string $marca_nombre_clave - Nombre clave de la marca
 * @return array - Lista de códigos con scores
 */
function getBrandCodesWithScores($marca_nombre_clave) {
    global $url_usuario_sin_foto;
    
    $collection_codigos = getCollectionCodigos();
    
    $filtro = [
        'marca' => ['$regex' => '^' . preg_quote($marca_nombre_clave) . '$', '$options' => 'i'],
        'estado' => 0
    ];
    
    $codigos_cursor = $collection_codigos->find($filtro, [
        'sort' => ['destacado' => -1, 'fecha_publicacion' => -1],
        'limit' => 1000
    ]);
    
    $resultado = [];
    
    foreach ($codigos_cursor as $codigo) {
        $codigo_arr = is_array($codigo) ? $codigo : iterator_to_array($codigo);
        
        // Cargar usuario
        $usuario = null;
        if (isset($codigo_arr['id_usuario'])) {
            $usuario = get_object_user('_id', $codigo_arr['id_usuario']);
            if ($usuario && !is_array($usuario)) {
                $usuario = iterator_to_array($usuario);
            }
        }
        
        $score = calculateTrustScore($codigo_arr, $usuario);
        $es_destacado = !empty($codigo_arr['destacado']) && $codigo_arr['destacado'] != 0;
        
        // Determinar si el usuario es VIP
        $is_vip_user = false;
        if ($usuario) {
            $is_vip_user = !empty($usuario['is_vip']) || !empty($usuario['vip']) || !empty($usuario['suscripcion_vip']);
        }
        
        $trust = getTrustLevel($score, $es_destacado, $is_vip_user);
        
        // Preparar datos del usuario
        $usuario_nombre = 'Usuario anónimo';
        
        if ($usuario) {
            $usuario_nombre = $usuario['username'] ?? $usuario['nombre'] ?? 'Usuario';
        }
        
        $usuario_img = function_exists('get_user_avatar_url') 
            ? get_user_avatar_url($usuario, $usuario_nombre, 80) 
            : ($url_usuario_sin_foto ?? '/img/user-default.png');
        
        // Beneficio display
        $beneficio_texto = '';
        if (!empty($codigo_arr['num_beneficio']) && $codigo_arr['num_beneficio'] > 0) {
            $tipo = $codigo_arr['tipo_descuento'] ?? 'euros';
            if ($tipo === '% de descuento') {
                $beneficio_texto = $codigo_arr['num_beneficio'] . '% de descuento';
            } elseif ($tipo === 'minutos gratis') {
                $beneficio_texto = $codigo_arr['num_beneficio'] . ' minutos gratis';
            } else {
                $beneficio_texto = $codigo_arr['num_beneficio'] . '€';
            }
        }
        
        $resultado[] = [
            'codigo_id' => (string)$codigo_arr['_id'],
            'codigo' => $codigo_arr['codigo'] ?? '',
            'marca' => $codigo_arr['marca'] ?? $marca_nombre_clave,
            'descripcion' => $codigo_arr['descripcion'] ?? '',
            'beneficio_texto' => $beneficio_texto,
            'es_destacado' => $es_destacado,
            'is_vip' => $is_vip_user,
            'usuario_id' => isset($codigo_arr['id_usuario']) ? (string)$codigo_arr['id_usuario'] : '',
            'usuario_nombre' => $usuario_nombre,
            'usuario_img' => $usuario_img,
            'trust_score' => $score,
            'trust_stars' => $trust['stars'],
            'trust_label' => $trust['label'],
            'trust_class' => $trust['class'],
            'trust_color' => $trust['color'],
            'votos_positivos' => intval($codigo_arr['votos_positivos'] ?? 0),
            'fecha' => $codigo_arr['fecha_publicacion'] ?? '',
            'fecha_fin_destacado' => $codigo_arr['fecha_fin_destacado'] ?? null,
            'auto_renovar_destacado' => !empty($codigo_arr['auto_renovar_destacado'])
        ];
    }
    
    // Ordenar por score descendente
    usort($resultado, function($a, $b) {
        return $b['trust_score'] - $a['trust_score'];
    });
    
    return $resultado;
}

?>
