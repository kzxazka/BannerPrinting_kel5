<?php
if (!function_exists('sendHTMLEmail')) {
    function sendHTMLEmail($to, $subject, $message) {
        $headers  = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Danis Printing <no-reply@danisprinting.com>" . "\r\n";
        mail($to, $subject, $message, $headers);
    }
}
?>