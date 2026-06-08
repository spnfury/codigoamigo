<?php
/**
 * Página de renovación de destacado con 50% de descuento.
 * Accesible desde los emails de expiración.
 * 
 * Parámetros: ?codigo=ID_DEL_CODIGO
 * 
 * Valida que el código fue previamente destacado y ofrece renovación
 * al 50% del precio normal, pagando con saldo del usuario.
 */

$anula_adsense = true;

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/includes.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_utilidades.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_modern.php';

// Obtener la información del código
$codigo_id_str = $_REQUEST["codigo"] ?? '';

if (empty($codigo_id_str)) {
    header("Location: https://www.codigoamigo.com/");
    exit;
}

$codigo_id = new \MongoDB\BSON\ObjectId($codigo_id_str);
$codigo = getCodeByID($codigo_id);

if (!$codigo) {
    header("Location: https://www.codigoamigo.com/");
    exit;
}

$marca = getObjectMarca('nombre_clave', $codigo["marca"]);
$marca_nombre = htmlspecialchars($codigo['marca']);

// Verificar que el usuario esté logueado
$user_logged = isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
$is_owner = $user_logged && (string)$codigo['id_usuario'] === $_SESSION["user_id"];

// Verificar que el código fue previamente destacado (para justificar el descuento)
$fue_destacado = (isset($codigo['destacado']) && (int)$codigo['destacado'] > 0) 
    || (isset($codigo['destacado_social']) && (int)$codigo['destacado_social'] > 0)
    || (isset($codigo['tipo_destacado']) && !empty($codigo['tipo_destacado']));

// Precios con 50% de descuento
$precio_normal = DESTACADO_PRECIO_NORMAL;
$precio_super = DESTACADO_PRECIO_SUPER;
$descuento = 0.50; // 50%
$precio_normal_desc = round($precio_normal * $descuento, 2);
$precio_super_desc = round($precio_super * $descuento, 2);

