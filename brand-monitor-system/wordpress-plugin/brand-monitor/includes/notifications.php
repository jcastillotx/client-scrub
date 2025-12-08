<?php
class Brand_Monitor_Notifications {
    public static function send_email_alert($to, $subject, $message, $headers = array()) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        wp_mail($to, $subject, $message, $headers);
    }
}
