<?php
/**
 * CRON: Recuperación de Carritos Abandonados VIP
 * 
 * Este script busca sesiones de checkout de Stripe que se iniciaron
 * hace más de 2 horas pero donde el usuario aún no es VIP.
 * Se les envía un email recordatorio animándoles a finalizar el pago.
 * 
 * Se recomienda ejecutar cada 15-30 minutos:
 * Cada 15 minutos:
 * php /home/admin/web/codigoamigo.com/public_html/cron/recuperar_carritos_vip.php >> /home/admin/web/codigoamigo.com/public_html/cron/cron_recuperar_carritos.log 2>&1
 */

// Evitar bloqueos de tiempo
set_time_limit(0);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/brevo_api.php';

echo "[".date('Y-m-d H:i:s')."] INICIANDO RECUPERACIÓN DE CARRITOS VIP...\n";

try {
    $db = createConnection();
    if (!$db) {
        throw new Exception("No hay conexión a la base de datos.");
    }

    $coll_checkouts = $db->selectCollection('vip_checkout_intents');
    $coll_usuarios = $db->selectCollection('usuarios');
    
    // Buscar intenciones de checkout creadas hace entre 2 horas y 48 horas
    // que aún no han recibido el email de recuperación
    $dos_horas_atras = new MongoDB\BSON\UTCDateTime((time() - 7200) * 1000);
    $cuarenta_y_ocho_horas_atras = new MongoDB\BSON\UTCDateTime((time() - 172800) * 1000);
    
    $query = [
        'recovery_email_sent' => false,
        'created_at' => [
            '$lte' => $dos_horas_atras,
            '$gte' => $cuarenta_y_ocho_horas_atras
        ]
    ];
    
    $checkouts = $coll_checkouts->find($query);
    $procesados = 0;
    $emails_enviados = 0;
    
    foreach ($checkouts as $checkout) {
        $procesados++;
        $usuario_id = $checkout['usuario_id'];
        
        // Cargar usuario
        $usuario = $coll_usuarios->findOne(['_id' => $usuario_id]);
        if (!$usuario) {
            // Usuario no existe, marcar como completado/ignorar
            $coll_checkouts->updateOne(
                ['_id' => $checkout['_id']],
                ['$set' => ['recovery_email_sent' => true, 'status' => 'user_not_found']]
            );
            continue;
        }
        
        // Verificar si ya es VIP
        if (!empty($usuario['is_vip']) && $usuario['is_vip'] === true) {
            // Ya compró, o ya es VIP pagando por otra vía
            $coll_checkouts->updateOne(
                ['_id' => $checkout['_id']],
                ['$set' => ['recovery_email_sent' => true, 'status' => 'completed']]
            );
            echo "- Usuario {$usuario['username']} ya es VIP. Marcando carrito como completado.\n";
            continue;
        }
        
        // El usuario NO es VIP y abandonó el carrito. ¡Enviar email!
        $email_destino = $usuario['mail'];
        $nombre_destino = $usuario['username'];
        
        // Obtener leads si tiene para personalizar aún más el email
        $viewers_data = obtener_viewers_usuario((string)$usuario_id);
        $total_viewers = $viewers_data['total_viewers'] ?? 0;
        $total_potencial = $viewers_data['total_potencial'] ?? 0;
        
        $asunto = "Tienes referidos esperándote, " . $nombre_destino;
        
        // Contruir HTML del email
        $html_content = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
            <div style='text-align: center; padding: 20px 0;'>
                <img src='https://www.codigoamigo.com/img/logo_dark.png' alt='Código Amigo' style='max-height: 50px; background: #1a1a2e; padding: 10px 20px; border-radius: 10px;'>
            </div>
            
            <div style='background: white; border-radius: 15px; padding: 30px; border: 1px solid #e9ecef; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                <h2 style='color: #1a1a2e; margin-top: 0;'>¡No dejes escapar tus conversiones!</h2>
                
                <p>Hola <strong>{$nombre_destino}</strong>,</p>
                <p>Hemos notado que dejaste tu suscripción VIP a medias. ¡Estás a un solo paso de empezar a contactar con tus referidos perdidos!</p>
                ";
                
        if ($total_viewers > 0) {
            $html_content .= "
                <div style='background: #f8f9fa; border-left: 4px solid #ffd700; padding: 15px; margin: 20px 0; border-radius: 0 10px 10px 0;'>
                    <p style='margin: 0; font-size: 16px;'>
                        Actualmente tienes <strong>{$total_viewers} personas</strong> que han visto tus códigos recientemente. 
                        ¡Eso supone un potencial de <strong>" . number_format($total_potencial, 0) . "€</strong>!
                    </p>
                </div>
            ";
        }
        
        $html_content .= "
                <p>Recuerda que con la cuenta VIP podrás:</p>
                <ul style='color: #555; line-height: 1.6;'>
                    <li><strong>Chatear sin límites</strong> con cualquier usuario que haya hecho clic en tus códigos.</li>
                    <li>Ganarte la confianza con el <strong>Badge Dorado Verificado</strong>.</li>
                    <li>Recibir <strong>10€ de saldo gratis</strong> al instante para destacar tus publicaciones.</li>
                </ul>
                
                <div style='text-align: center; margin: 35px 0;'>
                    <a href='https://www.codigoamigo.com/public/mis_viewers.php' style='background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color: white; padding: 16px 40px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 18px; display: inline-block; box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);'>Completar mi suscripción VIP</a>
                </div>
                
                <p style='color: #888; font-size: 13px; text-align: center;'>Cancela cuando quieras. Sin compromisos de permanencia.</p>
            </div>
        </div>
        ";
        
        // Enviar usando la API de Brevo
        $resultado_email = enviarNewsletterBrevoAPI($email_destino, $nombre_destino, $asunto, $html_content);
        
        if ($resultado_email['success']) {
            echo "+ Correo de recuperación enviado a {$email_destino} (Usuario: {$nombre_destino})\n";
            $emails_enviados++;
            
            // Marcar como enviado en la bd
            $coll_checkouts->updateOne(
                ['_id' => $checkout['_id']],
                ['$set' => [
                    'recovery_email_sent' => true, 
                    'recovery_date' => new MongoDB\BSON\UTCDateTime(),
                    'recovery_message_id' => $resultado_email['message_id']
                ]]
            );
        } else {
            echo "x ERROR enviando a {$email_destino}: {$resultado_email['error']}\n";
        }
    }
    
    echo "[".date('Y-m-d H:i:s')."] PROCESO FINALIZADO. {$procesados} analizados, {$emails_enviados} correos enviados.\n";
    
} catch (Throwable $e) {
    error_log("Error crítico en cron recuperar_carritos_vip: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
