<?php
/**
 * CRON: Recuperación de Carritos Abandonados — Destacar código / Recarga saldo
 *
 * Mismo patrón que recuperar_carritos_vip.php: busca intentos de checkout de
 * Stripe iniciados hace 2-48h que siguen 'pending' (el webhook los marca
 * 'completed' al pagar) y envía un email recordatorio vía Brevo.
 *
 * Cron sugerido (cada 15-30 min):
 * php /home/admin/web/codigoamigo.com/public_html/cron/recuperar_carritos_destacar.php >> /home/admin/web/codigoamigo.com/public_html/cron/cron_recuperar_carritos_destacar.log 2>&1
 */

set_time_limit(0);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/brevo_api.php';

echo "[" . date('Y-m-d H:i:s') . "] INICIANDO RECUPERACIÓN DE CARRITOS (destacar/recarga)...\n";

try {
    $db = createConnection();
    if (!$db) {
        throw new Exception("No hay conexión a la base de datos.");
    }

    $coll_intents = $db->selectCollection('destacar_checkout_intents');

    $dos_horas_atras = new MongoDB\BSON\UTCDateTime((time() - 7200) * 1000);
    $cuarenta_y_ocho_horas_atras = new MongoDB\BSON\UTCDateTime((time() - 172800) * 1000);

    $query = [
        'status' => 'pending',
        'recovery_email_sent' => false,
        'created_at' => [
            '$lte' => $dos_horas_atras,
            '$gte' => $cuarenta_y_ocho_horas_atras,
        ],
    ];

    $intentos = $coll_intents->find($query);
    $procesados = 0;
    $emails_enviados = 0;

    foreach ($intentos as $intent) {
        $procesados++;
        $email_destino = $intent['email'] ?? '';
        if (empty($email_destino)) {
            $coll_intents->updateOne(['_id' => $intent['_id']], ['$set' => ['recovery_email_sent' => true, 'status' => 'sin_email']]);
            continue;
        }
        $nombre_destino = $intent['username'] ?? explode('@', $email_destino)[0];
        $tipo = $intent['tipo'] ?? 'destacar_codigo';

        if ($tipo === 'destacar_codigo') {
            $marca = $intent['marca'] ?? 'tu código';
            $codigo_id = (string)($intent['codigo_id'] ?? '');
            $asunto = "Tu código de " . $marca . " sigue esperando destacar";
            $cta_url = 'https://www.codigoamigo.com/destacar_codigo?codigo=' . urlencode($codigo_id);
            $cta_texto = 'Completar destacado';
            $cuerpo = "Dejaste a medias el pago para destacar tu código de <strong>{$marca}</strong>. "
                    . "Destacar tu código lo pone arriba del listado y le da mucha más visibilidad — más clics, más uso.";
        } else { // recarga_saldo
            $paquete = $intent['paquete'] ?? '';
            $saldo = $intent['saldo'] ?? '';
            $asunto = "Tu recarga de saldo sigue pendiente";
            $cta_url = 'https://www.codigoamigo.com/mis-anuncios';
            $cta_texto = 'Completar recarga';
            $cuerpo = "Dejaste a medias tu recarga de saldo" . ($saldo ? " de <strong>{$saldo}€</strong>" : "") . ". "
                    . "El saldo te sirve para destacar tus códigos y ganar más visibilidad.";
        }

        $html_content = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
            <div style='text-align: center; padding: 20px 0;'>
                <img src='https://www.codigoamigo.com/img/logo_dark.png' alt='Código Amigo' style='max-height: 50px; background: #1a1a2e; padding: 10px 20px; border-radius: 10px;'>
            </div>
            <div style='background: white; border-radius: 15px; padding: 30px; border: 1px solid #e9ecef; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                <h2 style='color: #1a1a2e; margin-top: 0;'>¡No dejes tu pago a medias!</h2>
                <p>Hola <strong>{$nombre_destino}</strong>,</p>
                <p>{$cuerpo}</p>
                <div style='text-align: center; margin: 35px 0;'>
                    <a href='{$cta_url}' style='background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color: white; padding: 16px 40px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 18px; display: inline-block; box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);'>{$cta_texto}</a>
                </div>
                <p style='color: #888; font-size: 13px; text-align: center;'>Si ya completaste el pago, ignora este correo.</p>
            </div>
        </div>
        ";

        $resultado_email = enviarNewsletterBrevoAPI($email_destino, $nombre_destino, $asunto, $html_content);

        if ($resultado_email['success']) {
            echo "+ Correo de recuperación ({$tipo}) enviado a {$email_destino}\n";
            $emails_enviados++;
            $coll_intents->updateOne(
                ['_id' => $intent['_id']],
                ['$set' => [
                    'recovery_email_sent' => true,
                    'recovery_date' => new MongoDB\BSON\UTCDateTime(),
                    'recovery_message_id' => $resultado_email['message_id'] ?? null,
                ]]
            );
        } else {
            echo "x ERROR enviando a {$email_destino}: " . ($resultado_email['error'] ?? 'desconocido') . "\n";
        }
    }

    echo "[" . date('Y-m-d H:i:s') . "] PROCESO FINALIZADO. {$procesados} analizados, {$emails_enviados} correos enviados.\n";

} catch (Throwable $e) {
    log_error("Error crítico en cron recuperar_carritos_destacar: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
}
