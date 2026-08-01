<?php

declare(strict_types=1);

session_start();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametresCookie = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametresCookie['path'],
        $parametresCookie['domain'],
        $parametresCookie['secure'],
        $parametresCookie['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;