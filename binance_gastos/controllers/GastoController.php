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

    public function getTransactions($groupId) {
        try {
            $gasto = new Gasto($this->db);
            $transactions = $gasto->getTransactionsByGroup($groupId);

            return ["success" => true, "data" => $transactions];
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return ["success" => false, "message" => "Error en el servidor. Revisa los logs para más detalles."];
        }
    }

    public function calculateMinTransactions($userId, $groupId) {
        try {
            $grupo = new Grupo($this->db);
            // Verificar si el usuario pertenece al grupo
            if (!$grupo->isUserInGroup($userId, $groupId)) {
                return ["success" => false, "message" => "No perteneces a este grupo"];
            }

            // Obtener los saldos de los usuarios en el grupo
            $query = "SELECT id_usuario, cantidad FROM saldos WHERE id_grupo = :groupId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':groupId', $groupId);

            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error al obtener los saldos: " . $errorInfo[2]);
                return ["success" => false, "message" => "Error al obtener los saldos"];
            }
            $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Separar deudores y acreedores
            $deudores = [];
            $acreedores = [];

            foreach ($balances as $balance) {
                $amount = round($balance['cantidad'], 2);
                if ($amount < 0) {
                    $deudores[] = [
                        'id_usuario' => $balance['id_usuario'],
                        'cantidad' => abs($amount)
                    ];
                } elseif ($amount > 0) {
                    $acreedores[] = [
                        'id_usuario' => $balance['id_usuario'],
                        'cantidad' => $amount
                    ];
                }
            }

            // Ordenar deudores y acreedores
            usort($deudores, function($a, $b) {
                return $a['cantidad'] - $b['cantidad'];
            });

            usort($acreedores, function($a, $b) {
                return $a['cantidad'] - $b['cantidad'];
            });

            $transactions = [];

            $i = 0; // Índice de deudores
            $j = 0; // Índice de acreedores

            while ($i < count($deudores) && $j < count($acreedores)) {
                $deudor = $deudores[$i];
                $acreedor = $acreedores[$j];

                $cantidad = min($deudor['cantidad'], $acreedor['cantidad']);

                // Agregar transacción
                $transactions[] = [
                    'deudor_id' => $deudor['id_usuario'],
                    'acreedor_id' => $acreedor['id_usuario'],
                    'cantidad' => $cantidad
                ];

                // Ajustar los saldos
                $deudores[$i]['cantidad'] -= $cantidad;
                $acreedores[$j]['cantidad'] -= $cantidad;

                // Avanzar en las listas si el saldo llega a cero (considerando margen por redondeo)
                if (round($deudores[$i]['cantidad'], 2) == 0) {
                    $i++;
                }

                if (round($acreedores[$j]['cantidad'], 2) == 0) {
                    $j++;
                }
            }

            // Obtener nombres de usuarios para las transacciones
            foreach ($transactions as &$transaction) {
                // Obtener nombre del deudor
                $stmt = $this->db->prepare("SELECT nombre FROM usuarios WHERE id = :id");
                $stmt->bindParam(':id', $transaction['deudor_id']);
                $stmt->execute();
                $transaction['deudor_nombre'] = $stmt->fetchColumn();

                // Obtener nombre del acreedor
                $stmt = $this->db->prepare("SELECT nombre FROM usuarios WHERE id = :id");
                $stmt->bindParam(':id', $transaction['acreedor_id']);
                $stmt->execute();
                $transaction['acreedor_nombre'] = $stmt->fetchColumn();
            }

            return ["success" => true, "data" => $transactions];

        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return ["success" => false, "message" => "Error en el servidor"];
        }
    }

    private function getUserNameById($userId) {
        $query = "SELECT nombre FROM usuarios WHERE id = :userId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['nombre'];
    }

    public function markAsPaid($deudorId, $acreedorId, $cantidad, $groupId) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();

            // Actualizar saldo del deudor
            $updateDeudorBalanceQuery = "UPDATE saldos SET cantidad = cantidad + :cantidad WHERE id_usuario = :userId AND id_grupo = :groupId";
            $updateDeudorBalanceStmt = $this->db->prepare($updateDeudorBalanceQuery);
            $updateDeudorBalanceStmt->bindParam(':cantidad', $cantidad);
            $updateDeudorBalanceStmt->bindParam(':userId', $deudorId);
            $updateDeudorBalanceStmt->bindParam(':groupId', $groupId);
            $updateDeudorBalanceStmt->execute();

            // Actualizar saldo del acreedor
            $updateAcreedorBalanceQuery = "UPDATE saldos SET cantidad = cantidad - :cantidad WHERE id_usuario = :userId AND id_grupo = :groupId";
            $updateAcreedorBalanceStmt = $this->db->prepare($updateAcreedorBalanceQuery);
            $updateAcreedorBalanceStmt->bindParam(':cantidad', $cantidad);
            $updateAcreedorBalanceStmt->bindParam(':userId', $acreedorId);
            $updateAcreedorBalanceStmt->bindParam(':groupId', $groupId);
            $updateAcreedorBalanceStmt->execute();

            // Confirmar transacción
            $this->db->commit();

            return ["success" => true, "message" => "Pago registrado exitosamente"];
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            error_log("Error al registrar el pago: " . $e->getMessage());
            return ["success" => false, "message" => "Error al registrar el pago"];
        }
    }
}
?>