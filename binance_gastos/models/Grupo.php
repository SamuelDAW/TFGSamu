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
        $query = "SELECT g.* FROM " . $this->table_name . " g
                  JOIN participantes p ON g.id = p.id_grupo
                  WHERE p.id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>