// Obtener saldo real y actualizado del usuario desde MongoDB
$saldo_usuario = 0;
if ($user_logged) {
    try {
        $collection_usuarios_saldo = getCollectionUsuarios();
        $db_user = $collection_usuarios_saldo->findOne(['_id' => new \MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
        if ($db_user && isset($db_user['saldo'])) {
            $saldo_usuario = floatval($db_user['saldo']);
        }
    } catch (\Exception $e) {
        $saldo_usuario = 0;
    }
}

// Estado actual de auto-renovación y vigencia del destacado
$auto_renovar_actual = isset($codigo['auto_renovar_destacado']) && $codigo['auto_renovar_destacado'] === true;
$ts_fin_destacado = 0;
if (isset($codigo['fecha_fin_destacado']) && $codigo['fecha_fin_destacado'] instanceof MongoDB\BSON\UTCDateTime) {
    $ts_fin_destacado = $codigo['fecha_fin_destacado']->toDateTime()->getTimestamp();
}
$destacado_vigente = $ts_fin_destacado > time();
$fecha_fin_fmt = $ts_fin_destacado > 0 ? date('d/m/Y', $ts_fin_destacado) : '';

// Procesar la renovación si se envía el formulario
$mensaje_exito = '';
$mensaje_error = '';
$mensaje_gestion = '';

// Gestión pura de auto-renovación (sin cobro): activar/desactivar el flag desde la propia página
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'gestionar_auto_renovar' && $is_owner) {
    $nuevo_estado = isset($_POST['auto_renovar_destacado']) && $_POST['auto_renovar_destacado'] == '1';
    try {
        $collection_codigos = getCollectionCodigos();
        $collection_codigos->updateOne(
            ['_id' => $codigo_id],
            ['$set' => ['auto_renovar_destacado' => $nuevo_estado]]
        );
        $auto_renovar_actual = $nuevo_estado;
        $codigo['auto_renovar_destacado'] = $nuevo_estado;
        $mensaje_gestion = $nuevo_estado
            ? "✅ Auto-renovación activada. Tu destacado se renovará solo al expirar (50% dto desde tu saldo)."
            : "Auto-renovación desactivada. Tu destacado no se renovará automáticamente.";
    } catch (Exception $e) {
        log_error("Error al gestionar auto-renovación destacado: " . $e->getMessage());
        $mensaje_error = "No se pudo actualizar la auto-renovación. Inténtalo de nuevo.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') !== 'gestionar_auto_renovar' && $is_owner && $fue_destacado) {
    $tipo_renovar = $_POST['tipo_destacado'] ?? 'normal';
    $tipo_renovar = in_array($tipo_renovar, ['normal', 'super']) ? $tipo_renovar : 'normal';
    $precio_a_cobrar = ($tipo_renovar === 'super') ? $precio_super_desc : $precio_normal_desc;
    
    if ($saldo_usuario < $precio_a_cobrar) {
        $mensaje_error = "No tienes suficiente saldo. Necesitas " . number_format($precio_a_cobrar, 2, ',', '.') . "€ y tienes " . number_format($saldo_usuario, 2, ',', '.') . "€.";
    } else {
        try {
            $collection_codigos = getCollectionCodigos();
            $collection_usuarios = getCollectionUsuarios();
            $collection_transacciones = getCollectionTransacciones();
            
            $duracion_dias = ($tipo_renovar === 'super') ? DESTACADO_DURACION_SUPER : DESTACADO_DURACION_NORMAL;
            
            $update_data = [
                'destacado' => time(),
                'fecha_destacado' => date('Y-m-d H:i:s'),
                'tipo_destacado' => $tipo_renovar,
                'prioridad_pago' => time(),
                'fecha_fin_destacado' => new MongoDB\BSON\UTCDateTime((time() + ($duracion_dias * 86400)) * 1000),
                'aviso_expiracion_enviado' => false,
                'aviso_expirado_enviado' => false
            ];
            
            if ($tipo_renovar === 'super') {
                $update_data['destacado_social'] = time();
            }

            // Manejo de Auto-Renovar permanente
            $auto_renovar = isset($_POST['auto_renovar_destacado']) && $_POST['auto_renovar_destacado'] == '1';
            $update_data['auto_renovar_destacado'] = $auto_renovar;
            
            $collection_codigos->updateOne(
                ['_id' => $codigo_id],
                ['$set' => $update_data]
            );
            
            // Cobrar saldo
            $nuevo_saldo = round($saldo_usuario - $precio_a_cobrar, 2);
            $collection_usuarios->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])],
                ['$set' => ['saldo' => $nuevo_saldo]]
            );
            
            // Registrar transacción
            $collection_transacciones->insertOne([
                'usuario_id' => $_SESSION["user_id"],
                'tipo' => 'renovacion_destacado_descuento',
                'cantidad' => -$precio_a_cobrar,
                'codigo_id' => $codigo_id_str,
                'descripcion' => 'Renovación ' . ($tipo_renovar === 'super' ? 'Super' : 'Normal') . ' con 50% dto en ' . $marca_nombre,
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'estado' => 'completada',
                'saldo_anterior' => $saldo_usuario,
                'saldo_nuevo' => $nuevo_saldo,
                'marca' => $marca_nombre,
                'descuento_aplicado' => '50%',
                'precio_original' => ($tipo_renovar === 'super') ? $precio_super : $precio_normal,
                'precio_cobrado' => $precio_a_cobrar
            ]);
            
            $fecha_fin = date('d/m/Y', time() + ($duracion_dias * 86400));
            $mensaje_exito = "¡Código renovado con éxito! Tu destacado estará activo hasta el $fecha_fin. Nuevo saldo: " . number_format($nuevo_saldo, 2, ',', '.') . "€";
            $saldo_usuario = $nuevo_saldo;

            // Reflejar el nuevo estado en la página (banner + checkboxes)
            $auto_renovar_actual = $auto_renovar;
            $destacado_vigente = true;
            $fecha_fin_fmt = $fecha_fin;

        } catch (Exception $e) {
            log_error("Error en renovación con descuento: " . $e->getMessage());
            $mensaje_error = "Error al procesar la renovación. Inténtalo de nuevo.";
        }
    }
}

