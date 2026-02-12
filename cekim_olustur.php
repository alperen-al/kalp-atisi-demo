<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini al
$stmt = $conn->prepare("SELECT full_name, ref_use_count FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $refCount);
$stmt->fetch();
$stmt->close();

// IBAN bilgilerini al ve temizle
$iban = strtoupper(trim($_POST['iban'] ?? ''));
$iban_name = strtolower(trim($_POST['iban_name'] ?? ''));  // IBAN sahibi adı
$user_name = strtolower(trim($full_name));

// ✅ IBAN formatı kontrolü
if (!preg_match('/^TR\d{24}$/', $iban)) {
  $_SESSION['cekim_durumu'] = 'gecersiz_iban';
  header("Location: profil.php");
  exit;
}

// ✅ Ad-soyad eşleşmesi
if ($iban_name !== $user_name) {
  $_SESSION['cekim_durumu'] = 'isim_uyusmuyor';
  header("Location: profil.php");
  exit;
}

// ✅ Kazanç hesapla
$toplam_hak_edilen = floor($refCount / 1000) * 750;
$kullaniciya_odenebilir = $toplam_hak_edilen * 0.10;

// Daha önce çekilen
$stmt = $conn->prepare("SELECT SUM(amount) FROM withdrawals WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cekilen_toplam);
$stmt->fetch();
$stmt->close();

$cekilen_toplam = $cekilen_toplam ?? 0;

// Çekilebilecek miktar
$cekilecek_miktar = $kullaniciya_odenebilir - $cekilen_toplam;

// ✅ Minimum 10$ limiti (istersen bu satırı da koy)
if ($cekilecek_miktar <= 0) {
  $_SESSION['cekim_durumu'] = 'yetersiz';
  header("Location: profil.php");
  exit;
}

// ✅ Çekim talebini kaydet
$stmt = $conn->prepare("
  INSERT INTO withdrawals (user_id, iban, amount, request_date, status) 
  VALUES (?, ?, ?, NOW(), 'bekliyor')
");
$stmt->bind_param("isd", $user_id, $iban, $cekilecek_miktar);
$stmt->execute();
$stmt->close();

// ✅ Başarıyla çekim oluşturuldu
$_SESSION['cekim_durumu'] = 'basarili';
header("Location: profil.php");
exit;
?>
