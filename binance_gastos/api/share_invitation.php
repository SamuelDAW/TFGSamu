<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'];
$invitationCode = $data['invitationCode'];
$groupName = $data['groupName'];

$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'samuel.lozoya.bartolome@iesmariamoliner.com';
    $mail->Password = 'eocxotmjzxbexnvm';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Remitente y destinatarios
    $mail->setFrom('binance_gastos@admin.com', 'Binance Gastos');
    $mail->addAddress($email);

    // Contenido del correo
    $mail->isHTML(true);    
    $mail->Subject = 'Codigo de Invitacion para unirse a un Grupo';
    $mail->Body = "Hola,<br><br>Utiliza el siguiente código de invitación para unirte a nuestro grupo <strong> $groupName </strong>: <strong>$invitationCode</strong><br><br>Saludos,<br>Binance Gastos";

    // Enviar el correo
    $mail->send();
    $response = ['success' => true, 'message' => 'Correo enviado exitosamente'];
    error_log('Correo enviado exitosamente');
} catch (Exception $e) {
    $response = ['success' => false, 'message' => "Error al enviar el correo: {$mail->ErrorInfo}"];
    error_log("Error al enviar el correo: {$mail->ErrorInfo}");
}

header('Content-Type: application/json');
echo json_encode($response);