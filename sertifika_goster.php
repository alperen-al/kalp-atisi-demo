<?php
session_start();
include 'db.php'; // Veritabanı bağlantısını da ekledik

if (!isset($_SESSION['last_pdf']) || !isset($_SESSION['user_id'])) {
    header("Location: profil.php");
    exit;
}

$pdf_path = $_SESSION['last_pdf'];
$user_id = $_SESSION['user_id'];
unset($_SESSION['last_pdf']);

if (!file_exists($pdf_path)) {
    echo "<p style='color:red; text-align:center;'>Sertifika bulunamadı: $pdf_path</p>";
    exit;
}

// Dosya adı ve bilgiler
$cert_file = basename($pdf_path);
$cert_title = "Sistem Sertifikası";
$upload_date = date("Y-m-d");

// Daha önce aynı dosya eklenmiş mi kontrol et
$stmt = $conn->prepare("SELECT COUNT(*) FROM certificates WHERE user_id = ? AND cert_file = ?");
$stmt->bind_param("is", $user_id, $cert_file);
$stmt->execute();
$stmt->bind_result($exists);
$stmt->fetch();
$stmt->close();

if ($exists == 0) {
    // Veritabanına ekle
    $stmt = $conn->prepare("INSERT INTO certificates (user_id, cert_title, cert_file, upload_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $cert_title, $cert_file, $upload_date);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sertifikanız Hazır</title>
  <script>
    window.onload = function() {
      const newTab = window.open("<?= $pdf_path ?>", "_blank");
      if (newTab) {
        document.getElementById("durum").innerText = "Sertifikanız yeni sekmede açıldı. Yönlendiriliyorsunuz...";
        setTimeout(() => window.location.href = "profil.php", 3000);
      } else {
        document.getElementById("durum").innerText = "Lütfen tarayıcınızdan açılır pencere iznine izin verin.";
      }
    };
  </script>
</head>
<body>
  <p id="durum" style="text-align:center; font-size:18px; margin-top:40px;">Sertifikanız hazırlanıyor, lütfen bekleyiniz...</p>
</body>
</html>
