<?php

class Gasto {
    private $conn;
    private $table_name = "gastos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($groupId, $payerId, $name, $amount, $participants) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();

            // Insertar gasto
            $query = "INSERT INTO " . $this->table_name . " (id_grupo, id_usuario, nombre, cantidad, fecha) VALUES (:id_grupo, :id_usuario, :nombre, :cantidad, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_grupo', $groupId);
            $stmt->bindParam(':id_usuario', $payerId); // Aquí se almacena el pagador
            $stmt->bindParam(':nombre', $name);
            $stmt->bindParam(':cantidad', $amount);
            $stmt->execute();

            // Obtener el ID del gasto insertado
            $expenseId = $this->conn->lastInsertId();

            // Calcular el monto por participante
            $splitAmount = $amount / count($participants);

            // Insertar en la tabla gastos_participantes
            $query = "INSERT INTO gastos_participantes (id_gasto, id_usuario, cantidad) VALUES (:id_gasto, :id_usuario, :cantidad)";
            $stmt = $this->conn->prepare($query);
            foreach ($participants as $participantId) {
                $stmt->bindParam(':id_gasto', $expenseId);
                $stmt->bindParam(':id_usuario', $participantId);
                $stmt->bindParam(':cantidad', $splitAmount);
                $stmt->execute();
            }

            // Confirmar transacción
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->conn->rollBack();
            error_log("Error al crear el gasto: " . $e->getMessage());
            return false;
        }
    }

    public function getExpensesByGroup($groupId) {
        $query = "SELECT g.*, u.nombre AS usuario_nombre FROM gastos g
                  JOIN usuarios u ON g.id_usuario = u.id
                  WHERE g.id_grupo = :groupId
                  ORDER BY g.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':groupId', $groupId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserExpensesByGroup($userId, $groupId) {
        $query = "SELECT g.*, u.nombre AS usuario_nombre FROM gastos g
                  JOIN usuarios u ON g.id_usuario = u.id
                  WHERE g.id_grupo = :id_grupo AND g.id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_grupo', $groupId);
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBalancesByGroup($groupId) {
        $query = "SELECT u.nombre, s.cantidad FROM saldos s
                  JOIN usuarios u ON s.id_usuario = u.id
                  WHERE s.id_grupo = :id_grupo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_grupo', $groupId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>