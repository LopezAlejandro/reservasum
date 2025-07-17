<?php
// src/Utils/Mailer.php

namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    /**
     * Prepara y envía un correo electrónico.
     * @param string $toAddress
     * @param string $toName
     * @param string $subject
     * @param string $body
     * @return bool True si se envió, false si hubo un error.
     */
    public static function sendMail(string $toAddress, string $toName, string $subject, string $body): bool {
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            // Remitente y destinatarios
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($toAddress, $toName);
	    $mail->addCC('gwinkler@fadu.uba.ar');
	    $mail->addBCC('lopalejandro@gmail.com');
	    $mail->addBCC('miglesia@fadu.uba.ar');

            // Contenido
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // En un entorno real, este log es crucial.
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}