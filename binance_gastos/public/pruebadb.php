<?php
// Incluye el archivo de configuración de la base de datos
include_once '../config/database.php';

// Crea una nueva instancia de la clase Database
$database = new Database();
$conn = $database->getConnection();

// Verifica si la conexión fue exitosa
if ($conn) {
    echo "Conexión a la base de datos establecida con éxito.";
} else {
    echo "Error al conectar con la base de datos.";
}
?>
