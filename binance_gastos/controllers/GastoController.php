<?php
// En controllers/GastoController.php

include_once '../models/Gasto.php';
include_once '../models/Grupo.php';

class GastoController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function addExpense($userId, $groupId, $name, $amount, $participants) {
        $grupo = new Grupo($this->db);

        // Verificar si el usuario pertenece al grupo
        if (!$grupo->isUserInGroup($userId, $groupId)) {
            return ["success" => false, "message" => "No perteneces a este grupo"];
        }

        $gasto = new Gasto($this->db);
        if ($gasto->crear($groupId, $userId, $name, $amount, $participants)) {
            return ["success" => true, "message" => "Gasto agregado exitosamente"];
        } else {
            return ["success" => false, "message" => "Error al agregar el gasto"];
        }
    }

    public function getGroupExpenses($userId, $groupId) {
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
    }
}