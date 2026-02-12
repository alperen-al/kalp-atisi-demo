<?php
session_start();

if (empty($_GET['provider'])) {
    header('Location: login.php');
    exit;
}

$provider = $_GET['provider'];

if ($provider !== 'google') {
    header('HTTP/1.1 400 Bad Request');
    echo "Desteklenmeyen provider: " . htmlspecialchars($provider);
    exit;
}

// Buraya kendi Google OAuth Client ID'nizi yazın
$clientId = '*************';

// Redirect URI, callback.php dosyanızın tam URL'si olmalı
$redirectUri = 'http://localhost/kalp_proje/oauth_callback.php?provider=google';

// İzin istediğiniz kapsamlar
$scope = urlencode('openid profile email');

$authUrl = "https://accounts.google.com/o/oauth2/v2/auth"
         . "?client_id={$clientId}"
         . "&redirect_uri={$redirectUri}"
         . "&response_type=code"
         . "&scope={$scope}";

header('Location: ' . $authUrl);
exit;
