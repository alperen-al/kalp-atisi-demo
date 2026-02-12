<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  echo "<pre>POST verileri:\n";
  print_r($_POST);
  echo "</pre>";

  $message_id = $_POST['message_id'] ?? null;
  $new_note = $_POST['new_note'] ?? '';
  $new_date = $_POST['new_date'] ?? '';
  $new_email = $_POST['new_email'] ?? '';

  if (!$message_id) {
    echo "Mesaj ID eksik!";
    exit();
  }

  // 1. messages tablosunu güncelle
  $stmt = $conn->prepare("UPDATE messages SET message_text = ?, send_date = ?, email = ? WHERE message_id = ?");
  if (!$stmt) {
    die("Sorgu hazırlanamadı: " . $conn->error);
  }

  $stmt->bind_param("sssi", $new_note, $new_date, $new_email, $message_id);
  $stmt->execute();

  if ($stmt->affected_rows > 0) {
    echo "Mesaj tablosu güncellendi.<br>";
  } else {
    echo "Mesaj tablosu GÜNCELLENMEDİ!<br>";
  }
  $stmt->close();

  // 2. İlgili purchase_id'yi al
  $stmt2 = $conn->prepare("SELECT purchase_id FROM messages WHERE message_id = ?");
  $stmt2->bind_param("i", $message_id);
  $stmt2->execute();
  $stmt2->bind_result($purchase_id);
  $stmt2->fetch();
  $stmt2->close();

  if (!$purchase_id) {
    echo "Purchase ID bulunamadı!";
    exit();
  }

  // 3. purchases tablosunu güncelle
  $stmt3 = $conn->prepare("UPDATE purchases SET purchase_date = ? WHERE purchase_id = ?");
  $stmt3->bind_param("si", $new_date, $purchase_id);
  $stmt3->execute();

  if ($stmt3->affected_rows > 0) {
    echo "Purchase tablosu güncellendi.<br>";
  } else {
    echo "Purchase tablosu GÜNCELLENMEDİ!<br>";
  }
  $stmt3->close();

  // Yönlendirme
  header("Location: arkadaslar.php");
  exit();
}
?>
