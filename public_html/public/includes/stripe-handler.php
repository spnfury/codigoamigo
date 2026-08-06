<?php
include_once __DIR__ . '/../../inc/logger.php';
function procesarPagoPendiente($datos_pago, $stripe_config) {
    try {
        \Stripe\Stripe::setApiKey($stripe_config['secret_key']);
        
        $charge = \Stripe\Charge::create([
            'amount' => $datos_pago["cantidad"],
            'currency' => 'eur',
            'description' => 'CODIGOAMIGO - ' . $datos_pago["lead_id"],
            'source' => $datos_pago["token_id"]
        ]);

        if($charge->status === "succeeded") {
            $obj_id_codigo = new \MongoDB\BSON\ObjectId($datos_pago["lead_id"]);
            $codigo_to_show = getCodeByID($obj_id_codigo);
            añadir_destacado_codigo($codigo_to_show, $datos_pago["codigo_operacion"]);
            
            // Registrar evento de analytics
            registrarEventoPago($obj_id_codigo, $datos_pago["cantidad"]);
            
            header("location:" . $GLOBALS["website"]);
            exit;
        }
    } catch(Exception $e) {
        log_error("Error en procesamiento de pago: " . $e->getMessage());
        header("location:" . $GLOBALS["website"] . "error?msg=pago_fallido");
        exit;
    }
} 