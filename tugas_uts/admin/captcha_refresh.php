<?php
session_start();

function generateCaptcha($length = 3) {
    $characters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $captcha = '';
    for ($i = 0; $i < $length; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}

$captcha_text = generateCaptcha();
$_SESSION['captcha_text'] = $captcha_text;

header('Content-Type: application/json');
echo json_encode(['captcha' => $captcha_text]);
exit;
?>