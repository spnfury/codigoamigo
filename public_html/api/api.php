<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Conexión a la base de datos (ajusta los valores según tu configuración)
$db_host = 'localhost';
$db_user = 'tu_usuario';
$db_pass = 'tu_password';
$db_name = 'tu_base_de_datos';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}

// Función para verificar el número de teléfono
function verifyPhoneNumber($phone) {
    global $conn;
    
    // Limpia el número de teléfono
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Verifica que el número tenga un formato válido (ajusta según tus necesidades)
    if (strlen($phone) < 10 || strlen($phone) > 15) {
        return ['success' => false, 'message' => 'Formato de número inválido'];
    }
    
    // Aquí puedes agregar más validaciones según tus necesidades
    // Por ejemplo, verificar si el número existe en tu base de datos
    
    $stmt = $conn->prepare("SELECT verified FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return ['success' => true, 'verified' => $row['verified']];
    }
    
    return ['success' => false, 'message' => 'Número no encontrado'];
}

// Manejo de la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['phone'])) {
        $response = verifyPhoneNumber($data['phone']);
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'No se proporcionó número de teléfono']);
    }
} else {
    echo json_encode(['error' => 'Método no permitido']);
}

$conn->close();
?>
