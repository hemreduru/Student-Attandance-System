<?php
session_start();
ini_set('session.cookie_httponly', true);

// Function to generate captcha code
function generateCaptchaCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $captchaCode = '';
    for ($i = 0; $i < $length; $i++) {
        $captchaCode .= $characters[rand(0, $charactersLength - 1)];
    }
    return $captchaCode;
}

// Generate and store new captcha code in session
$captchaCode = generateCaptchaCode();
$_SESSION['captcha'] = $captchaCode;

// Return the new captcha code
echo $captchaCode;
?>
