<?php

/**
 * Simpla CMS
 *
 * @copyright    2011 Denis Pikusov
 * @link        http://simplacms.ru
 * @author        Denis Pikusov
 *
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';


class Notify extends Simpla
{
    public $SMTPDebug = 0;

    function php_email($to, $subject, $message, $from = '', $reply_to = '')
    {
        $headers = "MIME-Version: 1.0\n";
        $headers .= "Content-type: text/html; charset=utf-8; \r\n";
        $headers .= "From: " . $this->settings->company_name . "<$from> \r\n";
        if (!empty($reply_to))
            $headers .= "reply-to: $reply_to\r\n";

        $subject = "=?utf-8?B?" . base64_encode($subject) . "?=";

        @mail($to, $subject, $message, $headers);
    }

    function email($to, $subject, $message, $from = '', $reply_to = '')
    {
        if (!$this->config->phpmailer_enable) {

            $this->php_mail($to, $subject, $message, $from, $reply_to);

        } else {

            $mailer = new Phpmailer();

            $mailer->IsHTML(true);
            $mailer->CharSet = "utf-8";

            $mailer->IsSMTP();
            $mailer->Host = $this->config->phpmailer_host;
            $mailer->Port = $this->config->phpmailer_port;

            if ($this->config->phpmailer_ssl == true) {
                $mailer->SMTPSecure = "ssl";
                $mailer->SMTPAutoTLS = "false";

                // Отключить проверку сертификата - https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting#updating-ca-certificates
                if ($this->config->phpmailer_ssl_verify != true) {
                    $mailer->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];
                }
            }

            $mailer->SMTPAuth = true;
            $mailer->Username = $this->config->phpmailer_user;
            $mailer->Password = $this->config->phpmailer_password;

            foreach(preg_split('/[\s,;]+/', $to) as $e) {
                $mailer->AddAddress($e);
            }
            
            $mailer->From = $mailer->Username; // $from
            $mailer->FromName = $this->settings->company_name;
            $mailer->Sender = $mailer->Username;
            $mailer->Subject = $subject;
            $mailer->Body = $message;
            $mailer->SMTPDebug = $this->SMTPDebug; // 3

            if (!$mailer->Send()) {

                if ($this->SMTPDebug) {
                    echo 'Message could not be sent.';
                    echo 'Mailer Error: ' . $mailer->ErrorInfo;
                }

                $this->php_email($to, $subject, $message, $mailer->From);
            }
        }
    }
}