$title_social = $title_social ?? $title;
$description_social = $description_social ?? $description;
$imagen_social = $imagen_social ?? 'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png';

$GLOBALS['header_modern_used'] = true; // Forzar el uso del footer moderno
get_header_new($title, $description, $title_social, $description_social, $imagen_social);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* Modern typography and spacing — idéntico al sistema de destaca.php */
:root {
    --primary-color: #E30613;
    --primary-dark: #C40510;
    --text-dark: #2C3E50;
    --text-light: #7F8C8D;
    --spacing: 2rem;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    line-height: 1.6;
    color: var(--text-dark);
    background: #F8F9FA;
}

.header-preview {
    background: linear-gradient(135deg, #1f1f1f 0%, #0d0d0d 100%);
    color: white;
    padding: 4rem 0;
    margin-bottom: 3rem;
    text-align: center;
}

.header-preview h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.header-preview .marca {
    color: #E30613;
    font-weight: 700;
}

.header-preview p {
    font-size: 1.8rem;
    opacity: 0.9;
    margin-top: 1.5rem;
}

.marca-imagen {
    height: 30px;
    width: auto;
    vertical-align: middle;
    margin-right: 8px;
}

.container-pricing {
    max-width: 1200px;
    margin: 0 auto 4rem;
    padding: 0 2rem;
}

.pricing-header {
    text-align: center;
    margin-bottom: var(--spacing);
}

.pricing-title {
    font-size: 3rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
}

.pricing-subtitle {
    font-size: 1.6rem;
    margin-bottom: 1rem;
}

.descuento-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ff6b35, #E30613);
    color: white;
    padding: 0.6rem 2rem;
    border-radius: 30px;
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
    animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.pricing-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--spacing);
    margin-top: var(--spacing);
}

.pricing-card {
    background: white;
    border-radius: 16px;
    padding: var(--spacing);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    transition: transform 0.2s;
    display: flex;
    flex-direction: column;
}

.pricing-card:hover {
    transform: translateY(-5px);
}

.pricing-card .plan-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.pricing-card .plan-price-wrapper {
    margin-bottom: 1.5rem;
}

.pricing-card .precio-original {
    font-size: 1.2rem;
    color: #999;
    text-decoration: line-through;
    margin-bottom: 4px;
}

.pricing-card .plan-price {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.3rem;
}

.pricing-card .plan-duracion {
    font-size: 1rem;
    color: var(--text-light);
}

.plan-features {
    margin: 1.5rem 0;
    font-size: 1.1rem;
    flex-grow: 1;
}

.plan-feature {
    margin: 0.8rem 0;
    display: flex;
    align-items: center;
}

.plan-feature:before {
    content: "✓";
    color: var(--primary-color);
    margin-right: 0.5rem;
    font-weight: 700;
}

.toggle-autorenovar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: #f0f4ff;
    border-radius: 8px;
    font-size: 0.95rem;
    color: #444;
    cursor: pointer;
    margin-bottom: 1rem;
    border: 1px solid #d5dff0;
    transition: all 0.2s;
}

.toggle-autorenovar:hover {
    background: #e5ecf9;
    border-color: #a8bfea;
}

.toggle-autorenovar input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #2a5298;
    flex-shrink: 0;
}

