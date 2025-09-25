<?php

// Cargar autoloader de Composer para MongoDB
require_once __DIR__ . '/../vendor/autoload.php';

include_once __DIR__ . '/../inc/includes.php';



if ($_REQUEST) {

    $datos = $_REQUEST;
    switch ($_REQUEST["metodo"]) {

        /****************************************************************************
         *  USUARIO
         ****************************************************************************/

        case "login_user":
            login_user($datos);
            break;

        case "more_codes":
            more_codes($datos);
            break;

        case "last_codigo":
            last_codigo($datos);
            break;

        case "show_estatistics":
            show_estatistics($datos);
            break;

        case "enviar_buzz_codigo":
            enviar_buzz_codigo($datos);
            break;



        case "login_user_facebook":
            login_user_facebook($datos);
            break;

        case "google_login":
            google_login($datos);
            break;

        case "registrar_usuario":
            registrar_usuario($datos, $datos["origin"]);
            $collection_usuarios = getCollectionUsuarios();
            $usuario = $collection_usuarios->findOne(
                [
                    'mail' => $datos['correo']
                ]
            );


            $_SESSION["user_id"] = $usuario["_id"];
            $_SESSION["mail"] = $usuario["mail"];
            $_SESSION["username"] = $usuario["username"];
            break;

        case "editar_perfil":
            editar_perfil($datos);
            break;

        case "desbanear_usuario":
            desbanear_usuario($datos);
            break;

        case "baneo_temporal":
            baneo_temporal($datos);
            break;

        case "baneo_definitivo":
            baneo_definitivo($datos);
            break;


        /***********************************
         *  CAPTACIÓN DE CLIENTES
         **********************************/

        case "descontar_lead_sin_validar":
            descontar_lead_sin_validar($datos);
            break;

        case "guardar_token_compra_lead_sin_validar":
            guardar_token_compra_lead_sin_validar($datos);
            break;

        case "descontar_lead_validado":
            descontar_lead_validado($datos);
            break;

        case "guardar_token_compra_lead_validado":
            guardar_token_compra_lead_validado($datos);
            break;

        /****************************************************************************
         *  PANEL DE CONTROL
         ****************************************************************************/


        case "desactivar_codigo":
            desactivar_codigo($datos);
            break;

        case "desactivar_codigo_usuario":

            desactivar_codigo_usuario($datos);
            break;

        case "restaurar_codigo_usuario":

            restaurar_codigo_usuario($datos);
            break;

        case "borrar_codigo":
            borrar_codigo($datos);
            break;

        case "actualizar_codigo":
            actualizar_codigo($datos);
            break;

        case "actualizar_marca":
            actualizar_marca($datos);
            break;


        case "update_marca":

            update_marca($datos);
            break;


        case "fusiona_marcas":

            fusiona_marcas($datos);
            break;

        case "sube_imagen_marca":

            sube_imagen_marca($datos);
            break;

        case "borrar_marca":
            borrar_marca($datos);
            break;

        /**********************************
         *  PUBLICAR CÓDIGO
         *********************************/

        case "publicar_nuevo_codigo":
            publicar_nuevo_codigo($datos);

            break;

        case "buscar_marcas":
            buscar_marcas($datos);
            break;

        /**********************************
         *  CONTACTO
         *********************************/

        case "formulario_contacto":
            echo formulario_contacto($datos);
            break;

    }

}

function update_marca($datos)
{

    //     echo "adsadsdasdas";
//     print_r($datos);die;

    try {

        $collection_marcas = getCollectionMarcas();

        $updateResult = $collection_marcas->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($datos["id_marca"])],
            [
                '$set' =>
                    [
                        'categoria' => $datos['marca_sel_txt'],
                        'categoria_clave' => $datos['marca_sel'],
                        'aviso' => 'revisada'

                    ]
            ]
        );
    } catch (MongoCursorException $e) {
        echo "Error al modificar datos\n";
    }

}

function editar_perfil($datos)
{
    try {
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $datos["correo"]],
            [
                '$set' => [
                    'username' => $datos['nombre'],
                    'notis' => $datos['notis'],
                    'email_comm' => $datos['email_comm'],
                    'pass' => $datos['password'],
                    'confirm_password' => $datos['password'],
                    'telefono' => $datos['telefono'],
                    'whatsapp' => $datos['whatsapp']
                ]
            ]
        );
    } catch (MongoCursorException $e) {
        echo "Error al modificar datos\n";
    }
}





?>