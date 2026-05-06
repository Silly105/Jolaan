<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'i/PHPMailer/src/Exception.php';
require 'i/PHPMailer/src/PHPMailer.php';
require 'i/PHPMailer/src/SMTP.php';

function mail_senden($variable){
  $mail = new PHPMailer;
  $mail->setFrom('con@westliches-siegel.de', 'Westorga');
  $mail->addAddress($variable['to']);
  $mail->Subject = $variable['subject'];
  $mail->isHTML(true);
  $mail->Body = $variable['htmlmessage'];
  $mail->AltBody = $variable['altmessage'];
  
  if(!$mail->send()) {
    $message = 'Message was not sent. Mailer error: ' . $mail->ErrorInfo;
    return $message;
  } else {
    return 0;
  }  
}

?>