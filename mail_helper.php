<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . "/phpmailer/src/Exception.php";
require_once __DIR__ . "/phpmailer/src/PHPMailer.php";
require_once __DIR__ . "/phpmailer/src/SMTP.php";
function send_mail($to, $subject, $html, &$err = null) {
    $cfg = require __DIR__ . "/mail_config.php";
    $m = new PHPMailer(true);
    try {
        $m->isSMTP();
        $m->Host = $cfg["host"];
        $m->SMTPAuth = true;
        $m->Username = $cfg["username"];
        $m->Password = $cfg["password"];
        $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $m->Port = $cfg["port"];
        $m->CharSet = "UTF-8";
        $m->setFrom($cfg["from_email"], $cfg["from_name"]);
        $m->addAddress($to);
        $m->isHTML(true);
        $m->Subject = $subject;
        $m->Body = $html;
        $m->AltBody = strip_tags($html);
        $m->send();
        return true;
    } catch (Exception $e) {
        $err = $m->ErrorInfo;
        return false;
    }
}
