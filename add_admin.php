<?php
session_start();
include 'db.php';

// Admin bilgileri
$username = 'admin1dunyaninkalbiatiyor';
$email = 'dunyaninikalbiatiyor@gmail.com';
$password = 'Sendoz_81';
$accountType = 'admin';

// Şifreyi hashle
$hash = password_hash($password, PASSWORD_DEFAULT);

// Zaten var mı kontrol et
$chk = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$chk->bind_param("s", $username);
$chk->execute();
$chk->bind_result($count);
$chk->fetch();
$chk->close();

if ($count > 0) {
  echo "⚠️ Bu admin zaten kayıtlı.";
  exit;
}

// Yeni kullanıcıyı ekle
$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, account_type) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $email, $hash, $accountType);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  echo "✅ Admin başarıyla eklendi.";
} else {
  echo "❌ Admin eklenemedi.";
}

$stmt->close();
$conn->close();
?>
