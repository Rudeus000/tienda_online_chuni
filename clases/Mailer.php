<?php

/**
 * Clase para envio de correo electrónico
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public function enviarEmail($email, $asunto, $cuerpo)
    {
        // Cargar configuración de Supabase que tiene todas las constantes necesarias
        if (!defined('MAIL_HOST')) {
            require_once __DIR__ . '/../config/supabase_config.php';
        }
        require  __DIR__ . '/../phpmailer/src/PHPMailer.php';
        require  __DIR__ . '/../phpmailer/src/SMTP.php';
        require  __DIR__ . '/../phpmailer/src/Exception.php';

        $mail = new PHPMailer(true);

        // Verificar que las constantes estén definidas
        if (empty(MAIL_HOST) || empty(MAIL_USER)) {
            error_log('Error: Las credenciales de correo no están configuradas (MAIL_HOST o MAIL_USER están vacíos)');
            return false;
        }

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                //Enable verbose debug output
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;                     //Configure el servidor SMTP para enviar
            $mail->SMTPAuth   = true;                          // Habilita la autenticación SMTP
            $mail->Username   = MAIL_USER;                     //Usuario SMTP
            $mail->Password   = MAIL_PASS;                     //Contraseña SMTP
            
            // Determinar el tipo de encriptación según el puerto
            if (MAIL_PORT == 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif (MAIL_PORT == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            
            $mail->Port       = MAIL_PORT;                     //Puerto TCP al que conectarse

            //Correo emisor y nombre
            $mail->setFrom(MAIL_USER, 'Tienda Online');
            //Correo receptor y nombre
            $mail->addAddress($email);

            //Contenido
            $mail->isHTML(true);   //Establecer el formato de correo electrónico en HTML
            $mail->Subject = $asunto; //Titulo del correo

            //Cuerpo del correo
            $mail->Body = mb_convert_encoding($cuerpo, 'ISO-8859-1', 'UTF-8');

            //Enviar correo
            return $mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar correo a {$email}: {$mail->ErrorInfo}");
            // No mostrar el error directamente al usuario, solo registrar en logs
            return false;
        }
    }
}
