<?php
session_start();
include 'db.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: profil.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$cert_id = intval($_POST['cert_id'] ?? 0);

if ($cert_id > 0) {
  // Önce dosya adını çek
  $stmt = $conn->prepare("SELECT cert_file FROM certificates WHERE cert_id = ? AND user_id = ?");
  $stmt->bind_param("ii", $cert_id, $user_id);
  $stmt->execute();
  $stmt->bind_result($filename);
  if ($stmt->fetch()) {
    $stmt->close();
    // Veritabanından sil
    $del = $conn->prepare("DELETE FROM certificates WHERE cert_id = ? AND user_id = ?");
    $del->bind_param("ii", $cert_id, $user_id);
    if ($del->execute()) {
      // Fiziksel dosyayı da sil
      $filePath = __DIR__ . '/uploads/certificates/' . $filename;
      if (is_file($filePath)) {
        @unlink($filePath);
      }
    }
    $del->close();
  } else {
    $stmt->close();
  }
}

// Profil sayfasına dön
header("Location: profil.php");
exit;
