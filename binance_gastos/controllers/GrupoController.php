<?php
include_once '../models/Grupo.php';

class GrupoController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createGroup($userId, $groupName) {
        $grupo = new Grupo($this->db);

        // Verificar si el nombre del grupo ya existe
        if ($grupo->existeNombreGrupo($groupName)) {
            return ["success" => false, "message" => "El nombre del grupo ya existe"];
        }

        $grupo->nombre = $groupName;
        $grupo->ownerId = $userId;
        $grupo->codigoInvitacion = $this->generarCodigoInvitacion();
        $grupo->fechaCreacion = date('Y-m-d H:i:s');

        if ($grupo->crear()) {
            // Obtener el ID del grupo recién creado
            $groupId = $this->db->lastInsertId();

            // Insertar en la tabla participantes
            $query = "INSERT INTO participantes (id_grupo, id_usuario, rol) VALUES (:id_grupo, :id_usuario, 'Administrador')";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $groupId);
            $stmt->bindParam(':id_usuario', $userId);
            if ($stmt->execute()) {
                return ["success" => true, "message" => "Grupo creado exitosamente"];
            } else {
                return ["success" => false, "message" => "Error al agregar el usuario como participante"];
            }
        } else {
            error_log("Error al crear el grupo: " . print_r($grupo, true));
            return ["success" => false, "message" => "Error al crear el grupo"];
        }
    }

    public function getGroupsByUser($userId) {
        $grupo = new Grupo($this->db);
        return $grupo->getGroupsByUser($userId);
    }

    // Método para generar un código de invitación único
    private function generarCodigoInvitacion() {
        do {
            $codigo = bin2hex(random_bytes(4)); // Código de 8 caracteres hexadecimales
            $grupo = new Grupo($this->db);
        } while ($grupo->existeCodigoInvitacion($codigo));
        return $codigo;
    }
}
?>