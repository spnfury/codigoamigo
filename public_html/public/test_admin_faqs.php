<?php
session_start();

// Test básico para verificar permisos
echo "<h1>Test de permisos de administrador</h1>";
echo "<pre>";

echo "SESSION user_id: " . (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "NO DEFINIDO") . "\n";
echo "SESSION admin: " . (isset($_SESSION["admin"]) ? $_SESSION["admin"] : "NO DEFINIDO") . "\n";

// Verificar permisos como en admin_faqs.php
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

echo "Array de acceso: " . implode(", ", $array_codigos_acceso) . "\n";

$user_id = $_SESSION["user_id"] ?? '';
$tiene_permiso = in_array($user_id, $array_codigos_acceso);

echo "Tiene permiso: " . ($tiene_permiso ? "SI" : "NO") . "\n";

if (!$tiene_permiso) {
    echo "Redirigiendo al home...\n";
    header('Location: https://www.codigoamigo.com');
    exit;
}

echo "Acceso concedido!\n";

echo "</pre>";
?>
