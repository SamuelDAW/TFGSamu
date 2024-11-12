<?php
include_once '../config/database.php';
include_once '../controllers/UsuarioController.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));
$response = ["message" => "Acción no reconocida"];

if (isset($data->action)) {
    $database = new Database();
    $db = $database->getConnection();
    $usuarioController = new UsuarioController($db);

    switch ($data->action) {
        case 'register':
            $response = $usuarioController->register($data);
            break;
        case 'login':
            $response = $usuarioController->login($data);
            break;
        // Otros casos para manejar grupos, etc.
        case 'getUserInfo':
            session_start(); // Asegúrate de iniciar la sesión
            if (isset($_SESSION['user_id'])) {
                $usuario = new Usuario($db);
                $userData = $usuario->getUserById($_SESSION['user_id']);
                $response = [
                    "success" => true,
                    "email" => $userData['email']
                ];
            } else {
                // Si no hay sesión activa, devuelve error
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        
        
            case 'logout':
                session_start(); // Iniciar la sesión
                session_destroy(); // Destruir la sesión
                $response = ["success" => true, "message" => "Sesión cerrada"];
                break;
            
        
        
    }
}

echo json_encode($response);
?>