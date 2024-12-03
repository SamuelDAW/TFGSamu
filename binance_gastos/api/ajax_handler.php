<?php
include_once '../config/database.php';
include_once '../controllers/UsuarioController.php';
include_once '../controllers/GrupoController.php';
include_once '../controllers/GastoController.php';

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
                // Añade esta línea para depurar
                error_log("User Data: " . print_r($userData, true));
                $response = [
                    "success" => true,
                    "nombre" => $userData['nombre']
                ];
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'createGroup':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $groupName = $data->groupName;
                error_log("Datos recibidos: user_id=" . $_SESSION['user_id'] . ", groupName=" . $groupName); // Depuración
                $grupoController = new GrupoController($db);
                $response = $grupoController->createGroup($_SESSION['user_id'], $groupName);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'logout':
            session_start(); // Iniciar la sesión
            session_destroy(); // Destruir la sesión
            $response = ["success" => true, "message" => "Sesión cerrada"];
            break;
        case 'getGroupsByUser':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $grupoController = new GrupoController($db);
                $response = $grupoController->getGroupsByUser($_SESSION['user_id']);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'joinGroup':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $invitationCode = $data->invitationCode;
                $grupoController = new GrupoController($db);
                $response = $grupoController->joinGroup($_SESSION['user_id'], $invitationCode);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'updateGroup':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $groupId = $data->groupId;
                $newName = $data->newName;
                $grupoController = new GrupoController($db);
                $response = $grupoController->updateGroup($_SESSION['user_id'], $groupId, $newName);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'deleteGroup':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $groupId = $data->groupId;
                $grupoController = new GrupoController($db);
                $response = $grupoController->deleteGroup($_SESSION['user_id'], $groupId);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'getGroupMembers':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $groupId = $data->groupId;
                $grupoController = new GrupoController($db);
                $response = $grupoController->getGroupMembers($_SESSION['user_id'], $groupId);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'expelMember':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $groupId = $data->groupId;
                $memberId = $data->memberId;
                $grupoController = new GrupoController($db);
                $response = $grupoController->expelMember($_SESSION['user_id'], $groupId, $memberId);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'getGroupDetails':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $groupId = $data->groupId;
                $grupoController = new GrupoController($db);
                $response = $grupoController->getGroupDetails($_SESSION['user_id'], $groupId);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'addExpense':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $groupId = $data->groupId;
                $name = $data->name;
                $amount = $data->amount;
                $participants = $data->participants;

                $gastoController = new GastoController($db);
                $response = $gastoController->addExpense($userId, $groupId, $name, $amount, $participants);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
        case 'getGroupExpenses':
            session_start();
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $groupId = $data->groupId;
                $gastoController = new GastoController($db);
                $response = $gastoController->getGroupExpenses($userId, $groupId);
            } else {
                $response = ["success" => false, "message" => "Usuario no autenticado"];
            }
            break;
    }
}

echo json_encode($response);
?>