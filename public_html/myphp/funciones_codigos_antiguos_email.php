<?php
/**
 * Funciones de email para desactivación de códigos por antigüedad (>1 año)
 * 
 * Template HTML para notificar al usuario que su código ha sido desactivado
 * por llevar más de 1 año publicado, con CTA para reactivarlo.
 */

require_once __DIR__ . '/email_helper.php';

/**
 * Email: Tu código ha sido desactivado por antigüedad
 * 
 * @param array $usuario Datos del usuario propietario del código
 * @param array $codigo Datos del código desactivado
 * @param string $marca_nombre Nombre clave de la marca
 * @return array|false Resultado del envío
 */
function enviarEmailCodigoDesactivadoPorAntiguedad($usuario, $codigo, $marca_nombre) {
    $email = $usuario['mail'] ?? '';
    $nombre = $usuario['username'] ?? 'Usuario';
    if (empty($email)) return false;

    $fecha_pub = '';
    if (isset($codigo['fecha_publicacion']) && !empty($codigo['fecha_publicacion'])) {
        // Intentar parsear ambos formatos de fecha
        $timestamp = strtotime($codigo['fecha_publicacion']);
        if ($timestamp !== false) {
            $fecha_pub = date('d/m/Y', $timestamp);
        }
    }

    $marca_display = ucfirst(str_replace('-', ' ', $marca_nombre));

    $contenido = '
        <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Tu código amigo en <strong>' . htmlspecialchars($marca_display) . '</strong> ha sido <strong>desactivado automáticamente</strong> porque lleva más de 1 año publicado' . ($fecha_pub ? ' (publicado el ' . $fecha_pub . ')' : '') . '.</p>
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:15px;margin:20px 0;">
            <p style="margin:5px 0;color:#856404;">⏳ Los códigos con más de 1 año de antigüedad se desactivan automáticamente para garantizar la calidad de nuestra plataforma.</p>
            <p style="margin:5px 0;color:#856404;">🔄 Si tu código sigue siendo válido, puedes <strong>reactivarlo</strong> fácilmente desde tu panel.</p>
        </div>
        <p>Si tu código amigo sigue vigente, simplemente haz clic en el botón de abajo para reactivarlo:</p>';

    $cta_url = "https://www.codigoamigo.com/usuario";

    $html = _templateBaseDestacadoEmail('⏳ Código desactivado por antigüedad', $contenido, 'Reactivar mi código', $cta_url);

    $codigo_id = (string)($codigo['_id'] ?? '');

    return enviarEmailConBrevoYRegistrar(
        $email, $nombre,
        '⏳ Tu código en ' . $marca_display . ' ha sido desactivado por antigüedad',
        $html,
        'codigo_desactivado_antiguedad',
        (string)($usuario['_id'] ?? ''),
        ['codigo_id' => $codigo_id, 'marca' => $marca_nombre]
    );
}
