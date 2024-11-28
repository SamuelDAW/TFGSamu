<?php
class Grupo {
    private $conn;
    private $table_name = "grupos";

    public $nombre;
    public $ownerId;
    public $codigoInvitacion;
    public $fechaCreacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        try {
            $query = "INSERT INTO " . $this->table_name . " (nombre, creador, codigo_invitacion, fecha_creacion) VALUES (:nombre, :creador, :codigo_invitacion, :fecha_creacion)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':creador', $this->ownerId);
            $stmt->bindParam(':codigo_invitacion', $this->codigoInvitacion);
            $stmt->bindParam(':fecha_creacion', $this->fechaCreacion);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al crear el grupo: " . $e->getMessage());
            return false;
        }
    }

    public function existeNombreGrupo($nombre) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE nombre = :nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }

    public function existeCodigoInvitacion($codigo) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE codigo_invitacion = :codigo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }

    public function getGroupsByUser($userId) {
        $query = "SELECT g.*, p.rol FROM " . $this->table_name . " g
                  JOIN participantes p ON g.id = p.id_grupo
                  WHERE p.id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener grupo por código de invitación
    public function getGroupByInvitationCode($invitationCode) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE codigo_invitacion = :codigo_invitacion LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo_invitacion', $invitationCode);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verificar si el usuario ya está en el grupo
    public function isUserInGroup($userId, $groupId) {
        $query = "SELECT COUNT(*) as total FROM participantes WHERE id_usuario = :id_usuario AND id_grupo = :id_grupo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->bindParam(':id_grupo', $groupId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }

    // Verificar si el usuario es administrador en un grupo
    public function isUserAdminInGroup($userId, $groupId) {
        $query = "SELECT rol FROM participantes WHERE id_usuario = :id_usuario AND id_grupo = :id_grupo LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->bindParam(':id_grupo', $groupId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['rol'] === 'Administrador';
    }

    // Actualizar el nombre del grupo
    public function updateGroupName($groupId, $newName) {
        $query = "UPDATE " . $this->table_name . " SET nombre = :nombre WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $newName);
        $stmt->bindParam(':id', $groupId);
        return $stmt->execute();
    }

    // Eliminar el grupo
    public function deleteGroup($groupId) {
        // Opcional: Aquí puedes eliminar registros relacionados, como gastos y participantes
        
        // Eliminar participantes del grupo
        $query = "DELETE FROM participantes WHERE id_grupo = :id_grupo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_grupo', $groupId);
        $stmt->execute();

        // Eliminar el grupo
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $groupId);
        return $stmt->execute();
    }

    // Obtener miembros por grupo
    public function getMembersByGroup($groupId) {
        $query = "SELECT u.id, u.nombre, p.rol FROM participantes p
                  JOIN usuarios u ON p.id_usuario = u.id
                  WHERE p.id_grupo = :id_grupo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_grupo', $groupId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Eliminar miembro del grupo
    public function removeMember($groupId, $memberId) {
        $query = "DELETE FROM participantes WHERE id_grupo = :id_grupo AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_grupo', $groupId);
        $stmt->bindParam(':id_usuario', $memberId);
        return $stmt->execute();
    }
}
?>