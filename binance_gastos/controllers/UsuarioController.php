<?php
include_once '../models/Usuario.php';

class UsuarioController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function register($data) {
        $usuario = new Usuario($this->db);
        $usuario->nombre = $data->nombre;
        $usuario->email = $data->email;
        $usuario->contrasena = password_hash($data->contrasena, PASSWORD_BCRYPT);

        if ($usuario->registrar()) {
            return ["message" => "Registro exitoso"];
        } else {
            return ["message" => "Error al registrar usuario"];
        }
    }
    public function login($data) {
        $usuario = new Usuario($this->db);
        $usuario->email = $data->email;
    
        $usuarioEncontrado = $usuario->buscarPorEmail();
        if ($usuarioEncontrado && password_verify($data->contrasena, $usuarioEncontrado['contrasena'])) {
            // Iniciar sesión
            session_start();
            $_SESSION['user_id'] = $usuarioEncontrado['id']; // Guardamos el ID del usuario en la sesión
            return [
                "success" => true,
                "message" => "Inicio de sesión exitoso"
            ];
        } else {
            return [
                "success" => false,
                "message" => "Email o contraseña incorrectos"
            ];
        }
    }
    
    
}

?>