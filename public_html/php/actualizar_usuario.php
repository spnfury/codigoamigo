<?php 

    // Validar que las variables estén definidas
    if (!isset($new_password) || !isset($new_confirm_password) || !isset($mail)) {
        echo "<script>alert('Error: Faltan datos del formulario'); window.location='/cambiar_password?msg_error=missing_data';</script>";
        exit;
    }

    // Validar que las contraseñas no estén vacías
    if (empty($new_password) || empty($new_confirm_password) || empty($mail)) {
        echo "<script>alert('Error: Todos los campos son obligatorios'); window.location='/cambiar_password?msg_error=empty_fields';</script>";
        exit;
    }

    // Validar que las contraseñas coincidan
    if ($new_password !== $new_confirm_password) {
        echo "<script>alert('Error: Las contraseñas no coinciden'); window.location='/cambiar_password?msg_error=password_mismatch';</script>";
        exit;
    }

    // Validar longitud mínima de contraseña
    if (strlen($new_password) < 6) {
        echo "<script>alert('Error: La contraseña debe tener al menos 6 caracteres'); window.location='/cambiar_password?msg_error=password_too_short';</script>";
        exit;
    }

    // Validar formato de email
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Error: Email inválido'); window.location='/cambiar_password?msg_error=invalid_email';</script>";
        exit;
    }

    try {
        $collection_usuarios = getCollectionUsuarios();
        
        // Verificar que el usuario existe antes de actualizar
        $usuario = $collection_usuarios->findOne(['mail' => $mail]);
        if (!$usuario) {
            echo "<script>alert('Error: Usuario no encontrado'); window.location='/cambiar_password?msg_error=user_not_found';</script>";
            exit;
        }

        // Verificar la contraseña actual antes de actualizar
        $contraseña_actual = isset($usuario['pass']) ? $usuario['pass'] : '';
        
        // Actualizar la contraseña
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $mail],
            ['$set' => ['pass' => $new_password, 'confirm_password' => $new_confirm_password]]
        );

        // Verificar que la actualización fue exitosa
        if ($updateResult->getMatchedCount() > 0) {
            // Verificar si la contraseña realmente cambió
            $usuario_actualizado = $collection_usuarios->findOne(['mail' => $mail]);
            $nueva_contraseña_guardada = isset($usuario_actualizado['pass']) ? $usuario_actualizado['pass'] : '';
            
            if ($nueva_contraseña_guardada === $new_password) {
                if ($contraseña_actual === $new_password) {
                    echo "<script>alert('La contraseña es la misma que la actual. No se realizaron cambios.'); window.location='/cambiar_password?msg=password_same';</script>";
                } else {
                    // Hacer login automático del usuario
                    $_SESSION["user_id"] = $usuario['_id']->__toString();
                    $_SESSION["username"] = isset($usuario['username']) ? $usuario['username'] : $usuario['mail'];
                    $_SESSION["mail"] = $usuario['mail'];
                    
                    // Redirigir a mis anuncios con mensaje de confirmación
                    echo "<script>alert('Contraseña actualizada correctamente'); window.location='/mis-anuncios?msg=password_updated';</script>";
                }
            } else {
                echo "<script>alert('Error: La contraseña no se guardó correctamente'); window.location='/cambiar_password?msg_error=save_failed';</script>";
            }
        } else {
            echo "<script>alert('Error: No se encontró el usuario para actualizar'); window.location='/cambiar_password?msg_error=user_not_found';</script>";
        }
        
    } catch(MongoCursorException $e) {
        error_log("Error MongoDB en actualizar_usuario: " . $e->getMessage());
        echo "<script>alert('Error de base de datos: " . addslashes($e->getMessage()) . "'); window.location='/cambiar_password?msg_error=database_error';</script>";
    } catch(Exception $e) {
        error_log("Error general en actualizar_usuario: " . $e->getMessage());
        echo "<script>alert('Error del sistema: " . addslashes($e->getMessage()) . "'); window.location='/cambiar_password?msg_error=system_error';</script>";
    }
?>