.pricing-button {
    display: inline-block;
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
    text-align: center;
    color: white;
    background: var(--primary-color);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.pricing-button:hover:not([disabled]) {
    background: var(--primary-dark);
}

/* Tarjeta Super/Premium — idéntico al ribbon de destaca.php */
.pricing-card.destacado-oro {
    background: #FFFBEB;
    border: 1px solid #FFD700;
    position: relative;
    overflow: hidden;
    padding: 2rem;
}

.pricing-card.destacado-oro::before {
    content: "Premium";
    position: absolute;
    top: -5px;
    right: -35px;
    background: #FFD700;
    color: #000000;
    padding: 20px 40px 5px 40px;
    transform: rotate(45deg);
    font-size: 0.9rem;
    font-weight: bold;
}

.pricing-card.destacado-oro .plan-name {
    color: #000000;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.pricing-card.destacado-oro .plan-price {
    color: #000000;
    font-size: 3rem;
    margin-bottom: 0.3rem;
    font-weight: 700;
}

.pricing-card.destacado-oro .plan-features {
    margin: 2rem 0;
}

.pricing-card.destacado-oro .plan-feature {
    color: #000000;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    font-size: 1rem;
}

.pricing-card.destacado-oro .plan-feature:before {
    content: "✓";
    color: #000000;
    margin-right: 0.5rem;
}

.pricing-card.destacado-oro .pricing-button {
    background: #FFD700;
    color: #000000;
    padding: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
    width: 100%;
    border: none;
    border-radius: 8px;
    margin-top: 0;
}

.pricing-card.destacado-oro .pricing-button:hover:not([disabled]) {
    background: #FFC700;
    cursor: pointer;
}

.pricing-card.destacado-oro .toggle-autorenovar {
    background: rgba(255, 215, 0, 0.15);
    border-color: #e6c200;
}

.pricing-card.destacado-oro .toggle-autorenovar:hover {
    background: rgba(255, 215, 0, 0.25);
    border-color: #FFD700;
}

.pricing-card.destacado-oro .toggle-autorenovar input[type="checkbox"] {
    accent-color: #DAA520;
}

.banner-autorenovar {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    max-width: 900px;
    margin: 0 auto 2rem;
    padding: 1.5rem 1.8rem;
    background: #eaf6ee;
    border: 1px solid #b7e0c4;
    border-radius: 14px;
    flex-wrap: wrap;
}

.banner-autorenovar--off {
    background: #fff4e5;
    border-color: #ffd9a8;
}

.banner-autorenovar-icon {
    font-size: 2.4rem;
    flex-shrink: 0;
}

.banner-autorenovar-texto {
    flex: 1;
    min-width: 240px;
}

.banner-autorenovar-texto strong {
    font-size: 1.2rem;
    color: var(--text-dark);
}

.banner-autorenovar-texto p {
    margin: 0.4rem 0 0;
    font-size: 1rem;
    color: #555;
    line-height: 1.5;
}

.banner-autorenovar-form {
    flex-shrink: 0;
}

.banner-autorenovar-btn {
    padding: 0.8rem 1.4rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #b02a37;
    background: #fff;
    border: 1px solid #e2b6bb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.banner-autorenovar-btn:hover {
    background: #fbeaec;
    border-color: #d99aa1;
}

.banner-autorenovar-btn--on {
    color: #fff;
    background: #1f8a4c;
    border-color: #1f8a4c;
}

.banner-autorenovar-btn--on:hover {
    background: #176b3b;
    border-color: #176b3b;
}

.security-badge {
    text-align: center;
    margin-top: var(--spacing);
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.security-badge p {
    font-size: 1.1rem;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.saldo-badge {
    text-align: center;
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.saldo-badge .saldo-amount {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
}

.payment-icons {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    align-items: center;
}

.payment-icons i {
    font-size: 2rem;
    color: #666;
}

.secure-icon {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

/* Mensajes de estado */
.msg-exito {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    text-align: center;
    font-weight: 500;
    font-size: 1.1rem;
}

.msg-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    text-align: center;
    font-weight: 500;
    font-size: 1.1rem;
}

.login-aviso {
    text-align: center;
    padding: 3rem;
    color: #666;
    font-size: 1.2rem;
}

.login-aviso a {
    display: inline-block;
    margin-top: 1.5rem;
    padding: 1rem 2.5rem;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: background 0.2s;
}

.login-aviso a:hover {
    background: var(--primary-dark);
}

@media (max-width: 768px) {
    .header-preview h1 { font-size: 2rem; }
    .header-preview p { font-size: 1.2rem; }
    .pricing-title { font-size: 2rem; }
    .pricing-subtitle { font-size: 1.2rem; }
    .pricing-cards { grid-template-columns: 1fr; }
}
</style>

<div class="header-preview">
    <div class="container">
        <h1>Renueva tu código de <span class="marca">
            <?php 
            if (!empty($marca["imagen"])) {
                echo '<img src="' . htmlspecialchars($marca["imagen"]) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="marca-imagen">';
            }
            echo htmlspecialchars(ucfirst($marca_nombre)); 
            ?>
        </span></h1>
        <p>Renueva tu destacado con un 50% de descuento exclusivo</p>
        <div style="margin-top: 20px;">
            <a href="https://www.codigoamigo.com/de-<?php echo urlencode($codigo['marca']); ?>" style="color: white; text-decoration: underline; font-size: 1.1rem; opacity: 0.9;"><i class="fas fa-arrow-left"></i> Volver a la página de <?php echo htmlspecialchars(ucfirst($marca_nombre)); ?></a>
        </div>
    </div>
</div>

<div class="container-pricing">
    <?php if (!empty($mensaje_exito)): ?>
        <div class="msg-exito">✅ <?php echo $mensaje_exito; ?></div>
        <div style="text-align:center;margin-top:1.5rem;">
            <a href="https://www.codigoamigo.com/de-<?php echo urlencode($codigo['marca']); ?>" 
               style="display:inline-block;padding:1rem 2.5rem;background:var(--primary-color);color:white;text-decoration:none;border-radius:8px;font-weight:600;font-size:1.1rem;">Ver mi código en la marca →</a>
        </div>
    <?php elseif (!$user_logged): ?>
        <!-- Login inline para renovar -->
        <div class="login-inline-container">
            <div class="login-inline-card">
                <div class="login-inline-icon">🔒</div>
                <h2 class="login-inline-title">Inicia sesi&oacute;n para renovar</h2>
                <p class="login-inline-subtitle">Accede a tu cuenta para renovar tu destacado con descuento exclusivo</p>
                
                <!-- Google Sign-In (opción principal) -->
                <div class="login-inline-google">
                    <div id="gsi-renovar-btn"></div>
                </div>

                <!-- Cargar Google Identity Services y renderizar el botón -->
                <script src="https://accounts.google.com/gsi/client" async defer onload="initGsiRenovar()"></script>
                <script>
                function initGsiRenovar() {
                    if (typeof google === 'undefined' || !google.accounts) return;
                    google.accounts.id.initialize({
                        client_id: '298004995594-1qm1qpjok7lq4lo1kdd45406j9rarcqd.apps.googleusercontent.com',
                        callback: googleLoginRenovar
                    });
                    google.accounts.id.renderButton(
                        document.getElementById('gsi-renovar-btn'),
                        { 
                            type: 'standard',
                            size: 'large', 
                            theme: 'filled_blue', 
                            text: 'continue_with', 
                            shape: 'pill',
                            logo_alignment: 'center',
                            width: 340
                        }
                    );
                }
                // If script already loaded
                if (typeof google !== 'undefined' && google.accounts) {
                    initGsiRenovar();
                }
                </script>

                <div class="login-inline-divider"><span>o inicia sesi&oacute;n con email</span></div>

                <!-- Formulario de login -->
                <form id="login-renovar-form" class="login-inline-form">
                    <div class="login-inline-field">
                        <input type="email" id="renovar-email" placeholder="Correo electr&oacute;nico" required>
                    </div>
                    <div class="login-inline-field">
                        <input type="password" id="renovar-pass" placeholder="Contrase&ntilde;a" required>
                    </div>
                    <button type="submit" class="login-inline-btn" id="renovar-login-btn">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesi&oacute;n
                    </button>
                    <div id="renovar-login-error" class="login-inline-error" style="display:none;"></div>
                </form>

                <div class="login-inline-links">
                    <a href="/cambiar_password">¿Olvidaste tu contraseña?</a>
                    <span class="login-inline-sep">·</span>
                    <a href="/registro">¿No tienes cuenta? Reg&iacute;strate</a>
                </div>
            </div>
        </div>

        <style>
        .login-inline-container {
            max-width: 460px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .login-inline-card {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            text-align: center;
        }
        .login-inline-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .login-inline-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #222;
            margin: 0 0 0.5rem;
        }
        .login-inline-subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1.8rem;
        }
        .login-inline-google {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .login-inline-google .g_id_signin {
            width: 100%;
            max-width: 340px;
            display: flex;
            justify-content: center;
        }
        .login-inline-divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
            color: #adb5bd;
            font-size: 13px;
        }
        .login-inline-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }
        .login-inline-divider span {
            background: #fff;
            padding: 0 14px;
            position: relative;
        }
        .login-inline-form {
            text-align: left;
        }
        .login-inline-field {
            margin-bottom: 14px;
        }
        .login-inline-field input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.2s;
            box-sizing: border-box;
            outline: none;
        }
        .login-inline-field input:focus {
            border-color: #E30613;
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.12);
        }
        .login-inline-btn {
            width: 100%;
            padding: 14px;
            background: #E30613;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 6px 20px rgba(227, 6, 19, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .login-inline-btn:hover {
            background: #C40510;
            transform: translateY(-1px);
        }
        .login-inline-btn:disabled {
            opacity: 0.6;
            cursor: wait;
        }
        .login-inline-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 12px;
            text-align: center;
        }
        .login-inline-links {
            margin-top: 1.5rem;
            font-size: 13px;
            color: #888;
        }
        .login-inline-links a {
            color: #E30613;
            text-decoration: none;
            font-weight: 500;
        }
        .login-inline-links a:hover {
            text-decoration: underline;
        }
        .login-inline-sep {
            margin: 0 8px;
            color: #ccc;
        }
        @media (max-width: 480px) {
            .login-inline-card {
                padding: 1.8rem 1.2rem;
            }
        }
        </style>

        <script>
        // Google Sign-In callback para la página de renovación
        function googleLoginRenovar(response) {
            if (!response || !response.credential) return;
            
            // Guardar redirect URL antes del login
            try {
                localStorage.setItem('redirectAfterLogin', window.location.href);
            } catch(e) {}

            $.ajax({
                type: "POST",
                url: "/api/login.php",
                data: {
                    metodo: "google_login",
                    credential: response.credential
                },
                cache: false,
                success: function(data) {
                    var resp = typeof data === 'string' ? JSON.parse(data.trim()) : data;
                    if (resp && resp.success) {
                        location.reload();
                    } else {
                        var errEl = document.getElementById('renovar-login-error');
                        if (errEl) {
                            errEl.textContent = resp.error || 'Error al iniciar sesión con Google';
                            errEl.style.display = 'block';
                        }
                    }
                },
                error: function() {
                    var errEl = document.getElementById('renovar-login-error');
                    if (errEl) {
                        errEl.textContent = 'Error de conexión. Inténtalo de nuevo.';
                        errEl.style.display = 'block';
                    }
                }
            });
        }

        // Formulario de login con email/contraseña
        $(document).ready(function() {
            $('#login-renovar-form').on('submit', function(e) {
                e.preventDefault();
                var btn = $('#renovar-login-btn');
                var errEl = $('#renovar-login-error');
                errEl.hide();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...');

                $.ajax({
                    type: "POST",
                    url: "/myphp/ajax_actions.php",
                    data: {
                        metodo: "login_user",
                        username: $('#renovar-email').val(),
                        password: $('#renovar-pass').val()
                    },
                    cache: false,
                    timeout: 10000,
                    success: function(data) {
                        var resp = typeof data === 'string' ? JSON.parse(data.trim()) : data;
                        if (resp.success) {
                            location.reload();
                        } else {
                            var msg = 'Error al iniciar sesión';
                            if (resp.error === 'no_trobat') msg = 'Usuario o contraseña incorrectos';
                            else if (resp.error === 'no_verificado') msg = 'Activa tu cuenta desde el email de registro';
                            else if (resp.error) msg = resp.error;
                            errEl.text(msg).show();
                            btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> Iniciar Sesión');
                        }
                    },
                    error: function() {
                        errEl.text('Error de conexión. Inténtalo de nuevo.').show();
                        btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> Iniciar Sesión');
                    }
                });
            });
        });
        </script>
    <?php elseif (!$is_owner): ?>
        <div class="msg-error">Este código no pertenece a tu cuenta.</div>
    <?php elseif (!$fue_destacado): ?>
        <div class="msg-error">Este código no fue destacado anteriormente. <a href="https://www.codigoamigo.com/destaca?codigo=<?php echo urlencode($codigo_id_str); ?>" style="color:#721c24;font-weight:600;">Destacar por primera vez →</a></div>
    <?php else: ?>
        <?php if (!empty($mensaje_error)): ?>
            <div class="msg-error"><?php echo $mensaje_error; ?></div>
        <?php endif; ?>
        <?php if (!empty($mensaje_gestion)): ?>
            <div class="msg-exito"><?php echo $mensaje_gestion; ?></div>
        <?php endif; ?>

        <?php if ($auto_renovar_actual && $destacado_vigente): ?>
            <!-- El destacado ya está vigente y con auto-renovación activa: no hace falta renovar manualmente -->
            <div class="banner-autorenovar">
                <div class="banner-autorenovar-icon">🔄</div>
                <div class="banner-autorenovar-texto">
                    <strong>Ya tienes la auto-renovación activada</strong>
                    <p>Tu destacado en <?php echo htmlspecialchars(ucfirst($marca_nombre)); ?> sigue activo<?php echo $fecha_fin_fmt ? " hasta el <strong>$fecha_fin_fmt</strong>" : ''; ?> y se renovará solo al expirar (50% dto desde tu saldo). <strong>No necesitas hacer nada.</strong></p>
                </div>
                <form method="POST" class="banner-autorenovar-form">
                    <input type="hidden" name="accion" value="gestionar_auto_renovar">
                    <input type="hidden" name="auto_renovar_destacado" value="0">
                    <button type="submit" class="banner-autorenovar-btn">Desactivar auto-renovación</button>
                </form>
            </div>
            <p style="text-align:center;color:var(--text-light);font-size:1rem;margin-bottom:2rem;">
                Si lo prefieres, también puedes renovar manualmente ahora mismo:
            </p>
        <?php elseif (!$auto_renovar_actual && $destacado_vigente): ?>
            <!-- Destacado vigente pero SIN auto-renovación: ofrecer activarla sin coste inmediato -->
            <div class="banner-autorenovar banner-autorenovar--off">
                <div class="banner-autorenovar-icon">⏰</div>
                <div class="banner-autorenovar-texto">
                    <strong>Tu destacado expira<?php echo $fecha_fin_fmt ? " el $fecha_fin_fmt" : ' pronto'; ?></strong>
                    <p>Actívale la auto-renovación y no perderás visibilidad: se renovará solo al expirar con un 50% de descuento desde tu saldo.</p>
                </div>
                <form method="POST" class="banner-autorenovar-form">
                    <input type="hidden" name="accion" value="gestionar_auto_renovar">
                    <input type="hidden" name="auto_renovar_destacado" value="1">
                    <button type="submit" class="banner-autorenovar-btn banner-autorenovar-btn--on">Activar auto-renovación</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="pricing-header">
            <h1 class="pricing-title">Renueva tu destacado</h1>
            <p class="pricing-subtitle">Elige el plan que mejor se adapte a tus necesidades</p>
            <div class="descuento-badge">🎉 -50% Descuento exclusivo de renovación</div>
        </div>

        <div class="pricing-cards">
            <!-- Plan Básico -->
            <div class="pricing-card">
                <div class="plan-name">Plan Básico</div>
                <div class="plan-price-wrapper">
                    <div class="precio-original"><?php echo number_format($precio_normal, 2, ',', '.'); ?>€</div>
                    <div class="plan-price"><?php echo number_format($precio_normal_desc, 2, ',', '.'); ?>€</div>
                    <div class="plan-duracion"><?php echo DESTACADO_DURACION_NORMAL; ?> días</div>
                </div>
                <div class="plan-features">
                    <div class="plan-feature">Destacado en listados de marca</div>
                    <div class="plan-feature">Mayor visibilidad</div>
                    <div class="plan-feature">Prioridad en búsquedas</div>
                </div>
                <form method="POST">
                    <input type="hidden" name="tipo_destacado" value="normal">
                    <label class="toggle-autorenovar">
                        <input type="checkbox" name="auto_renovar_destacado" value="1" <?php echo $auto_renovar_actual ? 'checked' : ''; ?>>
                        <span><strong>Auto-renovar</strong> automáticamente (50% dto)</span>
                    </label>
                    <button type="submit" class="pricing-button" <?php echo ($saldo_usuario < $precio_normal_desc) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                        <?php if ($saldo_usuario >= $precio_normal_desc): ?>
                            Renovar por <?php echo number_format($precio_normal_desc, 2, ',', '.'); ?>€
                        <?php else: ?>
                            Saldo insuficiente
                        <?php endif; ?>
                    </button>
                </form>
            </div>

            <!-- Plan Premium (Super) -->
            <div class="pricing-card destacado-oro">
                <div class="plan-name">⭐ Plan Premium</div>
                <div class="plan-price-wrapper">
                    <div class="precio-original"><?php echo number_format($precio_super, 2, ',', '.'); ?>€</div>
                    <div class="plan-price"><?php echo number_format($precio_super_desc, 2, ',', '.'); ?>€</div>
                    <div class="plan-duracion"><?php echo DESTACADO_DURACION_SUPER; ?> días</div>
                </div>
                <div class="plan-features">
                    <div class="plan-feature">Destacado en página principal</div>
                    <div class="plan-feature">Destacado en listados de marca</div>
                    <div class="plan-feature">Promoción en redes sociales</div>
                    <div class="plan-feature">Máxima visibilidad</div>
                </div>
                <form method="POST">
                    <input type="hidden" name="tipo_destacado" value="super">
                    <label class="toggle-autorenovar">
                        <input type="checkbox" name="auto_renovar_destacado" value="1" <?php echo $auto_renovar_actual ? 'checked' : ''; ?>>
                        <span><strong>Auto-renovar</strong> automáticamente (50% dto)</span>
                    </label>
                    <button type="submit" class="pricing-button" <?php echo ($saldo_usuario < $precio_super_desc) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                        <?php if ($saldo_usuario >= $precio_super_desc): ?>
                            Renovar por <?php echo number_format($precio_super_desc, 2, ',', '.'); ?>€
                        <?php else: ?>
                            Saldo insuficiente
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Saldo actual -->
        <div class="saldo-badge">
            <p>💰 Tu saldo actual</p>
            <div class="saldo-amount"><?php echo number_format($saldo_usuario, 2, ',', '.'); ?>€</div>
        </div>

        <!-- Badge de seguridad idéntico a destaca.php -->
        <div class="security-badge">
            <p>
                <i class="fas fa-shield-alt secure-icon"></i>
                Pago 100% Seguro desde tu saldo
            </p>
            <div class="payment-icons">
                <i class="fas fa-wallet" title="Monedero"></i>
                <i class="fas fa-lock" title="Conexión Segura"></i>
                <i class="fas fa-sync-alt" title="Auto-renovación"></i>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
