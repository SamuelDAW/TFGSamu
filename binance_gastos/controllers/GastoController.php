<?php
// En controllers/GastoController.php

include_once '../models/Gasto.php';
include_once '../models/Grupo.php';

class GastoController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function addExpense($userId, $groupId, $name, $amount, $payer, $participants) {
        try {
            $grupo = new Grupo($this->db);

            // Verificar si el usuario pertenece al grupo
            if (!$grupo->isUserInGroup($userId, $groupId)) {
                return ["success" => false, "message" => "No perteneces a este grupo"];
            }

            $gasto = new Gasto($this->db);
            if ($gasto->crear($groupId, $payer, $name, $amount, $participants)) {
                // Actualizar los saldos
                $this->updateBalances($payer, $groupId, $amount, $participants);
                return ["success" => true, "message" => "Gasto agregado exitosamente"];
            } else {
                return ["success" => false, "message" => "Error al agregar el gasto"];
            }
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return ["success" => false, "message" => "Error en el servidor. Revisa los logs para más detalles."];
        }
    }

    private function updateBalances($payerId, $groupId, $amount, $participants) {
        // Calcular el monto por participante
        $splitAmount = $amount / count($participants);

        // Actualizar el saldo del usuario que pagó
        $updatePayerBalanceQuery = "UPDATE saldos SET cantidad = cantidad + :amount WHERE id_usuario = :userId AND id_grupo = :groupId";
        $updatePayerBalanceStmt = $this->db->prepare($updatePayerBalanceQuery);
        $updatePayerBalanceStmt->bindParam(':amount', $amount);
        $updatePayerBalanceStmt->bindParam(':userId', $payerId);
        $updatePayerBalanceStmt->bindParam(':groupId', $groupId);
        $updatePayerBalanceStmt->execute();

        // Actualizar el saldo de los participantes
        $updateParticipantBalanceQuery = "UPDATE saldos SET cantidad = cantidad - :amount WHERE id_usuario = :participantId AND id_grupo = :groupId";
        $updateParticipantBalanceStmt = $this->db->prepare($updateParticipantBalanceQuery);
        foreach ($participants as $participantId) {
            if ($participantId != $payerId) {
                $updateParticipantBalanceStmt->bindParam(':amount', $splitAmount);
                $updateParticipantBalanceStmt->bindParam(':participantId', $participantId);
                $updateParticipantBalanceStmt->bindParam(':groupId', $groupId);
                $updateParticipantBalanceStmt->execute();
            }
        }

        // Ajustar el saldo del usuario que pagó por su parte del gasto si está en los participantes
        if (in_array($payerId, $participants)) {
            $adjustPayerBalanceQuery = "UPDATE saldos SET cantidad = cantidad - :amount WHERE id_usuario = :userId AND id_grupo = :groupId";
            $adjustPayerBalanceStmt = $this->db->prepare($adjustPayerBalanceQuery);
            $adjustPayerBalanceStmt->bindParam(':amount', $splitAmount);
            $adjustPayerBalanceStmt->bindParam(':userId', $payerId);
            $adjustPayerBalanceStmt->bindParam(':groupId', $groupId);
            $adjustPayerBalanceStmt->execute();
        }
    }

    public function getGroupExpenses($userId, $groupId) {
        try {
            $grupo = new Grupo($this->db);
            // Verificar si el usuario pertenece al grupo
            if (!$grupo->isUserInGroup($userId, $groupId)) {
                return ["success" => false, "message" => "No perteneces a este grupo"];
            }
            $gasto = new Gasto($this->db);
            $expenses = $gasto->getExpensesByGroup($groupId);

            // Calcular el gasto total del grupo
            $totalGroupExpense = array_reduce($expenses, function($sum, $expense) {
                return $sum + $expense['cantidad'];
            }, 0);

            // Calcular el gasto del usuario
            $userExpense = array_reduce($expenses, function($sum, $expense) use ($userId) {
                return $sum + ($expense['id_usuario'] == $userId ? $expense['cantidad'] : 0);
            }, 0);

            return ["success" => true, "data" => $expenses, "totalGroupExpense" => $totalGroupExpense, "userExpense" => $userExpense];
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return ["success" => false, "message" => "Error en el servidor. Revisa los logs para más detalles."];
        }
    }

    public function getUserExpenses($userId, $groupId) {
        try {
            $grupo = new Grupo($this->db);
            // Verificar si el usuario pertenece al grupo
            if (!$grupo->isUserInGroup($userId, $groupId)) {
                return ["success" => false, "message" => "No perteneces a este grupo"];
            }
            $gasto = new Gasto($this->db);
            $expenses = $gasto->getUserExpensesByGroup($userId, $groupId);

            return ["success" => true, "data" => $expenses];
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return ["success" => false, "message" => "Error en el servidor. Revisa los logs para más detalles."];
        }
    }

    public function getBalances($userId, $groupId) {
        try {
            $grupo = new Grupo($this->db);
            // Verificar si el usuario pertenece al grupo
            if (!$grupo->isUserInGroup($userId, $groupId)) {
                return ["success" => false, "message" => "No perteneces a este grupo"];
            }
            $gasto = new Gasto($this->db);
            $balances = $gasto->getBalancesByGroup($groupId);

            return ["success" => true, "data" => $balances];
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return ["success" => false, "message" => "Error en el servidor. Revisa los logs para más detalles."];
        }
    }
}
?>