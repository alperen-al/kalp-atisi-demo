<?php
session_start();
include 'db.php';

if (empty($_GET['provider']) || empty($_GET['code']) || $_GET['provider'] !== 'google') {
    header('Location: login.php');
    exit;
}

$clientId     = '***********************';
$clientSecret = '*****************';
$redirectUri  = 'http://localhost/kalp_proje/oauth_callback.php?provider=google';
$code         = $_GET['code'];

// 1) Access token al
$tokenRes = file_get_contents("https://oauth2.googleapis.com/token", false, stream_context_create([
  'http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
    'content' => http_build_query([
      'code'          => $code,
      'client_id'     => $clientId,
      'client_secret' => $clientSecret,
      'redirect_uri'  => $redirectUri,
      'grant_type'    => 'authorization_code'
    ]),
  ]
]));

$tokenData = json_decode($tokenRes, true);
$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    exit('Token alınamadı.');
}

// 2) Kullanıcı bilgisi al
$userRes = file_get_contents("https://www.googleapis.com/oauth2/v2/userinfo?access_token={$accessToken}");
$user    = json_decode($userRes, true);
$email   = $user['email']   ?? null;
$name    = $user['name']    ?? null;
$photo   = $user['picture'] ?? null;

if (empty($email)) {
    exit('E-posta alınamadı.');
}

// 3) Var olan kullanıcıyı bul
$userId = null;
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userId);
$stmt->fetch();
$stmt->close();

if (!$userId) {
    // 4) Yeni kullanıcı oluştur
    function generateRefCode($length = 8) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }

    // Benzersiz ref_code üret
    do {
        $refCode = generateRefCode();
        $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE ref_code = ?");
        $check->bind_param("s", $refCode);
        $check->execute();
        $check->bind_result($exists);
        $check->fetch();
        $check->close();
    } while ($exists > 0);

    $pwHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);

    $ins = $conn->prepare("
        INSERT INTO users (username, email, password_hash, profile_photo, account_type, ref_code, full_name)
        VALUES (?, ?, ?, ?, 'bireysel', ?, ?)
    ");
    $ins->bind_param("ssssss", $name, $email, $pwHash, $photo, $refCode, $name);
    $ins->execute();
    $userId = $ins->insert_id;
    $ins->close();
} else {
    // ✅ Mevcut kullanıcıysa profil fotoğrafını güncelle
    $upd = $conn->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
    $upd->bind_param("si", $photo, $userId);
    $upd->execute();
    $upd->close();
}

// 5) Oturumu başlat
$_SESSION['user_id'] = $userId;
header("Location: tr.php");
exit;
