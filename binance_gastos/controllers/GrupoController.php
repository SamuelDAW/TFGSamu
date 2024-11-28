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
        $groupsData = $grupo->getGroupsByUser($userId);
        return ["success" => true, "data" => $groupsData];
    }

    public function joinGroup($userId, $invitationCode) {
        $grupo = new Grupo($this->db);

        // Verificar si el grupo existe por código de invitación
        $groupData = $grupo->getGroupByInvitationCode($invitationCode);
        if (!$groupData) {
            return ["success" => false, "message" => "El código de invitación no es válido"];
        }

        $groupId = $groupData['id'];

        // Verificar si el usuario ya pertenece al grupo
        if ($grupo->isUserInGroup($userId, $groupId)) {
            return ["success" => false, "message" => "Ya perteneces a este grupo"];
        }

        // Insertar al usuario en la tabla participantes con rol 'Miembro'
        $query = "INSERT INTO participantes (id_grupo, id_usuario, rol) VALUES (:id_grupo, :id_usuario, 'Miembro')";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_grupo', $groupId);
        $stmt->bindParam(':id_usuario', $userId);
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Te has unido al grupo exitosamente"];
        } else {
            return ["success" => false, "message" => "Error al unirse al grupo"];
        }
    }

    public function updateGroup($userId, $groupId, $newName) {
        $grupo = new Grupo($this->db);

        // Verificar si el usuario es administrador del grupo
        if (!$grupo->isUserAdminInGroup($userId, $groupId)) {
            return ["success" => false, "message" => "No tienes permisos para editar este grupo"];
        }

        // Actualizar el nombre del grupo
        if ($grupo->updateGroupName($groupId, $newName)) {
            return ["success" => true, "message" => "Grupo actualizado exitosamente"];
        } else {
            return ["success" => false, "message" => "Error al actualizar el grupo"];
        }
    }

    public function deleteGroup($userId, $groupId) {
        $grupo = new Grupo($this->db);

        // Verificar si el usuario es administrador del grupo
        if (!$grupo->isUserAdminInGroup($userId, $groupId)) {
            return ["success" => false, "message" => "No tienes permisos para eliminar este grupo"];
        }

        // Eliminar el grupo
        if ($grupo->deleteGroup($groupId)) {
            return ["success" => true, "message" => "Grupo eliminado exitosamente"];
        } else {
            return ["success" => false, "message" => "Error al eliminar el grupo"];
        }
    }

    public function getGroupMembers($userId, $groupId) {
        $grupo = new Grupo($this->db);

        // Verificar si el usuario pertenece al grupo
        if (!$grupo->isUserInGroup($userId, $groupId)) {
            return ["success" => false, "message" => "No perteneces a este grupo"];
        }

        // Obtener si el usuario es administrador
        $isAdmin = $grupo->isUserAdminInGroup($userId, $groupId);

        // Obtener los miembros del grupo
        $members = $grupo->getMembersByGroup($groupId);

        return ["success" => true, "data" => $members, "isAdmin" => $isAdmin];
    }

    public function expelMember($userId, $groupId, $memberId) {
        $grupo = new Grupo($this->db);

        // Verificar si el usuario es administrador del grupo
        if (!$grupo->isUserAdminInGroup($userId, $groupId)) {
            return ["success" => false, "message" => "No tienes permisos para expulsar miembros de este grupo"];
        }

        // No permitir que se expulse a sí mismo
        if ($memberId == $userId) {
            return ["success" => false, "message" => "No puedes expulsarte a ti mismo"];
        }

        // Expulsar al miembro
        if ($grupo->removeMember($groupId, $memberId)) {
            return ["success" => true, "message" => "Miembro expulsado exitosamente"];
        } else {
            return ["success" => false, "message" => "Error al expulsar al miembro"];
        }
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