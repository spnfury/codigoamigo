<?php
// scripts/process_gdpr_deletion.php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Adjust path to point to public_html/inc/conexion.php from public_html/scripts/
require_once __DIR__ . '/../inc/conexion.php';

$email = "stan_marshall@hotmail.com";
$target_user_id = "618e4c875fac864cb05a1ec2"; // Confirmed ID

echo "STARTING GDPR DELETION FOR: $email ($target_user_id)\n";

$db = createConnection();
$collection_usuarios = $db->selectCollection('usuarios');
$collection_codigos = $db->selectCollection('codigos');

// 1. Verify user exists before deletion
$user = $collection_usuarios->findOne(['_id' => new \MongoDB\BSON\ObjectId($target_user_id)]);

if (!$user) {
    echo "ERROR: User not found by ID. Aborting safety check.\n";
    exit(1);
}

if ($user['mail'] !== $email) {
    echo "ERROR: User ID does not match email. Safety check failed.\n";
    echo "Expected: $email, Found: " . $user['mail'] . "\n";
    exit(1);
}

echo "Safety checks passed. User confirmed.\n";

// 2. Delete Codes
$deleteGroups = $collection_codigos->deleteMany(['id_usuario' => new \MongoDB\BSON\ObjectId($target_user_id)]);
echo "Deleted " . $deleteGroups->getDeletedCount() . " codes associated with the user.\n";

// 3. Delete User
$deleteUser = $collection_usuarios->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($target_user_id)]);
echo "Deleted " . $deleteUser->getDeletedCount() . " user document.\n";

echo "GDPR DELETION COMPLETED.\n";

?>
