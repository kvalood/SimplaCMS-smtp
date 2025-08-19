<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_errors','On');


require 'api/Simpla.php';
$simpla = new Simpla();

$simpla->notify->SMTPDebug = 3;
$simpla->notify->email('yourMail@mail.ru', 'Тема - тестовое сообщение', 'Тестируем SMTP');

if ($simpla->request->get('order_id', 'integer')) {
    $simpla->notify->email_order_admin($simpla->request->get('order_id', 'integer'));
}

if ($simpla->request->get('feedback_id', 'integer')) {
    $simpla->notify->email_feedback_admin($simpla->request->get('feedback_id', 'integer'));
}




?>
