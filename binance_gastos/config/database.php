<?php
class Database {
    private $host = "localhost";          // Dirección del servidor de la base de datos
    private $db_name = "binance_gastos";  // Nombre de la base de datos
    private $username = "root";           // Nombre de usuario de la base de datos
    private $password = "";               // Contraseña de la base de datos
    public $conn;

    // Establece la conexión a la base de datos
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");  // Configuración de UTF-8
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>