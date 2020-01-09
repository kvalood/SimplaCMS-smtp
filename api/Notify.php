<?php

/**
 * Simpla CMS
 *
 * @copyright	2017 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
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

            $this->php_email($to, $subject, $message, $from, $reply_to);

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

	public function email_order_user($order_id)
	{
        if(!($order = $this->orders->get_order(intval($order_id))) || empty($order->email))
            return false;

        $purchases = $this->orders->get_purchases(array('order_id'=>$order->id));
        $this->design->assign('purchases', $purchases);

        $products_ids = array();
        $variants_ids = array();
        foreach($purchases as $purchase)
        {
            $products_ids[] = $purchase->product_id;
            $variants_ids[] = $purchase->variant_id;
        }

        $products = array();
        foreach($this->products->get_products(array('id'=>$products_ids)) as $p)
            $products[$p->id] = $p;

        $images = $this->products->get_images(array('product_id'=>$products_ids));
        foreach($images as $image)
            $products[$image->product_id]->images[] = $image;

        $variants = array();
        foreach($this->variants->get_variants(array('id'=>$variants_ids)) as $v)
        {
            $variants[$v->id] = $v;
            $products[$v->product_id]->variants[] = $v;
        }

        foreach($purchases as &$purchase)
        {
            if(!empty($products[$purchase->product_id]))
                $purchase->product = $products[$purchase->product_id];
            if(!empty($variants[$purchase->variant_id]))
                $purchase->variant = $variants[$purchase->variant_id];
        }

        // Способ доставки
        $delivery = $this->delivery->get_delivery($order->delivery_id);
        $this->design->assign('delivery', $delivery);

        $this->design->assign('order', $order);
        $this->design->assign('purchases', $purchases);

        // Отправляем письмо
        // Если в шаблон не передавалась валюта, передадим
        if ($this->design->smarty->getTemplateVars('currency') === null)
        {
            $this->design->assign('currency', current($this->money->get_currencies(array('enabled'=>1))));
        }
        $email_template = $this->design->fetch($this->config->root_dir.'design/'.$this->settings->theme.'/html/email_order.tpl');
        $subject = $this->design->get_var('subject');
        $this->email($order->email, $subject, $email_template, $this->settings->notify_from_email);
	
	}

    /**
     * @param  int $order_id
     * @return bool
     */
    public function email_order_admin($order_id)
    {
        if (!($order = $this->orders->get_order(intval($order_id)))) {
            return false;
        }

        $purchases = $this->orders->get_purchases(array('order_id'=>$order->id));
        $this->design->assign('purchases', $purchases);

        $products_ids = array();
        foreach ($purchases as $purchase) {
            $products_ids[] = $purchase->product_id;
        }

        $products = $this->products->get_products_compile(array('id'=>$products_ids, 'limit' => count($products_ids)));

        foreach ($purchases as &$purchase) {
            if (!empty($products[$purchase->product_id])) {
                $purchase->product = $products[$purchase->product_id];

                if (!empty($products[$purchase->product_id]->variants[$purchase->variant_id])) {
                    $purchase->variant = $products[$purchase->product_id]->variants[$purchase->variant_id];
                }
            }
        }

        // Способ доставки
        $delivery = $this->delivery->get_delivery($order->delivery_id);
        $this->design->assign('delivery', $delivery);

        // Пользователь
        $user = $this->users->get_user(intval($order->user_id));
        $this->design->assign('user', $user);

        $this->design->assign('order', $order);
        $this->design->assign('purchases', $purchases);

        // В основной валюте
        $this->design->assign('main_currency', $this->money->get_currency());

        // Отправляем письмо
        $email_template = $this->design->fetch($this->config->root_dir.'simpla/design/html/email_order_admin.tpl');
        $subject = $this->design->get_var('subject');
        $this->email($this->settings->order_email, $subject, $email_template, $this->settings->notify_from_email);
    }


    /**
     * @param  int $comment_id
     * @return bool
     */
    public function email_comment_admin($comment_id)
    {
        if (!($comment = $this->comments->get_comment(intval($comment_id)))) {
            return false;
        }

        if ($comment->type == 'product') {
            $comment->product = $this->products->get_product(intval($comment->object_id));
        }
        if ($comment->type == 'blog') {
            $comment->post = $this->blog->get_post(intval($comment->object_id));
        }

        $this->design->assign('comment', $comment);

        // Отправляем письмо
        $email_template = $this->design->fetch($this->config->root_dir.'simpla/design/html/email_comment_admin.tpl');
        $subject = $this->design->get_var('subject');
        $this->email($this->settings->comment_email, $subject, $email_template, $this->settings->notify_from_email);
    }

    /**
     * @param  int $user_id
     * @param  string $code
     * @return bool
     */
    public function email_password_remind($user_id, $code)
    {
        if (!($user = $this->users->get_user(intval($user_id)))) {
            return false;
        }

        $this->design->assign('user', $user);
        $this->design->assign('code', $code);

        // Отправляем письмо
        $email_template = $this->design->fetch($this->config->root_dir.'design/'.$this->settings->theme.'/html/email_password_remind.tpl');
        $subject = $this->design->get_var('subject');
        $this->email($user->email, $subject, $email_template, $this->settings->notify_from_email);

        $this->design->smarty->clearAssign('user');
        $this->design->smarty->clearAssign('code');
    }

    /**
     * @param  int $feedback_id
     * @return bool
     */
    public function email_feedback_admin($feedback_id)
    {
        if (!($feedback = $this->feedbacks->get_feedback(intval($feedback_id)))) {
            return false;
        }

        $this->design->assign('feedback', $feedback);

        // Отправляем письмо
        $email_template = $this->design->fetch($this->config->root_dir.'simpla/design/html/email_feedback_admin.tpl');
        $subject = $this->design->get_var('subject');
        $this->email($this->settings->comment_email, $subject, $email_template, "$feedback->name <$feedback->email>", "$feedback->name <$feedback->email>");
    }
}
